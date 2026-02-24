/* assets/js/wizard.js — Techrappy SEO Wizard */
/* global TechrappySEO, TechrappySEOAjax */
(function ($) {
    'use strict';

    var TOTAL_STEPS = 6;
    var currentStep = 1;
    var currentJobId = null;
    var pollTimer = null;

    // ──────────────────────────────────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────────────────────────────────

    function showStep(n) {
        for (var i = 1; i <= TOTAL_STEPS; i++) {
            $('#wz-step-' + i).hide();
        }
        $('#wz-step-' + n).show();

        // Onglets de navigation
        $('.techrappy-wizard-step').removeClass('active');
        $('.techrappy-wizard-step[data-step="' + n + '"]').addClass('active');

        // Boutons nav
        if (n <= 1) {
            $('#wz-btn-prev').hide();
        } else {
            $('#wz-btn-prev').show();
        }

        if (n === TOTAL_STEPS - 1) {
            $('#wz-btn-next').hide();
            $('#wz-btn-launch').show();
        } else if (n >= TOTAL_STEPS) {
            $('#wz-btn-prev').hide();
            $('#wz-btn-next').hide();
            $('#wz-btn-launch').hide();
        } else {
            $('#wz-btn-next').show();
            $('#wz-btn-launch').hide();
        }

        currentStep = n;
    }

    function showNotice(msg, type) {
        type = type || 'error';
        $('#techrappy-wizard-notice')
            .html('<div class="notice notice-' + type + ' is-dismissible"><p>' + msg + '</p></div>');
    }

    function clearNotice() {
        $('#techrappy-wizard-notice').html('');
    }

    function getMode() {
        return $('input[name="wz_mode"]:checked').val() || 'single';
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Étape 1 : validation
    // ──────────────────────────────────────────────────────────────────────────

    function validateStep1() {
        var keyword = $('#wz_keyword').val().trim();
        if (!keyword) {
            showNotice('Le mot-clé principal est requis.');
            return false;
        }
        var profession = $('#wz_profession').val().trim();
        if (!profession) {
            showNotice('La profession / secteur est requise.');
            return false;
        }
        if (getMode() === 'bulk' && !$('#wz_ville_principale').val().trim()) {
            showNotice('La ville principale est requise pour la génération en masse.');
            return false;
        }
        return true;
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Étape 2 : chargement des templates
    // ──────────────────────────────────────────────────────────────────────────

    function loadTemplates() {
        var $spinner = $('#wz_template_spinner');
        var $select  = $('#wz_template_post_id');

        $spinner.addClass('is-active');
        $select.prop('disabled', true);

        TechrappySEOAjax(
            'techrappy_wizard_step',
            { nonce: TechrappySEO.nonces.wizard, wizard_action: 'get_templates' },
            function (data) {
                $spinner.removeClass('is-active');
                $select.prop('disabled', false);
                $select.find('option:not(:first)').remove();
                $.each(data.templates || [], function (i, tpl) {
                    $select.append($('<option>', { value: tpl.id, text: tpl.title + ' (' + tpl.type + ')' }));
                });
            },
            function () {
                $spinner.removeClass('is-active');
                $select.prop('disabled', false);
            }
        );
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Étape 3 : audit du template
    // ──────────────────────────────────────────────────────────────────────────

    function runAudit() {
        var templateId = parseInt($('#wz_template_post_id').val(), 10);
        var $container = $('#wz_audit_result');

        if (!templateId) {
            $container.html('<p class="description">Aucun template sélectionné — l\'HTML brut sera utilisé.</p>');
            return;
        }

        $container.html('<p><span class="spinner is-active" style="float:none;vertical-align:middle;"></span> Analyse en cours…</p>');

        TechrappySEOAjax(
            'techrappy_scan_template',
            { nonce: TechrappySEO.nonces.audit, post_id: templateId },
            function (data) {
                var html = '';

                if (data.has_divi) {
                    html += '<div style="display:flex;gap:16px;margin-bottom:12px;">';
                    html += '<div style="flex:1;border:1px solid #c3e6cb;background:#d4edda;padding:12px;border-radius:4px;">';
                    html += '<strong>✓ Tokens trouvés</strong><ul style="margin:6px 0 0;padding-left:18px;">';
                    $.each(data.tokens_found || [], function (i, t) { html += '<li><code>{{' + t + '}}</code></li>'; });
                    html += '</ul></div>';
                    html += '<div style="flex:1;border:1px solid #f5c6cb;background:#f8d7da;padding:12px;border-radius:4px;">';
                    html += '<strong>✗ Tokens manquants</strong>';
                    if (data.tokens_missing && data.tokens_missing.length) {
                        html += '<ul style="margin:6px 0 0;padding-left:18px;">';
                        $.each(data.tokens_missing, function (i, t) { html += '<li><code>{{' + t + '}}</code></li>'; });
                        html += '</ul>';
                    } else {
                        html += '<p style="color:#155724;margin:6px 0 0;">Tous les tokens requis sont présents.</p>';
                    }
                    html += '</div></div>';
                    if (data.is_valid) {
                        html += '<div class="notice notice-success inline"><p>✓ Template valide.</p></div>';
                    } else {
                        html += '<div class="notice notice-warning inline"><p>⚠ Template incomplet (tokens manquants).</p></div>';
                    }
                } else {
                    html += '<div class="notice notice-warning inline"><p>Ce template ne contient pas de shortcodes Divi. L\'HTML brut sera utilisé.</p></div>';
                }

                $container.html(html);
            },
            function (err) {
                $container.html('<div class="notice notice-error inline"><p>' + (err.message || 'Erreur lors de l\'audit.') + '</p></div>');
            }
        );
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Étape 5 : résumé
    // ──────────────────────────────────────────────────────────────────────────

    function fillSummary() {
        $('#s_mode').text(getMode() === 'bulk' ? 'Masse (bulk)' : 'Page unique');
        $('#s_keyword').text($('#wz_keyword').val().trim());
        $('#s_profession').text($('#wz_profession').val().trim());
        $('#s_type').text($('#wz_type').val());
        $('#s_template').text($('#wz_template_post_id option:selected').text());
        $('#s_status').text($('#wz_publish_status').val() === 'publish' ? 'Publié immédiatement' : 'Brouillon');
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Étape 6 : lancement & polling
    // ──────────────────────────────────────────────────────────────────────────

    function appendLog(msg, level) {
        var $log = $('#wz_log_output');
        $log.append('<div class="log-' + (level || 'info') + '">' + msg + '</div>');
        $log.scrollTop($log[0].scrollHeight);
    }

    function launchGeneration() {
        clearNotice();
        if (getMode() === 'bulk') {
            launchBulk();
        } else {
            launchSingle();
        }
    }

    function launchSingle() {
        var data = {
            nonce:            TechrappySEO.nonces.wizard,
            wizard_action:    'create_job',
            keyword:          $('#wz_keyword').val().trim(),
            profession:       $('#wz_profession').val().trim(),
            type:             $('#wz_type').val(),
            city:             $('#wz_city').val().trim(),
            template_post_id: $('#wz_template_post_id').val(),
            publish_status:   $('#wz_publish_status').val(),
            slug_rule:        $('#wz_slug_rule').val(),
            parent_id:        $('#wz_parent_id').val() || 0,
            category_id:      $('#wz_category_id').val() || 0,
            menu_action:      $('#bj-menu-action').val()      || 'none',
            menu_id:          $('#bj-menu-id').val()          || 0,
            menu_name:        $('#bj-menu-name').val()        || '',
            menu_location:    $('#bj-menu-location').val()    || '',
            label_format:     $('#bj-label-format').val()     || 'post_title',
            label_template:   $('#bj-label-template').val()   || '',
        };

        showStep(TOTAL_STEPS);
        $('#wz_progress').show();
        $('#wz_done, #wz_failed').hide();
        appendLog('[INFO] Création du job…');

        TechrappySEOAjax(
            'techrappy_wizard_step',
            data,
            function (resp) {
                currentJobId = resp.job_id;
                appendLog('[INFO] Job ' + currentJobId + ' créé. Polling démarré…');
                startPolling(currentJobId, false);
            },
            function (err) {
                appendLog('[ERROR] ' + (err.message || 'Erreur création job.'), 'error');
                $('#wz_progress').hide();
                $('#wz_failed').show();
            }
        );
    }

    function launchBulk() {
        var selectedCities = [];
        $('#wz_cities_list input[type="checkbox"]:checked').each(function () {
            selectedCities.push($(this).val());
        });

        if (!selectedCities.length) {
            showNotice('Sélectionnez au moins une ville pour la génération en masse.');
            showStep(1);
            return;
        }

        var data = {
            nonce:            TechrappySEO.nonces.bulk,
            keyword:          $('#wz_keyword').val().trim(),
            profession:       $('#wz_profession').val().trim(),
            type:             $('#wz_type').val(),
            ville_principale: $('#wz_ville_principale').val().trim(),
            template_post_id: $('#wz_template_post_id').val(),
            publish_status:   $('#wz_publish_status').val(),
            slug_rule:        $('#wz_slug_rule').val(),
            parent_id:        $('#wz_parent_id').val() || 0,
            category_id:      $('#wz_category_id').val() || 0,
            menu_action:      $('#bj-menu-action').val()      || 'none',
            menu_id:          $('#bj-menu-id').val()          || 0,
            menu_name:        $('#bj-menu-name').val()        || '',
            menu_location:    $('#bj-menu-location').val()    || '',
            label_format:     $('#bj-label-format').val()     || 'post_title',
            label_template:   $('#bj-label-template').val()   || '',
        };
        // Envoyer les villes comme tableau
        $.each(selectedCities, function (i, city) {
            data['cities[' + i + ']'] = city;
        });

        showStep(TOTAL_STEPS);
        $('#wz_progress').show();
        $('#wz_done, #wz_failed').hide();
        appendLog('[INFO] Lancement bulk (' + selectedCities.length + ' villes)…');

        TechrappySEOAjax(
            'techrappy_launch_bulk',
            data,
            function (resp) {
                currentJobId = resp.parent_job_id || resp.job_id;
                appendLog('[INFO] Job parent ' + currentJobId + ' créé.');
                startPolling(currentJobId, true);
            },
            function (err) {
                appendLog('[ERROR] ' + (err.message || 'Erreur lancement bulk.'), 'error');
                $('#wz_progress').hide();
                $('#wz_failed').show();
            }
        );
    }

    function startPolling(jobId, isBulk) {
        stopPolling();

        function poll() {
            var ajaxAction = isBulk ? 'techrappy_get_job_status' : 'techrappy_wizard_step';
            var pollData   = isBulk
                ? { nonce: TechrappySEO.nonces.bulk, job_id: jobId }
                : { nonce: TechrappySEO.nonces.wizard, wizard_action: 'get_job_status', job_id: jobId };

            TechrappySEOAjax(
                ajaxAction,
                pollData,
                function (data) {
                    var status = data.status;

                    var logs = data.logs || [];
                    if (logs.length) {
                        $('#wz_log_output').empty();
                        $.each(logs, function (i, log) {
                            var lvl = log.level || 'info';
                            appendLog('[' + lvl.toUpperCase() + '][' + (log.step || '') + '] ' + (log.msg || ''), lvl);
                        });
                    }

                    if (isBulk && data.progress) {
                        var p = data.progress;
                        $('#wz_progress_msg').text('Progression : ' + (p.done || 0) + '/' + (p.total || 0) + ' (' + (p.percent || 0) + '%)');
                    } else {
                        $('#wz_progress_msg').text('Statut : ' + status);
                    }

                    if (status === 'done' || status === 'done_with_errors') {
                        stopPolling();
                        $('#wz_progress').hide();
                        $('#wz_done').show();
                        var result = data.result || {};
                        if (result.permalink) { $('#wz_post_link').attr('href', result.permalink); }
                        if (result.post_id) {
                            $('#wz_edit_link').attr('href', TechrappySEO.admin_url + 'post.php?post=' + result.post_id + '&action=edit');
                        }
                    } else if (status === 'failed') {
                        stopPolling();
                        $('#wz_progress').hide();
                        $('#wz_failed').show();
                    } else {
                        pollTimer = setTimeout(poll, 4000);
                    }
                },
                function () {
                    pollTimer = setTimeout(poll, 8000);
                }
            );
        }

        poll();
    }

    function stopPolling() {
        if (pollTimer) { clearTimeout(pollTimer); pollTimer = null; }
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Chargement des villes bulk
    // ──────────────────────────────────────────────────────────────────────────

    function loadCities() {
        var ville    = $('#wz_ville_principale').val().trim();
        var radius   = $('#wz_radius_km').val() || 30;
        var $container = $('#wz_cities_list');

        if (!ville) { showNotice('Saisissez d\'abord la ville principale.'); return; }

        $container.html('<span class="spinner is-active" style="float:none;vertical-align:middle;"></span> Chargement…');

        TechrappySEOAjax(
            'techrappy_get_cities',
            { nonce: TechrappySEO.nonces.bulk, ville_principale: ville, radius_km: radius },
            function (data) {
                var cities = data.cities || [];
                if (!cities.length) { $container.html('<p>Aucune ville trouvée.</p>'); return; }

                var html = '<div id="bj-cities-checkboxes" style="max-height:200px;overflow-y:auto;border:1px solid #ddd;padding:10px;">';
                $.each(cities, function (i, c) {
                    var label = c.city + (c.cp ? ' (' + c.cp + ')' : '') + (c.distance ? ' — ' + parseFloat(c.distance).toFixed(1) + ' km' : '');
                    html += '<label style="display:block;margin-bottom:4px;"><input type="checkbox" name="wz_cities[]" value="' + c.city + '" checked> ' + label + '</label>';
                });
                html += '</div><p>';
                html += '<a href="#" id="wz_select_all_cities">Tout sélectionner</a> &nbsp;|&nbsp; ';
                html += '<a href="#" id="wz_deselect_all_cities">Tout désélectionner</a>';
                html += '<span style="float:right;color:#777;font-size:12px;">' + cities.length + ' ville(s)</span></p>';
                $container.html(html);
            },
            function (err) {
                $container.html('<p style="color:red;">' + (err.message || 'Erreur chargement villes.') + '</p>');
            }
        );
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Validation par étape
    // ──────────────────────────────────────────────────────────────────────────

    function validateCurrentStep() {
        clearNotice();
        if (currentStep === 1) { return validateStep1(); }
        return true;
    }

    function onEnterStep(n) {
        if (n === 2) { loadTemplates(); }
        else if (n === 3) { runAudit(); }
        else if (n === 5) { fillSummary(); }
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Init
    // ──────────────────────────────────────────────────────────────────────────

    function resetStep6() {
        $('#wz_done, #wz_failed').hide();
        $('#wz_progress').show();
        $('#wz_log_output').empty();
        $('#wz_progress_msg').text('Initialisation…');
    }

    function init() {
        if (!$('#techrappy-wizard').length) { return; }

        resetStep6();
        showStep(1);

        $('input[name="wz_mode"]').on('change', function () {
            var mode = $(this).val();
            if (mode === 'bulk') {
                $('#wz_city_row').hide();
                $('#wz_bulk_city_row').show();
                $('#wz_keyword_hint').show();
            } else {
                $('#wz_city_row').show();
                $('#wz_bulk_city_row').hide();
                $('#wz_keyword_hint').hide();
            }
        });

        $('#wz_type').on('change', function () {
            if ($(this).val() === 'post') { $('#wz_category_row').show(); }
            else { $('#wz_category_row').hide(); }
        }).trigger('change');

        $(document).on('click', '#wz_load_cities', function (e) {
            e.preventDefault();
            loadCities();
        });

        $(document).on('click', '#wz_select_all_cities', function (e) {
            e.preventDefault();
            $('#wz_cities_list input[type="checkbox"]').prop('checked', true);
        });

        $(document).on('click', '#wz_deselect_all_cities', function (e) {
            e.preventDefault();
            $('#wz_cities_list input[type="checkbox"]').prop('checked', false);
        });

        $('#wz-btn-next').on('click', function () {
            if (!validateCurrentStep()) { return; }
            var next = currentStep + 1;
            if (next <= TOTAL_STEPS) { onEnterStep(next); showStep(next); }
        });

        $('#wz-btn-prev').on('click', function () {
            var prev = currentStep - 1;
            if (prev >= 1) { showStep(prev); }
        });

        $('#wz-btn-launch').on('click', function () {
            launchGeneration();
        });

        $(document).on('click', '#wz_new_generation', function () {
            stopPolling();
            currentJobId = null;
            $('#wz_keyword, #wz_profession, #wz_city, #wz_ville_principale').val('');
            $('#wz_cities_list').empty();
            resetStep6();
            showStep(1);
        });

        $(document).on('click', '#wz_retry', function () {
            launchGeneration();
        });
    }

    $(document).ready(init);

})(jQuery);
