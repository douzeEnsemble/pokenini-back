<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Api;

use App\Service\Api\WhichShinyGameApiService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * @internal
 */
#[CoversClass(WhichShinyGameApiService::class)]
final class WhichShinyGameApiServiceTest extends TestCase
{
    #[Test]
    public function getDexList(): void
    {
        $service = $this->service('GET', 'https://api.local/which_shiny_game/8800088/dex?include_premium_dex=1', '[{"slug":"demo"}]');

        $this->assertSame([['slug' => 'demo']], $service->getDexList('8800088', ['include_premium_dex' => '1']));
    }

    #[Test]
    public function getDexListWithoutParams(): void
    {
        $service = $this->service('GET', 'https://api.local/which_shiny_game/8800088/dex?', '[]');

        $this->assertSame([], $service->getDexList('8800088'));
    }

    #[Test]
    public function getGames(): void
    {
        $cache = new TagAwareAdapter(new ArrayAdapter());
        $service = $this->service('GET', 'https://api.local/which_shiny_game/8800088/games', '[{"id":"g1"}]', null, $cache);

        $this->assertSame([['id' => 'g1']], $service->getGames('8800088'));
        // Served from the cache: the client expects one request only
        $this->assertSame([['id' => 'g1']], $service->getGames('8800088'));

        $item = $cache->getItem('games_which_shiny_game_8800088');

        $this->assertTrue($item->isHit());
        $this->assertSame('[{"id":"g1"}]', $item->get());
        $this->assertSame(
            ['tags' => ['trainer#8800088' => 'trainer#8800088', 'games_which_shiny_game_8800088' => 'games_which_shiny_game_8800088']],
            $item->getMetadata(),
        );
    }

    #[Test]
    public function createGameEmptiesTheGamesCache(): void
    {
        $cache = new TagAwareAdapter(new ArrayAdapter());
        $cache->get('games_which_shiny_game_8800088', static function (ItemInterface $item): string {
            $item->tag(['games_which_shiny_game_8800088']);

            return '[]';
        });
        $cache->get('games_which_shiny_game_other', static fn (): string => '[]');

        $service = $this->service('POST', 'https://api.local/which_shiny_game/8800088/games', '{"id":"g1"}', '{"dex_slug":"demo","round_count":null}', $cache);
        $service->createGame('8800088', 'demo', null);

        $this->assertFalse($cache->getItem('games_which_shiny_game_8800088')->isHit());
        $this->assertTrue($cache->getItem('games_which_shiny_game_other')->isHit());
    }

    #[Test]
    public function failedCreateGameEmptiesTheGamesCache(): void
    {
        $cache = new TagAwareAdapter(new ArrayAdapter());
        $cache->get('games_which_shiny_game_8800088', static function (ItemInterface $item): string {
            $item->tag(['games_which_shiny_game_8800088']);

            return '[]';
        });

        $response = $this->createStub(ResponseInterface::class);
        $response->method('getContent')->willThrowException($this->createStub(HttpExceptionInterface::class));

        $client = $this->createStub(HttpClientInterface::class);
        $client->method('request')->willReturn($response);

        $service = new WhichShinyGameApiService(
            $this->createStub(LoggerInterface::class),
            $client,
            'https://api.local',
            '/path/to/cafile',
            $cache,
            'login',
            'password',
        );

        try {
            $service->createGame('8800088', 'demo', null);
            $this->fail('The API error should be thrown');
        } catch (HttpExceptionInterface) {
            $this->assertFalse($cache->getItem('games_which_shiny_game_8800088')->isHit());
        }
    }

    #[Test]
    public function failedAnswerEmptiesTheGamesCache(): void
    {
        $cache = new TagAwareAdapter(new ArrayAdapter());
        $cache->get('games_which_shiny_game_8800088', static function (ItemInterface $item): string {
            $item->tag(['games_which_shiny_game_8800088']);

            return '[]';
        });

        $response = $this->createStub(ResponseInterface::class);
        $response->method('getContent')->willThrowException($this->createStub(HttpExceptionInterface::class));

        $client = $this->createStub(HttpClientInterface::class);
        $client->method('request')->willReturn($response);

        $service = new WhichShinyGameApiService(
            $this->createStub(LoggerInterface::class),
            $client,
            'https://api.local',
            '/path/to/cafile',
            $cache,
            'login',
            'password',
        );

        try {
            $service->answer('8800088', 'g1', 2, 1);
            $this->fail('The API error should be thrown');
        } catch (HttpExceptionInterface) {
            $this->assertFalse($cache->getItem('games_which_shiny_game_8800088')->isHit());
        }
    }

    #[Test]
    public function createGame(): void
    {
        $service = $this->service('POST', 'https://api.local/which_shiny_game/8800088/games', '{"id":"g1"}', '{"dex_slug":"demo","round_count":null}');

        $this->assertSame(['id' => 'g1'], $service->createGame('8800088', 'demo', null));
    }

