<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;

final class AnalyzePolicyControllerTest extends WebTestCase
{
    public function testAnalyzePolicyEndpointHandlesValidRequest(): void
    {
        $client = static::createClient();

        // Use the 6th parameter (content) for the raw body
        $client->request(
            'POST',
            '/analyze',
            [], // parameters
            [], // files
            ['CONTENT_TYPE' => 'application/json'], // server
            '{"policyText": "This is a sample insurance policy document."}' // content - note the space after colon
        );

        $response = $client->getResponse();

        $this->assertResponseIsSuccessful(
            'Response failed: ' . $response->getStatusCode() . ' - ' . $response->getContent()
        );

        $this->assertResponseHeaderSame('content-type', 'application/json');

        $responseData = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('coverage', $responseData);
        $this->assertArrayHasKey('deductibles', $responseData);
        $this->assertArrayHasKey('exclusions', $responseData);
        $this->assertArrayHasKey('riskLevel', $responseData);
        $this->assertArrayHasKey('requiredActions', $responseData);
        $this->assertArrayHasKey('flags', $responseData);
    }

    public function testAnalyzePolicyEndpointValidatesInput(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/analyze',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            '{"policyText": ""}' // Empty string should fail validation
        );

        $this->assertResponseStatusCodeSame(422);
    }

    public function testAnalyzePolicyEndpointReturnsCorrelationId(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/analyze',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            '{"policyText": "Sample policy text for correlation test"}'
        );

        $this->assertResponseIsSuccessful();
        $this->assertTrue($client->getResponse()->headers->has('X-Correlation-Id'));
    }

    public function testAnalyzePolicyEndpointHandlesMissingPolicyText(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/analyze',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            '{}' // Missing policyText
        );

        $this->assertResponseStatusCodeSame(422);
    }
}
