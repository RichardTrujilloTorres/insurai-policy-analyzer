<?php

namespace App\Infrastructure\Aws;

readonly class LambdaExecutionContext
{
    public function __construct(
        public string $requestId,
        public string $functionArn,
        public int $remainingTimeMs,
    ) {
    }
}
