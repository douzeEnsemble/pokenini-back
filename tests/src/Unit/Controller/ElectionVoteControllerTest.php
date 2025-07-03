<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller;

use App\Controller\ElectionVoteController;
use App\Service\ElectionVoteService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
#[CoversClass(ElectionVoteController::class)]
class ElectionVoteControllerTest extends TestCase
{
    public function testVote(): void
    {
        $request = new Request([], ['winners_slugs' => ['pichu'], 'losers_slugs' => ['pikachu']]);

        $electionVoteService = $this->createMock(ElectionVoteService::class);
        $electionVoteService
            ->expects($this->once())
            ->method('vote')
        ;

        $controller = new ElectionVoteController();

        $container = $this->createMock(ContainerInterface::class);
        $controller->setContainer($container);

        /** @var JsonResponse $response */
        $response = $controller->vote(
            $request,
            $electionVoteService,
            'demo',
            ''
        );

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testVoteEmpty(): void
    {
        $request = new Request();

        $electionVoteService = $this->createMock(ElectionVoteService::class);

        $controller = new ElectionVoteController();

        $response = $controller->vote(
            $request,
            $electionVoteService,
            'demo',
            ''
        );

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame('{"error":"Data cannot be empty"}', (string) $response->getContent());
    }

    public function testVoteNonvalid(): void
    {
        $request = new Request([], ['winners_slugs' => ['pichu']]);

        $electionVoteService = $this->createMock(ElectionVoteService::class);

        $controller = new ElectionVoteController();

        $response = $controller->vote(
            $request,
            $electionVoteService,
            'demo',
            ''
        );

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame(
            '{"error":"The required option \u0022losers_slugs\u0022 is missing."}',
            (string) $response->getContent()
        );
    }
}
