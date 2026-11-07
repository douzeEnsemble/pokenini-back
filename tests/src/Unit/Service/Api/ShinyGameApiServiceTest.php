<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Api;

use App\Service\Api\ShinyGameApiService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * @internal
 */
#[CoversClass(ShinyGameApiService::class)]
final class ShinyGameApiServiceTest extends TestCase
{
    #[Test]
    public function getDexList(): void
    {
        $service = $this->service('GET', 'https://api.local/shiny_game/8800088/dex?include_premium_dex=1', '[{"slug":"demo"}]');

        $this->assertSame([['slug' => 'demo']], $service->getDexList('8800088', ['include_premium_dex' => '1']));
    }

    #[Test]
    public function getDexListWithoutParams(): void
    {
        $service = $this->service('GET', 'https://api.local/shiny_game/8800088/dex?', '[]');

        $this->assertSame([], $service->getDexList('8800088'));
    }

    #[Test]
    public function getGames(): void
    {
        $cache = new TagAwareAdapter(new ArrayAdapter());
        $service = $this->service('GET', 'https://api.local/shiny_game/8800088/games', '[{"id":"g1"}]', null, $cache);

        $this->assertSame([['id' => 'g1']], $service->getGames('8800088'));
        // Served from the cache: the client expects one request only
        $this->assertSame([['id' => 'g1']], $service->getGames('8800088'));

        $item = $cache->getItem('games_shiny_game_8800088');

        $this->assertTrue($item->isHit());
        $this->assertSame('[{"id":"g1"}]', $item->get());
        $this->assertSame(['tags' => ['trainer#8800088' => 'trainer#8800088']], $item->getMetadata());
    }

    #[Test]
    public function createGameEmptiesTheGamesCache(): void
    {
        $cache = new TagAwareAdapter(new ArrayAdapter());
        $cache->get('games_shiny_game_8800088', static fn (): string => '[]');
        $cache->get('games_shiny_game_other', static fn (): string => '[]');

        $service = $this->service('POST', 'https://api.local/shiny_game/8800088/games', '{"id":"g1"}', '{"dex_slug":"demo","round_count":null}', $cache);
        $service->createGame('8800088', 'demo', null);

        $this->assertFalse($cache->getItem('games_shiny_game_8800088')->isHit());
        $this->assertTrue($cache->getItem('games_shiny_game_other')->isHit());
    }

    #[Test]
    public function failedCreateGameEmptiesTheGamesCache(): void
    {
        $cache = new TagAwareAdapter(new ArrayAdapter());
        $cache->get('games_shiny_game_8800088', static fn (): string => '[]');

        $response = $this->createStub(ResponseInterface::class);
        $response->method('getContent')->willThrowException($this->createStub(HttpExceptionInterface::class));

        $client = $this->createStub(HttpClientInterface::class);
        $client->method('request')->willReturn($response);

        $service = new ShinyGameApiService(
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
            $this->assertFalse($cache->getItem('games_shiny_game_8800088')->isHit());
        }
    }

    #[Test]
    public function failedAnswerEmptiesTheGamesCache(): void
    {
        $cache = new TagAwareAdapter(new ArrayAdapter());
        $cache->get('games_shiny_game_8800088', static fn (): string => '[]');

        $response = $this->createStub(ResponseInterface::class);
        $response->method('getContent')->willThrowException($this->createStub(HttpExceptionInterface::class));

        $client = $this->createStub(HttpClientInterface::class);
        $client->method('request')->willReturn($response);

        $service = new ShinyGameApiService(
            $this->createStub(LoggerInterface::class),
            $client,
            'https://api.local',
            '/path/to/cafile',
            $cache,
            'login',
            'password',
        );

        try {
            $service->answer('8800088', 'g1', 2, true);
            $this->fail('The API error should be thrown');
        } catch (HttpExceptionInterface) {
            $this->assertFalse($cache->getItem('games_shiny_game_8800088')->isHit());
        }
    }

    #[Test]
    public function createGame(): void
    {
        $service = $this->service('POST', 'https://api.local/shiny_game/8800088/games', '{"id":"g1"}', '{"dex_slug":"demo","round_count":null}');

        $this->assertSame(['id' => 'g1'], $service->createGame('8800088', 'demo', null));
    }

    #[Test]
    public function createGameWithRoundCount(): void
    {
        $service = $this->service('POST', 'https://api.local/shiny_game/8800088/games', '{"id":"g1"}', '{"dex_slug":"demo","round_count":10}');

        $this->assertSame(['id' => 'g1'], $service->createGame('8800088', 'demo', 10));
    }

    #[Test]
    public function getGame(): void
    {
        $service = $this->service('GET', 'https://api.local/shiny_game/8800088/games/g1', '{"round":null}');

        $this->assertSame(['round' => null], $service->getGame('8800088', 'g1'));
    }

    #[Test]
    public function getRounds(): void
    {
        $service = $this->service('GET', 'https://api.local/shiny_game/8800088/games/g1/rounds', '{"rounds":[]}');

        $this->assertSame(['rounds' => []], $service->getRounds('8800088', 'g1'));
    }

    #[Test]
    public function answer(): void
    {
        $cache = new TagAwareAdapter(new ArrayAdapter());
        $cache->get('games_shiny_game_8800088', static fn (): string => '[]');

        $service = $this->service('POST', 'https://api.local/shiny_game/8800088/games/g1/answers', '{"is_correct":true}', '{"position":2,"is_shiny":true}', $cache);

        $this->assertSame(['is_correct' => true], $service->answer('8800088', 'g1', 2, true));
        $this->assertFalse($cache->getItem('games_shiny_game_8800088')->isHit());
    }

    private function service(string $method, string $url, string $content, ?string $expectedBody = null, ?TagAwareAdapter $cache = null): ShinyGameApiService
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

        return new ShinyGameApiService(
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
