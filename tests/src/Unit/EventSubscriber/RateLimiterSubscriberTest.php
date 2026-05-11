<?php

declare(strict_types=1);

namespace App\Tests\Unit\EventSubscriber;

use App\EventSubscriber\RateLimiterSubscriber;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\RateLimiter\Storage\InMemoryStorage;

/**
 * @internal
 */
#[CoversClass(RateLimiterSubscriber::class)]
final class RateLimiterSubscriberTest extends TestCase
{
    public function testGetSubscribedEvents(): void
    {
        $subscriber = new RateLimiterSubscriber($this->makeFactory(10000));

        $this->assertSame(
            [KernelEvents::REQUEST => ['onRequest', 15]],
            $subscriber::getSubscribedEvents()
        );
    }

    public function testOnRequestIgnoresNonWriteRoutes(): void
    {
        // Exhaust limit with a write route, then verify a non-write route is not rate-limited
        $factory = $this->makeFactory(1);
        $subscriber = new RateLimiterSubscriber($factory);

        $writeRequest = new Request(server: ['HTTP_AUTHORIZATION' => 'Bearer token']);
        $writeRequest->attributes->set('_route', 'app_album_albumupsert_upsert');
        $subscriber->onRequest($this->makeEvent($writeRequest));
        $subscriber->onRequest($this->makeEvent($writeRequest));

        $readRequest = new Request(server: ['HTTP_AUTHORIZATION' => 'Bearer token']);
        $readRequest->attributes->set('_route', 'app_labels_labels_get');
        $readEvent = $this->makeEvent($readRequest);
        $subscriber->onRequest($readEvent);

        $this->assertNull($readEvent->getResponse());
    }

    public function testOnRequestIgnoresSubRequests(): void
    {
        // Exhaust limit with a main request, then verify a sub-request is not rate-limited
        $factory = $this->makeFactory(1);
        $subscriber = new RateLimiterSubscriber($factory);

        $request = new Request(server: ['HTTP_AUTHORIZATION' => 'Bearer token']);
        $request->attributes->set('_route', 'app_album_albumupsert_upsert');
        $subscriber->onRequest($this->makeEvent($request));

        $kernel = $this->createStub(HttpKernelInterface::class);
        $subRequest = new Request(server: ['HTTP_AUTHORIZATION' => 'Bearer token']);
        $subRequest->attributes->set('_route', 'app_album_albumupsert_upsert');
        $subEvent = new RequestEvent($kernel, $subRequest, HttpKernelInterface::SUB_REQUEST);
        $subscriber->onRequest($subEvent);

        $this->assertNull($subEvent->getResponse());
    }

    public function testOnRequestAllowsWhenLimitNotExceeded(): void
    {
        $subscriber = new RateLimiterSubscriber($this->makeFactory(10000));

        $request = new Request(server: ['HTTP_AUTHORIZATION' => 'Bearer some-token']);
        $request->attributes->set('_route', 'app_album_albumupsert_upsert');

        $event = $this->makeEvent($request);
        $subscriber->onRequest($event);

        $this->assertNull($event->getResponse());
    }

    public function testOnRequestReturns429WhenLimitExceeded(): void
    {
        $subscriber = new RateLimiterSubscriber($this->makeFactory(1));

        $request = new Request(server: ['HTTP_AUTHORIZATION' => 'Bearer some-token']);
        $request->attributes->set('_route', 'app_election_electionvote_vote');

        $subscriber->onRequest($this->makeEvent($request));

        $second = $this->makeEvent($request);
        $subscriber->onRequest($second);

        $response = $second->getResponse();
        $this->assertNotNull($response);
        $this->assertSame(429, $response->getStatusCode());

        /** @var array<string, string> $body */
        $body = json_decode((string) $response->getContent(), true);
        $this->assertSame('Too many requests', $body['error']);
    }

    public function testOnRequestTracksUsersByAuthorizationHeader(): void
    {
        // Exhaust limit for token-a; token-b must remain unaffected
        $factory = $this->makeFactory(1);
        $subscriber = new RateLimiterSubscriber($factory);

        $requestA = new Request(server: ['HTTP_AUTHORIZATION' => 'Bearer token-a']);
        $requestA->attributes->set('_route', 'app_album_albumupsert_upsert');
        $subscriber->onRequest($this->makeEvent($requestA));

        $requestB = new Request(server: ['HTTP_AUTHORIZATION' => 'Bearer token-b']);
        $requestB->attributes->set('_route', 'app_album_albumupsert_upsert');
        $eventB = $this->makeEvent($requestB);
        $subscriber->onRequest($eventB);

        $this->assertNull($eventB->getResponse());
    }

    public function testOnRequestUsesClientIpFallbackWhenNoAuthorizationHeader(): void
    {
        // Exhaust limit for IP 10.0.0.1; IP 10.0.0.2 must remain unaffected
        $factory = $this->makeFactory(1);
        $subscriber = new RateLimiterSubscriber($factory);

        $request1 = new Request(server: ['REMOTE_ADDR' => '10.0.0.1']);
        $request1->attributes->set('_route', 'app_trainer_trainerupsert_upsert');
        $subscriber->onRequest($this->makeEvent($request1));
        $second = $this->makeEvent($request1);
        $subscriber->onRequest($second);

        $response = $second->getResponse();
        $this->assertNotNull($response);
        $this->assertSame(429, $response->getStatusCode());

        $request2 = new Request(server: ['REMOTE_ADDR' => '10.0.0.2']);
        $request2->attributes->set('_route', 'app_trainer_trainerupsert_upsert');
        $event2 = $this->makeEvent($request2);
        $subscriber->onRequest($event2);

        $this->assertNull($event2->getResponse());
    }

    private function makeFactory(int $limit): RateLimiterFactory
    {
        return new RateLimiterFactory(
            ['id' => 'test', 'policy' => 'sliding_window', 'limit' => $limit, 'interval' => '1 minute'],
            new InMemoryStorage(),
        );
    }

    private function makeEvent(Request $request): RequestEvent
    {
        $kernel = $this->createStub(HttpKernelInterface::class);

        return new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);
    }
}
