<?php

declare(strict_types=1);

namespace App\Tests\Unit\EventSubscriber;

use App\EventSubscriber\CorrelationIdSubscriber;
use App\Infrastructure\Aws\CorrelationIdProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class CorrelationIdSubscriberTest extends TestCase
{
    private CorrelationIdProvider $provider;
    private CorrelationIdSubscriber $subscriber;

    protected function setUp(): void
    {
        $this->provider = $this->createMock(CorrelationIdProvider::class);
        $this->subscriber = new CorrelationIdSubscriber($this->provider);
    }

    public function testGetSubscribedEvents(): void
    {
        // Act
        $events = CorrelationIdSubscriber::getSubscribedEvents();

        // Assert
        $this->assertIsArray($events);
        $this->assertArrayHasKey(RequestEvent::class, $events);
        $this->assertArrayHasKey(ResponseEvent::class, $events);
        $this->assertSame('onRequest', $events[RequestEvent::class]);
        $this->assertSame('onResponse', $events[ResponseEvent::class]);
    }

    public function testOnRequestUsesIncomingCorrelationId(): void
    {
        // Arrange
        $correlationId = 'test-correlation-id-12345';

        $request = new Request();
        $request->headers->set('X-Correlation-ID', $correlationId);

        $kernel = $this->createMock(HttpKernelInterface::class);
        $event = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);

        // Expect provider to set the incoming correlation ID
        $this->provider
            ->expects($this->once())
            ->method('set')
            ->with($correlationId);

        // Provider->get() should NOT be called when header is present
        $this->provider
            ->expects($this->never())
            ->method('get');

        // Act
        $this->subscriber->onRequest($event);

        // Assert
        $this->assertSame($correlationId, $request->attributes->get('correlation_id'));
    }

    public function testOnRequestGeneratesNewCorrelationIdWhenNotProvided(): void
    {
        // Arrange
        $generatedId = 'generated-id-67890';

        $request = new Request();
        // No X-Correlation-ID header

        $kernel = $this->createMock(HttpKernelInterface::class);
        $event = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);

        // Provider should generate a new ID
        $this->provider
            ->expects($this->once())
            ->method('get')
            ->willReturn($generatedId);

        // Provider should set the generated ID
        $this->provider
            ->expects($this->once())
            ->method('set')
            ->with($generatedId);

        // Act
        $this->subscriber->onRequest($event);

        // Assert
        $this->assertSame($generatedId, $request->attributes->get('correlation_id'));
    }

    public function testOnRequestSetsCorrelationIdInRequestAttributes(): void
    {
        // Arrange
        $correlationId = 'attr-test-id';

        $request = new Request();
        $request->headers->set('X-Correlation-ID', $correlationId);

        $kernel = $this->createMock(HttpKernelInterface::class);
        $event = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);

        $this->provider
            ->method('set');

        // Act
        $this->subscriber->onRequest($event);

        // Assert
        $this->assertTrue($request->attributes->has('correlation_id'));
        $this->assertSame($correlationId, $request->attributes->get('correlation_id'));
    }

    public function testOnRequestHandlesEmptyCorrelationIdHeader(): void
    {
        // Arrange
        $generatedId = 'new-id-empty-case';

        $request = new Request();
        $request->headers->set('X-Correlation-ID', ''); // Empty string

        $kernel = $this->createMock(HttpKernelInterface::class);
        $event = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);

        // Empty string is falsy, so should generate new ID
        $this->provider
            ->expects($this->once())
            ->method('get')
            ->willReturn($generatedId);

        $this->provider
            ->expects($this->once())
            ->method('set')
            ->with($generatedId);

        // Act
        $this->subscriber->onRequest($event);

        // Assert
        $this->assertSame($generatedId, $request->attributes->get('correlation_id'));
    }

    public function testOnResponseAddsCorrelationIdToResponseHeaders(): void
    {
        // Arrange
        $correlationId = 'response-test-id';

        $request = new Request();
        $response = new Response();

        $kernel = $this->createMock(HttpKernelInterface::class);
        $event = new ResponseEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $response);

        // Provider should return the correlation ID
        $this->provider
            ->expects($this->once())
            ->method('get')
            ->willReturn($correlationId);

        // Act
        $this->subscriber->onResponse($event);

        // Assert
        $this->assertTrue($response->headers->has('X-Correlation-ID'));
        $this->assertSame($correlationId, $response->headers->get('X-Correlation-ID'));
    }

    public function testOnResponsePreservesExistingResponseHeaders(): void
    {
        // Arrange
        $correlationId = 'preserve-test-id';

        $request = new Request();
        $response = new Response();
        $response->headers->set('Content-Type', 'application/json');
        $response->headers->set('X-Custom-Header', 'custom-value');

        $kernel = $this->createMock(HttpKernelInterface::class);
        $event = new ResponseEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $response);

        $this->provider
            ->method('get')
            ->willReturn($correlationId);

        // Act
        $this->subscriber->onResponse($event);

        // Assert
        $this->assertSame('application/json', $response->headers->get('Content-Type'));
        $this->assertSame('custom-value', $response->headers->get('X-Custom-Header'));
        $this->assertSame($correlationId, $response->headers->get('X-Correlation-ID'));
    }

    public function testOnResponseOverwritesExistingCorrelationIdHeader(): void
    {
        // Arrange
        $oldId = 'old-correlation-id';
        $newId = 'new-correlation-id';

        $request = new Request();
        $response = new Response();
        $response->headers->set('X-Correlation-ID', $oldId);

        $kernel = $this->createMock(HttpKernelInterface::class);
        $event = new ResponseEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $response);

        $this->provider
            ->method('get')
            ->willReturn($newId);

        // Act
        $this->subscriber->onResponse($event);

        // Assert
        $this->assertSame($newId, $response->headers->get('X-Correlation-ID'));
        $this->assertNotSame($oldId, $response->headers->get('X-Correlation-ID'));
    }

    public function testSubscriberIsReadonly(): void
    {
        // Assert - Class should be readonly
        $reflection = new \ReflectionClass(CorrelationIdSubscriber::class);
        $this->assertTrue(
            $reflection->isReadOnly(),
            'CorrelationIdSubscriber should be readonly'
        );
    }

    public function testOnRequestWithSubRequest(): void
    {
        // Arrange
        $correlationId = 'sub-request-id';

        $request = new Request();
        $request->headers->set('X-Correlation-ID', $correlationId);

        $kernel = $this->createMock(HttpKernelInterface::class);
        // Create a sub-request event
        $event = new RequestEvent($kernel, $request, HttpKernelInterface::SUB_REQUEST);

        // Provider should still be called for sub-requests
        $this->provider
            ->expects($this->once())
            ->method('set')
            ->with($correlationId);

        // Act
        $this->subscriber->onRequest($event);

        // Assert
        $this->assertSame($correlationId, $request->attributes->get('correlation_id'));
    }

    public function testOnResponseWithSubRequest(): void
    {
        // Arrange
        $correlationId = 'sub-response-id';

        $request = new Request();
        $response = new Response();

        $kernel = $this->createMock(HttpKernelInterface::class);
        // Create a sub-request event
        $event = new ResponseEvent($kernel, $request, HttpKernelInterface::SUB_REQUEST, $response);

        $this->provider
            ->method('get')
            ->willReturn($correlationId);

        // Act
        $this->subscriber->onResponse($event);

        // Assert - Header should be set even for sub-requests
        $this->assertSame($correlationId, $response->headers->get('X-Correlation-ID'));
    }

    public function testCorrelationIdFlowFromRequestToResponse(): void
    {
        // This test simulates the full flow: request -> processing -> response

        // Arrange
        $incomingId = 'flow-test-correlation-id';

        $request = new Request();
        $request->headers->set('X-Correlation-ID', $incomingId);

        $response = new Response();

        $kernel = $this->createMock(HttpKernelInterface::class);
        $requestEvent = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);
        $responseEvent = new ResponseEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $response);

        // Mock provider to store and return the same ID
        $storedId = null;
        $this->provider
            ->method('set')
            ->willReturnCallback(function ($id) use (&$storedId) {
                $storedId = $id;
            });

        $this->provider
            ->method('get')
            ->willReturnCallback(function () use (&$storedId) {
                return $storedId;
            });

        // Act
        $this->subscriber->onRequest($requestEvent);
        $this->subscriber->onResponse($responseEvent);

        // Assert
        $this->assertSame($incomingId, $request->attributes->get('correlation_id'));
        $this->assertSame($incomingId, $response->headers->get('X-Correlation-ID'));
    }
}
