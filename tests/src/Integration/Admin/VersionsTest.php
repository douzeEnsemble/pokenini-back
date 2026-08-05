<?php

declare(strict_types=1);

namespace App\Tests\Integration\Admin;

use App\Controller\Admin\AdminVersionController;
use App\Tests\Integration\Trait\ClientRequestTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 */
#[Group('api-mocked-testing')]
#[CoversClass(AdminVersionController::class)]
final class VersionsTest extends WebTestCase
{
    use ClientRequestTrait;

    public function testGetVersion(): void
    {
        $client = self::createClient();

        $this->authenticatedRequest(
            $client,
            'admin',
            'GET',
            '/istration/version',
        );

        $this->assertResponseIsSuccessful();

        $content = $client->getResponse()->getContent();
        self::assertIsString($content);

        $expectedBackVersion = trim((string) file_get_contents(dirname(__DIR__, 4).'/resources/metadata/version'));

        self::assertJsonStringEqualsJsonString(
            json_encode(['back' => $expectedBackVersion, 'api' => '1.9.8'], JSON_THROW_ON_ERROR),
            $content,
        );
    }

    public function testGetVersionNonAuthenticated(): void
    {
        $client = self::createClient();

        $client->request(
            'GET',
            '/istration/version',
        );

        $this->assertResponseStatusCodeSame(401);
    }
}
