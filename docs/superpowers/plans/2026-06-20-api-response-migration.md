# API Response Migration Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Adapter pokenini-back aux breaking changes introduits par `pokenini-api/feature/refactoring_responses` : 5 breaking changes sur les endpoints consommés.

**Architecture:** pokenini-back est un BFF pur pass-through — aucune DB, toute la logique métier est dans l'API. Les changements se concentrent sur (1) les services API qui parsent les réponses, (2) les DTOs qui modélisent les structures reçues, et (3) toutes les fixtures de test (unit + moco + snapshots d'intégration). Aucune couche Controller n'est affectée sauf l'accès à `election/top` qui lit `significance` directement.

**Tech Stack:** PHP 8.5, Symfony 8.1, PHPUnit, Moco (mock HTTP), OptionsResolver, TagAwareAdapter (cache APCu/Redis).

## Global Constraints

- `declare(strict_types=1)` dans tous les fichiers PHP
- Classes `final` pour tous les DTOs et tests
- PHPStan niveau 9 + Psalm strict : tout phpDoc mis à jour
- `make quality` et `make tests` doivent être verts à la fin de chaque tâche
- Les tests s'exécutent via `docker compose exec php php vendor/bin/phpunit` (depuis `/home/renaud/projects/pokenini-back`)
- Aucun commit n'est créé

---

## Périmètre des 5 breaking changes

| # | Endpoint | Nature du changement | Impact back |
|---|----------|---------------------|-------------|
| 1 | `GET /forms/*` | 4 endpoints → 1 seul `GET /forms` | Service + cache + fixtures |
| 2 | `GET /action_logs` | Objet dynamique → tableau avec `action_type` | Service + fixtures |
| 3 | `GET /election/metrics` | `under_max_view_count`/`max_view_count` → `completion.under_max_count`/`completion.at_max_count` | Service + DTO + fixtures |
| 4 | `POST /election/vote` | `trainer_external_id` → `trainer.external_id` dans le body | Service + fixtures |
| 5 | `GET /election/top` | Structure plate → imbriquée (pokemon, forms, types, score) | Controller (lit `significance`) + fixtures |

Non-breaking (fixtures à mettre à jour si désiré, optionnel) : `game_bundles` (generation imbriqué), `reports` (slugs ajoutés).

---

## Fichiers modifiés par tâche

### Tâche 1 — /forms
- Modify: `src/Service/Api/GetFormsApiService.php`
- Modify: `src/Cache/KeyMaker.php`
- Modify: `src/Service/CacheInvalidator/FormsCacheInvalidatorService.php`
- Modify: `tests/src/Unit/Service/Api/GetFormsApiServiceTest.php`
- Modify: `tests/src/Unit/Cache/KeyMakerTest.php`
- Modify: `tests/src/Unit/Service/CacheInvalidator/FormsCacheInvalidatorServiceTest.php`
- Create: `tests/resources/unit/service/api/forms.json`
- Delete: `tests/resources/unit/service/api/category_forms.json`
- Delete: `tests/resources/unit/service/api/regional_forms.json`
- Delete: `tests/resources/unit/service/api/special_forms.json`
- Delete: `tests/resources/unit/service/api/variant_forms.json`
- Create: `tests/resources/moco/Api/responses/forms.json`
- Delete: `tests/resources/moco/Api/responses/category_forms.json`
- Delete: `tests/resources/moco/Api/responses/regional_forms.json`
- Delete: `tests/resources/moco/Api/responses/special_forms.json`
- Delete: `tests/resources/moco/Api/responses/variant_forms.json`
- Modify: `tests/resources/moco/Api/moco.json` (4 entrées → 1)

### Tâche 2 — /action_logs
- Modify: `src/Service/Api/GetActionLogsApiService.php`
- Modify: `tests/src/Unit/Service/Api/GetActionLogsApiServiceTest.php`
- Modify: `tests/resources/unit/service/api/action_logs.json`
- Modify: `tests/resources/moco/Api/responses/action_logs.json`
- Modify: `tests/resources/functional/controller/Admin/action-logs.json`

### Tâche 3 — /election/metrics
- Modify: `src/Service/Api/GetElectionMetricsApiService.php`
- Modify: `src/DTO/ElectionMetrics.php`
- Modify: `tests/src/Unit/DTO/ElectionMetricsTest.php`
- Modify: `tests/src/Unit/Service/Api/GetElectionMetricsApiServiceTest.php`
- Modify: `tests/resources/unit/service/api/election_metrics_4564650_home_fav.json`
- Modify: `tests/resources/unit/service/api/election_metrics_87654_demo_pref.json`
- (plus les variantes avec filtres si différentes)
- Modify: `tests/resources/moco/Api/responses/election/demolite_metrics.json`
- Modify: `tests/resources/moco/Api/responses/election/demolitelastone_metrics.json`
- Modify: `tests/resources/moco/Api/responses/election/demolitelastpage_metrics.json`
- Modify: `tests/resources/moco/Api/responses/election/demolitenotlastone_metrics.json`
- Modify: `tests/resources/moco/Api/responses/election/demolitenotlastpage_metrics.json`
- Modify: `tests/resources/functional/controller/ElectionIndex/demolite.json` (champ `metrics`)
- Modify: `tests/resources/functional/controller/ElectionIndex/demolitelastone.json`
- Modify: `tests/resources/functional/controller/ElectionIndex/demolitelastpage.json`
- Modify: `tests/resources/functional/controller/ElectionIndex/demolitenotlastone.json`
- Modify: `tests/resources/functional/controller/ElectionIndex/demolitenotlastpage.json`

