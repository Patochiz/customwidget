<?php
/**
 * Fonctions utilitaires pour le module customwidget
 */

/**
 * Retourne les onglets pour les pages d'administration
 */
function customwidget_admin_prepare_head()
{
    global $langs, $conf;
    $langs->load('customwidget@customwidget');

    $h = 0;
    $head = array();

    $head[$h][0] = dol_buildpath('/customwidget/admin/setup.php', 1);
    $head[$h][1] = $langs->trans('Setup');
    $head[$h][2] = 'setup';
    $h++;

    $head[$h][0] = dol_buildpath('/customwidget/widget/list.php', 1);
    $head[$h][1] = $langs->trans('CustomWidgetList');
    $head[$h][2] = 'list';
    $h++;

    $head[$h][0] = dol_buildpath('/customwidget/admin/about.php', 1);
    $head[$h][1] = $langs->trans('CustomWidgetAbout');
    $head[$h][2] = 'about';
    $h++;

    complete_head_from_modules($conf, $langs, null, $head, $h, 'customwidget_admin');
    complete_head_from_modules($conf, $langs, null, $head, $h, 'customwidget_admin', 'remove');

    return $head;
}

/**
 * Retourne les onglets pour la fiche widget
 */
function customwidget_prepare_head($object)
{
    global $langs, $conf;
    $langs->load('customwidget@customwidget');

    $h = 0;
    $head = array();

    $head[$h][0] = dol_buildpath('/customwidget/widget/card.php', 1).'?id='.(int) $object->id;
    $head[$h][1] = $langs->trans('CustomWidgetCard');
    $head[$h][2] = 'card';
    $h++;

    complete_head_from_modules($conf, $langs, $object, $head, $h, 'customwidget');
    complete_head_from_modules($conf, $langs, $object, $head, $h, 'customwidget', 'remove');

    return $head;
}
