<?php

declare(strict_types=1);

namespace App\Tests\Integration\Labels;

use App\Controller\Labels\LabelsController;
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
#[CoversClass(LabelsController::class)]
final class LabelsTest extends WebTestCase
{
    use ClientRequestTrait;
    use JsonResponseTrait;

    #[Test]
    public function get(): void
    {
        $client = self::createClient();

        $this->authenticatedRequest(
            $client,
            'admin',
            'GET',
            '/labels',
        );

        $this->assertResponseIsSuccessful();

        $this->assertResponseContent($client, 'Labels/all.json');
    }

    #[Test]
    public function getNonAuthenticated(): void
    {
        $client = self::createClient();

        $client->request(
            'GET',
            '/labels',
        );

        $this->assertResponseIsSuccessful();

        $this->assertResponseContent($client, 'Labels/all.json');
    }
}
