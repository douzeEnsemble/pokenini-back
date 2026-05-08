# Améliorations — pokenini-back

## Performance

### 1 — Cache Redis non configuré en production

**Problème** : `config/packages/cache.yaml` utilise `cache.adapter.filesystem` pour tous les environnements. Redis est présent dans le stack Docker (`redis:8.6.2-alpine`) mais n'est pas connecté comme adaptateur de cache. Avec des workers PHP-FPM multiples en production, le filesystem est partagé par NFS ou volume et peut créer des contentions ou des invalidations partielles.
**Fichier(s)** : `config/packages/cache.yaml:3`, `docker-compose.yaml:31-36`
**Correction** : ajouter une section `when@prod` dans `cache.yaml` :
```yaml
when@prod:
  framework:
    cache:
      app: cache.adapter.redis
      default_redis_provider: 'redis://:douze@redis'
```
et binder la variable `REDIS_URL` via `.env.prod`.

### 2 — Clé de cache SHA1 pour l'identifiant trainer

**Problème** : `UserTokenService::getLoggedUserToken()` applique `sha1()` sur l'identifiant OAuth. SHA1 produit une empreinte de 40 caractères hexadécimaux pour des IDs OAuth qui sont déjà courts (< 30 chars). Le hashing ajoute une collision théorique (négligeable) et un calcul inutile.
**Fichier(s)** : `src/Security/UserTokenService.php:26`
**Correction** : utiliser directement l'identifiant (ou un hash plus court/rapide comme `crc32b` hex). Si l'opacité est souhaitée pour des raisons de confidentialité dans les clés de cache, documenter ce choix.

---

## Qualité du code

### 3 — `AbstractAdminActionController::doAction` utilise un switch sans mécanisme d'extensibilité

**Problème** : ajouter une nouvelle action admin nécessite de modifier `doAction()` (switch) ET `CacheInvalidatorService::invalidate()` (map). Les deux méthodes codent en dur des chaînes de type, ce qui crée un couplage fort et un risque d'incohérence.
**Fichier(s)** : `src/Controller/Admin/AbstractAdminActionController.php:68-78`, `src/Service/CacheInvalidatorService.php:46-67`
**Correction** : extraire un objet `AdminActionDefinition` contenant le nom de l'action, la méthode API à appeler, et les tags à invalider. `CacheInvalidatorService` devient une lookup table d'objets immuables plutôt qu'une map de closures.

### 4 — `TrainerIdsService` mutable par `init()` — pattern fragile

**Problème** : le service accumule un état interne mutable (`$trainerId`, `$loggedTrainerId`) initialisé par `init()`. Si un Controller oublie d'appeler `init()`, toutes les méthodes retournent `null` silencieusement. PHPStan et Psalm ne détectent pas ce comportement.
**Fichier(s)** : `src/Service/TrainerIdsService.php`
**Correction** : rendre `init()` privé et appeler implicitement depuis le constructeur avec le `RequestStack`, ou transformer en factory retournant un DTO `TrainerIds(loggedId: ..., requestedId: ...)` immuable depuis le Controller.

### 5 — `KeyMaker` : boilerplate de getters pour des constantes privées

**Problème** : 12 méthodes statiques retournant chacune une constante privée (`getDexKey()` → `self::CACHE_KEY_DEX`). Toute la classe fait 132 lignes pour ce qui pourrait tenir en 15 lignes de constantes publiques.
**Fichier(s)** : `src/Cache/KeyMaker.php:9-79`
**Correction** : passer les constantes en `public const string` et les utiliser directement (`KeyMaker::CACHE_KEY_DEX`). Les méthodes complexes (`getDexKeyForTrainer`, `getPokedexKey`) restent des méthodes statiques.

---

## Tests

### 6 — Pas de test pour le service `CacheInvalidatorService`

**Problème** : `CacheInvalidatorService` contient 14 mappings de types → invalidateurs, mais aucun test unitaire ne vérifie que chaque type déclenche les bons invalidateurs de cache. Un changement dans la map pourrait passer silencieusement (le coverage 100% est atteint par les tests d'intégration qui ne vérifient pas l'invalidation de cache).
**Fichier(s)** : `src/Service/CacheInvalidatorService.php` (pas de fichier de test correspondant dans `tests/src/Unit/Service/`)
**Correction** : ajouter `tests/src/Unit/Service/CacheInvalidatorServiceTest.php` qui vérifie que chaque type invalide exactement les bons tags via des mocks de `TagAwareCacheInterface`.

