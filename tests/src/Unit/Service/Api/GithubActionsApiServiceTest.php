<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Api;

use App\Exception\ModifyFailedException;
use App\Service\Api\GithubActionsApiService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * @internal
 */
#[CoversClass(GithubActionsApiService::class)]
final class GithubActionsApiServiceTest extends TestCase
{
    public function testDispatchWorkflow(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->exactly(2))
            ->method('info')
        ;

        $response = $this->createMock(ResponseInterface::class);
        $response
            ->expects($this->once())
            ->method('getStatusCode')
            ->willReturn(204)
        ;

        $client = $this->createMock(HttpClientInterface::class);
        $client
            ->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                'https://api.github.com/repos/douzeensemble/pokenini-icon/actions/workflows/update-images.yml/dispatches',
                [
                    'headers' => [
                        'accept' => 'application/vnd.github+json',
                        'authorization' => 'Bearer secret-token',
                        'X-GitHub-Api-Version' => '2022-11-28',
                    ],
                    'json' => [
                        'ref' => 'main',
                    ],
                ],
            )
            ->willReturn($response)
        ;

        $service = new GithubActionsApiService(
            $logger,
            $client,
            'secret-token',
            'douzeensemble/pokenini-icon',
            'update-images.yml',
        );

        $service->dispatchWorkflow();
    }

    public function testDispatchWorkflowFails(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->once())
            ->method('info')
        ;

        $client = $this->createMock(HttpClientInterface::class);
        $client
            ->expects($this->once())
            ->method('request')
            ->willThrowException(new TransportException('Network error'))
        ;

        $service = new GithubActionsApiService(
            $logger,
            $client,
            'secret-token',
            'douzeensemble/pokenini-icon',
            'update-images.yml',
        );

        $this->expectException(ModifyFailedException::class);

        $service->dispatchWorkflow();
    }
}
