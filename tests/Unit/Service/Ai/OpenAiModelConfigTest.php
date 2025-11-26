<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Ai;

use App\Service\Ai\OpenAiModelConfig;
use PHPUnit\Framework\TestCase;

class OpenAiModelConfigTest extends TestCase
{
    public function testConstructorWithDefaultValues(): void
    {
        // Act
        $config = new OpenAiModelConfig();

        // Assert
        $this->assertSame('gpt-4o-mini', $config->getModel());
        $this->assertSame(0.1, $config->getTemperature());
        $this->assertSame(2000, $config->getMaxTokens());
    }

    public function testConstructorWithCustomModel(): void
    {
        // Act
        $config = new OpenAiModelConfig(model: 'gpt-4-turbo-preview');

        // Assert
        $this->assertSame('gpt-4-turbo-preview', $config->getModel());
        $this->assertSame(0.1, $config->getTemperature());
        $this->assertSame(2000, $config->getMaxTokens());
    }

    public function testConstructorWithCustomTemperature(): void
    {
        // Act
        $config = new OpenAiModelConfig(temperature: 0.7);

        // Assert
        $this->assertSame('gpt-4o-mini', $config->getModel());
        $this->assertSame(0.7, $config->getTemperature());
        $this->assertSame(2000, $config->getMaxTokens());
    }

    public function testConstructorWithCustomMaxTokens(): void
    {
        // Act
        $config = new OpenAiModelConfig(maxTokens: 4000);

        // Assert
        $this->assertSame('gpt-4o-mini', $config->getModel());
        $this->assertSame(0.1, $config->getTemperature());
        $this->assertSame(4000, $config->getMaxTokens());
    }

    public function testConstructorWithAllCustomValues(): void
    {
        // Act
        $config = new OpenAiModelConfig(
            model: 'gpt-4',
            temperature: 0.5,
            maxTokens: 3000
        );

        // Assert
        $this->assertSame('gpt-4', $config->getModel());
        $this->assertSame(0.5, $config->getTemperature());
        $this->assertSame(3000, $config->getMaxTokens());
    }

    public function testGetModelReturnsCorrectValue(): void
    {
        // Arrange
        $config = new OpenAiModelConfig(model: 'gpt-4o');

        // Act
        $model = $config->getModel();

        // Assert
        $this->assertIsString($model);
        $this->assertSame('gpt-4o', $model);
    }

    public function testGetTemperatureReturnsCorrectValue(): void
    {
        // Arrange
        $config = new OpenAiModelConfig(temperature: 0.9);

        // Act
        $temperature = $config->getTemperature();

        // Assert
        $this->assertIsFloat($temperature);
        $this->assertSame(0.9, $temperature);
    }

    public function testGetMaxTokensReturnsCorrectValue(): void
    {
        // Arrange
        $config = new OpenAiModelConfig(maxTokens: 8000);

        // Act
        $maxTokens = $config->getMaxTokens();

        // Assert
        $this->assertIsInt($maxTokens);
        $this->assertSame(8000, $maxTokens);
    }

    public function testTemperatureWithZeroValue(): void
    {
        // Act
        $config = new OpenAiModelConfig(temperature: 0.0);

        // Assert
        $this->assertSame(0.0, $config->getTemperature());
    }

    public function testTemperatureWithMaxValue(): void
    {
        // Act
        $config = new OpenAiModelConfig(temperature: 2.0);

        // Assert
        $this->assertSame(2.0, $config->getTemperature());
    }

    public function testMaxTokensWithMinimumValue(): void
    {
        // Act
        $config = new OpenAiModelConfig(maxTokens: 1);

        // Assert
        $this->assertSame(1, $config->getMaxTokens());
    }

    public function testMaxTokensWithLargeValue(): void
    {
        // Act
        $config = new OpenAiModelConfig(maxTokens: 128000);

        // Assert
        $this->assertSame(128000, $config->getMaxTokens());
    }

    public function testConfigIsReadonly(): void
    {
        // Arrange
        $config = new OpenAiModelConfig(
            model: 'gpt-4o-mini',
            temperature: 0.1,
            maxTokens: 2000
        );

        // Assert - Multiple calls return same values (immutable)
        $this->assertSame('gpt-4o-mini', $config->getModel());
        $this->assertSame('gpt-4o-mini', $config->getModel());
        $this->assertSame(0.1, $config->getTemperature());
        $this->assertSame(0.1, $config->getTemperature());
        $this->assertSame(2000, $config->getMaxTokens());
        $this->assertSame(2000, $config->getMaxTokens());
    }

    public function testDifferentInstancesAreIndependent(): void
    {
        // Arrange
        $config1 = new OpenAiModelConfig(model: 'gpt-4o-mini', temperature: 0.1);
        $config2 = new OpenAiModelConfig(model: 'gpt-4', temperature: 0.7);

        // Assert
        $this->assertSame('gpt-4o-mini', $config1->getModel());
        $this->assertSame('gpt-4', $config2->getModel());
        $this->assertSame(0.1, $config1->getTemperature());
        $this->assertSame(0.7, $config2->getTemperature());
    }

    public function testNamedArgumentsOrder(): void
    {
        // Act - Arguments in different order
        $config = new OpenAiModelConfig(
            maxTokens: 5000,
            model: 'gpt-4',
            temperature: 0.8
        );

        // Assert
        $this->assertSame('gpt-4', $config->getModel());
        $this->assertSame(0.8, $config->getTemperature());
        $this->assertSame(5000, $config->getMaxTokens());
    }

    public function testPartialNamedArguments(): void
    {
        // Act - Only some named arguments provided
        $config = new OpenAiModelConfig(
            model: 'gpt-4-turbo',
            maxTokens: 6000
        );

        // Assert
        $this->assertSame('gpt-4-turbo', $config->getModel());
        $this->assertSame(0.1, $config->getTemperature()); // Default
        $this->assertSame(6000, $config->getMaxTokens());
    }

    public function testWithVeryPreciseTemperature(): void
    {
        // Act
        $config = new OpenAiModelConfig(temperature: 0.123456789);

        // Assert
        $this->assertSame(0.123456789, $config->getTemperature());
    }

    public function testModelNamesWithDifferentFormats(): void
    {
        $modelNames = [
            'gpt-4o-mini',
            'gpt-4-turbo-preview',
            'gpt-4',
            'gpt-3.5-turbo',
            'gpt-4o',
        ];

        foreach ($modelNames as $modelName) {
            // Act
            $config = new OpenAiModelConfig(model: $modelName);

            // Assert
            $this->assertSame($modelName, $config->getModel());
        }
    }
}
