<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class PolicyAnalysisResponse
{
    #[Assert\NotNull]
    #[Assert\Valid]
    public Coverage $coverage;

    /** @var array<string, mixed> */
    #[Assert\NotNull]
    #[Assert\Valid]
    #[Assert\Type('array')]
    public array $deductibles = [];

    /** @var array<int, string> */
    #[Assert\NotNull]
    #[Assert\Type('array')]
    public array $exclusions = [];

    #[Assert\NotBlank]
    #[Assert\Choice(choices: ['low', 'medium', 'high'])]
    public string $riskLevel;

    /** @var array<int, string> */
    #[Assert\Type('array')]
    public array $requiredActions = [];

    #[Assert\NotNull]
    #[Assert\Valid]
    public Flags $flags;
}
