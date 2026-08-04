<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Exception\ModifyFailedException;
use App\Security\UserTokenService;
use App\Service\Api\ModifyAlbumApiService;
use App\Service\CacheInvalidator\AlbumCacheInvalidatorService;
use App\Service\CacheInvalidator\AlbumsCacheInvalidatorService;
use App\Service\ModifyTrainerAlbumService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * @internal
 */
#[CoversClass(ModifyTrainerAlbumService::class)]
final class ModifyTrainerAlbumServiceTest extends TestCase
{
    public function testModifyAlbumInvalidatesEveryUpdatedDexSlug(): void
    {
        $userTokenService = $this->createMock(UserTokenService::class);
        $userTokenService->expects($this->once())
            ->method('getLoggedUserToken')
            ->willReturn('8800088')
        ;

        $modifyAlbumService = $this->createMock(ModifyAlbumApiService::class);
        $modifyAlbumService->expects($this->once())
            ->method('modify')
            ->with('PUT', 'douze', 'treize', '{"ceci": "est-du-contenu"}', '8800088')
            ->willReturn(['douze', 'treize-dex'])
        ;

        $albumsCacheInvalidatorService = $this->createMock(AlbumsCacheInvalidatorService::class);
        $albumsCacheInvalidatorService->expects($this->once())->method('invalidate');

        $albumCacheInvalidatorService = $this->createMock(AlbumCacheInvalidatorService::class);
        $albumCacheInvalidatorService->expects($this->exactly(2))
            ->method('invalidate')
            ->willReturnMap([
                ['douze', '8800088', null],
                ['treize-dex', '8800088', null],
            ])
        ;

        $request = Request::create('test.local', 'PUT');
        $requestStack = new RequestStack();
        $requestStack->push($request);

        $service = new ModifyTrainerAlbumService(
            $userTokenService,
            $modifyAlbumService,
            $albumsCacheInvalidatorService,
            $albumCacheInvalidatorService,
            $requestStack,
        );
        $service->modifyAlbum('douze', 'treize', '{"ceci": "est-du-contenu"}');
    }

    public function testModifyAlbumInvalidatesOnlyTheOriginWhenNothingElseChanged(): void
    {
        $userTokenService = $this->createMock(UserTokenService::class);
        $userTokenService->method('getLoggedUserToken')->willReturn('8800088');

        $modifyAlbumService = $this->createMock(ModifyAlbumApiService::class);
        $modifyAlbumService->method('modify')->willReturn(['douze']);

        $albumsCacheInvalidatorService = $this->createMock(AlbumsCacheInvalidatorService::class);
        $albumsCacheInvalidatorService->expects($this->once())->method('invalidate');

        $albumCacheInvalidatorService = $this->createMock(AlbumCacheInvalidatorService::class);
        $albumCacheInvalidatorService->expects($this->once())
            ->method('invalidate')
            ->with('douze', '8800088')
        ;

        $request = Request::create('test.local', 'PUT');
        $requestStack = new RequestStack();
        $requestStack->push($request);

        $service = new ModifyTrainerAlbumService(
            $userTokenService,
            $modifyAlbumService,
            $albumsCacheInvalidatorService,
            $albumCacheInvalidatorService,
            $requestStack,
        );
        $service->modifyAlbum('douze', 'treize', '{"ceci": "est-du-contenu"}');
    }

    public function testModifyDexWithHttpException(): void
    {
        $userTokenService = $this->createMock(UserTokenService::class);
        $userTokenService->expects($this->once())
            ->method('getLoggedUserToken')
            ->willReturn('8800088')
        ;

        $exception = $this->createStub(HttpExceptionInterface::class);

        $modifyAlbumService = $this->createMock(ModifyAlbumApiService::class);
        $modifyAlbumService->expects($this->once())
            ->method('modify')
            ->with('PUT', 'douze', 'treize', '{"ceci": "est-du-contenu"}', '8800088')
            ->willThrowException($exception)
        ;

        $albumsCacheInvalidatorService = $this->createMock(AlbumsCacheInvalidatorService::class);
        $albumsCacheInvalidatorService->expects($this->never())->method('invalidate');

        $albumCacheInvalidatorService = $this->createMock(AlbumCacheInvalidatorService::class);
        $albumCacheInvalidatorService->expects($this->never())->method('invalidate');

        $request = Request::create('test.local', 'PUT');
        $requestStack = new RequestStack();
        $requestStack->push($request);

        $service = new ModifyTrainerAlbumService(
            $userTokenService,
            $modifyAlbumService,
            $albumsCacheInvalidatorService,
            $albumCacheInvalidatorService,
            $requestStack,
        );

        $this->expectException(ModifyFailedException::class);

        $service->modifyAlbum('douze', 'treize', '{"ceci": "est-du-contenu"}');
    }

