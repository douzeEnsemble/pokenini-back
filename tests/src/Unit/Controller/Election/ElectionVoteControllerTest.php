<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller\Election;

use App\Controller\Election\ElectionVoteController;
use App\DTO\ElectionVote;
use App\Service\ModifyElectionVoteService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * @internal
 */
#[CoversClass(ElectionVoteController::class)]
final class ElectionVoteControllerTest extends TestCase
{
    public function testVote(): void
    {
        $electionVote = new ElectionVote();
        $electionVote->winnersSlugs = ['pichu'];
        $electionVote->losersSlugs = ['pikachu'];

        $electionVoteService = $this->createMock(ModifyElectionVoteService::class);
        $electionVoteService
            ->expects($this->once())
            ->method('vote')
        ;

        $controller = new ElectionVoteController();

        $container = $this->createStub(ContainerInterface::class);
        $controller->setContainer($container);

        /** @var JsonResponse $response */
        $response = $controller->vote(
            $electionVoteService,
            $electionVote,
            'demo',
            ''
        );

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testVoteSetsSlugFromRoute(): void
    {
        $electionVote = new ElectionVote();
        $electionVote->winnersSlugs = ['pichu'];
        $electionVote->losersSlugs = ['pikachu'];

        $electionVoteService = $this->createMock(ModifyElectionVoteService::class);
        $electionVoteService
            ->expects($this->once())
            ->method('vote')
        ;

        $controller = new ElectionVoteController();

        $container = $this->createStub(ContainerInterface::class);
        $controller->setContainer($container);

        $controller->vote(
            $electionVoteService,
            $electionVote,
            'pokedex',
            'round-1'
        );

        $this->assertSame('pokedex', $electionVote->dexSlug);
        $this->assertSame('round-1', $electionVote->electionSlug);
    }
}
