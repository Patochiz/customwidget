<?php
/**
 * Classe CRUD pour les widgets SQL personnalisés
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';

class CustomWidget extends CommonObject
{
    public $module = 'customwidget';
    public $element = 'customwidget';
    public $table_element = 'customwidget';
    public $fk_element = 'fk_customwidget';
    public $picto = 'customwidget@customwidget';

    // Champs mappés
    public $fields = array(
        'rowid'           => array('type' => 'integer', 'label' => 'TechnicalID', 'enabled' => 1, 'position' => 1, 'notnull' => 1, 'visible' => 0, 'index' => 1),
        'ref'             => array('type' => 'varchar(128)', 'label' => 'Ref', 'enabled' => 1, 'position' => 10, 'notnull' => 1, 'visible' => 4, 'index' => 1, 'searchall' => 1),
        'label'           => array('type' => 'varchar(255)', 'label' => 'Label', 'enabled' => 1, 'position' => 20, 'notnull' => 1, 'visible' => 1, 'searchall' => 1),
        'description'     => array('type' => 'text', 'label' => 'Description', 'enabled' => 1, 'position' => 25, 'notnull' => 0, 'visible' => 3),
        'widget_type'     => array('type' => 'varchar(20)', 'label' => 'Type', 'enabled' => 1, 'position' => 30, 'notnull' => 1, 'visible' => 1, 'default' => 'number'),
        'sql_query'       => array('type' => 'text', 'label' => 'SQLQuery', 'enabled' => 1, 'position' => 40, 'notnull' => 1, 'visible' => 3),
        'number_icon'     => array('type' => 'varchar(128)', 'label' => 'NumberIcon', 'enabled' => 1, 'position' => 50, 'notnull' => 0, 'visible' => 0),
        'number_color'    => array('type' => 'varchar(7)', 'label' => 'NumberColor', 'enabled' => 1, 'position' => 51, 'notnull' => 0, 'visible' => 0, 'default' => '#0077b6'),
        'number_suffix'   => array('type' => 'varchar(20)', 'label' => 'NumberSuffix', 'enabled' => 1, 'position' => 52, 'notnull' => 0, 'visible' => 0),
        'number_sub1_sql' => array('type' => 'text', 'label' => 'NumberSub1SQL', 'enabled' => 1, 'position' => 53, 'notnull' => 0, 'visible' => 0),
        'number_sub1_label' => array('type' => 'varchar(128)', 'label' => 'NumberSub1Label', 'enabled' => 1, 'position' => 54, 'notnull' => 0, 'visible' => 0),
        'number_sub2_sql' => array('type' => 'text', 'label' => 'NumberSub2SQL', 'enabled' => 1, 'position' => 55, 'notnull' => 0, 'visible' => 0),
        'number_sub2_label' => array('type' => 'varchar(128)', 'label' => 'NumberSub2Label', 'enabled' => 1, 'position' => 56, 'notnull' => 0, 'visible' => 0),
        'number_url'      => array('type' => 'varchar(255)', 'label' => 'NumberUrl', 'enabled' => 1, 'position' => 57, 'notnull' => 0, 'visible' => 0),
        'table_columns'   => array('type' => 'text', 'label' => 'TableColumns', 'enabled' => 1, 'position' => 60, 'notnull' => 0, 'visible' => 0),
        'table_maxrows'   => array('type' => 'integer', 'label' => 'TableMaxRows', 'enabled' => 1, 'position' => 61, 'notnull' => 0, 'visible' => 0, 'default' => 10),
        'chart_type'      => array('type' => 'varchar(20)', 'label' => 'ChartType', 'enabled' => 1, 'position' => 70, 'notnull' => 0, 'visible' => 0, 'default' => 'bar'),
        'chart_colors'    => array('type' => 'text', 'label' => 'ChartColors', 'enabled' => 1, 'position' => 71, 'notnull' => 0, 'visible' => 0),
        'chart_height'    => array('type' => 'integer', 'label' => 'ChartHeight', 'enabled' => 1, 'position' => 72, 'notnull' => 0, 'visible' => 0, 'default' => 300),
        'chart_label_col' => array('type' => 'integer', 'label' => 'ChartLabelCol', 'enabled' => 1, 'position' => 73, 'notnull' => 0, 'visible' => 0, 'default' => 0),
        'chart_data_col'  => array('type' => 'integer', 'label' => 'ChartDataCol', 'enabled' => 1, 'position' => 74, 'notnull' => 0, 'visible' => 0, 'default' => 1),
        'display_zone'    => array('type' => 'varchar(20)', 'label' => 'DisplayZone', 'enabled' => 1, 'position' => 80, 'notnull' => 0, 'visible' => 1, 'default' => 'box'),
        'position'        => array('type' => 'integer', 'label' => 'Position', 'enabled' => 1, 'position' => 90, 'notnull' => 0, 'visible' => 1, 'default' => 0),
        'active'          => array('type' => 'integer', 'label' => 'Active', 'enabled' => 1, 'position' => 100, 'notnull' => 1, 'visible' => 1, 'default' => 1),
        'cache_duration'  => array('type' => 'integer', 'label' => 'CacheDuration', 'enabled' => 1, 'position' => 110, 'notnull' => 0, 'visible' => 0, 'default' => 300),
        'entity'          => array('type' => 'integer', 'label' => 'Entity', 'enabled' => 1, 'position' => 190, 'notnull' => 1, 'visible' => 0, 'default' => 1),
        'fk_user_creat'   => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'UserAuthor', 'enabled' => 1, 'position' => 191, 'notnull' => 0, 'visible' => 0),
        'fk_user_modif'   => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'UserModif', 'enabled' => 1, 'position' => 192, 'notnull' => 0, 'visible' => 0),
        'date_creation'   => array('type' => 'datetime', 'label' => 'DateCreation', 'enabled' => 1, 'position' => 195, 'notnull' => 0, 'visible' => 0),
        'tms'             => array('type' => 'timestamp', 'label' => 'DateModif', 'enabled' => 1, 'position' => 196, 'notnull' => 0, 'visible' => 0),
    );

    // Propriétés objets
    public $rowid;
    public $ref;
    public $label;
    public $description;
    public $widget_type;
    public $sql_query;
    public $number_icon;
    public $number_color = '#0077b6';
    public $number_suffix;
    public $number_sub1_sql;
    public $number_sub1_label;
    public $number_sub2_sql;
    public $number_sub2_label;
    public $number_url;
    public $table_columns;
    public $table_maxrows = 10;
    public $chart_type = 'bar';
    public $chart_colors;
    public $chart_height = 300;
    public $chart_label_col = 0;
    public $chart_data_col = 1;
    public $display_zone = 'box';
    public $position = 0;
    public $active = 1;
    public $cache_duration = 300;
    public $entity;
    public $fk_user_creat;
    public $fk_user_modif;
    public $date_creation;
    public $tms;

    public function __construct($db)
    {
        global $conf;
        $this->db = $db;
        $this->entity = isset($conf->entity) ? $conf->entity : 1;
    }

    /**
     * Crée un nouveau widget en base
     */
    public function create($user, $notrigger = 0)
    {
        global $conf;

        if (empty($this->ref)) {
            $this->ref = 'WID-'.dol_print_date(dol_now(), 'dayhourlog');
        }

        $this->fk_user_creat = $user->id;
        $this->date_creation = $this->db->idate(dol_now());
        $this->entity = $conf->entity;

        $sql = "INSERT INTO ".MAIN_DB_PREFIX."customwidget (";
        $sql .= "ref, label, description, widget_type, sql_query,";
        $sql .= "number_icon, number_color, number_suffix, number_sub1_sql, number_sub1_label,";
        $sql .= "number_sub2_sql, number_sub2_label, number_url,";
        $sql .= "table_columns, table_maxrows,";
        $sql .= "chart_type, chart_colors, chart_height, chart_label_col, chart_data_col,";
        $sql .= "display_zone, position, active, cache_duration,";
        $sql .= "entity, fk_user_creat, date_creation";
        $sql .= ") VALUES (";
        $sql .= "'".$this->db->escape($this->ref)."',";
        $sql .= "'".$this->db->escape($this->label)."',";
        $sql .= ($this->description ? "'".$this->db->escape($this->description)."'" : "NULL").",";
        $sql .= "'".$this->db->escape($this->widget_type)."',";
        $sql .= "'".$this->db->escape($this->sql_query)."',";
        $sql .= "'".$this->db->escape($this->number_icon)."',";
        $sql .= "'".$this->db->escape($this->number_color)."',";
        $sql .= "'".$this->db->escape($this->number_suffix)."',";
        $sql .= ($this->number_sub1_sql ? "'".$this->db->escape($this->number_sub1_sql)."'" : "NULL").",";
        $sql .= "'".$this->db->escape($this->number_sub1_label)."',";
        $sql .= ($this->number_sub2_sql ? "'".$this->db->escape($this->number_sub2_sql)."'" : "NULL").",";
        $sql .= "'".$this->db->escape($this->number_sub2_label)."',";
        $sql .= "'".$this->db->escape($this->number_url)."',";
        $sql .= ($this->table_columns ? "'".$this->db->escape($this->table_columns)."'" : "NULL").",";
        $sql .= (int) $this->table_maxrows.",";
        $sql .= "'".$this->db->escape($this->chart_type)."',";
        $sql .= ($this->chart_colors ? "'".$this->db->escape($this->chart_colors)."'" : "NULL").",";
        $sql .= (int) $this->chart_height.",";
        $sql .= (int) $this->chart_label_col.",";
        $sql .= (int) $this->chart_data_col.",";
        $sql .= "'".$this->db->escape($this->display_zone)."',";
        $sql .= (int) $this->position.",";
        $sql .= (int) $this->active.",";
        $sql .= (int) $this->cache_duration.",";
        $sql .= (int) $this->entity.",";
        $sql .= (int) $user->id.",";
        $sql .= "'".$this->db->idate(dol_now())."'";
        $sql .= ")";

        $this->db->begin();
        $resql = $this->db->query($sql);
        if ($resql) {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX.'customwidget');
            $this->db->commit();
            return $this->id;
        } else {
            $this->error = $this->db->lasterror();
            $this->db->rollback();
            return -1;
        }
    }

    /**
     * Charge un widget depuis la base
     */
    public function fetch($id, $ref = null)
    {
        global $conf;

        $sql = "SELECT * FROM ".MAIN_DB_PREFIX."customwidget WHERE ";
        if ($ref) {
            $sql .= "ref = '".$this->db->escape($ref)."' AND entity = ".(int) $conf->entity;
        } else {
            $sql .= "rowid = ".(int) $id;
        }

        $resql = $this->db->query($sql);
        if ($resql) {
            $obj = $this->db->fetch_object($resql);
            if ($obj) {
                $this->id = $obj->rowid;
                $this->ref = $obj->ref;
                $this->label = $obj->label;
                $this->description = $obj->description;
                $this->widget_type = $obj->widget_type;
                $this->sql_query = $obj->sql_query;
                $this->number_icon = $obj->number_icon;
                $this->number_color = $obj->number_color;
                $this->number_suffix = $obj->number_suffix;
                $this->number_sub1_sql = $obj->number_sub1_sql;
                $this->number_sub1_label = $obj->number_sub1_label;
                $this->number_sub2_sql = $obj->number_sub2_sql;
                $this->number_sub2_label = $obj->number_sub2_label;
                $this->number_url = $obj->number_url;
                $this->table_columns = $obj->table_columns;
                $this->table_maxrows = $obj->table_maxrows;
                $this->chart_type = $obj->chart_type;
                $this->chart_colors = $obj->chart_colors;
                $this->chart_height = $obj->chart_height;
                $this->chart_label_col = $obj->chart_label_col;
                $this->chart_data_col = $obj->chart_data_col;
                $this->display_zone = $obj->display_zone;
                $this->position = $obj->position;
                $this->active = $obj->active;
                $this->cache_duration = $obj->cache_duration;
                $this->entity = $obj->entity;
                $this->fk_user_creat = $obj->fk_user_creat;
                $this->fk_user_modif = $obj->fk_user_modif;
                $this->date_creation = $this->db->jdate($obj->date_creation);
                $this->tms = $this->db->jdate($obj->tms);
                return 1;
            }
            return 0;
        } else {
            $this->error = $this->db->lasterror();
            return -1;
        }
    }

    /**
     * Récupère tous les widgets (avec filtres optionnels)
     */
    public function fetchAll($type = '', $zone = '', $active_only = true)
    {
        global $conf;

        $sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."customwidget WHERE entity = ".(int) $conf->entity;
        if ($active_only) {
            $sql .= " AND active = 1";
        }
        if ($type) {
            $sql .= " AND widget_type = '".$this->db->escape($type)."'";
        }
        if ($zone) {
            $sql .= " AND display_zone = '".$this->db->escape($zone)."'";
        }
        $sql .= " ORDER BY position ASC, rowid ASC";

        $resql = $this->db->query($sql);
        if ($resql) {
            $list = array();
            while ($obj = $this->db->fetch_object($resql)) {
                $w = new CustomWidget($this->db);
                $w->fetch($obj->rowid);
                $list[] = $w;
            }
            return $list;
        } else {
            $this->error = $this->db->lasterror();
            return array();
        }
    }

    /**
     * Récupère les widgets actifs visibles par l'utilisateur
     */
    public function fetchAllForUser($user, $type = '', $zone = '')
    {
        $all = $this->fetchAll($type, $zone, true);
        $result = array();
        foreach ($all as $w) {
            if ($w->userCanView($user)) {
                $result[] = $w;
            }
        }
        return $result;
    }

    /**
     * Met à jour un widget
     */
    public function update($user, $notrigger = 0)
    {
        $this->fk_user_modif = $user->id;

        $sql = "UPDATE ".MAIN_DB_PREFIX."customwidget SET";
        $sql .= " ref = '".$this->db->escape($this->ref)."',";
        $sql .= " label = '".$this->db->escape($this->label)."',";
        $sql .= " description = ".($this->description ? "'".$this->db->escape($this->description)."'" : "NULL").",";
        $sql .= " widget_type = '".$this->db->escape($this->widget_type)."',";
        $sql .= " sql_query = '".$this->db->escape($this->sql_query)."',";
        $sql .= " number_icon = '".$this->db->escape($this->number_icon)."',";
        $sql .= " number_color = '".$this->db->escape($this->number_color)."',";
        $sql .= " number_suffix = '".$this->db->escape($this->number_suffix)."',";
        $sql .= " number_sub1_sql = ".($this->number_sub1_sql ? "'".$this->db->escape($this->number_sub1_sql)."'" : "NULL").",";
        $sql .= " number_sub1_label = '".$this->db->escape($this->number_sub1_label)."',";
        $sql .= " number_sub2_sql = ".($this->number_sub2_sql ? "'".$this->db->escape($this->number_sub2_sql)."'" : "NULL").",";
        $sql .= " number_sub2_label = '".$this->db->escape($this->number_sub2_label)."',";
        $sql .= " number_url = '".$this->db->escape($this->number_url)."',";
        $sql .= " table_columns = ".($this->table_columns ? "'".$this->db->escape($this->table_columns)."'" : "NULL").",";
        $sql .= " table_maxrows = ".(int) $this->table_maxrows.",";
        $sql .= " chart_type = '".$this->db->escape($this->chart_type)."',";
        $sql .= " chart_colors = ".($this->chart_colors ? "'".$this->db->escape($this->chart_colors)."'" : "NULL").",";
        $sql .= " chart_height = ".(int) $this->chart_height.",";
        $sql .= " chart_label_col = ".(int) $this->chart_label_col.",";
        $sql .= " chart_data_col = ".(int) $this->chart_data_col.",";
        $sql .= " display_zone = '".$this->db->escape($this->display_zone)."',";
        $sql .= " position = ".(int) $this->position.",";
        $sql .= " active = ".(int) $this->active.",";
        $sql .= " cache_duration = ".(int) $this->cache_duration.",";
        $sql .= " fk_user_modif = ".(int) $user->id;
        $sql .= " WHERE rowid = ".(int) $this->id;

        $this->db->begin();
        $resql = $this->db->query($sql);
        if ($resql) {
            // Purge le cache
            require_once __DIR__.'/customwidget.helper.class.php';
            CustomWidgetHelper::purgeCache($this->id);
            $this->db->commit();
            return 1;
        } else {
            $this->error = $this->db->lasterror();
            $this->db->rollback();
            return -1;
        }
    }

    /**
     * Supprime un widget
     */
    public function delete($user, $notrigger = 0)
    {
        $this->db->begin();

        // Supprimer les liaisons groupes
        $sql = "DELETE FROM ".MAIN_DB_PREFIX."customwidget_usergroup WHERE fk_customwidget = ".(int) $this->id;
        $this->db->query($sql);

        $sql = "DELETE FROM ".MAIN_DB_PREFIX."customwidget WHERE rowid = ".(int) $this->id;
        $resql = $this->db->query($sql);
        if ($resql) {
            require_once __DIR__.'/customwidget.helper.class.php';
            CustomWidgetHelper::purgeCache($this->id);
            $this->db->commit();
            return 1;
        } else {
            $this->error = $this->db->lasterror();
            $this->db->rollback();
            return -1;
        }
    }

    /**
     * Vérifie si l'utilisateur peut voir ce widget.
     * Si aucun groupe n'est associé, le widget est visible par tous.
     */
    public function userCanView($user)
    {
        if ($user->admin) {
            return true;
        }
        $groups = $this->getGroups();
        if (empty($groups)) {
            return true;
        }
        // Vérifier si l'utilisateur appartient à l'un des groupes
        $sql = "SELECT ug.fk_usergroup FROM ".MAIN_DB_PREFIX."usergroup_user ug";
        $sql .= " WHERE ug.fk_user = ".(int) $user->id;
        $sql .= " AND ug.fk_usergroup IN (".implode(',', array_map('intval', $groups)).")";
        $resql = $this->db->query($sql);
        if ($resql && $this->db->num_rows($resql) > 0) {
            return true;
        }
        return false;
    }

    /**
     * Associer un groupe utilisateur au widget
     */
    public function addGroup($fk_usergroup)
    {
        global $conf;
        $sql = "INSERT IGNORE INTO ".MAIN_DB_PREFIX."customwidget_usergroup (fk_customwidget, fk_usergroup, entity)";
        $sql .= " VALUES (".(int) $this->id.", ".(int) $fk_usergroup.", ".(int) $conf->entity.")";
        $resql = $this->db->query($sql);
        return $resql ? 1 : -1;
    }

    /**
     * Retirer un groupe utilisateur du widget
     */
    public function removeGroup($fk_usergroup)
    {
        $sql = "DELETE FROM ".MAIN_DB_PREFIX."customwidget_usergroup";
        $sql .= " WHERE fk_customwidget = ".(int) $this->id." AND fk_usergroup = ".(int) $fk_usergroup;
        $resql = $this->db->query($sql);
        return $resql ? 1 : -1;
    }

    /**
     * Récupérer les IDs des groupes associés
     */
    public function getGroups()
    {
        $sql = "SELECT fk_usergroup FROM ".MAIN_DB_PREFIX."customwidget_usergroup WHERE fk_customwidget = ".(int) $this->id;
        $resql = $this->db->query($sql);
        $groups = array();
        if ($resql) {
            while ($obj = $this->db->fetch_object($resql)) {
                $groups[] = (int) $obj->fk_usergroup;
            }
        }
        return $groups;
    }

    /**
     * Met à jour les groupes associés (remplace tous les groupes existants)
     */
    public function setGroups($group_ids)
    {
        $this->db->begin();

        $sql = "DELETE FROM ".MAIN_DB_PREFIX."customwidget_usergroup WHERE fk_customwidget = ".(int) $this->id;
        $this->db->query($sql);

        foreach ($group_ids as $gid) {
            $this->addGroup((int) $gid);
        }

        $this->db->commit();
        return 1;
    }

    /**
     * Clone un widget
     */
    public function createFromClone($user, $fromid)
    {
        $orig = new CustomWidget($this->db);
        $orig->fetch($fromid);

        $this->ref = 'CLONE-'.$orig->ref.'-'.dol_print_date(dol_now(), 'dayhourlog');
        $this->label = 'Copie de '.$orig->label;
        $this->description = $orig->description;
        $this->widget_type = $orig->widget_type;
        $this->sql_query = $orig->sql_query;
        $this->number_icon = $orig->number_icon;
        $this->number_color = $orig->number_color;
        $this->number_suffix = $orig->number_suffix;
        $this->number_sub1_sql = $orig->number_sub1_sql;
        $this->number_sub1_label = $orig->number_sub1_label;
        $this->number_sub2_sql = $orig->number_sub2_sql;
        $this->number_sub2_label = $orig->number_sub2_label;
        $this->number_url = $orig->number_url;
        $this->table_columns = $orig->table_columns;
        $this->table_maxrows = $orig->table_maxrows;
        $this->chart_type = $orig->chart_type;
        $this->chart_colors = $orig->chart_colors;
        $this->chart_height = $orig->chart_height;
        $this->chart_label_col = $orig->chart_label_col;
        $this->chart_data_col = $orig->chart_data_col;
        $this->display_zone = $orig->display_zone;
        $this->position = $orig->position + 1;
        $this->active = 0;
        $this->cache_duration = $orig->cache_duration;

        $newid = $this->create($user);
        if ($newid > 0) {
            // Copier les groupes
            $groups = $orig->getGroups();
            foreach ($groups as $gid) {
                $this->addGroup($gid);
            }
        }
        return $newid;
    }
}
