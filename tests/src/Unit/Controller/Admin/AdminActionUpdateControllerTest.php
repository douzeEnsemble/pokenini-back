<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller\Admin;

use App\Controller\Admin\AbstractAdminActionController;
use App\Controller\Admin\AdminActionUpdateController;
use App\Exception\ActionAlreadyRunningException;
use App\Service\Api\AdminActionApiService;
use App\Service\Api\GetActionCatalogApiService;
use App\Service\CacheInvalidatorService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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

        $catalog = $this->createStub(GetActionCatalogApiService::class);
        $catalog->method('has')->willReturn(true);

        $controller = new AdminActionUpdateController(
            $cacheInvalidatorService,
            $adminActionService,
            $logger,
            $catalog
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

        $catalog = $this->createStub(GetActionCatalogApiService::class);
        $catalog->method('has')->willReturn(true);

        $controller = new AdminActionUpdateController(
            $cacheInvalidatorService,
            $adminActionService,
            $logger,
            $catalog
        );

        $controller->process('something');
    }

    #[Test]
    public function unknownActionIsNotFound(): void
    {
        $adminActionService = $this->createMock(AdminActionApiService::class);
        $adminActionService
            ->expects($this->never())
            ->method('update')
        ;

        $catalog = $this->createMock(GetActionCatalogApiService::class);
        $catalog
            ->expects($this->once())
            ->method('has')
            ->with('update', 'truc')
            ->willReturn(false)
        ;

        $controller = new AdminActionUpdateController(
            $this->createMock(CacheInvalidatorService::class),
            $adminActionService,
            $this->createMock(LoggerInterface::class),
            $catalog
        );
        $controller->setContainer(new Container());

        $this->expectException(NotFoundHttpException::class);

        $controller->process('truc');
    }

    #[Test]
    public function alreadyRunningIsAConflict(): void
    {
        $cacheInvalidatorService = $this->createMock(CacheInvalidatorService::class);
        $cacheInvalidatorService
            ->expects($this->never())
            ->method('invalidate')
        ;

        $adminActionService = $this->createMock(AdminActionApiService::class);
        $adminActionService
            ->expects($this->once())
            ->method('update')
            ->willThrowException(new ActionAlreadyRunningException())
        ;

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->never())
            ->method('critical')
        ;
        $logger
            ->expects($this->once())
            ->method('warning')
        ;

        $catalog = $this->createStub(GetActionCatalogApiService::class);
        $catalog->method('has')->willReturn(true);

        $controller = new AdminActionUpdateController(
            $cacheInvalidatorService,
            $adminActionService,
            $logger,
            $catalog
        );

        $response = $controller->process('something');

        $this->assertSame(409, $response->getStatusCode());
        $this->assertStringContainsString('admin.action.already_running', (string) $response->getContent());
    }
}
