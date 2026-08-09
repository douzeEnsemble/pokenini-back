<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\DTO\ElectionPokemonsList;
use App\Security\UserTokenService;
use App\Service\Api\GetPokemonsApiService;
use App\Service\GetPokemonsListService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(GetPokemonsListService::class)]
final class GetPokemonsListServiceTest extends TestCase
{
    #[Test]
    public function get(): void
    {
        $userTokenService = $this->createMock(UserTokenService::class);
        $userTokenService
            ->expects($this->once())
            ->method('getLoggedUserToken')
            ->willReturn('8800088')
        ;

        $getPokemonsService = $this->createMock(GetPokemonsApiService::class);
        $getPokemonsService
            ->expects($this->once())
            ->method('get')
            ->with(
                '8800088',
                'douze',
                '',
                12,
            )
            ->willReturn(
                new ElectionPokemonsList(
                    [
                        'type' => 'pick',
                        'items' => [
                            [
                                'pokemon' => ['slug' => 'pichu', 'name' => 'Pichu', 'french_name' => 'Pichu', 'national_dex_number' => 172, 'regional_dex_number' => null, 'simplified_name' => 'Pichu', 'forms_label' => '', 'simplified_french_name' => 'Pichu', 'forms_french_label' => '', 'icon' => 'pichu', 'family_order' => 0, 'family_lead' => null, 'original_game_bundle' => ['slug' => 'goldsilvercrystal'], 'order_number' => '9999-0172-000', 'game_bundles' => [['slug' => 'goldsilvercrystal']], 'game_bundles_shiny' => [['slug' => 'goldsilvercrystal']], 'small_regular_credit' => null, 'small_shiny_credit' => null, 'big_regular_credit' => null, 'big_shiny_credit' => null],
                                'forms' => ['category' => null, 'regional' => null, 'special' => null, 'variant' => null],
                                'types' => ['primary' => ['slug' => 'electric', 'name' => 'Electric', 'french_name' => 'Électrik', 'color' => '#F8D030'], 'secondary' => null],
                            ],
                            [
                                'pokemon' => ['slug' => 'raichu', 'name' => 'Raichu', 'french_name' => 'Raichu', 'national_dex_number' => 26, 'regional_dex_number' => null, 'simplified_name' => 'Raichu', 'forms_label' => '', 'simplified_french_name' => 'Raichu', 'forms_french_label' => '', 'icon' => 'raichu', 'family_order' => 2, 'family_lead' => ['slug' => 'pichu'], 'original_game_bundle' => ['slug' => 'redgreenblueyellow'], 'order_number' => '9999-0026-002', 'game_bundles' => [['slug' => 'redgreenblueyellow']], 'game_bundles_shiny' => [['slug' => 'redgreenblueyellow']], 'small_regular_credit' => null, 'small_shiny_credit' => null, 'big_regular_credit' => null, 'big_shiny_credit' => null],
                                'forms' => ['category' => null, 'regional' => null, 'special' => null, 'variant' => null],
                                'types' => ['primary' => ['slug' => 'electric', 'name' => 'Electric', 'french_name' => 'Électrik', 'color' => '#F8D030'], 'secondary' => null],
                            ],
                        ],
                    ]
                )
            )
        ;

        $service = new GetPokemonsListService($userTokenService, $getPokemonsService, 12);
        $list = $service->get('douze', '', []);

        $this->assertSame('pick', $list->type);
        $this->assertCount(2, $list->items);
        $this->assertSame('pichu', $list->items[0]['pokemon']['slug']);
        $this->assertSame('raichu', $list->items[1]['pokemon']['slug']);
    }

    #[Test]
    public function getWithFilters(): void
    {
        $userTokenService = $this->createMock(UserTokenService::class);
        $userTokenService
            ->expects($this->once())
            ->method('getLoggedUserToken')
            ->willReturn('8800088')
        ;

        $getPokemonsService = $this->createMock(GetPokemonsApiService::class);
        $getPokemonsService
            ->expects($this->once())
            ->method('get')
            ->with(
                '8800088',
                'douze',
                '',
                12,
                ['at' => ['poison', 'fire'], 'cf' => ['legendary']],
            )
            ->willReturn(
                new ElectionPokemonsList(
                    [
                        'type' => 'pick',
                        'items' => [
                            [
                                'pokemon' => ['slug' => 'pichu', 'name' => 'Pichu', 'french_name' => 'Pichu', 'national_dex_number' => 172, 'regional_dex_number' => null, 'simplified_name' => 'Pichu', 'forms_label' => '', 'simplified_french_name' => 'Pichu', 'forms_french_label' => '', 'icon' => 'pichu', 'family_order' => 0, 'family_lead' => null, 'original_game_bundle' => ['slug' => 'goldsilvercrystal'], 'order_number' => '9999-0172-000', 'game_bundles' => [['slug' => 'goldsilvercrystal']], 'game_bundles_shiny' => [['slug' => 'goldsilvercrystal']], 'small_regular_credit' => null, 'small_shiny_credit' => null, 'big_regular_credit' => null, 'big_shiny_credit' => null],
                                'forms' => ['category' => null, 'regional' => null, 'special' => null, 'variant' => null],
                                'types' => ['primary' => ['slug' => 'electric', 'name' => 'Electric', 'french_name' => 'Électrik', 'color' => '#F8D030'], 'secondary' => null],
                            ],
                            [
                                'pokemon' => ['slug' => 'raichu', 'name' => 'Raichu', 'french_name' => 'Raichu', 'national_dex_number' => 26, 'regional_dex_number' => null, 'simplified_name' => 'Raichu', 'forms_label' => '', 'simplified_french_name' => 'Raichu', 'forms_french_label' => '', 'icon' => 'raichu', 'family_order' => 2, 'family_lead' => ['slug' => 'pichu'], 'original_game_bundle' => ['slug' => 'redgreenblueyellow'], 'order_number' => '9999-0026-002', 'game_bundles' => [['slug' => 'redgreenblueyellow']], 'game_bundles_shiny' => [['slug' => 'redgreenblueyellow']], 'small_regular_credit' => null, 'small_shiny_credit' => null, 'big_regular_credit' => null, 'big_shiny_credit' => null],
                                'forms' => ['category' => null, 'regional' => null, 'special' => null, 'variant' => null],
                                'types' => ['primary' => ['slug' => 'electric', 'name' => 'Electric', 'french_name' => 'Électrik', 'color' => '#F8D030'], 'secondary' => null],
                            ],
                        ],
                    ]
                )
            )
        ;

        $service = new GetPokemonsListService($userTokenService, $getPokemonsService, 12);
        $list = $service->get('douze', '', ['at' => ['poison', 'fire'], 'cf' => ['legendary']]);

        $this->assertSame('pick', $list->type);
        $this->assertCount(2, $list->items);
        $this->assertSame('pichu', $list->items[0]['pokemon']['slug']);
        $this->assertSame('raichu', $list->items[1]['pokemon']['slug']);
    }
}
