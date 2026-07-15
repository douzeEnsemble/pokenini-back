<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\Api\GithubActionsApiService;
use App\Service\Api\ImagePipelineApiService;
use App\Service\TriggerImagesPipelineService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @internal
 */
#[CoversClass(TriggerImagesPipelineService::class)]
final class TriggerImagesPipelineServiceTest extends TestCase
{
    public function testTriggerUpdateImagesDispatchesAndRegistersRun(): void
    {
        $githubActionsApiService = $this->createMock(GithubActionsApiService::class);
        $githubActionsApiService
            ->expects($this->once())
            ->method('dispatchWorkflow')
            ->with($this->matchesRegularExpression('/^[0-9a-f-]{36}$/'))
        ;

        $imagePipelineApiService = $this->createMock(ImagePipelineApiService::class);
        $imagePipelineApiService
            ->expects($this->once())
            ->method('create')
            ->with($this->matchesRegularExpression('/^[0-9a-f-]{36}$/'))
        ;

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->never())->method('critical');

        $service = new TriggerImagesPipelineService($githubActionsApiService, $imagePipelineApiService, $logger);

        $service->triggerUpdateImages();
    }

    public function testRegistrationFailureIsLoggedButDoesNotThrow(): void
    {
        $githubActionsApiService = $this->createMock(GithubActionsApiService::class);

        $imagePipelineApiService = $this->createMock(ImagePipelineApiService::class);
        $imagePipelineApiService
            ->method('create')
            ->willThrowException(new \RuntimeException('pokenini-api unreachable'))
        ;

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->once())
            ->method('critical')
            ->with('pokenini-api unreachable')
        ;

        $service = new TriggerImagesPipelineService($githubActionsApiService, $imagePipelineApiService, $logger);

        $service->triggerUpdateImages();
    }
}
