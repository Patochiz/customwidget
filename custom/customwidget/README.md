# Module CustomWidget pour Dolibarr

Widgets SQL personnalisés pour le tableau de bord Dolibarr.

## Fonctionnalités

- Création de widgets personnalisés via requêtes SQL SELECT
- 3 types : **Nombre (KPI)**, **Tableau**, **Graphique (Chart.js)**
- 2 zones d'affichage : boîtes dashboard ou zone statistiques
- Interface d'administration complète (CRUD)
- Test de requête en temps réel
- Prévisualisation avant sauvegarde
- Gestion des permissions par groupes utilisateurs
- Cache fichier configurable
- Drag & drop pour réordonner les widgets
- Remplacement automatique de `__PREFIX__` par le préfixe des tables

## Installation

1. Décompresser dans `/custom/customwidget/`
2. Activer le module dans **Accueil > Configuration > Modules/Applications**
3. Configurer via **Outils > CustomWidgets**

## Sécurité SQL

Seules les requêtes `SELECT` sont autorisées. Les mots-clés dangereux sont bloqués.

## Version

- **Module** : 1.0.0
- **Dolibarr** : 20.0.0+
- **PHP** : >= 7.4
