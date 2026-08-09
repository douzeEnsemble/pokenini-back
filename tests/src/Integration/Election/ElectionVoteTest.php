<?php

declare(strict_types=1);

namespace App\Tests\Integration\Election;

use App\Controller\Election\ElectionVoteController;
use App\Tests\Integration\Trait\ClientRequestTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 */
#[Group('api-mocked-testing')]
#[CoversClass(ElectionVoteController::class)]
final class ElectionVoteTest extends WebTestCase
{
    use ClientRequestTrait;

    #[Test]
    public function vote(): void
    {
        $client = self::createClient();

        $this->authenticatedRequest(
            $client,
            'trainer',
            'POST',
            '/election/demolite',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: (string) json_encode([
                'winners_slugs' => ['pichu'],
                'losers_slugs' => ['pikachu', 'raichu'],
            ]),
        );

        $this->assertResponseIsSuccessful();
    }

    #[Test]
    public function voteWithElectionSlug(): void
    {
        $client = self::createClient();

        $this->authenticatedRequest(
            $client,
            'trainer',
            'POST',
            '/election/demolite/favorite',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: (string) json_encode([
                'winners_slugs' => ['pichu'],
                'losers_slugs' => ['pikachu', 'raichu'],
            ]),
        );

        $this->assertResponseIsSuccessful();
    }

    #[Test]
    public function voteWithFilters(): void
    {
        $client = self::createClient();

        $this->authenticatedRequest(
            $client,
            'trainer',
            'POST',
            '/election/demolite/favorite?at[]=poison&at[]=fire&t1[]=&t2[]=&fc[]=&fr[]=&fs[]=&fv[]=&ogb[]=&gba[]=&gbsa[]',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: (string) json_encode([
                'winners_slugs' => ['pichu'],
                'losers_slugs' => ['pikachu', 'raichu'],
            ]),
        );

        $this->assertResponseIsSuccessful();
    }

    #[Test]
    public function voteEmpty(): void
    {
        $client = self::createClient();

        $this->authenticatedRequest(
            $client,
            'trainer',
            'POST',
            '/election/demolite',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: (string) json_encode([]),
        );

        $this->assertResponseIsSuccessful();
    }

    #[Test]
    public function voteBadPayload(): void
    {
        $client = self::createClient();

        $this->authenticatedRequest(
            $client,
            'trainer',
            'POST',
            '/election/demolite',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: 'not-json',
        );

        $this->assertResponseStatusCodeSame(400);
    }

    #[Test]
    public function voteNonAuthenticated(): void
    {
        $client = self::createClient();

        $client->request(
            'POST',
            '/election/demolite',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: (string) json_encode([
                'winners_slugs' => ['pichu'],
                'losers_slugs' => ['pikachu', 'raichu'],
            ]),
        );

        $this->assertResponseStatusCodeSame(401);
    }
}
