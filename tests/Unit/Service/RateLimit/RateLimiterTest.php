<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\RateLimit;

use App\Service\RateLimit\RateLimiter;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class RateLimiterTest extends TestCase
{
    private CacheInterface $cache;
    private RateLimiter $rateLimiter;

    protected function setUp(): void
    {
        $this->cache = $this->createMock(CacheInterface::class);
        $this->rateLimiter = new RateLimiter($this->cache, 5, 60);
    }

    public function testCheckOrThrowAllowsFirstRequest(): void
    {
        // Arrange
        $clientId = 'client-123';
        $cacheKey = 'rate_limit_'.md5($clientId);

        // First call returns 0 (no previous requests)
        $this->cache
            ->expects($this->exactly(2))
            ->method('get')
            ->willReturnCallback(function ($key, $callback) {
                $item = $this->createMock(ItemInterface::class);

                return $callback($item);
            });

        $this->cache
            ->expects($this->once())
            ->method('delete')
            ->with($cacheKey);

        // Act & Assert - Should not throw
        $this->rateLimiter->checkOrThrow($clientId);
        $this->assertTrue(true); // If we get here, no exception was thrown
    }

    public function testCheckOrThrowAllowsRequestsUnderLimit(): void
    {
        // Arrange
        $clientId = 'client-456';
        $currentCount = 3; // Under limit of 5

        $this->cache
            ->method('get')
            ->willReturnOnConsecutiveCalls($currentCount, $currentCount + 1);

        $this->cache->method('delete');

        // Act & Assert - Should not throw
        $this->rateLimiter->checkOrThrow($clientId);
        $this->assertTrue(true);
    }

    public function testCheckOrThrowThrowsWhenLimitExceeded(): void
    {
        // Arrange
        $clientId = 'rate-limited-client';
        $currentCount = 5; // At limit

        $this->cache
            ->method('get')
            ->willReturn($currentCount);

        // Assert
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Rate limit exceeded. Try again later.');

        // Act
        $this->rateLimiter->checkOrThrow($clientId);
    }

    public function testCheckOrThrowThrowsWhenAtLimit(): void
    {
        // Arrange - exactly at limit (5 requests)
        $clientId = 'at-limit-client';

        $this->cache
            ->method('get')
            ->willReturn(5);

        // Assert
        $this->expectException(\RuntimeException::class);

        // Act
        $this->rateLimiter->checkOrThrow($clientId);
    }

    public function testCheckOrThrowThrowsWhenOverLimit(): void
    {
        // Arrange - over limit (6 requests)
        $clientId = 'over-limit-client';

        $this->cache
            ->method('get')
            ->willReturn(6);

        // Assert
        $this->expectException(\RuntimeException::class);

        // Act
        $this->rateLimiter->checkOrThrow($clientId);
    }

    public function testCheckOrThrowUsesMd5HashedCacheKey(): void
    {
        // Arrange
        $clientId = 'test-client';
        $expectedKey = 'rate_limit_'.md5($clientId);

        $this->cache
            ->expects($this->exactly(2))
            ->method('get')
            ->with($expectedKey)
            ->willReturnCallback(function ($key, $callback) {
                $item = $this->createMock(ItemInterface::class);

                return $callback($item);
            });

        $this->cache
            ->expects($this->once())
            ->method('delete')
            ->with($expectedKey);

        // Act
        $this->rateLimiter->checkOrThrow($clientId);
    }

    public function testCheckOrThrowIncrementsCounter(): void
    {
        // Arrange
        $clientId = 'increment-test';
        $initialCount = 2;

        $callCount = 0;
        $this->cache
            ->method('get')
            ->willReturnCallback(function ($key, $callback) use (&$callCount, $initialCount) {
                ++$callCount;
                $item = $this->createMock(ItemInterface::class);

                if (1 === $callCount) {
                    // First call: return initial count
                    return $initialCount;
                } else {
                    // Second call: should increment
                    return $callback($item);
                }
            });

        $this->cache->method('delete');

        // Act
        $this->rateLimiter->checkOrThrow($clientId);

        // Assert
        $this->assertSame(2, $callCount);
    }

    public function testCheckOrThrowSetsExpirationTime(): void
    {
        // Arrange
        $clientId = 'expiration-test';

        $item = $this->createMock(ItemInterface::class);
        $item
            ->expects($this->exactly(2))
            ->method('expiresAfter')
            ->with(60); // windowSeconds

        $this->cache
            ->method('get')
            ->willReturnCallback(function ($key, $callback) use ($item) {
                return $callback($item);
            });

        $this->cache->method('delete');

        // Act
        $this->rateLimiter->checkOrThrow($clientId);
    }

    public function testCheckOrThrowWithDifferentClientIds(): void
    {
        // Arrange
        $client1 = 'client-1';
        $client2 = 'client-2';

        $this->cache
            ->method('get')
            ->willReturnCallback(function ($key, $callback) {
                $item = $this->createMock(ItemInterface::class);

                return $callback($item);
            });

        $this->cache->method('delete');

        // Act - Both clients should be allowed
        $this->rateLimiter->checkOrThrow($client1);
        $this->rateLimiter->checkOrThrow($client2);

        // Assert
        $this->assertTrue(true);
    }

    public function testCheckOrThrowDeletesBeforeIncrement(): void
    {
        // Arrange
        $clientId = 'delete-test';

        $deleteCallOrder = null;
        $getCallOrder = null;
        $callSequence = 0;

        $this->cache
            ->method('delete')
            ->willReturnCallback(function () use (&$deleteCallOrder, &$callSequence) {
                $deleteCallOrder = ++$callSequence;

                return true; // delete() must return bool
            });

        $this->cache
            ->method('get')
            ->willReturnCallback(function ($key, $callback) use (&$getCallOrder, &$callSequence) {
                if (null === $getCallOrder) {
                    $getCallOrder = ++$callSequence;

                    return 0; // First call
                }
                ++$callSequence;
                $item = $this->createMock(ItemInterface::class);

                return $callback($item);
            });

        // Act
        $this->rateLimiter->checkOrThrow($clientId);

        // Assert - delete should be called between the two get calls
        $this->assertNotNull($deleteCallOrder);
        $this->assertGreaterThan(1, $deleteCallOrder);
    }

    public function testConstructorWithCustomLimits(): void
    {
        // Arrange
        $customRateLimiter = new RateLimiter($this->cache, 10, 120);

        $this->cache
            ->method('get')
            ->willReturn(9); // Just under custom limit

        $this->cache->method('delete');

        // Act & Assert - Should not throw
        $customRateLimiter->checkOrThrow('test-client');
        $this->assertTrue(true);
    }

    public function testConstructorWithCustomLimitsThrowsAtLimit(): void
    {
        // Arrange
        $customRateLimiter = new RateLimiter($this->cache, 3, 30);

        $this->cache
            ->method('get')
            ->willReturn(3); // At custom limit

        // Assert
        $this->expectException(\RuntimeException::class);

        // Act
        $customRateLimiter->checkOrThrow('test-client');
    }

    public function testCheckOrThrowWithEmptyClientId(): void
    {
        // Arrange
        $clientId = '';
        $cacheKey = 'rate_limit_'.md5($clientId);

        $this->cache
            ->method('get')
            ->willReturnCallback(function ($key, $callback) {
                $item = $this->createMock(ItemInterface::class);

                return $callback($item);
            });

        $this->cache->method('delete');

        // Act & Assert - Should not throw
        $this->rateLimiter->checkOrThrow($clientId);
        $this->assertTrue(true);
    }

    public function testCheckOrThrowWithSpecialCharactersInClientId(): void
    {
        // Arrange
        $clientId = 'client!@#$%^&*()_+-=[]{}|;:,.<>?';

        $this->cache
            ->method('get')
            ->willReturnCallback(function ($key, $callback) {
                $item = $this->createMock(ItemInterface::class);

                return $callback($item);
            });

        $this->cache->method('delete');

        // Act & Assert - MD5 should handle special characters
        $this->rateLimiter->checkOrThrow($clientId);
        $this->assertTrue(true);
    }

    public function testCheckOrThrowMultipleCallsSameClient(): void
    {
        // Arrange
        $clientId = 'repeated-client';
        $counts = [0, 1, 2, 3, 4]; // Simulate multiple calls

        $callIndex = 0;
        $this->cache
            ->method('get')
            ->willReturnCallback(function ($key, $callback) use (&$callIndex, $counts) {
                if (0 === $callIndex % 2) {
                    // First call in pair: return current count
                    $result = $counts[intdiv($callIndex, 2)];
                    ++$callIndex;

                    return $result;
                } else {
                    // Second call in pair: increment
                    ++$callIndex;
                    $item = $this->createMock(ItemInterface::class);

                    return $callback($item);
                }
            });

        $this->cache->method('delete');

        // Act - Make 5 calls (should all succeed)
        for ($i = 0; $i < 5; ++$i) {
            $this->rateLimiter->checkOrThrow($clientId);
        }

        // Assert
        $this->assertTrue(true);
    }

    public function testCheckOrThrowSixthCallThrows(): void
    {
        // Arrange
        $clientId = 'sixth-call-client';

        // Simulate 5 previous requests
        $this->cache
            ->method('get')
            ->willReturn(5);

        // Assert
        $this->expectException(\RuntimeException::class);

        // Act
        $this->rateLimiter->checkOrThrow($clientId);
    }

    public function testCheckOrThrowWithVeryLongClientId(): void
    {
        // Arrange
        $clientId = str_repeat('a', 1000); // Very long client ID

        $this->cache
            ->method('get')
            ->willReturnCallback(function ($key, $callback) {
                $item = $this->createMock(ItemInterface::class);

                return $callback($item);
            });

        $this->cache->method('delete');

        // Act & Assert - MD5 should hash it to fixed length
        $this->rateLimiter->checkOrThrow($clientId);
        $this->assertTrue(true);
    }

    public function testCheckOrThrowCacheKeyPrefixIsConsistent(): void
    {
        // Arrange
        $clientId = 'consistency-test';
        $expectedPrefix = 'rate_limit_';

        $actualKey = null;
        $this->cache
            ->method('get')
            ->willReturnCallback(function ($key, $callback) use (&$actualKey) {
                $actualKey = $key;
                $item = $this->createMock(ItemInterface::class);

                return $callback($item);
            });

        $this->cache->method('delete');

        // Act
        $this->rateLimiter->checkOrThrow($clientId);

        // Assert
        $this->assertStringStartsWith($expectedPrefix, $actualKey);
    }

    public function testCheckOrThrowExceptionMessageIsDescriptive(): void
    {
        // Arrange
        $clientId = 'exception-message-test';

        $this->cache
            ->method('get')
            ->willReturn(10); // Over limit

        // Act & Assert
        try {
            $this->rateLimiter->checkOrThrow($clientId);
            $this->fail('Expected RuntimeException to be thrown');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('Rate limit exceeded', $e->getMessage());
            $this->assertStringContainsString('Try again later', $e->getMessage());
        }
    }
}
