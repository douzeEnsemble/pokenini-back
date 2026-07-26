<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\Serializer\Attribute\SerializedName;

final class ImagePipelineStatus
{
    /**
     * See ImagePipelineStageStatus::__construct()'s docblock - same
     * verified coverage-attribution artifact, not a real gap.
     *
     * @codeCoverageIgnore
     */
    public function __construct(
        #[SerializedName('correlation_id')]
        public readonly string $correlationId,
        #[SerializedName('workflow_a')]
        public readonly ImagePipelineStageStatus $workflowA,
        #[SerializedName('icon_pr')]
        public readonly ImagePipelineStageStatus $iconPr,
        #[SerializedName('workflow_b')]
        public readonly ImagePipelineStageStatus $workflowB,
        #[SerializedName('resources_pr')]
        public readonly ImagePipelineStageStatus $resourcesPr,
    ) {}
}
