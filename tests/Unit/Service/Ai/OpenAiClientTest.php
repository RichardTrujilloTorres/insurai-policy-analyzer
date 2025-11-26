<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Ai;

use App\Exception\ExternalApiException;
use App\Service\Ai\OpenAiClient;
use App\Service\Ai\OpenAiModelConfig;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class OpenAiClientTest extends TestCase
{
    private HttpClientInterface $httpClient;
    private LoggerInterface $logger;
    private OpenAiModelConfig $config;
    private OpenAiClient $client;
    private string $apiKey = 'test-api-key-sk-1234567890';

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(HttpClientInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->config = new OpenAiModelConfig(
            model: 'gpt-4o-mini',
            temperature: 0.1,
            maxTokens: 2000
        );

        $this->client = new OpenAiClient(
            $this->httpClient,
            $this->logger,
            $this->config,
            $this->apiKey
        );
    }

    public function testGetModelNameReturnsConfiguredModel(): void
    {
        // Act
        $modelName = $this->client->getModelName();

        // Assert
        $this->assertSame('gpt-4o-mini', $modelName);
    }

    public function testRunSuccessfullyReturnsDecodedResponse(): void
    {
        // Arrange
        $messages = [
            ['role' => 'system', 'content' => 'You are an expert insurance policy analyzer.'],
            ['role' => 'user', 'content' => 'Analyze this policy...']
        ];

        $tools = [
            [
                'type' => 'function',
                'function' => [
                    'name' => 'analyze_insurance_policy',
                    'description' => 'Analyzes insurance policies',
                    'parameters' => ['type' => 'object']
                ]
            ]
        ];

        $expectedResponseData = [
            'coverage' => [
                'coverageType' => 'health',
                'coverageAmount' => '$10,000',
                'coverageBreakdown' => []
            ],
            'deductibles' => [
                ['type' => 'annual', 'amount' => '$1,000']
            ],
            'exclusions' => ['Pre-existing conditions'],
            'riskLevel' => 'medium',
            'requiredActions' => ['Review exclusions'],
            'flags' => [
                'needsLegalReview' => false,
                'inconsistentClausesDetected' => false
            ]
        ];

        $openAiApiResponse = [
            'choices' => [
                [
                    'message' => [
                        'tool_calls' => [
                            [
                                'function' => [
                                    'arguments' => json_encode($expectedResponseData)
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);
        $response->method('toArray')->willReturn($openAiApiResponse);

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                'https://api.openai.com/v1/chat/completions',
                $this->callback(function ($options) use ($messages, $tools) {
                    // Verify headers
                    $this->assertArrayHasKey('headers', $options);
                    $this->assertSame('Bearer test-api-key-sk-1234567890', $options['headers']['Authorization']);
                    $this->assertSame('application/json', $options['headers']['Content-Type']);

                    // Verify payload structure
                    $this->assertArrayHasKey('json', $options);
                    $payload = $options['json'];

                    $this->assertSame('gpt-4o-mini', $payload['model']);
                    $this->assertSame($messages, $payload['messages']);
                    $this->assertSame($tools, $payload['tools']);
                    $this->assertSame(0.1, $payload['temperature']);
                    $this->assertSame(2000, $payload['max_tokens']);

                    // Verify tool_choice
                    $this->assertArrayHasKey('tool_choice', $payload);
                    $this->assertSame('function', $payload['tool_choice']['type']);
                    $this->assertSame('analyze_insurance_policy', $payload['tool_choice']['function']['name']);

                    return true;
                })
            )
            ->willReturn($response);

        // Act
        $result = $this->client->run($messages, $tools);

        // Assert
        $this->assertSame($expectedResponseData, $result);
    }

    public function testRunThrowsExceptionOnNon200StatusCode(): void
    {
        // Arrange
        $messages = [['role' => 'user', 'content' => 'test']];
        $tools = [['type' => 'function']];

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(429);

        $this->httpClient
            ->method('request')
            ->willReturn($response);

        // Assert
        $this->expectException(ExternalApiException::class);
        $this->expectExceptionMessage('OpenAI request failed: OpenAI returned non-200: 429');

        // Act
        $this->client->run($messages, $tools);
    }

    public function testRunThrowsExceptionOnUnexpectedResponseStructure(): void
    {
        // Arrange
        $messages = [['role' => 'user', 'content' => 'test']];
        $tools = [['type' => 'function']];

        $malformedResponse = [
            'choices' => [
                [
                    'message' => [
                        'content' => 'Some text response without tool_calls'
                    ]
                ]
            ]
        ];

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);
        $response->method('toArray')->willReturn($malformedResponse);

        $this->httpClient
            ->method('request')
            ->willReturn($response);

        // Assert
        $this->expectException(ExternalApiException::class);
        $this->expectExceptionMessage('OpenAI request failed: Unexpected OpenAI response structure');

        // Act
        $this->client->run($messages, $tools);
    }

    public function testRunThrowsExceptionOnInvalidJson(): void
    {
        // Arrange
        $messages = [['role' => 'user', 'content' => 'test']];
        $tools = [['type' => 'function']];

        $responseWithInvalidJson = [
            'choices' => [
                [
                    'message' => [
                        'tool_calls' => [
                            [
                                'function' => [
                                    'arguments' => '{invalid json syntax'
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);
        $response->method('toArray')->willReturn($responseWithInvalidJson);

        $this->httpClient
            ->method('request')
            ->willReturn($response);

        // Assert
        $this->expectException(ExternalApiException::class);
        $this->expectExceptionMessageMatches('/OpenAI request failed/');

        // Act
        $this->client->run($messages, $tools);
    }

    public function testRunThrowsExceptionOnHttpClientException(): void
    {
        // Arrange
        $messages = [['role' => 'user', 'content' => 'test']];
        $tools = [['type' => 'function']];

        $this->httpClient
            ->method('request')
            ->willThrowException(new \RuntimeException('Network connection failed'));

        // Assert
        $this->expectException(ExternalApiException::class);
        $this->expectExceptionMessage('OpenAI request failed: Network connection failed');

        // Act
        $this->client->run($messages, $tools);
    }

    public function testRunUsesCorrectApiUrl(): void
    {
        // Arrange
        $messages = [['role' => 'user', 'content' => 'test']];
        $tools = [['type' => 'function']];

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);
        $response->method('toArray')->willReturn([
            'choices' => [
                [
                    'message' => [
                        'tool_calls' => [
                            [
                                'function' => [
                                    'arguments' => '{"test": "data"}'
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ]);

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                'https://api.openai.com/v1/chat/completions',
                $this->anything()
            )
            ->willReturn($response);

        // Act
        $this->client->run($messages, $tools);
    }

    public function testRunSendsCorrectAuthorizationHeader(): void
    {
        // Arrange
        $messages = [['role' => 'user', 'content' => 'test']];
        $tools = [['type' => 'function']];

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);
        $response->method('toArray')->willReturn([
            'choices' => [
                [
                    'message' => [
                        'tool_calls' => [
                            [
                                'function' => [
                                    'arguments' => '{"test": "data"}'
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ]);

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                $this->anything(),
                $this->callback(function ($options) {
                    return isset($options['headers']['Authorization'])
                        && $options['headers']['Authorization'] === 'Bearer test-api-key-sk-1234567890';
                })
            )
            ->willReturn($response);

        // Act
        $this->client->run($messages, $tools);
    }

    public function testRunIncludesAllConfigurationParameters(): void
    {
        // Arrange
        $customConfig = new OpenAiModelConfig(
            model: 'gpt-4-turbo-preview',
            temperature: 0.5,
            maxTokens: 4000
        );

        $client = new OpenAiClient(
            $this->httpClient,
            $this->logger,
            $customConfig,
            $this->apiKey
        );

        $messages = [['role' => 'user', 'content' => 'test']];
        $tools = [['type' => 'function']];

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);
        $response->method('toArray')->willReturn([
            'choices' => [
                [
                    'message' => [
                        'tool_calls' => [
                            [
                                'function' => [
                                    'arguments' => '{"test": "data"}'
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ]);

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                $this->anything(),
                $this->callback(function ($options) {
                    $payload = $options['json'];
                    return $payload['model'] === 'gpt-4-turbo-preview'
                        && $payload['temperature'] === 0.5
                        && $payload['max_tokens'] === 4000;
                })
            )
            ->willReturn($response);

        // Act
        $client->run($messages, $tools);
    }

    public function testRunHandlesEmptyToolCallsArray(): void
    {
        // Arrange
        $messages = [['role' => 'user', 'content' => 'test']];
        $tools = [['type' => 'function']];

        $responseWithEmptyToolCalls = [
            'choices' => [
                [
                    'message' => [
                        'tool_calls' => []
                    ]
                ]
            ]
        ];

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);
        $response->method('toArray')->willReturn($responseWithEmptyToolCalls);

        $this->httpClient
            ->method('request')
            ->willReturn($response);

        // Assert
        $this->expectException(ExternalApiException::class);
        $this->expectExceptionMessage('Unexpected OpenAI response structure');

        // Act
        $this->client->run($messages, $tools);
    }

    public function testRunHandlesMissingChoicesArray(): void
    {
        // Arrange
        $messages = [['role' => 'user', 'content' => 'test']];
        $tools = [['type' => 'function']];

        $responseWithoutChoices = [
            'error' => [
                'message' => 'Invalid request',
                'type' => 'invalid_request_error'
            ]
        ];

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);
        $response->method('toArray')->willReturn($responseWithoutChoices);

        $this->httpClient
            ->method('request')
            ->willReturn($response);

        // Assert
        $this->expectException(ExternalApiException::class);
        $this->expectExceptionMessage('Unexpected OpenAI response structure');

        // Act
        $this->client->run($messages, $tools);
    }

    public function testRunPreservesComplexJsonStructure(): void
    {
        // Arrange
        $messages = [['role' => 'user', 'content' => 'test']];
        $tools = [['type' => 'function']];

        $complexData = [
            'coverage' => [
                'coverageType' => 'health',
                'coverageAmount' => '€1,000,000',
                'coverageBreakdown' => [
                    ['category' => 'medical', 'limit' => '€500,000'],
                    ['category' => 'dental', 'limit' => '€50,000']
                ]
            ],
            'deductibles' => [
                ['type' => 'annual', 'amount' => '€1,000'],
                ['type' => 'per_visit', 'amount' => '€50']
            ],
            'exclusions' => [
                'Pre-existing conditions',
                'Cosmetic procedures',
                'Experimental treatments'
            ],
            'riskLevel' => 'medium',
            'requiredActions' => [
                'Verify coverage limits',
                'Review exclusions carefully'
            ],
            'flags' => [
                'needsLegalReview' => true,
                'inconsistentClausesDetected' => false
            ]
        ];

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);
        $response->method('toArray')->willReturn([
            'choices' => [
                [
                    'message' => [
                        'tool_calls' => [
                            [
                                'function' => [
                                    'arguments' => json_encode($complexData)
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ]);

        $this->httpClient
            ->method('request')
            ->willReturn($response);

        // Act
        $result = $this->client->run($messages, $tools);

        // Assert
        $this->assertSame($complexData, $result);
        $this->assertIsArray($result['coverage']['coverageBreakdown']);
        $this->assertCount(2, $result['coverage']['coverageBreakdown']);
        $this->assertCount(3, $result['exclusions']);
    }
}
