<?php
/**
 * Endpoint AJAX : test d'une requête SQL
 */

$dolibarr_nocsrfcheck = 1;
define('NOCSRFCHECK', 1);

$res = 0;
if (!$res && file_exists("../main.inc.php")) $res = @include "../main.inc.php";
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if (!$res && file_exists("../../../../main.inc.php")) $res = @include "../../../../main.inc.php";
if (!$res) die("Include of main fails");

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

$sql_raw = GETPOST('sql', 'nohtml');
$maxrows = GETPOST('maxrows', 'int');
if (!$maxrows || $maxrows > 20) $maxrows = 20;

if (empty($sql_raw)) {
    echo json_encode(array('success' => false, 'error' => 'Requête vide'));
    exit;
}

$validation = CustomWidgetHelper::validateQuery($sql_raw);
if (!$validation['valid']) {
    echo json_encode(array('success' => false, 'error' => $validation['error']));
    exit;
}

$result = CustomWidgetHelper::executeQuery($db, $validation['sql'], $maxrows);

if ($result['error']) {
    echo json_encode(array('success' => false, 'error' => $result['error']));
    exit;
}

echo json_encode(array(
    'success' => true,
    'columns' => $result['columns'],
    'rows' => $result['rows'],
    'num_rows' => $result['num_rows'],
    'execution_time' => $result['execution_time'],
));
exit;
