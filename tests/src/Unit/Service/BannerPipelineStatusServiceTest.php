<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\Api\BannerPipelineApiService;
use App\Service\Api\GithubQueryApiService;
use App\Service\BannerPipelineStatusService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(BannerPipelineStatusService::class)]
final class BannerPipelineStatusServiceTest extends TestCase
{
    #[Test]
    public function returnsNullWhenNoRunExists(): void
    {
        $bannerPipelineApiService = $this->createMock(BannerPipelineApiService::class);
        $bannerPipelineApiService->method('getLatest')->willReturn(null);

        $service = $this->getService($bannerPipelineApiService, $this->createMock(GithubQueryApiService::class));

        $this->assertNull($service->getStatus(refresh: false));
    }

    #[Test]
    public function withoutRefreshReturnsLatestUnchanged(): void
    {
        $latest = ['correlation_id' => 'corr-1', 'workflow_a_conclusion' => null];

        $bannerPipelineApiService = $this->createMock(BannerPipelineApiService::class);
        $bannerPipelineApiService->method('getLatest')->willReturn($latest);
        $bannerPipelineApiService->expects($this->never())->method('updateFields');

        $githubQueryApiService = $this->createMock(GithubQueryApiService::class);
        $githubQueryApiService->expects($this->never())->method('findWorkflowRunByDisplayTitle');

        $service = $this->getService($bannerPipelineApiService, $githubQueryApiService);

        $this->assertSame($latest, $service->getStatus(refresh: false));
    }

    #[Test]
    public function refreshPollsWorkflowAWhenNotYetConcluded(): void
    {
        $latest = [
            'correlation_id' => 'corr-1',
            'workflow_a_run_id' => null,
            'workflow_a_conclusion' => null,
            'icon_pr_state' => null,
            'icon_pr_merge_commit_sha' => null,
            'workflow_b_conclusion' => null,
        ];
        $refreshed = array_merge($latest, ['workflow_a_conclusion' => 'success']);

        $bannerPipelineApiService = $this->createMock(BannerPipelineApiService::class);
        $bannerPipelineApiService
            ->method('getLatest')
            ->willReturnOnConsecutiveCalls($latest, $refreshed)
        ;
        $bannerPipelineApiService
            ->expects($this->once())
            ->method('updateFields')
            ->with('corr-1', [
                'workflowARunId' => 2,
                'workflowAStatus' => 'completed',
                'workflowAConclusion' => 'success',
                'workflowAUrl' => 'https://github.com/x/y/actions/runs/2',
            ])
        ;

        $githubQueryApiService = $this->createMock(GithubQueryApiService::class);
        $githubQueryApiService
            ->expects($this->once())
            ->method('findWorkflowRunByDisplayTitle')
            ->with('douzeensemble/pokenini-icon', 'update-banners.yml', 'Update banners (corr-1)')
            ->willReturn([
                'id' => 2,
                'status' => 'completed',
                'conclusion' => 'success',
                'htmlUrl' => 'https://github.com/x/y/actions/runs/2',
                'headSha' => 'sha2',
            ])
        ;

        $service = $this->getService($bannerPipelineApiService, $githubQueryApiService);

        $this->assertSame($refreshed, $service->getStatus(refresh: true));
    }

    #[Test]
    public function refreshPollsIconPrWhenWorkflowASucceededButNotYetMerged(): void
    {
        $latest = [
            'correlation_id' => 'corr-1',
            'workflow_a_run_id' => 2,
            'workflow_a_conclusion' => 'success',
            'icon_pr_state' => null,
            'icon_pr_merge_commit_sha' => null,
            'workflow_b_conclusion' => null,
        ];
        $refreshed = array_merge($latest, ['icon_pr_state' => 'open']);

        $bannerPipelineApiService = $this->createMock(BannerPipelineApiService::class);
        $bannerPipelineApiService
            ->method('getLatest')
            ->willReturnOnConsecutiveCalls($latest, $refreshed)
        ;
        $bannerPipelineApiService
            ->expects($this->once())
            ->method('updateFields')
            ->with('corr-1', [
                'iconPrNumber' => 5,
                'iconPrUrl' => 'https://github.com/x/y/pull/5',
                'iconPrState' => 'open',
                'iconPrMergeCommitSha' => 'merge-sha-5',
            ])
        ;

        $githubQueryApiService = $this->createMock(GithubQueryApiService::class);
        $githubQueryApiService
            ->expects($this->once())
            ->method('findPullRequestByBranch')
            ->with('douzeensemble/pokenini-icon', 'update-banners-2')
            ->willReturn([
                'number' => 5,
                'htmlUrl' => 'https://github.com/x/y/pull/5',
                'state' => 'open',
                'mergedAt' => null,
                'mergeCommitSha' => 'merge-sha-5',
            ])
        ;

        $service = $this->getService($bannerPipelineApiService, $githubQueryApiService);

        $this->assertSame($refreshed, $service->getStatus(refresh: true));
    }

