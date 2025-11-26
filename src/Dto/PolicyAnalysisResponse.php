<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class PolicyAnalysisResponse
{
    #[Assert\NotNull]
    #[Assert\Valid]
    public Coverage $coverage;

    #[Assert\NotNull]
    #[Assert\Valid]
    #[Assert\Type('array')]
    public array $deductibles = [];

    #[Assert\NotNull]
    #[Assert\Type('array')]
    public array $exclusions = [];

    #[Assert\NotBlank]
    #[Assert\Choice(choices: ['low', 'medium', 'high'])]
    public string $riskLevel;

    #[Assert\Type('array')]
    public array $requiredActions = [];

    #[Assert\NotNull]
    #[Assert\Valid]
    public Flags $flags;
}
