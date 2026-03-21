<?php
/**
 * Box générique : affiche tous les widgets SQL actifs visibles par l'utilisateur
 */

require_once DOL_DOCUMENT_ROOT.'/core/boxes/modules_boxes.php';

class box_customwidget extends ModeleBoxes
{
    public $boxcode  = 'customwidget';
    public $boximg   = 'customwidget@customwidget';
    public $boxlabel = 'SQL Widgets';
    public $depends  = array('customwidget');

    public $info_box_head     = array();
    public $info_box_contents = array();

    public function __construct($db, $param = '')
    {
        global $langs;
        $this->db = $db;
        parent::__construct($db, $param);
        if (is_object($langs)) {
            $langs->loadLangs(array('customwidget@customwidget'));
        }
    }

    public function loadBox($max = 5, $cachedelay = 0)
    {
        global $user, $langs;

        if (!isModEnabled('customwidget')) {
            return;
        }
        if (!$user->hasRight('customwidget', 'read')) {
            return;
        }

        require_once dol_buildpath('/customwidget/class/customwidget.class.php', 0);
        require_once dol_buildpath('/customwidget/class/customwidget.helper.class.php', 0);

        $langs->loadLangs(array('customwidget@customwidget'));

        $this->info_box_head = array(
            'text'  => $langs->trans('CustomWidgets'),
            'limit' => 0,
        );

        // Charger tous les widgets actifs zone='box' visibles par l'utilisateur
        $w_obj   = new CustomWidget($this->db);
        $widgets = $w_obj->fetchAllForUser($user, '', 'box');

        if (empty($widgets)) {
            $this->info_box_contents = array(
                array(
                    array(
                        'td'   => 'class="nohover opacitymedium center"',
                        'text' => $langs->trans('NoWidgetForThisSlot'),
                    ),
                ),
            );
            return;
        }

        // Inclure Chart.js une seule fois si besoin
        $need_chartjs = false;
        foreach ($widgets as $w) {
            if ($w->widget_type === 'chart') {
                $need_chartjs = true;
                break;
            }
        }

        $refresh_url = dol_buildpath('/customwidget/ajax/refresh.php', 1);

        $html = '';
        if ($need_chartjs) {
            global $conf;
            $chartjs_url = !empty($conf->global->CUSTOMWIDGET_CHARTJS_CDN)
                ? $conf->global->CUSTOMWIDGET_CHARTJS_CDN
                : dol_buildpath('/customwidget/js/chart.min.js', 1);
            $html .= '<script src="'.htmlspecialchars($chartjs_url).'"></script>';
        }

        $html .= '<div class="customwidget-box-wrapper customwidget-fullwidth">';
        foreach ($widgets as $widget) {
            $html .= '<div class="customwidget-box-item" data-widget-id="'.(int) $widget->id.'" data-refresh-url="'.htmlspecialchars($refresh_url).'" style="position:relative;">';
            $html .= '<button type="button" class="cw-refresh-btn" onclick="cwRefreshWidget('.(int) $widget->id.')" title="'.$langs->trans('Refresh').'"><i class="fas fa-sync-alt"></i></button>';
            try {
                $html .= CustomWidgetHelper::render($widget, $this->db, $langs, ($cachedelay > 0));
            } catch (Exception $e) {
                $html .= '<div class="error">'.htmlspecialchars($e->getMessage()).'</div>';
            }
            $html .= '</div>';
        }
        $html .= '</div>';

        $this->info_box_contents = array(
            array(
                array(
                    'td'   => 'class="nohover"',
                    'text' => $html,
                ),
            ),
        );
    }

    public function showBox($head = null, $contents = null, $nooutput = 0)
    {
        return parent::showBox($this->info_box_head, $this->info_box_contents, $nooutput);
    }
}
