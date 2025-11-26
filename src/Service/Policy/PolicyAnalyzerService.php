<?php

declare(strict_types=1);

namespace App\Service\Policy;

use App\Dto\PolicyAnalysisRequest;
use App\Dto\PolicyAnalysisResponse;
use App\Exception\PolicyAnalysisException;
use App\Service\Ai\OpenAiClient;
use App\Service\Ai\OpenAiToolSchemaFactory;
use App\Service\Logging\RequestLogger;

final readonly class PolicyAnalyzerService
{
    public function __construct(
        private OpenAiClient $client,
        private OpenAiToolSchemaFactory $toolSchemaFactory,
        private PolicyPromptBuilder $promptBuilder,
        private PolicyResponseNormalizer $normalizer,
        private RequestLogger $requestLogger,
    ) {
    }

    /**
     * @throws PolicyAnalysisException
     */
    public function analyze(PolicyAnalysisRequest $request): PolicyAnalysisResponse
    {
        try {
            // 1. Log metadata (never log raw text)
            $this->requestLogger->logIncomingRequest([
                'policyType'   => $request->policyType,
                'jurisdiction' => $request->jurisdiction,
                'language'     => $request->language,
                'metadata'     => $request->metadata,
            ]);

            // 2. Prepare system/user messages
            $messages = $this->promptBuilder->buildMessages($request);

            // 3. Build structured tool schema
            $tools = $this->toolSchemaFactory->createPolicyAnalysisTools();

            // 4. Log outbound OpenAI call
            $this->requestLogger->logOpenAiCall(
                model: $this->client->getModelName() // small helper you already have or we add
            );

            // 5. Call OpenAI
            $result = $this->client->run($messages, $tools);

            // 6. Log success
            $this->requestLogger->logOpenAiSuccess();

            // 7. Map JSON → DTO
            return $this->normalizer->normalize($result);

        } catch (\Throwable $e) {
            $this->requestLogger->logOpenAiFailure($e->getMessage());

            throw new PolicyAnalysisException('Failed to analyze insurance policy.', previous: $e);
        }
    }
}
