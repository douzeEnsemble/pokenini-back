<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\DTO\ElectionVote;
use App\Security\UserTokenService;
use App\Service\Api\ModifyElectionVoteApiService;
use App\Service\ModifyElectionVoteService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(ModifyElectionVoteService::class)]
final class ModifyElectionVoteServiceTest extends TestCase
{
    public function testVote(): void
    {
        $userTokenService = $this->createMock(UserTokenService::class);
        $userTokenService
            ->expects($this->once())
            ->method('getLoggedUserToken')
            ->willReturn('8800088')
        ;

        $electionVote = new ElectionVote(
            'demo',
            'whatever',
            [
                'winners_slugs' => ['pichu'],
                'losers_slugs' => ['pikachu', 'raichu'],
            ],
        );

        $apiService = $this->createMock(ModifyElectionVoteApiService::class);
        $apiService
            ->expects($this->once())
            ->method('vote')
            ->with(
                '8800088',
                $electionVote,
            )
        ;

        $service = new ModifyElectionVoteService($userTokenService, $apiService);
        $service->vote($electionVote);
    }

    public function testVoteWinnerAsLoser(): void
    {
        $userTokenService = $this->createMock(UserTokenService::class);
        $userTokenService
            ->expects($this->once())
            ->method('getLoggedUserToken')
            ->willReturn('8800088')
        ;

        $electionVote = new ElectionVote(
            'demo',
            'whatever',
            [
                'winners_slugs' => ['pichu'],
                'losers_slugs' => ['pikachu', 'pichu', 'raichu'],
            ],
        );

        $apiService = $this->createMock(ModifyElectionVoteApiService::class);
        $apiService
            ->expects($this->once())
            ->method('vote')
            ->with(
                '8800088',
                $electionVote,
            )
        ;

        $service = new ModifyElectionVoteService($userTokenService, $apiService);
        $service->vote($electionVote);
    }

    public function testVoteAllLosers(): void
    {
        $userTokenService = $this->createMock(UserTokenService::class);
        $userTokenService
            ->expects($this->once())
            ->method('getLoggedUserToken')
            ->willReturn('8800088')
        ;

        $electionVote = new ElectionVote(
            'demo',
            'whatever',
            [
                'winners_slugs' => [],
                'losers_slugs' => ['pikachu', 'pichu', 'raichu'],
            ],
        );

        $apiService = $this->createMock(ModifyElectionVoteApiService::class);
        $apiService
            ->expects($this->once())
            ->method('vote')
            ->with(
                '8800088',
                $electionVote,
            )
        ;

        $service = new ModifyElectionVoteService($userTokenService, $apiService);
        $service->vote($electionVote);
    }

    public function testVoteAllWinners(): void
    {
        $userTokenService = $this->createMock(UserTokenService::class);
        $userTokenService
            ->expects($this->once())
            ->method('getLoggedUserToken')
            ->willReturn('8800088')
        ;

        $electionVote = new ElectionVote(
            'demo',
            'whatever',
            [
                'winners_slugs' => ['pikachu', 'pichu', 'raichu'],
                'losers_slugs' => [],
            ],
        );

        $apiService = $this->createMock(ModifyElectionVoteApiService::class);
        $apiService
            ->expects($this->once())
            ->method('vote')
            ->with(
                '8800088',
                $electionVote,
            )
        ;

        $service = new ModifyElectionVoteService($userTokenService, $apiService);
        $service->vote($electionVote);
    }
}
