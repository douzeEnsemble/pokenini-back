<?php

declare(strict_types=1);

namespace App\Tests\Integration\Admin;

use App\Controller\Admin\AdminActionLogsController;
use App\Tests\Integration\Trait\ClientRequestTrait;
use App\Tests\Integration\Trait\JsonResponseTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 */
#[Group('api-mocked-testing')]
#[CoversClass(AdminActionLogsController::class)]
class ActionLogsTest extends WebTestCase
{
    use ClientRequestTrait;
    use JsonResponseTrait;

    public function testGetActionLogs(): void
    {
        $client = static::createClient();

        $this->authenticatedRequest(
            $client,
            'admin',
            'GET',
            '/istration/action-logs',
        );

        $this->assertResponseIsSuccessful();

        $this->assertResponseContent($client, 'Admin/action-logs.json');
    }

    public function testGetActionLogsNonAuthenticated(): void
    {
        $client = static::createClient();

        $client->request(
            'GET',
            '/istration/action-logs',
        );

        $this->assertResponseStatusCodeSame(401);
    }
}
