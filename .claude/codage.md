# Conventions de codage — pokenini-back

## Langage et framework

- **PHP** ≥ 8.5.4, **Symfony** 8.0.*
- `declare(strict_types=1)` obligatoire en tête de chaque fichier PHP
- **PHPUnit** 13.1.8 — tests unitaires et intégration
- **PHPStan** 2.1.54 — niveau 9 (max), avec extensions symfony + phpunit
- **Psalm** 6.16.1 — niveau d'erreur 1 (max), `findUnusedCode=true`
- **PHP-CS-Fixer** 3.95.1 — formatage automatique
- **PHPMD** 2.15 — analyse de code mort et design
- **Deptrac** 4.6.0 — contrôle des dépendances entre couches
- **Infection** 0.32.7 — tests de mutation, cible 100% MSI

Commandes principales : `make quality`, `make cq`, `make phpcsfixer-fix`

---

## Style de code

PHP-CS-Fixer avec rulesets combinés (`.php-cs-fixer.dist.php`) :
- `@PER-CS`, `@Symfony`, `@PSR12`, `@PhpCsFixer`, `@PHP83Migration`
- `declare_strict_types: true` activé
- Parallélisation activée via `ParallelConfigFactory::detect()`
- Cache : `var/cache/phpcsfixer/.php-cs-fixer.cache`

S'applique à `src/` et `tests/` (sauf `tests/bootstrap.php`).

---

## Nommage

| Élément | Convention | Exemple |
|---------|-----------|---------|
| Classes | PascalCase + suffixe sémantique | `GetPokedexApiService`, `AlbumCacheInvalidatorService` |
| Méthodes | camelCase | `getPokedexDataByTrainerId()`, `loadUserFromLists()` |
| Variables | camelCase | `$trainerId`, `$dexSlug`, `$cacheKeySuffixe` |
| Constantes | UPPER_SNAKE_CASE | `CACHE_KEY_SEPARATOR`, `FILTERS` |
| Fichiers | PascalCase, même nom que la classe | `GetPokedexApiService.php` |
| Paramètres URL | snake_case | `trainer_id`, `dex_slug`, `catch_states` |

### Suffixes obligatoires par couche

| Suffixe | Couche | Exemple |
|---------|--------|---------|
| `*Controller` | Contrôleurs Symfony | `AlbumPokedexController` |
| `*ApiService` | Appels HTTP vers pokenini-api | `GetPokedexApiService`, `ModifyAlbumApiService` |
| `*Service` | Services métier | `GetTrainerPokedexService`, `TrainerIdsService` |
| `*CacheInvalidatorService` | Invalidation de cache par tags | `AlbumCacheInvalidatorService` |
| `*Exception` | Exceptions métier | `DexNotFoundException`, `NoLoggedUserException` |
| `*Helper` | Helpers statiques purs | `TotalRoundCountHelper` |
| `*Trait` | Traits partagés | `AuthenticatorTrait`, `ClientRequestTrait` |
| `*ApiService` → préfixe `Get*` | Lecture | `GetDexListApiService`, `GetLabelsApiService` |
| `*ApiService` → préfixe `Modify*` | Écriture | `ModifyAlbumApiService`, `ModifyElectionVoteApiService` |

---

## Structure des fichiers

```
src/
├── AlbumFilters/         Extraction des filtres URL (statique)
├── Cache/                KeyMaker — génération déterministe des clés de cache
├── Controller/           Contrôleurs minces (1 endpoint = 1 contrôleur)
│   ├── Admin/            Contrôleurs admin (actions, invalidation, rapports)
│   ├── Album/            Lecture/modification pokedex personnel
│   ├── Election/         Vote et classement
│   ├── Labels/           Référentiels (types, formes, jeux…)
│   ├── Trainer/          Gestion de dex par trainer
│   └── User/             Info utilisateur connecté
├── DTO/                  Value objects typés, construits via createFromArray()
├── Exception/            Exceptions métier
├── Helper/               Calculs purs sans dépendances
├── Security/             Authenticators, User, UserProvider, UserTokenService
├── Service/
│   ├── Api/              Appels HTTP vers pokenini-api (Get*/Modify*)
│   └── CacheInvalidator/ Invalidation par tags
├── Utils/                Utilitaires transverses (JsonDecoder)
└── Validator/            Contrainte Symfony pour les catch states

tests/
├── src/
│   ├── Integration/      WebTestCase + Moco (full stack, #[Group('api-mocked-testing')])
│   └── Unit/             PHPUnit pur, pas de stack HTTP
└── resources/
    ├── functional/controller/  Snapshots JSON attendus par endpoint
    └── moco/Api/               Config Moco + réponses mockées
```

---

## Patterns récurrents

### Héritage abstrait + final

Toute nouvelle classe est `final` sauf si elle est prévue pour être étendue.
Seules les classes de base exposent un héritage : `AbstractApiService`, `AbstractAdminActionController`, `AbstractCacheInvalidatorService`.

