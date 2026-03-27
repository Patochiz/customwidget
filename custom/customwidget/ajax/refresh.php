<?php
/**
 * Endpoint AJAX : rafraîchir un widget par son ID (sans cache)
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
if (!$user->hasRight('customwidget', 'read')) {
    echo json_encode(array('success' => false, 'error' => 'Permission denied'));
    exit;
}

$widget_id = GETPOST('widget_id', 'int');
if (empty($widget_id)) {
    echo json_encode(array('success' => false, 'error' => 'Missing widget_id'));
    exit;
}

$widget = new CustomWidget($db);
$ret = $widget->fetch($widget_id);
if ($ret <= 0) {
    echo json_encode(array('success' => false, 'error' => 'Widget not found'));
    exit;
}

if (!$widget->userCanView($user)) {
    echo json_encode(array('success' => false, 'error' => 'Permission denied'));
    exit;
}

try {
    $html = CustomWidgetHelper::render($widget, $db, $langs, false);
    echo json_encode(array('success' => true, 'html' => $html));
} catch (Exception $e) {
    echo json_encode(array('success' => false, 'error' => $e->getMessage()));
}
exit;
