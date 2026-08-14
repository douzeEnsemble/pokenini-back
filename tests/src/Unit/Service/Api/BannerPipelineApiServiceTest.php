<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Api;

use App\Exception\ModifyFailedException;
use App\Service\Api\BannerPipelineApiService;
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
#[CoversClass(BannerPipelineApiService::class)]
final class BannerPipelineApiServiceTest extends TestCase
{
    #[Test]
    public function create(): void
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
                'https://api.domain/istration/banner-pipeline-runs',
                $this->callback(fn (array $o): bool => ['correlationId' => 'corr-1'] === $o['json']),
            )
            ->willReturn($response)
        ;

        $this->getService($client)->create('corr-1');
    }

    #[Test]
    public function updateFields(): void
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
                'https://api.domain/istration/banner-pipeline-runs/corr-1',
                $this->callback(fn (array $o): bool => ['workflowARunId' => 42] === $o['json']),
            )
            ->willReturn($response)
        ;

        $this->getService($client)->updateFields('corr-1', ['workflowARunId' => 42]);
    }

    #[Test]
    public function getLatestReturnsDecodedBody(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getContent')->willReturn('{"correlation_id":"corr-1","workflow_a_run_id":null}');
        $response->method('getStatusCode')->willReturn(200);

        $client = $this->createMock(HttpClientInterface::class);
        $client->method('request')->willReturn($response);

        $result = $this->getService($client)->getLatest();

        $this->assertSame(['correlation_id' => 'corr-1', 'workflow_a_run_id' => null], $result);
    }

    #[Test]
    public function getLatestReturnsNullOn404(): void
    {
        $errorResponse = $this->createStub(ResponseInterface::class);
        $errorResponse->method('getStatusCode')->willReturn(404);

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

        $this->assertNull($this->getService($client)->getLatest());
    }

    #[Test]
    public function createFailsOnTransportError(): void
    {
        $client = $this->createMock(HttpClientInterface::class);
        $client->method('request')->willThrowException(
            $this->createMock(TransportException::class)
        );

        $this->expectException(ModifyFailedException::class);

        $this->getService($client)->create('corr-1');
    }

    #[Test]
    public function updateFieldsFailsOnTransportError(): void
    {
        $client = $this->createMock(HttpClientInterface::class);
        $client->method('request')->willThrowException(
            $this->createMock(TransportException::class)
        );

        $this->expectException(ModifyFailedException::class);

        $this->getService($client)->updateFields('corr-1', ['workflowARunId' => 42]);
    }

    #[Test]
    public function getLatestFailsOnNonNotFoundHttpError(): void
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

        $this->expectException(ModifyFailedException::class);

        $this->getService($client)->getLatest();
    }

    #[Test]
    public function getLatestFailsOnTransportError(): void
    {
        $client = $this->createMock(HttpClientInterface::class);
        $client->method('request')->willThrowException(
            $this->createMock(TransportException::class)
        );

        $this->expectException(ModifyFailedException::class);

        $this->getService($client)->getLatest();
    }

    private function getService(HttpClientInterface $client): BannerPipelineApiService
    {
        $cache = new TagAwareAdapter(new ArrayAdapter(), new ArrayAdapter());

        return new BannerPipelineApiService(
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
