<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller\Album;

use App\Controller\Album\AlbumPokedexController;
use App\Exception\DexNotFoundException;
use App\Security\User;
use App\Service\GetTrainerPokedexService;
use App\Service\TrainerIdsService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * @internal
 */
#[CoversClass(AlbumPokedexController::class)]
final class AlbumPokedexControllerTest extends TestCase
{
    #[Test]
    public function get(): void
    {
        $trainerIdsService = $this->createMock(TrainerIdsService::class);
        $trainerIdsService
            ->expects($this->once())
            ->method('getTrainerId')
            ->willReturn('douze')
        ;

        $getTrainerPokedexService = $this->createMock(GetTrainerPokedexService::class);
        $getTrainerPokedexService
            ->expects($this->once())
            ->method('getPokedexDataByTrainerId')
            ->with('machi', [], 'douze', null)
            ->willReturn([
                'dex' => [
                    'slug' => 'machi',
                    'name' => 'Machi Pokedex',
                    'flags' => [
                        'is_private' => false,
                        'is_released' => true,
                    ],
                ],
            ])
        ;

        $token = $this->createMock(TokenInterface::class);
        $token
            ->expects($this->once())
            ->method('getUser')
            ->willReturn(new User('douze', 'ROLE_TRAINER'))
        ;
        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage
            ->expects($this->once())
            ->method('getToken')
            ->willReturn($token)
        ;

        $container = $this->createMock(ContainerInterface::class);
        $container
            ->expects($this->once())
            ->method('has')
            ->willReturn(true)
        ;
        $container
            ->expects($this->once())
            ->method('get')
            ->willReturn($tokenStorage)
        ;

        $controller = new AlbumPokedexController(
            $trainerIdsService,
            $getTrainerPokedexService,
            ['en', 'fr'],
        );
        $controller->setContainer($container);

        $response = $controller->get(new Request(), 'machi');

        $this->assertEquals(200, $response->getStatusCode());
    }

    #[Test]
    public function getWithoutAuthenticatedUser(): void
    {
        $trainerIdsService = $this->createMock(TrainerIdsService::class);
        $trainerIdsService
            ->expects($this->once())
            ->method('getTrainerId')
            ->willReturn(null)
        ;

        $getTrainerPokedexService = $this->createStub(GetTrainerPokedexService::class);

        $controller = new AlbumPokedexController(
            $trainerIdsService,
            $getTrainerPokedexService,
            ['en', 'fr'],
        );

        $response = $controller->get(new Request(), 'machi');

        $this->assertEquals(404, $response->getStatusCode());
    }

    #[Test]
    public function getDexNotFound(): void
    {
        $trainerIdsService = $this->createMock(TrainerIdsService::class);
        $trainerIdsService
            ->expects($this->once())
            ->method('getTrainerId')
            ->willReturn('douze')
        ;

        $getTrainerPokedexService = $this->createMock(GetTrainerPokedexService::class);
        $getTrainerPokedexService
            ->expects($this->once())
            ->method('getPokedexDataByTrainerId')
            ->willThrowException(new DexNotFoundException())
        ;

        $controller = new AlbumPokedexController(
            $trainerIdsService,
            $getTrainerPokedexService,
            ['en', 'fr'],
        );

        $response = $controller->get(new Request(), 'machi');

        $this->assertEquals(404, $response->getStatusCode());
    }

    #[Test]
    public function getInALanguage(): void
    {
        $trainerIdsService = $this->createStub(TrainerIdsService::class);
        $trainerIdsService->method('getTrainerId')->willReturn('douze');
        $trainerIdsService->method('getLoggedTrainerId')->willReturn('douze');

        $getTrainerPokedexService = $this->createMock(GetTrainerPokedexService::class);
        $getTrainerPokedexService
            ->expects($this->once())
            ->method('getPokedexDataByTrainerId')
            ->with('machi', [], 'douze', 'fr')
            ->willReturn([
                'dex' => ['slug' => 'machi', 'flags' => ['is_private' => false, 'is_released' => true]],
                'pokemons' => [],
            ])
        ;

        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn(new User('douze', 'ROLE_TRAINER'));
        $tokenStorage = $this->createStub(TokenStorageInterface::class);
        $tokenStorage->method('getToken')->willReturn($token);
        $container = $this->createStub(ContainerInterface::class);
        $container->method('has')->willReturn(true);
        $container->method('get')->willReturn($tokenStorage);

        $controller = new AlbumPokedexController($trainerIdsService, $getTrainerPokedexService, ['en', 'fr']);
        $controller->setContainer($container);

        $response = $controller->get(new Request(['lang' => 'fr']), 'machi');

        $this->assertEquals(200, $response->getStatusCode());
    }

    #[Test]
    public function getInANotEnabledLanguage(): void
    {
        $trainerIdsService = $this->createStub(TrainerIdsService::class);
        $trainerIdsService->method('getTrainerId')->willReturn('douze');

        $getTrainerPokedexService = $this->createMock(GetTrainerPokedexService::class);
        $getTrainerPokedexService
            ->expects($this->never())
            ->method('getPokedexDataByTrainerId')
        ;

        $controller = new AlbumPokedexController($trainerIdsService, $getTrainerPokedexService, ['en', 'fr']);

        $response = $controller->get(new Request(['lang' => 'de']), 'machi');

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString('{"error": "Invalid language"}', (string) $response->getContent());
    }
}
