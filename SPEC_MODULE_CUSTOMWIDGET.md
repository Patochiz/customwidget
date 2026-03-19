# SPEC MODULE CUSTOMWIDGET — Widgets SQL personnalisés pour Dolibarr

## 1. CONTEXTE & OBJECTIF

Module Dolibarr permettant de créer des widgets personnalisés sur le tableau de bord, alimentés par des requêtes SQL `SELECT` définies par l'administrateur via une interface graphique (aucun code requis).

### Environnement cible
- **Dolibarr** : 20.0.0
- **Hébergement** : OVH mutualisé (pas de SSH, pas de Composer)
- **Chemin serveur** : `/home/diamanti/www/doli/`
- **Modules custom** : `/home/diamanti/www/doli/custom/`
- **PHP** : >= 7.4
- **URL** : `https://diamant-industrie.com/doli`

### Principes de développement
- **Fichiers complets** : toujours livrer des fichiers entiers, jamais de patchs
- **Petits fichiers** : séparer fonctions et logique dans des fichiers distincts
- **Nommage module** : `customwidget` (tout en minuscules, pas de tirets ni underscores)
- **Préfixe tables** : `llx_customwidget`
- **Préfixe constantes** : `CUSTOMWIDGET_`

---

## 2. ARBORESCENCE DU MODULE

```
custom/customwidget/
├── core/
│   ├── modules/
│   │   └── modCustomWidget.class.php          # Descripteur du module
│   ├── boxes/
│   │   └── box_customwidget.php               # Classe box générique dashboard
│   └── hooks/
│       └── customwidget.class.php             # Hooks (dashboard stats zone)
├── class/
│   ├── customwidget.class.php                 # Classe CRUD principale (définition widget)
│   └── customwidget.helper.class.php          # Fonctions utilitaires (exécution SQL, formatage)
├── admin/
│   ├── setup.php                              # Page configuration générale du module
│   └── about.php                              # Page à propos
├── widget/
│   ├── list.php                               # Liste des widgets (CRUD)
│   ├── card.php                               # Fiche création/édition d'un widget
│   ├── testquery.php                          # Page AJAX test de requête SQL
│   └── clone.php                              # Duplication d'un widget
├── ajax/
│   ├── testquery.php                          # Endpoint AJAX : test requête SQL
│   ├── preview.php                            # Endpoint AJAX : prévisualisation widget
│   └── reorder.php                            # Endpoint AJAX : réordonner les widgets
├── lib/
│   └── customwidget.lib.php                   # Fonctions lib (onglets admin, helpers)
├── sql/
│   ├── llx_customwidget.sql                   # Création table principale
│   ├── llx_customwidget.key.sql               # Index
│   ├── llx_customwidget_usergroup.sql         # Table liaison widget <-> groupes
│   └── llx_customwidget_usergroup.key.sql     # Index
├── langs/
│   ├── fr_FR/
│   │   └── customwidget.lang                  # Traductions françaises
│   └── en_US/
│       └── customwidget.lang                  # Traductions anglaises
├── css/
│   └── customwidget.css                       # Styles spécifiques
├── js/
│   └── customwidget.js                        # JS (preview, test query, chart rendering)
├── img/
│   └── object_customwidget.png                # Icône module (32x32)
└── README.md
```

---

## 3. BASE DE DONNÉES

### 3.1 Table `llx_customwidget`

Stocke les définitions de widgets.

