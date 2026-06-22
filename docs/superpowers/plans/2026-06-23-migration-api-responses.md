# Migration des réponses API — Adaptation pokenini-back Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Aligner `pokenini-back` sur le nouveau format de réponse de `pokenini-api` (branche `feature/refactoring_responses`) — toutes les clés en `snake_case`, structures imbriquées, suppression des champs plats.

**Architecture:** Le back est un BFF qui passe les données de l'API au web. Pour la plupart des endpoints il suffit de mettre à jour les fixtures moco (simulation de l'API) et les snapshots de tests fonctionnels. Quelques changements de code PHP sont nécessaires là où le back transformait activement les données (action logs → objet indexé, election top → ajout `pokemon_icon`).

**Tech Stack:** PHP 8.5, Symfony 8, PHPUnit, Moco (mock HTTP server), JSON fixtures

## Global Constraints

- `declare(strict_types=1)` dans chaque fichier PHP
- Classes finales pour Controller/DTO/Exception/tests
- PHPStan level 9 / Psalm strict — mettre à jour les annotations de type
- 100% coverage, 100% MSI
- Pas de commit, pas d'exécution de tests
- Source de vérité pour les formats : `pokenini-api/doc/endpoints.md`

---

## Contexte : ce qui EST déjà migré dans le code PHP

Ces services lisent **déjà** le nouveau format de l'API — seules leurs fixtures ont besoin d'être mises à jour :

| Service | Endpoint API | Format déjà attendu |
|---------|-------------|---------------------|
| `GetElectionMetricsApiService` | `/election/metrics` | `completion.at_max_count`, `completion.under_max_count` ✓ |
| `GetActionLogsApiService` | `/action_logs` | Tableau avec `action_type` ✓ |
| `ModifyElectionVoteApiService` | `/election/vote` | Envoie `trainer: {external_id}` ✓ |
| `GetGameBundlesApiService` | `/game_bundles` | `generation: {slug}` ✓ |
| `GetFormsApiService` | `/forms` | Endpoint unifié ✓ |
| `GetElectionTopApiService` | `/election/top` | `pokemon.labels`, `types.primary.color` ✓ |

---

## Règles de transformation par endpoint

### A. `/dex/{trainerId}/list` — drapeaux imbriqués

**Avant (moco actuel `list_premium.json`):**
```json
{
  "is_shiny": true,
  "is_private": true,
  "is_on_home": true,
  "is_display_form": true,
  "is_released": true,
  "is_premium": false,
  "is_custom": false,
  "display_template": "box",
  "region": { "name": "Kanto", "french_name": "Kanto", "slug": "kanto" },
  "name": "Red, Green, Blue, Yellow",
  "french_name": "Rouge, Vert, Bleu, Jaune",
  "slug": "redgreenblueyellow",
  "original_slug": "redgreenblueyellow"
}
```

**Après (nouveau format) :**
```json
{
  "dex": { "slug": "redgreenblueyellow" },
  "name": "Red / Green / Blue / Yellow",
  "french_name": "Rouge / Vert / Bleu / Jaune",
  "slug": "redgreenblueyellow",
  "flags": {
    "is_shiny": false,
    "is_private": true,
    "is_on_home": false,
    "is_display_form": true,
    "is_released": true,
    "is_premium": false,
    "is_custom": false
  },
  "display_template": "box"
}
```

Changements : `is_*` → `flags: {is_*}` ; `region` supprimé de la liste ; `dex: {slug}` ajouté.

### B. `/dex/can_hold_election` — drapeaux imbriqués

**Avant :**
```json
{
  "is_shiny": false,
  "is_private": false,
  "is_on_home": true,
  "is_display_form": true,
  "is_released": true,
  "is_premium": false,
  "is_custom": false,
  "display_template": "box",
  "name": "Home",
  "french_name": "Home",
  "slug": "home",
  "original_slug": "home",
  "description": "...",
  "french_description": "...",
  "dex_total_count": 61
}
```

**Après :**
```json
{
  "slug": "home",
  "original_slug": "home",
  "name": "Home",
  "french_name": "Home",
  "flags": {
    "is_shiny": false,
    "is_private": false,
    "is_on_home": false,
    "is_display_form": true,
    "is_released": true,
    "is_premium": false,
    "is_custom": false
  },
  "description": "",
  "french_description": "",
  "dex_total_count": 22
}
```

Changements : `is_*` → `flags: {is_*}` ; `display_template` supprimé.

### C. `/reports` — `trainer` en objet

**Avant :**
```json
{ "count": 5735, "trainer": "f86cbe805674d85f7806b175b70647a6a9334631" }
```

**Après :**
```json
{ "count": 5735, "trainer": { "external_id": "f86cbe805674d85f7806b175b70647a6a9334631" } }
```

### D. `/action_logs` — tableau indexé → tableau brut

**Avant (réponse du back, `Admin/action-logs.json`) :**
```json
{
  "calculate_dex_availabilities": { "item": "...", "current": {...}, "last": {...} },
  "update_pokemons": { "item": "...", "current": {...}, "last": {...} }
}
```

