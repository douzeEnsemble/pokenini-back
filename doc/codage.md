# Conventions de codage — Pokenini Back

## Langage et runtime

- **PHP 8.5.6**, `declare(strict_types=1)` obligatoire sur chaque fichier (sans exception)
- **Symfony 8.0.***, BFF sans base de données, orienté cache et proxy HTTP
- Autoload PSR-4 : `App\` → `src/`, `App\Tests\` → `tests/src/`, `App\Tests\Utils\` → `tests/Utils/`

## Style de code

- Outil : **PHP CS Fixer** avec rulesets `@PER-CS`, `@Symfony`, `@PSR12`, `@PhpCsFixer`, `@PHP83Migration`
  Référence : `.php-cs-fixer.dist.php`
- Analyse statique : **PHPStan niveau 9** (extensions symfony + phpunit), **Psalm errorLevel 1** (strict, `findUnusedCode=true`, plugins symfony + phpunit)
  Références : `phpstan.neon.dist`, `psalm.xml`, `psalm-src-only.xml`
- Qualité structurelle : **PHPMD** (`phpmd.ruleset.xml`), **Deptrac** (`deptrac.yaml`)

## Nommage

### Classes
- PascalCase strict
- Classes `final` par défaut — sauf exceptions documentées :
  - `src/Security/` et `src/Service/` : non-`final` pour permettre le mock en tests (supprimé dans `psalm.xml:44`)
  - `AbstractAdminActionController` : non-`final` par design (classe abstraite)
- Suffixes sémantiques imposés par couche :
  - `*Controller` — `src/Controller/Album/AlbumPokedexController.php`
  - `*ApiService` — `src/Service/Api/GetPokedexApiService.php`
  - `*CacheInvalidatorService` — `src/Service/CacheInvalidator/AlbumCacheInvalidatorService.php`
  - `*Exception` — `src/Exception/DexNotFoundException.php`
  - `*Helper` — `src/Helper/TotalRoundCountHelper.php`
  - `*Trait` — `src/Security/AuthenticatorTrait.php`
  - DTOs : nom métier seul (pas de suffixe) — `src/DTO/DexFilters.php`

### Méthodes
- camelCase : `getPokedexData`, `getTrainerId`, `createFromArray`
- Préfixes conventionnels : `get`, `is`, `has` pour les accesseurs ; `create`/`make` pour les factories statiques
- `#[\Override]` obligatoire sur toute méthode implémentant une interface ou surchargeant une parent — `src/Security/AbstractAuthenticator.php:28`

### Variables
- camelCase : `$trainerId`, `$dexSlug`, `$accessToken`
- Tous les paramètres injectés au constructeur sont `private readonly`

### Constantes
- `UPPER_SNAKE_CASE` avec typage explicite :
  ```php
  private const string CACHE_KEY_SEPARATOR = '_';   // src/Cache/KeyMaker.php:9
  private const array FILTERS = [...];               // src/AlbumFilters/FromRequest.php:11
  ```

### Fichiers
- Un fichier = une classe, nommé exactement comme la classe
- PSR-4 strict : `App\Service\Api\GetPokedexApiService` → `src/Service/Api/GetPokedexApiService.php`

---

## Structure des classes

### Controllers (`src/Controller/`)
- Toujours `final`, étendent `AbstractController`
- Routes via attributs `#[Route]` sur la classe et les méthodes
- Droits via `security.yaml` (`access_control`) ou `#[IsGranted]`
- **Aucune logique métier** — délèguent immédiatement aux services
- Référence : `src/Controller/Album/AlbumPokedexController.php`

