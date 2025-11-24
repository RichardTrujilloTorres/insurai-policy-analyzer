<?php

namespace App\Tests\Api;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

abstract class ApiTestCase extends WebTestCase
{
    protected KernelBrowser $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();
    }

    protected function jsonRequest(string $method, string $uri, array $payload = []): Response
    {
        $this->client->request(
            $method,
            $uri,
            server: ['CONTENT_TYPE' => 'application/json'],
            content: [] === $payload ? null : json_encode($payload, JSON_THROW_ON_ERROR)
        );

        return $this->client->getResponse();
    }

    protected function assertJsonResponse(Response $response, int $expectedStatusCode = 200): array
    {
        self::assertSame(
            $expectedStatusCode,
            $response->getStatusCode(),
            sprintf(
                'Expected status code %d, got %d. Response body: %s',
                $expectedStatusCode,
                $response->getStatusCode(),
                $response->getContent()
            )
        );

        self::assertTrue(
            $response->headers->contains('Content-Type', 'application/json'),
            'Response is not JSON'
        );

        $data = json_decode($response->getContent(), true);

        self::assertIsArray($data, 'Response JSON is not an array');

        return $data;
    }
}