```sql
CREATE TABLE IF NOT EXISTS llx_customwidget (
    rowid           INTEGER AUTO_INCREMENT PRIMARY KEY,
    ref             VARCHAR(128) NOT NULL,              -- Référence unique du widget
    label           VARCHAR(255) NOT NULL,              -- Titre affiché du widget
    description     TEXT,                                -- Description optionnelle
    widget_type     VARCHAR(20) NOT NULL DEFAULT 'number', -- 'number', 'table', 'chart'
    sql_query       TEXT NOT NULL,                       -- Requête SELECT
    
    -- Config Number (KPI)
    number_icon     VARCHAR(128) DEFAULT '',             -- Icône : fa-xxx, picto Dolibarr ou emoji
    number_color    VARCHAR(7) DEFAULT '#0077b6',        -- Couleur principale HEX
    number_suffix   VARCHAR(20) DEFAULT '',              -- Suffixe (€, %, unités...)
    number_sub1_sql TEXT,                                -- Sous-indicateur 1 (requête SQL)
    number_sub1_label VARCHAR(128) DEFAULT '',           -- Label sous-indicateur 1
    number_sub2_sql TEXT,                                -- Sous-indicateur 2 (requête SQL)
    number_sub2_label VARCHAR(128) DEFAULT '',           -- Label sous-indicateur 2
    number_url      VARCHAR(255) DEFAULT '',             -- URL clic sur le KPI
    
    -- Config Table
    table_columns   TEXT,                                -- JSON : [{name, label, type, link}]
    table_maxrows   INTEGER DEFAULT 10,                  -- Nombre de lignes max
    
    -- Config Chart
    chart_type      VARCHAR(20) DEFAULT 'bar',           -- 'bar', 'line', 'doughnut', 'pie'
    chart_colors    TEXT,                                -- JSON : palette de couleurs ["#xxx",...]
    chart_height    INTEGER DEFAULT 300,                 -- Hauteur en pixels
    chart_label_col INTEGER DEFAULT 0,                   -- Index colonne labels (résultat SQL)
    chart_data_col  INTEGER DEFAULT 1,                   -- Index colonne données (résultat SQL)
    
    -- Config commune
    display_zone    VARCHAR(20) DEFAULT 'box',           -- 'box' (boîtes dashboard) ou 'stats' (zone KPI native)
    position        INTEGER DEFAULT 0,                   -- Ordre d'affichage
    active          TINYINT DEFAULT 1,                   -- 1=actif, 0=désactivé
    cache_duration  INTEGER DEFAULT 300,                 -- Cache en secondes (0=pas de cache)
    
    -- Métadonnées
    entity          INTEGER DEFAULT 1,
    fk_user_creat   INTEGER,
    fk_user_modif   INTEGER,
    date_creation   DATETIME,
    tms             TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;
```

### 3.2 Table `llx_customwidget_usergroup`

Liaison N:N entre widgets et groupes d'utilisateurs autorisés.

```sql
CREATE TABLE IF NOT EXISTS llx_customwidget_usergroup (
    rowid           INTEGER AUTO_INCREMENT PRIMARY KEY,
    fk_customwidget INTEGER NOT NULL,
    fk_usergroup    INTEGER NOT NULL,
    entity          INTEGER DEFAULT 1
) ENGINE=InnoDB;
```

### 3.3 Index

```sql
-- llx_customwidget.key.sql
ALTER TABLE llx_customwidget ADD INDEX idx_customwidget_active (active);
ALTER TABLE llx_customwidget ADD INDEX idx_customwidget_type (widget_type);
ALTER TABLE llx_customwidget ADD INDEX idx_customwidget_entity (entity);
ALTER TABLE llx_customwidget ADD UNIQUE INDEX uk_customwidget_ref (ref, entity);

-- llx_customwidget_usergroup.key.sql
ALTER TABLE llx_customwidget_usergroup ADD INDEX idx_cwug_widget (fk_customwidget);
ALTER TABLE llx_customwidget_usergroup ADD INDEX idx_cwug_group (fk_usergroup);
ALTER TABLE llx_customwidget_usergroup ADD UNIQUE INDEX uk_cwug_pair (fk_customwidget, fk_usergroup);
```

---

## 4. DESCRIPTEUR DU MODULE — `modCustomWidget.class.php`

```
Emplacement : custom/customwidget/core/modules/modCustomWidget.class.php
```

### Paramètres clés

| Propriété | Valeur |
|---|---|
| `$this->numero` | `500200` (vérifier disponibilité) |
| `$this->rights_class` | `'customwidget'` |
| `$this->family` | `'other'` |
| `$this->module_position` | `90` |
| `$this->name` | `'CustomWidget'` |
| `$this->description` | `'Widgets SQL personnalisés pour le dashboard'` |
| `$this->version` | `'1.0.0'` |
| `$this->picto` | `'customwidget@customwidget'` |

### Permissions à déclarer

```
$r = 0;
$this->rights[$r][0] = $this->numero + $r + 1;     // 500201
$this->rights[$r][1] = 'Voir les widgets SQL';
$this->rights[$r][3] = 0; // non actif par défaut
$this->rights[$r][4] = 'read';

$r++;
$this->rights[$r][0] = $this->numero + $r + 1;     // 500202
$this->rights[$r][1] = 'Créer/modifier les widgets SQL';
$this->rights[$r][3] = 0;
$this->rights[$r][4] = 'write';

$r++;
$this->rights[$r][0] = $this->numero + $r + 1;     // 500203
$this->rights[$r][1] = 'Supprimer les widgets SQL';
$this->rights[$r][3] = 0;
$this->rights[$r][4] = 'delete';
```

### Boxes à déclarer

Déclarer **10 slots de boxes** génériques (chacun affichera un widget différent selon la config en base) :

```php
$this->boxes = array();
for ($i = 0; $i < 10; $i++) {
    $this->boxes[$i] = array(
        'file' => 'box_customwidget.php@customwidget',
        'note' => 'Widget SQL personnalisé - Slot '.($i+1),
        'enabledbydefaulton' => 'Home',
    );
}
```

