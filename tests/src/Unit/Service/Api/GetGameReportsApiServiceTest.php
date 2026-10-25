<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Api;

use App\Service\Api\GetGameReportsApiService;
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
#[CoversClass(GetGameReportsApiService::class)]
final class GetGameReportsApiServiceTest extends TestCase
{
    #[Test]
    public function get(): void
    {
        $reports = $this->getService()->get();

        $this->assertArrayHasKey('games', $reports);
        $this->assertIsArray($reports['games']);
        $this->assertCount(3, $reports['games']);
        $this->assertArrayHasKey('daily_games', $reports);
        $this->assertIsArray($reports['daily_games']);
        $this->assertCount(30, $reports['daily_games']);
    }

    private function getService(): GetGameReportsApiService
    {
        $client = $this->createMock(HttpClientInterface::class);

        $json = (string) file_get_contents('/app/tests/resources/unit/service/api/reports_games.json');

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
                'https://api.domain/reports/games',
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

        return new GetGameReportsApiService(
            $this->createStub(LoggerInterface::class),
            $client,
            'https://api.domain',
            './resources/certificates/cacert.pem',
            $this->createStub(TagAwareCacheInterface::class),
            'web',
            'douze',
        );
    }
}
