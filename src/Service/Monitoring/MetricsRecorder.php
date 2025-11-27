<?php

namespace App\Service\Monitoring;

use Psr\Log\LoggerInterface;

/**
 * Minimal metrics recorder for AWS Lambda environments.
 *
 * We keep metrics simple:
 *  - duration
 *  - success/failure counts
 *  - token usage (optional)
 *
 * Logs are shipped to CloudWatch automatically.
 */
class MetricsRecorder
{
    public function __construct(
        private LoggerInterface $logger,
    ) {
    }

    /**
     * Record a successful analysis with timing + optional metadata.
     *
     * @param array<string, mixed> $meta
     */
    public function recordSuccess(float $durationMs, array $meta = []): void
    {
        $this->logger->info('metrics.policy_analysis.success', array_merge([
            'duration_ms' => $durationMs,
        ], $meta));
    }

    /**
     * Record a failure analysis with reason + timing.
     *
     * @param array<string, mixed> $meta
     */
    public function recordFailure(float $durationMs, string $reason, array $meta = []): void
    {
        $this->logger->warning('metrics.policy_analysis.failure', array_merge([
            'duration_ms' => $durationMs,
            'reason'      => $reason,
        ], $meta));
    }
}
