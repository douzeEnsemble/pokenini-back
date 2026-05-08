# Correctifs — pokenini-back

## Recherche préliminaire

```bash
grep -rn "TODO\|FIXME\|HACK\|XXX\|@deprecated" src/ tests/
```

**Résultat : aucun TODO/FIXME/HACK/XXX/@deprecated trouvé dans le code source.**

---

## Haute priorité

- [ ] [haute] `catch (\Exception $e)` trop générique dans `AbstractAdminActionController`
  Fichier : `src/Controller/Admin/AbstractAdminActionController.php:35`
  Suggestion : restreindre au moins à `\RuntimeException` ou créer une hiérarchie d'exceptions admin. Actuellement, toute exception (y compris `TypeError`, `LogicException`) est silencieusement loguée comme erreur critique et retourne un HTTP 500 — le comportement intentionnel est présent mais la portée est trop large.

- [ ] [haute] `.env.prod` contient `API_URL=http://moco.api` — valeur de test qui pointe vers le mock Moco
  Fichier : `.env.prod:24`
  Suggestion : remplacer par l'URL réelle de pokenini-api en production. En l'état, un déploiement sans override de cette variable enverrait les requêtes vers un serveur Moco inexistant.

- [ ] [haute] Credentials OAuth en clair dans `.env.prod` et `.env.dev`
  Fichier : `.env.prod:43-46`, `.env.dev:43-46`
  Suggestion : ces fichiers sont versionnés avec des secrets OAuth (Google, Discord). Même si ce sont des valeurs de dev/staging, les commitre dans git présente un risque. Utiliser des variables d'environnement injectées au déploiement ou un gestionnaire de secrets.

---

## Priorité moyenne

- [ ] [moyenne] `switch` sans branche `default` dans `AbstractAdminActionController::doAction`
  Fichier : `src/Controller/Admin/AbstractAdminActionController.php:68-78`
  Suggestion : si une action inconnue est passée, le switch ne fait rien (ni update ni calculate) mais `cacheInvalidatorService->invalidate($name)` est quand même appelé — l'invalidation de cache se produit sans que l'action métier ait été exécutée. Ajouter un `default: throw new \InvalidArgumentException("Unknown action '$action'")` ou documenter explicitement ce comportement.

- [ ] [moyenne] `cache.yaml` n'utilise que `cache.adapter.filesystem` sans distinction par environnement
  Fichier : `config/packages/cache.yaml:3`
  Suggestion : Redis est disponible dans Docker (`redis:8.6.2`) mais n'est pas configuré comme adaptateur de cache. En production, le filesystem partagé entre workers PHP-FPM peut causer des incohérences. Ajouter une configuration `when@prod` avec `cache.adapter.redis`.

- [ ] [moyenne] `TrainerIdsService` est un service stateful (propriétés mutées par `init()`)
  Fichier : `src/Service/TrainerIdsService.php:13-16`
  Suggestion : `$loggedTrainerId`, `$trainerId` et `$requestedTrainerId` sont initialisés à `null` puis mutés par `init()`. Si `init()` n'est pas appelé, toutes les méthodes retournent `null` silencieusement. Documenter l'obligation d'appeler `init()` avant toute utilisation, ou transformer en méthode de factory retournant un objet immuable.

---

## Basse priorité

- [ ] [basse] `KeyMaker` expose 12 getters statiques redondants (un par constante)
  Fichier : `src/Cache/KeyMaker.php:28-79`
  Suggestion : les méthodes `getDexKey()`, `getCatchStatesKey()`, etc. sont de simples wrappers retournant une constante privée. Les constantes pourraient être rendues `public const` directement. Amélioration cosmétique sans impact fonctionnel, mais réduit la boilerplate.

- [ ] [basse] `'pokemons' => fn (): null => null` dans `CacheInvalidatorService` — no-op non documenté
  Fichier : `src/Service/CacheInvalidatorService.php:47`
  Suggestion : le type `'pokemons'` ne déclenche aucune invalidation de cache. Si c'est intentionnel (les données Pokémon ne sont pas cachées côté BFF), ajouter un commentaire expliquant pourquoi ce type existe dans la map.

- [ ] [basse] `AlbumFilters/FromRequest.php` n'est pas dans le namespace `AlbumFilters` attendu
  Fichier : `src/AlbumFilters/FromRequest.php`
  Suggestion : la classe s'appelle `FromRequest` mais l'usage métier serait plus clair avec `AlbumFilters` ou `AlbumFiltersFromRequest`. Pas un bug, mais le nommage peut surprendre un nouveau développeur attendant une classe `AlbumFilters`.

- [ ] [basse] `resources/version` ne semble pas être consommé par le code applicatif
  Fichier : `resources/metadata/version`
  Suggestion : vérifier si ce fichier est utilisé (endpoint de version ? déploiement ?). S'il n'est pas consommé, le documenter ou le supprimer.
