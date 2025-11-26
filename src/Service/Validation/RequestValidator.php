<?php

namespace App\Service\Validation;

use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

readonly class RequestValidator
{
    public function __construct(
        private ValidatorInterface $validator,
    ) {
    }

    /**
     * @throws ValidationFailedException
     */
    public function validate(object $dto): void
    {
        $violations = $this->validator->validate($dto);

        if (count($violations) > 0) {
            throw new ValidationFailedException($dto, $violations);
        }
    }
}
