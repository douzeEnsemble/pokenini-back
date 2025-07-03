<?php

declare(strict_types=1);

namespace App\Tests\Functional\AlbumUpsert;

use App\Controller\AlbumUpsertController;
use App\Tests\Functional\Trait\ClientRequestTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 */
#[CoversClass(AlbumUpsertController::class)]
class AlbumUpsertTest extends WebTestCase
{
    use ClientRequestTrait;

    public function testUpsertAsTrainer(): void
    {
        $client = static::createClient();

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

    public function testUpsertPremuimAsCollector(): void
    {
        $client = static::createClient();

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

    public function testUpsertPremuimAsTrainer(): void
    {
        $client = static::createClient();

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

    public function testUpsertFailed(): void
    {
        $client = static::createClient();

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
}
