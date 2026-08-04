<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller\Album;

use App\Controller\Album\TrainerDexLinkController;
use App\Exception\ApiValidationException;
use App\Exception\DexNotFoundException;
use App\Service\GetTrainerPokedexService;
use App\Service\TrainerDexLinkService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * @internal
 */
#[CoversClass(TrainerDexLinkController::class)]
final class TrainerDexLinkControllerTest extends TestCase
{
    public function testListRejectsPremiumDexForNonCollector(): void
    {
        $getTrainerPokedexService = $this->createMock(GetTrainerPokedexService::class);
        $getTrainerPokedexService->method('getPokedexData')
            ->willReturn(['dex' => ['flags' => ['is_premium' => true]], 'pokemons' => []])
        ;

        $trainerDexLinkService = $this->createMock(TrainerDexLinkService::class);
        $trainerDexLinkService->expects($this->never())->method('list');

        $controller = $this->controller($getTrainerPokedexService, $trainerDexLinkService, false);

        $response = $controller->list('douze');

        $this->assertSame(404, $response->getStatusCode());
    }

    public function testListReturnsLinksWhenAccessible(): void
    {
        $getTrainerPokedexService = $this->createMock(GetTrainerPokedexService::class);
        $getTrainerPokedexService->method('getPokedexData')
            ->willReturn(['dex' => ['flags' => ['is_premium' => false]], 'pokemons' => []])
        ;

        $trainerDexLinkService = $this->createMock(TrainerDexLinkService::class);
        $trainerDexLinkService->expects($this->once())
            ->method('list')
            ->with('douze')
            ->willReturn([['id' => 'link-1']])
        ;

        $controller = $this->controller($getTrainerPokedexService, $trainerDexLinkService, true);

        $response = $controller->list('douze');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('[{"id":"link-1"}]', $response->getContent());
    }

