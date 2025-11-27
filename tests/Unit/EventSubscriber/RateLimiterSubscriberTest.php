<?php

declare(strict_types=1);

namespace App\Tests\Unit\EventSubscriber;

use App\EventSubscriber\RateLimiterSubscriber;
use App\Service\RateLimit\RateLimiter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

class RateLimiterSubscriberTest extends TestCase
{
    private RateLimiter $mockRateLimiter;
    private RateLimiterSubscriber $subscriber;

    protected function setUp(): void
    {
        $this->mockRateLimiter = $this->createMock(RateLimiter::class);

        // Pass 'prod' as the environment so tests actually run the rate limiter logic
        $this->subscriber = new RateLimiterSubscriber(
            $this->mockRateLimiter,
            'prod'
        );
    }

    public function testGetSubscribedEvents(): void
    {
        // Act
        $events = RateLimiterSubscriber::getSubscribedEvents();

        // Assert
        $this->assertIsArray($events);
        $this->assertArrayHasKey(KernelEvents::REQUEST, $events);
        $this->assertSame('onKernelRequest', $events[KernelEvents::REQUEST]);
    }

    public function testOnKernelRequestAllowsRequestWhenUnderRateLimit(): void
    {
        // Arrange
        $request = new Request();
        $request->headers->set('X-Client-Id', 'test-client-123');

        $kernel = $this->createMock(HttpKernelInterface::class);
        $event = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);

        // Rate limiter should check and not throw
        $this->mockRateLimiter
            ->expects($this->once())
            ->method('checkOrThrow')
            ->with('test-client-123');

        // Act & Assert - Should not throw
        $this->subscriber->onKernelRequest($event);