### Hooks à déclarer

```php
$this->module_parts = array(
    'hooks' => array('index'),  // Hook sur la page d'accueil
);
```

### Menus à déclarer

```php
// Menu principal sous "Outils" ou menu top-level
$this->menu = array();
$r = 0;

// Entrée menu gauche
$this->menu[$r] = array(
    'fk_menu'  => 'fk_mainmenu=tools',
    'type'     => 'left',
    'titre'    => 'CustomWidgets',
    'prefix'   => img_picto('', 'customwidget@customwidget', 'class="paddingright"'),
    'mainmenu' => 'tools',
    'leftmenu' => 'customwidget',
    'url'      => '/customwidget/widget/list.php',
    'langs'    => 'customwidget@customwidget',
    'position' => 100,
    'enabled'  => 'isModEnabled("customwidget")',
    'perms'    => '$user->hasRight("customwidget", "read")',
    'target'   => '',
    'user'     => 0,
);
$r++;

$this->menu[$r] = array(
    'fk_menu'  => 'fk_mainmenu=tools,fk_leftmenu=customwidget',
    'type'     => 'left',
    'titre'    => 'Nouveau widget',
    'mainmenu' => 'tools',
    'leftmenu' => 'customwidget_new',
    'url'      => '/customwidget/widget/card.php?action=create',
    'langs'    => 'customwidget@customwidget',
    'position' => 101,
    'enabled'  => 'isModEnabled("customwidget")',
    'perms'    => '$user->hasRight("customwidget", "write")',
    'target'   => '',
    'user'     => 0,
);
```

---

## 5. CLASSE CRUD — `customwidget.class.php`

```
Emplacement : custom/customwidget/class/customwidget.class.php
```

### Responsabilités

- CRUD complet (create, fetch, fetchAll, update, delete)
- Gestion des groupes associés (addGroup, removeGroup, getGroups)
- Vérification d'accès (`userCanView($user)` → vérifie si l'utilisateur appartient à un groupe autorisé. Si aucun groupe n'est défini, le widget est visible par tous)
- Clonage d'un widget (`clone()`)

### Méthodes principales

```php
class CustomWidget extends CommonObject
{
    public $table_element = 'customwidget';
    public $element = 'customwidget';
    public $fk_element = 'fk_customwidget';
    
    // Champs mappés sur la table
    public $fields = array(
        'rowid'          => array('type' => 'integer', 'label' => 'TechnicalID', 'enabled' => 1, 'position' => 1, 'notnull' => 1, 'visible' => 0, 'index' => 1),
        'ref'            => array('type' => 'varchar(128)', 'label' => 'Ref', 'enabled' => 1, 'position' => 10, 'notnull' => 1, 'visible' => 4, 'index' => 1, 'searchall' => 1),
        'label'          => array('type' => 'varchar(255)', 'label' => 'Label', 'enabled' => 1, 'position' => 20, 'notnull' => 1, 'visible' => 1, 'searchall' => 1),
        'widget_type'    => array('type' => 'varchar(20)', 'label' => 'Type', 'enabled' => 1, 'position' => 30, 'notnull' => 1, 'visible' => 1),
        'sql_query'      => array('type' => 'text', 'label' => 'SQLQuery', 'enabled' => 1, 'position' => 40, 'notnull' => 1, 'visible' => 3),
        'active'         => array('type' => 'integer', 'label' => 'Active', 'enabled' => 1, 'position' => 200, 'notnull' => 1, 'visible' => 1, 'default' => 1),
        // ... tous les autres champs
    );

    /**
     * Récupère tous les widgets actifs visibles par l'utilisateur
     * @param User $user       Utilisateur courant
     * @param string $type     Filtrer par type ('number','table','chart' ou '' pour tous)
     * @param string $zone     Filtrer par zone ('box','stats' ou '' pour tous)
     * @return array           Tableau d'objets CustomWidget
     */
    public function fetchAllForUser($user, $type = '', $zone = '') { }
    
    /**
     * Vérifie si l'utilisateur peut voir ce widget
     * Règle : si aucun groupe n'est associé, visible par tous.
     *         Sinon, l'utilisateur doit appartenir à au moins un groupe associé.
     */
    public function userCanView($user) { }
    
    /**
     * Associer un groupe utilisateur au widget
     */
    public function addGroup($fk_usergroup) { }
    
    /**
     * Retirer un groupe utilisateur du widget
     */
    public function removeGroup($fk_usergroup) { }
    
    /**
     * Récupérer les IDs des groupes associés
     * @return array  Liste des fk_usergroup
     */
    public function getGroups() { }
}
```

