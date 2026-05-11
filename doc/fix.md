# Correctifs — Pokenini Back

Recherche de dette technique : aucun `TODO`, `FIXME`, `HACK`, `XXX` ni `@deprecated` trouvé dans `src/` ou `tests/`.

## Exceptions et choix délibérés

- **Credentials OAuth dans les `.env`** : les valeurs de `OAUTH_DISCORD_CLIENT_SECRET` et `OAUTH_GOOGLE_CLIENT_SECRET` ressemblent intentionnellement à des credentials réels (format `GOCSPX-`…) mais n'en sont pas — choix délibéré pour tester des formats valides en dev/test.
- **Mot de passe Redis dans `docker-compose.yaml`** : `requirepass douze` hardcodé est intentionnel pour l'environnement de développement local.
- **`AbstractAdminActionController` non-`final`** : classe abstraite par nécessité de design (partage de `execute()` entre sous-classes concrètes qui sont, elles, toutes `final`).