```php
// src/Service/Api/GetPokedexApiService.php:12
class GetPokedexApiService extends AbstractApiService { ... }
```

### Named constructors (DTOs)

Tous les DTOs utilisent `private function __construct` + `public static function createFromArray(array $data): self`.

```php
// src/DTO/ActionLog.php:29
public static function createFromArray(array $data): self { ... }
```

### Cache avec tags

Les ApiServices utilisent le pattern `$cache->get($key, function(ItemInterface $item) { $item->tag([...]); ... })`.

```php
// src/Service/Api/GetPokedexApiService.php:25
$json = $this->cache->get($key, function (ItemInterface $item) use (...) {
    $item->tag([KeyMaker::getAlbumKey(), KeyMaker::getTrainerIdKey($trainerId)]);
    return $this->requestContent('GET', $url, ['query' => $filters]);
});
```

### Injection via bind global

`services.yaml` bind les variables d'env directement par nom de paramètre constructeur (`string $apiUrl`, `string $listAdmin`, etc.) — pas besoin de tag ou d'annotation.

---

## Annotations et style

| Annotation | Usage | Obligatoire |
|-----------|-------|-------------|
| `declare(strict_types=1)` | Tout fichier PHP | Oui |
| `final` | Toute classe non abstraite | Oui |
| `#[\Override]` | Surcharge de méthode parent | Oui |
| `/** @var Type */` | Assertion de type dans closures ou casts | Selon contexte |
| `@psalm-suppress RiskyTruthyFalsyComparison` | Suppressions statiques ponctuelles | Non |
| `@SuppressWarnings("PHPMD.UnusedFormalParameter")` | Paramètre intentionnellement ignoré | Non |
| `// @codeCoverageIgnoreStart/End` | Code non testable (ex: `eraseCredentials`) | Non |

---

## Tests

### Nommage

- Classes : `{Classe}Test` (ex. `AlbumPokedexTest`, `KeyMakerTest`)
- Méthodes : `test{ScenarioEnPascalCase}` (ex. `testGetForAPrivateDex`)
- DataProviders : `get{Nom}Provider()` (ex. `getFilteredProvider()`)

### Attributs obligatoires

```php
/** @internal */
#[Group('api-mocked-testing')]      // tests d'intégration uniquement
#[CoversClass(MyController::class)] // toujours présent
final class MyControllerTest extends WebTestCase { ... }
```

### Fixtures et données de test

- Réponses Moco : `tests/resources/moco/Api/responses/*.json`
- Snapshots attendus : `tests/resources/functional/controller/{Controller}/{cas}.json`
- Requêtes authentifiées : `$this->authenticatedRequest($client, 'trainer', 'GET', '/album/demo')`
- Assertion de réponse : `$this->assertResponseContent($client, 'Pokedex/demo.json')`
- Debug : décommenter `file_put_contents` dans `JsonResponseTrait` pour dumper la réponse réelle

---

## Règles framework spécifiques

- **Routing** : attributs PHP uniquement (`#[Route(...)]`), déclarés sur la classe et la méthode
- **Services** : autowire + autoconfigure activés ; pas de tags manuels pour les services standards
- **Pas de Doctrine** : aucune Entity, aucun Repository, aucun ORM
- **Pas de Form** : pas de `FormType`, l'input vient directement de `Request`
- **Cache** : `TagAwareCacheInterface` injecté, jamais `CacheItemPoolInterface` directement
- **Exceptions** : les contrôleurs capturent uniquement les exceptions métier (`DexNotFoundException`, etc.) et retournent un `JsonResponse` — ils ne catchent jamais `\Exception` générique
- **Deptrac** : respecter strictement les règles de dépendances (voir `deptrac.yaml`) — violation = échec CI

---

## À faire / À éviter

**À faire**
- `declare(strict_types=1)` + `final` sur chaque nouvelle classe
- Créer un `Service` métier intermédiaire avant un `ApiService` (jamais Controller → ApiService direct)
- Placer les outils qualité dans `tools/{outil}/composer.json`, jamais dans le `composer.json` racine
- Taguer les items de cache dans la closure `$cache->get()`
- Nommer les ApiServices `Get{Ressource}ApiService` ou `Modify{Ressource}ApiService`
- Atteindre 100% de coverage et 100% MSI en mutation testing avant tout merge

**À éviter**
- Appeler un `ApiService` directement depuis un `Controller`
- Ajouter des dépendances qualité (`phpstan`, `psalm`, etc.) dans le `composer.json` principal
- Utiliser `catch (\Exception $e)` générique en dehors de `AbstractAdminActionController` (pattern admin intentionnel)
- Écrire des méthodes de controller faisant plus qu'initialiser, déléguer et retourner
- Utiliser `json_decode` sans `JSON_THROW_ON_ERROR` — passer par `JsonDecoder::decode()`
