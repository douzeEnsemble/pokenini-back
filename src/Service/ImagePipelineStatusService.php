<?php

declare(strict_types=1);

namespace App\Service;

use App\Service\Api\GithubQueryApiService;
use App\Service\Api\ImagePipelineApiService;

class ImagePipelineStatusService
{
    public function __construct(
        private readonly ImagePipelineApiService $imagePipelineApiService,
        private readonly GithubQueryApiService $githubQueryApiService,
        private readonly string $githubImagesRepo,
        private readonly string $githubImagesWorkflowFile,
        private readonly string $githubImagesWorkflowBFile,
        private readonly string $githubResourcesRepo,
    ) {}

    /**
     * @return array<string, mixed>|null
     */
    public function getStatus(bool $refresh): ?array
    {
        $latest = $this->imagePipelineApiService->getLatest();

        if (null === $latest || !$refresh) {
            return $latest;
        }

        $updates = $this->pollNextStage($latest);

        if ([] === $updates) {
            return $latest;
        }

        /** @var string $correlationId */
        $correlationId = $latest['correlation_id'];

        $this->imagePipelineApiService->updateFields($correlationId, $updates);

        return $this->imagePipelineApiService->getLatest();
    }

    /**
     * @param array<string, mixed> $latest
     *
     * @return array<string, int|string>
     */
    private function pollNextStage(array $latest): array
    {
        if (null === $latest['workflow_a_conclusion']) {
            /** @var string $correlationId */
            $correlationId = $latest['correlation_id'];

            $run = $this->githubQueryApiService->findWorkflowRunByDisplayTitle(
                $this->githubImagesRepo,
                $this->githubImagesWorkflowFile,
                "Update images ({$correlationId})",
            );

            if (null === $run) {
                return [];
            }

            return [
                'workflowARunId' => $run['id'],
                'workflowAStatus' => $run['status'],
                'workflowAConclusion' => $run['conclusion'] ?? '',
                'workflowAUrl' => $run['htmlUrl'],
            ];
        }

        if ('success' === $latest['workflow_a_conclusion'] && 'merged' !== $latest['icon_pr_state']) {
            $pr = $this->githubQueryApiService->findPullRequestByBranch(
                $this->githubImagesRepo,
                "update-images-{$latest['workflow_a_run_id']}",
            );

            if (null === $pr) {
                return [];
            }

            $updates = [
                'iconPrNumber' => $pr['number'],
                'iconPrUrl' => $pr['htmlUrl'],
                'iconPrState' => $pr['state'],
            ];

            if (null !== $pr['mergeCommitSha']) {
                $updates['iconPrMergeCommitSha'] = $pr['mergeCommitSha'];
            }

            return $updates;
        }

        if (null !== $latest['icon_pr_merge_commit_sha'] && null === $latest['workflow_b_conclusion']) {
            /** @var string $mergeCommitSha */
            $mergeCommitSha = $latest['icon_pr_merge_commit_sha'];

            $run = $this->githubQueryApiService->findWorkflowRunByHeadSha(
                $this->githubImagesRepo,
                $this->githubImagesWorkflowBFile,
                $mergeCommitSha,
            );

            if (null === $run) {
                return [];
            }

            return [
                'workflowBRunId' => $run['id'],
                'workflowBStatus' => $run['status'],
                'workflowBConclusion' => $run['conclusion'] ?? '',
                'workflowBUrl' => $run['htmlUrl'],
            ];
        }

        if ('success' === $latest['workflow_b_conclusion']) {
            /** @var string $mergeCommitSha */
            $mergeCommitSha = $latest['icon_pr_merge_commit_sha'];

            $pr = $this->githubQueryApiService->findPullRequestByBranch(
                $this->githubResourcesRepo,
                "sync-images-{$mergeCommitSha}",
            );

            if (null === $pr) {
                return [];
            }

            return [
                'resourcesPrNumber' => $pr['number'],
                'resourcesPrUrl' => $pr['htmlUrl'],
                'resourcesPrState' => $pr['state'],
            ];
        }

        return [];
    }
}
