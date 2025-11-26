<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Logging;

use App\Service\Logging\RequestLogger;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class RequestLoggerTest extends TestCase
{
    private LoggerInterface $logger;
    private RequestLogger $requestLogger;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->requestLogger = new RequestLogger($this->logger);
    }

    public function testLogIncomingRequestLogsAllContextFields(): void
    {
        // Arrange
        $context = [
            'policyType' => 'health',
            'jurisdiction' => 'US',
            'language' => 'en',
            'metadata' => [
                'source' => 'upload',
                'userId' => 'user-123'
            ]
        ];

        // Assert
        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with(
                'Incoming policy analysis request',
                [
                    'policyType' => 'health',
                    'jurisdiction' => 'US',
                    'language' => 'en',
                    'metadata' => [
                        'source' => 'upload',
                        'userId' => 'user-123'
                    ]
                ]
            );

        // Act
        $this->requestLogger->logIncomingRequest($context);
    }

    public function testLogIncomingRequestHandlesMissingFields(): void
    {
        // Arrange
        $context = [
            'policyType' => 'auto'
            // Missing: jurisdiction, language, metadata
        ];

        // Assert - Missing fields should be null
        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with(
                'Incoming policy analysis request',
                [
                    'policyType' => 'auto',
                    'jurisdiction' => null,
                    'language' => null,
                    'metadata' => null
                ]
            );

        // Act
        $this->requestLogger->logIncomingRequest($context);
    }

    public function testLogIncomingRequestHandlesEmptyContext(): void
    {
        // Arrange
        $context = [];

        // Assert - All fields should be null
        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with(
                'Incoming policy analysis request',
                [
                    'policyType' => null,
                    'jurisdiction' => null,
                    'language' => null,
                    'metadata' => null
                ]
            );

        // Act
        $this->requestLogger->logIncomingRequest($context);
    }

    public function testLogIncomingRequestDoesNotLogPolicyText(): void
    {
        // Arrange - Context includes policy text (should not be logged)
        $context = [
            'policyText' => 'Sensitive policy information that should not be logged',
            'policyType' => 'health',
            'jurisdiction' => 'US',
            'language' => 'en'
        ];

        // Assert - policyText should NOT appear in logged data
        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with(
                'Incoming policy analysis request',
                $this->callback(function ($loggedContext) {
                    // Verify policyText is NOT in logged context
                    $this->assertArrayNotHasKey('policyText', $loggedContext);
                    return true;
                })
            );

        // Act
        $this->requestLogger->logIncomingRequest($context);
    }

    public function testLogIncomingRequestHandlesNullValues(): void
    {
        // Arrange
        $context = [
            'policyType' => null,
            'jurisdiction' => null,
            'language' => null,
            'metadata' => null
        ];

        // Assert
        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with(
                'Incoming policy analysis request',
                [
                    'policyType' => null,
                    'jurisdiction' => null,
                    'language' => null,
                    'metadata' => null
                ]
            );

        // Act
        $this->requestLogger->logIncomingRequest($context);
    }

    public function testLogIncomingRequestHandlesComplexMetadata(): void
    {
        // Arrange
        $context = [
            'policyType' => 'life',
            'jurisdiction' => 'CA',
            'language' => 'fr',
            'metadata' => [
                'source' => 'api',
                'userId' => 'user-456',
                'requestId' => 'req-789',
                'clientVersion' => '2.0.0',
                'nested' => [
                    'data' => 'value',
                    'more' => ['deeply', 'nested']
                ]
            ]
        ];

        // Assert - Complex metadata should be logged as-is
        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with(
                'Incoming policy analysis request',
                [
                    'policyType' => 'life',
                    'jurisdiction' => 'CA',
                    'language' => 'fr',
                    'metadata' => $context['metadata']
                ]
            );

        // Act
        $this->requestLogger->logIncomingRequest($context);
    }

    public function testLogOpenAiCallLogsModelName(): void
    {
        // Arrange
        $model = 'gpt-4o-mini';

        // Assert
        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with(
                'Calling OpenAI model',
                ['model' => $model]
            );

        // Act
        $this->requestLogger->logOpenAiCall($model);
    }

    public function testLogOpenAiCallHandlesDifferentModels(): void
    {
        $models = [
            'gpt-4o-mini',
            'gpt-4-turbo',
            'gpt-4',
            'gpt-3.5-turbo'
        ];

        foreach ($models as $model) {
            // Arrange - Create new logger mock for each iteration
            $logger = $this->createMock(LoggerInterface::class);
            $requestLogger = new RequestLogger($logger);

            // Assert
            $logger
                ->expects($this->once())
                ->method('info')
                ->with(
                    'Calling OpenAI model',
                    ['model' => $model]
                );

            // Act
            $requestLogger->logOpenAiCall($model);
        }
    }

    public function testLogOpenAiSuccessLogsSimpleMessage(): void
    {
        // Assert
        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with('OpenAI call succeeded');

        // Act
        $this->requestLogger->logOpenAiSuccess();
    }

    public function testLogOpenAiFailureLogsErrorMessage(): void
    {
        // Arrange
        $errorMessage = 'Rate limit exceeded';

        // Assert
        $this->logger
            ->expects($this->once())
            ->method('error')
            ->with(
                'OpenAI call failed',
                ['error' => $errorMessage]
            );

        // Act
        $this->requestLogger->logOpenAiFailure($errorMessage);
    }

    public function testLogOpenAiFailureHandlesDifferentErrorMessages(): void
    {
        $errorMessages = [
            'Rate limit exceeded',
            'Invalid API key',
            'Network timeout',
            'Service unavailable',
            'Invalid request format'
        ];

        foreach ($errorMessages as $errorMessage) {
            // Arrange - Create new logger mock for each iteration
            $logger = $this->createMock(LoggerInterface::class);
            $requestLogger = new RequestLogger($logger);

            // Assert
            $logger
                ->expects($this->once())
                ->method('error')
                ->with(
                    'OpenAI call failed',
                    ['error' => $errorMessage]
                );

            // Act
            $requestLogger->logOpenAiFailure($errorMessage);
        }
    }

    public function testLogOpenAiFailureHandlesEmptyErrorMessage(): void
    {
        // Arrange
        $errorMessage = '';

        // Assert
        $this->logger
            ->expects($this->once())
            ->method('error')
            ->with(
                'OpenAI call failed',
                ['error' => '']
            );

        // Act
        $this->requestLogger->logOpenAiFailure($errorMessage);
    }

    public function testCompleteLoggingFlow(): void
    {
        // Simulate a complete request flow

        // 1. Log incoming request
        $context = [
            'policyType' => 'health',
            'jurisdiction' => 'US',
            'language' => 'en',
            'metadata' => ['source' => 'web']
        ];

        $this->logger
            ->expects($this->exactly(3))
            ->method('info')
            ->willReturnCallback(function ($message) {
                static $callCount = 0;
                $callCount++;

                if ($callCount === 1) {
                    $this->assertSame('Incoming policy analysis request', $message);
                } elseif ($callCount === 2) {
                    $this->assertSame('Calling OpenAI model', $message);
                } elseif ($callCount === 3) {
                    $this->assertSame('OpenAI call succeeded', $message);
                }

                return true;
            });

        // Act - Simulate complete flow
        $this->requestLogger->logIncomingRequest($context);
        $this->requestLogger->logOpenAiCall('gpt-4o-mini');
        $this->requestLogger->logOpenAiSuccess();
    }

    public function testCompleteLoggingFlowWithFailure(): void
    {
        // Simulate a request flow that fails

        $context = [
            'policyType' => 'auto',
            'jurisdiction' => 'CA',
            'language' => 'en'
        ];

        // Expect 2 info logs (incoming + OpenAI call) and 1 error log (failure)
        $this->logger
            ->expects($this->exactly(2))
            ->method('info');

        $this->logger
            ->expects($this->once())
            ->method('error')
            ->with(
                'OpenAI call failed',
                ['error' => 'API timeout']
            );

        // Act - Simulate flow with failure
        $this->requestLogger->logIncomingRequest($context);
        $this->requestLogger->logOpenAiCall('gpt-4o-mini');
        $this->requestLogger->logOpenAiFailure('API timeout');
    }

    public function testLogIncomingRequestWithExtraUnexpectedFields(): void
    {
        // Arrange - Context with extra fields that should be ignored
        $context = [
            'policyType' => 'health',
            'jurisdiction' => 'US',
            'language' => 'en',
            'metadata' => ['source' => 'upload'],
            'unexpectedField1' => 'should not be logged',
            'unexpectedField2' => 'also should not be logged'
        ];

        // Assert - Only expected fields should be logged
        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with(
                'Incoming policy analysis request',
                $this->callback(function ($loggedContext) {
                    // Verify only expected fields are present
                    $expectedKeys = ['policyType', 'jurisdiction', 'language', 'metadata'];
                    $actualKeys = array_keys($loggedContext);

                    sort($expectedKeys);
                    sort($actualKeys);

                    $this->assertSame($expectedKeys, $actualKeys);
                    return true;
                })
            );

        // Act
        $this->requestLogger->logIncomingRequest($context);
    }

    public function testMultipleSuccessiveCallsToSameMethod(): void
    {
        // Test that the logger can be called multiple times

        $this->logger
            ->expects($this->exactly(3))
            ->method('info')
            ->with('OpenAI call succeeded');

        // Act
        $this->requestLogger->logOpenAiSuccess();
        $this->requestLogger->logOpenAiSuccess();
        $this->requestLogger->logOpenAiSuccess();
    }

    public function testLoggerUsesCorrectLogLevels(): void
    {
        // Arrange
        $context = ['policyType' => 'health'];

        // Assert - Verify log levels
        $this->logger
            ->expects($this->exactly(3))
            ->method('info'); // info level

        $this->logger
            ->expects($this->once())
            ->method('error'); // error level

        // Act
        $this->requestLogger->logIncomingRequest($context);
        $this->requestLogger->logOpenAiCall('gpt-4o-mini');
        $this->requestLogger->logOpenAiSuccess();
        $this->requestLogger->logOpenAiFailure('Test error');
    }
}
