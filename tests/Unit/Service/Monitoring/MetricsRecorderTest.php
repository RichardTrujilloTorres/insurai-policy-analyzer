<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Monitoring;

use App\Service\Monitoring\MetricsRecorder;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class MetricsRecorderTest extends TestCase
{
    private LoggerInterface $logger;
    private MetricsRecorder $metricsRecorder;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->metricsRecorder = new MetricsRecorder($this->logger);
    }

    public function testRecordSuccessLogsWithDuration(): void
    {
        // Arrange
        $durationMs = 1234.56;

        // Assert
        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with(
                'metrics.policy_analysis.success',
                ['duration_ms' => $durationMs]
            );

        // Act
        $this->metricsRecorder->recordSuccess($durationMs);
    }

    public function testRecordSuccessWithMetadata(): void
    {
        // Arrange
        $durationMs = 500.0;
        $meta = [
            'tokens_used' => 1500,
            'model' => 'gpt-4o-mini'
        ];

        // Assert
        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with(
                'metrics.policy_analysis.success',
                [
                    'duration_ms' => $durationMs,
                    'tokens_used' => 1500,
                    'model' => 'gpt-4o-mini'
                ]
            );

        // Act
        $this->metricsRecorder->recordSuccess($durationMs, $meta);
    }

    public function testRecordSuccessWithEmptyMetadata(): void
    {
        // Arrange
        $durationMs = 750.25;

        // Assert
        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with(
                'metrics.policy_analysis.success',
                ['duration_ms' => $durationMs]
            );

        // Act
        $this->metricsRecorder->recordSuccess($durationMs, []);
    }

    public function testRecordSuccessWithComplexMetadata(): void
    {
        // Arrange
        $durationMs = 2000.0;
        $meta = [
            'tokens_used' => 2500,
            'model' => 'gpt-4o-mini',
            'policy_type' => 'health',
            'jurisdiction' => 'US',
            'user_id' => 'user-123',
            'request_id' => 'req-456'
        ];

        // Assert
        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with(
                'metrics.policy_analysis.success',
                array_merge(['duration_ms' => $durationMs], $meta)
            );

        // Act
        $this->metricsRecorder->recordSuccess($durationMs, $meta);
    }

    public function testRecordSuccessHandlesZeroDuration(): void
    {
        // Arrange
        $durationMs = 0.0;

        // Assert
        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with(
                'metrics.policy_analysis.success',
                ['duration_ms' => 0.0]
            );

        // Act
        $this->metricsRecorder->recordSuccess($durationMs);
    }

    public function testRecordSuccessHandlesVeryLargeDuration(): void
    {
        // Arrange
        $durationMs = 999999.99;

        // Assert
        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with(
                'metrics.policy_analysis.success',
                ['duration_ms' => $durationMs]
            );

        // Act
        $this->metricsRecorder->recordSuccess($durationMs);
    }

    public function testRecordSuccessHandlesPreciseDuration(): void
    {
        // Arrange
        $durationMs = 123.456789;

        // Assert
        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with(
                'metrics.policy_analysis.success',
                ['duration_ms' => $durationMs]
            );

        // Act
        $this->metricsRecorder->recordSuccess($durationMs);
    }

    public function testRecordFailureLogsWithDurationAndReason(): void
    {
        // Arrange
        $durationMs = 100.5;
        $reason = 'Rate limit exceeded';

        // Assert
        $this->logger
            ->expects($this->once())
            ->method('warning')
            ->with(
                'metrics.policy_analysis.failure',
                [
                    'duration_ms' => $durationMs,
                    'reason' => $reason
                ]
            );

        // Act
        $this->metricsRecorder->recordFailure($durationMs, $reason);
    }

    public function testRecordFailureWithMetadata(): void
    {
        // Arrange
        $durationMs = 250.0;
        $reason = 'Invalid API key';
        $meta = [
            'model' => 'gpt-4o-mini',
            'attempt' => 1
        ];

        // Assert
        $this->logger
            ->expects($this->once())
            ->method('warning')
            ->with(
                'metrics.policy_analysis.failure',
                [
                    'duration_ms' => $durationMs,
                    'reason' => $reason,
                    'model' => 'gpt-4o-mini',
                    'attempt' => 1
                ]
            );

        // Act
        $this->metricsRecorder->recordFailure($durationMs, $reason, $meta);
    }

    public function testRecordFailureWithEmptyMetadata(): void
    {
        // Arrange
        $durationMs = 300.0;
        $reason = 'Network timeout';

        // Assert
        $this->logger
            ->expects($this->once())
            ->method('warning')
            ->with(
                'metrics.policy_analysis.failure',
                [
                    'duration_ms' => $durationMs,
                    'reason' => $reason
                ]
            );

        // Act
        $this->metricsRecorder->recordFailure($durationMs, $reason, []);
    }

    public function testRecordFailureWithDifferentReasons(): void
    {
        $reasons = [
            'Rate limit exceeded',
            'Invalid API key',
            'Network timeout',
            'Service unavailable',
            'Invalid request format',
            'Authentication failed'
        ];

        foreach ($reasons as $reason) {
            // Arrange
            $logger = $this->createMock(LoggerInterface::class);
            $recorder = new MetricsRecorder($logger);

            // Assert
            $logger
                ->expects($this->once())
                ->method('warning')
                ->with(
                    'metrics.policy_analysis.failure',
                    [
                        'duration_ms' => 100.0,
                        'reason' => $reason
                    ]
                );

            // Act
            $recorder->recordFailure(100.0, $reason);
        }
    }

    public function testRecordFailureWithEmptyReason(): void
    {
        // Arrange
        $durationMs = 150.0;
        $reason = '';

        // Assert
        $this->logger
            ->expects($this->once())
            ->method('warning')
            ->with(
                'metrics.policy_analysis.failure',
                [
                    'duration_ms' => $durationMs,
                    'reason' => ''
                ]
            );

        // Act
        $this->metricsRecorder->recordFailure($durationMs, $reason);
    }

    public function testRecordFailureWithComplexMetadata(): void
    {
        // Arrange
        $durationMs = 500.0;
        $reason = 'Validation error';
        $meta = [
            'error_code' => 'VALIDATION_001',
            'field' => 'policyText',
            'user_id' => 'user-789',
            'request_id' => 'req-012',
            'retry_count' => 3
        ];

        // Assert
        $this->logger
            ->expects($this->once())
            ->method('warning')
            ->with(
                'metrics.policy_analysis.failure',
                [
                    'duration_ms' => $durationMs,
                    'reason' => $reason,
                    'error_code' => 'VALIDATION_001',
                    'field' => 'policyText',
                    'user_id' => 'user-789',
                    'request_id' => 'req-012',
                    'retry_count' => 3
                ]
            );

        // Act
        $this->metricsRecorder->recordFailure($durationMs, $reason, $meta);
    }

    public function testRecordSuccessUsesInfoLogLevel(): void
    {
        // Assert - Verify info() is called, not warning() or error()
        $this->logger
            ->expects($this->once())
            ->method('info');

        $this->logger
            ->expects($this->never())
            ->method('warning');

        $this->logger
            ->expects($this->never())
            ->method('error');

        // Act
        $this->metricsRecorder->recordSuccess(100.0);
    }

    public function testRecordFailureUsesWarningLogLevel(): void
    {
        // Assert - Verify warning() is called, not info() or error()
        $this->logger
            ->expects($this->once())
            ->method('warning');

        $this->logger
            ->expects($this->never())
            ->method('info');

        $this->logger
            ->expects($this->never())
            ->method('error');

        // Act
        $this->metricsRecorder->recordFailure(100.0, 'Test failure');
    }

    public function testMultipleSuccessRecordings(): void
    {
        // Assert
        $this->logger
            ->expects($this->exactly(3))
            ->method('info')
            ->with('metrics.policy_analysis.success', $this->anything());

        // Act
        $this->metricsRecorder->recordSuccess(100.0);
        $this->metricsRecorder->recordSuccess(200.0);
        $this->metricsRecorder->recordSuccess(300.0);
    }

    public function testMultipleFailureRecordings(): void
    {
        // Assert
        $this->logger
            ->expects($this->exactly(3))
            ->method('warning')
            ->with('metrics.policy_analysis.failure', $this->anything());

        // Act
        $this->metricsRecorder->recordFailure(100.0, 'Error 1');
        $this->metricsRecorder->recordFailure(200.0, 'Error 2');
        $this->metricsRecorder->recordFailure(300.0, 'Error 3');
    }

    public function testMixedSuccessAndFailureRecordings(): void
    {
        // Assert
        $this->logger
            ->expects($this->exactly(2))
            ->method('info');

        $this->logger
            ->expects($this->exactly(2))
            ->method('warning');

        // Act - Simulate realistic workflow
        $this->metricsRecorder->recordSuccess(150.0);
        $this->metricsRecorder->recordFailure(100.0, 'Rate limit');
        $this->metricsRecorder->recordSuccess(200.0);
        $this->metricsRecorder->recordFailure(50.0, 'Network error');
    }

    public function testMetadataCanOverwriteDuration(): void
    {
        // Arrange - Metadata overwrites duration_ms (this is how array_merge works)
        $durationMs = 1000.0;
        $meta = [
            'duration_ms' => 9999.0, // This WILL overwrite due to array_merge order
            'other_field' => 'value'
        ];

        // Assert - Metadata value wins due to array_merge(['duration_ms' => 1000.0], $meta)
        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with(
                'metrics.policy_analysis.success',
                [
                    'duration_ms' => 9999.0, // Metadata overwrites the parameter
                    'other_field' => 'value'
                ]
            );

        // Act
        $this->metricsRecorder->recordSuccess($durationMs, $meta);
    }

    public function testMetadataCanOverwriteReasonInFailure(): void
    {
        // Arrange - Metadata overwrites reason (this is how array_merge works)
        $durationMs = 500.0;
        $reason = 'Original reason';
        $meta = [
            'reason' => 'Fake reason', // This WILL overwrite due to array_merge order
            'other_field' => 'value'
        ];

        // Assert - Metadata value wins
        $this->logger
            ->expects($this->once())
            ->method('warning')
            ->with(
                'metrics.policy_analysis.failure',
                [
                    'duration_ms' => 500.0,
                    'reason' => 'Fake reason', // Metadata overwrites the parameter
                    'other_field' => 'value'
                ]
            );

        // Act
        $this->metricsRecorder->recordFailure($durationMs, $reason, $meta);
    }

    public function testRecordSuccessWithNestedMetadata(): void
    {
        // Arrange
        $durationMs = 800.0;
        $meta = [
            'tokens' => [
                'prompt' => 1000,
                'completion' => 500,
                'total' => 1500
            ],
            'model_info' => [
                'name' => 'gpt-4o-mini',
                'version' => '2024-01'
            ]
        ];

        // Assert - Nested structures should be preserved
        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with(
                'metrics.policy_analysis.success',
                [
                    'duration_ms' => $durationMs,
                    'tokens' => [
                        'prompt' => 1000,
                        'completion' => 500,
                        'total' => 1500
                    ],
                    'model_info' => [
                        'name' => 'gpt-4o-mini',
                        'version' => '2024-01'
                    ]
                ]
            );

        // Act
        $this->metricsRecorder->recordSuccess($durationMs, $meta);
    }

    public function testRecordFailureWithNestedMetadata(): void
    {
        // Arrange
        $durationMs = 300.0;
        $reason = 'API Error';
        $meta = [
            'error_details' => [
                'code' => 500,
                'message' => 'Internal Server Error',
                'trace_id' => 'abc-123'
            ]
        ];

        // Assert - Nested structures should be preserved
        $this->logger
            ->expects($this->once())
            ->method('warning')
            ->with(
                'metrics.policy_analysis.failure',
                [
                    'duration_ms' => $durationMs,
                    'reason' => $reason,
                    'error_details' => [
                        'code' => 500,
                        'message' => 'Internal Server Error',
                        'trace_id' => 'abc-123'
                    ]
                ]
            );

        // Act
        $this->metricsRecorder->recordFailure($durationMs, $reason, $meta);
    }

    public function testMetricMessageFormats(): void
    {
        // Test that metric messages follow consistent naming

        // Assert - Check message format for success
        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with(
                $this->matchesRegularExpression('/^metrics\.\w+\.\w+$/'),
                $this->anything()
            );

        // Act
        $this->metricsRecorder->recordSuccess(100.0);
    }
}
