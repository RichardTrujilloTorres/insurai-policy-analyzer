<?php

namespace App\Tests\EventListener;

use App\EventListener\ExceptionListener;
use App\Exception\ValidationException;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationList;

class ExceptionListenerTest extends TestCase
{
    private function createEvent(\Throwable $exception): ExceptionEvent
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = new Request();

        return new ExceptionEvent(
            $kernel,
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $exception
        );
    }

    public function testValidationExceptionReturns400WithViolations(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $listener = new ExceptionListener($logger, 'prod');

        // Build violation mocks
        $v1 = $this->createMock(ConstraintViolationInterface::class);
        $v1->method('getPropertyPath')->willReturn('field1');
        $v1->method('getMessage')->willReturn('Error 1');

        $v2 = $this->createMock(ConstraintViolationInterface::class);
        $v2->method('getPropertyPath')->willReturn('field2');
        $v2->method('getMessage')->willReturn('Error 2');

        $violations = new ConstraintViolationList([$v1, $v2]);

        $exception = new ValidationException($violations);
        $event = $this->createEvent($exception);

        ($listener)($event);

        $response = $event->getResponse();
        $this->assertSame(400, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);

        $this->assertTrue($data['error']);
        $this->assertSame('Validation failed', $data['message']);
        $this->assertCount(2, $data['violations']);

        $this->assertSame('field1', $data['violations'][0]['field']);
        $this->assertSame('Error 1', $data['violations'][0]['message']);
    }

    public function testGenericExceptionInProd(): void
    {
        $logger = $this->createMock(LoggerInterface::class);

        // Logger MUST be called with specific context keys
        $logger->expects($this->once())
            ->method('error')
            ->with(
                'Unhandled exception',
                $this->callback(function (array $context) {
                    return isset($context['message'], $context['class'], $context['trace']);
                })
            );

        $listener = new ExceptionListener($logger, 'prod');
        $exception = new \RuntimeException('Boom!');
        $event = $this->createEvent($exception);

        ($listener)($event);

        $response = $event->getResponse();
        $this->assertSame(500, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);

        $this->assertTrue($data['error']);
        $this->assertSame('Internal server error', $data['message']);

        $this->assertArrayNotHasKey('exception', $data);
    }

    public function testGenericExceptionInDev(): void
    {
        $logger = $this->createMock(LoggerInterface::class);

        $listener = new ExceptionListener($logger, 'dev');
        $exception = new \RuntimeException('Dev Boom');
        $event = $this->createEvent($exception);

        ($listener)($event);

        $response = $event->getResponse();
        $this->assertSame(500, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);

        $this->assertTrue($data['error']);
        $this->assertSame('Dev Boom', $data['message']);

        $this->assertArrayHasKey('exception', $data);
        $this->assertSame(\RuntimeException::class, $data['exception']['class']);
        $this->assertArrayHasKey('file', $data['exception']);
        $this->assertArrayHasKey('line', $data['exception']);
    }

    public function testHttpExceptionReturnsCustomStatusAndMessage(): void
    {
        $logger = $this->createMock(LoggerInterface::class);

        $listener = new ExceptionListener($logger, 'prod');

        $exception = new NotFoundHttpException('Not here');
        $event = $this->createEvent($exception);

        ($listener)($event);

        $response = $event->getResponse();
        $this->assertSame(404, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);

        $this->assertTrue($data['error']);
        $this->assertSame('Not here', $data['message']);
    }
}
