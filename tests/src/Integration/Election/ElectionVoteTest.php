<?php

declare(strict_types=1);

namespace App\Tests\Integration\Election;

use App\Controller\Election\ElectionVoteController;
use App\Tests\Integration\Trait\ClientRequestTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 */
#[Group('api-mocked-testing')]
#[CoversClass(ElectionVoteController::class)]
final class ElectionVoteTest extends WebTestCase
{
    use ClientRequestTrait;

    public function testVote(): void
    {
        $client = self::createClient();

        $this->authenticatedRequest(
            $client,
            'trainer',
            'POST',
            '/election/demolite',
            content: (string) json_encode([
                'winners_slugs' => ['pichu'],
                'losers_slugs' => ['pikachu', 'raichu'],
            ]),
        );

        $this->assertResponseIsSuccessful();
    }

    public function testVoteWithElectionSlug(): void
    {
        $client = self::createClient();

        $this->authenticatedRequest(
            $client,
            'trainer',
            'POST',
            '/election/demolite/favorite',
            content: (string) json_encode([
                'winners_slugs' => ['pichu'],
                'losers_slugs' => ['pikachu', 'raichu'],
            ]),
        );

        $this->assertResponseIsSuccessful();
    }

    public function testVoteWithFilters(): void
    {
        $client = self::createClient();

        $this->authenticatedRequest(
            $client,
            'trainer',
            'POST',
            '/election/demolite/favorite?at[]=poison&at[]=fire&t1[]=&t2[]=&fc[]=&fr[]=&fs[]=&fv[]=&ogb[]=&gba[]=&gbsa[]',
            content: (string) json_encode([
                'winners_slugs' => ['pichu'],
                'losers_slugs' => ['pikachu', 'raichu'],
            ]),
        );

        $this->assertResponseIsSuccessful();
    }

    public function testVoteEmpty(): void
    {
        $client = self::createClient();

        $this->authenticatedRequest(
            $client,
            'trainer',
            'POST',
            '/election/demolite',
            content: (string) json_encode([]),
        );

        $this->assertResponseStatusCodeSame(400);

        $this->assertJsonStringEqualsJsonString(
            '{"error":"Data cannot be empty"}',
            (string) $client->getResponse()->getContent(),
        );
    }

    public function testVoteBad(): void
    {
        $client = self::createClient();

        $this->authenticatedRequest(
            $client,
            'trainer',
            'POST',
            '/election/demolite',
            [
                'electionSlug' => '',
                'winnersSlugs' => ['pichu'],
                'losersSlugs' => ['pikachu', 'raichu'],
            ],
        );

        $this->assertResponseStatusCodeSame(400);

        $this->assertJsonStringEqualsJsonString(
            '{"error":"Data cannot be empty"}',
            (string) $client->getResponse()->getContent(),
        );
    }

    public function testVoteNonAuthenticated(): void
    {
        $client = self::createClient();

        $client->request(
            'POST',
            '/election/demolite',
            content: (string) json_encode([
                'winners_slugs' => ['pichu'],
                'losers_slugs' => ['pikachu', 'raichu'],
            ]),
        );

        $this->assertResponseStatusCodeSame(401);
    }
}
