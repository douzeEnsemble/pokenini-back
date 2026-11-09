<?php

declare(strict_types=1);

namespace App\Cache;

final class KeyMaker
{
    private const string CACHE_KEY_SEPARATOR = '_';
    private const string CACHE_KEY_ID_SEPARATOR = '#';

    private const string CACHE_KEY_DEX = 'dex';
    private const string CACHE_KEY_ELECTION_DEX_LIST = 'election_dex_list_v2';
    private const string CACHE_KEY_CATCH_STATES = 'catch_states';
    private const string CACHE_KEY_TYPES = 'types';
    private const string CACHE_KEY_GAME_BUNDLES = 'game_bundles';
    private const string CACHE_KEY_COLLECTIONS = 'collections';
    private const string CACHE_KEY_FORMS = 'forms';
    private const string CACHE_KEY_ALBUM = 'album';
    private const string CACHE_KEY_REPORTS = 'reports';
    private const string CACHE_KEY_GAME_REPORTS = 'game_reports';
    private const string CACHE_KEY_TRAINER = 'trainer';
    private const string CACHE_KEY_CREDITS = 'credits_v2';
    private const string CACHE_KEY_GAMES = 'games';

    public static function getDexKey(): string
    {
        return self::CACHE_KEY_DEX;
    }

    /**
     * @param null|string $lang the language of the names, null for the legacy ones (English and French)
     */
    public static function getCatchStatesKey(?string $lang = null): string
    {
        return self::withLang(self::CACHE_KEY_CATCH_STATES, $lang);
    }

    /**
     * @param null|string $lang the language of the names, null for the legacy ones (English and French)
     */
    public static function getTypesKey(?string $lang = null): string
    {
        return self::withLang(self::CACHE_KEY_TYPES, $lang);
    }

    /**
     * @param null|string $lang the language of the names, null for the legacy ones (English and French)
     */
    public static function getGameBundlesKey(?string $lang = null): string
    {
        return self::withLang(self::CACHE_KEY_GAME_BUNDLES, $lang);
    }

    /**
     * @param null|string $lang the language of the names, null for the legacy ones (English and French)
     */
    public static function getCollectionsKey(?string $lang = null): string
    {
        return self::withLang(self::CACHE_KEY_COLLECTIONS, $lang);
    }

    /**
     * @param null|string $lang the language of the names, null for the legacy ones (English and French)
     */
    public static function getFormsKey(?string $lang = null): string
    {
        return self::withLang(self::CACHE_KEY_FORMS, $lang);
    }

    public static function getAlbumKey(): string
    {
        return self::CACHE_KEY_ALBUM;
    }

    public static function getReportsKey(): string
    {
        return self::CACHE_KEY_REPORTS;
    }

    public static function getGameReportsKey(): string
    {
        return self::CACHE_KEY_GAME_REPORTS;
    }

    /**
     * @param null|string $lang the language of the names, null for the legacy ones (English and French)
     */
    public static function getCreditsKey(?string $lang = null): string
    {
        return self::withLang(self::CACHE_KEY_CREDITS, $lang);
    }

    /**
     * @param string[] $queryParams
     */
    public static function getDexKeyForTrainer(string $trainerId, array $queryParams = []): string
    {
        $cacheKeySuffixe = http_build_query($queryParams, '', self::CACHE_KEY_SEPARATOR);

        return self::CACHE_KEY_DEX.self::CACHE_KEY_SEPARATOR.$trainerId
            .($cacheKeySuffixe ? self::CACHE_KEY_SEPARATOR.$cacheKeySuffixe : '');
    }

    /**
     * @param string[] $queryParams
     */
    public static function getElectionDexListKey(array $queryParams = []): string
    {
        $cacheKeySuffixe = http_build_query($queryParams, '', self::CACHE_KEY_SEPARATOR);

        return self::CACHE_KEY_ELECTION_DEX_LIST.($cacheKeySuffixe ? self::CACHE_KEY_SEPARATOR.$cacheKeySuffixe : '');
    }

    /**
     * @param string[] $queryParams
     */
    public static function getElectionDexListKeyForTrainer(string $trainerId, array $queryParams = []): string
    {
        $cacheKeySuffixe = http_build_query($queryParams, '', self::CACHE_KEY_SEPARATOR);

        return self::CACHE_KEY_ELECTION_DEX_LIST.self::CACHE_KEY_SEPARATOR.$trainerId
            .($cacheKeySuffixe ? self::CACHE_KEY_SEPARATOR.$cacheKeySuffixe : '');
    }

    /**
     * @param string[]|string[][] $filters
     * @param null|string         $lang    the language of the names, null for the legacy ones (English and French)
     */
    public static function getPokedexKey(string $dexSlug, string $trainerId, array $filters = [], ?string $lang = null): string
    {
        $prefix = self::CACHE_KEY_ALBUM
            .self::CACHE_KEY_SEPARATOR.$dexSlug
            .self::CACHE_KEY_SEPARATOR.$trainerId;

        $strFilters = '';
        foreach ($filters as $key => $value) {
            if (!is_array($value)) {
                $strFilters .= self::CACHE_KEY_SEPARATOR.$key.$value;

                continue;
            }

            foreach ($value as $subValue) {
                $strFilters .= self::CACHE_KEY_SEPARATOR.$key.$subValue;
            }
        }

        return self::withLang($prefix.$strFilters, $lang);
    }

    /**
     * The key of the games of a trainer, which is also the tag of its language variants.
     *
     * @param null|string $lang the language of the names, null for the legacy ones (English and French)
     */
    public static function getGamesKeyForTrainer(string $game, string $trainerId, ?string $lang = null): string
    {
        return self::withLang(
            self::CACHE_KEY_GAMES
            .self::CACHE_KEY_SEPARATOR.$game
            .self::CACHE_KEY_SEPARATOR.$trainerId,
            $lang,
        );
    }

    public static function getTrainerIdKey(string $trainerId): string
    {
        return self::CACHE_KEY_TRAINER.self::CACHE_KEY_ID_SEPARATOR.$trainerId;
    }

    private static function withLang(string $key, ?string $lang): string
    {
        return null === $lang ? $key : $key.self::CACHE_KEY_SEPARATOR.$lang;
    }
}
