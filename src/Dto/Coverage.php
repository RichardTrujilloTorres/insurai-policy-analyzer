<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class Coverage
{
    #[Assert\NotBlank]
    #[Assert\Type("string")]
    public string $coverageType;

    #[Assert\NotBlank]
    #[Assert\Type("string")]
    public string $coverageAmount;

    #[Assert\Type("array")]
    #[Assert\Valid]
    public array $coverageBreakdown = []; // CoverageBreakdown[]
}
