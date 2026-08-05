<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Api;

use App\Service\Api\GetVersionApiService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * @internal
 */
#[CoversClass(GetVersionApiService::class)]
final class GetVersionApiServiceTest extends TestCase
{
    public function testGetReturnsVersionFromDecodedBody(): void
    {
        $response = $this->createStub(ResponseInterface::class);
        $response->method('getContent')->willReturn('{"version":"1.2.12"}');

        $client = $this->createMock(HttpClientInterface::class);
        $client
            ->expects($this->once())
            ->method('request')
            ->with(
                'GET',
                'https://api.domain/version',
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

        $this->assertSame('1.2.12', $this->getService($client)->get());
    }

    public function testGetReturnsNullOnTransportError(): void
    {
        $client = $this->createMock(HttpClientInterface::class);
        $client->method('request')->willThrowException(
            $this->createMock(TransportException::class)
        );

        $this->assertNull($this->getService($client)->get());
    }

    public function testGetReturnsNullOnHttpError(): void
    {
        $errorResponse = $this->createMock(ResponseInterface::class);
        $errorResponse->method('getStatusCode')->willReturn(500);

        $exception = $this->createStub(ClientExceptionInterface::class);
        $exception->method('getResponse')->willReturn($errorResponse);

        $response = $this->createMock(ResponseInterface::class);
        $response
            ->expects($this->once())
            ->method('getContent')
            ->willThrowException($exception)
        ;

        $client = $this->createMock(HttpClientInterface::class);
        $client->method('request')->willReturn($response);

        $this->assertNull($this->getService($client)->get());
    }

    public function testGetReturnsNullOnMalformedJson(): void
    {
        $response = $this->createStub(ResponseInterface::class);
        $response->method('getContent')->willReturn('not json');

        $client = $this->createMock(HttpClientInterface::class);
        $client->method('request')->willReturn($response);

        $this->assertNull($this->getService($client)->get());
    }

    private function getService(HttpClientInterface $client): GetVersionApiService
    {
        $cache = new TagAwareAdapter(new ArrayAdapter(), new ArrayAdapter());

        return new GetVersionApiService(
            $this->createStub(LoggerInterface::class),
            $client,
            'https://api.domain',
            './resources/certificates/cacert.pem',
            $cache,
            'web',
            'douze',
        );
    }
}
