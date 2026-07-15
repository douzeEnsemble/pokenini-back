<?php

declare(strict_types=1);

namespace App\DTO;

final class ImagePipelineStatus
{
    /**
     * See ImagePipelineStageStatus::__construct()'s docblock - same
     * verified coverage-attribution artifact, not a real gap.
     *
     * @codeCoverageIgnore
     */
    public function __construct(
        public readonly string $correlationId,
        public readonly ImagePipelineStageStatus $workflowA,
        public readonly ImagePipelineStageStatus $iconPr,
        public readonly ImagePipelineStageStatus $workflowB,
        public readonly ImagePipelineStageStatus $resourcesPr,
    ) {}
}
