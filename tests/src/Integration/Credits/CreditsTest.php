<?php

declare(strict_types=1);

namespace App\Tests\Integration\Credits;

use App\Controller\CreditsController;
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
#[CoversClass(CreditsController::class)]
final class CreditsTest extends WebTestCase
{
    use ClientRequestTrait;
    use JsonResponseTrait;

    #[Test]
    public function get(): void
    {
        $client = self::createClient();

        $this->authenticatedRequest(
            $client,
            'trainer',
            'GET',
            '/credits',
        );

        $this->assertResponseIsSuccessful();

        $this->assertResponseContent($client, 'Credits/all.json');
    }

    #[Test]
    public function getNonAuthenticated(): void
    {
        $client = self::createClient();

        $client->request(
            'GET',
            '/credits',
        );

        $this->assertResponseIsSuccessful();

        $this->assertResponseContent($client, 'Credits/all.json');
    }
}
