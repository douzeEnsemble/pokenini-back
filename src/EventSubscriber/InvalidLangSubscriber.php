<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Exception\InvalidLangException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * A language which is not enabled is a bad request.
 */
final class InvalidLangSubscriber implements EventSubscriberInterface
{
    #[\Override]
    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::EXCEPTION => ['onException', 10]];
    }

    public function onException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        if (!$exception instanceof InvalidLangException) {
            return;
        }

        $event->setResponse(new JsonResponse(['error' => $exception->getMessage()], Response::HTTP_BAD_REQUEST));
    }
}
