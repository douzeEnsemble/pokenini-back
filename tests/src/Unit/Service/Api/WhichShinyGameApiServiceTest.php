<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Api;

use App\Service\Api\WhichShinyGameApiService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
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
        $service = $this->service('GET', 'https://api.local/which_shiny_game/8800088/games', '[{"id":"g1"}]');

        $this->assertSame([['id' => 'g1']], $service->getGames('8800088'));
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
    public function answer(): void
    {
        $service = $this->service('POST', 'https://api.local/which_shiny_game/8800088/games/g1/answers', '{"is_correct":true}', '{"position":2,"slot":2}');

        $this->assertSame(['is_correct' => true], $service->answer('8800088', 'g1', 2, 2));
    }

    private function service(string $method, string $url, string $content, ?string $expectedBody = null): WhichShinyGameApiService
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
            $this->createStub(TagAwareCacheInterface::class),
            'login',
            'password',
        );
    }
}
