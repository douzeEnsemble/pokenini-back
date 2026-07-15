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