### Tâche 4 — /election/vote
- Modify: `src/Service/Api/ModifyElectionVoteApiService.php`
- Modify: `tests/src/Unit/Service/Api/ModifyElectionVoteApiServiceTest.php`

### Tâche 5 — /election/top
- Modify: `src/Controller/Election/ElectionIndexController.php`
- Modify: `tests/resources/unit/service/api/election_top_5_4564650_home_fav.json`
- Modify: `tests/resources/unit/service/api/election_top_10_87654_demo_pref.json`
- Modify: `tests/resources/moco/Api/responses/election/demolite_top_5.json`
- Modify: `tests/resources/functional/controller/ElectionIndex/demolite.json` (champ `election_top`)
- Modify: `tests/resources/functional/controller/ElectionIndex/demolitelastone.json`
- Modify: `tests/resources/functional/controller/ElectionIndex/demolitelastpage.json`
- Modify: `tests/resources/functional/controller/ElectionIndex/demolitenotlastone.json`
- Modify: `tests/resources/functional/controller/ElectionIndex/demolitenotlastpage.json`

---

## Task 1 — Consolidation GET /forms (4 endpoints → 1)

**Files:**
- Modify: `src/Service/Api/GetFormsApiService.php`
- Modify: `src/Cache/KeyMaker.php`
- Modify: `src/Service/CacheInvalidator/FormsCacheInvalidatorService.php`
- Modify: `tests/src/Unit/Service/Api/GetFormsApiServiceTest.php`
- Modify: `tests/src/Unit/Cache/KeyMakerTest.php`
- Modify: `tests/src/Unit/Service/CacheInvalidator/FormsCacheInvalidatorServiceTest.php`
- Create: `tests/resources/unit/service/api/forms.json`
- Delete 4 unit fixtures, create 1 moco fixture, delete 4 moco fixtures, modify `moco.json`

**Interfaces:**
- `GetFormsApiService::getFormsCategory/Regional/Special/Variant()` : signatures inchangées (retournent toujours `string[][]`)
- `KeyMaker::getFormsKey() : string` — remplace les 4 méthodes individuelles

- [x] **Étape 1 — Créer la fixture unit unifiée**

Créer `tests/resources/unit/service/api/forms.json` :
```json
{
  "category": [
    { "slug": "starter", "name": "Starter", "french_name": "de Départ" },
    { "slug": "legendary", "name": "Legendary", "french_name": "Légendaire" }
  ],
  "regional": [
    { "slug": "alolan", "name": "Alolan", "french_name": "d'Alola" },
    { "slug": "galarian", "name": "Galarian", "french_name": "de Galar" }
  ],
  "special": [
    { "slug": "mega", "name": "Mega", "french_name": "Mega" },
    { "slug": "primal", "name": "Primal", "french_name": "Originelle" }
  ],
  "variant": [
    { "slug": "gender", "name": "Gender", "french_name": "Genre" },
    { "slug": "alternate", "name": "Alternate", "french_name": "Alternatif" },
    { "slug": "therian", "name": "Therian", "french_name": "Totémique" }
  ]
}
```
(Les valeurs doivent correspondre exactement à ce que les tests `testGetFormsCategory` etc. vérifient actuellement — voir `GetFormsApiServiceTest.php`.)

- [x] **Étape 2 — Mettre à jour `KeyMaker.php`**

Remplacer les 4 constantes et méthodes de forms par une seule :

```php
// Remplacer :
private const string CACHE_KEY_FORMS_CATEGORY = 'forms_category';
private const string CACHE_KEY_FORMS_REGIONAL = 'forms_regional';
private const string CACHE_KEY_FORMS_SPECIAL = 'forms_special';
private const string CACHE_KEY_FORMS_VARIANT = 'forms_variant';
// ...
public static function getFormsCategoryKey(): string { ... }
public static function getFormsRegionalKey(): string { ... }
public static function getFormsSpecialKey(): string { ... }
public static function getFormsVariantKey(): string { ... }

// Par :
private const string CACHE_KEY_FORMS = 'forms';
// ...
public static function getFormsKey(): string
{
    return self::CACHE_KEY_FORMS;
}
```

- [x] **Étape 3 — Mettre à jour `GetFormsApiService.php`**

```php
<?php

declare(strict_types=1);

namespace App\Service\Api;

use App\Cache\KeyMaker;
use App\Utils\JsonDecoder;

class GetFormsApiService extends AbstractApiService
{
    /**
     * @return string[][]
     */
    public function getFormsCategory(): array
    {
        /** @var string[][] */
        return $this->getForms()['category'];
    }

    /**
     * @return string[][]
     */
    public function getFormsRegional(): array
    {
        /** @var string[][] */
        return $this->getForms()['regional'];
    }

    /**
     * @return string[][]
     */
    public function getFormsSpecial(): array
    {
        /** @var string[][] */
        return $this->getForms()['special'];
    }

    /**
     * @return string[][]
     */
    public function getFormsVariant(): array
    {
        /** @var string[][] */
        return $this->getForms()['variant'];
    }

    /**
     * @return array{category: string[][], regional: string[][], special: string[][], variant: string[][]}
     */
    private function getForms(): array
    {
        $json = $this->cache->get(KeyMaker::getFormsKey(), function () {
            return $this->requestContent('GET', '/forms');
        });

        /** @var array{category: string[][], regional: string[][], special: string[][], variant: string[][]} */
        return JsonDecoder::decode($json);
    }
}
```

