# Architecture — pokenini-back

## Vue d'ensemble

Pokenini-back est un **BFF (Backend for Frontend)** Symfony 8.0 pour un tracker de Pokédex (living dex, alternate forms, gender dex). Il n'a **pas de base de données** : toute la persistance est dans l'API externe `pokenini-api`. Son rôle est de gérer l'authentification OAuth2 (Google, Discord), de mettre en cache les réponses de l'API distante (tag-aware), d'appliquer des règles de contrôle d'accès, et d'exposer un JSON REST API consommé par le frontend.

Fonctionnalités principales : consultation du Pokédex par trainer, gestion d'album (catch states), liste de dex avec filtres, élection de Pokémon, rapports admin, invalidation de cache.

---

## Carte des répertoires

```
pokenini-back/
├── src/
│   ├── AlbumFilters/          Extraction et validation des filtres URL de l'album
│   ├── Cache/                 KeyMaker — clés de cache déterministes
│   ├── Controller/            Contrôleurs slim (1 endpoint = 1 fichier)
│   │   ├── Admin/             Endpoints admin : actions, rapports, invalidation
│   │   ├── Album/             Pokedex personnel, upsert catch states
│   │   ├── Election/          Index, liste dex, vote
│   │   ├── Labels/            Référentiels publics (types, formes, jeux)
│   │   ├── Trainer/           Liste des dex, upsert dex trainer
│   │   └── User/              Info utilisateur connecté
│   ├── DTO/                   Value objects typés construits depuis tableaux
│   ├── Exception/             Exceptions métier applicatives
│   ├── Helper/                Calculs purs (TotalRoundCountHelper)
│   ├── Security/              Authenticators OAuth2 + AccessToken + Fake/Mock
│   ├── Service/
│   │   ├── Api/               Appels HTTP vers pokenini-api (avec cache)
│   │   └── CacheInvalidator/  Invalidation de cache par tags
│   ├── Utils/                 JsonDecoder (json_decode centralisé)
│   └── Validator/             Contrainte Symfony pour les catch states
├── tests/
│   ├── src/
│   │   ├── Integration/       Tests full-stack avec Moco (WebTestCase)
│   │   └── Unit/              Tests unitaires purs (PHPUnit)
│   └── resources/
│       ├── functional/        Snapshots JSON attendus par endpoint
│       └── moco/              Config Moco + réponses HTTP mockées
├── config/                    Configuration Symfony (packages, routes, services)
├── tools/                     Outils qualité isolés (chacun avec son composer.json)
│   ├── deptrac/
│   ├── infection/
│   ├── jsonlint/
│   ├── php-cs-fixer/
│   ├── phpmd/
│   ├── phpstan/
│   └── psalm/
├── .docker/                   Dockerfiles PHP, Nginx, Moco
├── .github/workflows/         CI GitHub Actions (tests, qualité, sécurité)
└── resources/
    ├── certificates/          CA certificate pour HTTPS vers pokenini-api
    └── metadata/              Version de l'application
```

---

## Rôle de chaque couche

| Couche | Rôle | Responsabilités | Fichier de référence |
|--------|------|----------------|---------------------|
| `Controller` | Point d'entrée HTTP | Reçoit la Request, délègue à un Service, retourne JsonResponse | `src/Controller/Album/AlbumPokedexController.php` |
| `Service` (métier) | Orchestration | Combine ApiServices, applique la logique métier, gère les IDs trainer | `src/Service/GetTrainerPokedexService.php` |
| `Service/Api` | Accès données | Appels HTTP à pokenini-api, mise en cache avec tags, décodage JSON | `src/Service/Api/GetPokedexApiService.php` |
| `Service/CacheInvalidator` | Invalidation | Invalide les tags de cache selon le type de ressource modifiée | `src/Service/CacheInvalidator/AlbumCacheInvalidatorService.php` |
| `DTO` | Transfert de données | Value objects immuables, nommés constructors `createFromArray()` | `src/DTO/ActionLog.php` |
| `Security` | Authentification | OAuth2 (Google/Discord), AccessToken, Fake (dev), Mock (test) | `src/Security/AbstractAuthenticator.php` |
| `AlbumFilters` | Parsing URL | Extrait les paramètres de filtre du `Request` vers un tableau typé | `src/AlbumFilters/FromRequest.php` |
| `Cache/KeyMaker` | Clés de cache | Génère des clés déterministes à partir des paramètres de requête | `src/Cache/KeyMaker.php` |
| `Utils` | Utilitaires | `JsonDecoder::decode()` — json_decode centralisé avec JSON_THROW_ON_ERROR | `src/Utils/JsonDecoder.php` |
| `Validator` | Validation | Contrainte Symfony pour valider les valeurs de catch states | `src/Validator/CatchStatesValidator.php` |

### Règles de dépendances (enforced by Deptrac — `deptrac.yaml`)

| Couche | Peut dépendre de |
|--------|----------------|
| `Controller` | AlbumFilters, DTO, Exception, Security, Service, Validator |
| `Service` | Cache, DTO, Exception, Security, Utils, SymfonyContracts |
| `DTO` | Helper, SymfonyOptionsResolver |
| `Security` | Exception, KnpU OAuth2, League OAuth2 |
| `Validator` | Service, SymfonyValidator |
| `AlbumFilters` | SymfonyHttpFoundation uniquement |
| `Cache`, `Utils`, `Helper`, `Exception` | Aucune dépendance interne |

