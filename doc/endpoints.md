# Endpoints — Pokenini Back

Documentation des endpoints exposés par pokenini-back et des structures JSON associées.

---

## Structures communes

### Objet `pokemon` (pokédex)

Utilisé dans les réponses de `/album/{dexSlug}` et `/election/{dexSlug}/{electionSlug}` (liste `pokemons`).

```json
{
  "slug": "venusaur-mega",
  "name": "Mega Venusaur",
  "french_name": "Mega Florizarre",
  "national_dex_number": 3,
  "regional_dex_number": null,
  "simplified_name": "Venusaur",
  "forms_label": "Mega",
  "simplified_french_name": "Florizarre",
  "forms_french_label": "Mega",
  "icon": "venusaur-mega",
  "family_order": 4,
  "family_lead": { "slug": "bulbasaur" },
  "original_game_bundle": { "slug": "redgreenblueyellow" },
  "order_number": "9999-0003-004",
  "game_bundles": [{ "slug": "redgreenblueyellow" }],
  "game_bundles_shiny": [{ "slug": "redgreenblueyellow" }]
}
```

### Objet `pokemon` (election_top)

Utilisé uniquement dans le champ `election_top` de `/election/{dexSlug}/{electionSlug}`.

```json
{
  "slug": "venusaur-mega",
  "labels": {
    "name": "Mega Venusaur",
    "simplified_name": "Venusaur",
    "french_name": "Mega Florizarre",
    "simplified_french_name": "Florizarre",
    "forms_label": "Mega",
    "forms_french_label": "Mega"
  },
  "national_dex_number": 3,
  "regional_dex_number": null,
  "icon": "venusaur-mega",
  "family_order": 2,
  "family_lead": { "slug": "bulbasaur" },
  "original_game_bundle": { "slug": "redgreenblueyellow" },
  "order_number": "9999-0003-020",
  "game_bundles": {
    "normal": [{ "slug": "redgreenblueyellow" }],
    "shiny": [{ "slug": "redgreenblueyellow" }]
  }
}
```

### Objet `forms` (pokédex / liste election)

Présent dans chaque entrée de `pokemons`. Toutes les clés sont toujours présentes, `null` si non applicable.

```json
{
  "category": { "slug": "starter", "name": "Starter", "french_name": "de Départ" },
  "regional": { "slug": "alolan", "name": "Alolan", "french_name": "d'Alola" },
  "special": { "slug": "mega", "name": "Mega", "french_name": "Mega" },
  "variant": { "slug": "gender", "name": "Gender", "french_name": "Sexe" }
}
```

### Objet `forms` (election_top)

Dans `election_top`, `forms` est `null` si aucune forme, sinon contient uniquement la/les clés pertinentes.

```json
null
{ "special": { "slug": "mega", "name": "Mega", "french_name": "Mega" } }
{ "variant": { "slug": "gender", "name": "Gender", "french_name": "Sexe" } }
```

### Objet `types`

```json
{
  "primary": { "slug": "grass", "name": "Grass", "french_name": "Plante", "color": "#78C850" },
  "secondary": { "slug": "poison", "name": "Poison", "french_name": "Poison", "color": "#A040A0" }
}
```

`secondary` peut être `null`.

### Objet `catch_state`

```json
{ "slug": "yes", "name": "Yes", "french_name": "Oui", "color": "#66bb6a" }
```

Peut être `null` si le Pokémon n'a pas d'état de capture défini.

### Objet `flags` (dex)

```json
{
  "is_shiny": false,
  "is_private": false,
  "is_on_home": true,
  "is_display_form": true,
  "is_released": true,
  "is_premium": false,
  "is_custom": false
}
```

---

## GET /album/dex

Liste des pokédex disponibles pour l'album. Le contenu varie selon le rôle :
- `trainer` / `collector` : dex released + premium
- `admin` : tous les dex (unreleased inclus)

Requiert un utilisateur connecté (`401` sinon).

### Réponse

```json
[
  {
    "dex": { "slug": "redgreenblueyellow" },
    "name": "Red, Green, Blue, Yellow",
    "french_name": "Rouge, Vert, Bleu, Jaune",
    "slug": "redgreenblueyellow",
    "flags": {
      "is_shiny": true,
      "is_private": true,
      "is_on_home": true,
      "is_display_form": true,
      "is_released": true,
      "is_premium": false,
      "is_custom": false
    },
    "display_template": "box"
  }
]
```

