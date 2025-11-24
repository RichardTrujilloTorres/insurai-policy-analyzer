<?php

namespace App\Service\Ai;

readonly class OpenAiModelConfig
{
    public function __construct(
        private string $model = 'gpt-4.1-mini',
        private float  $temperature = 0.1,
        private int    $maxTokens = 2000
    ) {}

    public function getModel(): string
    {
        return $this->model;
    }

    public function getTemperature(): float
    {
        return $this->temperature;
    }

    public function getMaxTokens(): int
    {
        return $this->maxTokens;
    }
}
