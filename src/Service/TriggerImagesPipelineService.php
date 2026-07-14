<?php

declare(strict_types=1);

namespace App\Service;

use App\Service\Api\GithubActionsApiService;

class TriggerImagesPipelineService
{
    public function __construct(
        private readonly GithubActionsApiService $githubActionsApiService,
    ) {}

    public function triggerUpdateImages(): void
    {
        $this->githubActionsApiService->dispatchWorkflow();
    }
}