---

## 6. HELPER — `customwidget.helper.class.php`

```
Emplacement : custom/customwidget/class/customwidget.helper.class.php
```

### Responsabilités

Toute la logique d'exécution et de formatage est ici, séparée de la classe CRUD.

```php
class CustomWidgetHelper
{
    /**
     * Valide une requête SQL : doit commencer par SELECT, interdit INSERT/UPDATE/DELETE/DROP/ALTER/TRUNCATE/EXEC/GRANT/REVOKE
     * Remplace __PREFIX__ par MAIN_DB_PREFIX
     * @param string $sql   Requête brute
     * @return array        ['valid' => bool, 'error' => string, 'sql' => string (nettoyée)]
     */
    public static function validateQuery($sql) { }
    
    /**
     * Exécute une requête SELECT validée et retourne les résultats
     * @param DoliDB $db    Instance base de données
     * @param string $sql   Requête validée
     * @param int $maxrows  Limite de lignes (défaut 100, max 500)
     * @return array        ['columns' => [...], 'rows' => [...], 'num_rows' => int, 'error' => string]
     */
    public static function executeQuery($db, $sql, $maxrows = 100) { }
    
    /**
     * Formate une valeur selon son type pour affichage
     * @param mixed $value    Valeur brute
     * @param string $type    'text', 'integer', 'price', 'percentage', 'date', 'status'
     * @param Translate $langs Instance langue
     * @return string          Valeur formatée HTML
     */
    public static function formatValue($value, $type, $langs) { }
    
    /**
     * Génère le HTML pour un widget de type "number" (KPI)
     * @param CustomWidget $widget  Objet widget
     * @param DoliDB $db            Instance BDD
     * @param Translate $langs      Instance langue
     * @return string               HTML du KPI
     */
    public static function renderNumber($widget, $db, $langs) { }
    
    /**
     * Génère le HTML pour un widget de type "table"
     * @param CustomWidget $widget  Objet widget
     * @param DoliDB $db            Instance BDD
     * @param Translate $langs      Instance langue
     * @return string               HTML du tableau
     */
    public static function renderTable($widget, $db, $langs) { }
    
    /**
     * Génère le HTML/JS pour un widget de type "chart" (Chart.js)
     * @param CustomWidget $widget  Objet widget
     * @param DoliDB $db            Instance BDD
     * @param Translate $langs      Instance langue
     * @return string               HTML + JS du graphique
     */
    public static function renderChart($widget, $db, $langs) { }
    
    /**
     * Gestion du cache : lit/écrit le cache dans /documents/customwidget/cache/
     * @param string $key       Clé de cache (ex: "widget_42")
     * @param int $duration     Durée en secondes
     * @param callable $callback Fonction à exécuter si cache expiré
     * @return string           Contenu HTML (depuis cache ou fraîchement généré)
     */
    public static function cached($key, $duration, $callback) { }
}
```

---

## 7. BOXES — `box_customwidget.php`

```
Emplacement : custom/customwidget/core/boxes/box_customwidget.php
```

### Principe

Une seule classe box, instanciée N fois (10 slots déclarés dans le descripteur). Chaque instance détecte son numéro de slot et affiche le widget correspondant (par ordre de `position` en base).

### Logique

```php
class box_customwidget extends ModeleBoxes
{
    public $boxcode = "customwidget";
    public $boximg = "customwidget@customwidget";
    public $boxlabel = "SQL Widget";
    public $depends = array('customwidget');
    
    /**
     * loadBox() :
     * 1. Récupérer le numéro de slot de cette instance (via $this->box_order ou $this->box_id)
     * 2. Charger les widgets actifs de type 'box' visibles par $user, triés par position
     * 3. Si un widget correspond à ce slot → appeler le render approprié (Helper::renderXxx)
     * 4. Si pas de widget pour ce slot → boîte vide/masquée
     */
    public function loadBox($max = 5, $cachedelay = 0) { }
    
    /**
     * showBox() :
     * Affiche le contenu HTML généré par loadBox
     * Pour les charts : inclure Chart.js si pas déjà chargé
     */
    public function showBox($head = null, $contents = null, $nooutput = 0) { }
}
```

**IMPORTANT** — Mécanisme de mapping slot ↔ widget :
- Lors du `loadBox`, la box récupère tous les widgets actifs zone='box' visibles par l'utilisateur
- Elle se base sur son index d'instance (0 à 9) pour choisir le widget à afficher
- Le widget d'index N dans la liste triée par `position` est affiché dans le slot N
- Si N widgets < nombre de slots, les slots excédentaires restent vides

---

