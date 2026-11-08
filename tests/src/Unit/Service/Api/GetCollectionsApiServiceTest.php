<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Api;

use App\Service\Api\GetCollectionsApiService;
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
#[CoversClass(GetCollectionsApiService::class)]
final class GetCollectionsApiServiceTest extends TestCase
{
    private ArrayAdapter $cachePool;
    private TagAwareAdapter $cache;

    #[Test]
    public function get(): void
    {
        $types = $this->getService()->get();

        $this->assertCount(8, $types);

        /** @var string $value */
        $value = $this->cache->getItem('collections')->get();
        $this->assertNotEmpty($value);
        $this->assertJson($value);
    }

    #[Test]
    public function getInALanguage(): void
    {
        $result = $this->getService('fr')->get('fr');

        $this->assertNotEmpty($result);
        $this->assertTrue($this->cache->hasItem('collections_fr'));
        $this->assertFalse($this->cache->hasItem('collections'));
    }

    private function getService(?string $lang = null): GetCollectionsApiService
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->exactly(2))
            ->method('info')
        ;

        $client = $this->createMock(HttpClientInterface::class);
        $langOptions = null === $lang ? [] : ['query' => ['lang' => $lang]];

        $json = (string) file_get_contents('/app/tests/resources/unit/service/api/collections.json');

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
                'https://api.domain/collections',
                [
                    'headers' => [
                        'accept' => 'application/json',
                    ],
                    'auth_basic' => [
                        'web',
                        'douze',
                    ],
                    'cafile' => './resources/certificates/cacert.pem',
                ] + $langOptions,
            )
            ->willReturn($response)
        ;

        $this->cachePool = new ArrayAdapter();
        $this->cache = new TagAwareAdapter($this->cachePool, new ArrayAdapter());

        return new GetCollectionsApiService(
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