    #[Test]
    public function refreshPollsWorkflowBWhenIconPrMergedButNotYetConcluded(): void
    {
        $latest = [
            'correlation_id' => 'corr-1',
            'workflow_a_run_id' => 2,
            'workflow_a_conclusion' => 'success',
            'icon_pr_state' => 'merged',
            'icon_pr_merge_commit_sha' => 'merge-sha-5',
            'workflow_b_conclusion' => null,
        ];
        $refreshed = array_merge($latest, ['workflow_b_conclusion' => 'success']);

        $bannerPipelineApiService = $this->createMock(BannerPipelineApiService::class);
        $bannerPipelineApiService
            ->method('getLatest')
            ->willReturnOnConsecutiveCalls($latest, $refreshed)
        ;
        $bannerPipelineApiService
            ->expects($this->once())
            ->method('updateFields')
            ->with('corr-1', [
                'workflowBRunId' => 9,
                'workflowBStatus' => 'completed',
                'workflowBConclusion' => 'success',
                'workflowBUrl' => 'https://github.com/x/y/actions/runs/9',
            ])
        ;

        $githubQueryApiService = $this->createMock(GithubQueryApiService::class);
        $githubQueryApiService
            ->expects($this->once())
            ->method('findWorkflowRunByHeadSha')
            ->with('douzeensemble/pokenini-icon', 'publish-images-to-resources.yml', 'merge-sha-5')
            ->willReturn([
                'id' => 9,
                'status' => 'completed',
                'conclusion' => 'success',
                'htmlUrl' => 'https://github.com/x/y/actions/runs/9',
                'headSha' => 'merge-sha-5',
            ])
        ;

        $service = $this->getService($bannerPipelineApiService, $githubQueryApiService);

        $this->assertSame($refreshed, $service->getStatus(refresh: true));
    }

    #[Test]
    public function refreshPollsResourcesPrWhenWorkflowBSucceededUsingTheSharedSyncImagesBranchPrefix(): void
    {
        $latest = [
            'correlation_id' => 'corr-1',
            'workflow_a_run_id' => 2,
            'workflow_a_conclusion' => 'success',
            'icon_pr_state' => 'merged',
            'icon_pr_merge_commit_sha' => 'merge-sha-5',
            'workflow_b_conclusion' => 'success',
            'resources_pr_state' => null,
        ];
        $refreshed = array_merge($latest, ['resources_pr_state' => 'open']);

        $bannerPipelineApiService = $this->createMock(BannerPipelineApiService::class);
        $bannerPipelineApiService
            ->method('getLatest')
            ->willReturnOnConsecutiveCalls($latest, $refreshed)
        ;
        $bannerPipelineApiService
            ->expects($this->once())
            ->method('updateFields')
            ->with('corr-1', [
                'resourcesPrNumber' => 11,
                'resourcesPrUrl' => 'https://github.com/x/z/pull/11',
                'resourcesPrState' => 'open',
            ])
        ;

        $githubQueryApiService = $this->createMock(GithubQueryApiService::class);
        $githubQueryApiService
            ->expects($this->once())
            ->method('findPullRequestByBranch')
            // Not "sync-banners-..." - publish-images-to-resources.yml is shared,
            // unmodified, and always names its branch "sync-images-<sha>" no
            // matter which images/** path changed.
            ->with('douzeensemble/pokenini-resources', 'sync-images-merge-sha-5')
            ->willReturn([
                'number' => 11,
                'htmlUrl' => 'https://github.com/x/z/pull/11',
                'state' => 'open',
                'mergedAt' => null,
                'mergeCommitSha' => null,
            ])
        ;

        $service = $this->getService($bannerPipelineApiService, $githubQueryApiService);

        $this->assertSame($refreshed, $service->getStatus(refresh: true));
    }

    #[Test]
    public function refreshDoesNothingWhenWorkflowAAlreadyFailed(): void
    {
        $latest = [
            'correlation_id' => 'corr-1',
            'workflow_a_run_id' => 2,
            'workflow_a_conclusion' => 'failure',
            'icon_pr_state' => null,
            'icon_pr_merge_commit_sha' => null,
            'workflow_b_conclusion' => null,
        ];

        $bannerPipelineApiService = $this->createMock(BannerPipelineApiService::class);
        $bannerPipelineApiService->method('getLatest')->willReturn($latest);
        $bannerPipelineApiService->expects($this->never())->method('updateFields');

        $githubQueryApiService = $this->createMock(GithubQueryApiService::class);
        $githubQueryApiService->expects($this->never())->method('findPullRequestByBranch');
        $githubQueryApiService->expects($this->never())->method('findWorkflowRunByHeadSha');
        $githubQueryApiService->expects($this->never())->method('findWorkflowRunByDisplayTitle');

        $service = $this->getService($bannerPipelineApiService, $githubQueryApiService);

        $this->assertSame($latest, $service->getStatus(refresh: true));
    }

