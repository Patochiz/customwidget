<?php
/**
 * Hook ActionsCustomWidget
 * Injecte les widgets de type 'number' zone 'stats' dans la page d'accueil
 */

class ActionsCustomWidget
{
    public $results = array();
    public $resprints;
    public $errors = array();

    public function __construct()
    {
        // nothing
    }

    /**
     * Hook appelé sur la page d'accueil pour injecter des statistiques
     */
    public function addStatisticLine($parameters, &$object, &$action, $hookmanager)
    {
        global $conf, $db, $langs, $user;

        if (!isModEnabled('customwidget')) {
            return 0;
        }
        if (!$user->hasRight('customwidget', 'read')) {
            return 0;
        }

        $context = $parameters['context'] ?? '';
        if (strpos($context, 'index') === false && $context !== 'index') {
            return 0;
        }

        require_once dol_buildpath('/customwidget/class/customwidget.class.php', 0);
        require_once dol_buildpath('/customwidget/class/customwidget.helper.class.php', 0);

        $langs->loadLangs(array('customwidget@customwidget'));

        $w_obj = new CustomWidget($db);
        $widgets = $w_obj->fetchAllForUser($user, 'number', 'stats');

        if (empty($widgets)) {
            return 0;
        }

        $html = '<div class="customwidget-stats-zone">';
        foreach ($widgets as $widget) {
            try {
                $html .= CustomWidgetHelper::render($widget, $db, $langs);
            } catch (Exception $e) {
                // Ignorer les erreurs silencieusement sur la page d'accueil
            }
        }
        $html .= '</div>';

        $this->resprints = $html;
        return 1;
    }

    /**
     * Hook addMoreBoxStatsCustom - alternative possible
     */
    public function addMoreBoxStatsCustom($parameters, &$object, &$action, $hookmanager)
    {
        return $this->addStatisticLine($parameters, $object, $action, $hookmanager);
    }
}
