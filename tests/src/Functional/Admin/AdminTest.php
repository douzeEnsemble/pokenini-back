<?php

declare(strict_types=1);

namespace App\Tests\Functional\Admin;

use App\Controller\AdminController;
use App\Tests\Functional\Trait\ClientRequestTrait;
use App\Tests\Functional\Trait\JsonResponseTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 */
#[CoversClass(AdminController::class)]
class AdminTest extends WebTestCase
{
    use ClientRequestTrait;
    use JsonResponseTrait;

    public function testGetReports(): void
    {
        $client = static::createClient();

        $this->authenticatedRequest(
            $client,
            'admin',
            'GET',
            '/istration/reports',
        );

        $this->assertResponseIsSuccessful();

        $this->assertResponseContent($client, 'Admin/reports.json');
    }

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
}
