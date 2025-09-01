<?php

declare(strict_types=1);

namespace App\Tests\Functional\AdminAction;

use App\Controller\AdminActionController;
use App\Tests\Functional\Trait\ClientRequestTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 */
#[CoversClass(AdminActionController::class)]
class InvalidateTest extends WebTestCase
{
    use ClientRequestTrait;

    #[DataProvider('providerInvalidateSuccess')]
    public function testInvalidateSuccess(string $name): void
    {
        $client = static::createClient();

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

    #[DataProvider('providerInvalidateNotExists')]
    public function testInvalidateNotExists(string $name): void
    {
        $client = static::createClient();

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

    public function testUnknown(): void
    {
        $client = static::createClient();

        $this->authenticatedRequest(
            $client,
            'admin',
            'DELETE',
            '/istration/action/invalidate/truc'
        );

        $this->assertResponseStatusCodeSame(404);
    }

    public function testNonAuthenticate(): void
    {
        $client = static::createClient();

        $client->request('DELETE', '/istration/action/invalidate/labels');

        $this->assertResponseStatusCodeSame(401);
    }

    public function testNoProvider(): void
    {
        $client = static::createClient();

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

    public function testNonAdmin(): void
    {
        $client = static::createClient();

        $this->authenticatedRequest(
            $client,
            'user',
            'DELETE',
            '/istration/action/invalidate/labels'
        );

        $this->assertResponseStatusCodeSame(403);
    }
}
