<?php

declare(strict_types=1);

namespace App\Tests\Integration\Album;

use App\Controller\Album\AlbumDexListController;
use App\Tests\Integration\Trait\ClientRequestTrait;
use App\Tests\Integration\Trait\JsonResponseTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
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

        $this->assertResponseContent($client, 'AlbumDexList/trainer.json');
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

        $this->assertResponseContent($client, 'AlbumDexList/collector.json');
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

        $this->assertResponseContent($client, 'AlbumDexList/admin.json');
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
