<?php

declare(strict_types=1);

namespace App\Service\Ai;

/**
 * Builds the OpenAI tool (function calling) schema for insurance policy analysis.
 *
 * This schema is used to force the model to return strictly structured JSON
 * that maps to PolicyAnalysisResponse and its nested structures.
 */
final class OpenAiToolSchemaFactory
{
    /**
     * Returns the tools array to be sent to OpenAI.
     *
     * Example usage:
     *
     * $tools = $toolSchemaFactory->createPolicyAnalysisTools();
     * $payload = [
     *     'model'  => 'gpt-4.1', // or o3-mini, etc.
     *     'tools'  => $tools,
     *     'tool_choice' => [
     *         'type' => 'function',
     *         'function' => ['name' => 'analyze_insurance_policy'],
     *     ],
     *     // ...
     * ];
     */
    public function createPolicyAnalysisTools(): array
    {
        return [
            [
                'type' => 'function',
                'function' => [
                    'name' => 'analyze_insurance_policy',
                    'description' => 'Analyze an insurance policy text and return a structured summary including coverage, deductibles, exclusions, risk level, recommended actions and compliance flags.',
                    'parameters' => $this->getPolicyAnalysisParametersSchema(),
                ],
            ],
        ];
    }

    /**
     * JSON schema for the function parameters.
     *
     * This schema mirrors the shape of PolicyAnalysisResponse
     * (and its nested Coverage / Flags structures).
     */
    private function getPolicyAnalysisParametersSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'coverage' => $this->getCoverageSchema(),
                'deductibles' => $this->getDeductiblesSchema(),
                'exclusions' => $this->getExclusionsSchema(),
                'riskLevel' => $this->getRiskLevelSchema(),
                'requiredActions' => $this->getRequiredActionsSchema(),
                'flags' => $this->getFlagsSchema(),
            ],
            'required' => [
                'coverage',
                'deductibles',
                'exclusions',
                'riskLevel',
                'flags',
            ],
            'additionalProperties' => false,
        ];
    }

    private function getCoverageSchema(): array
    {
        return [
            'type' => 'object',
            'description' => 'Overall coverage summary extracted from the policy.',
            'properties' => [
                'coverageType' => [
                    'type' => 'string',
                    'description' => 'High-level category of coverage, e.g. "vehicle_liability", "property_damage", "health", "life", etc.',
                ],
                'coverageAmount' => [
                    'type' => 'string',
                    'description' => 'Human-readable main coverage limit, including currency if possible, e.g. "€1,000,000".',
                ],
                'coverageBreakdown' => [
                    'type' => 'array',
                    'description' => 'Optional detailed breakdown by category.',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'category' => [
                                'type' => 'string',
                                'description' => 'Coverage category, e.g. "property_damage", "bodily_injury".',
                            ],
                            'limit' => [
                                'type' => 'string',
                                'description' => 'Coverage limit for this category, including currency if possible.',
                            ],
                        ],
                        'required' => ['category', 'limit'],
                        'additionalProperties' => false,
                    ],
                ],
            ],
            'required' => ['coverageType', 'coverageAmount'],
            'additionalProperties' => false,
        ];
    }

    private function getDeductiblesSchema(): array
    {
        return [
            'type' => 'array',
            'description' => 'List of deductibles described in the policy.',
            'items' => [
                'type' => 'object',
                'properties' => [
                    'type' => [
                        'type' => 'string',
                        'description' => 'Type of deductible, e.g. "collision", "theft", "medical".',
                    ],
                    'amount' => [
                        'type' => 'string',
                        'description' => 'Deductible amount, including currency if possible.',
                    ],
                ],
                'required' => ['type', 'amount'],
                'additionalProperties' => false,
            ],
        ];
    }

    private function getExclusionsSchema(): array
    {
        return [
            'type' => 'array',
            'description' => 'Key exclusions extracted from the policy.',
            'items' => [
                'type' => 'string',
                'description' => 'A single exclusion clause in concise form.',
            ],
        ];
    }

    private function getRiskLevelSchema(): array
    {
        return [
            'type' => 'string',
            'description' => 'Qualitative risk level inferred from the policy.',
            'enum' => ['low', 'medium', 'high'],
        ];
    }

    private function getRequiredActionsSchema(): array
    {
        return [
            'type' => 'array',
            'description' => 'Recommended follow-up actions, checks, or confirmations.',
            'items' => [
                'type' => 'string',
                'description' => 'One recommended action or next step.',
            ],
        ];
    }

    private function getFlagsSchema(): array
    {
        return [
            'type' => 'object',
            'description' => 'Compliance and review flags for the policy.',
            'properties' => [
                'needsLegalReview' => [
                    'type' => 'boolean',
                    'description' => 'True if a human legal review is strongly recommended.',
                ],
                'inconsistentClausesDetected' => [
                    'type' => 'boolean',
                    'description' => 'True if the model detected contradictions or inconsistencies.',
                ],
            ],
            'required' => ['needsLegalReview', 'inconsistentClausesDetected'],
            'additionalProperties' => false,
        ];
    }
}
