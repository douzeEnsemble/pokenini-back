<?php

declare(strict_types=1);

namespace App\Service;

use App\Service\Api\BannerPipelineApiService;
use App\Service\Api\GithubQueryApiService;

class BannerPipelineStatusService
{
    public function __construct(
        private readonly BannerPipelineApiService $bannerPipelineApiService,
        private readonly GithubQueryApiService $githubQueryApiService,
        private readonly string $githubImagesRepo,
        private readonly string $githubBannersWorkflowFile,
        private readonly string $githubBannersWorkflowBFile,
        private readonly string $githubResourcesRepo,
    ) {}

    /**
     * @return null|array<string, mixed>
     */
    public function getStatus(bool $refresh): ?array
    {
        $latest = $this->bannerPipelineApiService->getLatest();

        if (null === $latest || !$refresh) {
            return $latest;
        }

        $updates = $this->pollNextStage($latest);

        if ([] === $updates) {
            return $latest;
        }

        /** @var string $correlationId */
        $correlationId = $latest['correlation_id'];

        $this->bannerPipelineApiService->updateFields($correlationId, $updates);

        return $this->bannerPipelineApiService->getLatest();
    }

    /**
     * @param array<string, mixed> $latest
     *
     * @return array<string, int|string>
     */
    private function pollNextStage(array $latest): array
    {
        if (null === $latest['workflow_a_conclusion']) {
            return $this->pollWorkflowA($latest);
        }

        if ('success' === $latest['workflow_a_conclusion'] && 'merged' !== $latest['icon_pr_state']) {
            return $this->pollIconPr($latest);
        }

        if (null !== $latest['icon_pr_merge_commit_sha'] && null === $latest['workflow_b_conclusion']) {
            return $this->pollWorkflowB($latest);
        }

        if ('success' === $latest['workflow_b_conclusion']) {
            return $this->pollResourcesPr($latest);
        }

        return [];
    }

    /**
     * @param array<string, mixed> $latest
     *
     * @return array<string, int|string>
     */
    private function pollWorkflowA(array $latest): array
    {
        /** @var string $correlationId */
        $correlationId = $latest['correlation_id'];

        $run = $this->githubQueryApiService->findWorkflowRunByDisplayTitle(
            $this->githubImagesRepo,
            $this->githubBannersWorkflowFile,
            "Update banners ({$correlationId})",
        );

        if (null === $run) {
            return [];
        }

        $updates = [
            'workflowARunId' => $run['id'],
            'workflowAStatus' => $run['status'],
            'workflowAUrl' => $run['htmlUrl'],
        ];

        if (null !== $run['conclusion']) {
            $updates['workflowAConclusion'] = $run['conclusion'];
        }

        return $updates;
    }

    /**
     * @param array<string, mixed> $latest
     *
     * @return array<string, int|string>
     */
    private function pollIconPr(array $latest): array
    {
        /** @var int|string $workflowARunId */
        $workflowARunId = $latest['workflow_a_run_id'];

        $pullRequest = $this->githubQueryApiService->findPullRequestByBranch(
            $this->githubImagesRepo,
            "update-banners-{$workflowARunId}",
        );

        if (null === $pullRequest) {
            return [];
        }

        $updates = [
            'iconPrNumber' => $pullRequest['number'],
            'iconPrUrl' => $pullRequest['htmlUrl'],
            'iconPrState' => $pullRequest['state'],
        ];

        if (null !== $pullRequest['mergeCommitSha']) {
            $updates['iconPrMergeCommitSha'] = $pullRequest['mergeCommitSha'];
        }

        return $updates;
    }

    /**
     * @param array<string, mixed> $latest
     *
     * @return array<string, int|string>
     */
    private function pollWorkflowB(array $latest): array
    {
        /** @var string $mergeCommitSha */
        $mergeCommitSha = $latest['icon_pr_merge_commit_sha'];

        $run = $this->githubQueryApiService->findWorkflowRunByHeadSha(
            $this->githubImagesRepo,
            $this->githubBannersWorkflowBFile,
            $mergeCommitSha,
        );

        if (null === $run) {
            return [];
        }

        $updates = [
            'workflowBRunId' => $run['id'],
            'workflowBStatus' => $run['status'],
            'workflowBUrl' => $run['htmlUrl'],
        ];

        if (null !== $run['conclusion']) {
            $updates['workflowBConclusion'] = $run['conclusion'];
        }

        return $updates;
    }

    /**
     * @param array<string, mixed> $latest
     *
     * @return array<string, int|string>
     */
    private function pollResourcesPr(array $latest): array
    {
        /** @var string $mergeCommitSha */
        $mergeCommitSha = $latest['icon_pr_merge_commit_sha'];

        $pullRequest = $this->githubQueryApiService->findPullRequestByBranch(
            $this->githubResourcesRepo,
            "sync-images-{$mergeCommitSha}",
        );

        if (null === $pullRequest) {
            return [];
        }

        return [
            'resourcesPrNumber' => $pullRequest['number'],
            'resourcesPrUrl' => $pullRequest['htmlUrl'],
            'resourcesPrState' => $pullRequest['state'],
        ];
    }
}
