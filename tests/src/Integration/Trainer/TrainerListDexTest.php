<?php

declare(strict_types=1);

namespace App\Tests\Integration\Trainer;

use App\Controller\Trainer\TrainerListDexController;
use App\Tests\Integration\Trait\ClientRequestTrait;
use App\Tests\Integration\Trait\JsonResponseTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 */
#[Group('api-mocked-testing')]
#[CoversClass(TrainerListDexController::class)]
final class TrainerListDexTest extends WebTestCase
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

        $this->assertResponseContent($client, 'TrainerListDex/trainer.json');
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

        $this->assertResponseContent($client, 'TrainerListDex/collector.json');
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

        $this->assertResponseContent($client, 'TrainerListDex/admin.json');
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
