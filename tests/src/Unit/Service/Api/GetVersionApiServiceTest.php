<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Api;

use App\Service\Api\GetVersionApiService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
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
    #[Test]
    public function getReturnsVersionAndUpdatedAtFromDecodedBody(): void
    {
        $response = $this->createStub(ResponseInterface::class);
        $response->method('getContent')->willReturn('{"version":"1.2.12","updated_at":"2026-08-05T09:12:00+00:00"}');

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

        $result = $this->getService($client)->get();

        $this->assertSame('1.2.12', $result['version']);
        $this->assertSame('2026-08-05T09:12:00+00:00', $result['updated_at']?->format(\DateTimeInterface::ATOM));
    }

    #[Test]
    public function getHandlesNullUpdatedAtField(): void
    {
        $response = $this->createStub(ResponseInterface::class);
        $response->method('getContent')->willReturn('{"version":"1.2.12","updated_at":null}');

        $client = $this->createMock(HttpClientInterface::class);
        $client->method('request')->willReturn($response);

        $result = $this->getService($client)->get();

        $this->assertSame('1.2.12', $result['version']);
        $this->assertNull($result['updated_at']);
    }

    #[Test]
    public function getReturnsNullFieldsOnTransportError(): void
    {
        $client = $this->createMock(HttpClientInterface::class);
        $client->method('request')->willThrowException(
            $this->createMock(TransportException::class)
        );

        $result = $this->getService($client)->get();

        $this->assertArrayHasKey('version', $result);
        $this->assertNull($result['version']);
        $this->assertNull($result['updated_at']);
    }

    #[Test]
    public function getReturnsNullFieldsOnHttpError(): void
    {
        $errorResponse = $this->createStub(ResponseInterface::class);
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

        $result = $this->getService($client)->get();

        $this->assertArrayHasKey('version', $result);
        $this->assertNull($result['version']);
        $this->assertNull($result['updated_at']);
    }

    #[Test]
    public function getReturnsNullFieldsOnMalformedJson(): void
    {
        $response = $this->createStub(ResponseInterface::class);
        $response->method('getContent')->willReturn('not json');

        $client = $this->createMock(HttpClientInterface::class);
        $client->method('request')->willReturn($response);

        $result = $this->getService($client)->get();

        $this->assertArrayHasKey('version', $result);
        $this->assertNull($result['version']);
        $this->assertNull($result['updated_at']);
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