- [x] **Étape 4 — Mettre à jour `FormsCacheInvalidatorService.php`**

```php
#[\Override]
public function invalidate(): void
{
    $this->cache->delete(KeyMaker::getFormsKey());
}
```

- [x] **Étape 5 — Mettre à jour `GetFormsApiServiceTest.php`**

Le test doit maintenant :
- Attendre **un seul** appel HTTP vers `https://api.domain/forms` (sans type dans l'URL)
- Charger le fichier `forms.json` (et non plus `{type}_forms.json`)
- Vérifier que chaque getter retourne le bon sous-tableau

```php
public function testGetFormsCategory(): void
{
    $expectedResult = [
        ['slug' => 'starter', 'name' => 'Starter', 'french_name' => 'de Départ'],
        ['slug' => 'legendary', 'name' => 'Legendary', 'french_name' => 'Légendaire'],
    ];

    $this->assertEquals($expectedResult, $this->getService()->getFormsCategory());

    /** @var string $value */
    $value = $this->cache->getItem('forms')->get();
    $this->assertNotNull($value);
}

public function testGetFormsRegional(): void
{
    $expectedResult = [
        ['slug' => 'alolan', 'name' => 'Alolan', 'french_name' => "d'Alola"],
        ['slug' => 'galarian', 'name' => 'Galarian', 'french_name' => 'de Galar'],
    ];

    $this->assertEquals($expectedResult, $this->getService()->getFormsRegional());
}

public function testGetFormsSpecial(): void
{
    $expectedResult = [
        ['slug' => 'mega', 'name' => 'Mega', 'french_name' => 'Mega'],
        ['slug' => 'primal', 'name' => 'Primal', 'french_name' => 'Originelle'],
    ];

    $this->assertEquals($expectedResult, $this->getService()->getFormsSpecial());
}

public function testGetFormsVariant(): void
{
    $expectedResult = [
        ['slug' => 'gender', 'name' => 'Gender', 'french_name' => 'Genre'],
        ['slug' => 'alternate', 'name' => 'Alternate', 'french_name' => 'Alternatif'],
        ['slug' => 'therian', 'name' => 'Therian', 'french_name' => 'Totémique'],
    ];

    $this->assertEquals($expectedResult, $this->getService()->getFormsVariant());
}

private function getService(): GetFormsApiService
{
    $logger = $this->createMock(LoggerInterface::class);
    $logger->expects($this->exactly(2))->method('info');

    $client = $this->createMock(HttpClientInterface::class);
    $json = (string) file_get_contents('/app/tests/resources/unit/service/api/forms.json');

    $response = $this->createMock(ResponseInterface::class);
    $response->expects($this->once())->method('getContent')->willReturn($json);

    $client
        ->expects($this->once())
        ->method('request')
        ->with(
            'GET',
            'https://api.domain/forms',
            [
                'headers' => ['accept' => 'application/json'],
                'auth_basic' => ['web', 'douze'],
                'cafile' => './resources/certificates/cacert.pem',
            ],
        )
        ->willReturn($response)
    ;

    $this->cachePool = new ArrayAdapter();
    $this->cache = new TagAwareAdapter($this->cachePool, new ArrayAdapter());

    return new GetFormsApiService(
        $logger, $client, 'https://api.domain',
        './resources/certificates/cacert.pem', $this->cache, 'web', 'douze',
    );
}
```

- [x] **Étape 6 — Mettre à jour `KeyMakerTest.php`**

Remplacer les 4 tests `testGetForms*Key()` par un seul `testGetFormsKey()` :
```php
public function testGetFormsKey(): void
{
    $this->assertSame('forms', KeyMaker::getFormsKey());
}
```
Supprimer les tests `testGetFormsCategoryKey`, `testGetFormsRegionalKey`, `testGetFormsSpecialKey`, `testGetFormsVariantKey`.

- [x] **Étape 7 — Mettre à jour `FormsCacheInvalidatorServiceTest.php`**

Adapter pour s'attendre à 1 seul `delete('forms')` au lieu de 4 appels.

- [x] **Étape 8 — Supprimer les 4 anciennes fixtures unit**

```bash
rm tests/resources/unit/service/api/category_forms.json
rm tests/resources/unit/service/api/regional_forms.json
rm tests/resources/unit/service/api/special_forms.json
rm tests/resources/unit/service/api/variant_forms.json
```

- [x] **Étape 9 — Créer la fixture moco unifiée**

Créer `tests/resources/moco/Api/responses/forms.json` avec le même contenu que `forms.json` ci-dessus (même structure, valeurs cohérentes avec les réponses moco actuelles — combiner les données de `category_forms.json`, `regional_forms.json`, `special_forms.json`, `variant_forms.json` en un seul objet).

- [x] **Étape 10 — Supprimer les 4 anciennes fixtures moco et les fichiers correspondants**

```bash
rm tests/resources/moco/Api/responses/category_forms.json
rm tests/resources/moco/Api/responses/regional_forms.json
rm tests/resources/moco/Api/responses/special_forms.json
rm tests/resources/moco/Api/responses/variant_forms.json
```

- [x] **Étape 11 — Mettre à jour `moco.json`**

Dans `tests/resources/moco/Api/moco.json`, remplacer les 4 entrées pour `/forms/category`, `/forms/regional`, `/forms/special`, `/forms/variant` par une seule :

```json
{
  "request": {
    "uri": "/forms",
    "headers": {
      "accept": "application/json",
      "authorization": "Basic d2ViOmRvdXpl"
    }
  },
  "response": {
    "file": "/var/moco/responses/forms.json"
  }
}
```

- [x] **Étape 12 — Lancer les tests unitaires**

```bash
docker compose exec php php vendor/bin/phpunit tests/src/Unit/Service/Api/GetFormsApiServiceTest.php tests/src/Unit/Cache/KeyMakerTest.php tests/src/Unit/Service/CacheInvalidator/FormsCacheInvalidatorServiceTest.php -v
```
Attendu : PASS (tous verts).

- [x] **Étape 13 — Lancer les tests d'intégration Labels**

```bash
docker compose exec php php vendor/bin/phpunit tests/src/Integration/Labels/LabelsTest.php -v
```
Attendu : PASS.

---

## Task 2 — GET /action_logs : objet → tableau avec `action_type`

**Files:**
- Modify: `src/Service/Api/GetActionLogsApiService.php`
- Modify: `tests/src/Unit/Service/Api/GetActionLogsApiServiceTest.php`
- Modify: `tests/resources/unit/service/api/action_logs.json`
- Modify: `tests/resources/moco/Api/responses/action_logs.json`
- Modify: `tests/resources/functional/controller/Admin/action-logs.json`

**Interfaces:**
- `GetActionLogsApiService::get()` : retourne toujours `array<string, ActionLogData>` (keyed par `action_type`)
- La signature publique ne change pas

- [x] **Étape 1 — Mettre à jour `GetActionLogsApiService.php`**

La boucle doit maintenant itérer sur un tableau et extraire `action_type` :

```php
public function get(): array
{
    $json = $this->requestContent('GET', '/action_logs');

    /** @var array<int, array{
     *  action_type: string,
     *  current: array{
     *    created_at: string,
     *    done_at?: null|string,
     *    execution_time?: null|int|string,
     *    details?: int[],
     *    error_trace?: null|string,
     *  },
     *  last?: null|array{
     *    created_at: string,
     *    done_at?: null|string,
     *    execution_time?: null|int|string,
     *    details?: int[],
     *    error_trace?: null|string,
     *  },
     * }> */
    $actionLogsData = JsonDecoder::decode($json);

    $list = [];
    foreach ($actionLogsData as $entry) {
        $item = $entry['action_type'];

        /** @var array{
         *  created_at: string,
         *  done_at?: null|string,
         *  execution_time?: null|int|string,
         *  details?: int[],
         *  error_trace?: null|string,
         * } $currentData
         */
        $currentData = $entry['current'];

        /** @var ?array{
         *  created_at: string,
         *  done_at?: null|string,
         *  execution_time?: null|int|string,
         *  details?: int[],
         *  error_trace?: null|string,
         * } $lastData
         */
        $lastData = $entry['last'] ?? null;

        $list[$item] = new ActionLogData(
            $item,
            ActionLog::createFromArray($currentData),
            $lastData ? ActionLog::createFromArray($lastData) : null,
        );
    }

    return $list;
}
```

- [x] **Étape 2 — Mettre à jour la fixture unit `action_logs.json`**

Convertir l'objet en tableau. Copier le contenu actuel et transformer en tableau d'objets avec `action_type` :

```json
[
  {
    "action_type": "calculate_dex_availabilities",
    "current": {
      "row_number": 1,
      "created_at": "2023-03-21 09:14:36+00",
      "done_at": null,
      "execution_time": null,
      "details": {},
      "error_trace": null
    },
    "last": {
      "row_number": 2,
      "created_at": "2023-03-20 09:14:36+00",
      "done_at": "2023-03-20 10:05:08+00",
      "execution_time": "3032",
      "details": { "dex_availabilities": 22472 },
      "error_trace": null
    }
  },
  {
    "action_type": "calculate_pokemon_availabilities",
    "current": {
      "row_number": 1,
      "created_at": "2024-02-14 09:14:36+00",
      "done_at": "2024-02-14 09:14:36+00",
      "execution_time": "0",
      "details": { "pokemon_availabilities_game_bundle": 1, "pokemon_availabilities_game_bundle_shiny": 0 },
      "error_trace": null
    },
    "last": {
      "row_number": 2,
      "created_at": "2024-02-14 09:14:36+00",
      "done_at": "2024-02-14 09:14:36+00",
      "execution_time": "0",
      "details": { "pokemon_availabilities_game_bundle": 1, "pokemon_availabilities_game_bundle_shiny": 0 },
      "error_trace": null
    }
  },
  {
    "action_type": "calculate_game_bundles_availabilities",
    "current": {
      "row_number": 1,
      "created_at": "2023-03-21 07:15:04+00",
      "done_at": null,
      "details": {},
      "error_trace": null
    }
  },
  {
    "action_type": "calculate_game_bundles_shinies_availabilities",
    "current": {
      "row_number": 1,
      "created_at": "2023-04-21 15:24:18+00",
      "done_at": "2023-04-21 15:27:18+00",
      "execution_time": "180",
      "details": { "game_bundles_shinies_availabilities": 1234 },
      "error_trace": null
    },
    "last": {
      "row_number": 2,
      "created_at": "2023-04-20 15:24:18+00",
      "done_at": "2023-04-20 15:28:18+00",
      "execution_time": "200",
      "details": { "game_bundles_shinies_availabilities": 321 },
      "error_trace": null
    }
  },
  {
    "action_type": "update_games_collections_and_dex",
    "current": {
      "row_number": 1,
      "created_at": "2023-09-01 08:00:20+00",
      "done_at": null,
      "execution_time": null,
      "details": null,
      "error_trace": null
    },
    "last": {
      "row_number": 2,
      "created_at": "2023-03-21 08:51:00+00",
      "done_at": "2023-04-20 00:52:59+00",
      "execution_time": "2559719",
      "details": {},
      "error_trace": "Exception has been thrown for X reason"
    }
  },
  {
    "action_type": "update_games_availabilities",
    "current": {
      "row_number": 1,
      "created_at": "2023-03-21 08:51:00+00",
      "done_at": "2023-03-21 09:25:38+00",
      "execution_time": "2078",
      "details": {},
      "error_trace": null
    },
    "last": {
      "row_number": 2,
      "created_at": "2023-03-20 18:51:00+00",
      "done_at": "2023-03-20 19:25:38+00",
      "execution_time": "2012",
      "details": {},
      "error_trace": null
    }
  },
  {
    "action_type": "update_games_shinies_availabilities",
    "current": {
      "row_number": 1,
      "created_at": "2023-03-21 08:51:00+00",
      "done_at": "2023-04-22 00:52:59+00",
      "execution_time": "2559719",
      "details": {},
      "error_trace": "Exception has been thrown for X reason"
    },
    "last": {
      "row_number": 2,
      "created_at": "2023-03-20 08:51:00+00",
      "done_at": "2023-03-20 09:25:38+00",
      "execution_time": "2078",
      "details": { "games_shinies_availabilities": 41691 },
      "error_trace": null
    }
  },
  {
    "action_type": "update_labels",
    "current": {
      "row_number": 1,
      "created_at": "2023-03-21 12:51:39+00",
      "done_at": "2023-03-21 12:53:07+00",
      "execution_time": "88",
      "details": { "catch_states": 6, "regions": 0, "category_forms": 6, "regional_forms": 4, "special_forms": 7, "variant_forms": 7 },
      "error_trace": null
    },
    "last": {
      "row_number": 2,
      "created_at": "2023-03-20 12:51:39+00",
      "done_at": "2023-03-20 12:53:07+00",
      "execution_time": "8",
      "details": { "catch_states": 5, "regions": 0, "category_forms": 5, "regional_forms": 4, "special_forms": 6, "variant_forms": 6 },
      "error_trace": null
    }
  },
  {
    "action_type": "update_pokemons",
    "current": {
      "row_number": 1,
      "created_at": "2023-03-21 08:34:47+00",
      "done_at": "2023-03-21 09:38:03+00",
      "execution_time": "88",
      "details": { "pokemons": 1934 },
      "error_trace": null
    },
    "last": {
      "row_number": 2,
      "created_at": "2023-03-20 08:34:47+00",
      "done_at": "2023-03-20 09:38:03+00",
      "execution_time": "78",
      "details": { "pokemons": 1930 },
      "error_trace": null
    }
  }
]
```

Note : `update_collections_availabilities` est présent dans l'ancien fichier moco mais pas dans la fixture unit (9 entrées dans unit, 10 dans moco). Garder le même nombre.

- [x] **Étape 3 — Mettre à jour la fixture moco `action_logs.json`**

Appliquer la même conversion (objet → tableau) à `tests/resources/moco/Api/responses/action_logs.json`. Inclure toutes les entrées présentes dans ce fichier (9 entrées).

- [x] **Étape 4 — Lancer les tests unitaires `GetActionLogsApiServiceTest`**

```bash
docker compose exec php php vendor/bin/phpunit tests/src/Unit/Service/Api/GetActionLogsApiServiceTest.php -v
```
Attendu : PASS.

- [x] **Étape 5 — Lancer les tests d'intégration `ActionLogsTest`**

```bash
docker compose exec php php vendor/bin/phpunit tests/src/Integration/Admin/ActionLogsTest.php -v
```
Si le snapshot `Admin/action-logs.json` est différent, le mettre à jour en décommentant `file_put_contents` dans `JsonResponseTrait`, relancer, copier le fichier généré dans `tests/resources/functional/controller/Admin/action-logs.json`, puis recommenter.

---

## Task 3 — GET /election/metrics : `completion` imbriqué

**Files:**
- Modify: `src/Service/Api/GetElectionMetricsApiService.php`
- Modify: `src/DTO/ElectionMetrics.php`
- Modify: `tests/src/Unit/DTO/ElectionMetricsTest.php`
- Modify: `tests/src/Unit/Service/Api/GetElectionMetricsApiServiceTest.php`
- Modify: 2 fixtures unit + 5 fixtures moco + 5 snapshots ElectionIndex

**Interfaces:**
- `ElectionMetrics` : propriétés `underMaxViewCount` et `maxViewCount` conservent leurs noms publics (pour ne pas casser pokenini-web)
- `ElectionMetrics::configureOptions()` accepte maintenant les clés `under_max_count` et `at_max_count`
- `GetElectionMetricsApiService::getMetrics()` aplatit `completion` avant de passer au DTO

- [x] **Étape 1 — Mettre à jour `GetElectionMetricsApiService.php`**

Après `JsonDecoder::decode()`, aplatir le sous-objet `completion` avant de retourner :

```php
public function getMetrics(
    string $trainerId,
    string $dexSlug,
    string $electionSlug,
): array {
    $json = $this->requestContent(
        'GET',
        '/election/metrics',
        [
            'query' => [
                'trainer_external_id' => $trainerId,
                'dex_slug' => $dexSlug,
                'election_slug' => $electionSlug,
            ],
        ],
    );

    /** @var array{
     *  view_count: array{sum: int, max: int},
     *  win_count: array{sum: int, max: int},
     *  completion: array{under_max_count: int, at_max_count: int},
     *  dex_total_count: int,
     * } $raw
     */
    $raw = JsonDecoder::decode($json);

    /** @var float[]|int[] */
    return [
        'view_count_sum' => $raw['view_count']['sum'],
        'win_count_sum' => $raw['win_count']['sum'],
        'view_count_max' => $raw['view_count']['max'],
        'win_count_max' => $raw['win_count']['max'],
        'under_max_count' => $raw['completion']['under_max_count'],
        'at_max_count' => $raw['completion']['at_max_count'],
        'dex_total_count' => $raw['dex_total_count'],
    ];
}
```

**Important :** la structure de la nouvelle réponse API `view_count` et `win_count` sont aussi des objets `{sum, max}`. Vérifier dans `migration.md` :

```json
{
  "view_count": { "sum": 0, "max": 0 },
  "win_count":  { "sum": 0, "max": 0 },
  "completion": { "at_max_count": 15, "under_max_count": 15 },
  "dex_total_count": 21
}
```

Donc `view_count_sum` et `view_count_max` sont maintenant `view_count.sum` et `view_count.max`. Le service doit tout aplatir.

- [x] **Étape 2 — Mettre à jour `ElectionMetrics.php`**

Renommer les clés dans `configureOptions()` : `under_max_view_count` → `under_max_count`, `max_view_count` → `at_max_count`. Aussi renommer les clés `view_count_sum`, `win_count_sum`, `view_count_max`, `win_count_max` qui restent les mêmes (l'aplatissement est fait dans le service). Les propriétés PHP (`$underMaxViewCount`, `$maxViewCount`) ne changent pas.

```php
private function configureOptions(OptionsResolver $resolver): void
{
    $resolver->setDefault('view_count_sum', 0);
    $resolver->setAllowedTypes('view_count_sum', 'int');

    $resolver->setDefault('win_count_sum', 0);
    $resolver->setAllowedTypes('win_count_sum', 'int');

    $resolver->setDefault('view_count_max', 0);
    $resolver->setAllowedTypes('view_count_max', 'int');

    $resolver->setDefault('win_count_max', 0);
    $resolver->setAllowedTypes('win_count_max', 'int');

    $resolver->setDefault('under_max_count', 0);
    $resolver->setAllowedTypes('under_max_count', 'int');

    $resolver->setDefault('at_max_count', 0);
    $resolver->setAllowedTypes('at_max_count', 'int');

    $resolver->setDefault('dex_total_count', 0);
    $resolver->setAllowedTypes('dex_total_count', 'int');
}
```

Et dans le constructeur :
```php
$this->underMaxViewCount = $options['under_max_count'];
$this->maxViewCount = $options['at_max_count'];
```

- [x] **Étape 3 — Mettre à jour `ElectionMetricsTest.php`**

Dans **tous** les tests, remplacer les clés `under_max_view_count` par `under_max_count` et `max_view_count` par `at_max_count`. Exemple :

```php
// Avant :
'under_max_view_count' => 62,
'max_view_count' => 27,

// Après :
'under_max_count' => 62,
'at_max_count' => 27,
```

Appliquer ce remplacement dans les ~13 tests (tous les `testOk`, `testZeros`, `testEdge`, `testLowerAverage`, `testFloor`, `testMissing*`, `testBad*`).

Les assertions sur `$object->underMaxViewCount` et `$object->maxViewCount` restent inchangées (les propriétés PHP n'ont pas changé de nom).

- [x] **Étape 4 — Mettre à jour les fixtures unit election metrics**

Pour `tests/resources/unit/service/api/election_metrics_4564650_home_fav.json` :
```json
{
    "view_count": { "sum": 6, "max": 1 },
    "win_count": { "sum": 2, "max": 1 },
    "completion": { "under_max_count": 1, "at_max_count": 5 },
    "dex_total_count": 48
}
```

Pour `tests/resources/unit/service/api/election_metrics_87654_demo_pref.json` :
```json
{
    "view_count": { "sum": 5, "max": 1 },
    "win_count": { "sum": 10, "max": 1 },
    "completion": { "under_max_count": 1, "at_max_count": 5 },
    "dex_total_count": 48
}
```

Vérifier aussi les variantes avec filtres (`*_cflegendary`, `*_at0poison_*`) et les mettre à jour.

- [x] **Étape 5 — Mettre à jour `GetElectionMetricsApiServiceTest.php`**

Les assertions `assertSame([ ... ], $items)` doivent maintenant vérifier le tableau aplati produit par le service. Mettre à jour les valeurs attendues :

```php
$this->assertSame(
    [
        'view_count_sum' => 6,
        'win_count_sum' => 2,
        'view_count_max' => 1,
        'win_count_max' => 1,
        'under_max_count' => 1,
        'at_max_count' => 5,
        'dex_total_count' => 48,
    ],
    $items
);
```

- [x] **Étape 6 — Mettre à jour les 5 fixtures moco election metrics**

Appliquer la même transformation à chacun des 5 fichiers sous `tests/resources/moco/Api/responses/election/`:
- `demolite_metrics.json`
- `demolitelastone_metrics.json`
- `demolitelastpage_metrics.json`
- `demolitenotlastone_metrics.json`
- `demolitenotlastpage_metrics.json`

En conservant les valeurs actuelles (`view_count_sum`, `under_max_view_count`, etc.) mais re-structurées :
```json
{
    "view_count": { "sum": <view_count_sum>, "max": <view_count_max> },
    "win_count": { "sum": <win_count_sum>, "max": <win_count_max> },
    "completion": { "under_max_count": <under_max_view_count>, "at_max_count": <max_view_count> },
    "dex_total_count": <dex_total_count>
}
```

Exemple pour `demolite_metrics.json` (actuel : `view_count_sum: 82, win_count_sum: 41, view_count_max: 1, win_count_max: 1, under_max_view_count: 1, max_view_count: 5, dex_total_count: 48`) :
```json
{
    "view_count": { "sum": 82, "max": 1 },
    "win_count": { "sum": 41, "max": 1 },
    "completion": { "under_max_count": 1, "at_max_count": 5 },
    "dex_total_count": 48
}
```

- [x] **Étape 7 — Lancer les tests unitaires ElectionMetrics**

```bash
docker compose exec php php vendor/bin/phpunit tests/src/Unit/DTO/ElectionMetricsTest.php tests/src/Unit/Service/Api/GetElectionMetricsApiServiceTest.php -v
```
Attendu : PASS.

- [x] **Étape 8 — Lancer les tests d'intégration ElectionIndex**

```bash
docker compose exec php php vendor/bin/phpunit tests/src/Integration/Election/ElectionIndexTest.php -v
```
Si les snapshots `ElectionIndex/*.json` échouent sur le champ `metrics`, les régénérer via `file_put_contents` dans `JsonResponseTrait`, puis les copier en tant que nouvelles références.

---

## Task 4 — POST /election/vote : `trainer_external_id` → `trainer.external_id`

**Files:**
- Modify: `src/Service/Api/ModifyElectionVoteApiService.php`
- Modify: `tests/src/Unit/Service/Api/ModifyElectionVoteApiServiceTest.php`

**Interfaces:**
- `ModifyElectionVoteApiService::vote()` : signature inchangée, seul le body JSON change

- [x] **Étape 1 — Mettre à jour `ModifyElectionVoteApiService.php`**

```php
public function vote(
    string $trainerId,
    ElectionVote $electionVote,
): void {
    $this->requestContent(
        'POST',
        '/election/vote',
        [
            'body' => json_encode([
                'trainer' => ['external_id' => $trainerId],
                'dex_slug' => $electionVote->dexSlug,
                'election_slug' => $electionVote->electionSlug,
                'winners_slugs' => $electionVote->filteredWinners(),
                'losers_slugs' => $electionVote->filteredLosers(),
            ]),
        ]
    );
}
```

- [x] **Étape 2 — Mettre à jour `ModifyElectionVoteApiServiceTest.php`**

Dans `getService()`, mettre à jour la valeur attendue par le mock `->with(...)` :

```php
'body' => json_encode([
    'trainer' => ['external_id' => $trainerId],
    'dex_slug' => $dexSlug,
    'election_slug' => $electionSlug,
    'winners_slugs' => $winnersSlugs,
    'losers_slugs' => $losersSlugs,
]),
```

- [x] **Étape 3 — Lancer les tests unitaires**

```bash
docker compose exec php php vendor/bin/phpunit tests/src/Unit/Service/Api/ModifyElectionVoteApiServiceTest.php -v
```
Attendu : PASS.

---

## Task 5 — GET /election/top : structure imbriquée + controller

**Files:**
- Modify: `src/Controller/Election/ElectionIndexController.php`
- Modify: `src/Service/GetElectionTopService.php` *(enrichissement BFF ajouté en post-migration)*
- Modify: `src/Service/Api/GetElectionTopApiService.php` *(type hint étendu)*
- Modify: `tests/src/Unit/Service/GetElectionTopServiceTest.php`
- Modify: `tests/resources/unit/service/api/election_top_5_4564650_home_fav.json`
- Modify: `tests/resources/unit/service/api/election_top_10_87654_demo_pref.json`
- Modify: `tests/resources/moco/Api/responses/election/demolite_top_5.json`
- Modify (via snapshot regen): 5 fichiers `ElectionIndex/*.json`

**Interfaces:**
- `GetElectionTopApiService::getTop()` : retourne la structure imbriquée avec `labels` enrichis (`simplified_name`, `simplified_french_name`)
- `GetElectionTopService::getTop()` : ajoute `pokemon.pokemon_icon` (= `pokemon.slug`) avant de retourner
- `ElectionIndexController` accède à `$pokemon['score']['significance']` au lieu de `$pokemon['significance']`

**Enrichissement BFF post-migration (découvert par pokenini-web) :**
Le web utilisait `pokemon_icon` et `simplified_name` de l'ancien format plat. La nouvelle structure imbriquée ne les exposait plus. Fix :
- `pokenini-api` doit retourner `labels.simplified_name` et `labels.simplified_french_name` dans `/election/top`
- `GetElectionTopService` injecte `pokemon.pokemon_icon` (= `pokemon.slug`) côté BFF
- Structure de sortie par item :
```json
{
  "pokemon": { "slug": "venusaur-mega", "pokemon_icon": "venusaur-mega",
    "labels": { "name": "Mega Venusaur", "simplified_name": "Venusaur",
                "french_name": "Mega Florizarre", "simplified_french_name": "Florizarre" },
    "national_dex_number": 3 },
  "forms": { "special": { "slug": "mega", "name": "Mega", "french_name": "Mega" } },
  "types": { "primary": {...}, "secondary": {...} },
  "score": { "elo": 1040, "significance": false }
}
```

- [x] **Étape 1 — Mettre à jour `ElectionIndexController.php`**

```php
$detachedCount = 0;
foreach ($electionTop as $pokemon) {
    if ($pokemon['score']['significance']) {
        ++$detachedCount;
    }
}
```

- [x] **Étape 2 — Mettre à jour la fixture unit `election_top_5_4564650_home_fav.json`**

Convertir chaque entrée plate vers la nouvelle structure imbriquée. Exemple pour une entrée existante (actuellement plate avec `elo`, `significance`, `pokemon_slug`, etc.) :

```json
{
  "pokemon": {
    "slug": "rattata-f",
    "labels": {
      "name": "Rattata ♀",
      "french_name": "Rattata ♀️"
    },
    "national_dex_number": 19
  },
  "forms": {
    "variant": { "slug": "gender", "name": "Gender", "french_name": "Sexe" }
  },
  "types": {
    "primary": { "slug": "normal", "name": "Normal", "french_name": "Normal", "color": "#A8A878" },
    "secondary": null
  },
  "score": {
    "elo": 1040,
    "significance": false
  }
}
```

Appliquer cette structure pour toutes les entrées du fichier (5 Pokémon actuellement).

- [x] **Étape 3 — Mettre à jour la fixture unit `election_top_10_87654_demo_pref.json`**

Même conversion que l'étape 2, pour les 10 entrées de ce fichier.

- [x] **Étape 4 — Mettre à jour la fixture moco `demolite_top_5.json`**

Même conversion pour les 5 entrées (Bulbasaur, Ivysaur, Venusaur, Venusaur-f, Venusaur-mega).

Exemple pour Bulbasaur :
```json
{
  "pokemon": {
    "slug": "bulbasaur",
    "labels": { "name": "Bulbasaur", "french_name": "Bulbizarre" },
    "national_dex_number": 1
  },
  "forms": null,
  "types": {
    "primary": { "slug": "grass", "name": "Grass", "french_name": "Plante", "color": "#78C850" },
    "secondary": { "slug": "poison", "name": "Poison", "french_name": "Poison", "color": "#A040A0" }
  },
  "score": { "elo": 1040, "significance": true }
}
```

- [x] **Étape 5 — Lancer les tests unitaires GetElectionTopApiServiceTest**

```bash
docker compose exec php php vendor/bin/phpunit tests/src/Unit/Service/Api/GetElectionTopApiServiceTest.php -v
```
Attendu : PASS.

- [x] **Étape 6 — Lancer les tests d'intégration ElectionIndex et régénérer les snapshots**

```bash
docker compose exec php php vendor/bin/phpunit tests/src/Integration/Election/ElectionIndexTest.php -v
```

Les snapshots `ElectionIndex/*.json` échoueront car `election_top` a changé de format. Utiliser la procédure de régénération (`file_put_contents` dans `JsonResponseTrait`) pour chacun des 5 cas, puis copier les fichiers générés dans le répertoire de référence.

- [x] **Étape 7 — Lancer tous les tests**

```bash
docker compose exec php php vendor/bin/phpunit -v
```
Attendu : tous verts. ✅ 441 tests, 2009 assertions.

---

## Vérification finale

- [ ] **Lancer `make quality`** *(à valider)*

```bash
cd /home/renaud/projects/pokenini-back && make quality
```
Attendu : 0 erreur PHPStan, 0 erreur Psalm, 0 erreur PHPMD, 0 violation Deptrac, 0 erreur CS Fixer.

- [x] **Lancer `make tests` complet**

```bash
cd /home/renaud/projects/pokenini-back && make tests
```
Attendu : tous verts. ✅ 441 tests, 2009 assertions.

---

## Self-Review — Couverture du spec

| Endpoint API | Tâche | Couvert ? |
|---|---|---|
| `GET /forms` (consolidation) | Task 1 | ✅ |
| `GET /action_logs` (tableau) | Task 2 | ✅ |
| `GET /election/metrics` (completion) | Task 3 | ✅ |
| `POST /election/vote` (trainer imbriqué) | Task 4 | ✅ |
| `GET /election/top` (structure imbriquée) | Task 5 | ✅ |
| `GET /game_bundles` (generation imbriqué) | non-breaking, optionnel | — |
| `GET /reports` (slugs additifs) | non-breaking, optionnel | — |
| Renommages globaux camelCase→snake_case | Ces renommages concernent l'API interne — pokenini-back ne lit pas ces champs directement, ils sont pass-through | N/A |

Les endpoints `/debogage/*` ne sont pas exposés par pokenini-back — non concerné.
