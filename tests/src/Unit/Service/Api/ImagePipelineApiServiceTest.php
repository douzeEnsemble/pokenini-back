<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Api;

use App\Exception\ModifyFailedException;
use App\Service\Api\ImagePipelineApiService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * @internal
 */
#[CoversClass(ImagePipelineApiService::class)]
final class ImagePipelineApiServiceTest extends TestCase
{
    public function testCreate(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getContent')->willReturn('');
        $response->method('getStatusCode')->willReturn(201);

        $client = $this->createMock(HttpClientInterface::class);
        $client
            ->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                'https://api.domain/istration/image-pipeline-runs',
                $this->callback(fn (array $o): bool => ['correlationId' => 'corr-1'] === $o['json']),
            )
            ->willReturn($response)
        ;

        $this->getService($client)->create('corr-1');
    }

    public function testUpdateFields(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getContent')->willReturn('');
        $response->method('getStatusCode')->willReturn(200);

        $client = $this->createMock(HttpClientInterface::class);
        $client
            ->expects($this->once())
            ->method('request')
            ->with(
                'PATCH',
                'https://api.domain/istration/image-pipeline-runs/corr-1',
                $this->callback(fn (array $o): bool => ['workflowARunId' => 42] === $o['json']),
            )
            ->willReturn($response)
        ;

        $this->getService($client)->updateFields('corr-1', ['workflowARunId' => 42]);
    }

    public function testGetLatestReturnsDecodedBody(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getContent')->willReturn('{"correlation_id":"corr-1","workflow_a_run_id":null}');
        $response->method('getStatusCode')->willReturn(200);

        $client = $this->createMock(HttpClientInterface::class);
        $client->method('request')->willReturn($response);

        $result = $this->getService($client)->getLatest();

        $this->assertSame(['correlation_id' => 'corr-1', 'workflow_a_run_id' => null], $result);
    }

    public function testGetLatestReturnsNullOn404(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(404);

        $client = $this->createMock(HttpClientInterface::class);
        $client
            ->method('request')
            ->willReturnCallback(function () use ($response): ResponseInterface {
                $exception = $this->createStub(ClientExceptionInterface::class);
                $exception->method('getResponse')->willReturn($response);

                throw $exception;
            })
        ;

        $this->assertNull($this->getService($client)->getLatest());
    }

    public function testCreateFailsOnTransportError(): void
    {
        $client = $this->createMock(HttpClientInterface::class);
        $client->method('request')->willThrowException(
            $this->createMock(\Symfony\Component\HttpClient\Exception\TransportException::class)
        );

        $this->expectException(ModifyFailedException::class);

        $this->getService($client)->create('corr-1');
    }

    private function getService(HttpClientInterface $client): ImagePipelineApiService
    {
        $cache = new TagAwareAdapter(new ArrayAdapter(), new ArrayAdapter());

        return new ImagePipelineApiService(
            $this->createMock(LoggerInterface::class),
            $client,
            'https://api.domain',
            './resources/certificates/cacert.pem',
            $cache,
            'web',
            'douze',
        );
    }
}
