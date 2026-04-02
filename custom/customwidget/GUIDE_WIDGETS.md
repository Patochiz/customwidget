# Guide rapide - Génération de widgets par type

Ce guide explique comment créer chaque type de widget dans le module CustomWidget.

---

## Prérequis communs

- Accéder à **Outils > CustomWidgets > Nouveau widget**
- Chaque widget nécessite :
  - **Référence** : auto-générée si vide (format `WID-YYYYMMDDHHMMSS`)
  - **Libellé** : nom affiché sur le dashboard
  - **Requête SQL** : requête `SELECT` uniquement (utiliser `__PREFIX__` pour le préfixe des tables)
  - **Zone d'affichage** : `box` (boîte dashboard) ou `stats` (zone statistiques page d'accueil)
  - **Position** : ordre d'affichage (entier)
  - **Groupes** : laisser vide = visible par tous, sinon restreindre aux groupes sélectionnés

---

## 1. Widget NUMBER (KPI)

Affiche une valeur numérique mise en avant (chiffre d'affaires, nombre de clients, etc.).

### Configuration

| Champ | Description | Exemple |
|-------|-------------|---------|
| **Icône** | FontAwesome ou emoji | `fa-euro-sign`, `fa-users` |
| **Couleur** | Code hex pour la bordure | `#0077b6` |
| **Suffixe** | Texte après la valeur | ` €`, ` %`, ` clients` |
| **URL clic** | Lien au clic sur le widget | `/compta/facture/list.php` |
| **Sous-indicateur 1** | Requête SQL secondaire + libellé | Voir exemple |
| **Sous-indicateur 2** | Requête SQL tertiaire + libellé | Voir exemple |

### Exemple : CA du mois en cours

```sql
-- Requête principale
SELECT SUM(total_ht) FROM __PREFIX__facture
WHERE DATE_FORMAT(datef, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m')
```

- **Icône** : `fa-chart-bar`
- **Couleur** : `#00b4d8`
- **Suffixe** : ` €`
- **Sous-indicateur 1 SQL** :
  ```sql
  SELECT COUNT(*) FROM __PREFIX__facture
  WHERE DATE_FORMAT(datef, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m')
  ```
- **Sous-indicateur 1 Libellé** : `Factures`

### Rendu

```
┌─────────────────────────────┐
│  📊  125 430 €              │
│  Chiffre d'affaires mensuel │
│  42 Factures | 5 Impayées   │
└─────────────────────────────┘
```

### Astuces
- La requête doit retourner **une seule valeur** (première colonne, première ligne)
- Zone `stats` recommandée pour les KPI (injection dans la zone statistiques de la page d'accueil)
- Les sous-indicateurs sont optionnels et apparaissent sous la valeur principale

---

## 2. Widget TABLE

Affiche les résultats SQL sous forme de tableau avec colonnes configurables.

### Configuration

| Champ | Description | Exemple |
|-------|-------------|---------|
| **Nb lignes max** | Limite d'affichage (max 500) | `10` |
| **Colonnes** | Configuration JSON des colonnes | Voir ci-dessous |

### Configuration des colonnes

Chaque colonne est définie par :
- **name** : nom de la colonne SQL
- **label** : libellé affiché dans l'en-tête
- **type** : format d'affichage
- **link** : URL cliquable (optionnel)

**Types disponibles :**

| Type | Affichage |
|------|-----------|
| `text` | Texte brut |
| `integer` | Nombre avec séparateur de milliers |
| `price` | Montant avec symbole monétaire |
| `percentage` | Valeur suivie de `%` |
| `date` | Date formatée |
| `status` | Badge coloré (vert/rouge) |

**Liens** : utiliser `{value}` (valeur de la cellule) et `{rowid}` (ID de ligne) comme placeholders.

### Exemple : Dernières factures

```sql
SELECT f.rowid, f.ref, s.nom, f.total_ht, f.datef, f.paye
FROM __PREFIX__facture f
LEFT JOIN __PREFIX__societe s ON f.fk_soc = s.rowid
ORDER BY f.datef DESC
```

Configuration des colonnes :
```json
[
  {"name": "ref",      "label": "Référence", "type": "text",    "link": "/compta/facture/card.php?facid={rowid}"},
  {"name": "nom",      "label": "Client",    "type": "text",    "link": ""},
  {"name": "total_ht", "label": "Montant HT","type": "price",   "link": ""},
  {"name": "datef",    "label": "Date",      "type": "date",    "link": ""},
  {"name": "paye",     "label": "Payée",     "type": "status",  "link": ""}
]
```

- **Nb lignes max** : `10`

### Rendu

```
┌────────────┬──────────┬───────────┬────────────┬────────┐
│ Référence  │ Client   │ Montant HT│ Date       │ Payée  │
├────────────┼──────────┼───────────┼────────────┼────────┤
│ FA2401-001 │ Acme SAS │ 1 250,00€ │ 01/03/2025 │ ● Oui  │
│ FA2401-002 │ Beta Ltd │   850,00€ │ 28/02/2025 │ ● Non  │
└────────────┴──────────┴───────────┴────────────┴────────┘
```

### Astuces
- Les colonnes JSON doivent correspondre exactement aux alias SQL
- Le `LIMIT` est ajouté automatiquement si absent (basé sur **Nb lignes max**)
- Les JOINs sont autorisés par défaut (configurable dans les paramètres du module)

---

## 3. Widget CHART (Graphique)

Affiche un graphique interactif via Chart.js.

### Configuration

| Champ | Description | Exemple |
|-------|-------------|---------|
| **Type de graphique** | Forme du graphique | `bar`, `line`, `doughnut`, `pie` |
| **Hauteur** | Hauteur en pixels (min 100) | `300` |
| **Colonne labels** | Index de la colonne SQL pour les étiquettes (axe X) | `0` |
| **Colonne données** | Index de la colonne SQL pour les valeurs (axe Y) | `1` |
| **Couleurs** | Tableau JSON de codes hex | `["#0077b6", "#00b4d8"]` |

### Types de graphiques

| Type | Usage recommandé |
|------|------------------|
| `bar` | Comparaison de valeurs (CA par mois, ventes par produit) |
| `line` | Évolution temporelle (tendance, progression) |
| `doughnut` | Répartition proportionnelle (parts de marché) |
| `pie` | Répartition proportionnelle (alternative au doughnut) |

### Exemple : CA mensuel sur 12 mois (barres)

```sql
SELECT DATE_FORMAT(datef, '%Y-%m') AS mois, SUM(total_ht) AS ca
FROM __PREFIX__facture
WHERE datef >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
GROUP BY DATE_FORMAT(datef, '%Y-%m')
ORDER BY mois ASC
```

- **Type** : `bar`
- **Hauteur** : `350`
- **Colonne labels** : `0` (mois)
- **Colonne données** : `1` (ca)
- **Couleurs** : `["#0077b6", "#00b4d8", "#90e0ef", "#caf0f8"]`

### Exemple : Répartition par statut (doughnut)

```sql
SELECT
  CASE fk_statut
    WHEN 0 THEN 'Brouillon'
    WHEN 1 THEN 'Validée'
    WHEN 2 THEN 'Payée'
    WHEN 3 THEN 'Abandonnée'
  END AS statut,
  COUNT(*) AS nb
FROM __PREFIX__facture
GROUP BY fk_statut
```

- **Type** : `doughnut`
- **Colonne labels** : `0` (statut)
- **Colonne données** : `1` (nb)
- **Couleurs** : `["#2ecc71", "#3498db", "#9b59b6", "#e74c3c"]`

### Astuces
- La requête doit retourner **2 colonnes minimum** : une pour les labels, une pour les données
- Les colonnes sont référencées par **index** (0 = première colonne, 1 = deuxième, etc.)
- Les valeurs de données sont converties en nombre décimal automatiquement
- Maximum 500 lignes de données
- Les couleurs par défaut sont appliquées si le champ est vide

---

## Rappels importants

### Sécurité SQL
- Seules les requêtes `SELECT` sont autorisées
- Mots-clés interdits : `INSERT`, `UPDATE`, `DELETE`, `DROP`, `ALTER`, `TRUNCATE`, `CREATE`, `EXEC`, `GRANT`, etc.
- Utiliser `__PREFIX__` au lieu du préfixe de table en dur (ex: `llx_`)

### Cache
- Configurable par widget (en secondes, 0 = pas de cache)
- Valeur par défaut : 300 secondes (5 minutes)
- Le bouton "Rafraîchir" sur le dashboard force le recalcul

### Test et prévisualisation
- **Tester la requête** : bouton disponible dans le formulaire, exécute la requête et affiche les résultats (max 20 lignes)
- **Prévisualiser** : affiche le rendu final du widget avant sauvegarde

### Clonage
- Un widget existant peut être dupliqué via le bouton "Cloner"
- Le clone est créé en état **inactif** par défaut
- La référence est préfixée par `CLONE-`
