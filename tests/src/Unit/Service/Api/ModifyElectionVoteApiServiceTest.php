<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Api;

use App\Service\Api\ModifyElectionVoteApiService;
use App\Tests\Unit\Trait\ElectionVoteFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * @internal
 */
#[CoversClass(ModifyElectionVoteApiService::class)]
final class ModifyElectionVoteApiServiceTest extends TestCase
{
    use ElectionVoteFactory;

    private ArrayAdapter $cachePool;
    private TagAwareAdapter $cache;

    #[Test]
    public function vote(): void
    {
        $electionVote = $this->makeVote('demo', 'whatever', ['pichu'], ['pikachu', 'raichu']);

        $this
            ->getService('5465465', 'demo', 'whatever', ['pichu'], ['pikachu', 'raichu'])
            ->vote(
                '5465465',
                $electionVote,
            )
        ;
    }

    #[Test]
    public function voteAllLosers(): void
    {
        $electionVote = $this->makeVote('demo', 'whatever', [], ['pikachu', 'pichu', 'raichu']);

        $this
            ->getService('5465465', 'demo', 'whatever', [], ['pikachu', 'pichu', 'raichu'])
            ->vote(
                '5465465',
                $electionVote,
            )
        ;
    }

    #[Test]
    public function voteAllWinners(): void
    {
        $electionVote = $this->makeVote('demo', 'whatever', ['pikachu', 'pichu', 'raichu'], []);

        $this
            ->getService('5465465', 'demo', 'whatever', ['pikachu', 'pichu', 'raichu'], [])
            ->vote(
                '5465465',
                $electionVote,
            )
        ;

        $this->assertEmpty($this->cachePool->getValues());
    }

    /**
     * @param string[] $winnersSlugs
     * @param string[] $losersSlugs
     */
    /**
     * @param string[] $winnersSlugs
     * @param string[] $losersSlugs
     */
    private function getService(
        string $trainerId,
        string $dexSlug,
        string $electionSlug,
        array $winnersSlugs,
        array $losersSlugs,
    ): ModifyElectionVoteApiService {
        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->exactly(2))
            ->method('info')
        ;

        $client = $this->createMock(HttpClientInterface::class);

        $json = (string) file_get_contents("/app/tests/resources/unit/service/api/election_vote_{$trainerId}_{$dexSlug}_{$electionSlug}.json");

        $response = $this->createMock(ResponseInterface::class);
        $response
            ->expects($this->once())
            ->method('getContent')
            ->willReturn($json)
        ;

        $client
            ->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                'https://api.domain/election/vote',
                [
                    'headers' => [
                        'accept' => 'application/json',
                    ],
                    'auth_basic' => [
                        'web',
                        'douze',
                    ],
                    'cafile' => './resources/certificates/cacert.pem',
                    'body' => json_encode([
                        'trainer' => ['external_id' => $trainerId],
                        'dex_slug' => $dexSlug,
                        'election_slug' => $electionSlug,
                        'winners_slugs' => $winnersSlugs,
                        'losers_slugs' => $losersSlugs,
                    ]),
                ],
            )
            ->willReturn($response)
        ;

        $this->cachePool = new ArrayAdapter();
        $this->cache = new TagAwareAdapter($this->cachePool, new ArrayAdapter());

        return new ModifyElectionVoteApiService(
            $logger,
            $client,
            'https://api.domain',
            './resources/certificates/cacert.pem',
            $this->cache,
            'web',
            'douze',
        );
    }
}
