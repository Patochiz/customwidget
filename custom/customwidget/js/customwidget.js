/* CustomWidget - JavaScript
 * IMPORTANT : Ne pas utiliser let/const au top-level (compatibilité Dolibarr)
 */

var customwidget_loaded = customwidget_loaded || false;

/* ========================
   Test de requête SQL
   ======================== */
function cwTestQuery() {
    var sql = document.getElementById('cw_sql_query') ? document.getElementById('cw_sql_query').value : '';
    var resultDiv = document.getElementById('cw-query-result');
    if (!sql || !resultDiv) return;

    resultDiv.style.display = 'block';
    resultDiv.innerHTML = '<div class="cw-query-meta">Exécution en cours...</div>';

    var fd = new FormData();
    fd.append('sql', sql);
    fd.append('maxrows', '20');
    fd.append('token', typeof cw_token !== 'undefined' ? cw_token : '');

    fetch(typeof cw_ajax_testquery_url !== 'undefined' ? cw_ajax_testquery_url : '', {
        method: 'POST',
        body: fd
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (!data.success) {
            resultDiv.innerHTML = '<div class="cw-query-error">Erreur : ' + cwEscape(data.error) + '</div>';
            return;
        }
        var html = '<div class="cw-query-meta">';
        html += data.num_rows + ' ligne(s) retournée(s) en ' + data.execution_time + ' ms';
        html += '</div>';

        if (data.columns && data.columns.length > 0) {
            html += '<table>';
            html += '<thead><tr>';
            for (var c = 0; c < data.columns.length; c++) {
                html += '<th>' + cwEscape(data.columns[c]) + '</th>';
            }
            html += '</tr></thead><tbody>';
            for (var r = 0; r < data.rows.length; r++) {
                html += '<tr>';
                for (var v = 0; v < data.rows[r].length; v++) {
                    html += '<td>' + cwEscape(data.rows[r][v] !== null ? data.rows[r][v] : '') + '</td>';
                }
                html += '</tr>';
            }
            html += '</tbody></table>';
        } else {
            html += '<div class="opacitymedium">Aucun résultat</div>';
        }
        resultDiv.innerHTML = html;
    })
    .catch(function(e) {
        resultDiv.innerHTML = '<div class="cw-query-error">Erreur réseau : ' + cwEscape(e.message) + '</div>';
    });
}

/* ========================
   Prévisualisation widget
   ======================== */
function cwPreview() {
    var previewZone = document.getElementById('cw-preview-zone');
    var previewContent = document.getElementById('cw-preview-content');
    if (!previewZone || !previewContent) return;

    previewZone.style.display = 'block';
    previewContent.innerHTML = 'Chargement...';

    var form = document.getElementById('customwidget-card-form');
    var fd = new FormData(form);
    // Ajouter les couleurs du graphique
    var colorInputs = document.querySelectorAll('#cw-chart-colors input[type="color"]');
    fd.delete('chart_color[]');
    for (var i = 0; i < colorInputs.length; i++) {
        fd.append('chart_color[]', colorInputs[i].value);
    }
    // Construire table_columns JSON
    fd.delete('table_columns');
    var colNames = document.querySelectorAll('#cw-col-body input[name="col_name[]"]');
    var colLabels = document.querySelectorAll('#cw-col-body input[name="col_label[]"]');
    var colTypes = document.querySelectorAll('#cw-col-body select[name="col_type[]"]');
    var colLinks = document.querySelectorAll('#cw-col-body input[name="col_link[]"]');
    var cols = [];
    for (var j = 0; j < colNames.length; j++) {
        if (colNames[j].value) {
            cols.push({
                name: colNames[j].value,
                label: colLabels[j] ? colLabels[j].value : colNames[j].value,
                type: colTypes[j] ? colTypes[j].value : 'text',
                link: colLinks[j] ? colLinks[j].value : ''
            });
        }
    }
    fd.append('table_columns', JSON.stringify(cols));

    fetch(typeof cw_ajax_preview_url !== 'undefined' ? cw_ajax_preview_url : '', {
        method: 'POST',
        body: fd
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (!data.success) {
            previewContent.innerHTML = '<div class="error">Erreur : ' + cwEscape(data.error) + '</div>';
            return;
        }
        previewContent.innerHTML = data.html;
        // Ré-exécuter les scripts injectés (pour Chart.js)
        var scripts = previewContent.querySelectorAll('script');
        for (var s = 0; s < scripts.length; s++) {
            var sc = document.createElement('script');
            sc.textContent = scripts[s].textContent;
            document.head.appendChild(sc);
        }
    })
    .catch(function(e) {
        previewContent.innerHTML = '<div class="error">Erreur réseau : ' + cwEscape(e.message) + '</div>';
    });
}

/* ========================
   Sections dynamiques (card)
   ======================== */
function cwUpdateSections() {
    var type = document.getElementById('cw_widget_type') ? document.getElementById('cw_widget_type').value : 'number';
    var sections = document.querySelectorAll('.cw-type-section');
    for (var i = 0; i < sections.length; i++) {
        sections[i].style.display = 'none';
    }
    var active = document.getElementById('cw-section-' + type);
    if (active) active.style.display = '';

    // Zone stats seulement pour number
    var zoneSelect = document.getElementById('cw_display_zone');
    if (zoneSelect) {
        var statsOpt = zoneSelect.querySelector('option[value="stats"]');
        if (statsOpt) {
            statsOpt.disabled = (type !== 'number');
            if (type !== 'number' && zoneSelect.value === 'stats') {
                zoneSelect.value = 'box';
            }
        }

        // Masquer le sélecteur box_position si zone != 'box'
        var bpRow = document.getElementById('cw_box_position_row');
        if (bpRow) {
            bpRow.style.display = (zoneSelect.value === 'box') ? '' : 'none';
        }
    }
}

