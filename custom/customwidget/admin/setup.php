<?php
/**
 * Page de configuration du module CustomWidget
 */

$res = 0;
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if (!$res && file_exists("../../../../main.inc.php")) $res = @include "../../../../main.inc.php";
if (!$res) die("Include of main fails");

require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once __DIR__.'/../lib/customwidget.lib.php';

$langs->loadLangs(array('admin', 'customwidget@customwidget'));

if (!$user->admin) {
    accessforbidden();
}

$action = GETPOST('action', 'aZ09');

// Actions
if ($action === 'update') {
    $CUSTOMWIDGET_MAX_ROWS = GETPOST('CUSTOMWIDGET_MAX_ROWS', 'int');
    $CUSTOMWIDGET_CACHE_DEFAULT = GETPOST('CUSTOMWIDGET_CACHE_DEFAULT', 'int');
    $CUSTOMWIDGET_ALLOW_JOIN = GETPOST('CUSTOMWIDGET_ALLOW_JOIN', 'int') ? 1 : 0;
    $CUSTOMWIDGET_MAX_SLOTS = GETPOST('CUSTOMWIDGET_MAX_SLOTS', 'int');
    $CUSTOMWIDGET_CHARTJS_CDN = GETPOST('CUSTOMWIDGET_CHARTJS_CDN', 'alpha');

    if ($CUSTOMWIDGET_MAX_SLOTS < 1) $CUSTOMWIDGET_MAX_SLOTS = 1;
    if ($CUSTOMWIDGET_MAX_SLOTS > 20) $CUSTOMWIDGET_MAX_SLOTS = 20;

    dolibarr_set_const($db, 'CUSTOMWIDGET_MAX_ROWS', $CUSTOMWIDGET_MAX_ROWS, 'chaine', 0, '', $conf->entity);
    dolibarr_set_const($db, 'CUSTOMWIDGET_CACHE_DEFAULT', $CUSTOMWIDGET_CACHE_DEFAULT, 'chaine', 0, '', $conf->entity);
    dolibarr_set_const($db, 'CUSTOMWIDGET_ALLOW_JOIN', $CUSTOMWIDGET_ALLOW_JOIN, 'chaine', 0, '', $conf->entity);
    dolibarr_set_const($db, 'CUSTOMWIDGET_MAX_SLOTS', $CUSTOMWIDGET_MAX_SLOTS, 'chaine', 0, '', $conf->entity);
    dolibarr_set_const($db, 'CUSTOMWIDGET_CHARTJS_CDN', $CUSTOMWIDGET_CHARTJS_CDN, 'chaine', 0, '', $conf->entity);

    setEventMessages($langs->trans('SetupSaved'), null, 'mesgs');
    header('Location: setup.php');
    exit;
}

// Affichage
llxHeader('', $langs->trans('CustomWidgetSetup'));

$head = customwidget_admin_prepare_head();
print dol_get_fiche_head($head, 'setup', $langs->trans('Module680200Name'), -1, 'customwidget@customwidget');

print '<form method="POST" action="setup.php">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="update">';

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans('Parameter').'</td>';
print '<td>'.$langs->trans('Value').'</td>';
print '</tr>';

// Max rows
print '<tr class="oddeven">';
print '<td><label for="CUSTOMWIDGET_MAX_ROWS">'.$langs->trans('CUSTOMWIDGET_MAX_ROWS').'</label></td>';
print '<td><input type="number" id="CUSTOMWIDGET_MAX_ROWS" name="CUSTOMWIDGET_MAX_ROWS" class="flat" value="'.(!empty($conf->global->CUSTOMWIDGET_MAX_ROWS) ? (int) $conf->global->CUSTOMWIDGET_MAX_ROWS : 10).'" min="1" max="500"></td>';
print '</tr>';