        $this->assertTrue(true); // If we get here, no exception was thrown
    }

    public function testOnKernelRequestThrowsExceptionWhenRateLimitExceeded(): void
    {
        // Arrange
        $request = new Request();
        $request->headers->set('X-Client-Id', 'rate-limited-client');

        $kernel = $this->createMock(HttpKernelInterface::class);
        $event = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);

        // Rate limiter throws RuntimeException
        $this->mockRateLimiter
            ->expects($this->once())
            ->method('checkOrThrow')
            ->with('rate-limited-client')
            ->willThrowException(new \RuntimeException('Rate limit exceeded'));

        // Assert
        $this->expectException(TooManyRequestsHttpException::class);
        $this->expectExceptionMessage('Rate limit exceeded.');

        // Act
        $this->subscriber->onKernelRequest($event);
    }

    public function testOnKernelRequestUsesClientIdFromHeader(): void
    {
        // Arrange
        $clientId = 'custom-client-id-456';

        $request = new Request();
        $request->headers->set('X-Client-Id', $clientId);

        $kernel = $this->createMock(HttpKernelInterface::class);
        $event = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);

        // Verify correct client ID is passed
        $this->mockRateLimiter
            ->expects($this->once())
            ->method('checkOrThrow')
            ->with($clientId);

        // Act
        $this->subscriber->onKernelRequest($event);
    }

    public function testOnKernelRequestFallsBackToClientIpWhenNoHeader(): void
    {
        // Arrange
        $clientIp = '192.168.1.100';

        $request = Request::create('http://example.com', 'GET', [], [], [], [
            'REMOTE_ADDR' => $clientIp,
        ]);
        // No X-Client-Id header

        $kernel = $this->createMock(HttpKernelInterface::class);
        $event = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);

        // Should use IP address as fallback
        $this->mockRateLimiter
            ->expects($this->once())
            ->method('checkOrThrow')
            ->with($clientIp);

        // Act
        $this->subscriber->onKernelRequest($event);
    }

    public function testOnKernelRequestUsesUnknownWhenNoClientIdOrIp(): void
    {
        // Arrange
        $request = new Request();
        // No X-Client-Id header and no IP

        $kernel = $this->createMock(HttpKernelInterface::class);
        $event = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);

        // Should use 'unknown' as final fallback
        $this->mockRateLimiter
            ->expects($this->once())
            ->method('checkOrThrow')
            ->with('unknown');

        // Act
        $this->subscriber->onKernelRequest($event);
    }

    public function testOnKernelRequestIgnoresSubRequests(): void
    {
        // Arrange
        $request = new Request();
        $request->headers->set('X-Client-Id', 'sub-request-client');

        $kernel = $this->createMock(HttpKernelInterface::class);
        // Create a SUB_REQUEST event
        $event = new RequestEvent($kernel, $request, HttpKernelInterface::SUB_REQUEST);

        // Rate limiter should NOT be called for sub-requests
        $this->mockRateLimiter
            ->expects($this->never())
            ->method('checkOrThrow');

        // Act
        $this->subscriber->onKernelRequest($event);
    }

    public function testOnKernelRequestPrioritizesHeaderOverIp(): void
    {
        // Arrange
        $clientId = 'header-client-id';
        $clientIp = '10.0.0.1';

        $request = Request::create('http://example.com', 'GET', [], [], [], [
            'REMOTE_ADDR' => $clientIp,
        ]);
        $request->headers->set('X-Client-Id', $clientId);

        $kernel = $this->createMock(HttpKernelInterface::class);
        $event = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);

        // Should use header value, not IP
        $this->mockRateLimiter
            ->expects($this->once())
            ->method('checkOrThrow')
            ->with($clientId);

        // Act
        $this->subscriber->onKernelRequest($event);
    }

    public function testOnKernelRequestHandlesEmptyClientIdHeader(): void
    {
        // Arrange - Create fresh mock and subscriber for this test
        $rateLimiterMock = $this->createMock(RateLimiter::class);
        $subscriberWithMock = new RateLimiterSubscriber($rateLimiterMock, 'prod');

        $clientIp = '172.16.0.1';

        $request = Request::create('http://example.com', 'GET', [], [], [], [
            'REMOTE_ADDR' => $clientIp,
        ]);
        $request->headers->set('X-Client-Id', ''); // Empty string

        $kernel = $this->createMock(HttpKernelInterface::class);
        $event = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);

        // Important: Empty string '' is NOT null, so ?? operator doesn't fall through
        // The actual code will pass empty string to checkOrThrow()
        $rateLimiterMock
            ->expects($this->once())
            ->method('checkOrThrow')
            ->with(''); // Expect empty string, not the IP!

        // Act - Use the subscriber with the mock
        $subscriberWithMock->onKernelRequest($event);

        // Assert - If we get here, the test passed (no exception thrown)
        $this->assertTrue(true);
    }

    public function testOnKernelRequestWrapsRuntimeExceptionAsTooManyRequests(): void
    {
        // Arrange
        $request = new Request();
        $request->headers->set('X-Client-Id', 'test-client');

        $kernel = $this->createMock(HttpKernelInterface::class);
        $event = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);

        $originalException = new \RuntimeException('Custom rate limit message');

        $this->mockRateLimiter
            ->method('checkOrThrow')
            ->willThrowException($originalException);

        // Act & Assert
        try {
            $this->subscriber->onKernelRequest($event);
            $this->fail('Expected TooManyRequestsHttpException to be thrown');
        } catch (TooManyRequestsHttpException $e) {
            $this->assertSame('Rate limit exceeded.', $e->getMessage());
            $this->assertSame(429, $e->getStatusCode());
        }
    }

    public function testOnKernelRequestWithIpv6Address(): void
    {
        // Arrange
        $ipv6 = '2001:0db8:85a3:0000:0000:8a2e:0370:7334';

        $request = Request::create('http://example.com', 'GET', [], [], [], [
            'REMOTE_ADDR' => $ipv6,
        ]);

        $kernel = $this->createMock(HttpKernelInterface::class);
        $event = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);

        // Should handle IPv6 addresses
        $this->mockRateLimiter
            ->expects($this->once())
            ->method('checkOrThrow')
            ->with($ipv6);

        // Act
        $this->subscriber->onKernelRequest($event);
    }

    public function testOnKernelRequestWithProxiedRequest(): void
    {
        // Arrange
        $realIp = '203.0.113.5';

        $request = Request::create('http://example.com', 'GET', [], [], [], [
            'REMOTE_ADDR' => '10.0.0.1', // Proxy IP
            'HTTP_X_FORWARDED_FOR' => $realIp,
        ]);

        // Trust the proxy
        Request::setTrustedProxies(['10.0.0.1'], Request::HEADER_X_FORWARDED_FOR);

        $kernel = $this->createMock(HttpKernelInterface::class);
        $event = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);

        // Should use the real client IP from X-Forwarded-For
        $this->mockRateLimiter
            ->expects($this->once())
            ->method('checkOrThrow')
            ->with($realIp);

        // Act
        $this->subscriber->onKernelRequest($event);

        // Cleanup
        Request::setTrustedProxies([], -1);
    }

    public function testMultipleRequestsWithSameClientId(): void
    {
        // Arrange
        $clientId = 'repeated-client';

        $kernel = $this->createMock(HttpKernelInterface::class);

        // First request - allowed
        $request1 = new Request();
        $request1->headers->set('X-Client-Id', $clientId);
        $event1 = new RequestEvent($kernel, $request1, HttpKernelInterface::MAIN_REQUEST);

        // Second request - also allowed
        $request2 = new Request();
        $request2->headers->set('X-Client-Id', $clientId);
        $event2 = new RequestEvent($kernel, $request2, HttpKernelInterface::MAIN_REQUEST);

        // Both should check the same client ID
        $this->mockRateLimiter
            ->expects($this->exactly(2))
            ->method('checkOrThrow')
            ->with($clientId);

        // Act
        $this->subscriber->onKernelRequest($event1);
        $this->subscriber->onKernelRequest($event2);
    }

    public function testOnKernelRequestWithSpecialCharactersInClientId(): void
    {
        // Arrange
        $clientId = 'client-id-with-special!@#$%^&*()';

        $request = new Request();
        $request->headers->set('X-Client-Id', $clientId);

        $kernel = $this->createMock(HttpKernelInterface::class);
        $event = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);

        // Should pass the client ID as-is
        $this->mockRateLimiter
            ->expects($this->once())
            ->method('checkOrThrow')
            ->with($clientId);

        // Act
        $this->subscriber->onKernelRequest($event);
    }

    public function testOnKernelRequestDoesNotModifyRequest(): void
    {
        // Arrange
        $request = new Request();
        $request->headers->set('X-Client-Id', 'test-client');
        $request->headers->set('Content-Type', 'application/json');

        $kernel = $this->createMock(HttpKernelInterface::class);
        $event = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);

        $this->mockRateLimiter
            ->method('checkOrThrow');

        // Act
        $this->subscriber->onKernelRequest($event);

        // Assert - Request should remain unchanged
        $this->assertSame('test-client', $request->headers->get('X-Client-Id'));
        $this->assertSame('application/json', $request->headers->get('Content-Type'));
    }
}
