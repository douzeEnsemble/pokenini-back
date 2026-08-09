<?php

declare(strict_types=1);

namespace App\Tests\Integration\Album;

use App\Controller\Album\AlbumUpsertController;
use App\Tests\Integration\Trait\ClientRequestTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 */
#[Group('api-mocked-testing')]
#[CoversClass(AlbumUpsertController::class)]
final class AlbumUpsertTest extends WebTestCase
{
    use ClientRequestTrait;

    #[Test]
    public function upsertAsTrainer(): void
    {
        $client = self::createClient();

        $this->authenticatedRequest(
            $client,
            'trainer',
            'PATCH',
            '/album/demo/bulbasaur',
            [],
            [],
            [],
            'yes',
        );

        $this->assertResponseIsSuccessful();
    }

    #[Test]
    public function upsertPremuimAsCollector(): void
    {
        $client = self::createClient();

        $this->authenticatedRequest(
            $client,
            'collector',
            'PATCH',
            '/album/homepokemongo/bulbasaur',
            [],
            [],
            [],
            'yes',
        );

        $this->assertResponseIsSuccessful();
    }

    #[Test]
    public function upsertPremuimAsTrainer(): void
    {
        $client = self::createClient();

        $this->authenticatedRequest(
            $client,
            'trainer',
            'PATCH',
            '/album/homepokemongo/bulbasaur',
            [],
            [],
            [],
            'yes',
        );

        $this->assertResponseStatusCodeSame(404);
    }

    #[Test]
    public function upsertFailed(): void
    {
        $client = self::createClient();

        $this->authenticatedRequest(
            $client,
            'trainer',
            'PATCH',
            '/album/demo/blastoise',
            [],
            [],
            [],
            'tobreed',
        );

        $this->assertResponseStatusCodeSame(500);

        $content = (string) $client->getResponse()->getContent();
        $this->assertSame('{"error":"Fail to modify resources"}', $content);
    }

    #[Test]
    public function upsertAsNonAuthenticated(): void
    {
        $client = self::createClient();

        $client->request(
            'PATCH',
            '/album/demo/bulbasaur',
            [],
            [],
            [],
            'yes',
        );

        $this->assertResponseStatusCodeSame(401);
    }
}
