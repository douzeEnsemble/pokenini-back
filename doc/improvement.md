# Améliorations — Pokenini Back

## Performance

### 1. Logging verbeux des réponses API en production
**Problème** : `AbstractApiService::request()` logue le corps complet de chaque réponse HTTP en niveau `info`. Pour des endpoints renvoyant de gros JSON (pokédex complets, listes de Pokémon), cela génère des logs volumineux à chaque requête.

**Fichiers** : `src/Service/Api/AbstractApiService.php:55-60`

**Correction** : Déplacer le log du body en niveau `debug`, ne logger que le code HTTP en `info`. Utiliser la configuration Monolog par environnement pour activer le niveau `debug` uniquement en dev.

---

## Qualité du code

### 2. Pattern `init()` obligatoire avant usage dans `TrainerIdsService`
**Problème** : `TrainerIdsService` expose `getTrainerId()` et `getLoggedTrainerId()` qui retournent `null` si `init()` n'a pas été appelé. L'appelant (`AlbumPokedexController`) doit se souvenir d'appeler `init()` manuellement. Aucune protection si oublié.

**Fichiers** : `src/Service/TrainerIdsService.php:23`, `src/Controller/Album/AlbumPokedexController.php:31`

**Correction** : Déplacer la logique d'initialisation dans le constructeur via le `RequestStack` (comme c'est déjà injecté), ou utiliser une lazy-initialization : appeler `init()` automatiquement dans `getTrainerId()` si l'état n'est pas initialisé.

### 3. Comparaison lâche pour des identifiants de type `?string`
**Problème** : `AlbumPokedexController::accessDexIsGranted()` utilise `!=` pour comparer deux `?string`. PHP coercera des valeurs inattendues. Convention du projet : typage strict partout.

**Fichiers** : `src/Controller/Album/AlbumPokedexController.php:67`

**Correction** : Remplacer `!=` par `!==`.

### 4. Catch générique dans le contrôleur admin
**Problème** : `AbstractAdminActionController::execute()` attrape `\Exception` — masque potentiellement des erreurs de programmation (`TypeError`, `Error`, `LogicException`) et les traite comme des erreurs fonctionnelles retournées au client.

**Fichiers** : `src/Controller/Admin/AbstractAdminActionController.php:35`

**Correction** : Restreindre le catch aux exceptions métier attendues (`HttpExceptionInterface`, `TransportExceptionInterface`) ; laisser les autres exceptions remonter au gestionnaire d'erreurs Symfony.

### 5. `CacheInvalidatorService` : couplage fort avec tous les types connus
**Problème** : La méthode `invalidate()` contient une map statique de tous les types connus. Ajouter un nouveau type oblige à modifier cette classe centrale. Si le domaine grossit, le couplage devient problématique.

**Fichiers** : `src/Service/CacheInvalidatorService.php:27-68`

**Correction** : Envisager un tag Symfony avec `#[AsTaggedItem]` et une collection d'invalidateurs injectée — chaque invalidateur déclare les types qu'il gère. Décision à peser selon la stabilité du domaine.

---

## Tests

### 6. Couverture à 100% — maintenir la discipline Infection
**Problème** : Rien d'identifié sur la couverture. La contrainte 100% MSI Infection est en place et vérifiée en CI. Risque : les assertions peuvent être trop larges (pas de valeurs précises vérifiées) sans que la couverture s'en aperçoive.

**Fichiers** : `tests/src/Integration/Album/AlbumPokedexTest.php`

**Correction** : S'assurer que chaque snapshot JSON de test couvre des cas limites (dex privé, dex non released, filtres multiples) et pas seulement le cas nominal. Bonne pratique déjà partiellement en place (`testGetForAPublicDex`, etc.).

---

## Sécurité

### 7. Absence de rate limiting sur les endpoints sensibles
**Problème** : Aucun rate limiting configuré (ni Symfony RateLimiter, ni Nginx). Les endpoints d'authentification OAuth (`/fr/connect/*`) et les endpoints d'écriture (`/album PATCH`, `/election/vote`) sont exposés à des abus.

**Correction** : Configurer `symfony/rate-limiter` avec une politique par IP sur les endpoints OAuth et d'écriture. Ou déléguer au reverse proxy Nginx (`limit_req_zone`).

### 8. SHA1 utilisé pour dériver les identifiants de cache dresseur
**Problème** : `UserTokenService::getLoggedUserToken()` utilise `sha1($user->getUserIdentifier())` comme clé de cache trainer. SHA1 est considéré cassé pour la cryptographie.

**Fichiers** : `src/Security/UserTokenService.php:26`

**Correction** : Utiliser `hash('sha256', ...)` ou directement l'identifiant OAuth (après validation qu'il est safe pour une clé de cache).

---

## Maintenabilité

### 9. Ruleset PHP CS Fixer `@PHP83Migration` avec PHP 8.5.6
**Problème** : Le projet cible PHP ≥ 8.5.6 mais le ruleset est `@PHP83Migration`, manquant les règles de style propres à PHP 8.4 et 8.5.

**Fichiers** : `.php-cs-fixer.dist.php:19`

**Correction** : Mettre à jour vers `@PHP84Migration` (disponible dans PHP CS Fixer 3.x) puis `@PHP85Migration` dès que disponible.

### 10. Psalm épinglé sur une version exacte (`6.16.1`)
**Problème** : Les autres outils utilisent des contraintes `^` (majeure fixe), mais Psalm est épinglé sur `6.16.1` exactement. Les corrections de bugs ne sont pas récupérées automatiquement avec `make updates`.

**Fichiers** : `tools/psalm/composer.json:15`

**Correction** : Utiliser `^6.16.1` pour suivre les patches. Tester la mise à jour avec `make psalm` avant de valider.

---

## DevX

### 11. `make security` ne vérifie pas les outils qualité (`tools/`)
**Problème** : `composer audit` (via `make security`) ne s'exécute que sur `composer.json` racine. Les outils dans `tools/*/composer.json` peuvent contenir des dépendances avec des vulnérabilités connues, non auditées.

**Correction** : Étendre la target `security` du Makefile pour auditer chaque outil :
```makefile
security-tools:
    for tool in tools/*/; do $(COMPOSER) audit --working-dir=$$tool; done
```

### 12. Nettoyage des mocks inutilisés non intégré au workflow CI
**Problème** : `make clean-unused-files` et `make clean-moco-routes` sont des outils utiles mais pas exécutés en CI. Des fichiers moco orphelins peuvent s'accumuler.

**Correction** : Exécuter ces outils en CI (ou en pre-commit hook) et échouer si des fichiers non référencés sont détectés.
