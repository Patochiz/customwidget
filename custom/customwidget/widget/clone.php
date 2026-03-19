<?php
/**
 * Duplication d'un widget
 */

$res = 0;
if (!$res && file_exists("../main.inc.php")) $res = @include "../main.inc.php";
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if (!$res && file_exists("../../../../main.inc.php")) $res = @include "../../../../main.inc.php";
if (!$res) die("Include of main fails");

require_once __DIR__.'/../class/customwidget.class.php';

$langs->loadLangs(array('customwidget@customwidget'));

if (!isModEnabled('customwidget')) accessforbidden();
if (!$user->hasRight('customwidget', 'write')) accessforbidden();

$id = GETPOST('id', 'int');

if ($id > 0) {
    $newwidget = new CustomWidget($db);
    $newid = $newwidget->createFromClone($user, $id);
    if ($newid > 0) {
        setEventMessages($langs->trans('WidgetCloned'), null, 'mesgs');
        header('Location: card.php?id='.$newid.'&action=edit');
        exit;
    } else {
        setEventMessages($newwidget->error, null, 'errors');
    }
}

header('Location: list.php');
exit;
