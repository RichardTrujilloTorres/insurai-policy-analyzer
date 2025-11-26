<?php

declare(strict_types=1);

namespace App\Service\Logging;

use Psr\Log\LoggerInterface;

class RequestLogger
{
    public function __construct(
        private LoggerInterface $logger
    ) {}

    /**
     * Log sanitized request + metadata before sending to OpenAI.
     *
     * NOTE: We do not log raw policy text for privacy/security.
     *       Only metadata and contextual info.
     */
    public function logIncomingRequest(array $context): void
    {
        $this->logger->info('Incoming policy analysis request', [
            'policyType'   => $context['policyType']   ?? null,
            'jurisdiction' => $context['jurisdiction'] ?? null,
            'language'     => $context['language']     ?? null,
            'metadata'     => $context['metadata']     ?? null,
        ]);
    }

    /**
     * Log key info about the OpenAI call.
     */
    public function logOpenAiCall(string $model): void
    {
        $this->logger->info('Calling OpenAI model', [
            'model' => $model,
        ]);
    }

    /**
     * Log a successful OpenAI result in high level.
     */
    public function logOpenAiSuccess(): void
    {
        $this->logger->info('OpenAI call succeeded');
    }

    /**
     * Log an OpenAI failure (handled upstream).
     */
    public function logOpenAiFailure(string $message): void
    {
        $this->logger->error('OpenAI call failed', [
            'error' => $message,
        ]);
    }
}
