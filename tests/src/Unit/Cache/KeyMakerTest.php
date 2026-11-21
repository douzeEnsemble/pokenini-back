<?php

declare(strict_types=1);

namespace App\Tests\Unit\Cache;

use App\Cache\KeyMaker;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(KeyMaker::class)]
final class KeyMakerTest extends TestCase
{
    #[Test]
    public function getActionCatalogKey(): void
    {
        $this->assertSame('action_catalog', KeyMaker::getActionCatalogKey());
    }

    #[Test]
    public function getDexKey(): void
    {
        $this->assertSame('dex', KeyMaker::getDexKey());
    }

    #[Test]
    public function getCatchStatesKey(): void
    {
        $this->assertSame('catch_states', KeyMaker::getCatchStatesKey());
    }

    #[Test]
    public function getTypesKey(): void
    {
        $this->assertSame('types', KeyMaker::getTypesKey());
    }

    #[Test]
    public function getGameBundlesKey(): void
    {
        $this->assertSame('game_bundles', KeyMaker::getGameBundlesKey());
    }

    #[Test]
    public function getCollectionsKey(): void
    {
        $this->assertSame('collections', KeyMaker::getCollectionsKey());
    }

    #[Test]
    public function getFormsKey(): void
    {
        $this->assertSame('forms', KeyMaker::getFormsKey());
    }

    #[Test]
    public function getAlbumKey(): void
    {
        $this->assertSame('album', KeyMaker::getAlbumKey());
    }

    #[Test]
    public function getReportsKey(): void
    {
        $this->assertSame('reports', KeyMaker::getReportsKey());
    }

    #[Test]
    public function getGameReportsKey(): void
    {
        $this->assertSame('game_reports', KeyMaker::getGameReportsKey());
    }

    #[Test]
    public function getCreditsKey(): void
    {
        $this->assertSame('credits_v2', KeyMaker::getCreditsKey());
    }

    #[Test]
    public function getDexKeyForTrainer(): void
    {
        $this->assertSame('dex_1', KeyMaker::getDexKeyForTrainer('1'));
        $this->assertSame('dex_12', KeyMaker::getDexKeyForTrainer('12'));
    }

    #[Test]
    public function getDexKeyForTrainerWithQueryParams(): void
    {
        $this->assertSame('dex_1_1=1', KeyMaker::getDexKeyForTrainer('1', ['1' => '1']));
        $this->assertSame('dex_12_1=1_2=2', KeyMaker::getDexKeyForTrainer('12', ['1' => '1', '2' => '2']));
    }

    #[Test]
    public function getElectionDexListKey(): void
    {
        $this->assertSame('election_dex_list_v2', KeyMaker::getElectionDexListKey());
    }

    #[Test]
    public function getElectionDexListKeyWithQueryParams(): void
    {
        $this->assertSame('election_dex_list_v2_1=1', KeyMaker::getElectionDexListKey(['1' => '1']));
        $this->assertSame('election_dex_list_v2_1=1_2=2', KeyMaker::getElectionDexListKey(['1' => '1', '2' => '2']));
    }

    #[Test]
    public function getElectionDexListKeyForTrainer(): void
    {
        $this->assertSame('election_dex_list_v2_1', KeyMaker::getElectionDexListKeyForTrainer('1'));
        $this->assertSame('election_dex_list_v2_12', KeyMaker::getElectionDexListKeyForTrainer('12'));
    }

    #[Test]
    public function getElectionDexListKeyForTrainerWithQueryParams(): void
    {
        $this->assertSame('election_dex_list_v2_1_1=1', KeyMaker::getElectionDexListKeyForTrainer('1', ['1' => '1']));
        $this->assertSame(
            'election_dex_list_v2_12_1=1_2=2',
            KeyMaker::getElectionDexListKeyForTrainer('12', ['1' => '1', '2' => '2']),
        );
    }

    #[Test]
    public function getPokedexKey(): void
    {
        $this->assertSame('album_douze_12', KeyMaker::getPokedexKey('douze', '12'));
        $this->assertSame('album_toto_0', KeyMaker::getPokedexKey('toto', '0'));
        $this->assertSame(
            'album_toto_0_csno_fpichu',
            KeyMaker::getPokedexKey(
                'toto',
                '0',
                [
                    'cs' => 'no',
                    'f' => 'pichu',
                ],
            )
        );
        $this->assertSame(
            'album_toto_0_fcun_fcdos_fctres',
            KeyMaker::getPokedexKey(
                'toto',
                '0',
                [
                    'fc' => [
                        'un',
                        'dos',
                        'tres',
                    ],
                ],
            )
        );
        $this->assertSame(
            'album_toto_0_fcun_fcdos_fctres_t1normal_t1water',
            KeyMaker::getPokedexKey(
                'toto',
                '0',
                [
                    'fc' => [
                        'un',
                        'dos',
                        'tres',
                    ],
                    't1' => [
                        'normal',
                        'water',
                    ],
                ],
            )
        );
    }

    #[Test]
    public function getTrainerIdKey(): void
    {
        $this->assertSame('trainer#123', KeyMaker::getTrainerIdKey('123'));
        $this->assertSame('trainer#1654da64faeg54a6f4a8', KeyMaker::getTrainerIdKey('1654da64faeg54a6f4a8'));
    }

    #[Test]
    public function getGamesKeyForTrainer(): void
    {
        $this->assertSame('games_color_game_8800088', KeyMaker::getGamesKeyForTrainer('color_game', '8800088'));
    }

    #[DataProvider('providerLabelsKeysByLanguage')]
    #[Test]
    public function labelsKeysByLanguage(string $method, string $legacy): void
    {
        $this->assertSame($legacy, KeyMaker::$method());
        $this->assertSame($legacy, KeyMaker::$method(null));
        $this->assertSame("{$legacy}_fr", KeyMaker::$method('fr'));
        $this->assertSame("{$legacy}_en", KeyMaker::$method('en'));
    }

    /**
     * @return array<string, array{method: string, legacy: string}>
     */
    public static function providerLabelsKeysByLanguage(): array
    {
        return [
            'catch states' => ['method' => 'getCatchStatesKey', 'legacy' => 'catch_states'],
            'types' => ['method' => 'getTypesKey', 'legacy' => 'types'],
            'game bundles' => ['method' => 'getGameBundlesKey', 'legacy' => 'game_bundles'],
            'collections' => ['method' => 'getCollectionsKey', 'legacy' => 'collections'],
            'forms' => ['method' => 'getFormsKey', 'legacy' => 'forms'],
            'credits' => ['method' => 'getCreditsKey', 'legacy' => 'credits_v2'],
        ];
    }

    #[Test]
    public function getPokedexKeyInALanguage(): void
    {
        $this->assertSame('album_douze_12', KeyMaker::getPokedexKey('douze', '12', [], null));
        $this->assertSame('album_douze_12_fr', KeyMaker::getPokedexKey('douze', '12', [], 'fr'));
        $this->assertSame(
            'album_toto_0_csno_en',
            KeyMaker::getPokedexKey('toto', '0', ['cs' => 'no'], 'en'),
        );
    }

    #[Test]
    public function getElectionDexListKeyForTrainerInALanguage(): void
    {
        $this->assertSame('election_dex_list_v2_1_fr', KeyMaker::getElectionDexListKeyForTrainer('1', [], 'fr'));
        $this->assertSame('election_dex_list_v2_1_1=1_en', KeyMaker::getElectionDexListKeyForTrainer('1', ['1' => '1'], 'en'));
    }
}
