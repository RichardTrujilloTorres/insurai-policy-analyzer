<?php

namespace App\EventSubscriber;

use App\Service\RateLimit\RateLimiter;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

class RateLimiterSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private RateLimiter $rateLimiter,
        private ?string $environment = null,
    ) {
        $this->environment = $environment ?? $_ENV['APP_ENV'] ?? 'prod';
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest',
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        // Skip rate limiting in test environment (integration tests)
        // But allow unit tests to override this by passing environment explicitly
        if ('test' === $this->environment) {
            return;
        }

        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        // Identify the client (IP-based fallback)
        $clientId = $request->headers->get('X-Client-Id')
            ?? $request->getClientIp()
            ?? 'unknown';

        try {
            $this->rateLimiter->checkOrThrow($clientId);
        } catch (\RuntimeException $e) {
            throw new TooManyRequestsHttpException(null, 'Rate limit exceeded.');
        }
    }
}
