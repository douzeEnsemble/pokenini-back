<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller\Admin;

use App\Controller\Admin\AbstractAdminActionController;
use App\Controller\Admin\AdminActionUpdateController;
use App\Service\Api\AdminActionApiService;
use App\Service\CacheInvalidatorService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @internal
 */
#[CoversClass(AbstractAdminActionController::class)]
#[CoversClass(AdminActionUpdateController::class)]
final class AdminActionUpdateControllerTest extends TestCase
{
    #[Test]
    public function action(): void
    {
        $cacheInvalidatorService = $this->createMock(CacheInvalidatorService::class);
        $cacheInvalidatorService
            ->expects($this->once())
            ->method('invalidate')
            ->with('something')
        ;

        $adminActionService = $this->createMock(AdminActionApiService::class);
        $adminActionService
            ->expects($this->once())
            ->method('update')
            ->with('something')
        ;

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->never())
            ->method('critical')
        ;
        $logger
            ->expects($this->once())
            ->method('info')
            ->with('Admin action succeeded: update something')
        ;

        $controller = new AdminActionUpdateController(
            $cacheInvalidatorService,
            $adminActionService,
            $logger
        );

        $response = $controller->process('something');

        $this->assertSame(202, $response->getStatusCode());
    }

    #[Test]
    public function failUpdateLogs(): void
    {
        $cacheInvalidatorService = $this->createMock(CacheInvalidatorService::class);
        $cacheInvalidatorService
            ->expects($this->never())
            ->method('invalidate')
        ;

        $adminActionService = $this->createMock(AdminActionApiService::class);
        $adminActionService
            ->expects($this->never())
            ->method('calculate')
        ;
        $adminActionService
            ->expects($this->once())
            ->method('update')
            ->willThrowException(new \RuntimeException('Aouch'))
        ;

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->once())
            ->method('critical')
            ->with(
                $this->equalTo('Aouch'),
                $this->equalTo([
                    'name' => 'something',
                    'action' => 'update',
                ])
            )
        ;

        $controller = new AdminActionUpdateController(
            $cacheInvalidatorService,
            $adminActionService,
            $logger
        );

        $controller->process('something');
    }
}
