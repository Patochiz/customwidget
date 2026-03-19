<?php
/**
 * Classe helper pour CustomWidget
 * Validation SQL, exécution, formatage, cache, rendu
 */

class CustomWidgetHelper
{
    /**
     * Valide une requête SQL SELECT
     */
    public static function validateQuery($sql)
    {
        // Trim + suppression commentaires SQL
        $sql = trim($sql);
        $sql = preg_replace('/--[^\n]*/', '', $sql);
        $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);
        $sql = trim($sql);

        // Remplacement __PREFIX__
        $sql = str_replace('__PREFIX__', MAIN_DB_PREFIX, $sql);

        // Doit commencer par SELECT
        if (!preg_match('/^SELECT\s/i', $sql)) {
            return array('valid' => false, 'error' => 'La requête doit commencer par SELECT', 'sql' => $sql);
        }

        // Mots-clés interdits
        $forbidden = array(
            'INSERT', 'UPDATE', 'DELETE', 'DROP', 'ALTER', 'TRUNCATE', 'CREATE',
            'EXEC', 'EXECUTE', 'GRANT', 'REVOKE', 'RENAME', 'LOAD', 'OUTFILE',
            'DUMPFILE', 'INTO',
        );
        foreach ($forbidden as $kw) {
            if (preg_match('/\b'.$kw.'\b/i', $sql)) {
                return array('valid' => false, 'error' => 'Mot-clé interdit : '.$kw, 'sql' => $sql);
            }
        }

        // Vérification JOIN si désactivé
        global $conf;
        if (empty($conf->global->CUSTOMWIDGET_ALLOW_JOIN) || $conf->global->CUSTOMWIDGET_ALLOW_JOIN == '0') {
            if (preg_match('/\bJOIN\b/i', $sql)) {
                return array('valid' => false, 'error' => 'Les JOIN sont désactivés dans la configuration', 'sql' => $sql);
            }
        }

