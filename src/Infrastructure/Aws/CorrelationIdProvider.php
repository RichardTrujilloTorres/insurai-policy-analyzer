<?php

namespace App\Infrastructure\Aws;

use Symfony\Component\Uid\Uuid;

class CorrelationIdProvider
{
    private ?string $correlationId = null;

    public function set(string $id): void
    {
        $this->correlationId = $id;
    }

    public function get(): string
    {
        return $this->correlationId ??= Uuid::v4()->toRfc4122();
    }
}
