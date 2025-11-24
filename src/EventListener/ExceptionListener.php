<?php

namespace App\EventListener;

use App\Exception\ValidationException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class ExceptionListener
{
    public function __construct(
        private LoggerInterface $logger,
        private string $environment,
    ) {
    }

    public function __invoke(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        // 1. Validation errors
        if ($exception instanceof ValidationException) {

            $violations = [];
            foreach ($exception->getViolations() as $violation) {
                $violations[] = [
                    'field' => $violation->getPropertyPath(),
                    'message' => $violation->getMessage(),
                ];
            }

            $event->setResponse(new JsonResponse([
                'error' => true,
                'message' => 'Validation failed',
                'violations' => $violations,
            ], 400));

            return;
        }

        // Log everything
        $this->logger->error('Unhandled exception', [
            'message' => $exception->getMessage(),
            'class'   => get_class($exception),
            'trace'   => $exception->getTraceAsString(),
        ]);

        // Determine HTTP status and message
        if ($exception instanceof HttpExceptionInterface) {
            $statusCode = $exception->getStatusCode();
            $message = $exception->getMessage();
        } else {
            $statusCode = 500;
            $message = 'dev' === $this->environment
                ? $exception->getMessage()
                : 'Internal server error';
        }

        $response = [
            'error'   => true,
            'message' => $message,
        ];

        // Extra debug info in dev-mode
        if ('dev' === $this->environment) {
            $response['exception'] = [
                'class' => get_class($exception),
                'file'  => $exception->getFile(),
                'line'  => $exception->getLine(),
            ];
        }

        $event->setResponse(new JsonResponse($response, $statusCode));
    }
}
