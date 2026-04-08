<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Security\UserTokenService;
use App\Service\Api\GetElectionTopApiService;
use App\Service\GetElectionTopService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(GetElectionTopService::class)]
final class GetElectionTopServiceTest extends TestCase
{
    public function testGetTop(): void
    {
        $userTokenService = $this->createMock(UserTokenService::class);
        $userTokenService
            ->expects($this->once())
            ->method('getLoggedUserToken')
            ->willReturn('8800088')
        ;

        $apiService = $this->createMock(GetElectionTopApiService::class);
        $apiService
            ->expects($this->once())
            ->method('getTop')
            ->with(
                '8800088',
                'demo',
                'whatever',
                12,
            )
            ->willReturn(['some', 'data'])
        ;

        $service = new GetElectionTopService($userTokenService, $apiService, 12);

        $this->assertSame(
            ['some', 'data'],
            $service->getTop('demo', 'whatever'),
        );
    }
}
