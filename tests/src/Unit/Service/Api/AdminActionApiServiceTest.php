<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Api;

use App\Exception\ActionAlreadyRunningException;
use App\Exception\ModifyFailedException;
use App\Service\Api\AdminActionApiService;
use App\Tests\Utils\WithConsecutive;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;
use Symfony\Component\HttpClient\Exception\ClientException;
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

    #[Test]
    public function update(): void
    {
        $this->getService('update/start')->update('start');

        $this->assertEmpty($this->cachePool->getValues());
    }

    #[Test]
    public function calculate(): void
    {
        $this->getService('calculate/start')->calculate('start');

        $this->assertEmpty($this->cachePool->getValues());
    }

    #[Test]
    public function updateFails(): void
    {
        $this->expectException(ModifyFailedException::class);

        $this->getFailingService()->update('start');
    }

    #[Test]
    public function calculateFails(): void
    {
        $this->expectException(ModifyFailedException::class);

        $this->getFailingService()->calculate('start');
    }

    #[Test]
    public function updateAlreadyRunning(): void
    {
        try {
            $this->getFailingService($this->clientException(409))->update('start');
            $this->fail('ActionAlreadyRunningException expected');
        } catch (ActionAlreadyRunningException $e) {
            $this->assertSame('admin.action.already_running', $e->getMessage());
        }
    }

    #[Test]
    public function calculateAlreadyRunning(): void
    {
        $this->expectException(ActionAlreadyRunningException::class);

        $this->getFailingService($this->clientException(409))->calculate('start');
    }

    #[Test]
    public function otherClientErrorsAreModifyFailures(): void
    {
        $this->expectException(ModifyFailedException::class);

        $this->getFailingService($this->clientException(404))->update('start');
    }

    private function clientException(int $status): ClientException
    {
        $response = $this->createStub(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn($status);
        $response->method('getInfo')->willReturnCallback(
            static fn (?string $type = null): mixed => match ($type) {
                'http_code' => $status,
                'url' => 'https://api.domain/istration/update/start',
                'response_headers' => [],
                default => [],
            }
        );
        $response->method('getContent')->willReturn('');
        $response->method('toArray')->willReturn([]);

        return new ClientException($response);
    }

    private function getFailingService(?\Throwable $failure = null): AdminActionApiService
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('info');

        $client = $this->createMock(HttpClientInterface::class);
        $client
            ->expects($this->once())
            ->method('request')
            ->willThrowException($failure ?? new TransportException('Network error'))
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
                    [],
                ],
            ]))
        ;
        $logger
            ->expects($this->once())
            ->method('debug')
            ->with('Response body', ['response' => $json])
        ;

        $client = $this->createMock(HttpClientInterface::class);

        $response = $this->createMock(ResponseInterface::class);
        $response
            ->expects($this->once())
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
