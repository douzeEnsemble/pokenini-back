<?php

declare(strict_types=1);

namespace App\Tests\Integration\Election;

use App\Controller\Election\ElectionDexController;
use App\Tests\Integration\Trait\ClientRequestTrait;
use App\Tests\Integration\Trait\JsonResponseTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 */
#[Group('api-mocked-testing')]
#[CoversClass(ElectionDexController::class)]
final class ElectionDexTest extends WebTestCase
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
            '/election/dex',
        );

        $this->assertResponseIsSuccessful();

        $this->assertResponseContent($client, 'ElectionDex/trainer.json');
    }

    public function testDexAsCollector(): void
    {
        $client = self::createClient();

        $this->authenticatedRequest(
            $client,
            'collector',
            'GET',
            '/election/dex',
        );

        $this->assertResponseIsSuccessful();

        $this->assertResponseContent($client, 'ElectionDex/collector.json');
    }

    public function testDexAdmin(): void
    {
        $client = self::createClient();

        $this->authenticatedRequest(
            $client,
            'admin',
            'GET',
            '/election/dex',
        );

        $this->assertResponseIsSuccessful();

        $this->assertResponseContent($client, 'ElectionDex/admin.json');
    }

    public function testDexNonAuthenticated(): void
    {
        $client = self::createClient();

        $client->request(
            'GET',
            '/election/dex',
        );

        $this->assertResponseStatusCodeSame(401);
    }
}
