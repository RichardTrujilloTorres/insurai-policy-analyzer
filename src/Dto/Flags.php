<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class Flags
{
    #[Assert\NotNull]
    #[Assert\Type('bool')]
    public bool $needsLegalReview = false;

    #[Assert\NotNull]
    #[Assert\Type('bool')]
    public bool $inconsistentClausesDetected = false;
}
