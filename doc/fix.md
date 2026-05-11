# Correctifs — Pokenini Back

Recherche de dette technique : aucun `TODO`, `FIXME`, `HACK`, `XXX` ni `@deprecated` trouvé dans `src/` ou `tests/`.

## Exceptions et choix délibérés

- **Credentials OAuth dans les `.env`** : les valeurs de `OAUTH_DISCORD_CLIENT_SECRET` et `OAUTH_GOOGLE_CLIENT_SECRET` ressemblent intentionnellement à des credentials réels (format `GOCSPX-`…) mais n'en sont pas — choix délibéré pour tester des formats valides en dev/test.
- **Mot de passe Redis dans `docker-compose.yaml`** : `requirepass douze` hardcodé est intentionnel pour l'environnement de développement local.
- **`AbstractAdminActionController` non-`final`** : classe abstraite par nécessité de design (partage de `execute()` entre sous-classes concrètes qui sont, elles, toutes `final`).

---

## Priorité moyenne

- [x] [moyenne] Catch générique `\Exception` dans `AbstractAdminActionController`
  Fichier : `src/Controller/Admin/AbstractAdminActionController.php:36`
  Résolu : remplacé par `catch (ExceptionInterface|\InvalidArgumentException $e)` — couvre les erreurs HTTP client (transport + HTTP) et les types d'invalidation inconnus.

- [x] [moyenne] Ruleset PHP CS Fixer `@PHP83Migration` avec PHP 8.5.6
  Fichier : `.php-cs-fixer.dist.php:19`
  Résolu : mis à jour vers `@PHP85Migration` (disponible dans php-cs-fixer v3.95.1).

- [x] [moyenne] Version `actions/checkout` incohérente entre les workflows CI
  Fichier : `.github/workflows/security.yml:15`
  Résolu : mis à jour de `@v5` vers `@v6`.


---

## Basse priorité

- [x] [basse] Comparaison lâche (`!=`) pour les identifiants dresseur
  Fichier : `src/Controller/Album/AlbumPokedexController.php:67`
  Résolu : remplacé `!=` par `!==`.

- [x] [basse] Logging de la réponse complète en `info` pour chaque appel API
  Fichier : `src/Service/Api/AbstractApiService.php:55-60`
  Résolu : code HTTP loggé en `info`, body de la réponse passé en `debug` (invisible en prod via `fingers_crossed action_level: error`, visible en dev).

- [x] [basse] `TrainerIdsService` requiert un appel explicite à `init()` avant usage
  Fichier : `src/Service/TrainerIdsService.php:23`
  Résolu : ajout d'un flag `$initialized` et d'une `\LogicException` dans `getTrainerId()` et `getLoggedTrainerId()` si `init()` n'a pas été appelé.