### 7 — `JsonResponseTrait::assertResponseContent` sans message d'erreur contextuel

**Problème** : lors d'un échec d'assertion de snapshot JSON, PHPUnit affiche un diff JSON brut sans indiquer quel endpoint ou quel cas de test est en cause. Le debug est laborieux sur les tests avec DataProvider.
**Fichier(s)** : `tests/src/Integration/Trait/JsonResponseTrait.php:14`
**Correction** : passer un `$message` optionnel à `assertJsonStringEqualsJsonFile()`, ou construire un message d'erreur incluant `$filePath`.

---

## Sécurité

### 8 — `APP_SECRET` en valeur par défaut dans `.env.prod`

**Problème** : `.env.prod:13` contient `APP_SECRET=!ChangeMe!`. Cette valeur par défaut trop simple invalide la protection CSRF et la signature des sessions si elle n'est pas overridée à chaque déploiement.
**Fichier(s)** : `.env.prod:13`
**Correction** : retirer `APP_SECRET` de `.env.prod` et forcer son injection via une variable d'environnement injectée au déploiement (secret manager). Documenter son caractère obligatoire.

### 9 — Credentials OAuth réels commitèrent dans `.env.dev` et `.env.prod`

**Problème** : les `OAUTH_GOOGLE_CLIENT_SECRET` et `OAUTH_DISCORD_CLIENT_SECRET` sont versionnés. Si ces valeurs sont réelles (même pour un environnement de staging), leur présence dans git expose les secrets à tout accès au dépôt.
**Fichier(s)** : `.env.dev:44-46`, `.env.prod:43-46`
**Correction** : utiliser des variables d'env réelles et retirer les valeurs concrètes du `.env.prod`/`.env.dev`. Les `.env.*.local` (gitignorés) sont le bon endroit pour les valeurs locales.

---

## Maintenabilité

### 10 — Pas de documentation des types d'invalidation dans `CacheInvalidatorService`

**Problème** : les 14 types d'invalidation (`'pokemons'`, `'labels'`, `'dex'`, etc.) sont définis en strings hardcodées sans enum ni constante. Ajouter un nouveau type ou corriger un typo nécessite de chercher dans le code consommateur.
**Fichier(s)** : `src/Service/CacheInvalidatorService.php:46-67`
**Correction** : créer une enum `AdminActionType` avec les cas nommés, utilisée comme clé dans la map et comme paramètre de `invalidate()`. Deptrac sera mis à jour pour inclure le nouveau namespace.

### 11 — `resources/metadata/version` — usage non documenté

**Problème** : le fichier `resources/metadata/version` existe mais aucun code dans `src/` ne le lit. Son rôle (versionning du déploiement ? endpoint de santé ?) n'est pas clair.
**Fichier(s)** : `resources/metadata/version`
**Correction** : soit l'exposer via un endpoint `/version` ou en header de réponse, soit le supprimer s'il n'est plus nécessaire.

---

## DevX

### 12 — Pas de cible `make` pour lancer un test unitaire ou d'intégration seul en dev

**Problème** : le `Makefile` expose `make tests`, `make tests-unit`, `make tests-integration`, mais pour filtrer un test précis il faut taper la commande `docker compose exec php php vendor/bin/phpunit` complète documentée dans `CLAUDE.md`. La friction est réelle pour le cycle test/fix rapide.
**Fichier(s)** : `Makefile`
**Correction** : ajouter une cible `make test f="MyTest"` ou `make t f="testMyMethod"` qui encapsule `docker compose exec php php vendor/bin/phpunit --filter "$(f)"`.

### 13 — Pas de commande `make` pour dump des réponses réelles vs snapshots

**Problème** : le debug des tests d'intégration nécessite de décommenter manuellement un bloc `file_put_contents` dans `JsonResponseTrait.php` selon la documentation dans `CLAUDE.md`. Ce workflow est fragile (risque de commit du code décommenté).
**Fichier(s)** : `tests/src/Integration/Trait/JsonResponseTrait.php`, `CLAUDE.md:95`
**Correction** : remplacer le bloc commenté par une variable d'env `DUMP_RESPONSES=1` lue dans `JsonResponseTrait`, injectable via `make` (`DUMP_RESPONSES=1 make tests-integration`).
