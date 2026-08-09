<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller\Trainer;

use App\Controller\Trainer\TrainerUpsertController;
use App\Exception\DexNotFoundException;
use App\Exception\EmptyContentException;
use App\Exception\InvalidJsonException;
use App\Exception\ModifyFailedException;
use App\Service\GetTrainerPokedexService;
use App\Service\ModifyTrainerDexService;
use App\Service\RequestedContentService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Constraints\Json;

/**
 * @internal
 */
#[CoversClass(TrainerUpsertController::class)]
final class TrainerUpsertControllerTest extends TestCase
{
    #[Test]
    public function upsert(): void
    {
        $getTrainerPokedexService = $this->createMock(GetTrainerPokedexService::class);
        $getTrainerPokedexService
            ->expects($this->once())
            ->method('getPokedexData')
            ->with('douze', [])
            ->willReturn([
                'dex' => [
                    'slug' => 'douze',
                    'flags' => [
                        'is_premium' => true,
                    ],
                ],
                'pokemons' => [],
            ])
        ;

        $modifyTrainerDexService = $this->createMock(ModifyTrainerDexService::class);
        $modifyTrainerDexService
            ->expects($this->once())
            ->method('modifyDex')
            ->with(
                'douze',
                '{"key": "value"}',
            )
        ;

        $requestedContentService = $this->createMock(RequestedContentService::class);
        $requestedContentService
            ->expects($this->once())
            ->method('getContent')
            ->with(new Json())
            ->willReturn('{"key": "value"}')
        ;

        $authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $authorizationChecker
            ->expects($this->once())
            ->method('isGranted')
            ->with('ROLE_COLLECTOR')
            ->willReturn(true)
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
            ->willReturn($authorizationChecker)
        ;

        $controller = new TrainerUpsertController(
            $getTrainerPokedexService,
            $modifyTrainerDexService,
            $requestedContentService,
        );
        $controller->setContainer($container);

        $response = $controller->upsert('douze');

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEmpty($response->getContent());
    }

    #[Test]
    public function upsertEmptyContentException(): void
    {
        $getTrainerPokedexService = $this->createMock(GetTrainerPokedexService::class);
        $getTrainerPokedexService
            ->expects($this->never())
            ->method('getPokedexData')
        ;

        $modifyTrainerDexService = $this->createMock(ModifyTrainerDexService::class);
        $modifyTrainerDexService
            ->expects($this->never())
            ->method('modifyDex')
        ;

        $requestedContentService = $this->createMock(RequestedContentService::class);
        $requestedContentService
            ->expects($this->once())
            ->method('getContent')
            ->with(new Json())
            ->willThrowException(new EmptyContentException())
        ;

        $container = $this->createMock(ContainerInterface::class);
        $container
            ->expects($this->never())
            ->method('has')
            ->willReturn(true)
        ;
        $container
            ->expects($this->never())
            ->method('get')
        ;

        $controller = new TrainerUpsertController(
            $getTrainerPokedexService,
            $modifyTrainerDexService,
            $requestedContentService,
        );
        $controller->setContainer($container);

        $response = $controller->upsert('douze');

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertSame('{"error":"Content cannot be empty"}', $response->getContent());
    }

    #[Test]
    public function upsertInvalidJsonException(): void
    {
        $getTrainerPokedexService = $this->createMock(GetTrainerPokedexService::class);
        $getTrainerPokedexService
            ->expects($this->never())
            ->method('getPokedexData')
        ;

        $modifyTrainerDexService = $this->createMock(ModifyTrainerDexService::class);
        $modifyTrainerDexService
            ->expects($this->never())
            ->method('modifyDex')
        ;

        $requestedContentService = $this->createMock(RequestedContentService::class);
        $requestedContentService
            ->expects($this->once())
            ->method('getContent')
            ->with(new Json())
            ->willThrowException(new InvalidJsonException())
        ;

        $container = $this->createMock(ContainerInterface::class);
        $container
            ->expects($this->never())
            ->method('has')
            ->willReturn(true)
        ;
        $container
            ->expects($this->never())
            ->method('get')
        ;

        $controller = new TrainerUpsertController(
            $getTrainerPokedexService,
            $modifyTrainerDexService,
            $requestedContentService,
        );
        $controller->setContainer($container);

        $response = $controller->upsert('douze');

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertSame('{"error":"Json is invalid"}', $response->getContent());
    }

    #[Test]
    public function upsertPokedexNull(): void
    {
        $getTrainerPokedexService = $this->createMock(GetTrainerPokedexService::class);
        $getTrainerPokedexService
            ->expects($this->once())
            ->method('getPokedexData')
            ->with('douze', [])
            ->willThrowException(new DexNotFoundException())
        ;

        $modifyTrainerDexService = $this->createMock(ModifyTrainerDexService::class);
        $modifyTrainerDexService
            ->expects($this->never())
            ->method('modifyDex')
        ;

        $requestedContentService = $this->createMock(RequestedContentService::class);
        $requestedContentService
            ->expects($this->once())
            ->method('getContent')
            ->with(new Json())
            ->willReturn('{"key": "value"}')
        ;

        $container = $this->createMock(ContainerInterface::class);
        $container
            ->expects($this->never())
            ->method('has')
        ;
        $container
            ->expects($this->never())
            ->method('get')
        ;

        $controller = new TrainerUpsertController(
            $getTrainerPokedexService,
            $modifyTrainerDexService,
            $requestedContentService,
        );
        $controller->setContainer($container);

        $response = $controller->upsert('douze');

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertSame('[]', $response->getContent());
    }

