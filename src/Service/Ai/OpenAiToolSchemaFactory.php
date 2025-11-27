<?php

declare(strict_types=1);

namespace App\Service\Ai;

/**
 * Builds the OpenAI tool (function calling) schema for insurance policy analysis.
 * Updated for OpenAI's Structured Outputs with strict: true
 */
class OpenAiToolSchemaFactory
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function createPolicyAnalysisTools(): array
    {
        return [
            [
                'type' => 'function',
                'function' => [
                    'name' => 'analyze_insurance_policy',
                    'description' => 'Analyze an insurance policy text and return a structured summary including coverage, deductibles, exclusions, risk level, recommended actions and compliance flags.',
                    'strict' => true,
                    'parameters' => $this->getPolicyAnalysisParametersSchema(),
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function getPolicyAnalysisParametersSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'coverage' => [
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
                    'required' => ['coverageType', 'coverageAmount', 'coverageBreakdown'],
                    'additionalProperties' => false,
                ],
                'deductibles' => [
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
                ],
                'exclusions' => [
                    'type' => 'array',
                    'description' => 'Key exclusions extracted from the policy.',
                    'items' => [
                        'type' => 'string',
                        'description' => 'A single exclusion clause in concise form.',
                    ],
                ],
                'riskLevel' => [
                    'type' => 'string',
                    'description' => 'Qualitative risk level inferred from the policy.',
                    'enum' => ['low', 'medium', 'high'],
                ],
                'requiredActions' => [
                    'type' => 'array',
                    'description' => 'Recommended follow-up actions, checks, or confirmations.',
                    'items' => [
                        'type' => 'string',
                        'description' => 'One recommended action or next step.',
                    ],
                ],
                'flags' => [
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
                ],
            ],
            'required' => [
                'coverage',
                'deductibles',
                'exclusions',
                'riskLevel',
                'requiredActions',
                'flags',
            ],
            'additionalProperties' => false,
        ];
    }
}
