# Améliorations — Pokenini Back

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