    #[Test]
    public function upsertDexNotDefined(): void
    {
        $getTrainerPokedexService = $this->createMock(GetTrainerPokedexService::class);
        $getTrainerPokedexService
            ->expects($this->once())
            ->method('getPokedexData')
            ->with('douze', [])
            ->willThrowException(new DexNotFoundException())
        ;

        $modifyTrainerDexService = $this->createMock(ModifyTrainerDexService::class);
        $modifyTrainerDexService
            ->expects($this->never())
            ->method('modifyDex')
        ;

        $requestedContentService = $this->createMock(RequestedContentService::class);
        $requestedContentService
            ->expects($this->once())
            ->method('getContent')
            ->with(new Json())
            ->willReturn('{"key": "value"}')
        ;

        $container = $this->createMock(ContainerInterface::class);
        $container
            ->expects($this->never())
            ->method('has')
        ;
        $container
            ->expects($this->never())
            ->method('get')
        ;

        $controller = new TrainerUpsertController(
            $getTrainerPokedexService,
            $modifyTrainerDexService,
            $requestedContentService,
        );
        $controller->setContainer($container);

        $response = $controller->upsert('douze');

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertSame('[]', $response->getContent());
    }

    #[Test]
    public function upsertNonPremiumDex(): void
    {
        $getTrainerPokedexService = $this->createMock(GetTrainerPokedexService::class);
        $getTrainerPokedexService
            ->expects($this->once())
            ->method('getPokedexData')
            ->with('douze', [])
            ->willReturn([
                'dex' => [
                    'slug' => 'douze',
                    'flags' => [
                        'is_premium' => false,
                    ],
                ],
                'pokemons' => [],
            ])
        ;

        $modifyTrainerDexService = $this->createMock(ModifyTrainerDexService::class);
        $modifyTrainerDexService
            ->expects($this->once())
            ->method('modifyDex')
            ->with(
                'douze',
                '{"key": "value"}',
            )
        ;

        $requestedContentService = $this->createMock(RequestedContentService::class);
        $requestedContentService
            ->expects($this->once())
            ->method('getContent')
            ->with(new Json())
            ->willReturn('{"key": "value"}')
        ;

        $container = $this->createMock(ContainerInterface::class);
        $container
            ->expects($this->never())
            ->method('has')
        ;
        $container
            ->expects($this->never())
            ->method('get')
        ;

        $controller = new TrainerUpsertController(
            $getTrainerPokedexService,
            $modifyTrainerDexService,
            $requestedContentService,
        );
        $controller->setContainer($container);

        $response = $controller->upsert('douze');

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEmpty($response->getContent());
    }

    #[Test]
    public function upsertPremiumDexNotCollector(): void
    {
        $getTrainerPokedexService = $this->createMock(GetTrainerPokedexService::class);
        $getTrainerPokedexService
            ->expects($this->once())
            ->method('getPokedexData')
            ->with('douze', [])
            ->willReturn([
                'dex' => [
                    'slug' => 'douze',
                    'flags' => [
                        'is_premium' => true,
                    ],
                ],
                'pokemons' => [],
            ])
        ;

        $modifyTrainerDexService = $this->createMock(ModifyTrainerDexService::class);
        $modifyTrainerDexService
            ->expects($this->never())
            ->method('modifyDex')
        ;

        $requestedContentService = $this->createMock(RequestedContentService::class);
        $requestedContentService
            ->expects($this->once())
            ->method('getContent')
            ->with(new Json())
            ->willReturn('{"key": "value"}')
        ;

        $authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $authorizationChecker
            ->expects($this->once())
            ->method('isGranted')
            ->with('ROLE_COLLECTOR')
            ->willReturn(false)
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
            ->willReturn($authorizationChecker)
        ;

        $controller = new TrainerUpsertController(
            $getTrainerPokedexService,
            $modifyTrainerDexService,
            $requestedContentService,
        );
        $controller->setContainer($container);

        $response = $controller->upsert('douze');

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertSame('[]', $response->getContent());
    }

    #[Test]
    public function upsertModifyFail(): void
    {
        $getTrainerPokedexService = $this->createMock(GetTrainerPokedexService::class);
        $getTrainerPokedexService
            ->expects($this->once())
            ->method('getPokedexData')
            ->with('douze', [])
            ->willReturn([
                'dex' => [
                    'slug' => 'douze',
                    'flags' => [
                        'is_premium' => true,
                    ],
                ],
                'pokemons' => [],
            ])
        ;

        $modifyTrainerDexService = $this->createMock(ModifyTrainerDexService::class);
        $modifyTrainerDexService
            ->expects($this->once())
            ->method('modifyDex')
            ->with(
                'douze',
                '{"key": "value"}',
            )
            ->willThrowException(new ModifyFailedException())
        ;

        $requestedContentService = $this->createMock(RequestedContentService::class);
        $requestedContentService
            ->expects($this->once())
            ->method('getContent')
            ->with(new Json())
            ->willReturn('{"key": "value"}')
        ;

        $authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $authorizationChecker
            ->expects($this->once())
            ->method('isGranted')
            ->with('ROLE_COLLECTOR')
            ->willReturn(true)
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
            ->willReturn($authorizationChecker)
        ;

        $controller = new TrainerUpsertController(
            $getTrainerPokedexService,
            $modifyTrainerDexService,
            $requestedContentService,
        );
        $controller->setContainer($container);

        $response = $controller->upsert('douze');

        $this->assertEquals(500, $response->getStatusCode());
        $this->assertSame('{"error":"Fail to modify resources"}', $response->getContent());
    }
}
