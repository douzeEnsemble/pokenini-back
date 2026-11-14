<?php

declare(strict_types=1);

namespace App\Tests\Integration\Admin;

use App\Controller\Admin\AdminActionCatalogController;
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
#[CoversClass(AdminActionCatalogController::class)]
final class ActionCatalogTest extends WebTestCase
{
    use ClientRequestTrait;
    use JsonResponseTrait;

    #[Test]
    public function getActionCatalog(): void
    {
        $client = self::createClient();

        $this->authenticatedRequest(
            $client,
            'admin',
            'GET',
            '/istration/actions',
        );

        $this->assertResponseIsSuccessful();

        $this->assertResponseContent($client, 'Admin/action_catalog.json');
    }

    #[Test]
    public function getActionCatalogNonAuthenticated(): void
    {
        $client = self::createClient();

        $client->request(
            'GET',
            '/istration/actions',
        );

        $this->assertResponseStatusCodeSame(401);
    }
}