**Notes :**
- `display_template` : `"box"` | `"list-3"` | `"list-7"`
- `dex.slug` : redondant avec `slug` racine (héritage de l'API)

---

## GET /album/{dexSlug}

Pokédex complet d'un dresseur pour un dex donné, avec tous les Pokémon et leur état de capture. Supporte des filtres via query params.

**Contrôle d'accès :**
- `404` si le dex n'existe pas ou si le dresseur n'a pas de compte
- `404` si le dex est `is_private` et que le dresseur connecté n'est pas le propriétaire
- `404` si le dex est `is_released: false` et que l'utilisateur n'est pas admin

### Query params (filtres)

| Param | Exemple | Description |
|-------|---------|-------------|
| `cs` | `yes`, `no`, `!no` | catch_state slug (préfixe `!` = exclusion) |
| `t1` | `fire` | type primaire |
| `t2` | `poison,flying` | types secondaires (virgule = ET) |
| `at` | `fire` | n'importe quel type |
| `fc` | `starter` | forme catégorie |
| `fr` | `paldean` | forme régionale |
| `fs` | `mega,gigantamax` | formes spéciales (virgule = OU) |
| `fv` | `alternate` | forme variante |
| `f`  | `bulbasaur` | famille (slug du lead) |
| `ogb` | `swordshield` | original_game_bundle |
| `ca` | `pogoshadow` | collection availability |

### Réponse

```json
{
  "pokedex": {
    "dex": {
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
    },
    "pokemons": [
      {
        "pokemon": { "...": "voir structure commune pokemon (pokédex)" },
        "catch_state": null,
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
    ],
    "report": {
      "total": 37,
      "total_caught": 20,
      "total_uncaught": 17,
      "detail": [
        {
          "catch_state": { "slug": "no", "name": "No", "french_name": "Non", "color": "#e57373" },
          "count": 1
        },
        {
          "catch_state": { "slug": "toevolve", "name": "To evolve", "french_name": "af. évoluer", "color": "#9575cd" },
          "count": 1
        },
        {
          "catch_state": { "slug": "tobreed", "name": "To breed", "french_name": "af. reproduire", "color": "#4fc3f7" },
          "count": 1
        },
        {
          "catch_state": { "slug": "totransfer", "name": "To transfer", "french_name": "à transférer", "color": "#ffd54f" },
          "count": 1
        },
        {
          "catch_state": { "slug": "totrade", "name": "To trade", "french_name": "À échanger", "color": "#ff9100" },
          "count": 0
        },
        {
          "catch_state": { "slug": "yes", "name": "Yes", "french_name": "Oui", "color": "#66bb6a" },
          "count": 20
        }
      ]
    },
    "filtered_report": {
      "total": 1,
      "total_caught": 0,
      "total_uncaught": 1,
      "detail": [
        { "catch_state": { "slug": "no", ... }, "count": 1 },
        { "catch_state": { "slug": "yes", ... }, "count": 0 }
      ]
    }
  },
  "filters": {
    "catch_states": ["no"]
  }
}
```

Quand aucun filtre n'est actif : `"filtered_report": []` et `"filters": []`.

**Notes :**
- `report` : statistiques sur l'ensemble du dex (sans filtre), toujours présent
- `filtered_report` : même structure complète que `report` (objet avec `total`, `total_caught`, `total_uncaught`, `detail`) appliquée aux Pokémon filtrés ; `[]` (tableau vide) quand aucun filtre n'est actif
- `filters` : objet listant les filtres actifs par clé (ex. `{ "catch_states": ["no"] }`) ; `[]` quand aucun filtre
- `dex.region` : `null` si aucune région, sinon `{ "name": "Kanto", "french_name": "Kanto" }` (peut avoir des chaînes vides si la région n'a pas de nom configuré)

---

## PATCH|PUT /album/{dexSlug}/{pokemonSlug}

Met à jour l'état de capture d'un Pokémon dans un album. Requiert `ROLE_TRAINER`. Rate-limited.

**Contrôle d'accès :**
- `404` si le dex n'existe pas
- `404` si le dex est `is_premium: true` et que l'utilisateur n'a pas `ROLE_COLLECTOR`
- `400` si le body est vide ou JSON invalide

### Corps de la requête

```json
{ "catch_state": "yes" }
```

Le champ `catch_state` doit correspondre à un slug valide retourné par `/labels`.

### Réponse

- `200` (body vide) si succès
- `400` si body invalide
- `404` si dex introuvable ou accès refusé
- `500` si l'API distante échoue

---

## GET /election/dex

Liste des pokédex disponibles pour les élections. Le contenu varie selon le rôle :
- `trainer` (non-collector) : dex released non-premium
- `collector` : dex released + premium
- `admin` : tous les dex (unreleased inclus)

### Réponse

```json
[
  {
    "slug": "home",
    "original_slug": "home",
    "name": "Home",
    "french_name": "Home",
    "flags": {
      "is_shiny": false,
      "is_private": false,
      "is_on_home": true,
      "is_display_form": true,
      "is_released": true,
      "is_premium": false,
      "is_custom": false
    },
    "description": "All Pokémons that can be transferred to Pokémon Home.",
    "french_description": "Tous les pokémons pouvant être transférés sur Pokémon Home.",
    "dex_total_count": 61
  }
]
```

**Différence avec `/album/dex` :** les items d'élection exposent `original_slug`, `description`, `french_description`, `dex_total_count` — mais pas `dex` (objet imbriqué) ni `display_template`.

---

## GET /election/{dexSlug}/{electionSlug}

Retourne les données nécessaires à l'affichage d'une page d'élection. Supporte les mêmes query params de filtres que `/album/{dexSlug}`.

### Réponse

```json
{
  "type": "pick",
  "pokemons": [
    {
      "pokemon": { "...": "voir structure commune pokemon (pokédex)" },
      "forms": {
        "category": null,
        "regional": null,
        "special": { "slug": "mega", "name": "Mega", "french_name": "Mega" },
        "variant": null
      },
      "types": {
        "primary": { "slug": "grass", "name": "Grass", "french_name": "Plante", "color": "#78C850" },
        "secondary": { "slug": "poison", "name": "Poison", "french_name": "Poison", "color": "#A040A0" }
      }
    }
  ],
  "pokedex": {
    "dex": { "...": "voir structure dex dans /album/{dexSlug}" },
    "pokemons": [ { "...": "avec catch_state" } ],
    "report": { "...": "voir structure report" },
    "filtered_report": []
  },
  "election_top": [
    {
      "pokemon": {
        "slug": "venusaur-mega",
        "labels": {
          "name": "Mega Venusaur",
          "simplified_name": "Venusaur",
          "french_name": "Mega Florizarre",
          "simplified_french_name": "Florizarre",
          "forms_label": "Mega",
          "forms_french_label": "Mega"
        },
        "national_dex_number": 3,
        "regional_dex_number": null,
        "icon": "venusaur-mega",
        "family_order": 2,
        "family_lead": { "slug": "bulbasaur" },
        "original_game_bundle": { "slug": "redgreenblueyellow" },
        "order_number": "9999-0003-020",
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
      "score": {
        "elo": 1040,
        "significance": true
      }
    }
  ],
  "metrics": {
    "view_count": { "sum": 82, "max": 1 },
    "win_count":  { "sum": 41, "max": 1 },
    "completion": { "under_max_count": 1, "at_max_count": 5 },
    "dex_total_count": 48,
    "round_count": 7,
    "total_round_count": 8,
    "winner_average": 5.86
  },
  "detached_count": 1,
  "is_the_last_one": false,
  "is_the_last_page": false
}
```

**Notes :**
- `type` : `"pick"` (seule valeur connue actuellement)
- `pokemons` : Pokémon à afficher pour voter — pas de `catch_state`
- `pokedex` : pokédex du dresseur connecté, `null` si non connecté ou dex introuvable
- `election_top` : classement Elo. `forms` est `null` si aucune forme spéciale, sinon objet avec uniquement les clés pertinentes (`special`, `variant`, etc.)
- `election_top[].pokemon.game_bundles` : objet `{ normal: [...], shiny: [...] }` — différent de `pokemons[].pokemon.game_bundles` qui est un tableau plat
- `election_top[].pokemon.labels.forms_label` : `null` si non applicable (vs `""` dans les pokemon du pokédex)
- `score.significance` : `true` si l'Elo est statistiquement significatif (contribue à `detached_count`)
- `metrics` : passthrough du format retourné par l'API (`/election/metrics`), enrichi de `round_count`, `total_round_count` et `winner_average` — calculés par le Back à partir de `ELECTION_CANDIDATE_COUNT` (voir `TotalRoundCountHelper`), champs que l'Api ne fournit pas
- `metrics.completion.under_max_count` : nombre de Pokémon n'ayant pas atteint le max de vues
- `metrics.completion.at_max_count` : nombre de Pokémon ayant atteint le max de vues
- `is_the_last_page` : vrai si tous les Pokémon ont atteint le max de vues et remplissent une page
- `is_the_last_one` : vrai si `is_the_last_page` et qu'il ne reste qu'un seul Pokémon

---

## POST /election/{dexSlug}/{electionSlug}

Soumet un vote d'élection. Rate-limited.

### Corps de la requête

```json
{
  "winnersSlugs": ["bulbasaur", "venusaur-mega"],
  "losersSlugs": ["ivysaur", "venusaur"]
}
```

### Réponse

- `200` : `[]` (succès)

---

## GET /labels

Retourne tous les labels de référence utilisés dans l'application.

### Réponse

```json
{
  "catch_states": [
    { "name": "No", "french_name": "Non", "slug": "no", "color": "#e57373" },
    { "name": "To evolve", "french_name": "af. évoluer", "slug": "toevolve", "color": "#9575cd" },
    { "name": "To breed", "french_name": "af. reproduire", "slug": "tobreed", "color": "#4fc3f7" },
    { "name": "To transfer", "french_name": "à transférer", "slug": "totransfer", "color": "#ffd54f" },
    { "name": "To trade", "french_name": "À échanger", "slug": "totrade", "color": "#ff9100" },
    { "name": "Yes", "french_name": "Oui", "slug": "yes", "color": "#66bb6a" }
  ],
  "types": [
    { "name": "Normal", "french_name": "Normal", "slug": "normal", "color": "#9199a1" }
  ],
  "category_forms": [
    { "name": "Starter", "french_name": "de Départ", "slug": "starter" },
    { "name": "Legendary", "french_name": "Légendaire", "slug": "legendary" },
    { "name": "Mythical", "french_name": "Fabuleux", "slug": "mythical" },
    { "name": "Ultra Beast", "french_name": "Ultra Chimère", "slug": "ultrabeast" },
    { "name": "Baby", "french_name": "Bébé", "slug": "baby" },
    { "name": "Paradox", "french_name": "Paradoxe", "slug": "paradox" }
  ],
  "regional_forms": [
    { "name": "Alolan", "french_name": "d'Alola", "slug": "alolan" },
    { "name": "Galarian", "french_name": "de Galar", "slug": "galarian" },
    { "name": "Hisuian", "french_name": "de Hisui", "slug": "hisuian" },
    { "name": "Paldean", "french_name": "de Paldea", "slug": "paldean" }
  ],
  "special_forms": [
    { "name": "Mega", "french_name": "Mega", "slug": "mega" },
    { "name": "Primal", "french_name": "Originelle", "slug": "primal" },
    { "name": "Gigantamax", "french_name": "Gigamax", "slug": "gigantamax" },
    { "name": "Totem", "french_name": "Dominant", "slug": "totem" },
    { "name": "Alpha", "french_name": "Baron", "slug": "alpha" },
    { "name": "Event", "french_name": "Événementiel", "slug": "event" },
    { "name": "Terastal", "french_name": "Stellaire", "slug": "terastal" }
  ],
  "variant_forms": [
    { "name": "Gender", "french_name": "Genre", "slug": "gender" },
    { "name": "Alternate", "french_name": "Alternatif", "slug": "alternate" },
    { "name": "Therian", "french_name": "Totémique", "slug": "therian" },
    { "name": "Battle", "french_name": "Combat", "slug": "battle" },
    { "name": "Item", "french_name": "Objet", "slug": "item" },
    { "name": "Fusion", "french_name": "Fusion", "slug": "fusion" },
    { "name": "Unobtainable", "french_name": "Non obtenable", "slug": "unobtainable" }
  ],
  "game_bundles": [
    {
      "name": "Red, Green, Blue, Yellow",
      "french_name": "Rouge, Vert, Bleu, Jaune",
      "slug": "redgreenblueyellow",
      "generation": { "slug": "1" }
    }
  ],
  "collections": [
    {
      "name": "Sword, Shield - Dynamax Adventures bosses",
      "french_name": "Sword, Shield - Boss des expéditions Dynamax",
      "slug": "swshdynamaxadventuresbosses",
      "order_number": 11
    }
  ]
}
```

---

## GET /trainer/dex

Liste de tous les pokédex du dresseur connecté, y compris les dex unreleased et premium. Requiert `ROLE_TRAINER`.

### Réponse

Même structure que `/album/dex` (tableau d'objets avec `dex`, `name`, `french_name`, `slug`, `flags`, `display_template`).

---

## PUT /trainer/dex/{dexSlug}

Met à jour les options d'un pokédex pour le dresseur connecté. Requiert `ROLE_TRAINER`. Rate-limited.

**Contrôle d'accès :**
- `404` si le dex n'existe pas
- `404` si le dex est `is_premium: true` et que l'utilisateur n'a pas `ROLE_COLLECTOR`
- `400` si le body est vide ou JSON invalide

### Corps de la requête

JSON libre transmis à pokenini-api (structure définie côté API).

### Réponse

- `200` (body vide) si succès
- `400` si body invalide
- `404` si dex introuvable ou accès refusé
- `500` si l'API distante échoue

---

## GET /user

Retourne les informations de l'utilisateur connecté.

### Réponse

```json
{
  "id": "this-is-trainer_s-id",
  "provider": "mock",
  "roles": ["ROLE_USER", "ROLE_TRAINER"],
  "profile": "trainer"
}
```

**Notes :**
- `provider` : `"google"` | `"discord"` | `"mock"` (dev/test uniquement)
- `roles` : liste des rôles Symfony (`ROLE_USER` toujours présent, plus `ROLE_TRAINER` | `ROLE_COLLECTOR` | `ROLE_ADMIN`)
- `profile` : nom lisible du rôle principal

---

## GET /istration/reports

Rapports d'utilisation agrégés. Réservé aux admins.

### Réponse

```json
{
  "catch_state_counts_defined_by_trainer": [
    {
      "count": 5735,
      "trainer": { "external_id": "f86cbe805674d85f7806b175b70647a6a9334631" }
    }
  ],
  "dex_usage": [
    {
      "count": 2,
      "dex": { "slug": "homeshiny", "name": "Home Shiny", "french_name": "Home Chromatique" }
    }
  ],
  "catch_state_usage": [
    {
      "count": 36,
      "catch_state": { "slug": "no", "name": "No", "french_name": "Non", "color": "#e57373" }
    }
  ]
}
```

---

## GET /istration/action-logs

Logs des actions d'administration (updates, calculs). Réservé aux admins.

### Réponse

```json
[
  {
    "action_type": "calculate_dex_availabilities",
    "current": {
      "created_at": "2023-03-21T09:14:36+00:00",
      "done_at": null,
      "execution_time": null,
      "details": [],
      "error_trace": null
    },
    "last": {
      "created_at": "2023-03-20T09:14:36+00:00",
      "done_at": "2023-03-20T10:05:08+00:00",
      "execution_time": 3032,
      "details": { "dex_availabilities": 22472 },
      "error_trace": null
    }
  }
]
```

**Notes :**
- `current` : exécution en cours (ou dernière si pas d'exécution active)
- `last` : avant-dernière exécution complète (`null` si aucune)
- `done_at` : `null` si l'action est en cours d'exécution
- `execution_time` : en secondes, `null` si en cours
- `details` : objet avec les compteurs spécifiques à l'action, ou `[]` si vide
- `error_trace` : message d'erreur si l'action a échoué, `null` sinon

---

## POST /istration/action/update/{name}

Déclenche une mise à jour de données depuis l'API. Requiert admin. Rate-limited.

**Valeurs acceptées pour `{name}` :**
`labels`, `games_collections_and_dex`, `pokemons`, `games_availabilities`, `games_shinies_availabilities`, `regional_dex_numbers`, `collections_availabilities`

### Réponse

```json
{
  "action": "update",
  "item": "labels",
  "state": "ok",
  "content": "",
  "error": ""
}
```

- `202` si succès (`state: "ok"`)
- `500` si échec (`state: "ko"`, `error` contient le message)

---

## POST /istration/action/calculate/{name}

Déclenche un calcul d'agrégats. Requiert admin. Rate-limited.

**Valeurs acceptées pour `{name}` :**
`game_bundles_availabilities`, `game_bundles_shinies_availabilities`, `collections_availabilities`, `dex_availabilities`, `pokemon_availabilities`

### Réponse

Même structure que `/istration/action/update/{name}` avec `"action": "calculate"`.

---

## DELETE /istration/action/invalidate/{name}

Invalide une entrée de cache. Requiert admin. Rate-limited.

**Valeurs acceptées pour `{name}` :**
`labels`, `dex`, `albums`, `reports`, `actions`

### Réponse

Même structure que `/istration/action/update/{name}` avec `"action": "invalidate"`.
