<?php
/**
 * Box Dolibarr générique pour les widgets SQL.
 *
 * ARCHITECTURE : UNE instance de cette classe = UN widget.
 * Dolibarr crée une instance par entrée dans llx_boxes_def.
 * Le champ note='cw_XX' identifie quel widget afficher.
 */

require_once DOL_DOCUMENT_ROOT.'/core/boxes/modules_boxes.php';

class box_customwidget extends ModeleBoxes
{
    public $boxcode  = 'customwidget';
    public $boximg   = 'customwidget@customwidget';
    public $boxlabel = 'SQL Widget';
    public $depends  = array('customwidget');

    /**
     * @var int ID du widget dans llx_customwidget
     */
    private $widget_id = 0;

    /**
     * @var CustomWidget|null objet widget chargé
     */
    private $widget_obj = null;

    public function __construct($db, $param = '')
    {
        global $langs;
        $this->db = $db;
        parent::__construct($db, $param);

        if (is_object($langs)) {
            $langs->loadLangs(array('customwidget@customwidget'));
        }

        // Extraire l'ID du widget depuis le paramètre (= note de boxes_def)
        // Format attendu : "cw_42"
        if (is_string($param) && preg_match('/^cw_(\d+)$/', $param, $m)) {
            $this->widget_id = (int) $m[1];
        }
    }

    /**
     * Charge le widget correspondant à cette instance de box.
     * Si widget_id n'est pas encore connu (Dolibarr ne passe pas toujours
     * le param au constructeur), on le retrouve via box_id → boxes_def.note.
     */
    private function _resolveWidget()
    {
        if ($this->widget_obj !== null) {
            return; // déjà chargé
        }

        // Tentative de résolution via box_id si widget_id pas encore connu
        if ($this->widget_id <= 0 && !empty($this->box_id)) {
            $sql = "SELECT note FROM ".MAIN_DB_PREFIX."boxes_def WHERE rowid = ".(int) $this->box_id;
            $resql = $this->db->query($sql);
            if ($resql) {
                $obj = $this->db->fetch_object($resql);
                if ($obj && preg_match('/^cw_(\d+)$/', $obj->note, $m)) {
                    $this->widget_id = (int) $m[1];
                }
            }
        }

        if ($this->widget_id <= 0) {
            return;
        }

        require_once dol_buildpath('/customwidget/class/customwidget.class.php', 0);

        $this->widget_obj = new CustomWidget($this->db);
        $ret = $this->widget_obj->fetch($this->widget_id);
        if ($ret <= 0) {
            $this->widget_obj = null;
            return;
        }

        // Adapter le label et le code de la box
        $this->boxlabel = $this->widget_obj->label;
        $this->boxcode  = 'customwidget_'.$this->widget_id;
    }

    public function loadBox($max = 5, $cachedelay = 0)
    {
        global $user, $langs;

        if (!isModEnabled('customwidget')) {
            return;
        }

        $this->_resolveWidget();

        // Pas de widget trouvé → message par défaut
        if ($this->widget_obj === null) {
            $this->info_box_head = array('text' => $langs->trans('CustomWidgets'));
            $this->info_box_contents = array(array(array(
                'td'   => 'class="nohover opacitymedium center"',
                'text' => $langs->trans('NoWidgetForThisSlot'),
            )));
            return;
        }

        // Vérification accès utilisateur
        if (!$this->widget_obj->userCanView($user)) {
            // Widget masqué pour cet utilisateur → box vide invisible
            $this->info_box_head = array('text' => '');
            $this->info_box_contents = array();
            return;
        }

        $this->info_box_head = array(
            'text'  => dol_escape_htmltag($this->widget_obj->label),
            'limit' => 0,
        );

        require_once dol_buildpath('/customwidget/class/customwidget.helper.class.php', 0);

        // Chart.js si nécessaire
        $html = '';
        if ($this->widget_obj->widget_type === 'chart') {
            global $conf;
            $chartjs_url = !empty($conf->global->CUSTOMWIDGET_CHARTJS_CDN)
                ? $conf->global->CUSTOMWIDGET_CHARTJS_CDN
                : dol_buildpath('/customwidget/js/chart.min.js', 1);
            $html .= '<script>if(typeof Chart==="undefined"){var s=document.createElement("script");'
                .'s.src="'.dol_escape_js($chartjs_url).'";document.head.appendChild(s);}</script>';
        }

        // Bouton refresh AJAX
        $refresh_url = dol_buildpath('/customwidget/ajax/refresh.php', 1);
        $wid = (int) $this->widget_obj->id;
        $html .= '<div class="customwidget-box-item" data-widget-id="'.$wid.'" data-refresh-url="'.dol_escape_htmltag($refresh_url).'" style="position:relative;">';
        $html .= '<button type="button" class="cw-refresh-btn" onclick="cwRefreshWidget('.$wid.')" title="'.$langs->trans('Refresh').'"><i class="fas fa-sync-alt"></i></button>';

        try {
            $html .= CustomWidgetHelper::render($this->widget_obj, $this->db, $langs, ($cachedelay > 0));
        } catch (Exception $e) {
            $html .= '<div class="error">'.dol_escape_htmltag($e->getMessage()).'</div>';
        }
        $html .= '</div>';

        $this->info_box_contents = array(array(array(
            'td'   => 'class="nohover"',
            'text' => $html,
        )));
    }

    public function showBox($head = null, $contents = null, $nooutput = 0)
    {
        return parent::showBox($this->info_box_head, $this->info_box_contents, $nooutput);
    }
}
