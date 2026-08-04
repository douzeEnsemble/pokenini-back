<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Api;

use App\Service\Api\ModifyAlbumApiService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * @internal
 */
#[CoversClass(ModifyAlbumApiService::class)]
final class ModifyAlbumApiServiceTest extends TestCase
{
    private ArrayAdapter $cachePool;
    private TagAwareAdapter $cache;

    public function testModifyPatch(): void
    {
        $updatedDexSlugs = $this
            ->getService(
                'PATCH',
                'album/123/home/pikachu',
                'yes',
                '{"updatedDexSlugs":["home"]}',
            )
            ->modify(
                'PATCH',
                'home',
                'pikachu',
                'yes',
                '123',
            )
        ;

        $this->assertSame(['home'], $updatedDexSlugs);
        $this->assertEmpty($this->cachePool->getValues());
    }

    public function testModifyPut(): void
    {
        $updatedDexSlugs = $this
            ->getService(
                'PUT',
                'album/123/home/pikachu',
                'yes',
                '{"updatedDexSlugs":["home"]}',
            )
            ->modify(
                'PUT',
                'home',
                'pikachu',
                'yes',
                '123',
            )
        ;

        $this->assertSame(['home'], $updatedDexSlugs);
        $this->assertEmpty($this->cachePool->getValues());
    }

    public function testModifyReturnsUpdatedDexSlugs(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getContent')->willReturn('{"updatedDexSlugs":["national","shiny-living"]}');

        $client = $this->createMock(HttpClientInterface::class);
        $client->expects($this->once())
            ->method('request')
            ->with(
                'PUT',
                'https://api.local/album/8800088/douze/treize',
                $this->callback(function (array $options): bool {
                    return 'yes' === $options['body'];
                }),
            )
            ->willReturn($response)
        ;

        $service = new ModifyAlbumApiService(
            $this->createMock(LoggerInterface::class),
            $client,
            'https://api.local',
            '/path/to/cafile',
            $this->createMock(TagAwareCacheInterface::class),
            'login',
            'password',
        );

        $this->assertSame(
            ['national', 'shiny-living'],
            $service->modify('PUT', 'douze', 'treize', 'yes', '8800088'),
        );
    }

    public function testModifyRejectsAnInvalidHttpMethod(): void
    {
        $service = new ModifyAlbumApiService(
            $this->createMock(LoggerInterface::class),
            $this->createMock(HttpClientInterface::class),
            'https://api.local',
            '/path/to/cafile',
            $this->createMock(TagAwareCacheInterface::class),
            'login',
            'password',
        );

        $this->expectException(\InvalidArgumentException::class);

        $service->modify('GET', 'douze', 'treize', 'yes', '8800088');
    }

    public function testModifyPost(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $logger = $this->createStub(LoggerInterface::class);

        $client = $this->createStub(HttpClientInterface::class);

        $service = new ModifyAlbumApiService(
            $logger,
            $client,
            'https://api.domain',
            './resources/certificates/cacert.pem',
            new TagAwareAdapter(new ArrayAdapter(), new ArrayAdapter()),
            'web',
            'douze',
        );

        $service->modify(
            'POST',
            'home',
            'pikachu',
            'yes',
            '123',
        );
    }

    private function getService(
        string $method,
        string $suffix,
        string $body,
        string $responseContent = '{"updatedDexSlugs":[]}',
    ): ModifyAlbumApiService {
        $logger = $this->createStub(LoggerInterface::class);

        $response = $this->createStub(ResponseInterface::class);
        $response->method('getContent')->willReturn($responseContent);

        $client = $this->createMock(HttpClientInterface::class);
        $client
            ->expects($this->once())
            ->method('request')
            ->with(
                $method,
                "https://api.domain/{$suffix}",
                [
                    'headers' => [
                        'accept' => 'application/json',
                    ],
                    'auth_basic' => [
                        'web',
                        'douze',
                    ],
                    'cafile' => './resources/certificates/cacert.pem',
                    'body' => $body,
                ],
            )
            ->willReturn($response)
        ;

        $this->cachePool = new ArrayAdapter();
        $this->cache = new TagAwareAdapter($this->cachePool, new ArrayAdapter());

        return new ModifyAlbumApiService(
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