## 8. HOOK ZONE STATS — `customwidget.class.php` (hooks)

```
Emplacement : custom/customwidget/core/hooks/customwidget.class.php
```

### Objectif

Injecter les widgets de type `number` avec `display_zone = 'stats'` directement dans la zone KPI native de la page d'accueil Dolibarr (au-dessus des boxes).

```php
class ActionsCustomWidget
{
    /**
     * Hook : addStatisticLine
     * Contexte : 'index' (page d'accueil)
     * 
     * Charge tous les widgets type='number', zone='stats', actifs, visibles par $user
     * Pour chacun : exécuter la requête, formater en HTML similaire aux KPI natifs Dolibarr
     * Injecter via $this->resprints
     */
    public function addStatisticLine($parameters, &$object, &$action, $hookmanager) { }
}
```

**Note** : vérifier quel hook exact est disponible sur la page index.php de Dolibarr 20.0. Les hooks possibles sont `index`, `mainloginpage`, ou `addStatisticLine`. Tester en priorité le hook `addMoreBoxStatsCustom` ou `addStatisticLine` sur la page d'accueil. Si aucun hook adapté n'existe pour la zone stats, rabattre tous les widgets sur le mode `box`.

---

## 9. PAGES ADMINISTRATION

### 9.1 `admin/setup.php`

Configuration générale du module :
- **CUSTOMWIDGET_MAX_ROWS** : nombre max de lignes par défaut pour les tables (défaut: 10)
- **CUSTOMWIDGET_CACHE_DEFAULT** : durée de cache par défaut en secondes (défaut: 300)
- **CUSTOMWIDGET_ALLOW_JOIN** : autoriser les JOIN dans les requêtes (1/0, défaut: 1)
- **CUSTOMWIDGET_MAX_SLOTS** : nombre de slots box actifs (défaut: 10, max: 20)
- **CUSTOMWIDGET_CHARTJS_CDN** : URL CDN Chart.js ou vide pour utiliser la copie locale

### 9.2 `admin/about.php`

Page à propos : version, auteur, licence.

---

## 10. PAGES CRUD WIDGET

### 10.1 `widget/list.php`

Liste de tous les widgets avec :
- Colonnes : Réf, Label, Type (badge coloré), Zone, Actif (toggle), Position, Groupes, Actions
- Filtres par type et zone
- Actions : éditer, dupliquer, supprimer, activer/désactiver
- Drag & drop pour réordonner (appel AJAX `ajax/reorder.php`)

### 10.2 `widget/card.php`

Fiche création/édition d'un widget. Interface en **onglets** ou **sections dynamiques** selon le type sélectionné :

#### Section commune (toujours visible)
- **Référence** : auto-générée ou manuelle
- **Label** : titre du widget
- **Description** : textarea optionnel
- **Type** : select → `number`, `table`, `chart` (recharge les sections dynamiques)
- **Requête SQL** : textarea avec coloration syntaxique basique (monospace). Placeholder : `Utilisez __PREFIX__ pour le préfixe des tables (ex: __PREFIX__societe)`
- **Bouton "Tester la requête"** : appel AJAX → affiche résultat dans un div preview
- **Zone d'affichage** : `box` (boîtes dashboard) ou `stats` (zone KPI, uniquement pour type `number`)
- **Ordre** : entier
- **Actif** : oui/non
- **Durée cache** : en secondes
- **Groupes autorisés** : multiselect des groupes Dolibarr existants (vide = visible par tous)

#### Section "Number" (visible si type = number)
- Icône : champ texte (fa-xxx ou emoji)
- Couleur : input color picker
- Suffixe : texte libre (€, %, unités)
- URL clic : URL vers une page Dolibarr
- Sous-indicateur 1 : label + requête SQL
- Sous-indicateur 2 : label + requête SQL

#### Section "Table" (visible si type = table)
- Nombre max de lignes
- Configuration colonnes : tableau dynamique (ajouter/supprimer lignes)
  - Nom colonne SQL (correspond au nom dans le SELECT)
  - Label affiché
  - Type : `text`, `integer`, `price`, `percentage`, `date`, `status`
  - Lien : URL pattern avec `{value}` et `{rowid}` comme placeholders (ex: `/commmande/card.php?id={rowid}`)

#### Section "Chart" (visible si type = chart)
- Type de graphique : `bar`, `line`, `doughnut`, `pie`
- Hauteur : pixels
- Colonne labels (index dans le résultat SQL, défaut 0)
- Colonne données (index dans le résultat SQL, défaut 1)
- Palette couleurs : champs couleur multiples (ajouter/supprimer)

