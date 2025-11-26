<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Policy;

use App\Dto\PolicyAnalysisRequest;
use App\Service\Policy\PolicyPromptBuilder;
use PHPUnit\Framework\TestCase;

class PolicyPromptBuilderTest extends TestCase
{
    private PolicyPromptBuilder $builder;

    protected function setUp(): void
    {
        $this->builder = new PolicyPromptBuilder();
    }

    private function createRequest(string $policyText, string $policyType, string $jurisdiction, string $language, ?array $metadata = null): PolicyAnalysisRequest
    {
        $request = new PolicyAnalysisRequest();
        $request->policyText = $policyText;
        $request->policyType = $policyType;
        $request->jurisdiction = $jurisdiction;
        $request->language = $language;
        $request->metadata = $metadata;

        return $request;
    }

    public function testBuildMessagesReturnsArrayWithTwoMessages(): void
    {
        $request = $this->createRequest('Sample policy text', 'health', 'US', 'en');
        $messages = $this->builder->buildMessages($request);
        $this->assertIsArray($messages);
        $this->assertCount(2, $messages);
    }

    public function testBuildMessagesFirstMessageIsSystem(): void
    {
        $request = $this->createRequest('Sample policy text', 'health', 'US', 'en');
        $messages = $this->builder->buildMessages($request);
        $this->assertArrayHasKey('role', $messages[0]);
        $this->assertSame('system', $messages[0]['role']);
        $this->assertArrayHasKey('content', $messages[0]);
        $this->assertIsString($messages[0]['content']);
    }

    public function testBuildMessagesSecondMessageIsUser(): void
    {
        $request = $this->createRequest('Sample policy text', 'health', 'US', 'en');
        $messages = $this->builder->buildMessages($request);
        $this->assertArrayHasKey('role', $messages[1]);
        $this->assertSame('user', $messages[1]['role']);
        $this->assertArrayHasKey('content', $messages[1]);
        $this->assertIsString($messages[1]['content']);
    }

    public function testSystemMessageIncludesJurisdiction(): void
    {
        $request = $this->createRequest('Sample policy text', 'health', 'US', 'en');
        $messages = $this->builder->buildMessages($request);
        $this->assertStringContainsString('US', $messages[0]['content']);
        $this->assertStringContainsString('Jurisdiction:', $messages[0]['content']);
    }

    public function testSystemMessageIncludesLanguage(): void
    {
        $request = $this->createRequest('Sample policy text', 'health', 'CA', 'fr');
        $messages = $this->builder->buildMessages($request);
        $this->assertStringContainsString('fr', $messages[0]['content']);
        $this->assertStringContainsString('Language:', $messages[0]['content']);
    }

    public function testSystemMessageIncludesInstructions(): void
    {
        $request = $this->createRequest('Sample policy text', 'health', 'US', 'en');
        $messages = $this->builder->buildMessages($request);
        $systemContent = $messages[0]['content'];
        $this->assertStringContainsString('expert insurance policy analysis', $systemContent);
        $this->assertStringContainsString('extract coverage', $systemContent);
        $this->assertStringContainsString('exclusions', $systemContent);
        $this->assertStringContainsString('deductibles', $systemContent);
        $this->assertStringContainsString('risk level', $systemContent);
    }

    public function testUserMessageIncludesPolicyText(): void
    {
        $policyText = 'This is a comprehensive health insurance policy with specific terms and conditions.';
        $request = $this->createRequest($policyText, 'health', 'US', 'en');
        $messages = $this->builder->buildMessages($request);
        $this->assertStringContainsString($policyText, $messages[1]['content']);
    }

    public function testUserMessageIncludesPolicyType(): void
    {
        $request = $this->createRequest('Sample policy text', 'auto', 'US', 'en');
        $messages = $this->builder->buildMessages($request);
        $this->assertStringContainsString('auto', $messages[1]['content']);
        $this->assertStringContainsString('Policy Type:', $messages[1]['content']);
    }

    public function testUserMessageIncludesMetadataWhenProvided(): void
    {
        $metadata = ['source' => 'upload', 'userId' => 'user-123'];
        $request = $this->createRequest('Sample policy text', 'health', 'US', 'en', $metadata);
        $messages = $this->builder->buildMessages($request);
        $this->assertStringContainsString('Metadata:', $messages[1]['content']);
        $this->assertStringContainsString('upload', $messages[1]['content']);
        $this->assertStringContainsString('user-123', $messages[1]['content']);
    }

    public function testUserMessageHandlesNullMetadata(): void
    {
        $request = $this->createRequest('Sample policy text', 'health', 'US', 'en', null);
        $messages = $this->builder->buildMessages($request);
        $this->assertStringContainsString('Metadata:', $messages[1]['content']);
        $this->assertStringContainsString('{}', $messages[1]['content']);
    }

