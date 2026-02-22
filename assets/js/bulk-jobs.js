/* assets/js/bulk-jobs.js — Techrappy SEO Bulk Jobs */
/* global TechrappySEO, TechrappySEOAjax */
(function ($) {
    'use strict';

    var pollTimers = {};

    // ──────────────────────────────────────────────────────────────────────────
    // Rafraîchissement de la liste
    // ──────────────────────────────────────────────────────────────────────────

    function refreshList() {
        location.reload();
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Modal statut
    // ──────────────────────────────────────────────────────────────────────────

    function openModal(jobId) {
        var $modal = $('#bj-status-modal');
        $('#bj-modal-title').text('Statut du job ' + jobId);
        $('#bj-modal-content').html('<p><span class="spinner is-active" style="float:none;vertical-align:middle;"></span> Chargement…</p>');
        $modal.show();
        loadJobStatus(jobId);
    }

    function closeModal() {
        $('#bj-status-modal').hide();
        // Arrêter les pollings des modaux
        Object.keys(pollTimers).forEach(function (id) {
            clearTimeout(pollTimers[id]);
            delete pollTimers[id];
        });
    }

    function loadJobStatus(jobId) {
        TechrappySEOAjax(
            'techrappy_get_job_status',
            { nonce: TechrappySEO.nonces.bulk, job_id: jobId },
            function (data) {
                var html = '';
                var status  = data.status || '—';
                var steps   = data.steps  || {};
                var result  = data.result || {};
                var logs    = data.logs   || [];

                html += '<p><strong>Statut :</strong> ' + status + '</p>';

                // Progression bulk
                if (steps._bulk_total) {
                    var pct = steps._bulk_progress || 0;
                    html += '<p><strong>Progression :</strong> ' + (steps._bulk_done || 0) + '/' + steps._bulk_total + ' (' + pct + '%)</p>';
                    html += '<div style="background:#e2e3e5;border-radius:4px;height:8px;margin-bottom:10px;">';
                    html += '<div style="background:#0073aa;height:8px;border-radius:4px;width:' + pct + '%;transition:width .3s;"></div></div>';
                }

                // Lien vers le post
                if (result.permalink) {
                    html += '<p><a href="' + result.permalink + '" target="_blank" class="button button-primary">Voir le post</a></p>';
                }

                // Étapes
                var stepKeys = Object.keys(steps).filter(function (k) { return k.charAt(0) !== '_'; });
                if (stepKeys.length) {
                    html += '<table class="wp-list-table widefat fixed striped" style="margin-bottom:12px;">';
                    html += '<thead><tr><th>Étape</th><th style="width:80px;">Statut</th></tr></thead><tbody>';
                    stepKeys.forEach(function (k) {
                        var s = (typeof steps[k] === 'object' && steps[k] !== null) ? (steps[k].status || '—') : '—';
                        html += '<tr><td><code>' + k + '</code></td><td>' + s + '</td></tr>';
                    });
                    html += '</tbody></table>';
                }

                // Derniers logs
                if (logs.length) {
                    html += '<div style="max-height:150px;overflow-y:auto;background:#f6f7f7;padding:8px;font-family:monospace;font-size:11px;border:1px solid #ddd;">';
                    logs.forEach(function (log) {
                        var lvl = log.level || 'info';
                        html += '<div class="log-' + lvl + '">[' + lvl.toUpperCase() + '][' + (log.step || '') + '] ' + (log.msg || '') + '</div>';
                    });
                    html += '</div>';
                }

                $('#bj-modal-content').html(html);

                // Continuer le polling si job en cours
                if (status === 'running' || status === 'pending') {
                    pollTimers[jobId] = setTimeout(function () {
                        if ($('#bj-status-modal').is(':visible')) {
                            loadJobStatus(jobId);
                        }
                    }, 5000);
                }
            },
            function (err) {
                $('#bj-modal-content').html('<p style="color:red;">' + (err.message || 'Erreur.') + '</p>');
            }
        );
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Init
    // ──────────────────────────────────────────────────────────────────────────

    function init() {
        if (!$('#techrappy-bulk-jobs').length) { return; }

        // Bouton rafraîchir
        $('#bj-btn-refresh').on('click', refreshList);

        // Bouton statut
        $(document).on('click', '.bj-btn-status', function () {
            var jobId = $(this).data('job-id');
            openModal(jobId);
        });

        // Fermer le modal
        $('#bj-modal-close').on('click', closeModal);

        // Fermer en cliquant dehors
        $(document).on('click', function (e) {
            var $modal = $('#bj-status-modal');
            if ($modal.is(':visible') && !$(e.target).closest('#bj-status-modal, .bj-btn-status').length) {
                closeModal();
            }
        });

        // Auto-refresh si des jobs sont en cours
        var hasRunning = false;
        $('[data-status="running"], [data-status="pending"]').each(function () {
            hasRunning = true;
        });
        if (hasRunning) {
            setTimeout(refreshList, 15000);
        }
    }

    $(document).ready(init);

})(jQuery);
