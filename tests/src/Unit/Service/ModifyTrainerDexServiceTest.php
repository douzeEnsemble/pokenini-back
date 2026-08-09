<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Exception\ModifyFailedException;
use App\Security\UserTokenService;
use App\Service\Api\ModifyDexApiService;
use App\Service\CacheInvalidator\AlbumCacheInvalidatorService;
use App\Service\CacheInvalidator\DexCacheInvalidatorService;
use App\Service\ModifyTrainerDexService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * @internal
 */
#[CoversClass(ModifyTrainerDexService::class)]
final class ModifyTrainerDexServiceTest extends TestCase
{
    #[Test]
    public function modifyDex(): void
    {
        $userTokenService = $this->createMock(UserTokenService::class);
        $userTokenService
            ->expects($this->once())
            ->method('getLoggedUserToken')
            ->willReturn('8800088')
        ;

        $modifyDexService = $this->createMock(ModifyDexApiService::class);
        $modifyDexService
            ->expects($this->once())
            ->method('modify')
            ->with(
                'douze',
                '{"ceci": "est-du-contenu"}',
                '8800088',
            )
        ;

        $albumCacheInvalidatorService = $this->createMock(AlbumCacheInvalidatorService::class);
        $albumCacheInvalidatorService
            ->expects($this->once())
            ->method('invalidate')
            ->with(
                'douze',
                '8800088',
            )
        ;

        $dexCacheInvalidatorService = $this->createMock(DexCacheInvalidatorService::class);
        $dexCacheInvalidatorService
            ->expects($this->once())
            ->method('invalidateByTrainerId')
            ->with(
                '8800088',
            )
        ;

        $service = new ModifyTrainerDexService(
            $userTokenService,
            $modifyDexService,
            $albumCacheInvalidatorService,
            $dexCacheInvalidatorService
        );
        $service->modifyDex('douze', '{"ceci": "est-du-contenu"}');
    }

    #[Test]
    public function modifyDexWithHttpException(): void
    {
        $userTokenService = $this->createMock(UserTokenService::class);
        $userTokenService
            ->expects($this->once())
            ->method('getLoggedUserToken')
            ->willReturn('8800088')
        ;

        $exception = $this->createStub(HttpExceptionInterface::class);

        $modifyDexService = $this->createMock(ModifyDexApiService::class);
        $modifyDexService
            ->expects($this->once())
            ->method('modify')
            ->with(
                'douze',
                '{"ceci": "est-du-contenu"}',
                '8800088',
            )
            ->willThrowException($exception)
        ;

        $albumCacheInvalidatorService = $this->createMock(AlbumCacheInvalidatorService::class);
        $albumCacheInvalidatorService
            ->expects($this->never())
            ->method('invalidate')
        ;

        $dexCacheInvalidatorService = $this->createMock(DexCacheInvalidatorService::class);
        $dexCacheInvalidatorService
            ->expects($this->never())
            ->method('invalidateByTrainerId')
        ;

        $service = new ModifyTrainerDexService(
            $userTokenService,
            $modifyDexService,
            $albumCacheInvalidatorService,
            $dexCacheInvalidatorService
        );

        $this->expectException(ModifyFailedException::class);

        $service->modifyDex('douze', '{"ceci": "est-du-contenu"}');
    }

    #[Test]
    public function modifyDexWithTransportException(): void
    {
        $userTokenService = $this->createMock(UserTokenService::class);
        $userTokenService
            ->expects($this->once())
            ->method('getLoggedUserToken')
            ->willReturn('8800088')
        ;

        $exception = $this->createStub(TransportExceptionInterface::class);

        $modifyDexService = $this->createMock(ModifyDexApiService::class);
        $modifyDexService
            ->expects($this->once())
            ->method('modify')
            ->with(
                'douze',
                '{"ceci": "est-du-contenu"}',
                '8800088',
            )
            ->willThrowException($exception)
        ;

        $albumCacheInvalidatorService = $this->createMock(AlbumCacheInvalidatorService::class);
        $albumCacheInvalidatorService
            ->expects($this->never())
            ->method('invalidate')
        ;

        $dexCacheInvalidatorService = $this->createMock(DexCacheInvalidatorService::class);
        $dexCacheInvalidatorService
            ->expects($this->never())
            ->method('invalidateByTrainerId')
        ;

        $service = new ModifyTrainerDexService(
            $userTokenService,
            $modifyDexService,
            $albumCacheInvalidatorService,
            $dexCacheInvalidatorService
        );

        $this->expectException(ModifyFailedException::class);

        $service->modifyDex('douze', '{"ceci": "est-du-contenu"}');
    }
}
