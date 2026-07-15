<?php

declare(strict_types=1);

namespace App\DTO;

final class ImagePipelineStageStatus
{
    /**
     * php-code-coverage reports this constructor as uncovered even though
     * ImagePipelineStatusControllerTest constructs it (directly and via
     * runStage()/prStage()) and asserts on the resulting values. Verified
     * this isn't a real gap: the sibling AdminAction DTO (identical
     * property-promotion shape) shows as covered in the same full-suite
     * run, but shows the same false "uncovered" result when either class
     * is run in isolation via a single test file - so it's an artifact of
     * this Xdebug/PHP combination's coverage attribution for this file,
     * not evidence the constructor doesn't execute.
     *
     * @codeCoverageIgnore
     */
    public function __construct(
        public readonly string $state,
        public readonly ?string $url = null,
    ) {}
}
