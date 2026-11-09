<?php

declare(strict_types=1);

namespace App\Tests\Integration\WhichShinyGame;

use App\Controller\WhichShinyGame\WhichShinyGameController;
use App\Tests\Integration\Trait\ClientRequestTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 */
#[Group('api-mocked-testing')]
#[CoversClass(WhichShinyGameController::class)]
final class WhichShinyGameLangTest extends WebTestCase
{
    use ClientRequestTrait;

    private const string GAME_ID = '11111111-1111-4111-8111-111111111111';
    private const array DEX = ['slug' => 'demo', 'name' => 'Démo', 'french_name' => null];

    #[Test]
    public function dex(): void
    {
        $client = self::createClient();

        $this->authenticatedRequest($client, 'trainer', 'GET', '/which_shiny_game/dex?lang=fr');

        $this->assertResponseIsSuccessful();
        $this->assertSame([self::DEX], $this->content($client));
    }

    #[Test]
    public function list(): void
    {
        $client = self::createClient();

        $this->authenticatedRequest($client, 'trainer', 'GET', '/which_shiny_game/games?lang=fr');

        $this->assertResponseIsSuccessful();
        $this->assertSame([['id' => self::GAME_ID, 'dex' => self::DEX]], $this->content($client));
    }

    #[Test]
    public function create(): void
    {
        $client = self::createClient();

        $this->authenticatedRequest(
            $client,
            'trainer',
            'POST',
            '/which_shiny_game/games?lang=fr',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: '{"dex_slug":"demo"}',
        );

        $this->assertResponseStatusCodeSame(201);
        $this->assertSame(['id' => self::GAME_ID, 'dex' => self::DEX], $this->content($client));
    }

    #[Test]
    public function show(): void
    {
        $client = self::createClient();

        $this->authenticatedRequest($client, 'trainer', 'GET', '/which_shiny_game/games/'.self::GAME_ID.'?lang=fr');

        $this->assertResponseIsSuccessful();
        $this->assertSame(['game' => ['id' => self::GAME_ID, 'dex' => self::DEX], 'round' => null], $this->content($client));
    }

    #[Test]
    public function rounds(): void
    {
        $client = self::createClient();

        $this->authenticatedRequest($client, 'trainer', 'GET', '/which_shiny_game/games/'.self::GAME_ID.'/rounds?lang=fr');

        $this->assertResponseIsSuccessful();
        $this->assertSame(['game' => ['id' => self::GAME_ID, 'dex' => self::DEX], 'rounds' => []], $this->content($client));
    }

    #[Test]
    public function answer(): void
    {
        $client = self::createClient();

        $this->authenticatedRequest(
            $client,
            'trainer',
            'POST',
            '/which_shiny_game/games/'.self::GAME_ID.'/answers?lang=fr',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: '{"position":2,"slot":2}',
        );

        $this->assertResponseIsSuccessful();
        $this->assertSame(
            ['position' => 2, 'is_correct' => true, 'game' => ['id' => self::GAME_ID, 'dex' => self::DEX]],
            $this->content($client),
        );
    }

    #[Test]
    #[DataProvider('providerNotEnabledLanguage')]
    public function notEnabledLanguage(string $method, string $url, ?string $content): void
    {
        $client = self::createClient();

        $this->authenticatedRequest($client, 'trainer', $method, $url, server: ['CONTENT_TYPE' => 'application/json'], content: $content);

        $this->assertResponseStatusCodeSame(400);
        $this->assertSame(['error' => 'Invalid language'], $this->content($client));
    }

    /**
     * @return array<string, array{string, string, null|string}>
     */
    public static function providerNotEnabledLanguage(): array
    {
        return [
            'dex' => ['GET', '/which_shiny_game/dex?lang=de', null],
            'list' => ['GET', '/which_shiny_game/games?lang=de', null],
            'create' => ['POST', '/which_shiny_game/games?lang=de', '{"dex_slug":"demo"}'],
            'show' => ['GET', '/which_shiny_game/games/'.self::GAME_ID.'?lang=de', null],
            'rounds' => ['GET', '/which_shiny_game/games/'.self::GAME_ID.'/rounds?lang=de', null],
            'answer' => ['POST', '/which_shiny_game/games/'.self::GAME_ID.'/answers?lang=de', '{"position":2,"slot":2}'],
        ];
    }

    /**
     * @return array<array-key, mixed>
     */
    private function content(KernelBrowser $client): array
    {
        /** @var array<array-key, mixed> */
        return json_decode((string) $client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);
    }
}
