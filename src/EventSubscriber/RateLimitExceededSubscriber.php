<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\RateLimiter\Event\RateLimitExceededEvent;

final class RateLimitExceededSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    #[\Override]
    public static function getSubscribedEvents(): array
    {
        return [RateLimitExceededEvent::class => 'onRateLimitExceeded'];
    }

    public function onRateLimitExceeded(RateLimitExceededEvent $event): void
    {
        $key = $event->getKey();

        // An error, not a warning: production only writes the logs of a request once it has an error.
        // The key may be the caller's token, so only a hash of it is written.
        $this->logger->error('Rate limit exceeded', [
            'limiter' => $event->getLimiterName(),
            'key_hash' => null === $key ? null : substr(hash('sha256', $key), 0, 12),
            'retry_after' => $event->getRateLimit()->getRetryAfter()->format(\DATE_ATOM),
        ]);
    }
}
