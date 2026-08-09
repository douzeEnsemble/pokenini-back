<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller\Admin;

use App\Controller\Admin\AbstractAdminActionController;
use App\Controller\Admin\AdminActionCalculateController;
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
#[CoversClass(AdminActionCalculateController::class)]
final class AdminActionCalculateControllerTest extends TestCase
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
            ->method('calculate')
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
            ->with('Admin action succeeded: calculate something')
        ;

        $controller = new AdminActionCalculateController(
            $cacheInvalidatorService,
            $adminActionService,
            $logger
        );

        $response = $controller->process('something');

        $this->assertSame(202, $response->getStatusCode());
    }

    #[Test]
    public function failCalculateLogs(): void
    {
        $cacheInvalidatorService = $this->createMock(CacheInvalidatorService::class);
        $cacheInvalidatorService
            ->expects($this->never())
            ->method('invalidate')
        ;

        $adminActionService = $this->createMock(AdminActionApiService::class);
        $adminActionService
            ->expects($this->once())
            ->method('calculate')
            ->willThrowException(new \RuntimeException('Aouch'))
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
                    'action' => 'calculate',
                ])
            )
        ;

        $controller = new AdminActionCalculateController(
            $cacheInvalidatorService,
            $adminActionService,
            $logger
        );

        $controller->process('something');
    }
}