// Cache default
print '<tr class="oddeven">';
print '<td><label for="CUSTOMWIDGET_CACHE_DEFAULT">'.$langs->trans('CUSTOMWIDGET_CACHE_DEFAULT').'</label></td>';
print '<td><input type="number" id="CUSTOMWIDGET_CACHE_DEFAULT" name="CUSTOMWIDGET_CACHE_DEFAULT" class="flat" value="'.(!empty($conf->global->CUSTOMWIDGET_CACHE_DEFAULT) ? (int) $conf->global->CUSTOMWIDGET_CACHE_DEFAULT : 300).'" min="0"></td>';
print '</tr>';

// Allow JOIN
$allow_join = isset($conf->global->CUSTOMWIDGET_ALLOW_JOIN) ? (int) $conf->global->CUSTOMWIDGET_ALLOW_JOIN : 1;
print '<tr class="oddeven">';
print '<td><label for="CUSTOMWIDGET_ALLOW_JOIN">'.$langs->trans('CUSTOMWIDGET_ALLOW_JOIN').'</label></td>';
print '<td><input type="checkbox" id="CUSTOMWIDGET_ALLOW_JOIN" name="CUSTOMWIDGET_ALLOW_JOIN" value="1"'.($allow_join ? ' checked' : '').'></td>';
print '</tr>';

// Max slots
print '<tr class="oddeven">';
print '<td><label for="CUSTOMWIDGET_MAX_SLOTS">'.$langs->trans('CUSTOMWIDGET_MAX_SLOTS').'</label></td>';
print '<td><input type="number" id="CUSTOMWIDGET_MAX_SLOTS" name="CUSTOMWIDGET_MAX_SLOTS" class="flat" value="'.(!empty($conf->global->CUSTOMWIDGET_MAX_SLOTS) ? (int) $conf->global->CUSTOMWIDGET_MAX_SLOTS : 10).'" min="1" max="20"></td>';
print '</tr>';

// Chart.js CDN
print '<tr class="oddeven">';
print '<td><label for="CUSTOMWIDGET_CHARTJS_CDN">'.$langs->trans('CUSTOMWIDGET_CHARTJS_CDN').'</label></td>';
print '<td><input type="text" id="CUSTOMWIDGET_CHARTJS_CDN" name="CUSTOMWIDGET_CHARTJS_CDN" class="flat minwidth500" value="'.htmlspecialchars(!empty($conf->global->CUSTOMWIDGET_CHARTJS_CDN) ? $conf->global->CUSTOMWIDGET_CHARTJS_CDN : '').'"></td>';
print '</tr>';

print '</table>';
print '<br>';
print '<div class="center">';
print '<input type="submit" class="button button-save" value="'.$langs->trans('Save').'">';
print '</div>';
print '</form>';

print dol_get_fiche_end();

// Exemples de requêtes
print load_fiche_titre($langs->trans('WidgetSQLExamples'), '', 'list');
print '<div class="info"><pre>';
print htmlspecialchars(
    "-- KPI : Nombre de commandes ce mois\nSELECT COUNT(*) as total FROM __PREFIX__commande\nWHERE date_commande >= DATE_FORMAT(NOW(), '%Y-%m-01') AND fk_statut > 0\n\n".
    "-- KPI : CA facturé ce mois\nSELECT SUM(total_ttc) as total FROM __PREFIX__facture\nWHERE datef >= DATE_FORMAT(NOW(), '%Y-%m-01') AND fk_statut = 1\n\n".
    "-- Table : 10 dernières commandes\nSELECT c.ref, s.nom as client, c.date_commande, c.total_ttc\nFROM __PREFIX__commande c\nLEFT JOIN __PREFIX__societe s ON c.fk_soc = s.rowid\nWHERE c.fk_statut > 0 ORDER BY c.date_commande DESC LIMIT 10\n\n".
    "-- Chart : CA mensuel sur 12 mois\nSELECT DATE_FORMAT(datef, '%Y-%m') as mois, SUM(total_ht) as ca\nFROM __PREFIX__facture\nWHERE datef >= DATE_SUB(NOW(), INTERVAL 12 MONTH) AND fk_statut = 1\nGROUP BY DATE_FORMAT(datef, '%Y-%m') ORDER BY mois"
);
print '</pre></div>';

llxFooter();
$db->close();
