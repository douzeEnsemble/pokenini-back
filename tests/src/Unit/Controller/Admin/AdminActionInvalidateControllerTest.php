<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller\Admin;

use App\Controller\Admin\AbstractAdminActionController;
use App\Controller\Admin\AdminActionInvalidateController;
use App\Service\Api\AdminActionService;
use App\Service\CacheInvalidatorService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @internal
 */
#[CoversClass(AbstractAdminActionController::class)]
#[CoversClass(AdminActionInvalidateController::class)]
class AdminActionInvalidateControllerTest extends TestCase
{
    public function testAction(): void
    {
        $cacheInvalidatorService = $this->createMock(CacheInvalidatorService::class);
        $cacheInvalidatorService
            ->expects($this->once())
            ->method('invalidate')
            ->with('something')
        ;

        $adminActionService = $this->createMock(AdminActionService::class);
        $adminActionService
            ->expects($this->never())
            ->method('calculate')
        ;
        $adminActionService
            ->expects($this->never())
            ->method('update')
        ;

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->never())
            ->method('critical')
        ;

        $controller = new AdminActionInvalidateController(
            $cacheInvalidatorService,
            $adminActionService,
            $logger
        );

        $response = $controller->process('something');

        $this->assertSame(202, $response->getStatusCode());
    }

    public function testFailInvalidateLogs(): void
    {
        $cacheInvalidatorService = $this->createMock(CacheInvalidatorService::class);
        $cacheInvalidatorService
            ->expects($this->once())
            ->method('invalidate')
            ->willThrowException(new \Exception('Aouch'))
        ;

        $adminActionService = $this->createMock(AdminActionService::class);
        $adminActionService
            ->expects($this->never())
            ->method('calculate')
        ;
        $adminActionService
            ->expects($this->never())
            ->method('update')
        ;

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->once())
            ->method('critical')
            ->with(
                $this->equalTo('Aouch'),
                $this->equalTo([
                    'name' => 'something',
                    'action' => 'invalidate',
                ])
            )
        ;

        $controller = new AdminActionInvalidateController(
            $cacheInvalidatorService,
            $adminActionService,
            $logger
        );

        $controller->process('something');
    }
}
