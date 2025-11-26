<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Ai;

use App\Service\Ai\OpenAiToolSchemaFactory;
use PHPUnit\Framework\TestCase;

class OpenAiToolSchemaFactoryTest extends TestCase
{
    private OpenAiToolSchemaFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new OpenAiToolSchemaFactory();
    }

    public function testCreatePolicyAnalysisToolsReturnsArray(): void
    {
        // Act
        $tools = $this->factory->createPolicyAnalysisTools();

        // Assert
        $this->assertIsArray($tools);
        $this->assertNotEmpty($tools);
    }

    public function testCreatePolicyAnalysisToolsReturnsSingleTool(): void
    {
        // Act
        $tools = $this->factory->createPolicyAnalysisTools();

        // Assert
        $this->assertCount(1, $tools);
    }

    public function testToolHasCorrectType(): void
    {
        // Act
        $tools = $this->factory->createPolicyAnalysisTools();

        // Assert
        $this->assertArrayHasKey('type', $tools[0]);
        $this->assertSame('function', $tools[0]['type']);
    }

    public function testToolHasFunctionDefinition(): void
    {
        // Act
        $tools = $this->factory->createPolicyAnalysisTools();

        // Assert
        $this->assertArrayHasKey('function', $tools[0]);
        $this->assertIsArray($tools[0]['function']);
    }

    public function testFunctionHasCorrectName(): void
    {
        // Act
        $tools = $this->factory->createPolicyAnalysisTools();
        $function = $tools[0]['function'];

        // Assert
        $this->assertArrayHasKey('name', $function);
        $this->assertSame('analyze_insurance_policy', $function['name']);
    }

    public function testFunctionHasDescription(): void
    {
        // Act
        $tools = $this->factory->createPolicyAnalysisTools();
        $function = $tools[0]['function'];

        // Assert
        $this->assertArrayHasKey('description', $function);
        $this->assertIsString($function['description']);
        $this->assertNotEmpty($function['description']);
        $this->assertStringContainsString('insurance policy', strtolower($function['description']));
    }

    public function testFunctionHasStrictMode(): void
    {
        // Act
        $tools = $this->factory->createPolicyAnalysisTools();
        $function = $tools[0]['function'];

        // Assert
        $this->assertArrayHasKey('strict', $function);
        $this->assertTrue($function['strict']);
    }

    public function testFunctionHasParameters(): void
    {
        // Act
        $tools = $this->factory->createPolicyAnalysisTools();
        $function = $tools[0]['function'];

        // Assert
        $this->assertArrayHasKey('parameters', $function);
        $this->assertIsArray($function['parameters']);
    }

    public function testParametersHasCorrectType(): void
    {
        // Act
        $tools = $this->factory->createPolicyAnalysisTools();
        $parameters = $tools[0]['function']['parameters'];

        // Assert
        $this->assertArrayHasKey('type', $parameters);
        $this->assertSame('object', $parameters['type']);
    }

    public function testParametersHasProperties(): void
    {
        // Act
        $tools = $this->factory->createPolicyAnalysisTools();
        $parameters = $tools[0]['function']['parameters'];

        // Assert
        $this->assertArrayHasKey('properties', $parameters);
        $this->assertIsArray($parameters['properties']);
    }

    public function testParametersHasAllRequiredFields(): void
    {
        // Act
        $tools = $this->factory->createPolicyAnalysisTools();
        $parameters = $tools[0]['function']['parameters'];

        // Assert
        $this->assertArrayHasKey('required', $parameters);
        $this->assertIsArray($parameters['required']);

        $expectedRequired = [
            'coverage',
            'deductibles',
            'exclusions',
            'riskLevel',
            'requiredActions',
            'flags',
        ];

        $this->assertSame($expectedRequired, $parameters['required']);
    }

    public function testParametersDisallowsAdditionalProperties(): void
    {
        // Act
        $tools = $this->factory->createPolicyAnalysisTools();
        $parameters = $tools[0]['function']['parameters'];

        // Assert
        $this->assertArrayHasKey('additionalProperties', $parameters);
        $this->assertFalse($parameters['additionalProperties']);
    }

    public function testCoveragePropertyStructure(): void
    {
        // Act
        $tools = $this->factory->createPolicyAnalysisTools();
        $properties = $tools[0]['function']['parameters']['properties'];

        // Assert
        $this->assertArrayHasKey('coverage', $properties);
        $coverage = $properties['coverage'];

        $this->assertSame('object', $coverage['type']);
        $this->assertArrayHasKey('description', $coverage);
        $this->assertArrayHasKey('properties', $coverage);
        $this->assertArrayHasKey('required', $coverage);
        $this->assertFalse($coverage['additionalProperties']);

        // Check coverage required fields
        $this->assertSame(
            ['coverageType', 'coverageAmount', 'coverageBreakdown'],
            $coverage['required']
        );
    }

    public function testCoverageBreakdownIsArray(): void
    {
        // Act
        $tools = $this->factory->createPolicyAnalysisTools();
        $coverage = $tools[0]['function']['parameters']['properties']['coverage'];

        // Assert
        $this->assertArrayHasKey('coverageBreakdown', $coverage['properties']);
        $breakdown = $coverage['properties']['coverageBreakdown'];

        $this->assertSame('array', $breakdown['type']);
        $this->assertArrayHasKey('items', $breakdown);
        $this->assertSame('object', $breakdown['items']['type']);
    }

    public function testDeductiblesPropertyStructure(): void
    {
        // Act
        $tools = $this->factory->createPolicyAnalysisTools();
        $properties = $tools[0]['function']['parameters']['properties'];

        // Assert
        $this->assertArrayHasKey('deductibles', $properties);
        $deductibles = $properties['deductibles'];

        $this->assertSame('array', $deductibles['type']);
        $this->assertArrayHasKey('description', $deductibles);
        $this->assertArrayHasKey('items', $deductibles);

        // Check deductible item structure
        $item = $deductibles['items'];
        $this->assertSame('object', $item['type']);
        $this->assertArrayHasKey('properties', $item);
        $this->assertSame(['type', 'amount'], $item['required']);
        $this->assertFalse($item['additionalProperties']);
    }

    public function testExclusionsPropertyStructure(): void
    {
        // Act
        $tools = $this->factory->createPolicyAnalysisTools();
        $properties = $tools[0]['function']['parameters']['properties'];

        // Assert
        $this->assertArrayHasKey('exclusions', $properties);
        $exclusions = $properties['exclusions'];

        $this->assertSame('array', $exclusions['type']);
        $this->assertArrayHasKey('description', $exclusions);
        $this->assertArrayHasKey('items', $exclusions);
        $this->assertSame('string', $exclusions['items']['type']);
    }

    public function testRiskLevelPropertyStructure(): void
    {
        // Act
        $tools = $this->factory->createPolicyAnalysisTools();
        $properties = $tools[0]['function']['parameters']['properties'];

        // Assert
        $this->assertArrayHasKey('riskLevel', $properties);
        $riskLevel = $properties['riskLevel'];

        $this->assertSame('string', $riskLevel['type']);
        $this->assertArrayHasKey('description', $riskLevel);
        $this->assertArrayHasKey('enum', $riskLevel);
        $this->assertSame(['low', 'medium', 'high'], $riskLevel['enum']);
    }

    public function testRequiredActionsPropertyStructure(): void
    {
        // Act
        $tools = $this->factory->createPolicyAnalysisTools();
        $properties = $tools[0]['function']['parameters']['properties'];

        // Assert
        $this->assertArrayHasKey('requiredActions', $properties);
        $requiredActions = $properties['requiredActions'];

        $this->assertSame('array', $requiredActions['type']);
        $this->assertArrayHasKey('description', $requiredActions);
        $this->assertArrayHasKey('items', $requiredActions);
        $this->assertSame('string', $requiredActions['items']['type']);
    }

    public function testFlagsPropertyStructure(): void
    {
        // Act
        $tools = $this->factory->createPolicyAnalysisTools();
        $properties = $tools[0]['function']['parameters']['properties'];

        // Assert
        $this->assertArrayHasKey('flags', $properties);
        $flags = $properties['flags'];

        $this->assertSame('object', $flags['type']);
        $this->assertArrayHasKey('description', $flags);
        $this->assertArrayHasKey('properties', $flags);
        $this->assertArrayHasKey('required', $flags);
        $this->assertFalse($flags['additionalProperties']);

        // Check flags required fields
        $this->assertSame(
            ['needsLegalReview', 'inconsistentClausesDetected'],
            $flags['required']
        );

        // Check boolean types
        $this->assertSame('boolean', $flags['properties']['needsLegalReview']['type']);
        $this->assertSame('boolean', $flags['properties']['inconsistentClausesDetected']['type']);
    }

    public function testAllPropertiesHaveDescriptions(): void
    {
        // Act
        $tools = $this->factory->createPolicyAnalysisTools();
        $properties = $tools[0]['function']['parameters']['properties'];

        // Assert
        foreach ($properties as $propertyName => $property) {
            $this->assertArrayHasKey(
                'description',
                $property,
                "Property '{$propertyName}' is missing description"
            );
            $this->assertIsString($property['description']);
            $this->assertNotEmpty($property['description']);
        }
    }

    public function testSchemaIsValidForStructuredOutputs(): void
    {
        // Act
        $tools = $this->factory->createPolicyAnalysisTools();
        $function = $tools[0]['function'];

        // Assert - Verify all requirements for OpenAI Structured Outputs
        $this->assertTrue($function['strict'], 'strict must be true');
        $this->assertArrayHasKey('parameters', $function);

        $parameters = $function['parameters'];
        $this->assertSame('object', $parameters['type']);
        $this->assertArrayHasKey('required', $parameters);
        $this->assertArrayHasKey('additionalProperties', $parameters);
        $this->assertFalse($parameters['additionalProperties']);
    }

    public function testCoverageBreakdownItemsHaveRequiredFields(): void
    {
        // Act
        $tools = $this->factory->createPolicyAnalysisTools();
        $coverage = $tools[0]['function']['parameters']['properties']['coverage'];
        $breakdown = $coverage['properties']['coverageBreakdown'];

        // Assert
        $this->assertArrayHasKey('required', $breakdown['items']);
        $this->assertSame(['category', 'limit'], $breakdown['items']['required']);
        $this->assertFalse($breakdown['items']['additionalProperties']);
    }

    public function testDeductibleItemsHaveRequiredFields(): void
    {
        // Act
        $tools = $this->factory->createPolicyAnalysisTools();
        $deductibles = $tools[0]['function']['parameters']['properties']['deductibles'];

        // Assert
        $this->assertArrayHasKey('required', $deductibles['items']);
        $this->assertSame(['type', 'amount'], $deductibles['items']['required']);
        $this->assertFalse($deductibles['items']['additionalProperties']);
    }

    public function testMultipleCallsReturnConsistentSchema(): void
    {
        // Act
        $tools1 = $this->factory->createPolicyAnalysisTools();
        $tools2 = $this->factory->createPolicyAnalysisTools();

        // Assert
        $this->assertEquals($tools1, $tools2);
        $this->assertSame(
            json_encode($tools1),
            json_encode($tools2)
        );
    }

    public function testSchemaCanBeJsonEncoded(): void
    {
        // Act
        $tools = $this->factory->createPolicyAnalysisTools();
        $json = json_encode($tools);

        // Assert
        $this->assertNotFalse($json);
        $this->assertJson($json);

        // Verify it can be decoded back
        $decoded = json_decode($json, true);
        $this->assertEquals($tools, $decoded);
    }

    public function testAllNestedObjectsDisallowAdditionalProperties(): void
    {
        // Act
        $tools = $this->factory->createPolicyAnalysisTools();
        $parameters = $tools[0]['function']['parameters'];

        // Assert - Check main parameters
        $this->assertFalse($parameters['additionalProperties']);

        // Check coverage
        $coverage = $parameters['properties']['coverage'];
        $this->assertFalse($coverage['additionalProperties']);

        // Check coverage breakdown items
        $breakdown = $coverage['properties']['coverageBreakdown'];
        $this->assertFalse($breakdown['items']['additionalProperties']);

        // Check deductibles items
        $deductibles = $parameters['properties']['deductibles'];
        $this->assertFalse($deductibles['items']['additionalProperties']);

        // Check flags
        $flags = $parameters['properties']['flags'];
        $this->assertFalse($flags['additionalProperties']);
    }

    public function testCoveragePropertiesHaveCorrectTypes(): void
    {
        // Act
        $tools = $this->factory->createPolicyAnalysisTools();
        $coverage = $tools[0]['function']['parameters']['properties']['coverage'];

        // Assert
        $this->assertSame('string', $coverage['properties']['coverageType']['type']);
        $this->assertSame('string', $coverage['properties']['coverageAmount']['type']);
        $this->assertSame('array', $coverage['properties']['coverageBreakdown']['type']);
    }

    public function testDeductiblePropertiesHaveCorrectTypes(): void
    {
        // Act
        $tools = $this->factory->createPolicyAnalysisTools();
        $deductibles = $tools[0]['function']['parameters']['properties']['deductibles'];
        $item = $deductibles['items'];

        // Assert
        $this->assertSame('string', $item['properties']['type']['type']);
        $this->assertSame('string', $item['properties']['amount']['type']);
    }

    public function testFlagsPropertiesHaveCorrectTypes(): void
    {
        // Act
        $tools = $this->factory->createPolicyAnalysisTools();
        $flags = $tools[0]['function']['parameters']['properties']['flags'];

        // Assert
        $this->assertSame('boolean', $flags['properties']['needsLegalReview']['type']);
        $this->assertSame('boolean', $flags['properties']['inconsistentClausesDetected']['type']);
    }
}
