<?php

declare(strict_types=1);

namespace App\Tests\Integration\Election;

use App\Controller\Election\ElectionDexListController;
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
#[CoversClass(ElectionDexListController::class)]
final class ElectionDexListTest extends WebTestCase
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
            '/election/dex',
        );

        $this->assertResponseIsSuccessful();

        $this->assertResponseContent($client, 'ElectionDexList/trainer.json');
    }

    #[Test]
    public function dexAsCollector(): void
    {
        $client = self::createClient();

        $this->authenticatedRequest(
            $client,
            'collector',
            'GET',
            '/election/dex',
        );

        $this->assertResponseIsSuccessful();

        $this->assertResponseContent($client, 'ElectionDexList/collector.json');
    }

    #[Test]
    public function dexAdmin(): void
    {
        $client = self::createClient();

        $this->authenticatedRequest(
            $client,
            'admin',
            'GET',
            '/election/dex',
        );

        $this->assertResponseIsSuccessful();

        $this->assertResponseContent($client, 'ElectionDexList/admin.json');
    }

    #[Test]
    public function dexNonAuthenticated(): void
    {
        $client = self::createClient();

        $client->request(
            'GET',
            '/election/dex',
        );

        $this->assertResponseStatusCodeSame(401);
    }
}
