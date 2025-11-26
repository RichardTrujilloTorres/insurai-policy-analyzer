<?php

declare(strict_types=1);

namespace App\Tests\Mock;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use App\Service\Ai\OpenAiModelConfig;

/**
 * Mock OpenAI Client for testing
 * Mimics the real OpenAiClient constructor signature
 */
class MockOpenAiClient
{
    private array $mockResponse;

    public function __construct(
        HttpClientInterface $httpClient,
        LoggerInterface $logger,
        OpenAiModelConfig $config,
        string $openAiApiKey
    ) {
        // We don't use these dependencies in the mock
        // But we need them to match the real constructor signature

        $this->mockResponse = [
            'coverage' => [
                'coverageType' => 'health',
                'coverageAmount' => '$10,000',
                'coverageBreakdown' => [
                    ['category' => 'medical', 'limit' => '$5,000'],
                    ['category' => 'hospitalization', 'limit' => '$5,000']
                ]
            ],
            'deductibles' => [
                ['type' => 'annual', 'amount' => '$1,000']
            ],
            'exclusions' => [
                'Pre-existing conditions',
                'Cosmetic procedures'
            ],
            'riskLevel' => 'medium',
            'requiredActions' => [
                'Review policy terms carefully',
                'Verify coverage limits'
            ],
            'flags' => [
                'needsLegalReview' => false,
                'inconsistentClausesDetected' => false
            ]
        ];
    }

    public function getModelName(): string
    {
        return 'gpt-4o-mini-mock';
    }

    public function run(array $messages, array $tools): array
    {
        // Return mock response instead of calling OpenAI
        return $this->mockResponse;
    }

    public function setMockResponse(array $response): void
    {
        $this->mockResponse = $response;
    }
}