### Services (`src/Service/`)
- Non-`final` pour permettre le mock en tests
- Injection via constructeur avec `private readonly`
- Deux sous-groupes :
  - `Service\Api\` : appels HTTP vers pokenini-api via `AbstractApiService` (cache, logging, auth Basic). Nommage : `Get*ApiService` / `Modify*ApiService`.
  - `Service\CacheInvalidator\` : invalidation cache par tags Symfony.
- Référence : `src/Service/GetTrainerPokedexService.php`, `src/Service/Api/GetPokedexApiService.php`

### DTOs (`src/DTO/`)
- `final`, construction via factory statique `createFromArray` + `OptionsResolver`
- Propriétés publiques en lecture directe (pas d'accesseurs)
- Référence : `src/DTO/DexFilters.php`

### Exceptions (`src/Exception/`)
- `final`, étendent `\RuntimeException` ou une interface Symfony
- Référence : `src/Exception/DexNotFoundException.php`

### Security (`src/Security/`)
- Non-`final` (contrainte framework)
- `AbstractAuthenticator` + `GoogleAuthenticator`/`DiscordAuthenticator` — flux OAuth2 classique (redirect)
- `AccessTokenHandler` — valide les Bearer tokens d'API (header `Authorization` + `X-Provider`)
- `FakeAuthenticator` — dev uniquement, pas de secret
- `MockAuthenticator` / `MockProvider` — tests uniquement

---

## Patterns récurrents

- **OptionsResolver** pour valider les tableaux d'entrée des DTOs — `src/DTO/DexFilters.php:26`
- **Tag-aware cache** avec clés déterministes via `KeyMaker` — `src/Cache/KeyMaker.php`
- **JsonDecoder::decode()** à la place de `json_decode()` direct — `src/Utils/JsonDecoder.php`
- **`#[\Override]`** obligatoire sur toutes les surcharges/implémentations — `src/Security/AbstractAuthenticator.php:28`
- **`@SuppressWarnings("PHPMD.…")`** et **`/** @psalm-suppress … */`** documentés explicitement

---

## Typage statique

- **PHPStan niveau 9** avec extensions `phpstan-symfony` et `phpstan-phpunit`, conteneur XML généré depuis l'env `test`
- **Psalm errorLevel 1 (strict)** avec `findUnusedCode="true"`, plugins symfony et phpunit
  - `PossiblyUnusedMethod` supprimé pour `src/Controller/` (méthodes appelées par le routeur)
  - `PropertyNotSetInConstructor` supprimé pour `src/Controller/` et `tests/src/Unit/`
  - `ClassMustBeFinal` supprimé pour `src/Security/` et `src/Service/`
- Suppressions explicites locales : `/** @psalm-suppress RiskyTruthyFalsyComparison */` — `src/Service/GetTrainerPokedexService.php:43`
- `// @codeCoverageIgnoreStart/End` réservé aux méthodes non testables imposées par le framework

---

## Tests

### Nommage
- Classes : `final` + `/** @internal */` + `#[CoversClass(ClassName::class)]`
- Méthodes : `test{Action}{Contexte}` — ex : `testGetForAPublicDex`, `testGet`
- DataProvider : `{action}Provider`

### Attributs PHPUnit obligatoires
- `#[CoversClass(...)]` sur chaque classe de test
- `#[Group('api-mocked-testing')]` sur les tests d'intégration

### Fixtures et snapshots
- Snapshots contrôleurs : `tests/resources/functional/controller/{Domain}/{scenario}.json`
- Mocks HTTP API : `tests/resources/moco/Api/`
- Mocks HTTP OAuth : `tests/resources/moco/OAuth/`
- Fixtures unitaires : `tests/resources/unit/`

### Traits de test partagés
- `ClientRequestTrait` — `authenticatedRequest()` avec Bearer token + header `X-Provider`
- `JsonResponseTrait` — assertion sur le contenu JSON par rapport aux snapshots

---

## Règles framework spécifiques

- Symfony Cache : utiliser `TagAwareCacheInterface` et invalider par tags (`KeyMaker::getAlbumKey()`, etc.)
- Ne jamais appeler un `ApiService` directement depuis un Controller — toujours via un `Service` métier
- La couche `AlbumFilters` ne dépend que de `SymfonyHttpFoundation` (enforced par Deptrac)
- `UserTokenService::getLoggedUserToken()` retourne `sha1($user->getUserIdentifier())` comme identifiant de cache — `src/Security/UserTokenService.php:26`

---

## À faire / À éviter

**À faire**
- `declare(strict_types=1)` en première ligne
- `final` sur toute classe non conçue pour l'extension
- `private readonly` pour toutes les injections constructeur
- `#[\Override]` sur toutes les surcharges / implémentations
- PHPDoc sur tous les tableaux complexes (`@param string[]`, `@return string[][]`)
- `OptionsResolver` pour valider les tableaux d'entrée des DTOs
- `JsonDecoder::decode()` plutôt que `json_decode()` direct
- 100% couverture et 100% MSI Infection

**À éviter**
- `json_decode()` direct — utiliser `JsonDecoder::decode()` (`src/Utils/JsonDecoder.php`)
- Accès à `$_ENV`/`$_SERVER` directement
- Logique métier dans les Controllers
- Appel direct d'un `ApiService` depuis un Controller (violation Deptrac)