**Après (même format que l'API) :**
```json
[
  {
    "action_type": "calculate_dex_availabilities",
    "current": { "created_at": "...", "done_at": null, "execution_time": null, "details": [], "error_trace": null },
    "last": { "created_at": "...", "done_at": "...", "execution_time": 3032, "details": {...}, "error_trace": null }
  },
  {
    "action_type": "update_pokemons",
    "current": { ... },
    "last": { ... }
  }
]
```

### E. `/election/top` — supprimer `pokemon_icon`, ajouter `icon` + `game_bundles`

**Avant (moco actuel `demolite_top_5.json`) :**
```json
{
  "pokemon": {
    "slug": "bulbasaur",
    "labels": { "name": "Bulbasaur", "simplified_name": "Bulbasaur", "french_name": "Bulbizarre", "simplified_french_name": "Bulbizarre" },
    "national_dex_number": 1
  },
  "forms": null,
  "types": { "primary": { "slug": "grass", "name": "Grass", "french_name": "Plante", "color": "#78C850" }, "secondary": {...} },
  "score": { "elo": 1040, "significance": true }
}
```

**Après (nouveau format API — cf. endpoints.md §15) :**
```json
{
  "pokemon": {
    "slug": "bulbasaur",
    "labels": {
      "name": "Bulbasaur",
      "french_name": "Bulbizarre",
      "simplified_name": "Bulbasaur",
      "simplified_french_name": "Bulbizarre",
      "forms_label": null,
      "forms_french_label": null
    },
    "national_dex_number": 1,
    "regional_dex_number": null,
    "icon": "bulbasaur",
    "family_order": 0,
    "family_lead": { "slug": "bulbasaur" },
    "original_game_bundle": { "slug": "redgreenblueyellow" },
    "order_number": "9999-0001-000",
    "game_bundles": {
      "normal": [{ "slug": "redgreenblueyellow" }],
      "shiny": [{ "slug": "redgreenblueyellow" }]
    }
  },
  "forms": null,
  "types": {
    "primary": { "slug": "grass", "name": "Grass", "french_name": "Plante", "color": "#78C850" },
    "secondary": { "slug": "poison", "name": "Poison", "french_name": "Poison", "color": "#A040A0" }
  },
  "score": { "elo": 1040.0, "significance": true }
}
```

Le back (`GetElectionTopService`) n'ajoute plus `pokemon_icon`. Le champ `icon` vient directement de l'API.

### F. `/pokemons/to_choose` — format plat → imbriqué

**Avant (moco actuel) :**
```json
{
  "type": "pick",
  "items": [
    {
      "pokemon_slug": "bulbasaur",
      "pokemon_name": "Bulbasaur",
      "pokemon_national_dex_number": 1,
      "pokemon_simplified_name": "Bulbasaur",
      "pokemon_forms_label": "",
      "pokemon_french_name": "Bulbizarre",
      "pokemon_simplified_french_name": "Bulbizarre",
      "pokemon_forms_french_label": "",
      "pokemon_icon": "bulbasaur",
      "pokemon_family_order": 0,
      "family_lead_slug": null,
      "category_form_slug": "starter",
      "category_form_name": "Starter",
      "regional_form_slug": null,
      "regional_form_name": null,
      "special_form_slug": null,
      "special_form_name": null,
      "variant_form_slug": null,
      "variant_form_name": null,
      "catch_state_slug": null,
      "catch_state_name": null,
      "catch_state_french_name": null,
      "primary_type_slug": "grass",
      "primary_type_name": "Grass",
      "primary_type_french_name": "Plante",
      "secondary_type_slug": "poison",
      "secondary_type_name": "Poison",
      "secondary_type_french_name": "Poison",
      "pokemon_order_number": "9999-0001-000",
      "original_game_bundle_slug": "redgreenblueyellow"
    }
  ]
}
```

**Après (nouveau format API — cf. endpoints.md §13) :**
```json
{
  "type": "pick",
  "items": [
    {
      "pokemon": {
        "slug": "bulbasaur",
        "name": "Bulbasaur",
        "french_name": "Bulbizarre",
        "national_dex_number": 1,
        "regional_dex_number": null,
        "simplified_name": "Bulbasaur",
        "forms_label": "",
        "simplified_french_name": "Bulbizarre",
        "forms_french_label": "",
        "icon": "bulbasaur",
        "family_order": 0,
        "family_lead": { "slug": "bulbasaur" },
        "original_game_bundle": { "slug": "redgreenblueyellow" },
        "order_number": "9999-0001-000",
        "game_bundles": [{ "slug": "redgreenblueyellow" }],
        "game_bundles_shiny": [{ "slug": "redgreenblueyellow" }]
      },
      "forms": {
        "category": { "slug": "starter", "name": "Starter", "french_name": "de Départ" },
        "regional": null,
        "special": null,
        "variant": null
      },
      "types": {
        "primary": { "slug": "grass", "name": "Grass", "french_name": "Plante", "color": "#78C850" },
        "secondary": { "slug": "poison", "name": "Poison", "french_name": "Poison", "color": "#A040A0" }
      }
    }
  ]
}
```

Note : pas de `catch_state` dans cet endpoint.

### G. `/album/{trainerId}/{dexSlug}` — format plat → imbriqué

**Dex avant :**
```json
{
  "slug": "demolite",
  "original_slug": "demolite",
  "name": "Demo light",
  "french_name": "Démo, extrait",
  "is_shiny": false,
  "is_private": false,
  "is_display_form": true,
  "display_template": "box",
  "region_name": null,
  "region_french_name": null,
  "description": "",
  "french_description": "",
  "version": 0,
  "is_released": true,
  "is_premium": false,
  "is_custom": false
}
```

**Dex après :**
```json
{
  "slug": "demolite",
  "original_slug": "demolite",
  "name": "Demo light",
  "french_name": "Démo, extrait",
  "flags": {
    "is_shiny": false,
    "is_private": false,
    "is_on_home": false,
    "is_display_form": true,
    "is_released": true,
    "is_premium": false,
    "is_custom": false
  },
  "display_template": "box",
  "region": null,
  "selection_rule": "",
  "description": "",
  "french_description": "",
  "version": "0"
}
```

**Item pokémon avant :**
```json
{
  "pokemon_slug": "bulbasaur",
  "pokemon_name": "Bulbasaur",
  "pokemon_national_dex_number": 1,
  "pokemon_simplified_name": "Bulbasaur",
  "pokemon_forms_label": "",
  "pokemon_french_name": "Bulbizarre",
  "pokemon_simplified_french_name": "Bulbizarre",
  "pokemon_forms_french_label": "",
  "pokemon_icon": "bulbasaur",
  "pokemon_family_order": 0,
  "family_lead_slug": null,
  "category_form_slug": "starter",
  "category_form_name": "Starter",
  "regional_form_slug": null,
  "regional_form_name": null,
  "special_form_slug": null,
  "special_form_name": null,
  "variant_form_slug": null,
  "variant_form_name": null,
  "catch_state_slug": null,
  "catch_state_name": null,
  "catch_state_french_name": null,
  "pokemon_regional_dex_number": null,
  "primary_type_slug": "grass",
  "primary_type_name": "Grass",
  "primary_type_french_name": "Plante",
  "secondary_type_slug": "poison",
  "secondary_type_name": "Poison",
  "secondary_type_french_name": "Poison",
  "pokemon_order_number": "0001-0001-000",
  "original_game_bundle_slug": "redgreenblueyellow",
  "game_bundles": ["redgreenblueyellow", "goldsilvercrystal"],
  "game_bundles_shiny": ["redgreenblueyellow", "goldsilvercrystal"]
}
```

**Item pokémon après :**
```json
{
  "pokemon": {
    "slug": "bulbasaur",
    "name": "Bulbasaur",
    "french_name": "Bulbizarre",
    "national_dex_number": 1,
    "regional_dex_number": 1,
    "simplified_name": "Bulbasaur",
    "forms_label": "",
    "simplified_french_name": "Bulbizarre",
    "forms_french_label": "",
    "icon": "bulbasaur",
    "family_order": 0,
    "family_lead": { "slug": "bulbasaur" },
    "original_game_bundle": { "slug": "redgreenblueyellow" },
    "order_number": "0001-0001-000",
    "game_bundles": [
      { "slug": "redgreenblueyellow" },
      { "slug": "goldsilvercrystal" }
    ],
    "game_bundles_shiny": [
      { "slug": "redgreenblueyellow" },
      { "slug": "goldsilvercrystal" }
    ]
  },
  "catch_state": {
    "slug": "no",
    "name": "No",
    "french_name": "Non",
    "color": "#e57373"
  },
  "forms": {
    "category": { "slug": "starter", "name": "Starter", "french_name": "de Départ" },
    "regional": null,
    "special": null,
    "variant": null
  },
  "types": {
    "primary": { "slug": "grass", "name": "Grass", "french_name": "Plante", "color": "#78C850" },
    "secondary": { "slug": "poison", "name": "Poison", "french_name": "Poison", "color": "#A040A0" }
  }
}
```

`catch_state: null` quand aucun état n'est défini. `game_bundles` passe de tableau de strings à tableau d'objets `{slug}`.

**Report (inchangé dans sa structure, mais toujours présent) :**
```json
{
  "total": 7,
  "total_caught": 0,
  "total_uncaught": 7,
  "detail": [
    { "catch_state": { "slug": "no", "name": "No", "french_name": "Non", "color": "#e57373" }, "count": 4 }
  ]
}
```

---

## File Structure

### Fichiers PHP à modifier
- `src/Service/Api/GetActionLogsApiService.php` — simplifier pour retourner le tableau brut
- `src/Controller/Admin/AdminActionLogsController.php` — utiliser `JsonResponse::fromJsonString()`
- `src/DTO/ActionLog.php` — **supprimer** (plus utilisé)
- `src/DTO/ActionLogData.php` — **supprimer** (plus utilisé)
- `src/Service/GetElectionTopService.php` — supprimer le mapping `pokemon_icon`
- `src/DTO/ElectionPokemonsList.php` — mettre à jour l'annotation de type pour `items`
- `src/Service/Api/GetPokemonsApiService.php` — mettre à jour les annotations de type
- `src/Service/Api/GetPokedexApiService.php` — mettre à jour l'annotation de retour

### Tests unitaires à modifier
- `tests/src/Unit/Service/Api/GetActionLogsApiServiceTest.php`
- `tests/src/Unit/DTO/ActionLogTest.php` — **supprimer**
- `tests/src/Unit/DTO/ActionLogDataTest.php` — **supprimer**
- `tests/src/Unit/Service/GetElectionTopServiceTest.php`
- `tests/src/Unit/DTO/ElectionPokemonsListTest.php`

### Fixtures Moco (simulation de l'API) à mettre à jour
- `tests/resources/moco/Api/responses/dexes/list_premium.json`
- `tests/resources/moco/Api/responses/dexes/list_unreleased_and_premium.json`
- `tests/resources/moco/Api/responses/dexes/963f1e5284a86d4a60fb5eb81cb75b22fbe683ec_premium.json`
- `tests/resources/moco/Api/responses/dexes/994e463d0e059ad0e1efc15d42485a3ec89958b9_unreleased_and_premium.json`
- `tests/resources/moco/Api/responses/dexes/d79ed57f46e74e66fa394b663810499405c818e3_premium.json`
- `tests/resources/moco/Api/responses/election_dex_list.json`
- `tests/resources/moco/Api/responses/election_dex_list_include_premium_dex1.json`
- `tests/resources/moco/Api/responses/election_dex_list_include_premium_dex1_include_unreleased_dex1.json`
- `tests/resources/moco/Api/responses/reports.json`
- `tests/resources/moco/Api/responses/election/demolite_top_5.json`
- `tests/resources/moco/Api/responses/pokemons_topick_demolite_12.json`
- `tests/resources/moco/Api/responses/pokemons_topick_demolitelastone_12.json`
- `tests/resources/moco/Api/responses/pokemons_topick_demolitenotlastone_12.json`
- `tests/resources/moco/Api/responses/album/default/demo-lite.json`
- `tests/resources/moco/Api/responses/album/default/demo.json`
- `tests/resources/moco/Api/responses/album/default/demo_list3.json`
- `tests/resources/moco/Api/responses/album/default/allshinies.json`
- `tests/resources/moco/Api/responses/album/default/goldsilvercrystal.json`
- `tests/resources/moco/Api/responses/album/default/homepokemongo.json`
- `tests/resources/moco/Api/responses/album/default/redgreenblueyellow.json`
- `tests/resources/moco/Api/responses/album/7b52009b64fd0a2a49e6d8a939753077792b0554/demo.json`
- `tests/resources/moco/Api/responses/album/7b52009b64fd0a2a49e6d8a939753077792b0554/demo_anytypes0fire.json`
- `tests/resources/moco/Api/responses/album/7b52009b64fd0a2a49e6d8a939753077792b0554/demo_catchstates!no.json`
- `tests/resources/moco/Api/responses/album/7b52009b64fd0a2a49e6d8a939753077792b0554/demo_catchstatesno.json`
- `tests/resources/moco/Api/responses/album/7b52009b64fd0a2a49e6d8a939753077792b0554/demo_categoryforms0starter.json`
- `tests/resources/moco/Api/responses/album/7b52009b64fd0a2a49e6d8a939753077792b0554/demo_collectionavailabilities0pogoshadow.json`
- `tests/resources/moco/Api/responses/album/7b52009b64fd0a2a49e6d8a939753077792b0554/demo_familybulbasaur.json`
- `tests/resources/moco/Api/responses/album/7b52009b64fd0a2a49e6d8a939753077792b0554/demo_originalgamebundle0swordshield.json`
- `tests/resources/moco/Api/responses/album/7b52009b64fd0a2a49e6d8a939753077792b0554/demo_originalgamebundle0unknown.json`
- `tests/resources/moco/Api/responses/album/7b52009b64fd0a2a49e6d8a939753077792b0554/demo_primarytypes0fire.json`
- `tests/resources/moco/Api/responses/album/7b52009b64fd0a2a49e6d8a939753077792b0554/demo_secondarytypes0poisonsecondarytypes1flying.json`
- `tests/resources/moco/Api/responses/album/7b52009b64fd0a2a49e6d8a939753077792b0554/demo_specialforms0mega.json`

### Fixtures unitaires à mettre à jour
- `tests/resources/unit/service/api/action_logs.json` — déjà à jour (array avec `action_type`)
- `tests/resources/unit/service/api/reports.json`
- `tests/resources/unit/service/api/election_top_5_4564650_home_fav.json`
- `tests/resources/unit/service/api/election_top_10_87654_demo_pref.json`
- `tests/resources/unit/service/api/pokemons_topick_12_123__3.json`
- `tests/resources/unit/service/api/pokemons_topick_12_123__5_cflegendary.json`
- `tests/resources/unit/service/api/pokemons_topick_13_123_a_5.json`
- `tests/resources/unit/service/api/pokemons_tovote_14_all_b_12.json`
- `tests/resources/unit/service/api/pokedex_lite_123.json`
- `tests/resources/unit/service/api/pokedex_lite_123_csyes.json`
- `tests/resources/unit/service/api/pokedex_lite_123_cs!yes.json`
- `tests/resources/unit/service/api/dex_123.json`
- `tests/resources/unit/service/api/dex_123_premium.json`
- `tests/resources/unit/service/api/dex_123_unreleased.json`
- `tests/resources/unit/service/api/dex_123_unreleased_and_premium.json`
- `tests/resources/unit/service/api/election_dex_list.json`
- `tests/resources/unit/service/api/election_dex_list_premium.json`
- `tests/resources/unit/service/api/election_dex_list_unreleased.json`
- `tests/resources/unit/service/api/election_dex_list_unreleased_and_premium.json`

### Snapshots fonctionnels à mettre à jour
- `tests/resources/functional/controller/Admin/action-logs.json`
- `tests/resources/functional/controller/Admin/reports.json`
- `tests/resources/functional/controller/AlbumDexList/admin.json`
- `tests/resources/functional/controller/AlbumDexList/collector.json`
- `tests/resources/functional/controller/AlbumDexList/trainer.json`
- `tests/resources/functional/controller/TrainerDexList/admin.json`
- `tests/resources/functional/controller/TrainerDexList/collector.json`
- `tests/resources/functional/controller/TrainerDexList/trainer.json`
- `tests/resources/functional/controller/ElectionDexList/admin.json`
- `tests/resources/functional/controller/ElectionDexList/collector.json`
- `tests/resources/functional/controller/ElectionDexList/trainer.json`
- `tests/resources/functional/controller/Pokedex/demolite.json`
- `tests/resources/functional/controller/Pokedex/demolite_f86cbe805674d85f7806b175b70647a6a9334631.json`
- `tests/resources/functional/controller/Pokedex/demo.json`
- `tests/resources/functional/controller/Pokedex/demolist3.json`
- `tests/resources/functional/controller/Pokedex/allshinies.json`
- Tous les `tests/resources/functional/controller/Pokedex/filters_*.json` (19 fichiers)
- `tests/resources/functional/controller/ElectionIndex/demolite.json`
- `tests/resources/functional/controller/ElectionIndex/demolitelastone.json`
- `tests/resources/functional/controller/ElectionIndex/demolitelastpage.json`
- `tests/resources/functional/controller/ElectionIndex/demolitenotlastone.json`
- `tests/resources/functional/controller/ElectionIndex/demolitenotlastpage.json`
- `tests/resources/functional/controller/Labels/all.json`
- `tests/resources/functional/controller/Labels/forms_category.json`
- `tests/resources/functional/controller/Labels/forms_regional.json`
- `tests/resources/functional/controller/Labels/forms_special.json`
- `tests/resources/functional/controller/Labels/forms_variant.json`
- `tests/resources/functional/controller/Labels/game_bundles.json`

---

## Tasks

### Task 1: Dex list (`/dex/{trainerId}/list`) — flags plats → objet `flags`

**Files:**
- Modify: `tests/resources/moco/Api/responses/dexes/list_premium.json`
- Modify: `tests/resources/moco/Api/responses/dexes/list_unreleased_and_premium.json`
- Modify: `tests/resources/moco/Api/responses/dexes/963f1e5284a86d4a60fb5eb81cb75b22fbe683ec_premium.json`
- Modify: `tests/resources/moco/Api/responses/dexes/994e463d0e059ad0e1efc15d42485a3ec89958b9_unreleased_and_premium.json`
- Modify: `tests/resources/moco/Api/responses/dexes/d79ed57f46e74e66fa394b663810499405c818e3_premium.json`
- Modify: `tests/resources/unit/service/api/dex_123.json`
- Modify: `tests/resources/unit/service/api/dex_123_premium.json`
- Modify: `tests/resources/unit/service/api/dex_123_unreleased.json`
- Modify: `tests/resources/unit/service/api/dex_123_unreleased_and_premium.json`
- Modify: `tests/resources/functional/controller/AlbumDexList/admin.json`
- Modify: `tests/resources/functional/controller/AlbumDexList/collector.json`
- Modify: `tests/resources/functional/controller/AlbumDexList/trainer.json`
- Modify: `tests/resources/functional/controller/TrainerDexList/admin.json`
- Modify: `tests/resources/functional/controller/TrainerDexList/collector.json`
- Modify: `tests/resources/functional/controller/TrainerDexList/trainer.json`

- [ ] **Step 1: Lire chaque moco fixture pour dex list**

  Lire `tests/resources/moco/Api/responses/dexes/list_premium.json`.

- [ ] **Step 2: Transformer le format de chaque item**

  Appliquer la règle **A** (cf. section "Règles de transformation") à chaque élément :
  - Retirer `is_shiny`, `is_private`, `is_on_home`, `is_display_form`, `is_released`, `is_premium`, `is_custom` du niveau racine
  - Les regrouper sous `"flags": { ... }`
  - Retirer le champ `region` (pas dans la réponse `/dex/{id}/list`)
  - Ajouter `"dex": { "slug": "<same value as slug>" }` à la racine

  Exemple après transformation d'un item `list_premium.json` :
  ```json
  {
    "dex": { "slug": "redgreenblueyellow" },
    "name": "Red / Green / Blue / Yellow",
    "french_name": "Rouge / Vert / Bleu / Jaune",
    "slug": "redgreenblueyellow",
    "flags": {
      "is_shiny": false,
      "is_private": true,
      "is_on_home": false,
      "is_display_form": true,
      "is_released": true,
      "is_premium": false,
      "is_custom": false
    },
    "display_template": "box"
  }
  ```

- [ ] **Step 3: Mettre à jour les fixtures unitaires dex list**

  Même transformation sur `dex_123.json`, `dex_123_premium.json`, `dex_123_unreleased.json`, `dex_123_unreleased_and_premium.json`.

- [ ] **Step 4: Mettre à jour les snapshots fonctionnels**

  Les snapshots `AlbumDexList/*.json` et `TrainerDexList/*.json` doivent refléter exactement le contenu des moco correspondants (le back ne transforme pas ces données). Mettre à jour les 6 fichiers.

---

### Task 2: Election dex list (`/dex/can_hold_election`) — flags plats → objet `flags`

**Files:**
- Modify: `tests/resources/moco/Api/responses/election_dex_list.json`
- Modify: `tests/resources/moco/Api/responses/election_dex_list_include_premium_dex1.json`
- Modify: `tests/resources/moco/Api/responses/election_dex_list_include_premium_dex1_include_unreleased_dex1.json`
- Modify: `tests/resources/unit/service/api/election_dex_list.json`
- Modify: `tests/resources/unit/service/api/election_dex_list_premium.json`
- Modify: `tests/resources/unit/service/api/election_dex_list_unreleased.json`
- Modify: `tests/resources/unit/service/api/election_dex_list_unreleased_and_premium.json`
- Modify: `tests/resources/functional/controller/ElectionDexList/admin.json`
- Modify: `tests/resources/functional/controller/ElectionDexList/collector.json`
- Modify: `tests/resources/functional/controller/ElectionDexList/trainer.json`

- [ ] **Step 1: Lire `election_dex_list.json` moco**

- [ ] **Step 2: Transformer chaque item selon la règle B**

  Appliquer la règle **B** :
  - Retirer `is_shiny`, `is_private`, `is_on_home`, `is_display_form`, `is_released`, `is_premium`, `is_custom` du niveau racine
  - Les regrouper sous `"flags": { ... }`
  - Retirer `display_template` (pas dans `/dex/can_hold_election`)
  - Conserver `slug`, `original_slug`, `name`, `french_name`, `description`, `french_description`, `dex_total_count`

  Exemple :
  ```json
  {
    "slug": "home",
    "original_slug": "home",
    "name": "Home",
    "french_name": "Home",
    "flags": {
      "is_shiny": false,
      "is_private": false,
      "is_on_home": false,
      "is_display_form": true,
      "is_released": true,
      "is_premium": false,
      "is_custom": false
    },
    "description": "...",
    "french_description": "...",
    "dex_total_count": 61
  }
  ```

- [ ] **Step 3: Mettre à jour les 4 fixtures unitaires `election_dex_list_*.json`**

  Même transformation.

- [ ] **Step 4: Mettre à jour les snapshots fonctionnels `ElectionDexList/*.json`**

---

### Task 3: Reports (`/reports`) — champ `trainer` string → objet

**Files:**
- Modify: `tests/resources/moco/Api/responses/reports.json`
- Modify: `tests/resources/unit/service/api/reports.json`
- Modify: `tests/resources/functional/controller/Admin/reports.json`

- [ ] **Step 1: Lire `tests/resources/moco/Api/responses/reports.json`**

- [ ] **Step 2: Transformer `trainer` string → objet**

  Chaque entrée `catch_state_counts_defined_by_trainer` :
  ```json
  // Avant
  { "count": 5735, "trainer": "f86cbe805674d85f7806b175b70647a6a9334631" }

  // Après
  { "count": 5735, "trainer": { "external_id": "f86cbe805674d85f7806b175b70647a6a9334631" } }
  ```

  Mettre à jour la fixture moco.

- [ ] **Step 3: Même changement sur `tests/resources/unit/service/api/reports.json`**

- [ ] **Step 4: Mettre à jour `tests/resources/functional/controller/Admin/reports.json`**

  Le back (`GetReportsApiService` → `AdminReportsController`) passe les données sans transformation. Le snapshot = contenu du moco.

---

### Task 4: Labels — `game_bundles` déjà OK, `forms_*` snapshots individuels

**Files:**
- Modify: `tests/resources/functional/controller/Labels/all.json`
- Modify: `tests/resources/functional/controller/Labels/game_bundles.json`
- Modify: `tests/resources/functional/controller/Labels/forms_category.json`
- Modify: `tests/resources/functional/controller/Labels/forms_regional.json`
- Modify: `tests/resources/functional/controller/Labels/forms_special.json`
- Modify: `tests/resources/functional/controller/Labels/forms_variant.json`

**Contexte :** Le endpoint `GET /labels` du back agrège `catch_states`, `types`, `category_forms`, `regional_forms`, `special_forms`, `variant_forms`, `game_bundles`, `collections`. Les fixtures moco sont déjà dans le bon format (game_bundles a `generation: {slug: "1"}`). Les snapshots fonctionnels des labels individuels (`forms_category.json`, etc.) correspondent à ce que retourne le back pour des sous-ensembles.

- [ ] **Step 1: Lire le snapshot `Labels/all.json` actuel**

- [ ] **Step 2: Vérifier que le champ `game_bundles` dans `all.json` a `generation: {slug: "1"}`**

  Si ce n'est pas le cas, mettre à jour depuis `tests/resources/moco/Api/responses/game_bundles.json`.

- [ ] **Step 3: Vérifier et mettre à jour `Labels/game_bundles.json`**

  Doit correspondre exactement au contenu de `tests/resources/moco/Api/responses/game_bundles.json`.

- [ ] **Step 4: Vérifier et mettre à jour les snapshots `Labels/forms_*.json`**

  Ces snapshots doivent correspondre aux sous-tableaux de `tests/resources/moco/Api/responses/forms.json` (ex : `forms_category.json` = `forms.json["category"]`).

---

### Task 5: Action logs — changer la sortie du back de keyed-object en tableau

Le back convertissait actuellement la réponse tableau de l'API en objet indexé par `action_type` (via `ActionLogData`). La réponse du back doit maintenant être identique à celle de l'API.

**Files:**
- Modify: `src/Service/Api/GetActionLogsApiService.php`
- Modify: `src/Controller/Admin/AdminActionLogsController.php`
- Delete: `src/DTO/ActionLog.php`
- Delete: `src/DTO/ActionLogData.php`
- Modify: `tests/src/Unit/Service/Api/GetActionLogsApiServiceTest.php`
- Delete: `tests/src/Unit/DTO/ActionLogTest.php`
- Delete: `tests/src/Unit/DTO/ActionLogDataTest.php`
- Modify: `tests/resources/functional/controller/Admin/action-logs.json`

- [ ] **Step 1: Modifier `GetActionLogsApiService.php`**

  Supprimer le parsing via `ActionLogData`. Retourner le tableau décodé directement :

  ```php
  <?php

  declare(strict_types=1);

  namespace App\Service\Api;

  use App\Utils\JsonDecoder;

  class GetActionLogsApiService extends AbstractApiService
  {
      /**
       * @return array<int, array{
       *   action_type: string,
       *   current: array{
       *     created_at: string,
       *     done_at: null|string,
       *     execution_time: null|int,
       *     details: array<string, int>,
       *     error_trace: null|string,
       *   },
       *   last: null|array{
       *     created_at: string,
       *     done_at: null|string,
       *     execution_time: null|int,
       *     details: array<string, int>,
       *     error_trace: null|string,
       *   },
       * }>
       */
      public function get(): array
      {
          $json = $this->requestContent('GET', '/action_logs');

          /** @var array<int, array{action_type: string, current: array{...}, last: null|array{...}}> */
          return JsonDecoder::decode($json);
      }
  }
  ```

- [ ] **Step 2: Modifier `AdminActionLogsController.php`**

  Supprimer le SerializerInterface (plus besoin), utiliser `json_encode` ou `JsonResponse::fromJsonString` :

  ```php
  <?php

  declare(strict_types=1);

  namespace App\Controller\Admin;

  use App\Service\Api\GetActionLogsApiService;
  use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
  use Symfony\Component\HttpFoundation\JsonResponse;
  use Symfony\Component\Routing\Attribute\Route;

  #[Route('/istration')]
  final class AdminActionLogsController extends AbstractController
  {
      public function __construct(
          private readonly GetActionLogsApiService $service,
      ) {}

      #[Route('/action-logs', methods: ['GET'])]
      public function actionLogs(): JsonResponse
      {
          return new JsonResponse($this->service->get());
      }
  }
  ```

- [ ] **Step 3: Supprimer `src/DTO/ActionLog.php` et `src/DTO/ActionLogData.php`**

  Vérifier qu'ils ne sont plus utilisés nulle part avec `grep -r "ActionLog\|ActionLogData" src/`.

- [ ] **Step 4: Supprimer `tests/src/Unit/DTO/ActionLogTest.php` et `tests/src/Unit/DTO/ActionLogDataTest.php`**

- [ ] **Step 5: Modifier `GetActionLogsApiServiceTest.php`**

  Le test doit maintenant vérifier que `get()` retourne un tableau indexé (non plus keyed) :

  ```php
  public function testGet(): void
  {
      $actionLogs = $this->getService()->get();

      $this->assertCount(9, $actionLogs);  // adapter au nb d'entrées dans la fixture

      $this->assertSame('calculate_dex_availabilities', $actionLogs[0]['action_type']);
      $this->assertArrayHasKey('current', $actionLogs[0]);
      $this->assertArrayHasKey('last', $actionLogs[0]);

      // Vérifier la présence des 9 action_types attendus
      $actionTypes = array_column($actionLogs, 'action_type');
      $this->assertContains('calculate_dex_availabilities', $actionTypes);
      $this->assertContains('update_pokemons', $actionTypes);
      // ... etc pour les 9 entrées de la fixture unitaire
  }
  ```

- [ ] **Step 6: Mettre à jour le snapshot fonctionnel `Admin/action-logs.json`**

  Le snapshot doit maintenant être un tableau JSON (comme la fixture moco), et non un objet. Le back retourne directement ce que l'API renvoie.

  Construire le contenu depuis `tests/resources/moco/Api/responses/action_logs.json` — **attention**, le moco a 10 entrées mais la fixture unitaire en a 9 (sans `update_collections_availabilities` peut-être). Vérifier le moco pour le snapshot fonctionnel.

  Le snapshot fonctionnel = le moco `action_logs.json` complet (ce que Moco retourne à l'integration test).

---

### Task 6: Election top — supprimer `pokemon_icon`, ajouter `icon` + `game_bundles`

**Files:**
- Modify: `src/Service/GetElectionTopService.php`
- Modify: `tests/resources/moco/Api/responses/election/demolite_top_5.json`
- Modify: `tests/resources/unit/service/api/election_top_5_4564650_home_fav.json`
- Modify: `tests/resources/unit/service/api/election_top_10_87654_demo_pref.json`
- Modify: `tests/src/Unit/Service/GetElectionTopServiceTest.php`

- [ ] **Step 1: Modifier `GetElectionTopService.php`**

  Supprimer le mapping `pokemon_icon` :

  ```php
  public function getTop(string $dexSlug, string $electionSlug): array
  {
      $trainerId = $this->userTokenService->getLoggedUserToken();

      return $this->apiService->getTop($trainerId, $dexSlug, $electionSlug, $this->topCount);
  }
  ```

  Le `array_map` est supprimé entièrement.

- [ ] **Step 2: Mettre à jour la fixture moco `election/demolite_top_5.json`**

  Ajouter les champs manquants dans chaque item selon la règle **E** :
  - `pokemon.icon` (= valeur du slug)
  - `pokemon.regional_dex_number` (= null)
  - `pokemon.family_order`, `pokemon.family_lead`, `pokemon.original_game_bundle`
  - `pokemon.order_number`
  - `pokemon.game_bundles: { normal: [...], shiny: [...] }` (format objet avec 2 sous-tableaux)
  - `pokemon.labels.forms_label` et `pokemon.labels.forms_french_label` (null ou string)

  Ne plus ajouter `pokemon_icon` (c'est le back qui l'ajoutait, pas l'API).

- [ ] **Step 3: Même mise à jour sur les fixtures unitaires `election_top_5_*.json` et `election_top_10_*.json`**

- [ ] **Step 4: Modifier `GetElectionTopServiceTest.php`**

  Supprimer l'assertion `$this->assertSame('bulbasaur', $result[0]['pokemon']['pokemon_icon'])` et vérifier `$result[0]['pokemon']['icon']` à la place :

  ```php
  $this->assertSame('bulbasaur', $result[0]['pokemon']['icon']);
  $this->assertSame('bulbasaur', $result[0]['pokemon']['slug']);
  ```

  Mettre à jour les données `$apiItem` du test pour inclure les nouveaux champs (`icon`, `game_bundles`, etc.).

---

### Task 7: `pokemons/to_choose` — format plat → imbriqué

**Files:**
- Modify: `src/DTO/ElectionPokemonsList.php`
- Modify: `src/Service/Api/GetPokemonsApiService.php`
- Modify: `tests/src/Unit/DTO/ElectionPokemonsListTest.php`
- Modify: `tests/resources/moco/Api/responses/pokemons_topick_demolite_12.json`
- Modify: `tests/resources/moco/Api/responses/pokemons_topick_demolitelastone_12.json`
- Modify: `tests/resources/moco/Api/responses/pokemons_topick_demolitenotlastone_12.json`
- Modify: `tests/resources/unit/service/api/pokemons_topick_12_123__3.json`
- Modify: `tests/resources/unit/service/api/pokemons_topick_12_123__5_cflegendary.json`
- Modify: `tests/resources/unit/service/api/pokemons_topick_13_123_a_5.json`
- Modify: `tests/resources/unit/service/api/pokemons_tovote_14_all_b_12.json`

- [ ] **Step 1: Mettre à jour l'annotation de type dans `ElectionPokemonsList.php`**

  ```php
  /**
   * @var array<int, array{
   *   pokemon: array{
   *     slug: string,
   *     name: string,
   *     french_name: string,
   *     national_dex_number: int,
   *     regional_dex_number: null|int,
   *     simplified_name: string,
   *     forms_label: string,
   *     simplified_french_name: string,
   *     forms_french_label: string,
   *     icon: string,
   *     family_order: int,
   *     family_lead: null|array{slug: string},
   *     original_game_bundle: array{slug: string},
   *     order_number: string,
   *     game_bundles: array<int, array{slug: string}>,
   *     game_bundles_shiny: array<int, array{slug: string}>,
   *   },
   *   forms: null|array{
   *     category: null|array{slug: string, name: string, french_name: string},
   *     regional: null|array{slug: string, name: string, french_name: string},
   *     special: null|array{slug: string, name: string, french_name: string},
   *     variant: null|array{slug: string, name: string, french_name: string},
   *   },
   *   types: array{
   *     primary: array{slug: string, name: string, french_name: string, color: string},
   *     secondary: null|array{slug: string, name: string, french_name: string, color: string},
   *   },
   * }>
   */
  public array $items;
  ```

  La validation OptionsResolver reste inchangée (elle ne valide que `type: string` et `items: array[]`).

- [ ] **Step 2: Mettre à jour l'annotation de type dans `GetPokemonsApiService.php`**

  Mettre à jour le `@var array{type: string, items: array<...>}` pour refléter la nouvelle structure imbriquée.

- [ ] **Step 3: Mettre à jour le test `ElectionPokemonsListTest.php`**

  Les données de test passent de format plat à format imbriqué :

  ```php
  public function testOk(): void
  {
      $object = new ElectionPokemonsList([
          'type' => 'pick',
          'items' => [
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
                  ],
                  'forms' => null,
                  'types' => [
                      'primary' => ['slug' => 'electric', 'name' => 'Electric', 'french_name' => 'Électrique', 'color' => '#FFCC33'],
                      'secondary' => null,
                  ],
              ],
              [
                  'pokemon' => ['slug' => 'raichu', /* ... */],
                  'forms' => null,
                  'types' => ['primary' => [/* ... */], 'secondary' => null],
              ],
          ],
      ]);

      $this->assertSame('pick', $object->type);
      $this->assertSame('pichu', $object->items[0]['pokemon']['slug']);
      $this->assertSame('raichu', $object->items[1]['pokemon']['slug']);
  }
  ```

  Mettre à jour aussi les tests `testMissingType()`, `testWrongType()`, `testMissingItems()`, `testWrongItems()` avec le nouveau format d'items.

- [ ] **Step 4: Remplacer les fixtures moco `pokemons_topick_*.json` par le nouveau format**

  Pour chaque fixture, transformer tous les items selon la règle **F**.

  La fixture `pokemons_topick_demolite_12.json` contient 12 pokémons en format plat. Les convertir en 12 items imbriqués. Le nombre d'items doit rester identique.

  Adapter `pokemons_tovote_14_all_b_12.json` — même transformation, `type: "vote"`.

- [ ] **Step 5: Remplacer les fixtures unitaires `pokemons_topick_*.json` et `pokemons_tovote_*.json`**

  Même transformation.

---

### Task 8: Album (`/album/{trainerId}/{dexSlug}`) — format plat → imbriqué

**Files:**
- Modify: `src/Service/Api/GetPokedexApiService.php`
- Modify: `src/Service/GetTrainerPokedexService.php`
- Modify: `tests/src/Unit/Service/Api/GetPokedexApiServiceTest.php`
- Modify: `tests/resources/unit/service/api/pokedex_lite_123.json`
- Modify: `tests/resources/unit/service/api/pokedex_lite_123_csyes.json`
- Modify: `tests/resources/unit/service/api/pokedex_lite_123_cs!yes.json`
- Modify: tous les `tests/resources/moco/Api/responses/album/default/*.json` (7 fichiers)
- Modify: tous les `tests/resources/moco/Api/responses/album/7b52009b.../*.json` (11 fichiers)
- Modify: tous les `tests/resources/functional/controller/Pokedex/*.json`

- [ ] **Step 1: Mettre à jour l'annotation de retour dans `GetPokedexApiService.php`**

  ```php
  /**
   * @param string[]|string[][] $filters
   *
   * @return array{
   *   dex: null|array{
   *     slug: string,
   *     original_slug: string,
   *     name: string,
   *     french_name: string,
   *     flags: array{
   *       is_shiny: bool,
   *       is_private: bool,
   *       is_on_home: bool,
   *       is_display_form: bool,
   *       is_released: bool,
   *       is_premium: bool,
   *       is_custom: bool,
   *     },
   *     display_template: string,
   *     region: null|array{name: string, french_name: string},
   *     selection_rule: string,
   *     description: string,
   *     french_description: string,
   *     version: string,
   *   },
   *   pokemons: array<int, array{
   *     pokemon: array{slug: string, name: string, ...},
   *     catch_state: null|array{slug: string, name: string, french_name: string, color: string},
   *     forms: array{category: null|array{...}, regional: null|array{...}, special: null|array{...}, variant: null|array{...}},
   *     types: array{primary: array{...}, secondary: null|array{...}},
   *   }>,
   *   report: array{total: int, total_caught: int, total_uncaught: int, detail: array<int, array{catch_state: array{...}, count: int}>},
   *   filtered_report: array{...},
   * }
   */
  public function get(string $dexSlug, string $trainerId, array $filters = []): array
  ```

- [ ] **Step 2: Mettre à jour `GetTrainerPokedexService.php`**

  Même annotation de retour que `GetPokedexApiService.get()`. La vérification `empty($data['dex'])` reste valide car le nouveau format conserve la clé `dex` (null si inconnu).

- [ ] **Step 3: Remplacer `tests/resources/unit/service/api/pokedex_lite_123.json`**

  Appliquer la règle **G** :
  - `dex` → nouvelles propriétés (`flags: {}`, `region: null`, `selection_rule`, `version` string)
  - `pokemons` → chaque item passe en format imbriqué
  - Conserver `report` et `filtered_report` si présents

  **Attention :** Le test `GetPokedexApiServiceTest.testGet()` vérifie `assertCount(41, $pokedex['pokemons'])`. Le nombre de pokémons dans la fixture doit rester 41. Compter les items après transformation.

  Les `game_bundles` passent de `["slug1", "slug2"]` à `[{"slug": "slug1"}, {"slug": "slug2"}]`.

- [ ] **Step 4: Même transformation sur `pokedex_lite_123_csyes.json`**

  Ce fichier filtre par `catch_states: [yes]` — le test vérifie `assertCount(2, $pokedex['pokemons'])`. Conserver 2 items.

- [ ] **Step 5: Même transformation sur `pokedex_lite_123_cs!yes.json`**

  Filtre `!yes` — vérifier le nombre d'items actuels pour conserver la même assertion dans le test (41 d'après le test `testGetWithMultiplesNegativeFilters`).

- [ ] **Step 6: Remplacer les 7 fixtures moco `album/default/*.json`**

  Pour chaque fichier, appliquer la règle **G** à tous les items. La structure globale `{dex, pokemons, report, filtered_report}` est conservée ; les données internes changent.

- [ ] **Step 7: Remplacer les 11 fixtures moco `album/7b52009b.../*.json`**

  Même transformation.

- [ ] **Step 8: Mettre à jour les snapshots fonctionnels `Pokedex/*.json`**

  Chaque snapshot = la réponse que le back retourne = le contenu du moco correspondant (le back passe les données sans transformation pour ce endpoint).

  Mapping moco → snapshot :
  - `album/default/demo-lite.json` → `Pokedex/demolite.json` (pour trainer de hash par défaut)
  - `album/default/demo.json` → `Pokedex/demo.json`
  - `album/default/demo_list3.json` → `Pokedex/demolist3.json`
  - `album/default/allshinies.json` → `Pokedex/allshinies.json`
  - `album/7b52009b.../demo.json` → `Pokedex/demolite_f86cbe*.json` (vérifier dans `AlbumPokedexTest.php`)
  - `album/7b52009b.../demo_primarytypes0fire.json` → `Pokedex/filters_t1-fire.json`
  - etc. — vérifier le mapping exact dans `AlbumPokedexTest.php`

---

### Task 9: Snapshots `ElectionIndex` — mise à jour après tasks 6, 7, 8

Cette task dépend des tasks 6 (election top), 7 (pokemons), et 8 (album/pokedex). Elle met à jour les 5 snapshots fonctionnels `ElectionIndex/*.json`.

**Files:**
- Modify: `tests/resources/functional/controller/ElectionIndex/demolite.json`
- Modify: `tests/resources/functional/controller/ElectionIndex/demolitelastone.json`
- Modify: `tests/resources/functional/controller/ElectionIndex/demolitelastpage.json`
- Modify: `tests/resources/functional/controller/ElectionIndex/demolitenotlastone.json`
- Modify: `tests/resources/functional/controller/ElectionIndex/demolitenotlastpage.json`

- [ ] **Step 1: Comprendre la structure composée de `ElectionIndexController`**

  Le controller assemble :
  ```json
  {
    "type": "<from /pokemons/to_choose>",
    "pokemons": "<items array from /pokemons/to_choose — new nested format>",
    "pokedex": "<full /album response — new nested format>",
    "election_top": "<array from /election/top — already new format, sans pokemon_icon>",
    "metrics": "<ElectionMetrics DTO — propriétés camelCase serialisées>",
    "detached_count": "<count of items in election_top where score.significance == true>",
    "is_the_last_one": "<bool>",
    "is_the_last_page": "<bool>"
  }
  ```

  `metrics` est sérialisé par le `SerializerInterface` Symfony depuis l'objet `ElectionMetrics`. Les propriétés PHP camelCase (`viewCountSum`) sont sérialisées en **camelCase** (sans `#[SerializedName]`). Exemple :
  ```json
  "metrics": {
    "viewCountSum": 82,
    "winCountSum": 41,
    "viewCountMax": 1,
    "winCountMax": 1,
    "underMaxViewCount": 1,
    "maxViewCount": 5,
    "dexTotalCount": 48,
    "roundCount": ...,
    "winnerAverage": ...,
    "totalRoundCount": ...
  }
  ```

- [ ] **Step 2: Pour chaque snapshot `ElectionIndex/*.json`, reconstruire le JSON**

  Pour `demolite.json` (cas nominal) :
  - `type`: lire depuis `pokemons_topick_demolite_12.json` (après Task 7)
  - `pokemons`: les `items` de `pokemons_topick_demolite_12.json` (après Task 7)
  - `pokedex`: contenu de `album/default/demo-lite.json` (après Task 8) — c'est le dex `demolite` pour le trainer par défaut
  - `election_top`: contenu de `election/demolite_top_5.json` (après Task 6, sans `pokemon_icon`)
  - `metrics`: calculer depuis `election/demolite_metrics.json` (`{view_count: {sum: 82, max: 1}, win_count: {sum: 41, max: 1}, completion: {under_max_count: 1, at_max_count: 5}, dex_total_count: 48}`) avec `perViewCount = 12` (valeur du service `electionCandidateCount`)
  - `detached_count`: compter les items de `election_top` avec `score.significance == true`
  - `is_the_last_page`: `underMaxViewCount == 0 && maxViewCount == count(list.items)` → `1 == 0 ? → false` → `false`
  - `is_the_last_one`: `is_the_last_page && maxViewCount == 1`

  Pour les autres snapshots (`demolitelastone`, `demolitelastpage`, etc.), identifier quels moco ils utilisent via `ElectionIndexTest.php` et les paramètres de route.

- [ ] **Step 3: Recalculer les valeurs `metrics` pour chaque cas**

  L'objet `ElectionMetrics` calcule :
  - `roundCount = round(viewCountSum / perViewCount)` avec `perViewCount = 12`
  - `winnerAverage = (roundCount == 0) ? 4.0 : round(winCountSum / roundCount, 2)`
  - `totalRoundCount = TotalRoundCountHelper::calculate(dexTotalCount, perViewCount, winnerAverage)`

  Recalculer pour chaque fixture de metrics utilisée.

- [ ] **Step 4: Vérifier `ElectionVoteController` — pas de changement de réponse**

  Le controller vote retourne une réponse vide (200). Le test `ElectionVoteTest.php` n'a pas de snapshot à mettre à jour.

---

## Self-Review Checklist

Après avoir écrit le plan, vérifier :

- [ ] Tous les endpoints de `endpoints.md` qui ont changé sont couverts (dex list, election dex list, reports, action logs, election top, pokemons to_choose, album)
- [ ] Pas de "TBD" dans le plan
- [ ] Les annotations de type PHP correspondent aux nouvelles structures
- [ ] Les snapshots fonctionnels = ce que le back retourne (= moco pour les endpoints passthrough)
- [ ] `GetElectionTopService` : `pokemon_icon` supprimé partout
- [ ] `GetActionLogsApiService` : `ActionLog` et `ActionLogData` supprimés partout
- [ ] `ElectionMetrics` : pas de changement de code (lit déjà le bon format)
- [ ] `GetFormsApiService` : pas de changement (déjà migré)
- [ ] `ModifyElectionVoteApiService` : pas de changement (déjà migré)
- [ ] Les fixtures unitaires de `GetPokedexApiServiceTest` ont les bons comptes de pokémons
