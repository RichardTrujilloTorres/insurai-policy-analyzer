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

final class PolicyAnalyzerServiceTest extends TestCase
{
    private OpenAiClient $mockClient;
    private OpenAiToolSchemaFactory $mockToolSchemaFactory;
    private PolicyPromptBuilder $mockPromptBuilder;
    private PolicyResponseNormalizer $mockNormalizer;
    private RequestLogger $mockLogger;
    private PolicyAnalyzerService $service;

    protected function setUp(): void
    {
        $this->mockClient = $this->createMock(OpenAiClient::class);
        $this->mockToolSchemaFactory = $this->createMock(OpenAiToolSchemaFactory::class);
        $this->mockPromptBuilder = $this->createMock(PolicyPromptBuilder::class);
        $this->mockNormalizer = $this->createMock(PolicyResponseNormalizer::class);
        $this->mockLogger = $this->createMock(RequestLogger::class);

        $this->service = new PolicyAnalyzerService(
            $this->mockClient,
            $this->mockToolSchemaFactory,
            $this->mockPromptBuilder,
            $this->mockNormalizer,
            $this->mockLogger,
        );
    }

    public function testAnalyzeSuccessfullyProcessesRequest(): void
    {
        $request = new PolicyAnalysisRequest();
        $request->policyText = 'Sample policy text';
        $request->policyType = 'health';

        $messages = [
            ['role' => 'system', 'content' => 'System prompt'],
            ['role' => 'user', 'content' => 'User prompt'],
        ];
        $tools = [['type' => 'function', 'function' => ['name' => 'analyze_policy']]];
        $openAiResult = ['some' => 'result'];
        $expectedResponse = $this->createMock(PolicyAnalysisResponse::class);

        $this->mockPromptBuilder
            ->expects($this->once())
            ->method('buildMessages')
            ->with($request)
            ->willReturn($messages);

        $this->mockToolSchemaFactory
            ->expects($this->once())
            ->method('createPolicyAnalysisTools')
            ->willReturn($tools);

        $this->mockClient
            ->expects($this->once())
            ->method('getModelName')
            ->willReturn('gpt-4o-mini');

        $this->mockClient
            ->expects($this->once())
            ->method('run')
            ->with($messages, $tools)
            ->willReturn($openAiResult);

        $this->mockNormalizer
            ->expects($this->once())
            ->method('normalize')
            ->with($openAiResult)
            ->willReturn($expectedResponse);

        $this->mockLogger
            ->expects($this->once())
            ->method('logIncomingRequest');

        $this->mockLogger
            ->expects($this->once())
            ->method('logOpenAiCall');

        $this->mockLogger
            ->expects($this->once())
            ->method('logOpenAiSuccess');

        $result = $this->service->analyze($request);

        $this->assertSame($expectedResponse, $result);
    }

    public function testAnalyzeThrowsExceptionOnFailure(): void
    {
        $request = new PolicyAnalysisRequest();
        $request->policyText = 'Sample policy text';

        $this->mockPromptBuilder
            ->method('buildMessages')
            ->willReturn([]);

        $this->mockToolSchemaFactory
            ->method('createPolicyAnalysisTools')
            ->willReturn([]);

        $this->mockClient
            ->method('getModelName')
            ->willReturn('gpt-4o-mini');

        $this->mockClient
            ->method('run')
            ->willThrowException(new \RuntimeException('API Error'));

        $this->mockLogger
            ->expects($this->once())
            ->method('logOpenAiFailure')
            ->with('API Error');

        $this->expectException(PolicyAnalysisException::class);
        $this->expectExceptionMessage('Failed to analyze insurance policy.');

        $this->service->analyze($request);
    }

