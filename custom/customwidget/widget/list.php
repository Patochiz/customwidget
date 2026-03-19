<?php
/**
 * Liste des widgets SQL
 */

$res = 0;
if (!$res && file_exists("../main.inc.php")) $res = @include "../main.inc.php";
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if (!$res && file_exists("../../../../main.inc.php")) $res = @include "../../../../main.inc.php";
if (!$res) die("Include of main fails");

require_once __DIR__.'/../class/customwidget.class.php';
require_once __DIR__.'/../lib/customwidget.lib.php';

$langs->loadLangs(array('customwidget@customwidget'));

if (!isModEnabled('customwidget')) {
    accessforbidden('Module customwidget is not enabled');
}
if (!$user->hasRight('customwidget', 'read')) {
    accessforbidden();
}

$action = GETPOST('action', 'aZ09');
$id = GETPOST('id', 'int');

// Actions
if ($action === 'toggle_active' && $user->hasRight('customwidget', 'write')) {
    $w = new CustomWidget($db);
    if ($w->fetch($id) > 0) {
        $w->active = $w->active ? 0 : 1;
        $w->update($user);
    }
    header('Location: list.php');
    exit;
}

if ($action === 'delete' && $user->hasRight('customwidget', 'delete')) {
    $w = new CustomWidget($db);
    if ($w->fetch($id) > 0) {
        $w->delete($user);
        setEventMessages($langs->trans('WidgetDeleted'), null, 'mesgs');
    }
    header('Location: list.php');
    exit;
}

// Filtres
$filter_type = GETPOST('filter_type', 'alpha');
$filter_zone = GETPOST('filter_zone', 'alpha');

$w_obj = new CustomWidget($db);
$widgets = $w_obj->fetchAll($filter_type, $filter_zone, false);

// Affichage
llxHeader('', $langs->trans('CustomWidgetList'), '', '', 0, 0, array(dol_buildpath('/customwidget/css/customwidget.css', 1)), array(dol_buildpath('/customwidget/js/customwidget.js', 1)));

$head = customwidget_admin_prepare_head();
print dol_get_fiche_head($head, 'list', $langs->trans('Module500200Name'), -1, 'customwidget@customwidget');

// Bouton nouveau widget
if ($user->hasRight('customwidget', 'write')) {
    print '<div class="tabsAction">';
    print '<a class="butAction" href="card.php?action=create">'.$langs->trans('CustomWidgetNew').'</a>';
    print '</div>';
}

// Filtres
print '<form method="GET" action="list.php">';
print '<div class="divsearchfield">';
print '<label>'.$langs->trans('WidgetType').' : </label>';
print '<select name="filter_type" class="flat">';
print '<option value="">'.$langs->trans('All').'</option>';
print '<option value="number"'.($filter_type === 'number' ? ' selected' : '').'>'.$langs->trans('WidgetTypeNumber').'</option>';
print '<option value="table"'.($filter_type === 'table' ? ' selected' : '').'>'.$langs->trans('WidgetTypeTable').'</option>';
print '<option value="chart"'.($filter_type === 'chart' ? ' selected' : '').'>'.$langs->trans('WidgetTypeChart').'</option>';
print '</select>';
print ' &nbsp; ';
print '<label>'.$langs->trans('WidgetDisplayZone').' : </label>';
print '<select name="filter_zone" class="flat">';
print '<option value="">'.$langs->trans('All').'</option>';
print '<option value="box"'.($filter_zone === 'box' ? ' selected' : '').'>'.$langs->trans('WidgetZoneBox').'</option>';
print '<option value="stats"'.($filter_zone === 'stats' ? ' selected' : '').'>'.$langs->trans('WidgetZoneStats').'</option>';
print '</select>';
print ' &nbsp; <input type="submit" class="button" value="'.$langs->trans('Search').'">';
print '</div>';
print '</form>';
print '<br>';

