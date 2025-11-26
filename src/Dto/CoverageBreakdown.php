<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class CoverageBreakdown
{
    #[Assert\NotBlank]
    #[Assert\Type('string')]
    public string $category;

    #[Assert\NotBlank]
    #[Assert\Type('string')]
    public string $limit;
}
