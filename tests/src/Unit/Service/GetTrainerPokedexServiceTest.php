<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Exception\DexNotFoundException;
use App\Security\UserTokenService;
use App\Service\Api\GetPokedexApiService;
use App\Service\GetTrainerPokedexService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * @internal
 */
#[CoversClass(GetTrainerPokedexService::class)]
final class GetTrainerPokedexServiceTest extends TestCase
{
    #[Test]
    public function getPokedexData(): void
    {
        $userTokenService = $this->createMock(UserTokenService::class);
        $userTokenService
            ->expects($this->once())
            ->method('getLoggedUserToken')
            ->willReturn('8800088')
        ;

        $getPokedexService = $this->createMock(GetPokedexApiService::class);
        $getPokedexService
            ->expects($this->once())
            ->method('get')
            ->with(
                'douze',
                '8800088',
                [],
            )
            ->willReturn([
                'dex' => [
                    'slug' => 'douze-douze',
                ],
                'pokemons' => [],
            ])
        ;

        $service = new GetTrainerPokedexService($userTokenService, $getPokedexService);
        $pokedexData = $service->getPokedexData('douze', []);

        $this->assertSame(
            [
                'dex' => [
                    'slug' => 'douze-douze',
                ],
                'pokemons' => [],
            ],
            $pokedexData,
        );
    }

    #[Test]
    public function getPokedexDataWithFilters(): void
    {
        $userTokenService = $this->createMock(UserTokenService::class);
        $userTokenService
            ->expects($this->once())
            ->method('getLoggedUserToken')
            ->willReturn('8800088')
        ;

        $getPokedexService = $this->createMock(GetPokedexApiService::class);
        $getPokedexService
            ->expects($this->once())
            ->method('get')
            ->with(
                'douze',
                '8800088',
                [
                    'to' => 'toto',
                    'ti' => 'titi',
                ],
            )
            ->willReturn([
                'dex' => [
                    'slug' => 'douze-douze',
                ],
                'pokemons' => [],
            ])
        ;

        $service = new GetTrainerPokedexService($userTokenService, $getPokedexService);
        $pokedexData = $service->getPokedexData(
            'douze',
            [
                'to' => 'toto',
                'ti' => 'titi',
            ]
        );

        $this->assertSame(
            [
                'dex' => [
                    'slug' => 'douze-douze',
                ],
                'pokemons' => [],
            ],
            $pokedexData,
        );
    }

    #[Test]
    public function getPokedexDataByTrainerId(): void
    {
        $userTokenService = $this->createMock(UserTokenService::class);
        $userTokenService
            ->expects($this->never())
            ->method('getLoggedUserToken')
        ;

        $getPokedexService = $this->createMock(GetPokedexApiService::class);
        $getPokedexService
            ->expects($this->once())
            ->method('get')
            ->with(
                'douze',
                '8800088',
                [],
            )
            ->willReturn([
                'dex' => [
                    'slug' => 'douze-douze',
                ],
                'pokemons' => [],
            ])
        ;

        $service = new GetTrainerPokedexService($userTokenService, $getPokedexService);
        $pokedexData = $service->getPokedexDataByTrainerId('douze', [], '8800088');

        $this->assertSame(
            [
                'dex' => [
                    'slug' => 'douze-douze',
                ],
                'pokemons' => [],
            ],
            $pokedexData,
        );
    }

    #[Test]
    public function getPokedexDataEmptyDe(): void
    {
        $userTokenService = $this->createMock(UserTokenService::class);
        $userTokenService
            ->expects($this->once())
            ->method('getLoggedUserToken')
            ->willReturn('8800088')
        ;

        $getPokedexService = $this->createMock(GetPokedexApiService::class);
        $getPokedexService
            ->expects($this->once())
            ->method('get')
            ->with(
                'douze',
                '8800088',
                [],
            )
            ->willReturn([
                'dex' => [],
                'pokemons' => [],
            ])
        ;

        $service = new GetTrainerPokedexService($userTokenService, $getPokedexService);

        $this->expectException(DexNotFoundException::class);
        $this->expectExceptionMessageIsOrContains('Dex not found');

        $service->getPokedexData('douze', []);
    }

    #[Test]
    public function getPokedexDataHttpException(): void
    {
        $userTokenService = $this->createMock(UserTokenService::class);
        $userTokenService
            ->expects($this->once())
            ->method('getLoggedUserToken')
            ->willReturn('8800088')
        ;

        $exception = $this->createStub(HttpExceptionInterface::class);

        $getPokedexService = $this->createMock(GetPokedexApiService::class);
        $getPokedexService
            ->expects($this->once())
            ->method('get')
            ->with(
                'douze',
                '8800088',
                [],
            )
            ->willThrowException($exception)
        ;

        $service = new GetTrainerPokedexService($userTokenService, $getPokedexService);

        $this->expectException(DexNotFoundException::class);
        $this->expectExceptionMessageIsOrContains('Dex not found');

        $service->getPokedexData('douze', []);
    }

    #[Test]
    public function getPokedexDataTransportException(): void
    {
        $userTokenService = $this->createMock(UserTokenService::class);
        $userTokenService
            ->expects($this->once())
            ->method('getLoggedUserToken')
            ->willReturn('8800088')
        ;

        $exception = $this->createStub(TransportExceptionInterface::class);

        $getPokedexService = $this->createMock(GetPokedexApiService::class);
        $getPokedexService
            ->expects($this->once())
            ->method('get')
            ->with(
                'douze',
                '8800088',
                [],
            )
            ->willThrowException($exception)
        ;

        $service = new GetTrainerPokedexService($userTokenService, $getPokedexService);

        $this->expectException(DexNotFoundException::class);
        $this->expectExceptionMessageIsOrContains('Dex not found');

        $service->getPokedexData('douze', []);
    }
}
