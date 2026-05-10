<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Api;

use App\Exception\ModifyFailedException;
use App\Service\Api\AdminActionApiService;
use App\Tests\Utils\WithConsecutive;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * @internal
 */
#[CoversClass(AdminActionApiService::class)]
final class AdminActionApiServiceTest extends TestCase
{
    private ArrayAdapter $cachePool;
    private TagAwareAdapter $cache;

    public function testUpdate(): void
    {
        $this->getService('update/start')->update('start');

        $this->assertEmpty($this->cachePool->getValues());
    }

    public function testCalculate(): void
    {
        $this->getService('calculate/start')->calculate('start');

        $this->assertEmpty($this->cachePool->getValues());
    }

    public function testUpdateFails(): void
    {
        $this->expectException(ModifyFailedException::class);

        $this->getFailingService()->update('start');
    }

    public function testCalculateFails(): void
    {
        $this->expectException(ModifyFailedException::class);

        $this->getFailingService()->calculate('start');
    }

    private function getFailingService(): AdminActionApiService
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('info');

        $client = $this->createMock(HttpClientInterface::class);
        $client
            ->expects($this->once())
            ->method('request')
            ->willThrowException(new TransportException('Network error'))
        ;

        $this->cachePool = new ArrayAdapter();
        $this->cache = new TagAwareAdapter($this->cachePool, new ArrayAdapter());

        return new AdminActionApiService(
            $logger,
            $client,
            'https://api.domain',
            './resources/certificates/cacert.pem',
            $this->cache,
            'web',
            'douze',
        );
    }

    private function getService(string $suffix): AdminActionApiService
    {
        $json = <<<JSON
            {
                "suffix": "{$suffix}"
            }
            JSON;

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->exactly(2))
            ->method('info')
            ->with(...WithConsecutive::create(...[
                [
                    "Requesting POST /istration/{$suffix}",
                    [],
                ],
                [
                    'Response status code: 200',
                    [
                        'response' => $json,
                    ],
                ],
            ]))
        ;

        $client = $this->createMock(HttpClientInterface::class);

        $response = $this->createMock(ResponseInterface::class);
        $response
            ->expects($this->exactly(2))
            ->method('getContent')
            ->willReturn($json)
        ;
        $response
            ->expects($this->once())
            ->method('getStatusCode')
            ->willReturn(200)
        ;

        $client
            ->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                "https://api.domain/istration/{$suffix}",
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

        return new AdminActionApiService(
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
