<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Security\UserTokenService;
use App\Service\Api\GetDexListApiService;
use App\Service\FindDexBySlugService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(FindDexBySlugService::class)]
final class FindDexBySlugServiceTest extends TestCase
{
    #[Test]
    public function findReturnsTheMatchingDex(): void
    {
        $getDexListService = $this->createMock(GetDexListApiService::class);
        $getDexListService
            ->expects($this->once())
            ->method('getWithUnreleasedAndPremium')
            ->with('1234567890')
            ->willReturn([
                ['slug' => 'redgreenblueyellow', 'flags' => ['is_premium' => false]],
                ['slug' => 'goldsilvercrystal', 'flags' => ['is_premium' => true]],
            ])
        ;

        $userTokenService = $this->createMock(UserTokenService::class);
        $userTokenService
            ->expects($this->once())
            ->method('getLoggedUserToken')
            ->willReturn('1234567890')
        ;

        $service = new FindDexBySlugService($getDexListService, $userTokenService);

        $dex = $service->find('goldsilvercrystal');

        $this->assertSame(['slug' => 'goldsilvercrystal', 'flags' => ['is_premium' => true]], $dex);
    }

    #[Test]
    public function findReturnsNullWhenSlugIsUnknown(): void
    {
        $getDexListService = $this->createMock(GetDexListApiService::class);
        $getDexListService
            ->expects($this->once())
            ->method('getWithUnreleasedAndPremium')
            ->with('1234567890')
            ->willReturn([
                ['slug' => 'redgreenblueyellow', 'flags' => ['is_premium' => false]],
            ])
        ;

        $userTokenService = $this->createMock(UserTokenService::class);
        $userTokenService
            ->expects($this->once())
            ->method('getLoggedUserToken')
            ->willReturn('1234567890')
        ;

        $service = new FindDexBySlugService($getDexListService, $userTokenService);

        $dex = $service->find('unknown');

        $this->assertNull($dex);
    }
}
