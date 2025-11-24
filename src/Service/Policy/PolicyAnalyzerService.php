<?php

declare(strict_types=1);

namespace App\Service\Policy;

use App\Dto\PolicyAnalysisRequest;
use App\Dto\PolicyAnalysisResponse;
use App\Exception\PolicyAnalysisException;
use App\Service\Ai\OpenAiClient;
use App\Service\Ai\OpenAiToolSchemaFactory;
use Psr\Log\LoggerInterface;
use Throwable;

final readonly class PolicyAnalyzerService
{
    public function __construct(
        private OpenAiClient             $client,
        private OpenAiToolSchemaFactory  $toolSchemaFactory,
        private PolicyPromptBuilder      $promptBuilder,
        private PolicyResponseNormalizer $normalizer,
        private LoggerInterface          $logger,
    ) {}

    /**
     * @throws PolicyAnalysisException
     */
    public function analyze(PolicyAnalysisRequest $request): PolicyAnalysisResponse
    {
        try {
            // 1. Build messages (system + user)
            $messages = $this->promptBuilder->buildMessages($request);

            // 2. Build OpenAI function-calling tools
            $tools = $this->toolSchemaFactory->createPolicyAnalysisTools();

            // 3. Call OpenAI client
            $result = $this->client->run($messages, $tools);

            // 4. Normalize the result (map to DTO)
            return $this->normalizer->normalize($result);

        } catch (Throwable $e) {
            $this->logger->error('Policy analysis failed', [
                'exception' => $e->getMessage(),
            ]);

            throw new PolicyAnalysisException(
                'Failed to analyze insurance policy.',
                previous: $e
            );
        }
    }
}
