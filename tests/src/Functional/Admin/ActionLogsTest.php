<?php

declare(strict_types=1);

namespace App\Tests\Functional\Admin;

use App\Controller\Admin\AdminActionLogsController;
use App\Tests\Functional\Trait\ClientRequestTrait;
use App\Tests\Functional\Trait\JsonResponseTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 */
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
