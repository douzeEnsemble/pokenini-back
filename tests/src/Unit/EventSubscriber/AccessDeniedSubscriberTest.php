<?php

declare(strict_types=1);

namespace App\Tests\Unit\EventSubscriber;

use App\EventSubscriber\AccessDeniedSubscriber;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * @internal
 */
#[CoversClass(AccessDeniedSubscriber::class)]
final class AccessDeniedSubscriberTest extends TestCase
{
    #[Test]
    public function getSubscribedEvents(): void
    {
        $this->assertSame(
            [
                KernelEvents::EXCEPTION => [
                    'onException',
                    0,
                ],
            ],
            AccessDeniedSubscriber::getSubscribedEvents(),
        );
    }

    #[Test]
    public function onExceptionLogsAccessDenied(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->once())
            ->method('warning')
            ->with('Access denied on route app_some_route')
        ;

        $request = new Request();
        $request->attributes->set('_route', 'app_some_route');

        $event = $this->makeEvent(
            $request,
            new AccessDeniedException(),
        );

        $subscriber = new AccessDeniedSubscriber($logger);
        $subscriber->onException($event);
    }

    #[Test]
    public function onExceptionIgnoresOtherExceptions(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->never())
            ->method('warning')
        ;

        $event = $this->makeEvent(
            new Request(),
            new \RuntimeException('oops'),
        );

        $subscriber = new AccessDeniedSubscriber($logger);
        $subscriber->onException($event);
    }

    #[Test]
    public function onExceptionIgnoresSubRequests(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->never())->method('warning');

        $kernel = $this->createStub(HttpKernelInterface::class);
        $event = new ExceptionEvent(
            $kernel,
            new Request(),
            HttpKernelInterface::SUB_REQUEST,
            new AccessDeniedException(),
        );

        $subscriber = new AccessDeniedSubscriber($logger);
        $subscriber->onException($event);
    }

    #[Test]
    public function onExceptionUsesUnknownWhenNoRoute(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->once())
            ->method('warning')
            ->with('Access denied on route unknown')
        ;

        $event = $this->makeEvent(
            new Request(),
            new AccessDeniedException(),
        );

        $subscriber = new AccessDeniedSubscriber($logger);
        $subscriber->onException($event);
    }

    private function makeEvent(Request $request, \Throwable $exception): ExceptionEvent
    {
        $kernel = $this->createStub(HttpKernelInterface::class);

        return new ExceptionEvent(
            $kernel,
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $exception,
        );
    }
}
