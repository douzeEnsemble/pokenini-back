<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\OptionsResolver\OptionsResolver;

final class ElectionPokemonsList
{
    public string $type;

    /**
     * @var array<int, array{
     *   pokemon: array{slug: string, name: string, french_name: string, national_dex_number: int, regional_dex_number: null|int, simplified_name: string, forms_label: string, simplified_french_name: string, forms_french_label: string, icon: string, family_order: int, family_lead: null|array{slug: string}, original_game_bundle: array{slug: string}, order_number: string, game_bundles: array<int, array{slug: string}>, game_bundles_shiny: array<int, array{slug: string}>, small_regular_credit: null|array{credit: string}, small_shiny_credit: null|array{credit: string}, big_regular_credit: null|array{credit: string}, big_shiny_credit: null|array{credit: string}},
     *   forms: array{category: null|array{slug: string, name: string, french_name: string}, regional: null|array{slug: string, name: string, french_name: string}, special: null|array{slug: string, name: string, french_name: string}, variant: null|array{slug: string, name: string, french_name: string}},
     *   types: array{primary: array{slug: string, name: string, french_name: string, color: string}, secondary: null|array{slug: string, name: string, french_name: string, color: string}},
     * }>
     */
    public array $items;

    /**
     * @param array{type: string, items: array<int, array{pokemon: array{slug: string, name: string, french_name: string, national_dex_number: int, regional_dex_number: null|int, simplified_name: string, forms_label: string, simplified_french_name: string, forms_french_label: string, icon: string, family_order: int, family_lead: null|array{slug: string}, original_game_bundle: array{slug: string}, order_number: string, game_bundles: array<int, array{slug: string}>, game_bundles_shiny: array<int, array{slug: string}>, small_regular_credit: null|array{credit: string}, small_shiny_credit: null|array{credit: string}, big_regular_credit: null|array{credit: string}, big_shiny_credit: null|array{credit: string}}, forms: array{category: null|array{slug: string, name: string, french_name: string}, regional: null|array{slug: string, name: string, french_name: string}, special: null|array{slug: string, name: string, french_name: string}, variant: null|array{slug: string, name: string, french_name: string}}, types: array{primary: array{slug: string, name: string, french_name: string, color: string}, secondary: null|array{slug: string, name: string, french_name: string, color: string}}}>} $values
     */
    public function __construct(array $values)
    {
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);

        /** @var array{type: string, items: array<int, array{pokemon: array{slug: string, name: string, french_name: string, national_dex_number: int, regional_dex_number: null|int, simplified_name: string, forms_label: string, simplified_french_name: string, forms_french_label: string, icon: string, family_order: int, family_lead: null|array{slug: string}, original_game_bundle: array{slug: string}, order_number: string, game_bundles: array<int, array{slug: string}>, game_bundles_shiny: array<int, array{slug: string}>, small_regular_credit: null|array{credit: string}, small_shiny_credit: null|array{credit: string}, big_regular_credit: null|array{credit: string}, big_shiny_credit: null|array{credit: string}}, forms: array{category: null|array{slug: string, name: string, french_name: string}, regional: null|array{slug: string, name: string, french_name: string}, special: null|array{slug: string, name: string, french_name: string}, variant: null|array{slug: string, name: string, french_name: string}}, types: array{primary: array{slug: string, name: string, french_name: string, color: string}, secondary: null|array{slug: string, name: string, french_name: string, color: string}}}>} $options */
        $options = $resolver->resolve($values);

        $this->type = $options['type'];
        $this->items = $options['items'];
    }

    private function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired('type');
        $resolver->setAllowedTypes('type', 'string');

        $resolver->setRequired('items');
        $resolver->setAllowedTypes('items', 'array[]');
    }
}
