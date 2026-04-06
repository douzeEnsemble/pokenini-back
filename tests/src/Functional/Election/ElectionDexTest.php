<?php

declare(strict_types=1);

namespace App\Tests\Functional\Election;

use App\Controller\Election\ElectionDexController;
use App\Tests\Functional\Trait\ClientRequestTrait;
use App\Tests\Functional\Trait\JsonResponseTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 */
#[Group('api-mocked-testing')]
#[CoversClass(ElectionDexController::class)]
class ElectionDexTest extends WebTestCase
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
            '/election/dex',
        );

        $this->assertResponseIsSuccessful();

        $this->assertResponseContent($client, 'ElectionDex/trainer.json');
    }

    public function testDexAsCollector(): void
    {
        $client = static::createClient();

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
        $client = static::createClient();

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
        $client = static::createClient();

        $client->request(
            'GET',
            '/election/dex',
        );

        $this->assertResponseStatusCodeSame(401);
    }
}
