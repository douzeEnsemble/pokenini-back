# Corrections — Différences Back vs Api

Le Back **ne doit pas** modifier les données ni les formats des réponses de l'Api. Ce document liste toutes les divergences identifiées entre ce que l'Api retourne et ce que le Back expose.

---

## ~~1. `GET /election/{dexSlug}/{electionSlug}` — champ `metrics`~~ ✅ Corrigé

Le champ `metrics` est désormais un passthrough direct du format retourné par l'API.
`GetElectionMetricsApiService` retourne le JSON brut, `ElectionMetrics` stocke `raw` sans transformation.
`TotalRoundCountHelper` et les champs calculés (`round_count`, `winner_average`, `total_round_count`) ont été supprimés.

---

## 2. `GET /election/{dexSlug}/{electionSlug}` — champs calculés ajoutés

Le Back ajoute trois champs qui n'existent pas dans l'Api :

| Champ Back | Provenance |
|---|---|
| `detached_count` | Compté par le Back à partir de `election_top[].score.significance` |
| `is_the_last_page` | Calculé : `underMaxViewCount == 0 && maxViewCount == count(pokemons)` |
| `is_the_last_one` | Calculé : `is_the_last_page && maxViewCount == 1` |

### Fichier concerné

- `src/Controller/Election/ElectionIndexController.php` (lignes 54–62)

---

## 3. `GET /album/{dexSlug}` — enveloppe de la réponse

### Ce que l'Api retourne (`/album/{trainerId}/{dexSlug}`)

```json
{
  "dex": { ... },
  "pokemons": [ ... ],
  "report": { ... },
  "filtered_report": { ... }
}
```

### Ce que le Back expose

```json
{
  "pokedex": {
    "dex": { ... },
    "pokemons": [ ... ],
    "report": { ... },
    "filtered_report": { ... }
  },
  "filters": { "catch_states": ["no"] }
}
```

### Transformations appliquées

| Transformation | Détail |
|---|---|
| Enveloppe | La réponse Api est encapsulée sous la clé `pokedex` |
| Ajout | Clé `filters` construite à partir des query params de la requête (par `FromRequest::get()`) |

### Fichier concerné

- `src/Controller/Album/AlbumPokedexController.php` (lignes 49–55)

---

## 4. `GET /labels` — assemblage depuis 5 endpoints Api

Le Back agrège 5 appels Api distincts et les réunit sous un seul objet :

| Clé exposée par le Back | Endpoint Api appelé |
|---|---|
| `catch_states` | `GET /catch_states` |
| `types` | `GET /types` |
| `category_forms` | `GET /forms` → clé `category` |
| `regional_forms` | `GET /forms` → clé `regional` |
| `special_forms` | `GET /forms` → clé `special` |
| `variant_forms` | `GET /forms` → clé `variant` |
| `game_bundles` | `GET /game_bundles` |
| `collections` | `GET /collections` |

L'Api expose les labels de formes via un seul endpoint `/forms` qui retourne `{ category, regional, special, variant }`. Le Back les réexpose sous 4 clés distinctes suffixées `_forms`.

### Fichier concerné

- `src/Controller/Labels/LabelsController.php`

---

## 5. `POST /istration/action/update/{name}` et `POST /istration/action/calculate/{name}` et `DELETE /istration/action/invalidate/{name}` — réponse entièrement reconstruite

L'Api (`/istration/update/{type}` et `/istration/calculate/{type}`) retourne un corps vide (`204`) en cas de succès, ou une erreur HTTP en cas d'échec.

Le Back construit et expose une réponse `AdminAction` créée de toutes pièces :

```json
{
  "action":  "update",
  "item":    "labels",
  "state":   "ok",
  "content": "",
  "error":   ""
}
```

Ce format n'est pas une rediffusion de l'Api — il est entièrement généré par le Back.

### Fichiers concernés

- `src/DTO/AdminAction.php`
- `src/Controller/Admin/AbstractAdminActionController.php`

---

## Récapitulatif

| Endpoint Back | Type de différence |
|---|---|
| ~~`GET /election/{dex}/{election}` → `metrics`~~ | ~~Aplatissement + renommage + 3 champs calculés~~ ✅ Corrigé |
| `GET /election/{dex}/{election}` → `detached_count`, `is_the_last_page`, `is_the_last_one` | Champs calculés absents de l'Api |
| `GET /album/{dexSlug}` | Enveloppe `pokedex` + ajout clé `filters` |
| `GET /labels` | Assemblage de 5 appels + renommage des clés de formes |
| `POST|DELETE /istration/action/*` | Réponse entièrement reconstruite (DTO `AdminAction`) |
