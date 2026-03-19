<?php
/**
 * Endpoint AJAX : prévisualisation d'un widget
 */

$dolibarr_nocsrfcheck = 1;
define('NOCSRFCHECK', 1);

$res = 0;
if (!$res && file_exists("../main.inc.php")) $res = @include "../main.inc.php";
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if (!$res && file_exists("../../../../main.inc.php")) $res = @include "../../../../main.inc.php";
if (!$res) die("Include of main fails");

require_once __DIR__.'/../class/customwidget.class.php';
require_once __DIR__.'/../class/customwidget.helper.class.php';

header('Content-Type: application/json; charset=utf-8');

if (!isModEnabled('customwidget')) {
    echo json_encode(array('success' => false, 'error' => 'Module disabled'));
    exit;
}
if (!$user->hasRight('customwidget', 'write')) {
    echo json_encode(array('success' => false, 'error' => 'Permission denied'));
    exit;
}

// Construire un objet temporaire depuis les données POST
$w = new CustomWidget($db);
$w->id = GETPOST('id', 'int') ?: 0;
$w->widget_type = GETPOST('widget_type', 'alpha') ?: 'number';
$w->sql_query = GETPOST('sql_query', 'nohtml');
$w->label = GETPOST('label', 'alphanohtml') ?: 'Prévisualisation';
$w->number_icon = GETPOST('number_icon', 'alpha');
$w->number_color = GETPOST('number_color', 'alpha') ?: '#0077b6';
$w->number_suffix = GETPOST('number_suffix', 'alphanohtml');
$w->number_sub1_sql = GETPOST('number_sub1_sql', 'nohtml');
$w->number_sub1_label = GETPOST('number_sub1_label', 'alphanohtml');
$w->number_sub2_sql = GETPOST('number_sub2_sql', 'nohtml');
$w->number_sub2_label = GETPOST('number_sub2_label', 'alphanohtml');
$w->number_url = GETPOST('number_url', 'alpha');
$w->table_maxrows = GETPOST('table_maxrows', 'int') ?: 10;
$w->table_columns = GETPOST('table_columns', 'nohtml'); // JSON string
$w->chart_type = GETPOST('chart_type', 'alpha') ?: 'bar';
$w->chart_height = GETPOST('chart_height', 'int') ?: 300;
$w->chart_label_col = GETPOST('chart_label_col', 'int') ?: 0;
$w->chart_data_col = GETPOST('chart_data_col', 'int') ?: 1;
$w->chart_colors = GETPOST('chart_colors', 'nohtml');
$w->cache_duration = 0; // Pas de cache pour la preview

if (empty($w->sql_query)) {
    echo json_encode(array('success' => false, 'error' => 'Requête SQL vide'));
    exit;
}

try {
    $html = CustomWidgetHelper::render($w, $db, $langs);
    echo json_encode(array('success' => true, 'html' => $html));
} catch (Exception $e) {
    echo json_encode(array('success' => false, 'error' => $e->getMessage()));
}
exit;
