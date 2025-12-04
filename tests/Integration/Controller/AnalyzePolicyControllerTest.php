<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Integration tests for the /analyze endpoint
 */
class AnalyzePolicyControllerTest extends WebTestCase
{
    private $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    public function testAnalyzePolicyEndpointHandlesValidRequest(): void
    {
        $data = [
            'policyText' => 'Comprehensive health insurance covering medical expenses up to $100,000',
            'policyType' => 'health',
            'jurisdiction' => 'US',
            'language' => 'en',
        ];

        $this->client->request('POST', '/analyze', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_DEMO_PASSWORD' => 'test-password',
        ], json_encode($data));

        $this->assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('coverage', $response);
        $this->assertArrayHasKey('deductibles', $response);
        $this->assertArrayHasKey('exclusions', $response);
    }

    public function testAnalyzePolicyEndpointValidatesInput(): void
    {
        // Missing required field
        $data = [
            'policyType' => 'health',
            'jurisdiction' => 'US',
        ];

        $this->client->request('POST', '/analyze', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_DEMO_PASSWORD' => 'test-password',
        ], json_encode($data));

        $this->assertResponseStatusCodeSame(422);
    }

    public function testAnalyzePolicyEndpointReturnsCorrelationId(): void
    {
        $data = [
            'policyText' => 'Test policy',
            'policyType' => 'health',
            'jurisdiction' => 'US',
        ];

        $this->client->request('POST', '/analyze', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_DEMO_PASSWORD' => 'test-password',
        ], json_encode($data));

        $this->assertResponseIsSuccessful();
        $this->assertTrue($this->client->getResponse()->headers->has('X-Correlation-Id'));
    }

    public function testAnalyzePolicyEndpointHandlesMissingPolicyText(): void
    {
        $data = [
            'policyType' => 'health',
        ];

        $this->client->request('POST', '/analyze', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_DEMO_PASSWORD' => 'test-password',
        ], json_encode($data));

        $this->assertResponseStatusCodeSame(422);
    }

    public function testAnalyzePolicyEndpointRejectsInvalidPassword(): void
    {
        $data = [
            'policyText' => 'Test policy',
            'policyType' => 'health',
            'jurisdiction' => 'US',
        ];

        $this->client->request('POST', '/analyze', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_DEMO_PASSWORD' => 'wrong-password',
        ], json_encode($data));

        $this->assertResponseStatusCodeSame(401);
        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('error', $response);
        $this->assertEquals('Invalid demo password', $response['error']);
        $this->assertArrayHasKey('contact', $response);
    }

    public function testAnalyzePolicyEndpointRejectsMissingPassword(): void
    {
        $data = [
            'policyText' => 'Test policy',
            'policyType' => 'health',
            'jurisdiction' => 'US',
        ];

        $this->client->request('POST', '/analyze', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode($data));

        $this->assertResponseStatusCodeSame(401);
        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('error', $response);
        $this->assertEquals('Demo password required', $response['error']);
        $this->assertArrayHasKey('contact', $response);
    }
}
