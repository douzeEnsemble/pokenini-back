<?php

declare(strict_types=1);

namespace App\Service;

use App\Service\Api\GithubActionsApiService;
use App\Service\Api\ImagePipelineApiService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Uid\Uuid;

class TriggerImagesPipelineService
{
    public function __construct(
        private readonly GithubActionsApiService $githubActionsApiService,
        private readonly ImagePipelineApiService $imagePipelineApiService,
        private readonly LoggerInterface $logger,
    ) {}

    public function triggerUpdateImages(): void
    {
        $correlationId = Uuid::v4()->toRfc4122();

        $this->githubActionsApiService->dispatchWorkflow($correlationId);

        try {
            $this->imagePipelineApiService->create($correlationId);
        } catch (\RuntimeException $e) {
            $this->logger->critical($e->getMessage());
        }
    }
}
