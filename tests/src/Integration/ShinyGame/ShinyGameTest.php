<?php

declare(strict_types=1);

namespace App\Tests\Integration\ShinyGame;

use App\Controller\ShinyGame\ShinyGameController;
use App\Tests\Integration\Trait\ClientRequestTrait;
use App\Tests\Integration\Trait\JsonResponseTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @internal
 */
#[Group('api-mocked-testing')]
#[CoversClass(ShinyGameController::class)]
final class ShinyGameTest extends WebTestCase
{
    use ClientRequestTrait;
    use JsonResponseTrait;

    private const string GAME_ID = '11111111-1111-4111-8111-111111111111';

    #[Test]
    #[DataProvider('providerDex')]
    public function dex(string $roleCode, string $expectedFile): void
    {
        $client = self::createClient();

        $this->authenticatedRequest($client, $roleCode, 'GET', '/shiny_game/dex');

        $this->assertResponseIsSuccessful();
        $this->assertResponseContent($client, "ShinyGame/{$expectedFile}.json");
    }

    /**
     * @return string[][]
     */
    public static function providerDex(): array
    {
        return [
            'trainer' => ['trainer', 'dex_trainer'],
            'collector' => ['collector', 'dex_collector'],
            'admin' => ['admin', 'dex_admin'],
        ];
    }

    #[Test]
    public function dexNonAuthenticated(): void
    {
        $client = self::createClient();

        $client->request('GET', '/shiny_game/dex');

        $this->assertResponseStatusCodeSame(401);
    }

    #[Test]
    public function list(): void
    {
        $client = self::createClient();

        $this->authenticatedRequest($client, 'trainer', 'GET', '/shiny_game/games');

        $this->assertResponseIsSuccessful();
        $this->assertResponseContent($client, 'ShinyGame/games.json');
    }

    #[Test]
    public function create(): void
    {
        $client = self::createClient();

        $this->authenticatedRequest(
            $client,
            'trainer',
            'POST',
            '/shiny_game/games',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: '{"dex_slug":"demo"}',
        );

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseContent($client, 'ShinyGame/created.json');
    }

    #[Test]
    public function createWithRoundCount(): void
    {
        $client = self::createClient();

        $this->authenticatedRequest(
            $client,
            'trainer',
            'POST',
            '/shiny_game/games',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: '{"dex_slug":"demo","round_count":2}',
        );

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseContent($client, 'ShinyGame/created_two_rounds.json');
    }

    #[Test]
    public function createWithOneRound(): void
    {
        $client = self::createClient();

        $this->authenticatedRequest(
            $client,
            'trainer',
            'POST',
            '/shiny_game/games',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: '{"dex_slug":"demo","round_count":1}',
        );

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseContent($client, 'ShinyGame/created.json');
    }

    #[Test]
    public function createWithNullRoundCount(): void
    {
        $client = self::createClient();

        $this->authenticatedRequest(
            $client,
            'trainer',
            'POST',
            '/shiny_game/games',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: '{"dex_slug":"demo","round_count":null}',
        );

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseContent($client, 'ShinyGame/created.json');
    }

    #[Test]
    #[DataProvider('providerCreateFailures')]
    public function createFailures(string $roleCode, string $content, int $expectedStatus): void
    {
        $client = self::createClient();

        $this->authenticatedRequest(
            $client,
            $roleCode,
            'POST',
            '/shiny_game/games',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: $content,
        );

        $this->assertResponseStatusCodeSame($expectedStatus);
        $this->assertSame('[]', $client->getResponse()->getContent());
    }

    /**
     * @return array<string, array{string, string, int}>
     */
    public static function providerCreateFailures(): array
    {
        return [
            'not json' => ['trainer', 'not-json', 400],
            'no dex slug' => ['trainer', '{}', 400],
            'dex slug is not a string' => ['trainer', '{"dex_slug":12}', 400],
            'no round' => ['trainer', '{"dex_slug":"demo","round_count":0}', 400],
            'negative round count' => ['trainer', '{"dex_slug":"demo","round_count":-3}', 400],
            'round count is not an integer' => ['trainer', '{"dex_slug":"demo","round_count":"10"}', 400],
            'unknown dex' => ['trainer', '{"dex_slug":"douze"}', 404],
            'premium dex as trainer' => ['trainer', '{"dex_slug":"redgreenblueyellow"}', 404],
            'unreleased dex as collector' => ['collector', '{"dex_slug":"demolite"}', 404],
            'refused by the api' => ['trainer', '{"dex_slug":"homepogo"}', 400],
        ];
    }

    #[Test]
    public function show(): void
    {
        $client = self::createClient();

        $this->authenticatedRequest($client, 'trainer', 'GET', '/shiny_game/games/'.self::GAME_ID);

        $this->assertResponseIsSuccessful();
        $this->assertResponseContent($client, 'ShinyGame/state.json');
    }

    #[Test]
    public function showUnknown(): void
    {
        $client = self::createClient();

        $this->authenticatedRequest($client, 'trainer', 'GET', '/shiny_game/games/99999999-9999-4999-8999-999999999999');

        $this->assertResponseStatusCodeSame(404);
        $this->assertSame('[]', $client->getResponse()->getContent());
    }

    #[Test]
    public function showNotAnUuid(): void
    {
        $client = self::createClient();

        $this->authenticatedRequest($client, 'trainer', 'GET', '/shiny_game/games/douze');

        $this->assertResponseStatusCodeSame(404);
    }

    #[Test]
    public function rounds(): void
    {
        $client = self::createClient();

        $this->authenticatedRequest($client, 'trainer', 'GET', '/shiny_game/games/'.self::GAME_ID.'/rounds');

        $this->assertResponseIsSuccessful();
        $this->assertResponseContent($client, 'ShinyGame/rounds.json');
    }

    #[Test]
    public function roundsUnknown(): void
    {
        $client = self::createClient();

        $this->authenticatedRequest($client, 'trainer', 'GET', '/shiny_game/games/99999999-9999-4999-8999-999999999999/rounds');

        $this->assertResponseStatusCodeSame(404);
        $this->assertSame('[]', $client->getResponse()->getContent());
    }

    #[Test]
    public function answer(): void
    {
        $client = self::createClient();

        $this->authenticatedRequest(
            $client,
            'trainer',
            'POST',
            '/shiny_game/games/'.self::GAME_ID.'/answers',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: '{"position":2,"is_shiny":true}',
        );

        $this->assertResponseIsSuccessful();
        $this->assertResponseContent($client, 'ShinyGame/answer.json');
    }

    #[Test]
    #[DataProvider('providerAnswerFailures')]
    public function answerFailures(string $content, int $expectedStatus): void
    {
        $client = self::createClient();

        $this->authenticatedRequest(
            $client,
            'trainer',
            'POST',
            '/shiny_game/games/'.self::GAME_ID.'/answers',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: $content,
        );

        $this->assertResponseStatusCodeSame($expectedStatus);
        $this->assertSame('[]', $client->getResponse()->getContent());
    }

    /**
     * @return array<string, array{string, int}>
     */
    public static function providerAnswerFailures(): array
    {
        return [
            'not json' => ['not-json', 400],
            'no position' => ['{"is_shiny":true}', 400],
            'no is_shiny' => ['{"position":2}', 400],
            'position is not an int' => ['{"position":"2","is_shiny":true}', 400],
            'is_shiny is not a bool' => ['{"position":2,"is_shiny":1}', 400],
            'refused by the api' => ['{"position":1,"is_shiny":false}', 409],
        ];
    }
}
