<?php

namespace App\Controller;

use App\Dto\PolicyAnalysisRequest;
use App\Service\Validation\RequestValidator;
use App\Service\Policy\PolicyAnalyzerService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

final class AnalyzePolicyController
{
    public function __construct(
        private SerializerInterface   $serializer,
        private PolicyAnalyzerService $analyzer,
        private RequestValidator      $validator
    ) {}

    #[Route('/analyze', name: 'analyze_policy', methods: ['POST'])]
    public function __invoke(
        #[MapRequestPayload] PolicyAnalysisRequest $dto
    ): JsonResponse
    {
        // Validate DTO
        $this->validator->validate($dto);

        // Run analysis
        $result = $this->analyzer->analyze($dto);

        // Return JSON response
        return new JsonResponse(
            json_decode($this->serializer->serialize($result, 'json'), true),
            200
        );
    }
}