        return array('valid' => true, 'error' => '', 'sql' => $sql);
    }

    /**
     * Exécute une requête SELECT validée
     */
    public static function executeQuery($db, $sql, $maxrows = 100)
    {
        $maxrows = min((int) $maxrows, 500);

        // Ajouter LIMIT si absent
        if (!preg_match('/\bLIMIT\s+\d+/i', $sql)) {
            $sql .= ' LIMIT '.$maxrows;
        }

        $t_start = microtime(true);
        $resql = $db->query($sql);
        $t_end = microtime(true);
        $exec_time = round(($t_end - $t_start) * 1000, 2);

        if (!$resql) {
            return array(
                'columns' => array(),
                'rows' => array(),
                'num_rows' => 0,
                'error' => $db->lasterror(),
                'execution_time' => $exec_time,
            );
        }

        $num = $db->num_rows($resql);
        $columns = array();
        $rows = array();

        if ($num > 0) {
            $first = $db->fetch_array($resql);
            if ($first) {
                $columns = array_keys($first);
                $rows[] = array_values($first);
                while ($row = $db->fetch_array($resql)) {
                    $rows[] = array_values($row);
                }
            }
        }

        return array(
            'columns' => $columns,
            'rows' => $rows,
            'num_rows' => count($rows),
            'error' => '',
            'execution_time' => $exec_time,
        );
    }

    /**
     * Formate une valeur selon son type
     */
    public static function formatValue($value, $type, $langs)
    {
        if (is_null($value)) {
            return '<span class="opacitymedium">-</span>';
        }
        switch ($type) {
            case 'price':
                return price($value).' '.$langs->getCurrencySymbol($langs->defaultlang);
            case 'percentage':
                return htmlspecialchars($value).' %';
            case 'date':
                if (preg_match('/^\d{4}-\d{2}-\d{2}/', $value)) {
                    return dol_print_date($value, 'day');
                }
                return htmlspecialchars($value);
            case 'integer':
                return number_format((int) $value, 0, ',', ' ');
            case 'status':
                $v = (int) $value;
                $color = $v > 0 ? 'badge-status4' : 'badge-status0';
                return '<span class="badge '.$color.'">'.htmlspecialchars($value).'</span>';
            case 'text':
            default:
                return htmlspecialchars($value);
        }
    }

    /**
     * Génère le HTML pour un widget de type "number" (KPI)
     */
    public static function renderNumber($widget, $db, $langs)
    {
        $validation = self::validateQuery($widget->sql_query);
        if (!$validation['valid']) {
            return '<div class="error">'.htmlspecialchars($validation['error']).'</div>';
        }

        $result = self::executeQuery($db, $validation['sql'], 1);
        if ($result['error']) {
            return '<div class="error">'.htmlspecialchars($result['error']).'</div>';
        }

        $value = '';
        if (!empty($result['rows'][0])) {
            $value = $result['rows'][0][0];
        }

        $color = htmlspecialchars($widget->number_color ?: '#0077b6');
        $suffix = htmlspecialchars($widget->number_suffix);
        $icon = htmlspecialchars($widget->number_icon);
        $url = htmlspecialchars($widget->number_url);

        $html = '<div class="customwidget-kpi" style="border-top: 3px solid '.$color.';">';
        if ($url) {
            $html .= '<a href="'.$url.'" style="text-decoration:none;color:inherit;">';
        }
        if ($icon) {
            if (strpos($icon, 'fa-') === 0) {
                $html .= '<div class="cw-icon" style="color:'.$color.';font-size:1.5em;"><i class="fas '.$icon.'"></i></div>';
            } else {
                $html .= '<div class="cw-icon" style="font-size:1.5em;">'.$icon.'</div>';
            }
        }
        $html .= '<div class="cw-value" style="color:'.$color.';">'.htmlspecialchars($value).$suffix.'</div>';
        $html .= '<div class="cw-label">'.htmlspecialchars($widget->label).'</div>';

        // Sous-indicateurs
        $subs = array();
        if ($widget->number_sub1_sql) {
            $v1 = self::validateQuery($widget->number_sub1_sql);
            if ($v1['valid']) {
                $r1 = self::executeQuery($db, $v1['sql'], 1);
                if (!$r1['error'] && isset($r1['rows'][0][0])) {
                    $subs[] = htmlspecialchars($widget->number_sub1_label).': '.htmlspecialchars($r1['rows'][0][0]);
                }
            }
        }
        if ($widget->number_sub2_sql) {
            $v2 = self::validateQuery($widget->number_sub2_sql);
            if ($v2['valid']) {
                $r2 = self::executeQuery($db, $v2['sql'], 1);
                if (!$r2['error'] && isset($r2['rows'][0][0])) {
                    $subs[] = htmlspecialchars($widget->number_sub2_label).': '.htmlspecialchars($r2['rows'][0][0]);
                }
            }
        }
        if ($subs) {
            $html .= '<div class="cw-sub">'.implode(' | ', $subs).'</div>';
        }

        if ($url) {
            $html .= '</a>';
        }
        $html .= '</div>';

        return $html;
    }

    /**
     * Génère le HTML pour un widget de type "table"
     */
    public static function renderTable($widget, $db, $langs)
    {
        $maxrows = !empty($widget->table_maxrows) ? (int) $widget->table_maxrows : 10;
        $validation = self::validateQuery($widget->sql_query);
        if (!$validation['valid']) {
            return '<div class="error">'.htmlspecialchars($validation['error']).'</div>';
        }

        $result = self::executeQuery($db, $validation['sql'], $maxrows);
        if ($result['error']) {
            return '<div class="error">'.htmlspecialchars($result['error']).'</div>';
        }

        // Configuration colonnes
        $col_config = array();
        if ($widget->table_columns) {
            $decoded = json_decode($widget->table_columns, true);
            if (is_array($decoded)) {
                $col_config = $decoded;
            }
        }

        $html = '<div class="div-table-responsive">';
        $html .= '<table class="tagtable liste">';
        $html .= '<thead><tr class="liste_titre">';
        foreach ($result['columns'] as $i => $col) {
            $label = isset($col_config[$i]['label']) ? $col_config[$i]['label'] : $col;
            $html .= '<th>'.htmlspecialchars($label).'</th>';
        }
        $html .= '</tr></thead>';
        $html .= '<tbody>';

        $tr = 0;
        foreach ($result['rows'] as $row) {
            $html .= '<tr class="'.($tr % 2 == 0 ? 'oddeven' : 'oddeven').'">';
            foreach ($row as $i => $val) {
                $type = isset($col_config[$i]['type']) ? $col_config[$i]['type'] : 'text';
                $link = isset($col_config[$i]['link']) ? $col_config[$i]['link'] : '';
                $formatted = self::formatValue($val, $type, $langs);
                if ($link) {
                    $href = str_replace(array('{value}', '{rowid}'), array(urlencode($val), isset($row[0]) ? urlencode($row[0]) : ''), $link);
                    $formatted = '<a href="'.htmlspecialchars($href).'">'.$formatted.'</a>';
                }
                $html .= '<td>'.$formatted.'</td>';
            }
            $html .= '</tr>';
            $tr++;
        }

        if (empty($result['rows'])) {
            $html .= '<tr><td colspan="'.count($result['columns']).'" class="opacitymedium center">'.$langs->trans('NoRecordFound').'</td></tr>';
        }

        $html .= '</tbody></table></div>';

        return $html;
    }

    /**
     * Génère le HTML/JS pour un widget de type "chart" (Chart.js)
     */
    public static function renderChart($widget, $db, $langs)
    {
        global $conf;

        $validation = self::validateQuery($widget->sql_query);
        if (!$validation['valid']) {
            return '<div class="error">'.htmlspecialchars($validation['error']).'</div>';
        }

        $result = self::executeQuery($db, $validation['sql'], 500);
        if ($result['error']) {
            return '<div class="error">'.htmlspecialchars($result['error']).'</div>';
        }

        $label_col = (int) $widget->chart_label_col;
        $data_col = (int) $widget->chart_data_col;
        $labels = array();
        $data = array();

        foreach ($result['rows'] as $row) {
            $labels[] = isset($row[$label_col]) ? $row[$label_col] : '';
            $data[] = isset($row[$data_col]) ? (float) $row[$data_col] : 0;
        }

        $default_colors = array('#0077b6', '#00b4d8', '#90e0ef', '#caf0f8', '#023e8a', '#48cae4', '#ade8f4', '#0096c7');
        $colors = $default_colors;
        if ($widget->chart_colors) {
            $decoded = json_decode($widget->chart_colors, true);
            if (is_array($decoded) && count($decoded) > 0) {
                $colors = $decoded;
            }
        }

        $chart_type = in_array($widget->chart_type, array('bar', 'line', 'doughnut', 'pie')) ? $widget->chart_type : 'bar';
        $height = max(100, (int) $widget->chart_height);
        $chart_id = 'customwidget_chart_'.(int) $widget->id;

        // Inclure Chart.js
        $chartjs_url = '';
        if (!empty($conf->global->CUSTOMWIDGET_CHARTJS_CDN)) {
            $chartjs_url = $conf->global->CUSTOMWIDGET_CHARTJS_CDN;
        } else {
            $chartjs_url = dol_buildpath('/customwidget/js/chart.min.js', 1);
        }

        $labels_json = json_encode($labels);
        $data_json = json_encode($data);
        $colors_json = json_encode($colors);

        $html = '<div class="customwidget-chart" style="position:relative;height:'.$height.'px;">';
        $html .= '<canvas id="'.htmlspecialchars($chart_id).'"></canvas>';
        $html .= '</div>';
        $html .= '<script>';
        $html .= 'if (typeof customwidget_chartjs_loaded === "undefined") { var customwidget_chartjs_loaded = false; }';
        $html .= '(function() {';
        $html .= '  function initChart_'.preg_replace('/[^a-z0-9_]/i', '_', $chart_id).'() {';
        $html .= '    if (typeof Chart === "undefined") return;';
        $html .= '    var ctx = document.getElementById("'.htmlspecialchars($chart_id).'");';
        $html .= '    if (!ctx) return;';
        $html .= '    new Chart(ctx, {';
        $html .= '      type: "'.htmlspecialchars($chart_type).'",';
        $html .= '      data: {';
        $html .= '        labels: '.$labels_json.',';
        $html .= '        datasets: [{';
        $html .= '          data: '.$data_json.',';
        $html .= '          backgroundColor: '.$colors_json.',';
        $html .= '          borderColor: '.$colors_json.',';
        $html .= '          borderWidth: 1';
        $html .= '        }]';
        $html .= '      },';
        $html .= '      options: {';
        $html .= '        responsive: true,';
        $html .= '        maintainAspectRatio: false,';
        $html .= '        plugins: { legend: { display: true } }';
        $html .= '      }';
        $html .= '    });';
        $html .= '  }';
        $html .= '  if (typeof Chart !== "undefined") {';
        $html .= '    initChart_'.preg_replace('/[^a-z0-9_]/i', '_', $chart_id).'();';
        $html .= '  } else if (!customwidget_chartjs_loaded) {';
        $html .= '    customwidget_chartjs_loaded = true;';
        $html .= '    var s = document.createElement("script");';
        $html .= '    s.src = "'.addslashes($chartjs_url).'";';
        $html .= '    s.onload = function() { initChart_'.preg_replace('/[^a-z0-9_]/i', '_', $chart_id).'(); };';
        $html .= '    document.head.appendChild(s);';
        $html .= '  } else {';
        $html .= '    var t = setInterval(function() { if(typeof Chart!=="undefined"){clearInterval(t);initChart_'.preg_replace('/[^a-z0-9_]/i', '_', $chart_id).'();}},100);';
        $html .= '  }';
        $html .= '})();';
        $html .= '</script>';

        return $html;
    }

    /**
     * Gestion du cache fichier
     */
    public static function cached($key, $duration, $callback)
    {
        if ($duration <= 0) {
            return call_user_func($callback);
        }

        $cache_dir = DOL_DATA_ROOT.'/customwidget/cache';
        if (!is_dir($cache_dir)) {
            dol_mkdir($cache_dir);
        }

        $cache_file = $cache_dir.'/'.preg_replace('/[^a-zA-Z0-9_-]/', '_', $key).'.html';

        if (file_exists($cache_file) && (dol_now() - filemtime($cache_file)) < $duration) {
            return file_get_contents($cache_file);
        }

        $content = call_user_func($callback);
        file_put_contents($cache_file, $content);
        return $content;
    }

    /**
     * Purge le cache d'un widget
     */
    public static function purgeCache($widget_id)
    {
        $cache_file = DOL_DATA_ROOT.'/customwidget/cache/widget_'.(int) $widget_id.'.html';
        if (file_exists($cache_file)) {
            @unlink($cache_file);
        }
    }

    /**
     * Rendu d'un widget avec gestion cache
     */
    public static function render($widget, $db, $langs)
    {
        $wid = $widget;
        $duration = (int) $widget->cache_duration;
        $key = 'widget_'.(int) $widget->id;

        return self::cached($key, $duration, function () use ($wid, $db, $langs) {
            if ($wid->widget_type === 'table') {
                return CustomWidgetHelper::renderTable($wid, $db, $langs);
            } elseif ($wid->widget_type === 'chart') {
                return CustomWidgetHelper::renderChart($wid, $db, $langs);
            } else {
                return CustomWidgetHelper::renderNumber($wid, $db, $langs);
            }
        });
    }
}
