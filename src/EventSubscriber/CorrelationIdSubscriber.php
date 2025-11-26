<?php

namespace App\EventSubscriber;

use App\Infrastructure\Aws\CorrelationIdProvider;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

readonly class CorrelationIdSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private CorrelationIdProvider $provider
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            RequestEvent::class  => 'onRequest',
            ResponseEvent::class => 'onResponse',
        ];
    }

    public function onRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        // Extract existing header or generate new one
        $incoming = $request->headers->get('X-Correlation-ID');

        $id = $incoming ?: $this->provider->get();
        $this->provider->set($id);

        // So you can see it in logs:
        $request->attributes->set('correlation_id', $id);
    }

    public function onResponse(ResponseEvent $event): void
    {
        $event->getResponse()->headers->set(
            'X-Correlation-ID',
            $this->provider->get()
        );
    }
}
