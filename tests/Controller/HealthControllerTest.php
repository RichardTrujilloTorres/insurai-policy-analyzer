<?php

namespace App\Tests\Controller;

use App\Tests\Api\ApiTestCase;

final class HealthControllerTest extends ApiTestCase
{
    public function testHealthEndpointReturnsOkStatus(): void
    {
        $response = $this->jsonRequest('GET', '/health');

        $data = $this->assertJsonResponse($response, 200);

        self::assertArrayHasKey('status', $data);
        self::assertSame('ok', $data['status']);
    }
}
