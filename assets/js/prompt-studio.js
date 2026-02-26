/* assets/js/prompt-studio.js — Techrappy SEO Prompt Studio */
/* global TechrappySEO, TechrappySEOAjax */
(function ($) {
    'use strict';

    var currentPromptKey = null;

    // ──────────────────────────────────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────────────────────────────────

    function showNotice(msg, type) {
        type = type || 'error';
        $('#ps-notice')
            .html('<div class="notice notice-' + type + ' is-dismissible"><p>' + msg + '</p></div>');
    }

    function clearNotice() {
        $('#ps-notice').html('');
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Sélection d'un prompt dans la liste
    // ──────────────────────────────────────────────────────────────────────────

    function selectPrompt(key) {
        currentPromptKey = key;

        $('.ps-prompt-item').removeClass('active');
        $('.ps-prompt-item[data-key="' + key + '"]').addClass('active');

        var $item = $('.ps-prompt-item[data-key="' + key + '"]');
        var content = $item.data('content') || '';
        var name    = $item.data('name')    || key;

        $('#ps-editor-title').text(name);
        $('#ps-prompt-key').val(key);
        $('#ps-prompt-content').val(content);
        $('#ps-editor-empty').hide();
        $('#ps-editor').show();

        // Afficher les variables disponibles pour ce prompt
        updateVariablesPanel(key);

        // Charger l'historique
        loadHistory(key);

        clearNotice();
    }

    function updateVariablesPanel(key) {
        var $panel = $('#ps-variables-list');
        if (!$panel.length) { return; }

        var vars = TechrappySEO.prompt_variables && TechrappySEO.prompt_variables[key]
            ? TechrappySEO.prompt_variables[key]
            : [];

        if (!vars.length) {
            $panel.html('<p class="description">Aucune variable définie pour ce prompt.</p>');
            return;
        }

        var html = '';
        vars.forEach(function (v) {
            html += '<code class="ps-var-tag" style="cursor:pointer;margin:2px;display:inline-block;" data-var="' + v + '">{{' + v + '}}</code> ';
        });
        $panel.html(html);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Sauvegarde
    // ──────────────────────────────────────────────────────────────────────────

    function savePrompt() {
        var key     = $('#ps-prompt-key').val();
        var content = $('#ps-prompt-content').val().trim();

        if (!key || !content) {
            showNotice('La clé et le contenu du prompt sont requis.');
            return;
        }

        var $spinner = $('#ps-spinner-save');
        var $btn     = $('#ps-btn-save');

        $spinner.addClass('is-active');
        $btn.prop('disabled', true);

        TechrappySEOAjax(
            'techrappy_save_prompt',
            { nonce: TechrappySEO.nonces.prompt_studio, prompt_key: key, content: content },
            function () {
                $spinner.removeClass('is-active');
                $btn.prop('disabled', false);
                showNotice('Prompt sauvegardé.', 'success');

                // Mettre à jour le data-content dans la liste
                $('.ps-prompt-item[data-key="' + key + '"]').data('content', content);

                // Recharger l'historique
                loadHistory(key);
            },
            function (err) {
                $spinner.removeClass('is-active');
                $btn.prop('disabled', false);
                showNotice(err.message || 'Erreur lors de la sauvegarde.');
            }
        );
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Reset prompts
    // ──────────────────────────────────────────────────────────────────────────

    function resetPrompts() {
        if (!window.confirm('Réinitialiser TOUS les prompts aux valeurs par défaut ? Cette action est irréversible.')) {
            return;
        }

        var $btn = $('#ps-btn-reset');
        $btn.prop('disabled', true);

        TechrappySEOAjax(
            'techrappy_reset_prompts',
            { nonce: TechrappySEO.nonces.prompt_studio },
            function () {
                showNotice('Tous les prompts ont été réinitialisés. Rechargement…', 'success');
                setTimeout(function () { location.reload(); }, 1500);
            },
            function (err) {
                $btn.prop('disabled', false);
                showNotice(err.message || 'Erreur lors de la réinitialisation.');
            }
        );
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Test de prompt
    // ──────────────────────────────────────────────────────────────────────────

    function testPrompt() {
        var key     = $('#ps-prompt-key').val();
        var content = $('#ps-prompt-content').val().trim();

        if (!key || !content) {
            showNotice('Sélectionnez et rédigez un prompt avant de tester.');
            return;
        }

        // Collecter les variables saisies
        var variables = {};
        $('#ps-test-variables .ps-test-var-input').each(function () {
            var varKey = $(this).data('var');
            variables[varKey] = $(this).val();
        });

        var $spinner = $('#ps-spinner-test');
        var $btn     = $('#ps-btn-test');
        var $output  = $('#ps-test-output');
        $('#ps-test-panel').show();

        $spinner.addClass('is-active');
        $btn.prop('disabled', true);
        $output.text('Génération en cours…');
        $('#ps-test-duration').text('');

        var postData = {
            nonce:      TechrappySEO.nonces.prompt_studio,
            prompt_key: key,
            content:    content,
            variables:  JSON.stringify(variables),
        };

        TechrappySEOAjax(
            'techrappy_test_prompt',
            postData,
            function (data) {
                $spinner.removeClass('is-active');
                $btn.prop('disabled', false);
                $output.text(typeof data.result === 'string' ? data.result : JSON.stringify(data.result, null, 2));
                if (data.duration_ms) {
                    $('#ps-test-duration').text('Durée : ' + (data.duration_ms / 1000).toFixed(2) + 's');
                }
            },
            function (err) {
                $spinner.removeClass('is-active');
                $btn.prop('disabled', false);
                $output.text('Erreur : ' + (err.message || 'Inconnue'));
            }
        );
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Historique
    // ──────────────────────────────────────────────────────────────────────────

    function loadHistory(key) {
        var $list = $('#ps-history-list');
        if (!$list.length) { return; }

        $list.html('<p><span class="spinner is-active" style="float:none;vertical-align:middle;"></span></p>');

        // L'historique est chargé côté PHP dans la liste — on utilise les données déjà présentes
        // (PromptVersioner stocke dans wp_options, pas d'endpoint dédié — on affiche ce qui est déjà rendu)
        $list.html('<p class="description">Sauvegardez le prompt pour voir les nouvelles versions ici.</p>');
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Insertion de variable dans le textarea
    // ──────────────────────────────────────────────────────────────────────────

    function insertVariable(varName) {
        var $ta = $('#ps-prompt-content');
        var ta  = $ta[0];
        var val = $ta.val();
        var token = '{{' + varName + '}}';

        if (ta.selectionStart !== undefined) {
            var start = ta.selectionStart;
            var end   = ta.selectionEnd;
            $ta.val(val.substring(0, start) + token + val.substring(end));
            ta.selectionStart = ta.selectionEnd = start + token.length;
            $ta.focus();
        } else {
            $ta.val(val + token);
        }
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Init
    // ──────────────────────────────────────────────────────────────────────────

    function init() {
        if (!$('#techrappy-prompt-studio').length) { return; }

        // Ajouter le div notice s'il n'existe pas
        if (!$('#ps-notice').length) {
            $('#techrappy-prompt-studio').prepend('<div id="ps-notice"></div>');
        }

        // Sélectionner le premier prompt par défaut
        var $first = $('.ps-prompt-item:first');
        if ($first.length) {
            selectPrompt($first.data('key'));
        }

        // Clic sur un prompt dans la liste
        $(document).on('click', '.ps-prompt-item', function () {
            selectPrompt($(this).data('key'));
        });

        // Sauvegarde
        $(document).on('click', '#ps-btn-save', savePrompt);

        // Raccourci clavier Ctrl+S
        $(document).on('keydown', '#ps-prompt-content', function (e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                e.preventDefault();
                savePrompt();
            }
        });

        // Reset
        $(document).on('click', '#ps-btn-reset', resetPrompts);

        // Test
        $(document).on('click', '#ps-btn-test', testPrompt);

        // Insertion variable (clic sur tag)
        $(document).on('click', '.ps-var-tag', function () {
            insertVariable($(this).data('var'));
        });
    }

    $(document).ready(init);

})(jQuery);
