# Architecture — Pokenini Back

## Vue d'ensemble

`pokenini-back` est un **BFF (Backend for Frontend)** en Symfony 8.0 servant de façade à une API externe (`pokenini-api`). Il gère l'authentification OAuth2 (Google, Discord), met en cache les réponses de l'API (APCu / Redis avec invalidation par tags), applique les contrôles d'accès par rôle, et expose une API JSON consommée par le frontend Pokenini.

Périmètre fonctionnel :
- Consultation et mise à jour des pokédex personnels des dresseurs (albums)
- Système d'élection de Pokémon (vote, métriques, classement)
- Administration : invalidation cache, mise à jour des données, calculs, rapports, logs
- Gestion des labels, types, collections et formes de Pokémon
- Authentification OAuth2 multi-provider avec rôles basés sur des listes d'IDs en env

---

## Carte des répertoires

```
pokenini-back/
├── src/
│   ├── AlbumFilters/       # Parsing des filtres URL → tableau pour l'API
│   ├── Cache/              # KeyMaker : génération déterministe des clés de cache
│   ├── Controller/         # Points d'entrée HTTP (un contrôleur = un endpoint)
│   │   ├── Admin/          # Endpoints admin (/istration/*)
│   │   ├── Album/          # Endpoints album/pokédex
│   │   ├── Election/       # Endpoints élection
│   │   ├── Labels/         # Endpoint labels
│   │   ├── Trainer/        # Endpoints dresseur
│   │   └── User/           # Endpoint info utilisateur
│   ├── DTO/                # Value objects typés (lecture seule, factory statique)
│   ├── Exception/          # Exceptions métier (final, RuntimeException)
│   ├── Helper/             # Helpers statiques purs (calculs)
│   ├── Security/           # Authenticators, AccessTokenHandler, UserProvider
│   ├── Service/
│   │   ├── Api/            # Appels HTTP vers pokenini-api (AbstractApiService)
│   │   └── CacheInvalidator/ # Invalidation cache par tags
│   ├── Utils/              # JsonDecoder (wrapper json_decode)
│   └── Validator/          # Contrainte Symfony CatchStates
├── tests/
│   ├── src/
│   │   ├── Integration/    # Tests d'intégration (WebTestCase + Moco)
│   │   └── Unit/           # Tests unitaires (PHPUnit pur)
│   ├── resources/
│   │   ├── moco/Api/       # Réponses mock de l'API externe
│   │   ├── moco/OAuth/     # Réponses mock OAuth2
│   │   └── functional/     # Snapshots JSON des réponses controller
│   └── Utils/              # Helpers de test (WithConsecutive)
├── config/
│   ├── packages/           # Configuration Symfony (cache, security, monolog…)
│   └── routes/             # Routes additionnelles (security OAuth)
├── tools/                  # Outils qualité isolés (composer indépendant par outil)
│   ├── deptrac/
│   ├── infection/
│   ├── jsonlint/
│   ├── php-cs-fixer/
│   ├── phpmd/
│   ├── phpstan/
│   └── psalm/
├── .docker/
│   ├── php/                # Dockerfile multi-stage (base/dev/prod)
│   ├── nginx/              # Config Nginx (reverse proxy)
│   └── moco/               # Dockerfile Moco (mock HTTP)
└── resources/
    ├── certificates/       # CA cert pour les appels HTTPS vers l'API externe
    └── metadata/           # Version de l'application
```

---

## Rôle de chaque couche

### Controller (`src/Controller/`)
Point d'entrée HTTP. Reçoit la requête, délègue à un ou deux services, retourne `JsonResponse`. **Aucune logique métier.** Toujours `final`, hérite de `AbstractController`.
Référence : `src/Controller/Album/AlbumPokedexController.php`

### AlbumFilters (`src/AlbumFilters/`)
Parse les paramètres de requête HTTP en tableau de filtres typés, transmis tel quel à l'API externe. Dépendance unique : `SymfonyHttpFoundation`.
Référence : `src/AlbumFilters/FromRequest.php`