    #[Test]
    public function refreshDoesNothingWhenPollFindsNothingNew(): void
    {
        $latest = [
            'correlation_id' => 'corr-1',
            'workflow_a_run_id' => null,
            'workflow_a_conclusion' => null,
            'icon_pr_state' => null,
            'icon_pr_merge_commit_sha' => null,
            'workflow_b_conclusion' => null,
        ];

        $bannerPipelineApiService = $this->createMock(BannerPipelineApiService::class);
        $bannerPipelineApiService->method('getLatest')->willReturn($latest);
        $bannerPipelineApiService->expects($this->never())->method('updateFields');

        $githubQueryApiService = $this->createMock(GithubQueryApiService::class);
        $githubQueryApiService->method('findWorkflowRunByDisplayTitle')->willReturn(null);

        $service = $this->getService($bannerPipelineApiService, $githubQueryApiService);

        $this->assertSame($latest, $service->getStatus(refresh: true));
    }

    #[Test]
    public function refreshDoesNothingWhenIconPrNotFound(): void
    {
        $latest = [
            'correlation_id' => 'corr-1',
            'workflow_a_run_id' => 2,
            'workflow_a_conclusion' => 'success',
            'icon_pr_state' => null,
            'icon_pr_merge_commit_sha' => null,
            'workflow_b_conclusion' => null,
        ];

        $bannerPipelineApiService = $this->createMock(BannerPipelineApiService::class);
        $bannerPipelineApiService->method('getLatest')->willReturn($latest);
        $bannerPipelineApiService->expects($this->never())->method('updateFields');

        $githubQueryApiService = $this->createMock(GithubQueryApiService::class);
        $githubQueryApiService->method('findPullRequestByBranch')->willReturn(null);

        $service = $this->getService($bannerPipelineApiService, $githubQueryApiService);

        $this->assertSame($latest, $service->getStatus(refresh: true));
    }

    #[Test]
    public function refreshDoesNothingWhenWorkflowBRunNotFound(): void
    {
        $latest = [
            'correlation_id' => 'corr-1',
            'workflow_a_run_id' => 2,
            'workflow_a_conclusion' => 'success',
            'icon_pr_state' => 'merged',
            'icon_pr_merge_commit_sha' => 'merge-sha-5',
            'workflow_b_conclusion' => null,
        ];

        $bannerPipelineApiService = $this->createMock(BannerPipelineApiService::class);
        $bannerPipelineApiService->method('getLatest')->willReturn($latest);
        $bannerPipelineApiService->expects($this->never())->method('updateFields');

        $githubQueryApiService = $this->createMock(GithubQueryApiService::class);
        $githubQueryApiService->method('findWorkflowRunByHeadSha')->willReturn(null);

        $service = $this->getService($bannerPipelineApiService, $githubQueryApiService);

        $this->assertSame($latest, $service->getStatus(refresh: true));
    }

    #[Test]
    public function refreshDoesNothingWhenResourcesPrNotFound(): void
    {
        $latest = [
            'correlation_id' => 'corr-1',
            'workflow_a_run_id' => 2,
            'workflow_a_conclusion' => 'success',
            'icon_pr_state' => 'merged',
            'icon_pr_merge_commit_sha' => 'merge-sha-5',
            'workflow_b_conclusion' => 'success',
        ];

        $bannerPipelineApiService = $this->createMock(BannerPipelineApiService::class);
        $bannerPipelineApiService->method('getLatest')->willReturn($latest);
        $bannerPipelineApiService->expects($this->never())->method('updateFields');

        $githubQueryApiService = $this->createMock(GithubQueryApiService::class);
        $githubQueryApiService->method('findPullRequestByBranch')->willReturn(null);

        $service = $this->getService($bannerPipelineApiService, $githubQueryApiService);

        $this->assertSame($latest, $service->getStatus(refresh: true));
    }

    private function getService(
        BannerPipelineApiService $bannerPipelineApiService,
        GithubQueryApiService $githubQueryApiService,
    ): BannerPipelineStatusService {
        return new BannerPipelineStatusService(
            $bannerPipelineApiService,
            $githubQueryApiService,
            'douzeensemble/pokenini-icon',
            'update-banners.yml',
            'publish-images-to-resources.yml',
            'douzeensemble/pokenini-resources',
        );
    }
}
