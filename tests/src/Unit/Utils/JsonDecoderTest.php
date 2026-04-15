<?php

declare(strict_types=1);

namespace App\Tests\Unit\Utils;

use App\Utils\JsonDecoder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(JsonDecoder::class)]
final class JsonDecoderTest extends TestCase
{
    /**
     * @param array<array-key, mixed> $expectedData
     */
    #[DataProvider('providerDecode')]
    public function testDecode(string $json, array $expectedData): void
    {
        $this->assertEquals(
            $expectedData,
            JsonDecoder::decode($json)
        );
    }

    /**
     * @return array<int, array{
     *      json: string,
     *      expectedData: array<array-key, mixed>
     * }>
     */
    public static function providerDecode(): array
    {
        return [
            [
                'json' => self::getOneColorJson(),
                'expectedData' => [
                    'color' => '#66bb6a',
                    'name' => 'Yes',
                    'french_name' => 'Oui',
                    'slug' => 'yes',
                ],
            ],
            [
                'json' => '{}',
                'expectedData' => [],
            ],
            [
                'json' => '[]',
                'expectedData' => [],
            ],
            [
                'json' => self::getManyColorsJson(),
                'expectedData' => [
                    [
                        'color' => '#e57373',
                        'name' => 'No',
                        'french_name' => 'Non',
                        'slug' => 'no',
                    ],
                    [
                        'color' => '#9575cd',
                        'name' => 'To evolve',
                        'french_name' => 'af. évoluer',
                        'slug' => 'toevolve',
                    ],
                    [
                        'color' => '#4fc3f7',
                        'name' => 'To breed',
                        'french_name' => 'af. reproduire',
                        'slug' => 'tobreed',
                    ],
                    [
                        'color' => '#ffd54f',
                        'name' => 'To transfer',
                        'french_name' => 'à transférer',
                        'slug' => 'totransfer',
                    ],
                    [
                        'color' => '#ff9100',
                        'name' => 'To trade',
                        'french_name' => 'À échanger',
                        'slug' => 'totrade',
                    ],
                    [
                        'color' => '#66bb6a',
                        'name' => 'Yes',
                        'french_name' => 'Oui',
                        'slug' => 'yes',
                    ],
                ],
            ],
            [
                'json' => self::getMaxDepthJson(),
                'expectedData' => [
                    'lvl1' => [
                        'lvl2' => [
                            'lvl3' => [
                                'value' => 'Douze',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    #[DataProvider('providerDecodeException')]
    public function testDecodeException(string $json): void
    {
        $this->expectException(\JsonException::class);

        JsonDecoder::decode($json);
    }

    /**
     * @return string[][]
     */
    public static function providerDecodeException(): array
    {
        return [
            [''],
            [self::getMaxDepthPlusOneJson()],
        ];
    }

    private static function getOneColorJson(): string
    {
        return <<<'JSON'
            {
                "color":"#66bb6a",
                "name":"Yes",
                "french_name":"Oui",
                "slug":"yes"
            }
            JSON;
    }

    private static function getManyColorsJson(): string
    {
        return <<<'JSON'
            [
                {
                    "color":"#e57373",
                    "name":"No",
                    "french_name":"Non",
                    "slug":"no"
                },
                {
                    "color":"#9575cd",
                    "name":"To evolve",
                    "french_name":"af. \u00e9voluer",
                    "slug":"toevolve"
                },
                {
                    "color":"#4fc3f7",
                    "name":"To breed",
                    "french_name":"af. reproduire",
                    "slug":"tobreed"
                },
                {
                    "color":"#ffd54f",
                    "name":"To transfer",
                    "french_name":"\u00e0 transf\u00e9rer",
                    "slug":"totransfer"
                },
                {
                    "color":"#ff9100",
                    "name":"To trade",
                    "french_name":"\u00c0 \u00e9changer",
                    "slug":"totrade"
                },
                {
                    "color":"#66bb6a",
                    "name":"Yes",
                    "french_name":"Oui",
                    "slug":"yes"
                }
            ]
            JSON;
    }

    private static function getMaxDepthJson(): string
    {
        return <<<'JSON'
            {
                "lvl1": {
                    "lvl2": {
                        "lvl3": {
                            "value": "Douze"
                        }
                    }
                }
            }
            JSON;
    }

    private static function getMaxDepthPlusOneJson(): string
    {
        return <<<'JSON'
            {
                "lvl1": {
                    "lvl2": {
                        "lvl3": {
                            "lvl4": {
                                "value": "Douze"
                            }
                        }
                    }
                }
            }
            JSON;
    }
}
