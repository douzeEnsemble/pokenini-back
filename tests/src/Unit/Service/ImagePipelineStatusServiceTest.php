<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\Api\GithubQueryApiService;
use App\Service\Api\ImagePipelineApiService;
use App\Service\ImagePipelineStatusService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(ImagePipelineStatusService::class)]
final class ImagePipelineStatusServiceTest extends TestCase
{
    public function testReturnsNullWhenNoRunExists(): void
    {
        $imagePipelineApiService = $this->createMock(ImagePipelineApiService::class);
        $imagePipelineApiService->method('getLatest')->willReturn(null);

        $service = $this->getService($imagePipelineApiService, $this->createMock(GithubQueryApiService::class));

        $this->assertNull($service->getStatus(refresh: false));
    }

    public function testWithoutRefreshReturnsLatestUnchanged(): void
    {
        $latest = ['correlation_id' => 'corr-1', 'workflow_a_conclusion' => null];

        $imagePipelineApiService = $this->createMock(ImagePipelineApiService::class);
        $imagePipelineApiService->method('getLatest')->willReturn($latest);
        $imagePipelineApiService->expects($this->never())->method('updateFields');

        $githubQueryApiService = $this->createMock(GithubQueryApiService::class);
        $githubQueryApiService->expects($this->never())->method('findWorkflowRunByDisplayTitle');

        $service = $this->getService($imagePipelineApiService, $githubQueryApiService);

        $this->assertSame($latest, $service->getStatus(refresh: false));
    }

    public function testRefreshPollsWorkflowAWhenNotYetConcluded(): void
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

        $imagePipelineApiService = $this->createMock(ImagePipelineApiService::class);
        $imagePipelineApiService
            ->method('getLatest')
            ->willReturnOnConsecutiveCalls($latest, $refreshed)
        ;
        $imagePipelineApiService
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
            ->with('douzeensemble/pokenini-icon', 'update-images.yml', 'Update images (corr-1)')
            ->willReturn([
                'id' => 2,
                'status' => 'completed',
                'conclusion' => 'success',
                'htmlUrl' => 'https://github.com/x/y/actions/runs/2',
                'headSha' => 'sha2',
            ])
        ;

        $service = $this->getService($imagePipelineApiService, $githubQueryApiService);

