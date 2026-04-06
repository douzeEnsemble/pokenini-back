<?php

declare(strict_types=1);

namespace App\Tests\Integration\Admin;

use App\Controller\Admin\AdminReportsController;
use App\Tests\Integration\Trait\ClientRequestTrait;
use App\Tests\Integration\Trait\JsonResponseTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 */
#[Group('api-mocked-testing')]
#[CoversClass(AdminReportsController::class)]
final class ReportsTest extends WebTestCase
{
    use ClientRequestTrait;
    use JsonResponseTrait;

    public function testGetReports(): void
    {
        $client = self::createClient();

        $this->authenticatedRequest(
            $client,
            'admin',
            'GET',
            '/istration/reports',
        );

        $this->assertResponseIsSuccessful();

        $this->assertResponseContent($client, 'Admin/reports.json');
    }

    public function testGetActionLogsNonAuthenticated(): void
    {
        $client = self::createClient();

        $client->request(
            'GET',
            '/istration/reports',
        );

        $this->assertResponseStatusCodeSame(401);
    }
}
