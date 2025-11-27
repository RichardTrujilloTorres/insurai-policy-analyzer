<?php

namespace App\Monolog;

use App\Infrastructure\Aws\CorrelationIdProvider;
use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

readonly class CorrelationIdProcessor implements ProcessorInterface
{
    public function __construct(
        private CorrelationIdProvider $provider,
    ) {
    }

    /**
     * @param array<string, mixed>|LogRecord $record
     * @return array<string, mixed>|LogRecord
     */
    public function __invoke(array|LogRecord $record): array|LogRecord
    {
        $record['extra']['correlation_id'] = $this->provider->get();

        return $record;
    }
}