**Règle fondamentale** : les Controllers ne peuvent pas appeler les `ApiService` directement — ils passent obligatoirement par un `Service` métier intermédiaire.

---

## Flux typique : consultation du Pokedex

```
GET /album/{dexSlug}?trainer_id=xxx&catch_states=no
        |
        ▼
Symfony Security (AccessTokenHandler → MockProvider en test, UserProvider en prod)
        |
        ▼
AlbumPokedexController::get()
  · TrainerIdsService::init()        ← résout trainer_id (request ou session)
  · FromRequest::get($request)       ← extrait les filtres URL
  · GetTrainerPokedexService::getPokedexDataByTrainerId()
          |
          ▼
      GetPokedexApiService::get($dexSlug, $trainerId, $filters)
        · KeyMaker::getPokedexKey()  ← clé de cache déterministe
        · TagAwareCacheInterface::get($key, fn → requestContent('GET', '/album/...'))
        · JsonDecoder::decode()
          |
          ▼ (cache miss uniquement)
      HTTP GET → pokenini-api /album/{trainerId}/{dexSlug}?...
          |
          ▼
      Réponse JSON stockée en cache avec tags [album, trainer#xxx]
  · accessDexIsGranted()             ← vérifie is_private / is_released
        |
        ▼
JsonResponse(['pokedex' => ..., 'filters' => ...], 200)
```

## Flux typique : modification catch state (upsert album)

```
PATCH /album/{dexSlug}/{pokemonSlug}
        |
        ▼
Symfony Security → ROLE_USER requis (voir security.yaml)
        |
        ▼
AlbumUpsertController::upsert()
  · Validation Symfony (CatchStates constraint)
  · ModifyTrainerAlbumService::modify()
          |
          ▼
      ModifyAlbumApiService::modify()     ← PATCH/PUT vers pokenini-api
      AlbumCacheInvalidatorService::invalidate()  ← invalide tags album
```

---

## Points d'entrée

| Type | Fichier/URL | Détail |
|------|-------------|--------|
| HTTP GET | `/album/{dexSlug}` | Pokedex d'un trainer (public si trainer_id fourni) |
| HTTP PATCH/PUT | `/album/{dexSlug}/{pokemonSlug}` | Upsert catch state (auth requise) |
| HTTP GET | `/album/list` | Liste des dex d'un trainer |
| HTTP GET | `/labels` | Référentiels (types, formes, jeux, collections) |
| HTTP GET | `/trainer/list` | Liste des dex (vue trainer) |
| HTTP GET | `/election/*` | Consultation + vote élection |
| HTTP GET | `/istration/*` | Actions admin (ROLE_ADMIN requis) |
| HTTP GET | `/user` | Info utilisateur connecté (PUBLIC_ACCESS) |
| HTTP GET | `/fr/connect/f/c?t=trainer\|admin\|collector` | Authentification fake (dev/test uniquement) |
| CLI | `bin/console` | Console Symfony standard |

---

## Environnements et infrastructure

| Env | Auth | API cible | Cache | Notes |
|-----|------|-----------|-------|-------|
| `dev` | FakeAuthenticator (`/fr/connect/f/c?t=...`) | Moco (http://moco.api) | filesystem | Xdebug disponible, profiler actif |
| `test` | MockAuthenticator + Bearer token | Moco (http://moco.api) | filesystem | Moco intercepte tous les appels HTTP |
| `ci` | MockAuthenticator | Moco | filesystem | Identique test, via GitHub Actions + Docker Compose |
| `prod` | Google OAuth2 + Discord OAuth2 + AccessToken | pokenini-api (réel) | filesystem* | *Redis disponible mais non configuré dans cache.yaml |

### Services Docker

| Service | Image | Version | Rôle |
|---------|-------|---------|------|
| `php` | `php:8.5.5-fpm-alpine3.23` | 8.5.5 | Application PHP-FPM |
| `web` | `nginx:1.29.8-alpine3.23` | 1.29.8 | Reverse proxy HTTP |
| `redis` | `redis:8.6.2-alpine3.23` | 8.6.2 | Cache (disponible, non utilisé par défaut) |
| `moco.api` | Image custom | 1.5.0 | Mock HTTP pour pokenini-api (test/dev) |
| `moco.oauth2` | Image custom | 1.5.0 | Mock HTTP pour OAuth2 (test/dev) |

### Authentification en production

Trois niveaux de rôles, pilotés par variables d'env :
- `LIST_ADMIN` → `ROLE_ADMIN` (accès `/istration/*`)
- `LIST_COLLECTOR` → `ROLE_COLLECTOR`
- `LIST_TRAINER` → `ROLE_TRAINER` (accès général authentifié)
- `REQUIRE_INVITATION` → si `true`, seuls les IDs dans `LIST_TRAINER` peuvent se connecter

Tous les identifiants OAuth sont hashés en `sha1()` pour générer les clés de cache.
