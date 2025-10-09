<?php

declare(strict_types=1);

namespace App\Tests\Functional\AlbumDex;

use App\Controller\AlbumDexController;
use App\Tests\Functional\Trait\ClientRequestTrait;
use App\Tests\Functional\Trait\JsonResponseTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 */
#[CoversClass(AlbumDexController::class)]
class AlbumDexTest extends WebTestCase
{
    use ClientRequestTrait;
    use JsonResponseTrait;

    public function testDexAsTrainer(): void
    {
        $client = static::createClient();

        $this->authenticatedRequest(
            $client,
            'trainer',
            'GET',
            '/album/dex',
        );

        $this->assertResponseIsSuccessful();

        $this->assertResponseContent($client, 'AlbumDex/trainer.json');
    }

    public function testDexAsCollector(): void
    {
        $client = static::createClient();

        $this->authenticatedRequest(
            $client,
            'collector',
            'GET',
            '/album/dex',
        );

        $this->assertResponseIsSuccessful();

        $this->assertResponseContent($client, 'AlbumDex/collector.json');
    }

    public function testDexAdmin(): void
    {
        $client = static::createClient();

        $this->authenticatedRequest(
            $client,
            'admin',
            'GET',
            '/album/dex',
        );

        $this->assertResponseIsSuccessful();

        $this->assertResponseContent($client, 'AlbumDex/admin.json');
    }

    public function testDexPublic(): void
    {
        $client = static::createClient();

        $client->request(
            'GET',
            '/album/dex',
        );

        $this->assertResponseStatusCodeSame(401);
    }
}
