<?php

declare(strict_types=1);

namespace App\Tests\Integration\Album;

use App\Controller\Album\AlbumDexListController;
use App\Tests\Integration\Trait\ClientRequestTrait;
use App\Tests\Integration\Trait\JsonResponseTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 */
#[Group('api-mocked-testing')]
#[CoversClass(AlbumDexListController::class)]
final class AlbumDexListTest extends WebTestCase
{
    use ClientRequestTrait;
    use JsonResponseTrait;

    #[Test]
    public function dexAsTrainer(): void
    {
        $client = self::createClient();

        $this->authenticatedRequest(
            $client,
            'trainer',
            'GET',
            '/album/dex',
        );

        $this->assertResponseIsSuccessful();

        $this->assertResponseContent($client, 'AlbumDexList/trainer.json');
    }

    #[Test]
    public function dexAsCollector(): void
    {
        $client = self::createClient();

        $this->authenticatedRequest(
            $client,
            'collector',
            'GET',
            '/album/dex',
        );

        $this->assertResponseIsSuccessful();

        $this->assertResponseContent($client, 'AlbumDexList/collector.json');
    }

    #[Test]
    public function dexAdmin(): void
    {
        $client = self::createClient();

        $this->authenticatedRequest(
            $client,
            'admin',
            'GET',
            '/album/dex',
        );

        $this->assertResponseIsSuccessful();

        $this->assertResponseContent($client, 'AlbumDexList/admin.json');
    }

    #[Test]
    public function dexPublicWithTrainerId(): void
    {
        $client = self::createClient();

        $client->request(
            'GET',
            '/album/dex',
            ['trainer_id' => '963f1e5284a86d4a60fb5eb81cb75b22fbe683ec'],
        );

        $this->assertResponseIsSuccessful();

        $this->assertResponseContent($client, 'AlbumDexList/trainer.json');
    }

    #[Test]
    public function dexPublicWithoutTrainerId(): void
    {
        $client = self::createClient();

        $client->request(
            'GET',
            '/album/dex',
        );

        $this->assertResponseStatusCodeSame(401);
    }
}
