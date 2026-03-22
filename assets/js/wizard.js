/* assets/js/wizard.js — Techrappy SEO Wizard */
/* global TechrappySEO, TechrappySEOAjax */
(function ($) {
    'use strict';

    var TOTAL_STEPS = 6;
    var currentStep = 1;
    var currentJobId = null;
    var pollTimer = null;
    var doneTimer = null;

    // Étapes du pipeline IA (dans l'ordre d'exécution).
    var PIPELINE_STEPS = [
        { key: 'intent',         label: 'Analyse intention' },
        { key: 'plan',           label: 'Plan' },
        { key: 'blocks_list',    label: 'Structure' },
        { key: 'intro',          label: 'Introduction' },
        { key: 'blocks',         label: 'Rédaction' },
        { key: 'anti_duplicate', label: 'Anti-dup.' },
        { key: 'conclusion_cta', label: 'Conclusion' },
        { key: 'meta',           label: 'Méta SEO' },
        { key: 'faq',            label: 'FAQ' },
        { key: 'internal_links', label: 'Liens' },
        { key: 'qa',             label: 'Qualité' },
    ];

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
        var mode    = getMode();
        var submode = getBulkSubmode();

        // En mode bulk + keywords, le champ "mot-clé" n'est pas requis.
        if (!(mode === 'bulk' && submode === 'keywords')) {
            var keyword = $('#wz_keyword').val().trim();
            if (!keyword) {
                showNotice('Le mot-clé principal est requis.');
                return false;
            }
        }

        var profession = $('#wz_profession').val().trim();
        if (!profession) {
            showNotice('La profession / secteur est requise.');
            return false;
        }

        if (mode === 'bulk') {
            if (submode === 'keywords') {
                var kws = $('#wz_keywords_list').val().split('\n').filter(function (l) { return l.trim().length > 0; });
                if (!kws.length) {
                    showNotice('Saisissez au moins un mot-clé dans la liste.');
                    return false;
                }
            } else {
                if (!$('#wz_ville_principale').val().trim()) {
                    showNotice('Le code postal de référence est requis pour la génération en masse par villes.');
                    return false;
                }
            }
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

    // ──────────────────────────────────────────────────────────────────────────
    // Barre de progression par étape
    // ──────────────────────────────────────────────────────────────────────────

    function renderPipelineSteps(steps) {
        var doneCount = 0;
        var currentLabel = '';
        var html = '';

        PIPELINE_STEPS.forEach(function (step) {
            var s = steps ? steps[step.key] : null;
            var status = s ? (s.status || 'pending') : 'pending';

            var icon, bg, color;
            if (status === 'ok') {
                icon = '✓'; bg = '#d4edda'; color = '#155724'; doneCount++;
            } else if (status === 'error') {
                icon = '✗'; bg = '#f8d7da'; color = '#721c24'; doneCount++;
            } else if (status === 'running') {
                icon = '⏳'; bg = '#fff3cd'; color = '#856404';
                currentLabel = step.label;
            } else {
                icon = '·'; bg = '#e2e3e5'; color = '#6c757d';
            }

            html += '<span style="padding:3px 10px;border-radius:12px;font-size:11px;font-weight:500;'
                  + 'background:' + bg + ';color:' + color + ';white-space:nowrap;">'
                  + icon + ' ' + step.label + '</span>';
        });

        $('#wz_steps_list').html(html);

        var pct = Math.round((doneCount / PIPELINE_STEPS.length) * 100);
        $('#wz_progress_bar').css('width', pct + '%');
        $('#wz_progress_pct').text(pct ? pct + '%' : '');

        if (currentLabel) {
            $('#wz_progress_msg').text('En cours : ' + currentLabel + '…');
        }
    }

    function appendLog(msg, level) {
        var $log = $('#wz_log_output');
        $log.append('<div class="log-' + (level || 'info') + '">' + msg + '</div>');
        $log.scrollTop($log[0].scrollHeight);
    }

    function getBulkSubmode() {
        return $('input[name="wz_bulk_submode"]:checked').val() || 'cities';
    }

    function launchGeneration() {
        clearNotice();
        if (getMode() === 'bulk') {
            if (getBulkSubmode() === 'keywords') {
                launchBulkKeywords();
            } else {
                launchBulk();
            }
        } else {
            launchSingle();
        }
    }

    function launchSingle() {
        // Arrêter tout polling précédent avant de démarrer un nouveau job.
        stopPolling();

        var intentMode = $('input[name="wz_intent_mode"]:checked').val() || 'auto';
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
            intent_mode:      intentMode,
            user_intent:      intentMode === 'manual' ? $('#wz_user_intent').val().trim() : '',
        };

        showStep(TOTAL_STEPS);
        $('#wz_progress').show();
        $('#wz_done, #wz_failed').hide();
        $('#wz_log_output').empty();
        renderPipelineSteps(null);
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
                $('#wz_progress, #wz_done').hide();
                $('#wz_failed').show();
            }
        );
    }

    function launchBulk() {
        // Arrêter tout polling précédent avant de démarrer un nouveau job.
        stopPolling();

        var selectedCities = [];
        $('#wz_cities_list input[type="checkbox"]:checked').each(function () {
            selectedCities.push({
                city: $(this).val(),
                cp:   $(this).data('cp') || ''
            });
        });

        if (!selectedCities.length) {
            showNotice('Sélectionnez au moins une ville pour la génération en masse.');
            showStep(1);
            return;
        }

        var intentModeBulk = $('input[name="wz_intent_mode"]:checked').val() || 'auto';
        var data = {
            nonce:            TechrappySEO.nonces.bulk,
            keyword_base:     $('#wz_keyword').val().trim(),
            profession:       $('#wz_profession').val().trim(),
            type:             $('#wz_type').val(),
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
            intent_mode:      intentModeBulk,
            user_intent:      intentModeBulk === 'manual' ? $('#wz_user_intent').val().trim() : '',
        };
        // Envoyer les villes comme tableau d'objets {city, cp}.
        $.each(selectedCities, function (i, c) {
            data['cities[' + i + '][city]'] = c.city;
            data['cities[' + i + '][cp]']   = c.cp;
        });

        showStep(TOTAL_STEPS);
        $('#wz_progress').show();
        $('#wz_done, #wz_failed').hide();
        $('#wz_log_output').empty();
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
                $('#wz_progress, #wz_done').hide();
                $('#wz_failed').show();
            }
        );
    }

    function launchBulkKeywords() {
        stopPolling();

        // Collecter et nettoyer les mots-clés (un par ligne).
        var rawKeywords = $('#wz_keywords_list').val() || '';
        var keywords = rawKeywords.split('\n')
            .map(function (k) { return k.trim(); })
            .filter(function (k) { return k.length > 0; });

        // Dédupliquer.
        keywords = keywords.filter(function (k, i, arr) { return arr.indexOf(k) === i; });

        if (!keywords.length) {
            showNotice('Saisissez au moins un mot-clé dans la liste.');
            showStep(1);
            return;
        }

        var intentModeBk = $('input[name="wz_intent_mode"]:checked').val() || 'auto';
        var data = {
            nonce:            TechrappySEO.nonces.bulk,
            profession:       $('#wz_profession').val().trim(),
            type:             $('#wz_type').val(),
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
            intent_mode:      intentModeBk,
            user_intent:      intentModeBk === 'manual' ? $('#wz_user_intent').val().trim() : '',
        };
        // Envoyer les mots-clés.
        $.each(keywords, function (i, kw) {
            data['keywords[' + i + ']'] = kw;
        });

        showStep(TOTAL_STEPS);
        $('#wz_progress').show();
        $('#wz_done, #wz_failed').hide();
        $('#wz_log_output').empty();
        appendLog('[INFO] Lancement bulk mots-clés (' + keywords.length + ' mots-clés)…');

        TechrappySEOAjax(
            'techrappy_launch_bulk_keywords',
            data,
            function (resp) {
                currentJobId = resp.parent_job_id || resp.job_id;
                appendLog('[INFO] Job parent ' + currentJobId + ' créé (' + (resp.total || keywords.length) + ' mots-clés).');
                startPolling(currentJobId, true);
            },
            function (err) {
                appendLog('[ERROR] ' + (err.message || 'Erreur lancement bulk mots-clés.'), 'error');
                $('#wz_progress, #wz_done').hide();
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

                    // Mettre à jour les étapes du pipeline (mode single).
                    if (!isBulk && data.steps) {
                        renderPipelineSteps(data.steps);
                    }

                    // Progression bulk.
                    if (isBulk && data.progress) {
                        var p = data.progress;
                        var bPct = p.percent || 0;
                        $('#wz_progress_bar').css('width', bPct + '%');
                        $('#wz_progress_pct').text(bPct ? bPct + '%' : '');

                        var parts = [];
                        if ((p.done || 0) > (p.failed || 0)) {
                            parts.push((p.done - (p.failed || 0)) + ' terminé(s)');
                        }
                        if (p.running) { parts.push(p.running + ' en cours'); }
                        if (p.pending) { parts.push(p.pending + ' en attente'); }
                        if (p.failed)  { parts.push(p.failed  + ' échoué(s)'); }
                        var msg = (p.done || 0) + '/' + (p.total || 0);
                        if (parts.length) { msg += ' — ' + parts.join(', '); }
                        $('#wz_progress_msg').text(msg);
                    }

                    // Logs.
                    var logs = data.logs || [];
                    if (logs.length) {
                        $('#wz_log_output').empty();
                        $.each(logs, function (i, log) {
                            var lvl = log.level || 'info';
                            appendLog('[' + lvl.toUpperCase() + '][' + (log.step || '') + '] ' + (log.msg || ''), lvl);
                        });
                    }

                    if (status === 'done' || status === 'done_with_errors') {
                        stopPolling();
                        // 100% sur la barre.
                        $('#wz_progress_bar').css('width', '100%');
                        $('#wz_progress_pct').text('100%');
                        $('#wz_spinner').removeClass('is-active');
                        $('#wz_progress_msg').text('Terminé !');

                        doneTimer = setTimeout(function () {
                            doneTimer = null;
                            $('#wz_progress, #wz_failed').hide();
                            var result = data.result || {};
                            if (result.permalink) { $('#wz_post_link').attr('href', result.permalink); }
                            if (result.post_id) {
                                $('#wz_edit_link').attr('href', TechrappySEO.admin_url + 'post.php?post=' + result.post_id + '&action=edit');
                            }
                            $('#wz_step6_title').text('Génération terminée !');
                            $('#wz_done').show();
                        }, 600);
                    } else if (status === 'failed') {
                        stopPolling();
                        $('#wz_progress, #wz_done').hide();
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
        if (doneTimer) { clearTimeout(doneTimer); doneTimer = null; }
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Chargement des villes bulk
    // ──────────────────────────────────────────────────────────────────────────

    function loadCities() {
        var cp       = $('#wz_ville_principale').val().trim();
        var radius   = $('#wz_radius_km').val() || 30;
        var $container = $('#wz_cities_list');

        if (!cp) { showNotice('Saisissez d\'abord le code postal.'); return; }

        $container.html('<span class="spinner is-active" style="float:none;vertical-align:middle;"></span> Chargement des communes…');

        TechrappySEOAjax(
            'techrappy_get_cities',
            { nonce: TechrappySEO.nonces.bulk, cp: cp, radius_km: radius },
            function (data) {
                var cities = data.cities || [];
                if (!cities.length) {
                    $container.html('<p style="color:#b91c1c;">Aucune commune trouvée pour ce code postal.</p>');
                    return;
                }

                var html = '<div id="bj-cities-checkboxes" style="max-height:240px;overflow-y:auto;border:1px solid #ddd;padding:10px;">';
                $.each(cities, function (i, c) {
                    var safeName = $('<span>').text(c.city).html();
                    var safeCp   = $('<span>').text(c.cp).html();
                    var dist     = c.distance ? ' — ' + parseFloat(c.distance).toFixed(1) + ' km' : '';
                    var label    = safeName + ' (' + safeCp + ')' + dist;
                    html += '<label style="display:block;margin-bottom:4px;">'
                          + '<input type="checkbox" name="wz_cities[]" value="' + safeName + '" data-cp="' + safeCp + '" checked> '
                          + label + '</label>';
                });
                html += '</div><p>';
                html += '<a href="#" id="wz_select_all_cities">Tout sélectionner</a> &nbsp;|&nbsp; ';
                html += '<a href="#" id="wz_deselect_all_cities">Tout désélectionner</a>';
                html += '<span style="float:right;color:#777;font-size:12px;">' + cities.length + ' commune(s)</span></p>';
                $container.html(html);
            },
            function (err) {
                $container.html('<p style="color:red;">' + (err.message || 'Erreur chargement communes.') + '</p>');
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
        $('#wz_done, #wz_failed, #wz_progress').hide();
        $('#wz_log_output').empty();
        $('#wz_progress_msg').text('Initialisation…');
    }

    function init() {
        if (!$('#techrappy-wizard').length) { return; }

        resetStep6();
        showStep(1);

        // Reprise d'un job depuis la liste (paramètre URL resume_job).
        if (window.URLSearchParams) {
            var resumeJobId = new URLSearchParams(window.location.search).get('resume_job');
            if (resumeJobId) {
                // Nettoyer l'URL pour éviter de réafficher le résultat au prochain chargement.
                var urlParams = new URLSearchParams(window.location.search);
                urlParams.delete('resume_job');
                var newSearch = urlParams.toString();
                var newUrl = window.location.pathname + (newSearch ? '?' + newSearch : '');
                window.history.replaceState({}, document.title, newUrl);

                currentJobId = resumeJobId;
                showStep(TOTAL_STEPS);
                $('#wz_progress').show();
                appendLog('[INFO] Reprise du suivi du job ' + resumeJobId + '…');
                TechrappySEOAjax(
                    'techrappy_get_job_status',
                    { nonce: TechrappySEO.nonces.bulk, job_id: resumeJobId },
                    function (data) {
                        startPolling(resumeJobId, data.mode === 'bulk');
                    },
                    function () {
                        startPolling(resumeJobId, false);
                    }
                );
            }
        }

        function applyBulkSubmode() {
            var submode = getBulkSubmode();
            if (submode === 'keywords') {
                $('#wz_bulk_city_row').hide();
                $('#wz_bulk_keywords_row').show();
                $('#wz_keyword_hint').hide();
                $('#wz_keyword').closest('tr').hide(); // Cacher le champ "mot-clé base" — non utilisé en mode keywords.
            } else {
                $('#wz_bulk_city_row').show();
                $('#wz_bulk_keywords_row').hide();
                $('#wz_keyword_hint').show();
                $('#wz_keyword').closest('tr').show();
            }
        }

        $('input[name="wz_mode"]').on('change', function () {
            var mode = $(this).val();
            if (mode === 'bulk') {
                $('#wz_city_row').hide();
                $('#wz_bulk_submode_row').show();
                applyBulkSubmode();
                $('#wz_keyword_hint').show();
            } else {
                $('#wz_city_row').show();
                $('#wz_bulk_submode_row').hide();
                $('#wz_bulk_city_row').hide();
                $('#wz_bulk_keywords_row').hide();
                $('#wz_keyword_hint').hide();
                $('#wz_keyword').closest('tr').show();
            }
        });

        // Sous-mode bulk : Par villes / Par mots-clés.
        $('input[name="wz_bulk_submode"]').on('change', applyBulkSubmode);

        // Compteur de mots-clés en temps réel.
        $('#wz_keywords_list').on('input', function () {
            var lines = $(this).val().split('\n').filter(function (l) { return l.trim().length > 0; });
            var n = lines.length;
            $('#wz_keywords_count').text(n ? n + ' mot' + (n > 1 ? 's-clés' : '-clé') : '');
        });

        // Intention de recherche : afficher/masquer le textarea.
        $('input[name="wz_intent_mode"]').on('change', function () {
            if ($(this).val() === 'manual') {
                $('#wz_user_intent_wrap').show();
            } else {
                $('#wz_user_intent_wrap').hide();
            }
        });

        // Menu : afficher/masquer les sous-champs selon l'action choisie.
        $(document).on('change', '#bj-menu-action', function () {
            var action = $(this).val();
            $('#wz_menu_existing_row').toggle(action === 'add_existing');
            $('#wz_menu_new_row').toggle(action === 'create_new');
            $('#wz_menu_label_row').toggle(action !== 'none');
        });

        // Format de libellé : afficher/masquer le champ custom.
        $(document).on('change', '#bj-label-format', function () {
            $('#wz_menu_custom_label_wrap').toggle($(this).val() === 'custom');
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
