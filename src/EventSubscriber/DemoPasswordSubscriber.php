<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Validates demo password for real API access
 *
 * This subscriber:
 * - Requires X-Demo-Password header on all requests
 * - Returns 401 if password is missing
 * - Returns 401 if password is wrong
 * - Lets valid requests continue to real API
 */
final class DemoPasswordSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly string $demoPassword
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 100], // Run before CORS (priority 9999)
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        // Only check /analyze endpoint
        if ($request->getPathInfo() !== '/analyze') {
            return;
        }

        // Skip OPTIONS preflight (CORS will handle this)
        if ($request->getMethod() === 'OPTIONS') {
            return;
        }

        $providedPassword = $request->headers->get('X-Demo-Password');

        // Password is required - reject if missing
        if (!$providedPassword) {
            $response = new JsonResponse([
                'error' => 'Demo password required',
                'message' => 'This API requires a demo password. Contact me to get access.',
                'contact' => [
                    'email' => 'richard.trujillo.torres@gmail.com',
                    'linkedin' => 'https://www.linkedin.com/in/richard-trujillo-1572b0b7/',
                ]
            ], 401);

            // Add CORS headers to error response
            $response->headers->set('Access-Control-Allow-Origin', '*');
            $response->headers->set('Access-Control-Allow-Methods', 'POST, OPTIONS');
            $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, X-Correlation-ID, X-Demo-Password');

            $event->setResponse($response);
            return;
        }

        // Password provided - validate it
        if ($providedPassword !== $this->demoPassword) {
            $response = new JsonResponse([
                'error' => 'Invalid demo password',
                'message' => 'The demo password you provided is incorrect. Contact me for access.',
                'contact' => [
                    'email' => 'richard.trujillo.torres@gmail.com',
                    'linkedin' => 'https://www.linkedin.com/in/richard-trujillo-1572b0b7/',
                ]
            ], 401);

            // Add CORS headers to error response
            $response->headers->set('Access-Control-Allow-Origin', '*');
            $response->headers->set('Access-Control-Allow-Methods', 'POST, OPTIONS');
            $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, X-Correlation-ID, X-Demo-Password');

            $event->setResponse($response);
            return;
        }

        // Password is valid - let request continue to controller
    }
}