### Service (`src/Service/`)
Orchestration de la logique métier. Deux sous-couches :
- **`Service\Api\`** : appels HTTP vers pokenini-api via `AbstractApiService` (cache, logging, auth Basic). Nommage : `Get*ApiService` / `Modify*ApiService`.
- **`Service\CacheInvalidator\`** : invalidation cache par tags Symfony.
Référence : `src/Service/GetTrainerPokedexService.php`, `src/Service/Api/GetPokedexApiService.php`

### DTO (`src/DTO/`)
Value objects typés immuables, construits via factory statique `createFromArray` + `OptionsResolver`. Propriétés publiques directes. `final`.
Référence : `src/DTO/DexFilters.php`

### Security (`src/Security/`)
- `AbstractAuthenticator` + `GoogleAuthenticator`/`DiscordAuthenticator` — flux OAuth2 classique (redirect)
- `AccessTokenHandler` — valide les Bearer tokens d'API (header `Authorization` + `X-Provider`)
- `UserProvider` / `User` — représentation de l'utilisateur avec rôles (admin/collector/trainer)
- `UserTokenService` — extrait le SHA1 de l'identifiant utilisateur comme clé de cache trainer
- `FakeAuthenticator` — dev uniquement, pas de secret
- `MockAuthenticator` / `MockProvider` — tests uniquement
Référence : `src/Security/AccessTokenHandler.php`

### Cache (`src/Cache/`)
`KeyMaker` : génère des clés de cache déterministes par type de ressource et paramètres. Toutes statiques.
Référence : `src/Cache/KeyMaker.php`

### Validator (`src/Validator/`)
Contrainte Symfony `CatchStates` + `CatchStatesValidator` — valide les états de capture transmis lors d'une mise à jour d'album.

### Utils (`src/Utils/`)
`JsonDecoder::decode()` : wrapper `json_decode` avec `JSON_THROW_ON_ERROR`, profondeur 5.

### Exception (`src/Exception/`)
Exceptions métier `final` : `DexNotFoundException`, `EmptyContentException`, `InvalidJsonException`, `ModifyFailedException`, `NoLoggedUserException`, `ToJsonResponseException`.

---

## Flux typiques

### 1. Consultation d'un pokédex (cas nominal avec cache)

```
Client HTTP
  → GET /album/{dexSlug}?catch_states[]=caught
  → Security (AccessTokenHandler::getUserBadgeFrom)
      → ClientRegistry::getClient($provider)
      → provider->fetchUserFromToken → User{roles}
  → AlbumPokedexController::get()
      → TrainerIdsService::init()        # résout trainerId depuis query ou session
      → FromRequest::get($request)       # parse les filtres URL
      → GetTrainerPokedexService::getPokedexDataByTrainerId()
          → GetPokedexApiService::get()
              → KeyMaker::getPokedexKey()
              → TagAwareCacheInterface::get()  # cache HIT → retour immédiat
              → [cache MISS] → AbstractApiService::requestContent('GET', /album/{id}/{slug})
              → JsonDecoder::decode()
  → JsonResponse{pokedex, filters} HTTP 200
```

### 2. Mise à jour d'un album (upsert)

```
Client HTTP
  → PATCH /album/{dexSlug}
  → Security (ROLE_USER requis)
  → AlbumUpsertController::upsert()
      → CatchStatesValidator (contrainte Symfony)
      → ModifyTrainerAlbumService::modify()
          → ModifyAlbumApiService::modify()  # POST vers pokenini-api
          → AlbumCacheInvalidatorService::invalidate()  # invalide tags cache
  → JsonResponse HTTP 202
```

### 3. Action admin (invalidation cache)

```
Client HTTP (ROLE_ADMIN)
  → POST /istration/action/update/{name}
  → Security (access_control: ^/istration → ROLE_ADMIN)
  → AdminActionUpdateController::process()
      → AbstractAdminActionController::execute()
          → AdminActionApiService::update()   # appel API externe
          → CacheInvalidatorService::invalidate($name)  # map type → invalidators
  → JsonResponse{action, name, state} HTTP 202 | 500
```

---

## Points d'entrée

| Type | Fichier | Détail |
|------|---------|--------|
| HTTP | `public/index.php` | Entrypoint Symfony standard (FPM) |
| CLI  | `bin/console` | Console Symfony (cache:clear, etc.) |

---

## Dépendances entre modules (enforced par Deptrac)

| Couche | Peut dépendre de |
|--------|-----------------|
| `AppController` | AlbumFilters, DTO, Exception, Security, Service, Validator, Logger, SymfonyFramework, HttpFoundation, Routing, Security, Serializer, Validator |
| `AppService` | Cache, DTO, Exception, Security, Utils, Logger, SymfonyContractsCache, HttpClient, HttpFoundation, SecurityBundle, Validator |
| `AppDTO` | Helper, SymfonyHttpFoundation, OptionsResolver |
| `AppSecurity` | Exception, KnpUOAuth2, LeagueOAuth2, PsrHttp, HttpFoundation, Routing, SymfonySecurity, SecurityBundle |
| `AppAlbumFilters` | SymfonyHttpFoundation uniquement |
| `AppValidator` | Service, SymfonyContractsCache, HttpClient, Validator |
| `AppCache` | — (aucune dépendance app) |
| `AppHelper` | — (aucune dépendance app) |
| `AppUtils` | — (aucune dépendance app) |
| `AppException` | — (aucune dépendance app) |

Référence : `deptrac.yaml`

---

## Infrastructure

### Docker

| Service | Image | Rôle |
|---------|-------|------|
| `php` | Custom (`php:8.5.6-fpm-alpine3.23`) | PHP-FPM, multi-stage (base/dev/prod) |
| `web` | `nginx:1.29.8-alpine3.23` | Reverse proxy, port `127.0.0.1:8081:8080` |
| `redis` | `redis:8.6.2-alpine3.23` | Cache tag-aware (Symfony Cache) |
| `moco.api` | Custom (Moco 1.5.0) | Mock du serveur pokenini-api (tests) |
| `moco.oauth2` | Custom (Moco 1.5.0) | Mock des providers OAuth2 (tests) |

### Environnements

| Env | Description | Auth |
|-----|-------------|------|
| `dev` | Dev local, FakeAuthenticator via `/fr/connect/f/c?t=admin\|collector\|trainer` | Fake (pas de secret) |
| `test` | PHPUnit + Moco, MockAuthenticator + MockProvider | Mock (token `Bearer this-is-{role}-token`) |
| `prod` | Déployé via image Docker `ghcr.io/douzeensemble/pokenini-back:latest` | OAuth2 Google + Discord |

### Environnement de test spécifique
Les tests d'intégration (`#[Group('api-mocked-testing')]`) tournent avec le kernel Symfony complet + Moco comme mock HTTP. Les réponses JSON sont comparées à des snapshots dans `tests/resources/functional/`.