// Appel initial
if (document.getElementById('cw_widget_type')) {
    cwUpdateSections();
}

/* ========================
   Gestion colonnes tableau
   ======================== */
function cwAddColumn() {
    var tbody = document.getElementById('cw-col-body');
    if (!tbody) return;
    var tr = document.createElement('tr');
    tr.className = 'oddeven';

    var typeOptions = '';
    if (typeof cw_col_types !== 'undefined') {
        for (var v in cw_col_types) {
            typeOptions += '<option value="' + cwEscape(v) + '">' + cwEscape(cw_col_types[v]) + '</option>';
        }
    } else {
        typeOptions = '<option value="text">Texte</option><option value="integer">Entier</option><option value="price">Prix</option><option value="date">Date</option>';
    }

    tr.innerHTML = '<td><input type="text" name="col_name[]" class="flat"></td>'
        + '<td><input type="text" name="col_label[]" class="flat"></td>'
        + '<td><select name="col_type[]" class="flat">' + typeOptions + '</select></td>'
        + '<td><input type="text" name="col_link[]" class="flat"></td>'
        + '<td><button type="button" class="button buttonDelete" onclick="cwRemoveRow(this)">✕</button></td>';
    tbody.appendChild(tr);
}

/* ========================
   Gestion couleurs graphique
   ======================== */
function cwAddColor() {
    var container = document.getElementById('cw-chart-colors');
    if (!container) return;
    var div = document.createElement('div');
    div.className = 'cw-color-row';
    div.style.display = 'inline-block';
    div.style.margin = '3px';
    div.innerHTML = '<input type="color" name="chart_color[]" value="#0077b6"> '
        + '<button type="button" class="button buttonDelete" onclick="cwRemoveRow(this)">✕</button>';
    container.appendChild(div);
}

/* ========================
   Suppression de ligne
   ======================== */
function cwRemoveRow(btn) {
    var row = btn.closest('tr, .cw-color-row');
    if (row) row.remove();
}

/* ========================
   Drag & drop liste
   ======================== */
(function() {
    var reorderUrl = typeof cw_ajax_reorder_url !== 'undefined' ? cw_ajax_reorder_url : '';
    var sortable = document.getElementById('customwidget-sortable');
    if (!sortable || !reorderUrl) return;

    var dragged = null;

    sortable.addEventListener('dragstart', function(e) {
        dragged = e.target.closest('tr');
        if (dragged) dragged.classList.add('dragging');
    });

    sortable.addEventListener('dragend', function() {
        if (dragged) dragged.classList.remove('dragging');
        dragged = null;
    });

    sortable.addEventListener('dragover', function(e) {
        e.preventDefault();
        var target = e.target.closest('tr');
        if (target && target !== dragged && sortable.contains(target)) {
            var rect = target.getBoundingClientRect();
            var after = e.clientY > rect.top + rect.height / 2;
            sortable.insertBefore(dragged, after ? target.nextSibling : target);
        }
    });

    sortable.addEventListener('drop', function(e) {
        e.preventDefault();
        var rows = sortable.querySelectorAll('tr[data-id]');
        var order = [];
        for (var i = 0; i < rows.length; i++) {
            order.push(rows[i].dataset.id);
        }
        var fd = new FormData();
        fd.append('order', JSON.stringify(order));
        fd.append('token', typeof cw_token !== 'undefined' ? cw_token : '');
        fetch(reorderUrl, { method: 'POST', body: fd })
            .then(function(r) { return r.json(); })
            .catch(function() {});
    });

    // Rendre les lignes draggables
    var rows = sortable.querySelectorAll('tr[data-id]');
    for (var i = 0; i < rows.length; i++) {
        rows[i].draggable = true;
    }
})();

/* ========================
   Rafraîchir un widget (dashboard)
   ======================== */
function cwRefreshWidget(widgetId) {
    var container = document.querySelector('.customwidget-box-item[data-widget-id="' + widgetId + '"]');
    if (!container) return;
    var btn = container.querySelector('.cw-refresh-btn');
    if (btn) btn.classList.add('cw-spinning');

    var fd = new FormData();
    fd.append('widget_id', widgetId);
    fd.append('token', typeof cw_token !== 'undefined' ? cw_token : '');

    var refreshUrl = container.getAttribute('data-refresh-url');
    if (!refreshUrl) {
        if (btn) btn.classList.remove('cw-spinning');
        return;
    }

    fetch(refreshUrl, {
        method: 'POST',
        body: fd
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (btn) btn.classList.remove('cw-spinning');
        if (!data.success) return;
        // Supprimer l'ancien contenu (sauf le bouton refresh)
        var children = Array.prototype.slice.call(container.childNodes);
        for (var i = 0; i < children.length; i++) {
            if (children[i] !== btn) {
                container.removeChild(children[i]);
            }
        }
        // Insérer le nouveau contenu
        var temp = document.createElement('div');
        temp.innerHTML = data.html;
        while (temp.firstChild) {
            container.appendChild(temp.firstChild);
        }
        // Ré-exécuter les scripts injectés (Chart.js)
        var scripts = container.querySelectorAll('script');
        for (var s = 0; s < scripts.length; s++) {
            var sc = document.createElement('script');
            sc.textContent = scripts[s].textContent;
            document.head.appendChild(sc);
        }
    })
    .catch(function() {
        if (btn) btn.classList.remove('cw-spinning');
    });
}

/* ========================
   Utilitaires
   ======================== */
function cwEscape(str) {
    if (str === null || str === undefined) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}