        $this->assertSame($refreshed, $service->getStatus(refresh: true));
    }

    public function testRefreshKeepsWorkflowAConclusionNullWhileRunStillInProgress(): void
    {
        $latest = [
            'correlation_id' => 'corr-1',
            'workflow_a_run_id' => null,
            'workflow_a_conclusion' => null,
            'icon_pr_state' => null,
            'icon_pr_merge_commit_sha' => null,
            'workflow_b_conclusion' => null,
        ];
        $afterFirstPoll = array_merge($latest, [
            'workflow_a_run_id' => 2,
            'workflow_a_status' => 'in_progress',
            'workflow_a_url' => 'https://github.com/x/y/actions/runs/2',
        ]);

        $imagePipelineApiService = $this->createMock(ImagePipelineApiService::class);
        $imagePipelineApiService
            ->method('getLatest')
            ->willReturnOnConsecutiveCalls($latest, $afterFirstPoll)
        ;
        $imagePipelineApiService
            ->expects($this->once())
            ->method('updateFields')
            ->with('corr-1', [
                'workflowARunId' => 2,
                'workflowAStatus' => 'in_progress',
                'workflowAUrl' => 'https://github.com/x/y/actions/runs/2',
            ])
        ;

        $githubQueryApiService = $this->createMock(GithubQueryApiService::class);
        $githubQueryApiService
            ->expects($this->once())
            ->method('findWorkflowRunByDisplayTitle')
            ->with('douzeensemble/pokenini-icon', 'update-images.yml', 'Update images (corr-1)')
            ->willReturn([
                'id' => 2,
                'status' => 'in_progress',
                'conclusion' => null,
                'htmlUrl' => 'https://github.com/x/y/actions/runs/2',
                'headSha' => 'sha2',
            ])
        ;

        $service = $this->getService($imagePipelineApiService, $githubQueryApiService);

        $result = $service->getStatus(refresh: true);

        $this->assertSame($afterFirstPoll, $result);
    }

    public function testRefreshKeepsWorkflowBConclusionNullWhileRunStillInProgress(): void
    {
        $latest = [
            'correlation_id' => 'corr-1',
            'workflow_a_run_id' => 2,
            'workflow_a_conclusion' => 'success',
            'icon_pr_state' => 'merged',
            'icon_pr_merge_commit_sha' => 'merge-sha-5',
            'workflow_b_conclusion' => null,
        ];
        $afterFirstPoll = array_merge($latest, [
            'workflow_b_run_id' => 9,
            'workflow_b_status' => 'in_progress',
            'workflow_b_url' => 'https://github.com/x/y/actions/runs/9',
        ]);

        $imagePipelineApiService = $this->createMock(ImagePipelineApiService::class);
        $imagePipelineApiService
            ->method('getLatest')
            ->willReturnOnConsecutiveCalls($latest, $afterFirstPoll)
        ;
        $imagePipelineApiService
            ->expects($this->once())
            ->method('updateFields')
            ->with('corr-1', [
                'workflowBRunId' => 9,
                'workflowBStatus' => 'in_progress',
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
                'status' => 'in_progress',
                'conclusion' => null,
                'htmlUrl' => 'https://github.com/x/y/actions/runs/9',
                'headSha' => 'merge-sha-5',
            ])
        ;

        $service = $this->getService($imagePipelineApiService, $githubQueryApiService);

        $result = $service->getStatus(refresh: true);

        $this->assertSame($afterFirstPoll, $result);
    }

    public function testRefreshDoesNothingWhenPollFindsNothingNew(): void
    {
        $latest = [
            'correlation_id' => 'corr-1',
            'workflow_a_run_id' => null,
            'workflow_a_conclusion' => null,
            'icon_pr_state' => null,
            'icon_pr_merge_commit_sha' => null,
            'workflow_b_conclusion' => null,
        ];

        $imagePipelineApiService = $this->createMock(ImagePipelineApiService::class);
        $imagePipelineApiService->method('getLatest')->willReturn($latest);
        $imagePipelineApiService->expects($this->never())->method('updateFields');

        $githubQueryApiService = $this->createMock(GithubQueryApiService::class);
        $githubQueryApiService->method('findWorkflowRunByDisplayTitle')->willReturn(null);

        $service = $this->getService($imagePipelineApiService, $githubQueryApiService);

        $this->assertSame($latest, $service->getStatus(refresh: true));
    }

    public function testRefreshPollsIconPrWhenWorkflowASucceededButNotYetMerged(): void
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

        $imagePipelineApiService = $this->createMock(ImagePipelineApiService::class);
        $imagePipelineApiService
            ->method('getLatest')
            ->willReturnOnConsecutiveCalls($latest, $refreshed)
        ;
        $imagePipelineApiService
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
            ->with('douzeensemble/pokenini-icon', 'update-images-2')
            ->willReturn([
                'number' => 5,
                'htmlUrl' => 'https://github.com/x/y/pull/5',
                'state' => 'open',
                'mergedAt' => null,
                'mergeCommitSha' => 'merge-sha-5',
            ])
        ;

        $service = $this->getService($imagePipelineApiService, $githubQueryApiService);

        $this->assertSame($refreshed, $service->getStatus(refresh: true));
    }

    public function testRefreshPollsIconPrWithoutMergeCommitShaWhenPrNotYetMerged(): void
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

        $imagePipelineApiService = $this->createMock(ImagePipelineApiService::class);
        $imagePipelineApiService
            ->method('getLatest')
            ->willReturnOnConsecutiveCalls($latest, $refreshed)
        ;
        $imagePipelineApiService
            ->expects($this->once())
            ->method('updateFields')
            ->with('corr-1', [
                'iconPrNumber' => 5,
                'iconPrUrl' => 'https://github.com/x/y/pull/5',
                'iconPrState' => 'open',
            ])
        ;

        $githubQueryApiService = $this->createMock(GithubQueryApiService::class);
        $githubQueryApiService
            ->method('findPullRequestByBranch')
            ->willReturn([
                'number' => 5,
                'htmlUrl' => 'https://github.com/x/y/pull/5',
                'state' => 'open',
                'mergedAt' => null,
                'mergeCommitSha' => null,
            ])
        ;

        $service = $this->getService($imagePipelineApiService, $githubQueryApiService);

        $this->assertSame($refreshed, $service->getStatus(refresh: true));
    }

    public function testRefreshDoesNothingWhenIconPrPollFindsNothingNew(): void
    {
        $latest = [
            'correlation_id' => 'corr-1',
            'workflow_a_run_id' => 2,
            'workflow_a_conclusion' => 'success',
            'icon_pr_state' => null,
            'icon_pr_merge_commit_sha' => null,
            'workflow_b_conclusion' => null,
        ];

        $imagePipelineApiService = $this->createMock(ImagePipelineApiService::class);
        $imagePipelineApiService->method('getLatest')->willReturn($latest);
        $imagePipelineApiService->expects($this->never())->method('updateFields');

        $githubQueryApiService = $this->createMock(GithubQueryApiService::class);
        $githubQueryApiService->method('findPullRequestByBranch')->willReturn(null);

        $service = $this->getService($imagePipelineApiService, $githubQueryApiService);

        $this->assertSame($latest, $service->getStatus(refresh: true));
    }

    public function testRefreshPollsWorkflowBWhenIconPrMergedButNotYetConcluded(): void
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

        $imagePipelineApiService = $this->createMock(ImagePipelineApiService::class);
        $imagePipelineApiService
            ->method('getLatest')
            ->willReturnOnConsecutiveCalls($latest, $refreshed)
        ;
        $imagePipelineApiService
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

        $service = $this->getService($imagePipelineApiService, $githubQueryApiService);

        $this->assertSame($refreshed, $service->getStatus(refresh: true));
    }

    public function testRefreshDoesNothingWhenWorkflowBPollFindsNothingNew(): void
    {
        $latest = [
            'correlation_id' => 'corr-1',
            'workflow_a_run_id' => 2,
            'workflow_a_conclusion' => 'success',
            'icon_pr_state' => 'merged',
            'icon_pr_merge_commit_sha' => 'merge-sha-5',
            'workflow_b_conclusion' => null,
        ];

        $imagePipelineApiService = $this->createMock(ImagePipelineApiService::class);
        $imagePipelineApiService->method('getLatest')->willReturn($latest);
        $imagePipelineApiService->expects($this->never())->method('updateFields');

        $githubQueryApiService = $this->createMock(GithubQueryApiService::class);
        $githubQueryApiService->method('findWorkflowRunByHeadSha')->willReturn(null);

        $service = $this->getService($imagePipelineApiService, $githubQueryApiService);

        $this->assertSame($latest, $service->getStatus(refresh: true));
    }

    public function testRefreshPollsResourcesPrWhenWorkflowBSucceeded(): void
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

        $imagePipelineApiService = $this->createMock(ImagePipelineApiService::class);
        $imagePipelineApiService
            ->method('getLatest')
            ->willReturnOnConsecutiveCalls($latest, $refreshed)
        ;
        $imagePipelineApiService
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
            ->with('douzeensemble/pokenini-resources', 'sync-images-merge-sha-5')
            ->willReturn([
                'number' => 11,
                'htmlUrl' => 'https://github.com/x/z/pull/11',
                'state' => 'open',
                'mergedAt' => null,
                'mergeCommitSha' => null,
            ])
        ;

        $service = $this->getService($imagePipelineApiService, $githubQueryApiService);

        $this->assertSame($refreshed, $service->getStatus(refresh: true));
    }

    public function testRefreshDoesNothingWhenResourcesPrPollFindsNothingNew(): void
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

        $imagePipelineApiService = $this->createMock(ImagePipelineApiService::class);
        $imagePipelineApiService->method('getLatest')->willReturn($latest);
        $imagePipelineApiService->expects($this->never())->method('updateFields');

        $githubQueryApiService = $this->createMock(GithubQueryApiService::class);
        $githubQueryApiService->method('findPullRequestByBranch')->willReturn(null);

        $service = $this->getService($imagePipelineApiService, $githubQueryApiService);

        $this->assertSame($latest, $service->getStatus(refresh: true));
    }

    public function testRefreshDoesNothingWhenWorkflowAAlreadyFailed(): void
    {
        $latest = [
            'correlation_id' => 'corr-1',
            'workflow_a_run_id' => 2,
            'workflow_a_conclusion' => 'failure',
            'icon_pr_state' => null,
            'icon_pr_merge_commit_sha' => null,
            'workflow_b_conclusion' => null,
        ];

        $imagePipelineApiService = $this->createMock(ImagePipelineApiService::class);
        $imagePipelineApiService->method('getLatest')->willReturn($latest);
        $imagePipelineApiService->expects($this->never())->method('updateFields');

        $githubQueryApiService = $this->createMock(GithubQueryApiService::class);
        $githubQueryApiService->expects($this->never())->method('findPullRequestByBranch');
        $githubQueryApiService->expects($this->never())->method('findWorkflowRunByHeadSha');
        $githubQueryApiService->expects($this->never())->method('findWorkflowRunByDisplayTitle');

        $service = $this->getService($imagePipelineApiService, $githubQueryApiService);

        $this->assertSame($latest, $service->getStatus(refresh: true));
    }

    private function getService(
        ImagePipelineApiService $imagePipelineApiService,
        GithubQueryApiService $githubQueryApiService,
    ): ImagePipelineStatusService {
        return new ImagePipelineStatusService(
            $imagePipelineApiService,
            $githubQueryApiService,
            'douzeensemble/pokenini-icon',
            'update-images.yml',
            'publish-images-to-resources.yml',
            'douzeensemble/pokenini-resources',
        );
    }
}
