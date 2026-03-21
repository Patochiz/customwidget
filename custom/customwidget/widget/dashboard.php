<?php
/**
 * Page dédiée : affichage des widgets SQL personnalisés
 */

$res = 0;
if (!$res && file_exists("../main.inc.php")) $res = @include "../main.inc.php";
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if (!$res && file_exists("../../../../main.inc.php")) $res = @include "../../../../main.inc.php";
if (!$res) die("Include of main fails");

require_once __DIR__.'/../class/customwidget.class.php';
require_once __DIR__.'/../class/customwidget.helper.class.php';

$langs->loadLangs(array('customwidget@customwidget'));

if (!isModEnabled('customwidget')) {
    accessforbidden('Module customwidget is not enabled');
}
if (!$user->hasRight('customwidget', 'read')) {
    accessforbidden();
}

// Charger les widgets actifs visibles par l'utilisateur
$w_obj = new CustomWidget($db);
$widgets = $w_obj->fetchAllForUser($user);

// Déterminer si Chart.js est nécessaire
$need_chartjs = false;
foreach ($widgets as $w) {
    if ($w->widget_type === 'chart') {
        $need_chartjs = true;
        break;
    }
}

$morejs = array();
if ($need_chartjs) {
    $chartjs_url = !empty($conf->global->CUSTOMWIDGET_CHARTJS_CDN)
        ? $conf->global->CUSTOMWIDGET_CHARTJS_CDN
        : dol_buildpath('/customwidget/js/chart.min.js', 1);
    $morejs[] = $chartjs_url;
}

llxHeader('', $langs->trans('CustomWidgetDashboard'), '', '', 0, 0, $morejs, array(dol_buildpath('/customwidget/css/customwidget.css', 1)));

print '<h1 class="titre">'.$langs->trans('CustomWidgetDashboard').'</h1>';

if (empty($widgets)) {
    print '<div class="opacitymedium center" style="padding:30px;">'.$langs->trans('NoWidgetForThisSlot').'</div>';
} else {
    $refresh_url = dol_buildpath('/customwidget/ajax/refresh.php', 1);

    print '<div class="customwidget-dashboard">';
    foreach ($widgets as $widget) {
        print '<div class="customwidget-box-item" data-widget-id="'.(int) $widget->id.'" data-refresh-url="'.htmlspecialchars($refresh_url).'" style="position:relative;margin-bottom:15px;">';
        print '<h2 class="customwidget-title">'.htmlspecialchars($widget->label).'</h2>';
        print '<button type="button" class="cw-refresh-btn" onclick="cwRefreshWidget('.(int) $widget->id.')" title="'.$langs->trans('Refresh').'"><i class="fas fa-sync-alt"></i></button>';
        try {
            print CustomWidgetHelper::render($widget, $db, $langs, false);
        } catch (Exception $e) {
            print '<div class="error">'.htmlspecialchars($e->getMessage()).'</div>';
        }
        print '</div>';
    }
    print '</div>';
}

print '<script>var cw_token = "'.newToken().'";</script>';
print '<script src="'.dol_buildpath('/customwidget/js/customwidget.js', 1).'?v='.urlencode(DOL_VERSION).'"></script>';

llxFooter();
$db->close();
