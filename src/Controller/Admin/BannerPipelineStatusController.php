<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\DTO\BannerPipelineStageStatus;
use App\DTO\BannerPipelineStatus;
use App\Service\BannerPipelineStatusService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/istration/action/trigger/update_banners')]
final class BannerPipelineStatusController extends AbstractController
{
    public function __construct(
        private readonly BannerPipelineStatusService $service,
        private readonly SerializerInterface $serializer,
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

        /** @var ?string $workflowAStatus */
        $workflowAStatus = $latest['workflow_a_status'] ?? null;

        /** @var ?string $workflowAConclusion */
        $workflowAConclusion = $latest['workflow_a_conclusion'] ?? null;

        /** @var ?string $workflowAUrl */
        $workflowAUrl = $latest['workflow_a_url'] ?? null;

        /** @var ?string $iconPrState */
        $iconPrState = $latest['icon_pr_state'] ?? null;

        /** @var ?string $iconPrUrl */
        $iconPrUrl = $latest['icon_pr_url'] ?? null;

        /** @var ?string $workflowBStatus */
        $workflowBStatus = $latest['workflow_b_status'] ?? null;

        /** @var ?string $workflowBConclusion */
        $workflowBConclusion = $latest['workflow_b_conclusion'] ?? null;

        /** @var ?string $workflowBUrl */
        $workflowBUrl = $latest['workflow_b_url'] ?? null;

        /** @var ?string $resourcesPrState */
        $resourcesPrState = $latest['resources_pr_state'] ?? null;

        /** @var ?string $resourcesPrUrl */
        $resourcesPrUrl = $latest['resources_pr_url'] ?? null;

        $status = new BannerPipelineStatus(
            correlationId: $correlationId,
            workflowA: $this->runStage($workflowAStatus, $workflowAConclusion, $workflowAUrl),
            iconPr: $this->prStage($iconPrState, $iconPrUrl),
            workflowB: $this->runStage($workflowBStatus, $workflowBConclusion, $workflowBUrl),
            resourcesPr: $this->prStage($resourcesPrState, $resourcesPrUrl),
        );

        return JsonResponse::fromJsonString($this->serializer->serialize($status, 'json'));
    }

    private function runStage(?string $status, ?string $conclusion, ?string $url): BannerPipelineStageStatus
    {
        $state = match (true) {
            null === $status => 'idle',
            'completed' === $status && 'success' === $conclusion => 'done',
            'completed' === $status => 'failed',
            default => 'running',
        };

        return new BannerPipelineStageStatus($state, $url);
    }

    private function prStage(?string $state, ?string $url): BannerPipelineStageStatus
    {
        return new BannerPipelineStageStatus($state ?? 'idle', $url);
    }
}
