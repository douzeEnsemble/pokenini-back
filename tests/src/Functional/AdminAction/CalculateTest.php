<?php

declare(strict_types=1);

namespace App\Tests\Functional\AdminAction;

use App\Controller\AdminActionController;
use App\Tests\Functional\Trait\ClientRequestTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 */
#[CoversClass(AdminActionController::class)]
class CalculateTest extends WebTestCase
{
    use ClientRequestTrait;

    public function testGamesBundlesAvailabilities(): void
    {
        $this->testAction('game_bundles_availabilities');
    }

    public function testGamesBundlesShiniesAvailabilities(): void
    {
        $this->testAction('game_bundles_shinies_availabilities');
    }

    public function testPokemonAvailabilities(): void
    {
        $this->testAction('pokemon_availabilities');
    }

    public function testDexAvailabilities(): void
    {
        $client = static::createClient();

        // For testing purpose, this case will fail in API side
        $this->authenticatedRequest(
            $client,
            'admin',
            'POST',
            '/istration/action/calculate/dex_availabilities',
        );

        $this->assertResponseStatusCodeSame(500);
        $content = (string) $client->getResponse()->getContent();
        $data = json_decode($content, true);

        $this->assertSame(
            [
                'action' => 'calculate',
                'item' => 'dex_availabilities',
                'state' => 'ko',
                'content' => '',
                'error' => 'HTTP/1.1 500 Internal Server Error returned for "http://moco.api/istration/calculate/dex_availabilities".',
            ],
            $data
        );
    }

    public function testUnknown(): void
    {
        $client = static::createClient();

        $this->authenticatedRequest(
            $client,
            'admin',
            'POST',
            '/istration/action/calculate/truc'
        );

        $this->assertResponseStatusCodeSame(404);
    }

    public function testNonAuthenticate(): void
    {
        $client = static::createClient();

        $client->request('POST', '/istration/action/calculate/game_bundles_availabilities');

        $this->assertResponseStatusCodeSame(401);
    }

    public function testNoProvider(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/istration/action/calculate/game_bundles_availabilities',
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
            'POST',
            '/istration/action/calculate/game_bundles_availabilities',
        );

        $this->assertResponseStatusCodeSame(403);
    }

    private function testAction(string $name): void
    {
        $client = static::createClient();

        $this->authenticatedRequest(
            $client,
            'admin',
            'POST',
            "/istration/action/calculate/{$name}",
        );

        $this->assertResponseStatusCodeSame(202);
        $content = (string) $client->getResponse()->getContent();
        $data = json_decode($content, true);

        $this->assertSame(
            [
                'action' => 'calculate',
                'item' => $name,
                'state' => 'ok',
                'content' => '',
                'error' => '',
            ],
            $data
        );
    }
}
