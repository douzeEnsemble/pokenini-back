# Correctifs — Pokenini Back

Recherche de dette technique : aucun `TODO`, `FIXME`, `HACK`, `XXX` ni `@deprecated` trouvé dans `src/` ou `tests/`.

## Exceptions et choix délibérés

- **Credentials OAuth dans les `.env`** : les valeurs de `OAUTH_DISCORD_CLIENT_SECRET` et `OAUTH_GOOGLE_CLIENT_SECRET` ressemblent intentionnellement à des credentials réels (format `GOCSPX-`…) mais n'en sont pas — choix délibéré pour tester des formats valides en dev/test.
- **Mot de passe Redis dans `docker-compose.yaml`** : `requirepass douze` hardcodé est intentionnel pour l'environnement de développement local.

---

## Priorité moyenne

- [x] [moyenne] Catch générique `\Exception` dans `AbstractAdminActionController`
  Fichier : `src/Controller/Admin/AbstractAdminActionController.php:36`
  Résolu : remplacé par `catch (ExceptionInterface|\InvalidArgumentException $e)` — couvre les erreurs HTTP client (transport + HTTP) et les types d'invalidation inconnus.

- [x] [moyenne] Ruleset PHP CS Fixer `@PHP83Migration` avec PHP 8.5.6
  Fichier : `.php-cs-fixer.dist.php:19`
  Résolu : mis à jour vers `@PHP85Migration` (disponible dans php-cs-fixer v3.95.1).

- [ ] [moyenne] Version `actions/checkout` incohérente entre les workflows CI
  Fichier : `.github/workflows/security.yml:15` (`@v5`) vs `.github/workflows/ci_codequality.yml:29`, `ci_tests.yml:30` (`@v6`)
  Suggestion : Standardiser sur `actions/checkout@v6` (ou la dernière version stable) dans tous les workflows.

- [ ] [moyenne] `AbstractAdminActionController` non-`final` sans nécessité d'extension externe
  Fichier : `src/Controller/Admin/AbstractAdminActionController.php:15`
  Suggestion : La classe est abstraite pour mutualiser `execute()`, mais les quatre sous-classes (`Calculate`, `Update`, `Invalidate`, `Logs`) sont toutes dans le même namespace. Il s'agit davantage d'un pattern template method interne. Documenter explicitement pourquoi elle n'est pas `final` ou envisager une composition plutôt qu'héritage.

---

## Basse priorité

- [ ] [basse] Comparaison lâche (`!=`) pour les identifiants dresseur
  Fichier : `src/Controller/Album/AlbumPokedexController.php:67`
  Suggestion : `$this->trainerIdsService->getTrainerId() != $this->trainerIdsService->getLoggedTrainerId()` utilise `!=` au lieu de `!==`. Les deux méthodes retournent `?string` — le comportement est correct en pratique (null != "string" est true), mais `!==` est plus explicite et évite toute surprise de coercition de type.

- [ ] [basse] Logging de la réponse complète en `info` pour chaque appel API
  Fichier : `src/Service/Api/AbstractApiService.php:55-60`
  Suggestion : `$response->getContent()` est loggé en entier à chaque requête HTTP sortante. En production avec un volume important, cela génère des logs très verbeux pouvant contenir des données utilisateur. Envisager de logger uniquement le code HTTP en prod (niveau `debug` pour le body) ou de tronquer le contenu.

- [ ] [basse] `TrainerIdsService` requiert un appel explicite à `init()` avant usage
  Fichier : `src/Service/TrainerIdsService.php:23`
  Suggestion : Le pattern `init()` obligatoire avant `getTrainerId()` est fragile — aucune garde ne protège contre un appel sans `init()` préalable (les propriétés sont `null` par défaut). Documenter explicitement ou lever une exception si `getTrainerId()` est appelé sans `init()`.
