<?php

declare(strict_types=1);

namespace App\AlbumFilters;

use Symfony\Component\HttpFoundation\Request;

final class FromRequest
{
    private const array FILTERS = [
        'catch_states',
        'families',
        'category_forms',
        'regional_forms',
        'special_forms',
        'variant_forms',
        'any_types',
        'primary_types',
        'secondary_types',
        'original_game_bundles',
        'game_bundle_availabilities',
        'game_bundle_shiny_availabilities',
        'collection_availabilities',
    ];

    /**
     * @return string[]|string[][]
     */
    public static function get(Request $request): array
    {
        $filters = [];

        foreach (self::FILTERS as $filterName) {
            if ($request->query->has($filterName)) {
                /** @var null|string|string[] $values */
                $values = $request->query->all()[$filterName];
                $values ??= [];

                $values = is_array($values) ? array_filter($values) : [$values];

                $filters[$filterName] = $values;
            }
        }

        return $filters;
    }
}
