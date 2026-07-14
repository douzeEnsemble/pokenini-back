<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\Api\GithubActionsApiService;
use App\Service\TriggerImagesPipelineService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(TriggerImagesPipelineService::class)]
final class TriggerImagesPipelineServiceTest extends TestCase
{
    public function testTriggerUpdateImages(): void
    {
        $githubActionsApiService = $this->createMock(GithubActionsApiService::class);
        $githubActionsApiService
            ->expects($this->once())
            ->method('dispatchWorkflow')
        ;

        $service = new TriggerImagesPipelineService($githubActionsApiService);

        $service->triggerUpdateImages();
    }
}
