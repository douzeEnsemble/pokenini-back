<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\DTO\ImagePipelineStageStatus;
use App\DTO\ImagePipelineStatus;
use App\Service\ImagePipelineStatusService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/istration/action/trigger/update_images')]
final class ImagePipelineStatusController extends AbstractController
{
    public function __construct(
        private readonly ImagePipelineStatusService $service,
    ) {}

    #[Route('/status', methods: ['GET'])]
    public function get(Request $request): JsonResponse
    {
        $latest = $this->service->getStatus($request->query->has('refresh'));

        if (null === $latest) {
            return new JsonResponse(new \stdClass());
        }

        /** @var string $correlationId */
        $correlationId = $latest['correlation_id'];

        $status = new ImagePipelineStatus(
            correlationId: $correlationId,
            workflowA: $this->runStage($latest['workflow_a_status'] ?? null, $latest['workflow_a_conclusion'] ?? null, $latest['workflow_a_url'] ?? null),
            iconPr: $this->prStage($latest['icon_pr_state'] ?? null, $latest['icon_pr_url'] ?? null),
            workflowB: $this->runStage($latest['workflow_b_status'] ?? null, $latest['workflow_b_conclusion'] ?? null, $latest['workflow_b_url'] ?? null),
            resourcesPr: $this->prStage($latest['resources_pr_state'] ?? null, $latest['resources_pr_url'] ?? null),
        );

        return new JsonResponse($status);
    }

    private function runStage(?string $status, ?string $conclusion, ?string $url): ImagePipelineStageStatus
    {
        $state = match (true) {
            null === $status => 'idle',
            'completed' === $status && 'success' === $conclusion => 'done',
            'completed' === $status => 'failed',
            default => 'running',
        };

        return new ImagePipelineStageStatus($state, $url);
    }

    private function prStage(?string $state, ?string $url): ImagePipelineStageStatus
    {
        return new ImagePipelineStageStatus($state ?? 'idle', $url);
    }
}
