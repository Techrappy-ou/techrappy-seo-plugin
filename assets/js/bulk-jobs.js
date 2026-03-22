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

        // Bouton "Voir erreurs" (bulk parent en échec partiel)
        $(document).on('click', '.bj-btn-errors', function () {
            var jobId  = $(this).data('job-id');
            var $modal = $('#bj-status-modal');
            $('#bj-modal-title').text('Erreurs du job ' + jobId);
            $('#bj-modal-content').html('<p><span class="spinner is-active" style="float:none;vertical-align:middle;"></span> Chargement…</p>');
            $modal.show();

            TechrappySEOAjax(
                'techrappy_get_bulk_errors',
                { nonce: TechrappySEO.nonces.bulk, job_id: jobId },
                function (data) {
                    var errors = data.errors || [];
                    if (!errors.length) {
                        $('#bj-modal-content').html('<p>Aucune erreur trouvée dans les jobs enfants.</p>');
                        return;
                    }
                    var html = '<table class="wp-list-table widefat fixed striped">';
                    html += '<thead><tr><th>Mot-clé</th><th>Ville</th><th>Erreur</th></tr></thead><tbody>';
                    errors.forEach(function (e) {
                        html += '<tr>';
                        html += '<td>' + $('<span>').text(e.keyword).html() + '</td>';
                        html += '<td>' + $('<span>').text(e.city || '—').html() + '</td>';
                        html += '<td style="color:#721c24;font-family:monospace;font-size:11px;">' + $('<span>').text(e.last_error).html() + '</td>';
                        html += '</tr>';
                    });
                    html += '</tbody></table>';
                    html += '<p style="margin-top:8px;font-size:12px;color:#555;">Affichage des 10 premiers jobs en échec.</p>';
                    $('#bj-modal-content').html(html);
                },
                function (err) {
                    $('#bj-modal-content').html('<p style="color:red;">' + ((err && err.message) || 'Erreur.') + '</p>');
                }
            );
        });

        // Bouton relancer (retry — repart de zéro)
        $(document).on('click', '.bj-btn-retry', function () {
            var jobId  = $(this).data('job-id');
            var isBulk = $(this).data('is-bulk') === '1';
            var msg    = isBulk
                ? 'Relancer tous les jobs enfants en échec (repart de zéro) ?'
                : 'Relancer ce job depuis zéro ?';

            if (!window.confirm(msg)) { return; }

            var $btn = $(this).prop('disabled', true).text('…');

            TechrappySEOAjax(
                'techrappy_retry_job',
                { nonce: TechrappySEO.nonces.bulk, job_id: jobId },
                function (data) {
                    $btn.prop('disabled', false).text('↺ Relancer (zéro)');
                    alert(data.message || 'Job relancé.');
                    refreshList();
                },
                function (err) {
                    $btn.prop('disabled', false).text('↺ Relancer (zéro)');
                    alert('Erreur : ' + (err.message || 'Impossible de relancer le job.'));
                }
            );
        });

        // Bouton reprendre (depuis le dernier point de succès)
        $(document).on('click', '.bj-btn-resume', function () {
            var jobId  = $(this).data('job-id');
            var isBulk = $(this).data('is-bulk') === '1';
            var msg    = isBulk
                ? 'Reprendre les jobs bloqués depuis leur dernier point de succès ?'
                : 'Reprendre ce job depuis son dernier point de succès ?';

            if (!window.confirm(msg)) { return; }

            var $btn = $(this).prop('disabled', true).text('…');

            TechrappySEOAjax(
                'techrappy_resume_job',
                { nonce: TechrappySEO.nonces.bulk, job_id: jobId },
                function (data) {
                    $btn.prop('disabled', false).text('↻ Reprendre');
                    alert(data.message || 'Job repris.');
                    refreshList();
                },
                function (err) {
                    $btn.prop('disabled', false).text('↻ Reprendre');
                    alert('Erreur : ' + (err.message || 'Impossible de reprendre le job.'));
                }
            );
        });

        // Bouton pause
        $(document).on('click', '.bj-btn-pause', function () {
            var jobId = $(this).data('job-id');
            if (!window.confirm('Mettre ce job en pause ?')) { return; }

            var $btn = $(this).prop('disabled', true).text('…');

            TechrappySEOAjax(
                'techrappy_pause_job',
                { nonce: TechrappySEO.nonces.bulk, job_id: jobId },
                function (data) {
                    $btn.prop('disabled', false).text('⏸ Pause');
                    alert(data.message || 'Job mis en pause.');
                    refreshList();
                },
                function (err) {
                    $btn.prop('disabled', false).text('⏸ Pause');
                    alert('Erreur : ' + (err.message || 'Impossible de mettre en pause.'));
                }
            );
        });

        // Bouton prioriser
        $(document).on('click', '.bj-btn-prioritize', function () {
            var jobId = $(this).data('job-id');
            if (!window.confirm('Passer ce job en priorité (sera traité en premier) ?')) { return; }

            var $btn = $(this).prop('disabled', true).text('…');

            TechrappySEOAjax(
                'techrappy_prioritize_job',
                { nonce: TechrappySEO.nonces.bulk, job_id: jobId },
                function (data) {
                    $btn.prop('disabled', false).text('⬆ Prioriser');
                    alert(data.message || 'Job priorisé.');
                    refreshList();
                },
                function (err) {
                    $btn.prop('disabled', false).text('⬆ Prioriser');
                    alert('Erreur : ' + (err.message || 'Impossible de prioriser.'));
                }
            );
        });

        // Bouton supprimer
        $(document).on('click', '.bj-btn-delete', function () {
            var jobId  = $(this).data('job-id');
            var isBulk = $(this).data('is-bulk') === '1';
            var msg    = isBulk
                ? 'Supprimer ce job bulk et tous ses jobs enfants ? Cette action est irréversible.'
                : 'Supprimer ce job ? Cette action est irréversible.';

            if (!window.confirm(msg)) { return; }

            var $btn = $(this).prop('disabled', true).text('…');

            TechrappySEOAjax(
                'techrappy_delete_job',
                { nonce: TechrappySEO.nonces.bulk, job_id: jobId },
                function (data) {
                    alert(data.message || 'Job supprimé.');
                    refreshList();
                },
                function (err) {
                    $btn.prop('disabled', false).text('✕ Supprimer');
                    alert('Erreur : ' + (err.message || 'Impossible de supprimer.'));
                }
            );
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

        // Auto-refresh si des jobs sont en cours (toutes les 8s).
        var hasRunning = $('[data-status="running"], [data-status="pending"]').length > 0;
        if (hasRunning) {
            setTimeout(refreshList, 8000);
        } else {
            // Rafraîchir une fois après 5s au cas où un job vient d'être créé.
            setTimeout(function () {
                var stillRunning = $('[data-status="running"], [data-status="pending"]').length > 0;
                if (stillRunning) { refreshList(); }
            }, 5000);
        }
    }

    $(document).ready(init);

})(jQuery);
