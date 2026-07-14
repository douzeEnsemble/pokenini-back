<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller\Admin;

use App\Controller\Admin\AdminActionTriggerController;
use App\Service\TriggerImagesPipelineService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @internal
 */
#[CoversClass(AdminActionTriggerController::class)]
final class AdminActionTriggerControllerTest extends TestCase
{
    public function testAction(): void
    {
        $triggerImagesPipelineService = $this->createMock(TriggerImagesPipelineService::class);
        $triggerImagesPipelineService
            ->expects($this->once())
            ->method('triggerUpdateImages')
        ;

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->never())
            ->method('critical')
        ;
        $logger
            ->expects($this->once())
            ->method('info')
            ->with('Admin action succeeded: trigger update_images')
        ;

        $controller = new AdminActionTriggerController(
            $triggerImagesPipelineService,
            $logger
        );

        $response = $controller->process('update_images');

        $this->assertSame(202, $response->getStatusCode());
    }

    public function testFailTriggerLogs(): void
    {
        $triggerImagesPipelineService = $this->createMock(TriggerImagesPipelineService::class);
        $triggerImagesPipelineService
            ->expects($this->once())
            ->method('triggerUpdateImages')
            ->willThrowException(new \RuntimeException('Aouch'))
        ;

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->once())
            ->method('critical')
            ->with(
                $this->equalTo('Aouch'),
                $this->equalTo([
                    'name' => 'update_images',
                    'action' => 'trigger',
                ])
            )
        ;

        $controller = new AdminActionTriggerController(
            $triggerImagesPipelineService,
            $logger
        );

        $response = $controller->process('update_images');

        $this->assertSame(500, $response->getStatusCode());
    }
}
