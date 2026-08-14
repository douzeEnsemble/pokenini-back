<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\Api\BannerPipelineApiService;
use App\Service\Api\GithubActionsApiService;
use App\Service\TriggerBannersPipelineService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @internal
 */
#[CoversClass(TriggerBannersPipelineService::class)]
final class TriggerBannersPipelineServiceTest extends TestCase
{
    #[Test]
    public function triggerUpdateBannersDispatchesAndRegistersRun(): void
    {
        $dispatchedCorrelationId = null;

        $bannerGithubActionsApiService = $this->createMock(GithubActionsApiService::class);
        $bannerGithubActionsApiService
            ->expects($this->once())
            ->method('dispatchWorkflow')
            ->with($this->matchesRegularExpression('/^[0-9a-f-]{36}$/'))
            ->willReturnCallback(function (string $correlationId) use (&$dispatchedCorrelationId): void {
                $dispatchedCorrelationId = $correlationId;
            })
        ;

        $bannerPipelineApiService = $this->createMock(BannerPipelineApiService::class);
        $bannerPipelineApiService
            ->expects($this->once())
            ->method('create')
            ->with($this->callback(function (string $correlationId) use (&$dispatchedCorrelationId): bool {
                return $correlationId === $dispatchedCorrelationId;
            }))
        ;

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->never())->method('critical');

        $service = new TriggerBannersPipelineService($bannerGithubActionsApiService, $bannerPipelineApiService, $logger);

        $service->triggerUpdateBanners();
    }

    #[Test]
    public function registrationFailureIsLoggedButDoesNotThrow(): void
    {
        $bannerGithubActionsApiService = $this->createMock(GithubActionsApiService::class);

        $bannerPipelineApiService = $this->createMock(BannerPipelineApiService::class);
        $bannerPipelineApiService
            ->method('create')
            ->willThrowException(new \RuntimeException('pokenini-api unreachable'))
        ;

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->once())
            ->method('critical')
            ->with('pokenini-api unreachable')
        ;

        $service = new TriggerBannersPipelineService($bannerGithubActionsApiService, $bannerPipelineApiService, $logger);

        $service->triggerUpdateBanners();
    }
}
