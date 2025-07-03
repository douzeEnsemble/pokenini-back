<?php

declare(strict_types=1);

namespace App\Tests\Functional\TrainerUpsert;

use App\Controller\TrainerUpsertController;
use App\Tests\Functional\Trait\ClientRequestTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 */
#[CoversClass(TrainerUpsertController::class)]
class TrainerUpsertTest extends WebTestCase
{
    use ClientRequestTrait;

    public function testUpsert(): void
    {
        $client = static::createClient();

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

    public function testUpsertOnlyPrivate(): void
    {
        $client = static::createClient();

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

    public function testUpsertOnlyOnHome(): void
    {
        $client = static::createClient();

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

    public function testUpsertOnPremiumDexAsCollector(): void
    {
        $client = static::createClient();

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

    public function testUpsertOnPremiumDexAsTrainer(): void
    {
        $client = static::createClient();

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

    public function testUpsertBadRequest(): void
    {
        $client = static::createClient();

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

    public function testUpsertFail(): void
    {
        $client = static::createClient();

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
}
