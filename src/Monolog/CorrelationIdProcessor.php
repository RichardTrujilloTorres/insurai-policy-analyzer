<?php

namespace App\Monolog;

use App\Infrastructure\Aws\CorrelationIdProvider;
use Monolog\Processor\ProcessorInterface;

readonly class CorrelationIdProcessor implements ProcessorInterface
{
    public function __construct(
        private CorrelationIdProvider $provider
    ) {}

    public function __invoke(array|\Monolog\LogRecord $record): array|\Monolog\LogRecord
    {
        $record['extra']['correlation_id'] = $this->provider->get();

        return $record;
    }
}
