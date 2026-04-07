<?php

declare(strict_types=1);

namespace App\Tests\Integration\Trainer;

use App\Controller\Trainer\TrainerDexController;
use App\Tests\Integration\Trait\ClientRequestTrait;
use App\Tests\Integration\Trait\JsonResponseTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 */
#[Group('api-mocked-testing')]
#[CoversClass(TrainerDexController::class)]
final class TrainerDexTest extends WebTestCase
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
            '/trainer/dex',
        );

        $this->assertResponseIsSuccessful();

        $this->assertResponseContent($client, 'TrainerDex/trainer.json');
    }

    public function testDexAsCollector(): void
    {
        $client = self::createClient();

        $this->authenticatedRequest(
            $client,
            'collector',
            'GET',
            '/trainer/dex',
        );

        $this->assertResponseIsSuccessful();

        $this->assertResponseContent($client, 'TrainerDex/collector.json');
    }

    public function testDexAdmin(): void
    {
        $client = self::createClient();

        $this->authenticatedRequest(
            $client,
            'admin',
            'GET',
            '/trainer/dex',
        );

        $this->assertResponseIsSuccessful();

        $this->assertResponseContent($client, 'TrainerDex/admin.json');
    }

    public function testDexPublic(): void
    {
        $client = self::createClient();

        $client->request(
            'GET',
            '/trainer/dex',
        );

        $this->assertResponseStatusCodeSame(401);
    }
}
