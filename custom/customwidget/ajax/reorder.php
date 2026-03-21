<?php
/**
 * Endpoint AJAX : réordonner les widgets (drag & drop)
 */

$dolibarr_nocsrfcheck = 1;
define('NOCSRFCHECK', 1);

$res = 0;
if (!$res && file_exists("../main.inc.php")) $res = @include "../main.inc.php";
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if (!$res && file_exists("../../../../main.inc.php")) $res = @include "../../../../main.inc.php";
if (!$res) die("Include of main fails");

header('Content-Type: application/json; charset=utf-8');

if (!isModEnabled('customwidget')) {
    echo json_encode(array('success' => false, 'error' => 'Module disabled'));
    exit;
}
if (!$user->hasRight('customwidget', 'write')) {
    echo json_encode(array('success' => false, 'error' => 'Permission denied'));
    exit;
}

$order_raw = GETPOST('order', 'nohtml');
$order = json_decode($order_raw, true);

if (!is_array($order)) {
    echo json_encode(array('success' => false, 'error' => 'Paramètre order invalide'));
    exit;
}

$db->begin();
$errors = array();
foreach ($order as $pos => $widget_id) {
    $widget_id = (int) $widget_id;
    $pos = (int) $pos;
    $sql = "UPDATE ".MAIN_DB_PREFIX."customwidget SET position = ".$pos." WHERE rowid = ".$widget_id." AND entity = ".(int) $conf->entity;
    $resql = $db->query($sql);
    if (!$resql) {
        $errors[] = $db->lasterror();
    }
}

if (empty($errors)) {
    $db->commit();
    echo json_encode(array('success' => true));
} else {
    $db->rollback();
    echo json_encode(array('success' => false, 'error' => implode(', ', $errors)));
}
exit;
