<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller\Admin;

use App\Controller\Admin\AdminActionTriggerController;
use App\Service\TriggerImagesPipelineService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @internal
 */
#[CoversClass(AdminActionTriggerController::class)]
final class AdminActionTriggerControllerTest extends TestCase
{
    #[Test]
    public function action(): void
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

    #[Test]
    public function failTriggerLogs(): void
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

    #[Test]
    public function failTriggerLogsWithInvalidArgumentException(): void
    {
        $triggerImagesPipelineService = $this->createMock(TriggerImagesPipelineService::class);
        $triggerImagesPipelineService
            ->expects($this->once())
            ->method('triggerUpdateImages')
            ->willThrowException(new \InvalidArgumentException('Aouch'))
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
