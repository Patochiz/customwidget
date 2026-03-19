<?php
/**
 * Classe box générique pour les widgets SQL dashboard
 */

require_once DOL_DOCUMENT_ROOT.'/core/boxes/modules_boxes.php';

class box_customwidget extends ModeleBoxes
{
    public $boxcode = "customwidget";
    public $boximg = "customwidget@customwidget";
    public $boxlabel = "SQL Widget";
    public $depends = array('customwidget');

    public $info_box_head = array();
    public $info_box_contents = array();

    private $html_content = '';
    private $widget_label = '';

    public function __construct($db, $param = '')
    {
        global $conf, $user, $langs;
        $this->db = $db;
        $langs->loadLangs(array('customwidget@customwidget'));
        parent::__construct($db, $param);
    }

    public function loadBox($max = 5, $cachedelay = 0)
    {
        global $conf, $user, $langs;

        if (!isModEnabled('customwidget')) {
            return;
        }

        require_once dol_buildpath('/customwidget/class/customwidget.class.php', 0);
        require_once dol_buildpath('/customwidget/class/customwidget.helper.class.php', 0);

        $langs->loadLangs(array('customwidget@customwidget'));

        // Déterminer le slot de cette instance
        $slot_index = 0;
        if (isset($this->box_order) && is_numeric($this->box_order)) {
            $slot_index = (int) $this->box_order;
        } elseif (isset($this->box_id)) {
            // Extraire l'index depuis box_id si possible
            $slot_index = max(0, (int) $this->box_id - 1);
        }

        // Charger tous les widgets actifs de type 'box' visibles par l'utilisateur
        $w_obj = new CustomWidget($this->db);
        $widgets = $w_obj->fetchAllForUser($user, '', 'box');

        if (isset($widgets[$slot_index])) {
            $widget = $widgets[$slot_index];
            $this->widget_label = $widget->label;
            $this->boxlabel = $widget->label;

            try {
                $this->html_content = CustomWidgetHelper::render($widget, $this->db, $langs);
            } catch (Exception $e) {
                $this->html_content = '<div class="error">'.htmlspecialchars($e->getMessage()).'</div>';
            }

            $this->info_box_head = array(
                'text' => $langs->trans('SQLWidget').': '.htmlspecialchars($widget->label),
                'limit' => 0,
            );
            $this->info_box_contents = array(
                array(array('td' => 'class="nohover"', 'text' => $this->html_content)),
            );
        } else {
            // Pas de widget pour ce slot
            $this->info_box_head = array(
                'text' => $langs->trans('SQLWidget'),
                'limit' => 0,
            );
            $this->info_box_contents = array(
                array(array('td' => 'class="nohover opacitymedium center"', 'text' => $langs->trans('NoWidgetForThisSlot'))),
            );
        }
    }

    public function showBox($head = null, $contents = null, $nooutput = 0)
    {
        return parent::showBox($this->info_box_head, $this->info_box_contents, $nooutput);
    }
}
