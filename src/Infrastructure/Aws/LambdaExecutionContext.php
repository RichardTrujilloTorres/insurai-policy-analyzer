<?php

namespace App\Infrastructure\Aws;

final readonly class LambdaExecutionContext
{
    public function __construct(
        public string $requestId,
        public string $functionArn,
        public int $deadlineMs,
        public int $memoryLimitMb,
        public int $remainingTimeMs,
    ) {
    }
}
