<?php

declare(strict_types=1);

namespace App\Tests\Unit\DTO;

use App\DTO\ElectionPokemonsList;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

/**
 * @internal
 */
#[CoversClass(ElectionPokemonsList::class)]
final class ElectionPokemonsListTest extends TestCase
{
    #[Test]
    public function ok(): void
    {
        $items = [
            [
                'pokemon' => [
                    'slug' => 'pichu',
                    'name' => 'Pichu',
                    'french_name' => 'Pichu',
                    'national_dex_number' => 172,
                    'regional_dex_number' => null,
                    'simplified_name' => 'Pichu',
                    'forms_label' => '',
                    'simplified_french_name' => 'Pichu',
                    'forms_french_label' => '',
                    'icon' => 'pichu',
                    'family_order' => 0,
                    'family_lead' => null,
                    'original_game_bundle' => ['slug' => 'goldsilvercrystal'],
                    'order_number' => '9999-0172-000',
                    'game_bundles' => [['slug' => 'goldsilvercrystal']],
                    'game_bundles_shiny' => [['slug' => 'goldsilvercrystal']],
                    'small_regular_credit' => null,
                    'small_shiny_credit' => null,
                    'big_regular_credit' => null,
                    'big_shiny_credit' => null,
                ],
                'forms' => ['category' => null, 'regional' => null, 'special' => null, 'variant' => null],
                'types' => [
                    'primary' => ['slug' => 'electric', 'name' => 'Electric', 'french_name' => 'Électrik', 'color' => '#F8D030'],
                    'secondary' => null,
                ],
            ],
            [
                'pokemon' => [
                    'slug' => 'raichu',
                    'name' => 'Raichu',
                    'french_name' => 'Raichu',
                    'national_dex_number' => 26,
                    'regional_dex_number' => null,
                    'simplified_name' => 'Raichu',
                    'forms_label' => '',
                    'simplified_french_name' => 'Raichu',
                    'forms_french_label' => '',
                    'icon' => 'raichu',
                    'family_order' => 2,
                    'family_lead' => ['slug' => 'pichu'],
                    'original_game_bundle' => ['slug' => 'redgreenblueyellow'],
                    'order_number' => '9999-0026-002',
                    'game_bundles' => [['slug' => 'redgreenblueyellow']],
                    'game_bundles_shiny' => [['slug' => 'redgreenblueyellow']],
                    'small_regular_credit' => null,
                    'small_shiny_credit' => null,
                    'big_regular_credit' => null,
                    'big_shiny_credit' => null,
                ],
                'forms' => ['category' => null, 'regional' => null, 'special' => null, 'variant' => null],
                'types' => [
                    'primary' => ['slug' => 'electric', 'name' => 'Electric', 'french_name' => 'Électrik', 'color' => '#F8D030'],
                    'secondary' => null,
                ],
            ],
        ];

        $object = new ElectionPokemonsList([
            'type' => 'pick',
            'items' => $items,
        ]);

        $this->assertSame('pick', $object->type);
        $this->assertSame($items, $object->items);
    }

    #[Test]
    public function missingType(): void
    {
        $this->expectException(MissingOptionsException::class);

        /**
         * @psalm-suppress InvalidArgument
         *
         * @phpstan-ignore argument.type
         */
        new ElectionPokemonsList([
            'items' => [
                ['pokemon' => ['slug' => 'pichu'], 'forms' => [], 'types' => []],
            ],
        ]);
    }

    #[Test]
    public function wrongType(): void
    {
        $this->expectException(InvalidOptionsException::class);

        /**
         * @psalm-suppress InvalidArgument
         *
         * @phpstan-ignore argument.type
         */
        new ElectionPokemonsList([
            'type' => 12,
            'items' => [
                ['pokemon' => ['slug' => 'pichu'], 'forms' => [], 'types' => []],
            ],
        ]);
    }

    #[Test]
    public function missingItems(): void
    {
        $this->expectException(MissingOptionsException::class);

        /**
         * @psalm-suppress InvalidArgument
         *
         * @phpstan-ignore argument.type
         */
        new ElectionPokemonsList([
            'type' => 'pick',
        ]);
    }

    #[Test]
    public function wrongItems(): void
    {
        $this->expectException(InvalidOptionsException::class);

        /**
         * @psalm-suppress InvalidArgument
         *
         * @phpstan-ignore argument.type
         */
        new ElectionPokemonsList([
            'type' => 'pick',
            'items' => [
                'pichu',
                'raichu',
            ],
        ]);
    }
}
