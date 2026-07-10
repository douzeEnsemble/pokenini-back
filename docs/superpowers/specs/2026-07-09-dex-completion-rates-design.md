# Taux de complétion sur les listes de dex

## Contexte

Trois pages consomment aujourd'hui des listes de dex sans aucune information de
progression pour le trainer connecté :

- `GET /album/dex` (back) → alimente la page d'accueil (`HomeController`) et
  `/album/dex` (`AlbumDexListController`) côté `pokenini-web`. Affiche une
  grille de cartes de dex.
- `GET /election/dex` (back) → alimente `/election/dex` côté web (page
  `Election/dex.html.twig`). Même type de grille, pour les dex ouverts au
  vote.

Sur la page album individuelle (`GET /album/{trainerId}/{dexSlug}`) et sur la
page élection individuelle (`GET /election/{dexSlug}/{electionSlug}`),
l'API expose déjà toutes les données de progression nécessaires. Le but de ce
travail est de réutiliser ces endpoints existants, en bouclant côté Back sur
les dex renvoyés par les listes, pour attacher ces mêmes données à chaque
entrée de liste. **Aucune modification de `pokenini-api` n'est nécessaire ni
prévue dans ce travail.**

Seul `pokenini-back` est modifié. L'affichage côté `pokenini-web` (barres de
progression, pourcentages) fait l'objet d'un travail séparé, non traité ici.

## Objectifs

1. `GET /album/dex` renvoie, pour chaque dex, le même `report` que celui
    renvoyé par `GET /album/{trainerId}/{dexSlug}` (total, total_caught,
    total_uncaught, détail par `catch_state`).
2. `GET /election/dex` renvoie, pour chaque dex, les mêmes métriques que
    celles utilisées par `ElectionIndexController` pour la page élection
    individuelle (round_count, total_round_count, completion brute,
    dex_total_count).
3. Les métriques d'élection (jusqu'ici jamais mises en cache) sont mises en
    cache côté Back, avec invalidation au moment du vote — bénéfice à la fois
    pour la nouvelle liste et pour la page élection individuelle existante.
4. Un échec ponctuel sur un dex (erreur API) ne doit pas faire échouer toute
    la liste.

## Non-objectifs

- Pas de changement d'API (`pokenini-api`).
- Pas de changement d'affichage côté `pokenini-web` dans ce lot.
- Pas de parallélisation des appels HTTP vers l'API dans cette itération — les
  appels restent séquentiels, un par dex de la liste. Le cache absorbe le
  coût des rechargements, mais le premier chargement à froid d'une liste
  reste aussi lent que la somme des appels séquentiels.
- Pas d'endpoint API "report only" ou "bulk" — voir section _Limites connues_.

## Architecture

### 1. Cache des métriques d'élection (prérequis transverse)

`GetElectionMetricsApiService::getMetrics()` est aujourd'hui un appel HTTP nu,
sans cache. On l'aligne sur le pattern déjà utilisé par
`GetPokedexApiService` / `GetDexListApiService` :

