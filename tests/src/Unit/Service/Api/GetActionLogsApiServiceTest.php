<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Api;

use App\Service\Api\GetActionLogsApiService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * @internal
 */
#[CoversClass(GetActionLogsApiService::class)]
final class GetActionLogsApiServiceTest extends TestCase
{
    private ArrayAdapter $cachePool;
    private TagAwareAdapter $cache;

    public function testGet(): void
    {
        $actionLogs = $this->getService()->get();

        $this->assertCount(9, $actionLogs);

        $expectedActionTypes = [
            'calculate_dex_availabilities',
            'calculate_pokemon_availabilities',
            'calculate_game_bundles_availabilities',
            'calculate_game_bundles_shinies_availabilities',
            'update_games_collections_and_dex',
            'update_games_availabilities',
            'update_games_shinies_availabilities',
            'update_labels',
            'update_pokemons',
        ];

        $actualActionTypes = array_column($actionLogs, 'action_type');
        foreach ($expectedActionTypes as $actionType) {
            $this->assertContains($actionType, $actualActionTypes);
        }

        $this->assertArrayHasKey('action_type', $actionLogs[0]);
        $this->assertArrayHasKey('current', $actionLogs[0]);
        $this->assertArrayHasKey('last', $actionLogs[0]);

        $this->assertEmpty($this->cachePool->getValues());
    }

    private function getService(): GetActionLogsApiService
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->exactly(2))
            ->method('info')
        ;

        $client = $this->createMock(HttpClientInterface::class);

        $json = (string) file_get_contents('/app/tests/resources/unit/service/api/action_logs.json');

        $response = $this->createMock(ResponseInterface::class);
        $response
            ->expects($this->once())
            ->method('getContent')
            ->willReturn($json)
        ;

        $client
            ->expects($this->once())
            ->method('request')
            ->with(
                'GET',
                'https://api.domain/action_logs',
                [
                    'headers' => [
                        'accept' => 'application/json',
                    ],
                    'auth_basic' => [
                        'web',
                        'douze',
                    ],
                    'cafile' => './resources/certificates/cacert.pem',
                ],
            )
            ->willReturn($response)
        ;

        $this->cachePool = new ArrayAdapter();
        $this->cache = new TagAwareAdapter($this->cachePool, new ArrayAdapter());

        return new GetActionLogsApiService(
            $logger,
            $client,
            'https://api.domain',
            './resources/certificates/cacert.pem',
            $this->cache,
            'web',
            'douze',
        );
    }
}
