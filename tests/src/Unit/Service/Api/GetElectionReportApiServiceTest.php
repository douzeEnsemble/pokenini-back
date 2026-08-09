<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Api;

use App\Service\Api\GetElectionReportApiService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * @internal
 */
#[CoversClass(GetElectionReportApiService::class)]
final class GetElectionReportApiServiceTest extends TestCase
{
    private ArrayAdapter $cachePool;
    private TagAwareAdapter $cache;

    #[Test]
    public function getReport(): void
    {
        $rawJson = json_encode([
            'top' => array_fill(0, 5, ['pokemon' => ['slug' => 'bulbasaur']]),
            'metrics' => [
                'view_count' => ['sum' => 6, 'max' => 1],
                'win_count' => ['sum' => 2, 'max' => 1],
                'completion' => ['under_max_count' => 1, 'at_max_count' => 5],
                'dex_total_count' => 48,
            ],
        ]);

        $result = $this->getService('4564650', 'home', 'fav', 5, (string) $rawJson)
            ->getReport('4564650', 'home', 'fav', 5)
        ;

        $this->assertCount(5, $result['top']);
        $this->assertSame(
            [
                'view_count' => ['sum' => 6, 'max' => 1],
                'win_count' => ['sum' => 2, 'max' => 1],
                'completion' => ['under_max_count' => 1, 'at_max_count' => 5],
                'dex_total_count' => 48,
            ],
            $result['metrics'],
        );

        $this->assertEmpty($this->cachePool->getValues());
    }

    private function getService(
        string $trainerId,
        string $dexSlug,
        string $electionSlug,
        int $count,
        string $json,
    ): GetElectionReportApiService {
        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->exactly(2))
            ->method('info')
        ;

        $client = $this->createMock(HttpClientInterface::class);

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
                "https://api.domain/election/{$trainerId}/{$dexSlug}",
                [
                    'query' => [
                        'election_slug' => $electionSlug,
                        'count' => $count,
                    ],
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

        return new GetElectionReportApiService(
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
