<?php
/**
 * Page à propos du module CustomWidget
 */

$res = 0;
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if (!$res && file_exists("../../../../main.inc.php")) $res = @include "../../../../main.inc.php";
if (!$res) die("Include of main fails");

require_once __DIR__.'/../lib/customwidget.lib.php';

$langs->loadLangs(array('admin', 'customwidget@customwidget'));

if (!$user->admin) {
    accessforbidden();
}

llxHeader('', $langs->trans('CustomWidgetAbout'));

$head = customwidget_admin_prepare_head();
print dol_get_fiche_head($head, 'about', $langs->trans('Module500200Name'), -1, 'customwidget@customwidget');

print '<div class="center">';
print '<br>';
print '<h2>'.$langs->trans('Module500200Name').' v1.0.0</h2>';
print '<p>'.$langs->trans('Module500200Desc').'</p>';
print '<br>';
print '<p><strong>Auteur</strong> : Module CustomWidget</p>';
print '<p><strong>Licence</strong> : GPL v3</p>';
print '<p><strong>Dolibarr</strong> : 20.0.0+</p>';
print '<p><strong>PHP</strong> : >= 7.4</p>';
print '</div>';

print dol_get_fiche_end();

llxFooter();
$db->close();
