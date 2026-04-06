<?php

declare(strict_types=1);

namespace App\Tests\Integration\Album;

use App\Controller\Album\AlbumDexController;
use App\Tests\Integration\Trait\ClientRequestTrait;
use App\Tests\Integration\Trait\JsonResponseTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 */
#[Group('api-mocked-testing')]
#[CoversClass(AlbumDexController::class)]
final class AlbumDexTest extends WebTestCase
{
    use ClientRequestTrait;
    use JsonResponseTrait;

    public function testDexAsTrainer(): void
    {
        $client = self::createClient();

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
        $client = self::createClient();

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
        $client = self::createClient();

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
        $client = self::createClient();

        $client->request(
            'GET',
            '/album/dex',
        );

        $this->assertResponseStatusCodeSame(401);
    }
}