#### Preview
- Zone de prévisualisation en bas de page
- Bouton "Prévisualiser le widget" → appel AJAX `ajax/preview.php`
- Affiche le rendu réel du widget avec les données en cours

---

## 11. ENDPOINTS AJAX

### 11.1 `ajax/testquery.php`

```
Méthode : POST
Paramètres : sql (requête), maxrows (optionnel)
Retour : JSON {success, columns, rows, num_rows, error, execution_time}
Sécurité : permission customwidget->write, validation SQL
CSRF : définir NOCSRFCHECK avant main.inc.php
```

**Logique** :
1. Valider la requête via `CustomWidgetHelper::validateQuery()`
2. Exécuter via `CustomWidgetHelper::executeQuery()` avec limite à 20 lignes pour le test
3. Mesurer le temps d'exécution
4. Retourner résultat JSON

### 11.2 `ajax/preview.php`

```
Méthode : POST
Paramètres : tous les champs du formulaire card.php
Retour : JSON {success, html, error}
Sécurité : permission customwidget->write
```

**Logique** :
1. Construire un objet CustomWidget temporaire avec les données POST
2. Appeler le render approprié (Helper::renderNumber/Table/Chart)
3. Retourner le HTML généré

### 11.3 `ajax/reorder.php`

```
Méthode : POST
Paramètres : order (JSON array d'IDs dans le nouvel ordre)
Retour : JSON {success, error}
Sécurité : permission customwidget->write
```

---

## 12. SÉCURITÉ SQL

### Règles de validation (dans `CustomWidgetHelper::validateQuery()`)

```
1. Trim + suppression des commentaires SQL (-- et /* */)
2. La requête DOIT commencer par SELECT (après trim, case-insensitive)
3. Mots-clés INTERDITS (recherche case-insensitive, word boundary) :
   INSERT, UPDATE, DELETE, DROP, ALTER, TRUNCATE, CREATE, EXEC, EXECUTE,
   GRANT, REVOKE, RENAME, REPLACE INTO, LOAD, OUTFILE, DUMPFILE, INTO
4. Remplacement de __PREFIX__ par la valeur de MAIN_DB_PREFIX
5. Si CUSTOMWIDGET_ALLOW_JOIN est désactivé, interdire le mot JOIN
6. Ajout automatique de LIMIT si absent (LIMIT à table_maxrows ou 500 max)
```

### Protection supplémentaire

