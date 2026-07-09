<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Api;

use App\Service\Api\GetElectionDexListApiService;
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
#[CoversClass(GetElectionDexListApiService::class)]
final class GetElectionDexListApiServiceTest extends TestCase
{
    private ArrayAdapter $cachePool;
    private TagAwareAdapter $cache;

    public function testGet(): void
    {
        $expectedSlugs = ['homeshiny'];

        $this->assertEquals(
            $expectedSlugs,
            self::extractSlugs($this->getService('8800088', 'election/8800088/list?count=0')->get('8800088')),
        );

        $cacheItem = $this->cache->getItem('election_dex_list_8800088');

        /** @var string $value */
        $value = $cacheItem->get();

        /** @var string[][] */
        $jsonData = json_decode($value, true);

        $this->assertEquals($expectedSlugs, self::extractSlugs($jsonData));

        $this->assertSame(
            [
                'dex' => 'dex',
                'election_dex_list' => 'election_dex_list',
                'trainer#8800088' => 'trainer#8800088',
            ],
            $cacheItem->getMetadata()['tags'],
        );
    }

    public function testGetWithPremium(): void
    {
        $expectedSlugs = ['home', 'redgreenblueyellow'];

        $this->assertEquals(
            $expectedSlugs,
            self::extractSlugs(
                $this
                    ->getService('8800088', 'election/8800088/list?count=0&include_premium_dex=1', 'election_dex_list_premium.json')
                    ->getWithPremium('8800088'),
            ),
        );

        $cacheItem = $this->cache->getItem('election_dex_list_8800088_include_premium_dex=1');

        $this->assertSame(
            [
                'dex' => 'dex',
                'election_dex_list' => 'election_dex_list',
                'trainer#8800088' => 'trainer#8800088',
            ],
            $cacheItem->getMetadata()['tags'],
        );
    }

    public function testGetWithUnreleasedAndPremium(): void
    {
        $expectedSlugs = ['home', 'homeshiny', 'redgreenblueyellow', 'redgreenblueyellowshiny'];

        $this->assertEquals(
            $expectedSlugs,
            self::extractSlugs(
                $this
                    ->getService(
                        '8800088',
                        'election/8800088/list?count=0&include_unreleased_dex=1&include_premium_dex=1',
                        'election_dex_list_unreleased_and_premium.json',
                    )
                    ->getWithUnreleasedAndPremium('8800088'),
            ),
        );

        $cacheItem = $this->cache->getItem('election_dex_list_8800088_include_unreleased_dex=1_include_premium_dex=1');

        $this->assertSame(
            [
                'dex' => 'dex',
                'election_dex_list' => 'election_dex_list',
                'trainer#8800088' => 'trainer#8800088',
            ],
            $cacheItem->getMetadata()['tags'],
        );
    }

    public function testGetIsScopedPerTrainer(): void
    {
        $expectedSlugs = ['homeshiny'];

        $service = $this->getService('123', 'election/123/list?count=0');
        $this->assertEquals($expectedSlugs, self::extractSlugs($service->get('123')));

        $this->assertTrue($this->cache->hasItem('election_dex_list_123'));
        $this->assertFalse($this->cache->hasItem('election_dex_list_456'));
    }

    private function getService(
        string $trainerId,
        string $endpoint,
        string $jsonFile = 'election_dex_list.json',
    ): GetElectionDexListApiService {
        $json = (string) file_get_contents(
            "/app/tests/resources/unit/service/api/{$jsonFile}"
        );

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

        return new GetElectionDexListApiService(
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
