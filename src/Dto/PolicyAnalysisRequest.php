<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class PolicyAnalysisRequest
{
    #[Assert\NotBlank(message: "policyText is required.")]
    #[Assert\Type("string")]
    public string $policyText;

    #[Assert\Optional]
    #[Assert\Type("string")]
    #[Assert\Length(max: 50)]
    public ?string $policyType = null;

    #[Assert\Optional]
    #[Assert\Type("string")]
    #[Assert\Choice(
        choices: ["IT", "EU", "US", "UK", "GLOBAL"],
        message: "jurisdiction must be one of IT, EU, US, UK, GLOBAL."
    )]
    public ?string $jurisdiction = null;

    #[Assert\Optional]
    #[Assert\Type("string")]
    public ?string $language = "en";

    #[Assert\Optional]
    #[Assert\Type("array")]
    public ?array $metadata = null;
}