- Les requêtes sont exécutées avec le même utilisateur MySQL que Dolibarr (pas d'escalade de privilèges possible)
- Les résultats sont échappés HTML (`htmlspecialchars`) avant affichage
- Temps d'exécution limité (timeout PHP si possible)

---

## 13. CACHE

### Mécanisme

Les résultats HTML des widgets sont mis en cache dans des fichiers :
```
/home/diamanti/www/doli/documents/customwidget/cache/widget_{ID}.html
```

**Logique** :
1. Si fichier cache existe ET âge < `cache_duration` → lire et retourner
2. Sinon → exécuter la requête, générer le HTML, écrire le fichier cache, retourner
3. Cache ignoré si `cache_duration = 0`
4. Le cache est purgé quand un widget est modifié (dans `update()` de la classe CRUD)

---

## 14. CHART.JS

### Intégration

Chart.js doit être inclus une seule fois sur la page. Deux stratégies :
1. **CDN** : `<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>` (si accès internet)
2. **Local** : Télécharger `chart.min.js` dans `custom/customwidget/js/chart.min.js`

**Recommandation** : stocker en local (pas de dépendance réseau sur le serveur OVH). Récupérer la version 4.x stable.

### Rendu Chart

Chaque widget chart génère :
```html
<canvas id="customwidget_chart_{ID}" height="{chart_height}"></canvas>
<script>
// Vérifier que Chart.js est chargé
if (typeof Chart !== 'undefined') {
    new Chart(document.getElementById('customwidget_chart_{ID}'), {
        type: '{chart_type}',
        data: {
            labels: [/* depuis résultat SQL colonne chart_label_col */],
            datasets: [{
                data: [/* depuis résultat SQL colonne chart_data_col */],
                backgroundColor: [/* depuis chart_colors JSON */]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: true } }
        }
    });
}
</script>
```

---

## 15. STYLES CSS — `customwidget.css`

### KPI Numbers (zone stats)

S'inspirer du style natif Dolibarr pour les statistiques de la page d'accueil :
```css
.customwidget-kpi {
    display: inline-block;
    text-align: center;
    padding: 10px 15px;
    margin: 5px;
    border-radius: 5px;
    background: #fff;
    box-shadow: 0 1px 3px rgba(0,0,0,0.12);
    min-width: 150px;
    cursor: pointer;
    transition: box-shadow 0.2s;
}
.customwidget-kpi:hover { box-shadow: 0 3px 8px rgba(0,0,0,0.2); }
.customwidget-kpi .cw-value { font-size: 2em; font-weight: bold; }
.customwidget-kpi .cw-label { font-size: 0.85em; color: #666; margin-top: 4px; }
.customwidget-kpi .cw-sub { font-size: 0.75em; color: #999; }
```

### Tables

Utiliser les classes Dolibarr natives (`liste_titre`, `oddeven`, etc.) pour s'intégrer au thème.

### Charts

Pas de style particulier, Chart.js gère son rendu dans le canvas.

---

## 16. FICHIERS LANG

### `langs/fr_FR/customwidget.lang`

```
Module500200Name = Widgets SQL
Module500200Desc = Widgets personnalisés alimentés par des requêtes SQL sur le tableau de bord

CustomWidgetSetup = Configuration des widgets SQL
CustomWidgetAbout = À propos
CustomWidgetList = Liste des widgets
CustomWidgetNew = Nouveau widget
CustomWidgetCard = Fiche widget

WidgetType = Type de widget
WidgetTypeNumber = Nombre (KPI)
WidgetTypeTable = Tableau
WidgetTypeChart = Graphique
WidgetLabel = Libellé
WidgetSqlQuery = Requête SQL
WidgetDisplayZone = Zone d'affichage
WidgetZoneBox = Boîte dashboard
WidgetZoneStats = Zone statistiques
WidgetActive = Actif
WidgetPosition = Ordre
WidgetCacheDuration = Durée cache (secondes)
WidgetGroups = Groupes autorisés
WidgetGroupsHelp = Laisser vide pour rendre visible à tous les utilisateurs

WidgetTestQuery = Tester la requête
WidgetPreview = Prévisualiser
WidgetQueryResult = Résultat de la requête
WidgetQueryError = Erreur dans la requête
WidgetQueryTime = Temps d'exécution
WidgetQueryRows = lignes retournées
WidgetQueryForbidden = Requête interdite : seules les requêtes SELECT sont autorisées
WidgetQueryPrefixHelp = Utilisez __PREFIX__ pour le préfixe des tables (ex: __PREFIX__societe)

WidgetNumberIcon = Icône
WidgetNumberColor = Couleur
WidgetNumberSuffix = Suffixe
WidgetNumberUrl = URL au clic
WidgetNumberSub1 = Sous-indicateur 1
WidgetNumberSub2 = Sous-indicateur 2

WidgetTableColumns = Configuration des colonnes
WidgetTableMaxRows = Nombre max de lignes
WidgetTableColName = Nom colonne SQL
WidgetTableColLabel = Libellé affiché
WidgetTableColType = Type
WidgetTableColLink = Lien (optionnel)

WidgetChartType = Type de graphique
WidgetChartBar = Barres
WidgetChartLine = Lignes
WidgetChartDoughnut = Anneau
WidgetChartPie = Camembert
WidgetChartHeight = Hauteur (px)
WidgetChartColors = Palette de couleurs
WidgetChartLabelCol = Colonne labels
WidgetChartDataCol = Colonne données

ConfirmDeleteWidget = Êtes-vous sûr de vouloir supprimer ce widget ?
WidgetCreated = Widget créé avec succès
WidgetUpdated = Widget modifié avec succès
WidgetDeleted = Widget supprimé
WidgetCloned = Widget dupliqué

CUSTOMWIDGET_MAX_ROWS = Nombre max de lignes par défaut
CUSTOMWIDGET_CACHE_DEFAULT = Durée de cache par défaut (sec.)
CUSTOMWIDGET_ALLOW_JOIN = Autoriser les JOIN dans les requêtes
CUSTOMWIDGET_CHARTJS_CDN = URL CDN Chart.js (vide = copie locale)
```

---

## 17. MÉTHODE D'INCLUSION DE `main.inc.php`

Toutes les pages PHP du module doivent commencer par :

```php
<?php
// Pour les fichiers ajax, ajouter ces 2 lignes AVANT l'include :
// $dolibarr_nocsrfcheck = 1;
// define('NOCSRFCHECK', 1);

$res = 0;
if (!$res && file_exists("../main.inc.php")) $res = @include "../main.inc.php";
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if (!$res) die("Include of main fails");
```

Adapter le nombre de niveaux `../` selon la profondeur du fichier dans l'arborescence.

---

## 18. EXEMPLES DE REQUÊTES PRÊTES À L'EMPLOI

Fournir dans la page d'admin ou dans un onglet "Exemples" quelques requêtes types :

### KPI : Nombre de commandes ce mois
```sql
SELECT COUNT(*) as total FROM __PREFIX__commande 
WHERE date_commande >= DATE_FORMAT(NOW(), '%Y-%m-01') 
AND fk_statut > 0
```

### KPI : CA facturé ce mois
```sql
SELECT SUM(total_ttc) as total FROM __PREFIX__facture 
WHERE datef >= DATE_FORMAT(NOW(), '%Y-%m-01') 
AND fk_statut = 1
```

### Table : 10 dernières commandes
```sql
SELECT c.ref, s.nom as client, c.date_commande, c.total_ttc, c.fk_statut 
FROM __PREFIX__commande c 
LEFT JOIN __PREFIX__societe s ON c.fk_soc = s.rowid 
WHERE c.fk_statut > 0 
ORDER BY c.date_commande DESC 
LIMIT 10
```

### Chart : CA mensuel sur 12 mois
```sql
SELECT DATE_FORMAT(datef, '%Y-%m') as mois, SUM(total_ht) as ca 
FROM __PREFIX__facture 
WHERE datef >= DATE_SUB(NOW(), INTERVAL 12 MONTH) 
AND fk_statut = 1 
GROUP BY DATE_FORMAT(datef, '%Y-%m') 
ORDER BY mois
```

### Chart : Répartition par statut de commande
```sql
SELECT 
  CASE fk_statut 
    WHEN 0 THEN 'Brouillon' 
    WHEN 1 THEN 'Validée' 
    WHEN 2 THEN 'Envoyée' 
    WHEN 3 THEN 'Facturée' 
  END as statut,
  COUNT(*) as nombre 
FROM __PREFIX__commande 
GROUP BY fk_statut
```

---

## 19. ORDRE DE DÉVELOPPEMENT RECOMMANDÉ

### Phase 1 — Fondations
1. `sql/` — Créer les fichiers SQL (tables + index)
2. `core/modules/modCustomWidget.class.php` — Descripteur complet
3. `class/customwidget.class.php` — Classe CRUD
4. `lib/customwidget.lib.php` — Fonctions helper (onglets admin)
5. `langs/` — Fichiers de traduction

### Phase 2 — Administration
6. `admin/setup.php` — Page configuration
7. `admin/about.php` — Page à propos
8. `widget/list.php` — Liste des widgets
9. `widget/card.php` — Formulaire création/édition (HTML statique d'abord)

### Phase 3 — Logique métier
10. `class/customwidget.helper.class.php` — Validation SQL, exécution, formatage
11. `ajax/testquery.php` — Test de requête en AJAX
12. `ajax/preview.php` — Prévisualisation

### Phase 4 — Affichage dashboard
13. `core/boxes/box_customwidget.php` — Classe box générique
14. `core/hooks/customwidget.class.php` — Hook zone stats (si hook disponible)
15. `css/customwidget.css` — Styles
16. `js/customwidget.js` — JavaScript (UI dynamique card.php, Chart.js)
17. `js/chart.min.js` — Librairie Chart.js locale

### Phase 5 — Finitions
18. `ajax/reorder.php` — Réordonnement drag & drop
19. `widget/clone.php` — Duplication
20. Tests complets, correction de bugs
21. `img/object_customwidget.png` — Icône

---

## 20. POINTS D'ATTENTION DOLIBARR

### Rappels critiques
- **`CREATE INDEX IF NOT EXISTS` non supporté** → utiliser `ALTER TABLE ADD INDEX`
- **Hooks** : la classe doit s'appeler `ActionsCustomWidget` dans le fichier `customwidget.class.php` dans `/core/hooks/`
- **Boxes** : le fichier box doit être dans `/core/boxes/` et référencé avec `@customwidget` dans le descripteur
- **CSRF** : pour les endpoints AJAX, toujours `$dolibarr_nocsrfcheck = 1; define('NOCSRFCHECK', 1);` **avant** l'include de main.inc.php
- **FormData AJAX** : privilégier FormData plutôt que JSON pur pour les POST, le token CSRF est géré automatiquement
- **Double-chargement JS** : protéger avec un flag global `var customwidget_loaded = customwidget_loaded || false;`
- **`isModEnabled('customwidget')`** pour vérifier si le module est actif
- **`$user->hasRight('customwidget', 'read')`** pour vérifier les permissions
- **Chemins** : utiliser `dol_buildpath('/customwidget/...', 1)` pour les URLs, `dol_buildpath('/customwidget/...', 0)` pour les chemins fichiers
- **Préfixe table** : dans les requêtes utilisateur, `__PREFIX__` est un placeholder remplacé à l'exécution par la valeur de `MAIN_DB_PREFIX`
- **Ne jamais utiliser `let/const` au top-level** dans les JS injectés dans Dolibarr → utiliser `var`
