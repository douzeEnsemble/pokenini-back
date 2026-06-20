<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Security\UserTokenService;
use App\Service\Api\ModifyElectionVoteApiService;
use App\Service\ModifyElectionVoteService;
use App\Tests\Unit\Trait\ElectionVoteFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(ModifyElectionVoteService::class)]
final class ModifyElectionVoteServiceTest extends TestCase
{
    use ElectionVoteFactory;

    public function testVote(): void
    {
        $userTokenService = $this->createMock(UserTokenService::class);
        $userTokenService
            ->expects($this->once())
            ->method('getLoggedUserToken')
            ->willReturn('8800088')
        ;

        $electionVote = $this->makeVote('demo', 'whatever', ['pichu'], ['pikachu', 'raichu']);

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

        $electionVote = $this->makeVote('demo', 'whatever', ['pichu'], ['pikachu', 'pichu', 'raichu']);

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

        $electionVote = $this->makeVote('demo', 'whatever', [], ['pikachu', 'pichu', 'raichu']);

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

        $electionVote = $this->makeVote('demo', 'whatever', ['pikachu', 'pichu', 'raichu'], []);

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

    /*
     * @param string[] $winnersSlugs
     * @param string[] $losersSlugs
     */
}