// Tableau liste
print '<table class="tagtable liste" id="customwidget-list">';
print '<thead>';
print '<tr class="liste_titre">';
print '<th>'.$langs->trans('WidgetRef').'</th>';
print '<th>'.$langs->trans('WidgetLabel').'</th>';
print '<th>'.$langs->trans('WidgetType').'</th>';
print '<th>'.$langs->trans('WidgetDisplayZone').'</th>';
print '<th class="center">'.$langs->trans('WidgetPosition').'</th>';
print '<th class="center">'.$langs->trans('WidgetActive').'</th>';
print '<th>'.$langs->trans('WidgetGroups').'</th>';
print '<th class="center">'.$langs->trans('Actions').'</th>';
print '</tr>';
print '</thead>';
print '<tbody id="customwidget-sortable">';

$type_colors = array('number' => 'badge-status4', 'table' => 'badge-status1', 'chart' => 'badge-status6');
$type_labels = array('number' => $langs->trans('WidgetTypeNumber'), 'table' => $langs->trans('WidgetTypeTable'), 'chart' => $langs->trans('WidgetTypeChart'));
$zone_labels = array('box' => $langs->trans('WidgetZoneBox'), 'stats' => $langs->trans('WidgetZoneStats'));

$i = 0;
foreach ($widgets as $w) {
    $groups = $w->getGroups();
    $type_color = isset($type_colors[$w->widget_type]) ? $type_colors[$w->widget_type] : 'badge-status0';
    $type_label = isset($type_labels[$w->widget_type]) ? $type_labels[$w->widget_type] : $w->widget_type;
    $zone_label = isset($zone_labels[$w->display_zone]) ? $zone_labels[$w->display_zone] : $w->display_zone;

    print '<tr class="oddeven drag-row" data-id="'.(int) $w->id.'">';
    print '<td><a href="card.php?id='.(int) $w->id.'">'.htmlspecialchars($w->ref).'</a></td>';
    print '<td>'.htmlspecialchars($w->label).'</td>';
    print '<td><span class="badge '.$type_color.'">'.$type_label.'</span></td>';
    print '<td>'.$zone_label.'</td>';
    print '<td class="center">'.(int) $w->position.'</td>';
    print '<td class="center">';
    if ($user->hasRight('customwidget', 'write')) {
        $title = $w->active ? $langs->trans('Deactivate') : $langs->trans('Activate');
        print '<a href="list.php?action=toggle_active&id='.(int) $w->id.'&token='.newToken().'" title="'.$title.'">';
        print $w->active ? '<span class="badge badge-status4">'.$langs->trans('Yes').'</span>' : '<span class="badge badge-status0">'.$langs->trans('No').'</span>';
        print '</a>';
    } else {
        print $w->active ? '<span class="badge badge-status4">'.$langs->trans('Yes').'</span>' : '<span class="badge badge-status0">'.$langs->trans('No').'</span>';
    }
    print '</td>';
    print '<td>'.count($groups).' groupe(s)</td>';
    print '<td class="center nowrap">';
    if ($user->hasRight('customwidget', 'write')) {
        print '<a href="card.php?id='.(int) $w->id.'" title="'.$langs->trans('Edit').'">'.img_edit().'</a> ';
        print '<a href="clone.php?id='.(int) $w->id.'&token='.newToken().'" title="'.$langs->trans('Clone').'">'.img_picto($langs->trans('Clone'), 'copy').'</a> ';
    }
    if ($user->hasRight('customwidget', 'delete')) {
        print '<a href="list.php?action=delete&id='.(int) $w->id.'&token='.newToken().'" onclick="return confirm(\''.dol_escape_js($langs->trans('ConfirmDeleteWidget')).'\')" title="'.$langs->trans('Delete').'">'.img_delete().'</a>';
    }
    print '</td>';
    print '</tr>';
    $i++;
}

if (!$i) {
    print '<tr><td colspan="8" class="opacitymedium center">'.$langs->trans('NoRecordFound').'</td></tr>';
}

print '</tbody>';
print '</table>';

print dol_get_fiche_end();

llxFooter();
$db->close();
