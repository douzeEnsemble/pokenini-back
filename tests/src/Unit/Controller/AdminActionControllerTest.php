<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller;

use App\Controller\AdminActionController;
use App\Service\Api\AdminActionService;
use App\Service\CacheInvalidatorService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @internal
 */
#[CoversClass(AdminActionController::class)]
class AdminActionControllerTest extends TestCase
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

        $logger = $this->createMock(LoggerInterface::class);

        $controller = new AdminActionController(
            $cacheInvalidatorService,
            $adminActionService,
            $logger
        );

        $response = $controller->invalidate('something');

        $this->assertSame(202, $response->getStatusCode());
    }

    public function testFailUpdateLogs(): void
    {
        $controller = $this->assertFailActionLogs('update');

        $controller->update('something');
    }

    public function testFailCalculateLogs(): void
    {
        $controller = $this->assertFailActionLogs('calculate');

        $controller->calculate('something');
    }

    private function assertFailActionLogs(string $action): AdminActionController
    {
        $cacheInvalidatorService = $this->createMock(CacheInvalidatorService::class);

        $adminActionService = $this->createMock(AdminActionService::class);
        $adminActionService
            ->expects($this->once())
            ->method($action)
            ->willThrowException(new \Exception('Aouch'))
        ;

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->once())
            ->method('critical')
            ->with(
                $this->equalTo('Aouch'),
                $this->equalTo([
                    'name' => 'something',
                    'action' => $action,
                ])
            )
        ;

        return new AdminActionController(
            $cacheInvalidatorService,
            $adminActionService,
            $logger
        );
    }
}
