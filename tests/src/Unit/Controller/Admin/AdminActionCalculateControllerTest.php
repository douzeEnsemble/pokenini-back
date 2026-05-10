<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller\Admin;

use App\Controller\Admin\AbstractAdminActionController;
use App\Controller\Admin\AdminActionCalculateController;
use App\Service\Api\AdminActionApiService;
use App\Service\CacheInvalidatorService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @internal
 */
#[CoversClass(AbstractAdminActionController::class)]
#[CoversClass(AdminActionCalculateController::class)]
final class AdminActionCalculateControllerTest extends TestCase
{
    public function testAction(): void
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

        $controller = new AdminActionCalculateController(
            $cacheInvalidatorService,
            $adminActionService,
            $logger
        );

        $response = $controller->process('something');

        $this->assertSame(202, $response->getStatusCode());
    }

    public function testFailCalculateLogs(): void
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
