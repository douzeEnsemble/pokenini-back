<?php

declare(strict_types=1);

namespace App\Tests\Integration\Trainer;

use App\Controller\Trainer\TrainerUpsertController;
use App\Tests\Integration\Trait\ClientRequestTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 */
#[Group('api-mocked-testing')]
#[CoversClass(TrainerUpsertController::class)]
final class TrainerUpsertTest extends WebTestCase
{
    use ClientRequestTrait;

    #[Test]
    public function upsert(): void
    {
        $client = self::createClient();

        $this->authenticatedRequest(
            $client,
            'trainer',
            'PUT',
            '/trainer/dex/demo',
            [],
            [],
            [],
            '{"is_private": true, "is_on_home": true}'
        );

        $this->assertResponseIsSuccessful();
    }

    #[Test]
    public function upsertOnlyPrivate(): void
    {
        $client = self::createClient();

        $this->authenticatedRequest(
            $client,
            'trainer',
            'PUT',
            '/trainer/dex/goldsilvercrystal',
            [],
            [],
            [],
            '{"is_private": true}'
        );

        $this->assertResponseIsSuccessful();
    }

    #[Test]
    public function upsertOnlyOnHome(): void
    {
        $client = self::createClient();

        $this->authenticatedRequest(
            $client,
            'trainer',
            'PUT',
            '/trainer/dex/goldsilvercrystal',
            [],
            [],
            [],
            '{"is_on_home": true}'
        );

        $this->assertResponseIsSuccessful();
    }

    #[Test]
    public function upsertOnPremiumDexAsCollector(): void
    {
        $client = self::createClient();

        $this->authenticatedRequest(
            $client,
            'collector',
            'PUT',
            '/trainer/dex/homepokemongo',
            [],
            [],
            [],
            '{"is_on_home": true}'
        );

        $this->assertResponseIsSuccessful();
    }

    #[Test]
    public function upsertOnPremiumDexAsTrainer(): void
    {
        $client = self::createClient();

        $this->authenticatedRequest(
            $client,
            'trainer',
            'PUT',
            '/trainer/dex/homepokemongo',
            [],
            [],
            [],
            '{"is_on_home": true}'
        );

        $this->assertResponseStatusCodeSame(404);
    }

    #[Test]
    public function upsertBadRequest(): void
    {
        $client = self::createClient();

        $this->authenticatedRequest(
            $client,
            'collector',
            'PUT',
            '/trainer/dex/homepokemongo',
            [],
            [],
            [],
            '{"isprivate": true, "isonhome": true}'
        );

        $this->assertResponseStatusCodeSame(500);

        $content = (string) $client->getResponse()->getContent();
        $this->assertSame('{"error":"Fail to modify resources"}', $content);
    }

    #[Test]
    public function upsertFail(): void
    {
        $client = self::createClient();

        $this->authenticatedRequest(
            $client,
            'collector',
            'PUT',
            '/trainer/dex/redgreenblueyellow',
            [],
            [],
            [],
            '{"is_private": true, "is_on_home": true}'
        );

        $this->assertResponseStatusCodeSame(500);

        $content = (string) $client->getResponse()->getContent();
        $this->assertSame('{"error":"Fail to modify resources"}', $content);
    }

    #[Test]
    public function upsertNonAuthenticated(): void
    {
        $client = self::createClient();

        $client->request(
            'PUT',
            '/trainer/dex/demo',
            [],
            [],
            [],
            '{"is_private": true, "is_on_home": true}'
        );

        $this->assertResponseStatusCodeSame(401);
    }
}