- Nouvelle clé `KeyMaker::getElectionMetricsKey(string $trainerId, string $dexSlug, string $electionSlug): string`.
- L'appel est enveloppé dans `$this->cache->get($key, ...)`, tag
  `KeyMaker::getTrainerIdKey($trainerId)`. Pas de TTL explicite (cohérent
  avec le reste du cache de l'application : invalidation par tag/clé
  uniquement, pas d'expiration automatique).

Invalidation :

- Nouveau `App\Service\CacheInvalidator\ElectionCacheInvalidatorService`,
  même forme que `AlbumCacheInvalidatorService` :
  ```php
  public function invalidate(string $dexSlug, string $electionSlug, string $trainerId): void
  {
      $this->cache->delete(KeyMaker::getElectionMetricsKey($trainerId, $dexSlug, $electionSlug));
      $this->cache->invalidateTags([KeyMaker::getTrainerIdKey($trainerId)]);
  }
  ```
- `ModifyElectionVoteService::vote()` appelle cet invalidator juste après un
  vote réussi (même position que `ModifyTrainerAlbumService` /
  `ModifyTrainerDexService` appellent leurs invalidators respectifs).

Effet de bord positif : la page élection individuelle
(`ElectionIndexController` → `GetElectionMetricsService`) profite aussi de ce
cache alors qu'elle refait actuellement l'appel API à chaque affichage.

### 2. Liste des dex albums — `GET /album/dex`

Nouveau service `App\Service\GetAlbumDexListWithReportService` :

```php
public function get(string $trainerId, bool $withUnreleasedAndPremium): array
```

- Reproduit la logique actuelle de sélection (`getWithPremium` /
  `getWithUnreleasedAndPremium` de `GetDexListApiService`) pour obtenir la
  liste brute des dex.
- Pour chaque entrée, appelle `GetPokedexApiService->get($slug, $trainerId)`
  et attache la clé `report` extraite de la réponse à l'entrée de la liste.
- En cas de `HttpExceptionInterface` / `TransportExceptionInterface` sur un
  dex donné : log de l'erreur, `report: null` sur cette entrée, la boucle
  continue.

`AlbumDexListController::index()` appelle ce nouveau service à la place de
`GetDexListApiService` directement. La résolution du `trainer_id` (query
param ou utilisateur connecté) et le contrôle admin/non-admin restent
inchangés.

Pas de changement de cache nécessaire ici : `GetPokedexApiService` est déjà
caché (tag `album` + `trainer_id`), et boucler dessus depuis la liste
préchauffe le même cache que celui utilisé par la page album individuelle.

Forme de la réponse (extrait, un élément de la liste) :

```json
{
  "dex": { "slug": "redgreenblueyellow" },
  "name": "Red, Green, Blue, Yellow",
  "french_name": "Rouge, Vert, Bleu, Jaune",
  "slug": "redgreenblueyellow",
  "flags": { "...": "..." },
  "display_template": "box",
  "report": {
    "total": 37,
    "total_caught": 20,
    "total_uncaught": 17,
    "detail": [
      { "catch_state": { "slug": "yes", "name": "Yes", "french_name": "Oui", "color": "#66bb6a" }, "count": 20 }
    ]
  }
}
```

### 3. Liste des dex élections — `GET /election/dex`

Nouveau service `App\Service\GetElectionDexListWithMetricsService` :

```php
public function get(): array
```

- Reproduit le switch admin / collector / défaut actuellement dans
  `ElectionDexListController` pour obtenir la liste brute des dex
  (`GetElectionDexListApiService`).
- Pour chaque entrée, appelle `GetElectionMetricsService->getMetrics($dexSlug, '')`
  (élection par défaut, comme le fait la page individuelle quand
  `electionSlug` n'est pas précisé) et attache `election_metrics` à
  l'entrée :
  ```php
  'election_metrics' => $metrics->raw + [
      'round_count' => $metrics->roundCount,
      'total_round_count' => $metrics->totalRoundCount,
  ]
  ```
  — même forme que celle déjà renvoyée par `ElectionIndexController` dans la
  clé `metrics` de la page individuelle.
- Même gestion d'erreur gracieuse par dex que pour les albums
  (`election_metrics: null` en cas d'échec ponctuel).

`ElectionDexListController::index()` appelle ce nouveau service à la place de
`GetElectionDexListApiService` directement.

Grâce au cache décrit en (1), les chargements répétés de la liste restent
rapides ; seul le tout premier chargement (cache froid) déclenche N appels
séquentiels vers l'API.

## Gestion des erreurs

Un échec sur un dex individuel (timeout, 5xx, etc.) ne doit jamais faire
échouer la liste complète : on logge et on renvoie `report`/
`election_metrics` à `null` pour cette entrée uniquement. C'est cohérent
avec le fait que ces boucles multiplient le nombre d'appels HTTP par rapport
à l'existant, donc la probabilité d'un échec partiel augmente
mécaniquement.

## Tests

- Tests unitaires pour `GetAlbumDexListWithReportService` et
  `GetElectionDexListWithMetricsService` (cas nominal, cas d'erreur partielle
  sur un dex, sélection admin/collector/défaut).
- Tests unitaires pour le cache de `GetElectionMetricsApiService` (clé
  générée, tag posé) et pour `ElectionCacheInvalidatorService`.
- Mise à jour du test unitaire de `ModifyElectionVoteService` pour vérifier
  l'appel à l'invalidator après un vote réussi.
- Mise à jour des tests d'intégration de `AlbumDexListController` et
  `ElectionDexListController` (`#[Group('api-mocked-testing')]`), avec de
  nouvelles fixtures Moco pour les appels par-dex
  (`/album/{trainer}/{dexSlug}` et `/election/metrics`). Réutilisation des
  fixtures `report` déjà présentes sous
  `tests/resources/moco/Api/responses/album/**` quand les slugs
  correspondent, sinon ajout des fixtures manquantes.
- Coverage et Mutation Score Index doivent rester à 100 % (contrainte du
  projet).

## Limites connues (pistes d'évolution API, hors scope)

- `/album/{trainerId}/{dexSlug}` calcule systématiquement la liste complète
  des pokémons même quand seul le `report` est utile. Un endpoint
  "report only" (ou un endpoint bulk multi-dex) réduirait la charge sur
  l'API et la taille des réponses côté Back. Accepté pour cette itération
  conformément à la consigne de ne pas toucher à l'API.
- Les appels par-dex restent séquentiels (pas de parallélisation HTTP côté
  Back). À revisiter si le nombre de dex dans les listes augmente
  significativement.
