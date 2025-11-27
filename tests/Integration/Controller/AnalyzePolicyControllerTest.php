<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AnalyzePolicyControllerTest extends WebTestCase
{
    public function testAnalyzePolicyEndpointHandlesValidRequest(): void
    {
        // Arrange
        $client = static::createClient();

        $payload = [
            'policyText' => 'This is a sample health insurance policy with comprehensive coverage for medical expenses up to $100,000 annually.',
            'policyType' => 'health',
            'jurisdiction' => 'US',
            'language' => 'en',
            'metadata' => [
                'source' => 'test',
                'testId' => 'integration-test-001',
            ],
        ];

        // Act
        $client->request(
            'POST',
            '/analyze',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload)
        );

        // Assert
        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('content-type', 'application/json');

        $responseData = json_decode($client->getResponse()->getContent(), true);

        // Verify expected structure
        $this->assertIsArray($responseData);
        $this->assertArrayHasKey('coverage', $responseData);
        $this->assertArrayHasKey('deductibles', $responseData);
        $this->assertArrayHasKey('exclusions', $responseData);
        $this->assertArrayHasKey('riskLevel', $responseData);
        $this->assertArrayHasKey('requiredActions', $responseData);
        $this->assertArrayHasKey('flags', $responseData);

        // Verify risk level is valid
        $this->assertContains($responseData['riskLevel'], ['low', 'medium', 'high']);
    }

    public function testAnalyzePolicyEndpointRejectsInvalidMethod(): void
    {
        // Arrange
        $client = static::createClient();

        // Act - Try GET instead of POST
        $client->request('GET', '/analyze');

        // Assert
        $this->assertResponseStatusCodeSame(405); // Method Not Allowed
    }
}
