<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\RateLimiter\RateLimiterFactory;

final class RateLimiterSubscriber implements EventSubscriberInterface
{
    private const array WRITE_ROUTES = [
        'app_album_albumupsert_upsert',
        'app_election_electionvote_vote',
        'app_trainer_trainerupsert_upsert',
        'app_admin_adminactioncalculate_process',
        'app_admin_adminactionupdate_process',
        'app_admin_adminactioninvalidate_process',
    ];

    public function __construct(
        #[Autowire(service: 'limiter.write_api')]
        private readonly RateLimiterFactory $writeApiLimiter,
    ) {}

    #[\Override]
    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST => ['onRequest', 15]];
    }

    public function onRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $route = $request->attributes->get('_route');

        if (!in_array($route, self::WRITE_ROUTES, true)) {
            return;
        }

        $key = $request->headers->get('Authorization', $request->getClientIp() ?? 'unknown') ?? 'unknown';
        $limiter = $this->writeApiLimiter->create(hash('sha256', $key));

        if (!$limiter->consume(1)->isAccepted()) {
            $event->setResponse(new JsonResponse(
                ['error' => 'Too many requests'],
                Response::HTTP_TOO_MANY_REQUESTS
            ));
        }
    }
}
