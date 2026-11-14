<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Api;

use App\Service\Api\GetActionCatalogApiService;
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
#[CoversClass(GetActionCatalogApiService::class)]
final class GetActionCatalogApiServiceTest extends TestCase
{
    private ArrayAdapter $cachePool;
    private TagAwareAdapter $cache;

    #[Test]
    public function get(): void
    {
        $service = $this->getService();

        $catalog = $service->get();

        $this->assertCount(11, $catalog);
        $this->assertSame(['type' => 'update', 'name' => 'labels', 'depends_on' => []], $catalog[0]);
        $this->assertTrue($this->cache->hasItem('action_catalog'));

        // Second call is served from the cache: the client is requested only once.
        $this->assertSame($catalog, $service->get());
    }

    #[Test]
    public function has(): void
    {
        $service = $this->getService();

        $this->assertTrue($service->has('update', 'labels'));
        $this->assertTrue($service->has('calculate', 'dex_availabilities'));
        $this->assertFalse($service->has('calculate', 'labels'));
        $this->assertFalse($service->has('update', 'truc'));
    }

    private function getService(): GetActionCatalogApiService
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->exactly(2))
            ->method('info')
        ;

        $client = $this->createMock(HttpClientInterface::class);

        $json = (string) file_get_contents('/app/tests/resources/unit/service/api/action_catalog.json');

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
                'https://api.domain/istration/actions',
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

        return new GetActionCatalogApiService(
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
