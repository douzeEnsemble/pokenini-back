<?php

declare(strict_types=1);

namespace App\Tests\Functional\ElectionVote;

use App\Controller\ElectionVoteController;
use App\Tests\Functional\Trait\ClientRequestTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 */
#[CoversClass(ElectionVoteController::class)]
class ElectionVoteTest extends WebTestCase
{
    use ClientRequestTrait;

    public function testVote(): void
    {
        $client = static::createClient();

        $this->authenticatedRequest(
            $client,
            'trainer',
            'POST',
            '/election/demolite',
            [
                'winners_slugs' => ['pichu'],
                'losers_slugs' => ['pikachu', 'raichu'],
            ],
        );

        $this->assertResponseIsSuccessful();
    }

    public function testVoteWithElectionSlug(): void
    {
        $client = static::createClient();

        $this->authenticatedRequest(
            $client,
            'trainer',
            'POST',
            '/election/demolite/favorite',
            [
                'winners_slugs' => ['pichu'],
                'losers_slugs' => ['pikachu', 'raichu'],
            ],
        );

        $this->assertResponseIsSuccessful();
    }

    public function testVoteWithFilters(): void
    {
        $client = static::createClient();

        $this->authenticatedRequest(
            $client,
            'trainer',
            'POST',
            '/election/demolite/favorite?at[]=poison&at[]=fire&t1[]=&t2[]=&fc[]=&fr[]=&fs[]=&fv[]=&ogb[]=&gba[]=&gbsa[]',
            [
                'winners_slugs' => ['pichu'],
                'losers_slugs' => ['pikachu', 'raichu'],
            ],
        );

        $this->assertResponseIsSuccessful();
    }

    public function testVoteEmpty(): void
    {
        $client = static::createClient();

        $this->authenticatedRequest(
            $client,
            'trainer',
            'POST',
            '/election/demolite',
            [
            ],
        );

        $this->assertResponseStatusCodeSame(400);

        $this->assertJsonStringEqualsJsonString(
            '{"error":"Data cannot be empty"}',
            (string) $client->getResponse()->getContent(),
        );
    }

    public function testVoteBad(): void
    {
        $client = static::createClient();

        $this->authenticatedRequest(
            $client,
            'trainer',
            'POST',
            '/election/demolite',
            [],
            [],
            [],
            http_build_query([
                'electionSlug' => '',
                'winnersSlugs' => ['pichu'],
                'losersSlugs' => ['pikachu', 'raichu'],
            ]),
        );

        $this->assertResponseStatusCodeSame(400);

        $this->assertJsonStringEqualsJsonString(
            '{"error":"Data cannot be empty"}',
            (string) $client->getResponse()->getContent(),
        );
    }
}
