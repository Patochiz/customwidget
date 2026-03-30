<?php
/**
 * Descripteur du module CustomWidget
 * Widgets SQL personnalisés pour Dolibarr
 */

include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';

class modCustomWidget extends DolibarrModules
{
    public function __construct($db)
    {
        global $langs, $conf;

        $this->db = $db;

        // Module identifier
        $this->numero = 680200;
        $this->rights_class = 'customwidget';
        $this->family = 'other';
        $this->module_position = 90;
        $this->name = preg_replace('/^mod/i', '', get_class($this));
        $this->description = 'Widgets SQL personnalisés pour le dashboard';
        $this->longdescription = 'Permet de créer des widgets personnalisés sur le tableau de bord, alimentés par des requêtes SQL SELECT définies par l\'administrateur.';
        $this->version = '1.0.0';
        $this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
        $this->picto = 'customwidget@customwidget';

        // Module parts
        $this->module_parts = array(
            'hooks' => array('index'),
            'css'   => array('/customwidget/css/customwidget.css'),
        );

        // Dependencies
        $this->depends = array();
        $this->requiredby = array();
        $this->conflictwith = array();

        // Language files
        $this->langfiles = array('customwidget@customwidget');

        // Config page
        $this->config_page_url = array('setup.php@customwidget');

        // SQL tables
        $this->tabs = array();
        $this->dictionaries = array();

        // Tables to create
        $this->tables = array(
            'customwidget',
            'customwidget_usergroup',
        );

        // Constants
        $this->const = array(
            0 => array('CUSTOMWIDGET_MAX_ROWS', 'chaine', '10', 'Nombre max de lignes par défaut', 0, 'allentities', 1),
            1 => array('CUSTOMWIDGET_CACHE_DEFAULT', 'chaine', '300', 'Durée de cache par défaut (secondes)', 0, 'allentities', 1),
            2 => array('CUSTOMWIDGET_ALLOW_JOIN', 'chaine', '1', 'Autoriser les JOIN dans les requêtes', 0, 'allentities', 1),
            3 => array('CUSTOMWIDGET_MAX_SLOTS', 'chaine', '10', 'Nombre de slots box actifs', 0, 'allentities', 1),
            4 => array('CUSTOMWIDGET_CHARTJS_CDN', 'chaine', '', 'URL CDN Chart.js (vide = copie locale)', 0, 'allentities', 1),
        );

        // Permissions
        $this->rights = array();
        $r = 0;

        $this->rights[$r][0] = $this->numero + $r + 1; // 680201
        $this->rights[$r][1] = 'Voir les widgets SQL';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'read';
        $r++;

        $this->rights[$r][0] = $this->numero + $r + 1; // 680202
        $this->rights[$r][1] = 'Créer/modifier les widgets SQL';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'write';
        $r++;

        $this->rights[$r][0] = $this->numero + $r + 1; // 680203
        $this->rights[$r][1] = 'Supprimer les widgets SQL';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'delete';

        // Pas de box statique. Chaque widget a sa propre entrée llx_boxes_def
        // créée dynamiquement par CustomWidget::create() / registerBoxDef()
        $this->boxes = array();

        // Menus
        $this->menu = array();
        $r = 0;

        $this->menu[$r] = array(
            'fk_menu'  => 'fk_mainmenu=tools',
            'type'     => 'left',
            'titre'    => 'CustomWidgets',
            'prefix'   => img_picto('', 'customwidget@customwidget', 'class="paddingright pictofixedwidth"'),
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
        $r++;

        $this->menu[$r] = array(
            'fk_menu'  => 'fk_mainmenu=home',
            'type'     => 'left',
            'titre'    => 'CustomWidgetDashboard',
            'prefix'   => img_picto('', 'customwidget@customwidget', 'class="paddingright pictofixedwidth"'),
            'mainmenu' => 'home',
            'leftmenu' => 'customwidget_dashboard',
            'url'      => '/customwidget/widget/dashboard.php',
            'langs'    => 'customwidget@customwidget',
            'position' => 1,
            'enabled'  => 'isModEnabled("customwidget")',
            'perms'    => '$user->hasRight("customwidget", "read")',
            'target'   => '',
            'user'     => 0,
        );
    }

    /**
     * Function called when module is enabled.
     */
    public function init($options = '')
    {
        global $conf;

        $this->_load_tables('/customwidget/sql/');
        $sql = array();
        $result = $this->_init($sql, $options);

        // Migration : supprimer l'ancienne box unique "conteneur"
        // (celle qui n'a pas de note au format 'cw_XX')
        $this->db->query(
            "DELETE FROM ".MAIN_DB_PREFIX."boxes WHERE box_id IN ("
            ."SELECT rowid FROM ".MAIN_DB_PREFIX."boxes_def"
            ." WHERE file = 'box_customwidget.php@customwidget'"
            ." AND (note IS NULL OR note NOT LIKE 'cw\\_%')"
            .")"
        );
        $this->db->query(
            "DELETE FROM ".MAIN_DB_PREFIX."boxes_def"
            ." WHERE file = 'box_customwidget.php@customwidget'"
            ." AND (note IS NULL OR note NOT LIKE 'cw\\_%')"
        );

        // Enregistrer chaque widget actif existant dans llx_boxes_def
        $resql = $this->db->query(
            "SELECT rowid FROM ".MAIN_DB_PREFIX."customwidget"
            ." WHERE entity = ".(int) $conf->entity." AND active = 1"
        );
        if ($resql) {
            require_once dol_buildpath('/customwidget/class/customwidget.class.php', 0);
            while ($obj = $this->db->fetch_object($resql)) {
                $w = new CustomWidget($this->db);
                $w->fetch($obj->rowid);
                $w->registerBoxDef();
            }
        }

        return $result;
    }

    /**
     * Function called when module is disabled.
     */
    public function remove($options = '')
    {
        // Supprimer toutes les activations utilisateur
        $this->db->query(
            "DELETE FROM ".MAIN_DB_PREFIX."boxes WHERE box_id IN ("
            ."SELECT rowid FROM ".MAIN_DB_PREFIX."boxes_def"
            ." WHERE file = 'box_customwidget.php@customwidget'"
            .")"
        );
        // Supprimer toutes les définitions de boxes
        $this->db->query(
            "DELETE FROM ".MAIN_DB_PREFIX."boxes_def"
            ." WHERE file = 'box_customwidget.php@customwidget'"
        );

        $sql = array();
        return $this->_remove($sql, $options);
    }
}