    public function testBuildMessagesWithDifferentPolicyTypes(): void
    {
        $policyTypes = ['health', 'auto', 'life', 'home', 'travel'];
        foreach ($policyTypes as $type) {
            $request = $this->createRequest("Sample {$type} policy", $type, 'US', 'en');
            $messages = $this->builder->buildMessages($request);
            $this->assertStringContainsString($type, $messages[1]['content']);
        }
    }

    public function testBuildMessagesWithDifferentJurisdictions(): void
    {
        $jurisdictions = ['US', 'CA', 'UK', 'EU', 'AU'];
        foreach ($jurisdictions as $jurisdiction) {
            $request = $this->createRequest('Sample policy', 'health', $jurisdiction, 'en');
            $messages = $this->builder->buildMessages($request);
            $this->assertStringContainsString($jurisdiction, $messages[0]['content']);
        }
    }

    public function testBuildMessagesWithDifferentLanguages(): void
    {
        $languages = ['en', 'fr', 'es', 'de', 'it'];
        foreach ($languages as $language) {
            $request = $this->createRequest('Sample policy', 'health', 'US', $language);
            $messages = $this->builder->buildMessages($request);
            $this->assertStringContainsString($language, $messages[0]['content']);
        }
    }

    public function testBuildMessagesWithLongPolicyText(): void
    {
        $longText = str_repeat('This is a policy clause. ', 1000);
        $request = $this->createRequest($longText, 'health', 'US', 'en');
        $messages = $this->builder->buildMessages($request);
        $this->assertStringContainsString($longText, $messages[1]['content']);
        $this->assertCount(2, $messages);
    }

    public function testBuildMessagesWithSpecialCharacters(): void
    {
        $policyText = 'Policy with "quotes", \'apostrophes\', & special <characters> €10,000.';
        $request = $this->createRequest($policyText, 'health', 'US', 'en');
        $messages = $this->builder->buildMessages($request);
        $this->assertStringContainsString($policyText, $messages[1]['content']);
    }

    public function testBuildMessagesWithComplexMetadata(): void
    {
        $metadata = [
            'source' => 'api',
            'userId' => 'user-456',
            'uploadDate' => '2024-01-15',
            'version' => '2.0',
            'nested' => ['key' => 'value'],
        ];
        $request = $this->createRequest('Sample policy', 'health', 'US', 'en', $metadata);
        $messages = $this->builder->buildMessages($request);
        $userContent = $messages[1]['content'];
        $this->assertStringContainsString('user-456', $userContent);
        $this->assertStringContainsString('2024-01-15', $userContent);
    }

    public function testSystemMessageFormatIsConsistent(): void
    {
        $request1 = $this->createRequest('Policy 1', 'health', 'US', 'en');
        $request2 = $this->createRequest('Policy 2', 'auto', 'CA', 'fr');
        $messages1 = $this->builder->buildMessages($request1);
        $messages2 = $this->builder->buildMessages($request2);
        $this->assertStringContainsString('Jurisdiction:', $messages1[0]['content']);
        $this->assertStringContainsString('Jurisdiction:', $messages2[0]['content']);
        $this->assertStringContainsString('Language:', $messages1[0]['content']);
        $this->assertStringContainsString('Language:', $messages2[0]['content']);
    }

    public function testUserMessageFormatIsConsistent(): void
    {
        $request1 = $this->createRequest('Policy 1', 'health', 'US', 'en');
        $request2 = $this->createRequest('Policy 2', 'life', 'UK', 'en');
        $messages1 = $this->builder->buildMessages($request1);
        $messages2 = $this->builder->buildMessages($request2);
        $this->assertStringContainsString('Policy Type:', $messages1[1]['content']);
        $this->assertStringContainsString('Policy Type:', $messages2[1]['content']);
        $this->assertStringContainsString('Metadata:', $messages1[1]['content']);
        $this->assertStringContainsString('Metadata:', $messages2[1]['content']);
        $this->assertStringContainsString('Policy Text:', $messages1[1]['content']);
        $this->assertStringContainsString('Policy Text:', $messages2[1]['content']);
    }

    public function testMessagesAreImmutableBetweenCalls(): void
    {
        $request = $this->createRequest('Sample policy', 'health', 'US', 'en');
        $messages1 = $this->builder->buildMessages($request);
        $messages2 = $this->builder->buildMessages($request);
        $this->assertEquals($messages1, $messages2);
        $messages1[0]['content'] = 'modified';
        $this->assertNotEquals($messages1[0]['content'], $messages2[0]['content']);
    }
}
