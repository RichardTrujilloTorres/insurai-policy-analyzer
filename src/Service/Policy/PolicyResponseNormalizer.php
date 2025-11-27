<?php

namespace App\Service\Policy;

use App\Dto\Coverage;
use App\Dto\Flags;
use App\Dto\PolicyAnalysisResponse;

class PolicyResponseNormalizer
{
    /**
     * Normalize the raw JSON decoded array from OpenAI
     * into a proper PolicyAnalysisResponse DTO.
     *
     * @param array<string, mixed> $data
     */
    public function normalize(array $data): PolicyAnalysisResponse
    {
        $response = new PolicyAnalysisResponse();

        // Coverage
        $coverage = new Coverage();
        $coverage->coverageType   = $data['coverage']['coverageType']   ?? null;
        $coverage->coverageAmount = $data['coverage']['coverageAmount'] ?? null;
        $coverage->coverageBreakdown = $data['coverage']['coverageBreakdown'] ?? [];
        $response->coverage = $coverage;

        // Deductibles
        $response->deductibles = $data['deductibles'] ?? [];

        // Exclusions
        $response->exclusions = $data['exclusions'] ?? [];

        // Risk level
        $response->riskLevel = $data['riskLevel'] ?? 'medium';

        // Required actions
        $response->requiredActions = $data['requiredActions'] ?? [];

        // Flags
        $flags = new Flags();
        $flags->needsLegalReview = $data['flags']['needsLegalReview'] ?? false;
        $flags->inconsistentClausesDetected = $data['flags']['inconsistentClausesDetected'] ?? false;
        $response->flags = $flags;

        return $response;
    }
}