    #[Test]
    public function createGameWithRoundCount(): void
    {
        $service = $this->service('POST', 'https://api.local/which_shiny_game/8800088/games', '{"id":"g1"}', '{"dex_slug":"demo","round_count":10}');

        $this->assertSame(['id' => 'g1'], $service->createGame('8800088', 'demo', 10));
    }

    #[Test]
    public function getGame(): void
    {
        $service = $this->service('GET', 'https://api.local/which_shiny_game/8800088/games/g1', '{"round":null}');

        $this->assertSame(['round' => null], $service->getGame('8800088', 'g1'));
    }

    #[Test]
    public function getRounds(): void
    {
        $service = $this->service('GET', 'https://api.local/which_shiny_game/8800088/games/g1/rounds', '{"rounds":[]}');

        $this->assertSame(['rounds' => []], $service->getRounds('8800088', 'g1'));
    }

    #[Test]
    public function answer(): void
    {
        $cache = new TagAwareAdapter(new ArrayAdapter());
        $cache->get('games_which_shiny_game_8800088', static function (ItemInterface $item): string {
            $item->tag(['games_which_shiny_game_8800088']);

            return '[]';
        });

        $service = $this->service('POST', 'https://api.local/which_shiny_game/8800088/games/g1/answers', '{"is_correct":true}', '{"position":2,"slot":2}', $cache);

        $this->assertSame(['is_correct' => true], $service->answer('8800088', 'g1', 2, 2));
        $this->assertFalse($cache->getItem('games_which_shiny_game_8800088')->isHit());
    }

    #[Test]
    public function getDexListInALanguage(): void
    {
        $service = $this->service('GET', 'https://api.local/which_shiny_game/8800088/dex?include_premium_dex=1&lang=fr', '[]');

        $this->assertSame([], $service->getDexList('8800088', ['include_premium_dex' => '1'], 'fr'));
    }

    #[Test]
    public function getGamesInALanguage(): void
    {
        $cache = new TagAwareAdapter(new ArrayAdapter());
        $service = $this->service('GET', 'https://api.local/which_shiny_game/8800088/games?lang=fr', '[{"id":"g1"}]', null, $cache);

        $this->assertSame([['id' => 'g1']], $service->getGames('8800088', 'fr'));
        $this->assertSame([['id' => 'g1']], $service->getGames('8800088', 'fr'));

        $this->assertTrue($cache->getItem('games_which_shiny_game_8800088_fr')->isHit());
        $this->assertFalse($cache->getItem('games_which_shiny_game_8800088')->isHit());
    }

    #[Test]
    public function createGameEmptiesTheGamesInEveryLanguage(): void
    {
        $cache = new TagAwareAdapter(new ArrayAdapter());
        $setup = $this->service('GET', 'https://api.local/which_shiny_game/8800088/games?lang=fr', '[]', null, $cache);
        $setup->getGames('8800088', 'fr');

        $service = $this->service('POST', 'https://api.local/which_shiny_game/8800088/games?lang=fr', '{"id":"g1"}', '{"dex_slug":"demo","round_count":null}', $cache);
        $service->createGame('8800088', 'demo', null, 'fr');

        $this->assertFalse($cache->getItem('games_which_shiny_game_8800088_fr')->isHit());
    }

    #[Test]
    public function getGameInALanguage(): void
    {
        $service = $this->service('GET', 'https://api.local/which_shiny_game/8800088/games/g1?lang=fr', '{"id":"g1"}');

        $this->assertSame(['id' => 'g1'], $service->getGame('8800088', 'g1', 'fr'));
    }

    #[Test]
    public function getRoundsInALanguage(): void
    {
        $service = $this->service('GET', 'https://api.local/which_shiny_game/8800088/games/g1/rounds?lang=fr', '{"rounds":[]}');

        $this->assertSame(['rounds' => []], $service->getRounds('8800088', 'g1', 'fr'));
    }

    #[Test]
    public function answerInALanguage(): void
    {
        $service = $this->service('POST', 'https://api.local/which_shiny_game/8800088/games/g1/answers?lang=fr', '{"is_correct":true}', '{"position":2,"slot":2}');

        $this->assertSame(['is_correct' => true], $service->answer('8800088', 'g1', 2, 2, 'fr'));
    }

    private function service(string $method, string $url, string $content, ?string $expectedBody = null, ?TagAwareAdapter $cache = null): WhichShinyGameApiService
    {
        $response = $this->createStub(ResponseInterface::class);
        $response->method('getContent')->willReturn($content);

        $client = $this->createMock(HttpClientInterface::class);
        $client->expects($this->once())
            ->method('request')
            ->with(
                $method,
                $url,
                $this->callback(fn (array $options): bool => ($options['body'] ?? null) === $expectedBody),
            )
            ->willReturn($response)
        ;

        return new WhichShinyGameApiService(
            $this->createStub(LoggerInterface::class),
            $client,
            'https://api.local',
            '/path/to/cafile',
            $cache ?? new TagAwareAdapter(new ArrayAdapter()),
            'login',
            'password',
        );
    }
}
