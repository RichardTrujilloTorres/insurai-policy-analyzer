<?php

declare(strict_types=1);

namespace App\Tests\Stub;

use App\Service\Ai\OpenAiClient;

/**
 * Stub OpenAI client for integration tests - never makes real API calls
 */
class StubOpenAiClient extends OpenAiClient
{
    private array $mockResponse;

    public function __construct()
    {
        // Don't call parent constructor - we don't need real dependencies
        $this->mockResponse = [
            'coverage' => [
                'coverageType' => 'comprehensive',  // camelCase, not snake_case
                'coverageAmount' => '1000000',
                'coverageBreakdown' => [
                    'medical' => true,
                    'dental' => false,
                    'vision' => false,
                    'pharmacy' => true,
                ],
            ],
            'deductibles' => [
                'annual' => 5000,
                'perIncident' => 1000,
            ],
            'exclusions' => [
                'Pre-existing conditions',
                'Cosmetic procedures',
            ],
            'riskLevel' => 'medium',
            'requiredActions' => [
                'Review exclusions carefully',
                'Consider supplemental coverage',
            ],
            'flags' => [
                'needsLegalReview' => false,
                'inconsistentClausesDetected' => false,
            ],
        ];
    }

    public function setMockResponse(array $response): void
    {
        $this->mockResponse = $response;
    }

    public function getModelName(): string
    {
        return 'gpt-4o-mini';
    }

    public function run(array $messages, array $tools): array
    {
        return $this->mockResponse;
    }
}
