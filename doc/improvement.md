# Améliorations — Pokenini Back

## Tests

### 1. Couverture à 100% — maintenir la discipline Infection
**Problème** : Rien d'identifié sur la couverture. La contrainte 100% MSI Infection est en place et vérifiée en CI. Risque : les assertions peuvent être trop larges (pas de valeurs précises vérifiées) sans que la couverture s'en aperçoive.

**Fichiers** : `tests/src/Integration/Album/AlbumPokedexTest.php`

**Correction** : S'assurer que chaque snapshot JSON de test couvre des cas limites (dex privé, dex non released, filtres multiples) et pas seulement le cas nominal. Bonne pratique déjà partiellement en place (`testGetForAPublicDex`, etc.).

---

## Maintenabilité

### 2. Psalm épinglé sur une version exacte (`6.16.1`)
**Problème** : Les autres outils utilisent des contraintes `^` (majeure fixe), mais Psalm est épinglé sur `6.16.1` exactement. Les corrections de bugs ne sont pas récupérées automatiquement avec `make updates`.

**Fichiers** : `tools/psalm/composer.json:15`

**Correction** : Utiliser `^6.16.1` pour suivre les patches. Tester la mise à jour avec `make psalm` avant de valider.

---

## DevX

### 3. `make security` ne vérifie pas les outils qualité (`tools/`)
**Problème** : `composer audit` (via `make security`) ne s'exécute que sur `composer.json` racine. Les outils dans `tools/*/composer.json` peuvent contenir des dépendances avec des vulnérabilités connues, non auditées.

**Correction** : Étendre la target `security` du Makefile pour auditer chaque outil :
```makefile
security-tools:
    for tool in tools/*/; do $(COMPOSER) audit --working-dir=$$tool; done
```

### 4. Nettoyage des mocks inutilisés non intégré au workflow CI
**Problème** : `make clean-unused-files` et `make clean-moco-routes` sont des outils utiles mais pas exécutés en CI. Des fichiers moco orphelins peuvent s'accumuler.

**Correction** : Exécuter ces outils en CI (ou en pre-commit hook) et échouer si des fichiers non référencés sont détectés.
