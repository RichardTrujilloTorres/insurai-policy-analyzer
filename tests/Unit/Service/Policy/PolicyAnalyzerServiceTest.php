<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Policy;

use App\Dto\PolicyAnalysisRequest;
use App\Dto\PolicyAnalysisResponse;
use App\Exception\PolicyAnalysisException;
use App\Service\Ai\OpenAiClient;
use App\Service\Ai\OpenAiToolSchemaFactory;
use App\Service\Logging\RequestLogger;
use App\Service\Policy\PolicyAnalyzerService;
use App\Service\Policy\PolicyPromptBuilder;
use App\Service\Policy\PolicyResponseNormalizer;
use PHPUnit\Framework\TestCase;

class PolicyAnalyzerServiceTest extends TestCase
{
    private OpenAiClient $client;
    private OpenAiToolSchemaFactory $toolSchemaFactory;
    private PolicyPromptBuilder $promptBuilder;
    private PolicyResponseNormalizer $normalizer;
    private RequestLogger $requestLogger;
    private PolicyAnalyzerService $service;

    protected function setUp(): void
    {
        $this->client = $this->createMock(OpenAiClient::class);
        $this->toolSchemaFactory = $this->createMock(OpenAiToolSchemaFactory::class);
        $this->promptBuilder = $this->createMock(PolicyPromptBuilder::class);
        $this->normalizer = $this->createMock(PolicyResponseNormalizer::class);
        $this->requestLogger = $this->createMock(RequestLogger::class);

        $this->service = new PolicyAnalyzerService(
            $this->client,
            $this->toolSchemaFactory,
            $this->promptBuilder,
            $this->normalizer,
            $this->requestLogger
        );
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

    public function testAnalyzeLogsIncomingRequest(): void
    {
        $request = $this->createRequest('Sample policy', 'health', 'US', 'en');

        $this->requestLogger
            ->expects($this->once())
            ->method('logIncomingRequest')
            ->with([
                'policyType' => 'health',
                'jurisdiction' => 'US',
                'language' => 'en',
                'metadata' => null
            ]);

        $this->setupSuccessfulAnalysisMocks();
        $this->service->analyze($request);
    }

    public function testAnalyzeBuildsMessagesFromRequest(): void
    {
        $request = $this->createRequest('Sample policy', 'health', 'US', 'en');

        $this->promptBuilder
            ->expects($this->once())
            ->method('buildMessages')
            ->with($request)
            ->willReturn([
                ['role' => 'system', 'content' => 'System prompt'],
                ['role' => 'user', 'content' => 'User prompt']
            ]);

        $this->setupSuccessfulAnalysisMocks();
        $this->service->analyze($request);
    }

    public function testAnalyzeBuildsToolSchema(): void
    {
        $request = $this->createRequest('Sample policy', 'health', 'US', 'en');

        $tools = [
            ['type' => 'function', 'function' => ['name' => 'analyze_insurance_policy']]
        ];

        $this->toolSchemaFactory
            ->expects($this->once())
            ->method('createPolicyAnalysisTools')
            ->willReturn($tools);

        $this->setupSuccessfulAnalysisMocks($tools);
        $this->service->analyze($request);
    }

    public function testAnalyzeLogsOpenAiCall(): void
    {
        $request = $this->createRequest('Sample policy', 'health', 'US', 'en');

        $this->client
            ->method('getModelName')
            ->willReturn('gpt-4o-mini');

        $this->requestLogger
            ->expects($this->once())
            ->method('logOpenAiCall')
            ->with('gpt-4o-mini');

        $this->setupSuccessfulAnalysisMocks();
        $this->service->analyze($request);
    }

    public function testAnalyzeCallsOpenAiClientWithMessagesAndTools(): void
    {
        $request = $this->createRequest('Sample policy', 'health', 'US', 'en');

        $messages = [
            ['role' => 'system', 'content' => 'System prompt'],
            ['role' => 'user', 'content' => 'User prompt']
        ];

        $tools = [
            ['type' => 'function', 'function' => ['name' => 'analyze_insurance_policy']]
        ];

        $this->promptBuilder->method('buildMessages')->willReturn($messages);
        $this->toolSchemaFactory->method('createPolicyAnalysisTools')->willReturn($tools);

        $this->client
            ->expects($this->once())
            ->method('run')
            ->with($messages, $tools)
            ->willReturn([
                'coverage' => ['coverageType' => 'health', 'coverageAmount' => '$10,000', 'coverageBreakdown' => []],
                'deductibles' => [],
                'exclusions' => [],
                'riskLevel' => 'low',
                'requiredActions' => [],
                'flags' => ['needsLegalReview' => false, 'inconsistentClausesDetected' => false]
            ]);

        $this->setupLoggingMocks();
        $this->setupNormalizerMock();
        $this->service->analyze($request);
    }

    public function testAnalyzeLogsSuccess(): void
    {
        $request = $this->createRequest('Sample policy', 'health', 'US', 'en');

        $this->requestLogger
            ->expects($this->once())
            ->method('logOpenAiSuccess');

        $this->setupSuccessfulAnalysisMocks();
        $this->service->analyze($request);
    }

    public function testAnalyzeNormalizesResponse(): void
    {
        $request = $this->createRequest('Sample policy', 'health', 'US', 'en');

        $rawResult = [
            'coverage' => ['coverageType' => 'health', 'coverageAmount' => '$10,000', 'coverageBreakdown' => []],
            'deductibles' => [],
            'exclusions' => [],
            'riskLevel' => 'low',
            'requiredActions' => [],
            'flags' => ['needsLegalReview' => false, 'inconsistentClausesDetected' => false]
        ];

        $this->client->method('run')->willReturn($rawResult);

        $this->normalizer
            ->expects($this->once())
            ->method('normalize')
            ->with($rawResult)
            ->willReturn($this->createMock(PolicyAnalysisResponse::class));

        $this->setupLoggingMocks();
        $this->setupPromptBuilderMock();
        $this->setupToolSchemaFactoryMock();
        $this->service->analyze($request);
    }

    public function testAnalyzeReturnsPolicyAnalysisResponse(): void
    {
        $request = $this->createRequest('Sample policy', 'health', 'US', 'en');
        $expectedResponse = $this->createMock(PolicyAnalysisResponse::class);
        $this->normalizer->method('normalize')->willReturn($expectedResponse);
        $this->setupSuccessfulAnalysisMocks();
        $result = $this->service->analyze($request);
        $this->assertSame($expectedResponse, $result);
    }

    public function testAnalyzeLogsFailureWhenExceptionThrown(): void
    {
        $request = $this->createRequest('Sample policy', 'health', 'US', 'en');
        $exception = new \RuntimeException('OpenAI API error');

        $this->client->method('run')->willThrowException($exception);

        $this->requestLogger
            ->expects($this->once())
            ->method('logOpenAiFailure')
            ->with('OpenAI API error');

        $this->setupLoggingMocks(false);
        $this->setupPromptBuilderMock();
        $this->setupToolSchemaFactoryMock();

        $this->expectException(PolicyAnalysisException::class);
        $this->expectExceptionMessage('Failed to analyze insurance policy.');
        $this->service->analyze($request);
    }

    public function testAnalyzeThrowsPolicyAnalysisExceptionOnError(): void
    {
        $request = $this->createRequest('Sample policy', 'health', 'US', 'en');
        $this->client->method('run')->willThrowException(new \RuntimeException('Network error'));
        $this->setupLoggingMocks(false);
        $this->setupPromptBuilderMock();
        $this->setupToolSchemaFactoryMock();
        $this->expectException(PolicyAnalysisException::class);
        $this->service->analyze($request);
    }

    public function testAnalyzePreservesPreviousException(): void
    {
        $request = $this->createRequest('Sample policy', 'health', 'US', 'en');
        $originalException = new \RuntimeException('Original error');
        $this->client->method('run')->willThrowException($originalException);
        $this->setupLoggingMocks(false);
        $this->setupPromptBuilderMock();
        $this->setupToolSchemaFactoryMock();

        try {
            $this->service->analyze($request);
            $this->fail('Expected PolicyAnalysisException to be thrown');
        } catch (PolicyAnalysisException $e) {
            $this->assertSame($originalException, $e->getPrevious());
        }
    }

    public function testAnalyzeWithMetadata(): void
    {
        $metadata = ['source' => 'upload', 'userId' => 'user-123'];
        $request = $this->createRequest('Sample policy', 'health', 'US', 'en', $metadata);

        $this->requestLogger
            ->expects($this->once())
            ->method('logIncomingRequest')
            ->with([
                'policyType' => 'health',
                'jurisdiction' => 'US',
                'language' => 'en',
                'metadata' => $metadata
            ]);

        $this->setupSuccessfulAnalysisMocks();
        $this->service->analyze($request);
    }

    public function testAnalyzeCompleteWorkflow(): void
    {
        $request = $this->createRequest('Comprehensive health insurance policy', 'health', 'US', 'en');
        $callOrder = [];

        $this->requestLogger->method('logIncomingRequest')->willReturnCallback(function () use (&$callOrder) {
            $callOrder[] = 'logIncomingRequest';
        });

        $this->promptBuilder->method('buildMessages')->willReturnCallback(function () use (&$callOrder) {
            $callOrder[] = 'buildMessages';
            return [['role' => 'system', 'content' => 'System'], ['role' => 'user', 'content' => 'User']];
        });

        $this->toolSchemaFactory->method('createPolicyAnalysisTools')->willReturnCallback(function () use (&$callOrder) {
            $callOrder[] = 'createPolicyAnalysisTools';
            return [];
        });

        $this->requestLogger->method('logOpenAiCall')->willReturnCallback(function () use (&$callOrder) {
            $callOrder[] = 'logOpenAiCall';
        });

        $this->client->method('run')->willReturnCallback(function () use (&$callOrder) {
            $callOrder[] = 'run';
            return [
                'coverage' => ['coverageType' => 'health', 'coverageAmount' => '$10,000', 'coverageBreakdown' => []],
                'deductibles' => [],
                'exclusions' => [],
                'riskLevel' => 'low',
                'requiredActions' => [],
                'flags' => ['needsLegalReview' => false, 'inconsistentClausesDetected' => false]
            ];
        });

        $this->requestLogger->method('logOpenAiSuccess')->willReturnCallback(function () use (&$callOrder) {
            $callOrder[] = 'logOpenAiSuccess';
        });

        $this->normalizer->method('normalize')->willReturnCallback(function () use (&$callOrder) {
            $callOrder[] = 'normalize';
            return $this->createMock(PolicyAnalysisResponse::class);
        });

        $this->client->method('getModelName')->willReturn('gpt-4o-mini');
        $this->service->analyze($request);

        $expectedOrder = [
            'logIncomingRequest',
            'buildMessages',
            'createPolicyAnalysisTools',
            'logOpenAiCall',
            'run',
            'logOpenAiSuccess',
            'normalize'
        ];

        $this->assertSame($expectedOrder, $callOrder);
    }

    public function testServiceIsReadonly(): void
    {
        $reflection = new \ReflectionClass(PolicyAnalyzerService::class);
        $this->assertTrue($reflection->isReadOnly(), 'PolicyAnalyzerService should be readonly');
    }

    public function testServiceIsFinal(): void
    {
        $reflection = new \ReflectionClass(PolicyAnalyzerService::class);
        $this->assertTrue($reflection->isFinal(), 'PolicyAnalyzerService should be final');
    }

    private function setupSuccessfulAnalysisMocks(array $tools = []): void
    {
        $this->setupLoggingMocks();
        $this->setupPromptBuilderMock();
        $this->setupToolSchemaFactoryMock($tools);
        $this->setupClientMock();
        $this->setupNormalizerMock();
    }

    private function setupLoggingMocks(bool $includeSuccess = true): void
    {
        $this->requestLogger->method('logIncomingRequest');
        $this->requestLogger->method('logOpenAiCall');
        if ($includeSuccess) {
            $this->requestLogger->method('logOpenAiSuccess');
        }
    }

    private function setupPromptBuilderMock(): void
    {
        $this->promptBuilder
            ->method('buildMessages')
            ->willReturn([
                ['role' => 'system', 'content' => 'System prompt'],
                ['role' => 'user', 'content' => 'User prompt']
            ]);
    }

    private function setupToolSchemaFactoryMock(array $tools = []): void
    {
        if (empty($tools)) {
            $tools = [['type' => 'function', 'function' => ['name' => 'analyze_insurance_policy']]];
        }
        $this->toolSchemaFactory->method('createPolicyAnalysisTools')->willReturn($tools);
    }

    private function setupClientMock(): void
    {
        $this->client->method('getModelName')->willReturn('gpt-4o-mini');
        $this->client->method('run')->willReturn([
            'coverage' => ['coverageType' => 'health', 'coverageAmount' => '$10,000', 'coverageBreakdown' => []],
            'deductibles' => [],
            'exclusions' => [],
            'riskLevel' => 'low',
            'requiredActions' => [],
            'flags' => ['needsLegalReview' => false, 'inconsistentClausesDetected' => false]
        ]);
    }

    private function setupNormalizerMock(): void
    {
        $this->normalizer->method('normalize')->willReturn($this->createMock(PolicyAnalysisResponse::class));
    }
}
