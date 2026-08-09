<?php

declare(strict_types=1);

namespace App\Tests\Integration\Admin;

use App\Controller\Admin\AdminActionInvalidateController;
use App\Tests\Integration\Trait\ClientRequestTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 */
#[Group('api-mocked-testing')]
#[CoversClass(AdminActionInvalidateController::class)]
final class InvalidateTest extends WebTestCase
{
    use ClientRequestTrait;

    #[Test]
    #[DataProvider('providerInvalidateSuccess')]
    public function invalidateSuccess(string $name): void
    {
        $client = self::createClient();

        $this->authenticatedRequest(
            $client,
            'admin',
            'DELETE',
            "/istration/action/invalidate/{$name}",
        );

        $this->assertResponseStatusCodeSame(202);
        $content = (string) $client->getResponse()->getContent();
        $data = json_decode($content, true);

        $this->assertSame(
            [
                'action' => 'invalidate',
                'item' => $name,
                'state' => 'ok',
                'content' => '',
                'error' => '',
            ],
            $data
        );
    }

    /**
     * @return string[][]
     */
    public static function providerInvalidateSuccess(): array
    {
        return [
            ['labels'],
            ['dex'],
            ['albums'],
            ['reports'],
        ];
    }

    #[Test]
    #[DataProvider('providerInvalidateNotExists')]
    public function invalidateNotExists(string $name): void
    {
        $client = self::createClient();

        $this->authenticatedRequest(
            $client,
            'admin',
            'DELETE',
            "/istration/action/invalidate/{$name}",
        );

        $this->assertResponseStatusCodeSame(404);
    }

    /**
     * @return string[][]
     */
    public static function providerInvalidateNotExists(): array
    {
        return [
            ['catch_states'],
            ['types'],
            ['games_collections_and_dex'],
            ['pokemons'],
            ['regional_dex_numbers'],
            ['games_availabilities'],
            ['games_shinies_availabilities'],
            ['game_bundles_availabilities'],
            ['game_bundles_shinies_availabilities'],
            ['dex_availabilities'],
            ['pokemon_availabilities'],
            ['collections'],
            ['collections_availabilities'],
        ];
    }

    #[Test]
    public function unknown(): void
    {
        $client = self::createClient();

        $this->authenticatedRequest(
            $client,
            'admin',
            'DELETE',
            '/istration/action/invalidate/truc'
        );

        $this->assertResponseStatusCodeSame(404);
    }

    #[Test]
    public function nonAuthenticate(): void
    {
        $client = self::createClient();

        $client->request('DELETE', '/istration/action/invalidate/labels');

        $this->assertResponseStatusCodeSame(401);
    }

    #[Test]
    public function noProvider(): void
    {
        $client = self::createClient();

        $client->request(
            'DELETE',
            '/istration/action/invalidate/labels',
            [],
            [],
            [
                'HTTP_AUTHORIZATION' => 'Bearer this-is-the-token',
            ],
        );

        $this->assertResponseStatusCodeSame(401);
    }

    #[Test]
    public function nonAdmin(): void
    {
        $client = self::createClient();

        $this->authenticatedRequest(
            $client,
            'user',
            'DELETE',
            '/istration/action/invalidate/labels'
        );

        $this->assertResponseStatusCodeSame(403);
    }
}
