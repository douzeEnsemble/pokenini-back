# Améliorations — Pokenini Back

## Maintenabilité

## DevX

### 4. Nettoyage des mocks inutilisés non intégré au workflow CI
**Problème** : `make clean-unused-files` et `make clean-moco-routes` sont des outils utiles mais pas exécutés en CI. Des fichiers moco orphelins peuvent s'accumuler.

**Correction** : Exécuter ces outils en CI (ou en pre-commit hook) et échouer si des fichiers non référencés sont détectés.
