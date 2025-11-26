<?php

namespace App\Controller;

use App\Dto\PolicyAnalysisRequest;
use App\Service\Policy\PolicyAnalyzerService;
use App\Service\Validation\RequestValidator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

final class AnalyzePolicyController
{
    public function __construct(
        private SerializerInterface $serializer,
        private PolicyAnalyzerService $analyzer,
        private RequestValidator $validator,
    ) {
    }

    #[Route('/analyze', name: 'analyze_policy', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        // 1. Decode JSON request body → DTO
        /** @var PolicyAnalysisRequest $dto */
        $dto = $this->serializer->deserialize(
            $request->getContent(),
            PolicyAnalysisRequest::class,
            'json'
        );

        // 2. Validate DTO (throws on error)
        $this->validator->validate($dto);

        // 3. Run analysis
        $result = $this->analyzer->analyze($dto);

        // 4. Return JSON response
        return new JsonResponse(
            json_decode($this->serializer->serialize($result, 'json'), true),
            200
        );
    }
}
