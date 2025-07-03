<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller;

use App\Controller\PokedexController;
use App\Security\User;
use App\Service\GetTrainerPokedexService;
use App\Service\TrainerIdsService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * @internal
 */
#[CoversClass(PokedexController::class)]
class PokedexControllerTest extends TestCase
{
    public function testGet(): void
    {
        $trainerIdsService = $this->createMock(TrainerIdsService::class);
        $trainerIdsService
            ->expects($this->once())
            ->method('init')
        ;
        $trainerIdsService
            ->expects($this->once())
            ->method('getTrainerId')
            ->willReturn('douze')
        ;

        $getTrainerPokedexService = $this->createMock(GetTrainerPokedexService::class);
        $getTrainerPokedexService
            ->expects($this->once())
            ->method('getPokedexDataByTrainerId')
            ->willReturn([
                'dex' => [
                    'slug' => 'machi',
                    'name' => 'Machi Pokedex',
                    'is_private' => false,
                    'is_released' => true,
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

        $controller = new PokedexController(
            $trainerIdsService,
            $getTrainerPokedexService
        );
        $controller->setContainer($container);

        $response = $controller->get(new Request(), 'machi');

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testGetWithoutAuthenticatedUser(): void
    {
        $trainerIdsService = $this->createMock(TrainerIdsService::class);
        $trainerIdsService
            ->expects($this->once())
            ->method('init')
        ;
        $trainerIdsService
            ->expects($this->once())
            ->method('getTrainerId')
            ->willReturn(null)
        ;

        $getTrainerPokedexService = $this->createMock(GetTrainerPokedexService::class);

        $controller = new PokedexController(
            $trainerIdsService,
            $getTrainerPokedexService
        );

        $response = $controller->get(new Request(), 'machi');

        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testGetWithNullPokedex(): void
    {
        $trainerIdsService = $this->createMock(TrainerIdsService::class);
        $trainerIdsService
            ->expects($this->once())
            ->method('init')
        ;
        $trainerIdsService
            ->expects($this->once())
            ->method('getTrainerId')
            ->willReturn('douze')
        ;

        $getTrainerPokedexService = $this->createMock(GetTrainerPokedexService::class);
        $getTrainerPokedexService
            ->expects($this->once())
            ->method('getPokedexDataByTrainerId')
            ->willReturn(null)
        ;

        $controller = new PokedexController(
            $trainerIdsService,
            $getTrainerPokedexService
        );

        $response = $controller->get(new Request(), 'machi');

        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testGetWithNonSetDexPokedex(): void
    {
        $trainerIdsService = $this->createMock(TrainerIdsService::class);
        $trainerIdsService
            ->expects($this->once())
            ->method('init')
        ;
        $trainerIdsService
            ->expects($this->once())
            ->method('getTrainerId')
            ->willReturn('douze')
        ;

        $getTrainerPokedexService = $this->createMock(GetTrainerPokedexService::class);
        $getTrainerPokedexService
            ->expects($this->once())
            ->method('getPokedexDataByTrainerId')
            ->willReturn([
                'dexes' => [
                    'slug' => 'machi',
                    'name' => 'Machi Pokedex',
                    'is_private' => false,
                    'is_released' => true,
                ],
            ])
        ;

        $controller = new PokedexController(
            $trainerIdsService,
            $getTrainerPokedexService
        );

        $response = $controller->get(new Request(), 'machi');

        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testGetWithEmptyDexPokedex(): void
    {
        $trainerIdsService = $this->createMock(TrainerIdsService::class);
        $trainerIdsService
            ->expects($this->once())
            ->method('init')
        ;
        $trainerIdsService
            ->expects($this->once())
            ->method('getTrainerId')
            ->willReturn('douze')
        ;

        $getTrainerPokedexService = $this->createMock(GetTrainerPokedexService::class);
        $getTrainerPokedexService
            ->expects($this->once())
            ->method('getPokedexDataByTrainerId')
            ->willReturn([
                'dex' => [],
            ])
        ;

        $controller = new PokedexController(
            $trainerIdsService,
            $getTrainerPokedexService
        );

        $response = $controller->get(new Request(), 'machi');

        $this->assertEquals(404, $response->getStatusCode());
    }
}