    public function testModifyDexWithMalformedJsonResponse(): void
    {
        $userTokenService = $this->createMock(UserTokenService::class);
        $userTokenService->expects($this->once())
            ->method('getLoggedUserToken')
            ->willReturn('8800088')
        ;

        $exception = new \JsonException('Syntax error');

        $modifyAlbumService = $this->createMock(ModifyAlbumApiService::class);
        $modifyAlbumService->expects($this->once())
            ->method('modify')
            ->with('PUT', 'douze', 'treize', '{"ceci": "est-du-contenu"}', '8800088')
            ->willThrowException($exception)
        ;

        $albumsCacheInvalidatorService = $this->createMock(AlbumsCacheInvalidatorService::class);
        $albumsCacheInvalidatorService->expects($this->never())->method('invalidate');

        $albumCacheInvalidatorService = $this->createMock(AlbumCacheInvalidatorService::class);
        $albumCacheInvalidatorService->expects($this->never())->method('invalidate');

        $request = Request::create('test.local', 'PUT');
        $requestStack = new RequestStack();
        $requestStack->push($request);

        $service = new ModifyTrainerAlbumService(
            $userTokenService,
            $modifyAlbumService,
            $albumsCacheInvalidatorService,
            $albumCacheInvalidatorService,
            $requestStack,
        );

        $this->expectException(ModifyFailedException::class);

        $service->modifyAlbum('douze', 'treize', '{"ceci": "est-du-contenu"}');
    }

    public function testModifyDexWithNoRequest(): void
    {
        $userTokenService = $this->createMock(UserTokenService::class);
        $userTokenService->expects($this->once())
            ->method('getLoggedUserToken')
            ->willReturn('8800088')
        ;

        $modifyAlbumService = $this->createMock(ModifyAlbumApiService::class);
        $modifyAlbumService->expects($this->never())->method('modify');

        $albumsCacheInvalidatorService = $this->createMock(AlbumsCacheInvalidatorService::class);
        $albumsCacheInvalidatorService->expects($this->never())->method('invalidate');

        $albumCacheInvalidatorService = $this->createMock(AlbumCacheInvalidatorService::class);
        $albumCacheInvalidatorService->expects($this->never())->method('invalidate');

        $requestStack = new RequestStack();

        $service = new ModifyTrainerAlbumService(
            $userTokenService,
            $modifyAlbumService,
            $albumsCacheInvalidatorService,
            $albumCacheInvalidatorService,
            $requestStack,
        );

        $this->expectException(ModifyFailedException::class);

        $service->modifyAlbum('douze', 'treize', '{"ceci": "est-du-contenu"}');
    }

    public function testModifyDexWithTransportException(): void
    {
        $userTokenService = $this->createMock(UserTokenService::class);
        $userTokenService->expects($this->once())
            ->method('getLoggedUserToken')
            ->willReturn('8800088')
        ;

        $exception = $this->createStub(TransportExceptionInterface::class);

        $modifyAlbumService = $this->createMock(ModifyAlbumApiService::class);
        $modifyAlbumService->expects($this->once())
            ->method('modify')
            ->with('PUT', 'douze', 'treize', '{"ceci": "est-du-contenu"}', '8800088')
            ->willThrowException($exception)
        ;

        $albumsCacheInvalidatorService = $this->createMock(AlbumsCacheInvalidatorService::class);
        $albumsCacheInvalidatorService->expects($this->never())->method('invalidate');

        $albumCacheInvalidatorService = $this->createMock(AlbumCacheInvalidatorService::class);
        $albumCacheInvalidatorService->expects($this->never())->method('invalidate');

        $request = Request::create('test.local', 'PUT');
        $requestStack = new RequestStack();
        $requestStack->push($request);

        $service = new ModifyTrainerAlbumService(
            $userTokenService,
            $modifyAlbumService,
            $albumsCacheInvalidatorService,
            $albumCacheInvalidatorService,
            $requestStack,
        );

        $this->expectException(ModifyFailedException::class);

        $service->modifyAlbum('douze', 'treize', '{"ceci": "est-du-contenu"}');
    }
}
