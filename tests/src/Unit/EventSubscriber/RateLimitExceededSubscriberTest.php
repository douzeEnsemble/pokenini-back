<?php

declare(strict_types=1);

namespace App\Tests\Unit\EventSubscriber;

use App\EventSubscriber\RateLimitExceededSubscriber;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\RateLimiter\Event\RateLimitExceededEvent;
use Symfony\Component\RateLimiter\RateLimit;

/**
 * @internal
 */
#[CoversClass(RateLimitExceededSubscriber::class)]
final class RateLimitExceededSubscriberTest extends TestCase
{
    #[Test]
    public function getSubscribedEvents(): void
    {
        $this->assertSame(
            [RateLimitExceededEvent::class => 'onRateLimitExceeded'],
            RateLimitExceededSubscriber::getSubscribedEvents(),
        );
    }

    #[Test]
    public function logsTheRejectionWithoutTheToken(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->once())
            ->method('error')
            ->with('Rate limit exceeded', [
                'limiter' => 'write_api',
                // The first 12 characters of the SHA-256 of "Bearer secret-token"
                'key_hash' => substr(hash('sha256', 'Bearer secret-token'), 0, 12),
                'retry_after' => '2026-01-02T03:04:05+00:00',
            ])
        ;

        new RateLimitExceededSubscriber($logger)->onRateLimitExceeded(new RateLimitExceededEvent(
            new RateLimit(0, new \DateTimeImmutable('2026-01-02T03:04:05+00:00'), false, 60),
            'write_api',
            'Bearer secret-token',
        ));
    }

    #[Test]
    public function logsARejectionWithoutKey(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->once())
            ->method('error')
            ->with('Rate limit exceeded', [
                'limiter' => null,
                'key_hash' => null,
                'retry_after' => '2026-01-02T03:04:05+00:00',
            ])
        ;

        new RateLimitExceededSubscriber($logger)->onRateLimitExceeded(new RateLimitExceededEvent(
            new RateLimit(0, new \DateTimeImmutable('2026-01-02T03:04:05+00:00'), false, 60),
        ));
    }
}
