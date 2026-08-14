<?php

declare(strict_types=1);

namespace App\Service;

use App\Service\Api\BannerPipelineApiService;
use App\Service\Api\GithubActionsApiService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Uid\Uuid;

class TriggerBannersPipelineService
{
    public function __construct(
        private readonly GithubActionsApiService $bannerGithubActionsApiService,
        private readonly BannerPipelineApiService $bannerPipelineApiService,
        private readonly LoggerInterface $logger,
    ) {}

    public function triggerUpdateBanners(): void
    {
        $correlationId = Uuid::v4()->toRfc4122();

        $this->bannerGithubActionsApiService->dispatchWorkflow($correlationId);

        try {
            $this->bannerPipelineApiService->create($correlationId);
        } catch (\RuntimeException $e) {
            $this->logger->critical($e->getMessage());
        }
    }
}
