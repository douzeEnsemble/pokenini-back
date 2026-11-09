<?php

declare(strict_types=1);

namespace App\Tests\Unit\EventSubscriber;

use App\EventSubscriber\InvalidLangSubscriber;
use App\Exception\InvalidLangException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * @internal
 */
#[CoversClass(InvalidLangSubscriber::class)]
final class InvalidLangSubscriberTest extends TestCase
{
    #[Test]
    public function subscribedEvents(): void
    {
        $this->assertSame(
            [KernelEvents::EXCEPTION => ['onException', 10]],
            InvalidLangSubscriber::getSubscribedEvents(),
        );
    }

    #[Test]
    public function invalidLanguage(): void
    {
        $event = $this->event(new InvalidLangException('Invalid language'));

        new InvalidLangSubscriber()->onException($event);

        $response = $event->getResponse();
        $this->assertNotNull($response);
        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame('{"error":"Invalid language"}', $response->getContent());
    }

    #[Test]
    public function otherException(): void
    {
        $event = $this->event(new \RuntimeException('Oops'));

        new InvalidLangSubscriber()->onException($event);

        $this->assertNull($event->getResponse());
    }

    private function event(\Throwable $exception): ExceptionEvent
    {
        return new ExceptionEvent(
            $this->createStub(HttpKernelInterface::class),
            new Request(),
            HttpKernelInterface::MAIN_REQUEST,
            $exception,
        );
    }
}
