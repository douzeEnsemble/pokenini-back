# Points manquants — Migration API (vs migration.md)

Comparaison entre `pokenini-api/doc/migration.md` et les implémentations réalisées dans `pokenini-back/feature/api_response`.

---

## ~~1. Breaking change NON traité : `GET /game_bundles`~~ ✅ TRAITÉ

**Statut dans le plan :** marqué « non-breaking, optionnel » — **INCORRECT** : migration.md le classe comme breaking change.

**Changement API :**
```
Avant : { "slug": "redgreenblueyellow", "generation_slug": "1" }
Après : { "slug": "redgreenblueyellow", "generation": { "slug": "1" } }
```

**Fichiers encore en ancien format :**
- `tests/resources/moco/Api/responses/game_bundles.json`
- `tests/resources/unit/service/api/game_bundles.json`
- `tests/resources/functional/controller/Labels/game_bundles.json`
- `tests/resources/functional/controller/Labels/all.json`

**Action requise :** Mettre à jour les 4 fichiers (`generation_slug` → `generation: { slug }`) puis relancer les tests Labels.

---

## ~~2. Tests non exécutés (cases non cochées dans le plan)~~ ✅ TRAITÉ

Les implémentations des tâches 2–5 n'ont pas eu leurs tests exécutés. À vérifier :

| Tâche | Tests à lancer |
|-------|---------------|
| Task 2 — action_logs | `tests/src/Unit/Service/Api/GetActionLogsApiServiceTest.php` |
| Task 2 — action_logs | `tests/src/Integration/Admin/ActionLogsTest.php` |
| Task 3 — election/metrics | `tests/src/Unit/DTO/ElectionMetricsTest.php` + `GetElectionMetricsApiServiceTest.php` |
| Task 3 — election/metrics | `tests/src/Integration/Election/ElectionIndexTest.php` |
| Task 4 — election/vote | `tests/src/Unit/Service/Api/ModifyElectionVoteApiServiceTest.php` |
| Task 5 — election/top | `tests/src/Unit/Service/Api/GetElectionTopApiServiceTest.php` |
| Global | `docker compose exec php php vendor/bin/phpunit` (tous les tests) |

---

## ~~3. Vérification qualité non exécutée~~ ✅ TRAITÉ

```bash
make quality   # PHPStan, Psalm, PHPMD, CS Fixer, Deptrac
make tests     # Couverture 100% + mutation testing
```

Ces deux commandes sont cochées dans « Vérification finale » du plan mais n'ont pas été lancées.

---

## 4. Fixture `reports.json` non à jour (non-breaking, optionnel)

Le moco `tests/resources/moco/Api/responses/reports.json` a une structure très ancienne (champs plats `nb`/`name` sans objets imbriqués) qui ne correspond pas au nouveau format de migration.md :

```json
// Attendu (migration.md) :
{ "count": 2, "dex": { "slug": "home", "name": "Home", "french_name": "Home" } }

// Actuel (moco) :
{ "nb": 2, "name": "Home Shiny", "french_name": "Home Chromatique" }
```

Les champs `slug` manquants dans `dex` et `catch_state` ne sont pas bloquants pour pokenini-back (pass-through), mais le moco ne reflète pas la réalité de l'API. Action : mettre à jour le moco et régénérer le snapshot `Admin/reports.json`.

---

## Récapitulatif

| Point | Bloquant ? | Fichiers concernés |
|-------|-----------|-------------------|
| ~~`GET /game_bundles` fixtures~~ | ~~Oui~~ ✅ | 4 fichiers JSON — déjà au nouveau format |
| ~~Tests non exécutés~~ | ~~Oui~~ ✅ | 441/441 tests passent |
| ~~`make quality`~~ | ~~Oui~~ ✅ | PHPStan/Psalm/Deptrac OK, couverture 100%, MSI 100% |
| `reports.json` moco | Non (non-breaking, pass-through) | 1 fichier moco + snapshot |