    public function testAnalyzeLogsMetadataWithoutPolicyText(): void
    {
        $request = new PolicyAnalysisRequest();
        $request->policyText = 'This should NOT be logged';
        $request->policyType = 'health';
        $request->jurisdiction = 'US';
        $request->language = 'en';
        $request->metadata = ['key' => 'value'];

        $this->mockPromptBuilder
            ->method('buildMessages')
            ->willReturn([]);

        $this->mockToolSchemaFactory
            ->method('createPolicyAnalysisTools')
            ->willReturn([]);

        $this->mockClient
            ->method('getModelName')
            ->willReturn('gpt-4o-mini');

        $this->mockClient
            ->method('run')
            ->willReturn([]);

        $this->mockNormalizer
            ->method('normalize')
            ->willReturn($this->createMock(PolicyAnalysisResponse::class));

        $this->mockLogger
            ->expects($this->once())
            ->method('logIncomingRequest')
            ->with($this->callback(function ($data) {
                // Ensure policyText is NOT in the logged data
                $this->assertArrayNotHasKey('policyText', $data);
                $this->assertArrayHasKey('policyType', $data);
                $this->assertArrayHasKey('jurisdiction', $data);
                $this->assertArrayHasKey('language', $data);
                $this->assertArrayHasKey('metadata', $data);

                return true;
            }));

        $this->service->analyze($request);
    }

    public function testServiceConstructorAcceptsDependencies(): void
    {
        $service = new PolicyAnalyzerService(
            $this->mockClient,
            $this->mockToolSchemaFactory,
            $this->mockPromptBuilder,
            $this->mockNormalizer,
            $this->mockLogger,
        );

        $this->assertInstanceOf(PolicyAnalyzerService::class, $service);
    }

    public function testAnalyzeCallsAllDependenciesInCorrectOrder(): void
    {
        $request = new PolicyAnalysisRequest();
        $request->policyText = 'Test';

        $callOrder = [];

        $this->mockLogger
            ->method('logIncomingRequest')
            ->willReturnCallback(function () use (&$callOrder) {
                $callOrder[] = 'logIncomingRequest';
            });

        $this->mockPromptBuilder
            ->method('buildMessages')
            ->willReturnCallback(function () use (&$callOrder) {
                $callOrder[] = 'buildMessages';

                return [];
            });

        $this->mockToolSchemaFactory
            ->method('createPolicyAnalysisTools')
            ->willReturnCallback(function () use (&$callOrder) {
                $callOrder[] = 'createTools';

                return [];
            });

        $this->mockClient
            ->method('getModelName')
            ->willReturn('gpt-4o-mini');

        $this->mockLogger
            ->method('logOpenAiCall')
            ->willReturnCallback(function () use (&$callOrder) {
                $callOrder[] = 'logOpenAiCall';
            });

        $this->mockClient
            ->method('run')
            ->willReturnCallback(function () use (&$callOrder) {
                $callOrder[] = 'run';

                return [];
            });

        $this->mockLogger
            ->method('logOpenAiSuccess')
            ->willReturnCallback(function () use (&$callOrder) {
                $callOrder[] = 'logOpenAiSuccess';
            });

        $this->mockNormalizer
            ->method('normalize')
            ->willReturnCallback(function () use (&$callOrder) {
                $callOrder[] = 'normalize';

                return $this->createMock(PolicyAnalysisResponse::class);
            });

        $this->service->analyze($request);

        $expectedOrder = [
            'logIncomingRequest',
            'buildMessages',
            'createTools',
            'logOpenAiCall',
            'run',
            'logOpenAiSuccess',
            'normalize',
        ];

        $this->assertEquals($expectedOrder, $callOrder);
    }

    public function testServiceIsFinal(): void
    {
        // Service is intentionally not final to allow mocking in integration tests
        $this->markTestSkipped('Service is not final to allow mocking in integration tests');
    }

    public function testAnalyzeHandlesNullableRequestFields(): void
    {
        $request = new PolicyAnalysisRequest();
        $request->policyText = 'Text';
        $request->policyType = null;
        $request->jurisdiction = null;
        $request->language = null;
        $request->metadata = null;

        $this->mockPromptBuilder
            ->method('buildMessages')
            ->willReturn([]);

        $this->mockToolSchemaFactory
            ->method('createPolicyAnalysisTools')
            ->willReturn([]);

        $this->mockClient
            ->method('getModelName')
            ->willReturn('gpt-4o-mini');

        $this->mockClient
            ->method('run')
            ->willReturn([]);

        $this->mockNormalizer
            ->method('normalize')
            ->willReturn($this->createMock(PolicyAnalysisResponse::class));

        $this->mockLogger
            ->expects($this->once())
            ->method('logIncomingRequest')
            ->with([
                'policyType' => null,
                'jurisdiction' => null,
                'language' => null,
                'metadata' => null,
            ]);

        $result = $this->service->analyze($request);

        $this->assertInstanceOf(PolicyAnalysisResponse::class, $result);
    }
}
