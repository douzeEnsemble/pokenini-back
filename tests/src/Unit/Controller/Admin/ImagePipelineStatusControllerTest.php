<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller\Admin;

use App\Controller\Admin\ImagePipelineStatusController;
use App\Service\ImagePipelineStatusService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
#[CoversClass(ImagePipelineStatusController::class)]
final class ImagePipelineStatusControllerTest extends TestCase
{
    public function testNoRunReturnsEmptyObject(): void
    {
        $service = $this->createMock(ImagePipelineStatusService::class);
        $service->expects($this->once())->method('getStatus')->with(false)->willReturn(null);

        $controller = new ImagePipelineStatusController($service);

        $response = $controller->get(new Request());

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('{}', $response->getContent());
    }

    public function testIdleStagesWhenNothingKnownYet(): void
    {
        $service = $this->createMock(ImagePipelineStatusService::class);
        $service->expects($this->once())->method('getStatus')->with(false)->willReturn([
            'correlation_id' => 'corr-1',
            'workflow_a_status' => null,
            'workflow_a_conclusion' => null,
            'workflow_a_url' => null,
            'icon_pr_state' => null,
            'icon_pr_url' => null,
            'workflow_b_status' => null,
            'workflow_b_conclusion' => null,
            'workflow_b_url' => null,
            'resources_pr_state' => null,
            'resources_pr_url' => null,
        ]);

        $controller = new ImagePipelineStatusController($service);

        $response = $controller->get(new Request());

        /**
         * @var array{
         *     correlationId: string,
         *     workflowA: array{state: string},
         *     iconPr: array{state: string},
         * }
         */
        $data = json_decode((string) $response->getContent(), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame('corr-1', $data['correlationId']);
        $this->assertSame('idle', $data['workflowA']['state']);
        $this->assertSame('idle', $data['iconPr']['state']);
    }

    public function testRefreshQueryParamIsForwarded(): void
    {
        $service = $this->createMock(ImagePipelineStatusService::class);
        $service->expects($this->once())->method('getStatus')->with(true)->willReturn(null);

        $controller = new ImagePipelineStatusController($service);

        $controller->get(new Request(query: ['refresh' => '1']));
    }

    public function testRunningAndFailedStates(): void
    {
        $service = $this->createMock(ImagePipelineStatusService::class);
        $service->method('getStatus')->willReturn([
            'correlation_id' => 'corr-1',
            'workflow_a_status' => 'in_progress',
            'workflow_a_conclusion' => null,
            'workflow_a_url' => 'https://github.com/x/y/actions/runs/1',
            'icon_pr_state' => null,
            'icon_pr_url' => null,
            'workflow_b_status' => 'completed',
            'workflow_b_conclusion' => 'failure',
            'workflow_b_url' => 'https://github.com/x/y/actions/runs/2',
            'resources_pr_state' => null,
            'resources_pr_url' => null,
        ]);

        $controller = new ImagePipelineStatusController($service);

        $response = $controller->get(new Request());

        /**
         * @var array{
         *     workflowA: array{state: string},
         *     workflowB: array{state: string},
         * }
         */
        $data = json_decode((string) $response->getContent(), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame('running', $data['workflowA']['state']);
        $this->assertSame('failed', $data['workflowB']['state']);
    }

    public function testDoneAndPrPassthroughStates(): void
    {
        $service = $this->createMock(ImagePipelineStatusService::class);
        $service->method('getStatus')->willReturn([
            'correlation_id' => 'corr-1',
            'workflow_a_status' => 'completed',
            'workflow_a_conclusion' => 'success',
            'workflow_a_url' => 'https://github.com/x/y/actions/runs/1',
            'icon_pr_state' => 'merged',
            'icon_pr_url' => 'https://github.com/x/y/pull/1',
            'workflow_b_status' => null,
            'workflow_b_conclusion' => null,
            'workflow_b_url' => null,
            'resources_pr_state' => 'open',
            'resources_pr_url' => 'https://github.com/x/z/pull/2',
        ]);

        $controller = new ImagePipelineStatusController($service);

        $response = $controller->get(new Request());

        /**
         * @var array{
         *     workflowA: array{state: string},
         *     iconPr: array{state: string},
         *     resourcesPr: array{state: string},
         * }
         */
        $data = json_decode((string) $response->getContent(), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame('done', $data['workflowA']['state']);
        $this->assertSame('merged', $data['iconPr']['state']);
        $this->assertSame('open', $data['resourcesPr']['state']);
    }
}
