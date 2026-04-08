<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Api;

use App\Service\Api\GetDexListApiService;
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
#[CoversClass(GetDexListApiService::class)]
final class GetDexListApiServiceTest extends TestCase
{
    private ArrayAdapter $cachePool;
    private TagAwareAdapter $cache;

    public function testGetWithPremium(): void
    {
        $expectedSlugs = [
            'goldsilvercrystal',
            'homepokemongo',
            'alpha',
            'mega',
        ];

        $this->assertEquals(
            $expectedSlugs,
            self::extractSlugs($this->getServiceWithPremium('123')->getWithPremium('123')),
        );

        $dexCacheItem = $this->cache->getItem('dex_123_include_premium_dex=1');

        $this->assertSame(
            [
                'tags' => [
                    'dex' => 'dex',
                    'trainer#123' => 'trainer#123',
                ],
            ],
            $dexCacheItem->getMetadata(),
        );

        /** @var string $value */
        $value = $dexCacheItem->get();

        /** @var string[][] */
        $jsonData = json_decode($value, true);

        $this->assertEquals(
            $expectedSlugs,
            self::extractSlugs($jsonData),
        );
    }

    public function testGetWithUnreleasedAndPremium(): void
    {
        $expectedSlugs = [
            'redgreenblueyellow',
            'goldsilvercrystal',
            'homepokemongo',
            'alpha',
            'mega',
        ];

        $this->assertEquals(
            $expectedSlugs,
            self::extractSlugs($this->getServiceWithUnreleasedAndPremium('123')->getWithUnreleasedAndPremium('123')),
        );

        $dexCacheItem = $this->cache->getItem('dex_123_include_unreleased_dex=1_include_premium_dex=1');

        $this->assertSame(
            [
                'tags' => [
                    'dex' => 'dex',
                    'trainer#123' => 'trainer#123',
                ],
            ],
            $dexCacheItem->getMetadata(),
        );

        /** @var string $value */
        $value = $dexCacheItem->get();

        /** @var string[][] */
        $jsonData = json_decode($value, true);

        $this->assertEquals(
            $expectedSlugs,
            self::extractSlugs($jsonData),
        );
    }

    private function getServiceWithPremium(string $trainerId): GetDexListApiService
    {
        $json = (string) file_get_contents(
            "/app/tests/resources/unit/service/api/dex_{$trainerId}_premium.json"
        );

        return $this->getMockService(
            $json,
            "dex/{$trainerId}/list?include_premium_dex=1",
        );
    }

    private function getServiceWithUnreleasedAndPremium(string $trainerId): GetDexListApiService
    {
        $json = (string) file_get_contents(
            "/app/tests/resources/unit/service/api/dex_{$trainerId}_unreleased_and_premium.json"
        );

        return $this->getMockService(
            $json,
            "dex/{$trainerId}/list?include_unreleased_dex=1&include_premium_dex=1",
        );
    }

    private function getMockService(
        string $json,
        string $endpoint,
    ): GetDexListApiService {
        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->exactly(2))
            ->method('info')
        ;

        $client = $this->createMock(HttpClientInterface::class);

        $response = $this->createMock(ResponseInterface::class);
        $response
            ->expects($this->exactly(2))
            ->method('getContent')
            ->willReturn($json)
        ;

        $client
            ->expects($this->once())
            ->method('request')
            ->with(
                'GET',
                "https://api.domain/{$endpoint}",
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

        return new GetDexListApiService(
            $logger,
            $client,
            'https://api.domain',
            './resources/certificates/cacert.pem',
            $this->cache,
            'web',
            'douze',
        );
    }

    /**
     * @param string[][] $items
     *
     * @return string[]
     */
    private static function extractSlugs(array $items): array
    {
        $slugs = [];

        foreach ($items as $item) {
            $slugs[] = $item['slug'];
        }

        return $slugs;
    }
}
