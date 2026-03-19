<?php
/**
 * Fiche création/édition d'un widget SQL
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

$action = GETPOST('action', 'aZ09');
$id = GETPOST('id', 'int');

$object = new CustomWidget($db);

// Récupérer tous les groupes Dolibarr
$sql_groups = "SELECT rowid, nom FROM ".MAIN_DB_PREFIX."usergroup WHERE entity = ".(int) $conf->entity." ORDER BY nom";
$resql_groups = $db->query($sql_groups);
$all_groups = array();
if ($resql_groups) {
    while ($obj = $db->fetch_object($resql_groups)) {
        $all_groups[$obj->rowid] = $obj->nom;
    }
}

// Charger l'objet existant
if ($id > 0) {
    if (!$user->hasRight('customwidget', 'read')) accessforbidden();
    $object->fetch($id);
}

// --- Actions ---
if ($action === 'add' && $user->hasRight('customwidget', 'write')) {
    $object->ref = GETPOST('ref', 'alphanohtml');
    $object->label = GETPOST('label', 'alphanohtml');
    $object->description = GETPOST('description', 'restricthtml');
    $object->widget_type = GETPOST('widget_type', 'alpha');
    $object->sql_query = GETPOST('sql_query', 'nohtml');
    $object->display_zone = GETPOST('display_zone', 'alpha');
    $object->position = GETPOST('position', 'int');
    $object->active = GETPOST('active', 'int') ? 1 : 0;
    $object->cache_duration = GETPOST('cache_duration', 'int');

    // Number
    $object->number_icon = GETPOST('number_icon', 'alpha');
    $object->number_color = GETPOST('number_color', 'alpha');
    $object->number_suffix = GETPOST('number_suffix', 'alphanohtml');
    $object->number_sub1_sql = GETPOST('number_sub1_sql', 'nohtml');
    $object->number_sub1_label = GETPOST('number_sub1_label', 'alphanohtml');
    $object->number_sub2_sql = GETPOST('number_sub2_sql', 'nohtml');
    $object->number_sub2_label = GETPOST('number_sub2_label', 'alphanohtml');
    $object->number_url = GETPOST('number_url', 'alpha');

    // Table
    $object->table_maxrows = GETPOST('table_maxrows', 'int');
    $col_names = GETPOST('col_name', 'array');
    $col_labels = GETPOST('col_label', 'array');
    $col_types = GETPOST('col_type', 'array');
    $col_links = GETPOST('col_link', 'array');
    if (is_array($col_names) && count($col_names) > 0) {
        $cols = array();
        foreach ($col_names as $i => $cn) {
            if ($cn !== '') {
                $cols[] = array(
                    'name' => $cn,
                    'label' => isset($col_labels[$i]) ? $col_labels[$i] : $cn,
                    'type' => isset($col_types[$i]) ? $col_types[$i] : 'text',
                    'link' => isset($col_links[$i]) ? $col_links[$i] : '',
                );
            }
        }
        $object->table_columns = json_encode($cols);
    }

    // Chart
    $object->chart_type = GETPOST('chart_type', 'alpha');
    $object->chart_height = GETPOST('chart_height', 'int');
    $object->chart_label_col = GETPOST('chart_label_col', 'int');
    $object->chart_data_col = GETPOST('chart_data_col', 'int');
    $chart_colors_arr = GETPOST('chart_color', 'array');
    if (is_array($chart_colors_arr) && count($chart_colors_arr) > 0) {
        $object->chart_colors = json_encode(array_values(array_filter($chart_colors_arr)));
    }

    $result = $object->create($user);
    if ($result > 0) {
        // Groupes
        $group_ids = GETPOST('groups', 'array');
        if (is_array($group_ids)) {
            $object->setGroups(array_map('intval', $group_ids));
        }
        setEventMessages($langs->trans('WidgetCreated'), null, 'mesgs');
        header('Location: card.php?id='.$result);
        exit;
    } else {
        setEventMessages($object->error, null, 'errors');
    }
    $action = 'create';
}

if ($action === 'update' && $user->hasRight('customwidget', 'write') && $id > 0) {
    $object->ref = GETPOST('ref', 'alphanohtml');
    $object->label = GETPOST('label', 'alphanohtml');
    $object->description = GETPOST('description', 'restricthtml');
    $object->widget_type = GETPOST('widget_type', 'alpha');
    $object->sql_query = GETPOST('sql_query', 'nohtml');
    $object->display_zone = GETPOST('display_zone', 'alpha');
    $object->position = GETPOST('position', 'int');
    $object->active = GETPOST('active', 'int') ? 1 : 0;
    $object->cache_duration = GETPOST('cache_duration', 'int');
    $object->number_icon = GETPOST('number_icon', 'alpha');
    $object->number_color = GETPOST('number_color', 'alpha');
    $object->number_suffix = GETPOST('number_suffix', 'alphanohtml');
    $object->number_sub1_sql = GETPOST('number_sub1_sql', 'nohtml');
    $object->number_sub1_label = GETPOST('number_sub1_label', 'alphanohtml');
    $object->number_sub2_sql = GETPOST('number_sub2_sql', 'nohtml');
    $object->number_sub2_label = GETPOST('number_sub2_label', 'alphanohtml');
    $object->number_url = GETPOST('number_url', 'alpha');
    $object->table_maxrows = GETPOST('table_maxrows', 'int');

    $col_names = GETPOST('col_name', 'array');
    $col_labels = GETPOST('col_label', 'array');
    $col_types = GETPOST('col_type', 'array');
    $col_links = GETPOST('col_link', 'array');
    if (is_array($col_names)) {
        $cols = array();
        foreach ($col_names as $i => $cn) {
            if ($cn !== '') {
                $cols[] = array(
                    'name' => $cn,
                    'label' => isset($col_labels[$i]) ? $col_labels[$i] : $cn,
                    'type' => isset($col_types[$i]) ? $col_types[$i] : 'text',
                    'link' => isset($col_links[$i]) ? $col_links[$i] : '',
                );
            }
        }
        $object->table_columns = json_encode($cols);
    }

    $object->chart_type = GETPOST('chart_type', 'alpha');
    $object->chart_height = GETPOST('chart_height', 'int');
    $object->chart_label_col = GETPOST('chart_label_col', 'int');
    $object->chart_data_col = GETPOST('chart_data_col', 'int');
    $chart_colors_arr = GETPOST('chart_color', 'array');
    if (is_array($chart_colors_arr)) {
        $object->chart_colors = json_encode(array_values(array_filter($chart_colors_arr)));
    }

    $result = $object->update($user);
    if ($result > 0) {
        $group_ids = GETPOST('groups', 'array');
        $object->setGroups(is_array($group_ids) ? array_map('intval', $group_ids) : array());
        setEventMessages($langs->trans('WidgetUpdated'), null, 'mesgs');
        header('Location: card.php?id='.$id);
        exit;
    } else {
        setEventMessages($object->error, null, 'errors');
    }
    $action = 'edit';
}

// --- Affichage ---
$title = ($action === 'create') ? $langs->trans('CustomWidgetNew') : $langs->trans('CustomWidgetCard');
llxHeader('', $title, '', '', 0, 0,
    array(dol_buildpath('/customwidget/js/customwidget.js', 1)),
    array(dol_buildpath('/customwidget/css/customwidget.css', 1))
);

$current_groups = ($id > 0) ? $object->getGroups() : array();
$col_config = array();
if ($object->table_columns) {
    $decoded = json_decode($object->table_columns, true);
    if (is_array($decoded)) $col_config = $decoded;
}
$chart_color_list = array('#0077b6', '#00b4d8', '#90e0ef');
if ($object->chart_colors) {
    $decoded = json_decode($object->chart_colors, true);
    if (is_array($decoded) && count($decoded) > 0) $chart_color_list = $decoded;
}

if ($id > 0) {
    $head = customwidget_prepare_head($object);
    print dol_get_fiche_head($head, 'card', $langs->trans('Module500200Name'), -1, 'customwidget@customwidget');
    print dol_banner_tab($object, 'id', null, 1, 'rowid', 'ref');
} else {
    print load_fiche_titre($langs->trans('CustomWidgetNew'), '', 'customwidget@customwidget');
}

$form_action = ($action === 'create') ? 'add' : 'update';
$edit_mode = ($action === 'create' || $action === 'edit' || ($id == 0));

print '<form method="POST" action="card.php" id="customwidget-card-form">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="'.$form_action.'">';
if ($id > 0) print '<input type="hidden" name="id" value="'.(int) $id.'">';

print '<div class="fichecenter">';
print '<div class="fichehalfleft">';
print '<table class="border centpercent tableforfield">';

// Référence
print '<tr><td class="titlefield">'.$langs->trans('WidgetRef').'</td>';
print '<td><input type="text" name="ref" class="flat minwidth200" value="'.htmlspecialchars($object->ref).'" '.($edit_mode ? '' : 'readonly').'></td></tr>';

// Label
print '<tr><td class="fieldrequired">'.$langs->trans('WidgetLabel').'</td>';
print '<td><input type="text" name="label" class="flat minwidth300" value="'.htmlspecialchars($object->label).'" required '.($edit_mode ? '' : 'readonly').'></td></tr>';

// Type
print '<tr><td class="fieldrequired">'.$langs->trans('WidgetType').'</td><td>';
if ($edit_mode) {
    print '<select name="widget_type" id="cw_widget_type" class="flat" onchange="cwUpdateSections()">';
    foreach (array('number' => $langs->trans('WidgetTypeNumber'), 'table' => $langs->trans('WidgetTypeTable'), 'chart' => $langs->trans('WidgetTypeChart')) as $v => $l) {
        print '<option value="'.$v.'"'.($object->widget_type === $v ? ' selected' : '').'>'.$l.'</option>';
    }
    print '</select>';
} else {
    print htmlspecialchars($object->widget_type);
}
print '</td></tr>';

// Zone d'affichage
print '<tr><td>'.$langs->trans('WidgetDisplayZone').'</td><td>';
if ($edit_mode) {
    print '<select name="display_zone" id="cw_display_zone" class="flat">';
    print '<option value="box"'.($object->display_zone === 'box' ? ' selected' : '').'>'.$langs->trans('WidgetZoneBox').'</option>';
    print '<option value="stats"'.($object->display_zone === 'stats' ? ' selected' : '').'>'.$langs->trans('WidgetZoneStats').'</option>';
    print '</select>';
} else {
    print htmlspecialchars($object->display_zone);
}
print '</td></tr>';

// Actif
print '<tr><td>'.$langs->trans('WidgetActive').'</td><td>';
if ($edit_mode) {
    print '<input type="checkbox" name="active" value="1"'.($object->active ? ' checked' : '').'>';
} else {
    print $object->active ? $langs->trans('Yes') : $langs->trans('No');
}
print '</td></tr>';

// Position
print '<tr><td>'.$langs->trans('WidgetPosition').'</td>';
print '<td><input type="number" name="position" class="flat width75" value="'.(int) $object->position.'" '.($edit_mode ? '' : 'readonly').'></td></tr>';

// Cache
print '<tr><td>'.$langs->trans('WidgetCacheDuration').'</td>';
print '<td><input type="number" name="cache_duration" class="flat width75" value="'.(int) $object->cache_duration.'" min="0" '.($edit_mode ? '' : 'readonly').'></td></tr>';

print '</table>';
print '</div>'; // fichehalfleft

print '<div class="fichehalfright">';
print '<table class="border centpercent tableforfield">';

// Description
print '<tr><td class="titlefield">'.$langs->trans('WidgetDescription').'</td>';
print '<td><textarea name="description" class="flat" rows="3" style="width:100%">'.htmlspecialchars($object->description).'</textarea></td></tr>';

// Groupes
print '<tr><td>'.$langs->trans('WidgetGroups').'</td><td>';
if ($all_groups) {
    print '<select name="groups[]" multiple class="flat" style="height:100px;">';
    foreach ($all_groups as $gid => $gname) {
        print '<option value="'.(int) $gid.'"'.(in_array($gid, $current_groups) ? ' selected' : '').'>';
        print htmlspecialchars($gname);
        print '</option>';
    }
    print '</select>';
    print '<br><small class="opacitymedium">'.$langs->trans('WidgetGroupsHelp').'</small>';
} else {
    print '<small class="opacitymedium">Aucun groupe disponible</small>';
}
print '</td></tr>';

print '</table>';
print '</div>'; // fichehalfright
print '</div>'; // fichecenter

// Requête SQL
print '<div class="fichecenter">';
print '<table class="border centpercent tableforfield">';
print '<tr><td class="titlefield fieldrequired">'.$langs->trans('WidgetSqlQuery').'</td><td>';
print '<textarea name="sql_query" id="cw_sql_query" class="flat" rows="5" style="width:100%;font-family:monospace;" required>'.htmlspecialchars($object->sql_query).'</textarea>';
print '<br><small class="opacitymedium">'.$langs->trans('WidgetQueryPrefixHelp').'</small>';
print '</td></tr>';
print '</table>';

if ($edit_mode) {
    print '<div class="center" style="margin-top:10px;">';
    print '<button type="button" class="button" onclick="cwTestQuery()">'.$langs->trans('WidgetTestQuery').'</button>';
    print '</div>';
    print '<div id="cw-query-result" class="customwidget-query-result" style="display:none;margin-top:10px;"></div>';
}
print '</div>';

// === Section Number ===
$num_style = ($object->widget_type !== 'number') ? 'display:none;' : '';
print '<div id="cw-section-number" class="fichecenter cw-type-section" style="'.$num_style.'">';
print '<h3>'.$langs->trans('WidgetTypeNumber').'</h3>';
print '<table class="border centpercent tableforfield">';

print '<tr><td class="titlefield">'.$langs->trans('WidgetNumberIcon').'</td>';
print '<td><input type="text" name="number_icon" class="flat" value="'.htmlspecialchars($object->number_icon).'" placeholder="fa-chart-bar"> ';
print '<small>'.$langs->trans('WidgetNumberIconHelp').'</small></td></tr>';

print '<tr><td>'.$langs->trans('WidgetNumberColor').'</td>';
print '<td><input type="color" id="cw_number_color_picker" value="'.htmlspecialchars($object->number_color ?: '#0077b6').'" oninput="document.getElementById(\'cw_number_color_text\').value=this.value"> ';
print '<input type="text" id="cw_number_color_text" name="number_color" class="flat width100" value="'.htmlspecialchars($object->number_color ?: '#0077b6').'" oninput="document.getElementById(\'cw_number_color_picker\').value=this.value" style="display:inline-block;margin-left:5px;"></td></tr>';

print '<tr><td>'.$langs->trans('WidgetNumberSuffix').'</td>';
print '<td><input type="text" name="number_suffix" class="flat width100" value="'.htmlspecialchars($object->number_suffix).'" placeholder="€"></td></tr>';

print '<tr><td>'.$langs->trans('WidgetNumberUrl').'</td>';
print '<td><input type="text" name="number_url" class="flat minwidth300" value="'.htmlspecialchars($object->number_url).'"></td></tr>';

print '<tr><td>'.$langs->trans('WidgetNumberSub1').'</td><td>';
print '<input type="text" name="number_sub1_label" class="flat width200" value="'.htmlspecialchars($object->number_sub1_label).'" placeholder="Label"> ';
print '<textarea name="number_sub1_sql" class="flat" rows="2" style="width:100%;font-family:monospace;">'.htmlspecialchars($object->number_sub1_sql).'</textarea>';
print '</td></tr>';

print '<tr><td>'.$langs->trans('WidgetNumberSub2').'</td><td>';
print '<input type="text" name="number_sub2_label" class="flat width200" value="'.htmlspecialchars($object->number_sub2_label).'" placeholder="Label"> ';
print '<textarea name="number_sub2_sql" class="flat" rows="2" style="width:100%;font-family:monospace;">'.htmlspecialchars($object->number_sub2_sql).'</textarea>';
print '</td></tr>';

print '</table>';
print '</div>';

// === Section Table ===
$tbl_style = ($object->widget_type !== 'table') ? 'display:none;' : '';
print '<div id="cw-section-table" class="fichecenter cw-type-section" style="'.$tbl_style.'">';
print '<h3>'.$langs->trans('WidgetTypeTable').'</h3>';
print '<table class="border centpercent tableforfield">';

print '<tr><td class="titlefield">'.$langs->trans('WidgetTableMaxRows').'</td>';
print '<td><input type="number" name="table_maxrows" class="flat width75" value="'.(int) ($object->table_maxrows ?: 10).'" min="1" max="500"></td></tr>';

print '</table>';

print '<br><strong>'.$langs->trans('WidgetTableColumns').'</strong>';
print '<table class="tagtable liste" id="cw-table-columns">';
print '<thead><tr class="liste_titre">';
print '<th>'.$langs->trans('WidgetTableColName').'</th>';
print '<th>'.$langs->trans('WidgetTableColLabel').'</th>';
print '<th>'.$langs->trans('WidgetTableColType').'</th>';
print '<th>'.$langs->trans('WidgetTableColLink').'</th>';
print '<th></th>';
print '</tr></thead><tbody id="cw-col-body">';

$col_types_list = array('text' => 'Texte', 'integer' => 'Entier', 'price' => 'Prix', 'percentage' => 'Pourcentage', 'date' => 'Date', 'status' => 'Statut');
if (empty($col_config)) {
    $col_config = array(array('name' => '', 'label' => '', 'type' => 'text', 'link' => ''));
}
foreach ($col_config as $i => $col) {
    print '<tr class="oddeven">';
    print '<td><input type="text" name="col_name[]" class="flat" value="'.htmlspecialchars($col['name']).'"></td>';
    print '<td><input type="text" name="col_label[]" class="flat" value="'.htmlspecialchars($col['label']).'"></td>';
    print '<td><select name="col_type[]" class="flat">';
    foreach ($col_types_list as $v => $l) {
        print '<option value="'.$v.'"'.($col['type'] === $v ? ' selected' : '').'>'.$l.'</option>';
    }
    print '</select></td>';
    print '<td><input type="text" name="col_link[]" class="flat" value="'.htmlspecialchars($col['link']).'"></td>';
    print '<td><button type="button" class="button buttonDelete" onclick="cwRemoveRow(this)">✕</button></td>';
    print '</tr>';
}

print '</tbody></table>';
print '<button type="button" class="button" onclick="cwAddColumn()" style="margin-top:5px;">'.$langs->trans('WidgetTableAddColumn').'</button>';
print '</div>';

// === Section Chart ===
$chart_style = ($object->widget_type !== 'chart') ? 'display:none;' : '';
print '<div id="cw-section-chart" class="fichecenter cw-type-section" style="'.$chart_style.'">';
print '<h3>'.$langs->trans('WidgetTypeChart').'</h3>';
print '<table class="border centpercent tableforfield">';

print '<tr><td class="titlefield">'.$langs->trans('WidgetChartType').'</td><td>';
print '<select name="chart_type" class="flat">';
foreach (array('bar' => $langs->trans('WidgetChartBar'), 'line' => $langs->trans('WidgetChartLine'), 'doughnut' => $langs->trans('WidgetChartDoughnut'), 'pie' => $langs->trans('WidgetChartPie')) as $v => $l) {
    print '<option value="'.$v.'"'.($object->chart_type === $v ? ' selected' : '').'>'.$l.'</option>';
}
print '</select></td></tr>';

print '<tr><td>'.$langs->trans('WidgetChartHeight').'</td>';
print '<td><input type="number" name="chart_height" class="flat width75" value="'.(int) ($object->chart_height ?: 300).'" min="100"></td></tr>';

print '<tr><td>'.$langs->trans('WidgetChartLabelCol').'</td>';
print '<td><input type="number" name="chart_label_col" class="flat width75" value="'.(int) $object->chart_label_col.'" min="0"></td></tr>';

print '<tr><td>'.$langs->trans('WidgetChartDataCol').'</td>';
print '<td><input type="number" name="chart_data_col" class="flat width75" value="'.(int) $object->chart_data_col.'" min="0"></td></tr>';

print '</table>';
print '<br><strong>'.$langs->trans('WidgetChartColors').'</strong>';
print '<div id="cw-chart-colors">';
foreach ($chart_color_list as $c) {
    print '<div class="cw-color-row" style="display:inline-block;margin:3px;">';
    print '<input type="color" name="chart_color[]" value="'.htmlspecialchars($c).'">';
    print ' <button type="button" class="button buttonDelete" onclick="cwRemoveRow(this)">✕</button>';
    print '</div>';
}
print '</div>';
print '<button type="button" class="button" onclick="cwAddColor()" style="margin-top:5px;">'.$langs->trans('WidgetChartAddColor').'</button>';
print '</div>';

// === Boutons d'action ===
print '<div class="center" style="margin-top:20px;">';
if ($edit_mode) {
    if ($id > 0) {
        print '<button type="button" class="button" onclick="cwPreview()">'.$langs->trans('WidgetPreview').'</button> ';
    }
    print '<input type="submit" class="button button-save" value="'.$langs->trans('Save').'">';
    print ' <a href="list.php" class="button button-cancel">'.$langs->trans('Cancel').'</a>';
} else {
    if ($user->hasRight('customwidget', 'write')) {
        print '<a href="card.php?id='.(int) $id.'&action=edit" class="butAction">'.$langs->trans('Modify').'</a>';
    }
    print ' <a href="list.php" class="butAction butActionDelete">'.$langs->trans('Back').'</a>';
}
print '</div>';

// Zone prévisualisation
print '<div id="cw-preview-zone" style="display:none;margin-top:20px;">';
print '<h3>'.$langs->trans('WidgetPreview').'</h3>';
print '<div id="cw-preview-content"></div>';
print '</div>';

print '</form>';

if ($id > 0) {
    print dol_get_fiche_end();
}

// Variables JS pour AJAX
print '<script>';
print 'var cw_ajax_testquery_url = "'.dol_buildpath('/customwidget/ajax/testquery.php', 1).'";';
print 'var cw_ajax_preview_url = "'.dol_buildpath('/customwidget/ajax/preview.php', 1).'";';
print 'var cw_token = "'.newToken().'";';
print 'var cw_col_types = '.json_encode($col_types_list).';';
print '</script>';

llxFooter();
$db->close();
