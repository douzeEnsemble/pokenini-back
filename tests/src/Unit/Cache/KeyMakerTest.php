<?php

declare(strict_types=1);

namespace App\Tests\Unit\Cache;

use App\Cache\KeyMaker;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(KeyMaker::class)]
final class KeyMakerTest extends TestCase
{
    #[Test]
    public function getDexKey(): void
    {
        $this->assertEquals('dex', KeyMaker::getDexKey());
    }

    #[Test]
    public function getCatchStatesKey(): void
    {
        $this->assertEquals('catch_states', KeyMaker::getCatchStatesKey());
    }

    #[Test]
    public function getTypesKey(): void
    {
        $this->assertEquals('types', KeyMaker::getTypesKey());
    }

    #[Test]
    public function getGameBundlesKey(): void
    {
        $this->assertEquals('game_bundles', KeyMaker::getGameBundlesKey());
    }

    #[Test]
    public function getCollectionsKey(): void
    {
        $this->assertEquals('collections', KeyMaker::getCollectionsKey());
    }

    #[Test]
    public function getFormsKey(): void
    {
        $this->assertSame('forms', KeyMaker::getFormsKey());
    }

    #[Test]
    public function getAlbumKey(): void
    {
        $this->assertEquals('album', KeyMaker::getAlbumKey());
    }

    #[Test]
    public function getReportsKey(): void
    {
        $this->assertEquals('reports', KeyMaker::getReportsKey());
    }

    #[Test]
    public function getCreditsKey(): void
    {
        $this->assertEquals('credits_v2', KeyMaker::getCreditsKey());
    }

    #[Test]
    public function getDexKeyForTrainer(): void
    {
        $this->assertEquals('dex_1', KeyMaker::getDexKeyForTrainer('1'));
        $this->assertEquals('dex_12', KeyMaker::getDexKeyForTrainer('12'));
    }

    #[Test]
    public function getDexKeyForTrainerWithQueryParams(): void
    {
        $this->assertEquals('dex_1_1=1', KeyMaker::getDexKeyForTrainer('1', ['1' => '1']));
        $this->assertEquals('dex_12_1=1_2=2', KeyMaker::getDexKeyForTrainer('12', ['1' => '1', '2' => '2']));
    }

    #[Test]
    public function getElectionDexListKey(): void
    {
        $this->assertEquals('election_dex_list', KeyMaker::getElectionDexListKey());
    }

    #[Test]
    public function getElectionDexListKeyWithQueryParams(): void
    {
        $this->assertEquals('election_dex_list_1=1', KeyMaker::getElectionDexListKey(['1' => '1']));
        $this->assertEquals('election_dex_list_1=1_2=2', KeyMaker::getElectionDexListKey(['1' => '1', '2' => '2']));
    }

    #[Test]
    public function getElectionDexListKeyForTrainer(): void
    {
        $this->assertEquals('election_dex_list_1', KeyMaker::getElectionDexListKeyForTrainer('1'));
        $this->assertEquals('election_dex_list_12', KeyMaker::getElectionDexListKeyForTrainer('12'));
    }

    #[Test]
    public function getElectionDexListKeyForTrainerWithQueryParams(): void
    {
        $this->assertEquals('election_dex_list_1_1=1', KeyMaker::getElectionDexListKeyForTrainer('1', ['1' => '1']));
        $this->assertEquals(
            'election_dex_list_12_1=1_2=2',
            KeyMaker::getElectionDexListKeyForTrainer('12', ['1' => '1', '2' => '2']),
        );
    }

    #[Test]
    public function getPokedexKey(): void
    {
        $this->assertEquals('album_douze_12', KeyMaker::getPokedexKey('douze', '12'));
        $this->assertEquals('album_toto_0', KeyMaker::getPokedexKey('toto', '0'));
        $this->assertEquals(
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
        $this->assertEquals(
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
        $this->assertEquals(
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
        $this->assertEquals('trainer#123', KeyMaker::getTrainerIdKey('123'));
        $this->assertEquals('trainer#1654da64faeg54a6f4a8', KeyMaker::getTrainerIdKey('1654da64faeg54a6f4a8'));
    }
}