    public function testListReturnsLinksForNonCollectorWhenDexIsNotPremium(): void
    {
        $getTrainerPokedexService = $this->createMock(GetTrainerPokedexService::class);
        $getTrainerPokedexService->method('getPokedexData')
            ->willReturn(['dex' => ['flags' => ['is_premium' => false]], 'pokemons' => []])
        ;

        $trainerDexLinkService = $this->createMock(TrainerDexLinkService::class);
        $trainerDexLinkService->expects($this->once())
            ->method('list')
            ->with('douze')
            ->willReturn([['id' => 'link-1']])
        ;

        $controller = $this->controller($getTrainerPokedexService, $trainerDexLinkService, false);

        $response = $controller->list('douze');

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testListNotFoundWhenDexUnknown(): void
    {
        $getTrainerPokedexService = $this->createMock(GetTrainerPokedexService::class);
        $getTrainerPokedexService->method('getPokedexData')
            ->willThrowException(new DexNotFoundException())
        ;

        $trainerDexLinkService = $this->createMock(TrainerDexLinkService::class);
        $trainerDexLinkService->expects($this->never())->method('list');

        $controller = $this->controller($getTrainerPokedexService, $trainerDexLinkService, true);

        $response = $controller->list('douze');

        $this->assertSame(404, $response->getStatusCode());
    }

    public function testCreateRejectsEmptyBody(): void
    {
        $getTrainerPokedexService = $this->createMock(GetTrainerPokedexService::class);
        $getTrainerPokedexService->method('getPokedexData')
            ->willReturn(['dex' => ['flags' => ['is_premium' => false]], 'pokemons' => []])
        ;

        $trainerDexLinkService = $this->createMock(TrainerDexLinkService::class);
        $trainerDexLinkService->expects($this->never())->method('create');

        $controller = $this->controller($getTrainerPokedexService, $trainerDexLinkService, true);

        $response = $controller->create('douze', Request::create('test.local', 'POST', content: ''));

        $this->assertSame(400, $response->getStatusCode());
    }

    public function testCreateRejectsMissingTargetDexSlug(): void
    {
        $getTrainerPokedexService = $this->createMock(GetTrainerPokedexService::class);
        $getTrainerPokedexService->method('getPokedexData')
            ->willReturn(['dex' => ['flags' => ['is_premium' => false]], 'pokemons' => []])
        ;

        $trainerDexLinkService = $this->createMock(TrainerDexLinkService::class);
        $trainerDexLinkService->expects($this->never())->method('create');

        $controller = $this->controller($getTrainerPokedexService, $trainerDexLinkService, true);

        $response = $controller->create('douze', Request::create('test.local', 'POST', content: '{}'));

        $this->assertSame(400, $response->getStatusCode());
    }

    public function testCreateRejectsPremiumDexForNonCollector(): void
    {
        $getTrainerPokedexService = $this->createMock(GetTrainerPokedexService::class);
        $getTrainerPokedexService->method('getPokedexData')
            ->willReturn(['dex' => ['flags' => ['is_premium' => true]], 'pokemons' => []])
        ;

        $trainerDexLinkService = $this->createMock(TrainerDexLinkService::class);
        $trainerDexLinkService->expects($this->never())->method('create');

        $controller = $this->controller($getTrainerPokedexService, $trainerDexLinkService, false);

        $response = $controller->create('douze', Request::create('test.local', 'POST', content: '{"targetDexSlug":"treize"}'));

        $this->assertSame(404, $response->getStatusCode());
    }

    public function testCreateRejectsNonBooleanBidirectional(): void
    {
        $getTrainerPokedexService = $this->createMock(GetTrainerPokedexService::class);
        $getTrainerPokedexService->method('getPokedexData')
            ->willReturn(['dex' => ['flags' => ['is_premium' => false]], 'pokemons' => []])
        ;

        $trainerDexLinkService = $this->createMock(TrainerDexLinkService::class);
        $trainerDexLinkService->expects($this->never())->method('create');

        $controller = $this->controller($getTrainerPokedexService, $trainerDexLinkService, true);

        $response = $controller->create('douze', Request::create('test.local', 'POST', content: '{"targetDexSlug":"treize","bidirectional":"yes"}'));

        $this->assertSame(400, $response->getStatusCode());
    }

    public function testCreateForwardsTheApiStatusCodeOnFailure(): void
    {
        $getTrainerPokedexService = $this->createMock(GetTrainerPokedexService::class);
        $getTrainerPokedexService->method('getPokedexData')
            ->willReturn(['dex' => ['flags' => ['is_premium' => false]], 'pokemons' => []])
        ;

        $trainerDexLinkService = $this->createMock(TrainerDexLinkService::class);
        $trainerDexLinkService->expects($this->once())
            ->method('create')
            ->with('douze', 'treize', false)
            ->willThrowException(new ApiValidationException(409))
        ;

        $controller = $this->controller($getTrainerPokedexService, $trainerDexLinkService, true);

        $response = $controller->create('douze', Request::create('test.local', 'POST', content: '{"targetDexSlug":"treize"}'));

        $this->assertSame(409, $response->getStatusCode());
    }

    public function testCreateSucceeds(): void
    {
        $getTrainerPokedexService = $this->createMock(GetTrainerPokedexService::class);
        $getTrainerPokedexService->method('getPokedexData')
            ->willReturn(['dex' => ['flags' => ['is_premium' => false]], 'pokemons' => []])
        ;

        $trainerDexLinkService = $this->createMock(TrainerDexLinkService::class);
        $trainerDexLinkService->expects($this->once())
            ->method('create')
            ->with('douze', 'treize', true)
        ;

        $controller = $this->controller($getTrainerPokedexService, $trainerDexLinkService, true);

        $response = $controller->create('douze', Request::create('test.local', 'POST', content: '{"targetDexSlug":"treize","bidirectional":true}'));

        $this->assertSame(201, $response->getStatusCode());
    }

    public function testDelete(): void
    {
        $getTrainerPokedexService = $this->createMock(GetTrainerPokedexService::class);

        $trainerDexLinkService = $this->createMock(TrainerDexLinkService::class);
        $trainerDexLinkService->expects($this->once())
            ->method('delete')
            ->with('link-1')
        ;

        $controller = new TrainerDexLinkController($getTrainerPokedexService, $trainerDexLinkService);

        $response = $controller->delete('link-1');

        $this->assertSame(200, $response->getStatusCode());
    }

    private function controller(
        GetTrainerPokedexService $getTrainerPokedexService,
        TrainerDexLinkService $trainerDexLinkService,
        bool $isCollector,
    ): TrainerDexLinkController {
        $authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $authorizationChecker->expects($this->atMost(1))->method('isGranted')->with('ROLE_COLLECTOR')->willReturn($isCollector);

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturn(true);
        $container->method('get')->willReturn($authorizationChecker);

        $controller = new TrainerDexLinkController($getTrainerPokedexService, $trainerDexLinkService);
        $controller->setContainer($container);

        return $controller;
    }
}
