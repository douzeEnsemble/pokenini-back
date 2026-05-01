<?php

declare(strict_types=1);

namespace App\Tests\Unit\AlbumFilters;

use App\AlbumFilters\FromRequest;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
#[CoversClass(FromRequest::class)]
final class FromRequestTest extends TestCase
{
    public function testGet(): void
    {
        $request = new Request($this->getTestGetInput());

        $this->assertEquals(
            FromRequest::get($request),
            $this->getTestGetExpected(),
        );
    }

    public function testGetWithNegatives(): void
    {
        $request = new Request([
            'catch_states' => '!no',
            'families' => 'pichu',
            'original_game_bundles' => [
                'ogb1',
                'ogb2',
            ],
            'game_bundle_availabilities' => [
                'gba1',
                'gba2',
                '!gba3',
            ],
            'game_bundle_shiny_availabilities' => [
                'gbsa1',
                'gbsa2',
                '!gbsa3',
            ],
            'collection_availabilities' => [
                'ca1',
                '!ca2',
            ],
        ]);

        $expectedData = [
            'catch_states' => [
                '!no',
            ],
            'families' => [
                'pichu',
            ],
            'original_game_bundles' => [
                'ogb1',
                'ogb2',
            ],
            'game_bundle_availabilities' => [
                'gba1',
                'gba2',
                '!gba3',
            ],
            'game_bundle_shiny_availabilities' => [
                'gbsa1',
                'gbsa2',
                '!gbsa3',
            ],
            'collection_availabilities' => [
                'ca1',
                '!ca2',
            ],
        ];

        $this->assertEquals(
            FromRequest::get($request),
            $expectedData,
        );
    }

    /**
     * @return null[][]|string[]|string[][]
     */
    private static function getTestGetInput(): array
    {
        return [
            'catch_states' => [
                'no',
            ],
            'families' => 'pichu',
            'category_forms' => [
                'cat1',
                'cat2',
                null,
            ],
            'regional_forms' => [
                'reg1',
                'reg2',
            ],
            'special_forms' => [
                'spe1',
                'spe2',
            ],
            'variant_forms' => [
                'var1',
                'var2',
                null,
            ],
            'any_types' => [
                'typ-a.1',
                'type-a.2',
            ],
            'primary_types' => [
                'typ1.1',
                'type1.2',
            ],
            'secondary_types' => [
                'typ2.1',
                'type2.2',
            ],
            'original_game_bundles' => [
                'ogb1',
                'ogb2',
            ],
            'game_bundle_availabilities' => [
                'gba1',
                'gba2',
            ],
            'game_bundle_shiny_availabilities' => [
                'gbsa1',
                'gbsa2',
            ],
            'collection_availabilities' => [
                'ca1',
                'ca2',
            ],
        ];
    }

    /**
     * @return null[][]|string[]|string[][]
     */
    private static function getTestGetExpected(): array
    {
        return [
            'catch_states' => [
                'no',
            ],
            'families' => [
                'pichu',
            ],
            'category_forms' => [
                'cat1',
                'cat2',
            ],
            'regional_forms' => [
                'reg1',
                'reg2',
            ],
            'special_forms' => [
                'spe1',
                'spe2',
            ],
            'variant_forms' => [
                'var1',
                'var2',
            ],
            'any_types' => [
                'typ-a.1',
                'type-a.2',
            ],
            'primary_types' => [
                'typ1.1',
                'type1.2',
            ],
            'secondary_types' => [
                'typ2.1',
                'type2.2',
            ],
            'original_game_bundles' => [
                'ogb1',
                'ogb2',
            ],
            'game_bundle_availabilities' => [
                'gba1',
                'gba2',
            ],
            'game_bundle_shiny_availabilities' => [
                'gbsa1',
                'gbsa2',
            ],
            'collection_availabilities' => [
                'ca1',
                'ca2',
            ],
        ];
    }
}
