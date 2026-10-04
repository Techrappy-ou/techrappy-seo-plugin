/* assets/js/prompt-studio.js — Techrappy SEO Prompt Studio v4 (cards) */
/* global TechrappySEO, TechrappySEOAjax */
(function ($) {
    'use strict';

    // ──────────────────────────────────────────────────────────────────────────
    // Helpers — notice globale
    // ──────────────────────────────────────────────────────────────────────────

    function showGlobalNotice(msg, type) {
        type = type || 'error';
        $('#ps-notice').html('<div class="notice notice-' + type + ' is-dismissible"><p>' + msg + '</p></div>');
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Helpers — notice inline dans chaque carte
    // ──────────────────────────────────────────────────────────────────────────

    function showCardNotice($card, msg, type) {
        var $notice = $card.find('.ps-card-notice').first();
        $notice
            .removeClass('ps-notice-success ps-notice-error')
            .addClass('ps-notice-' + (type || 'success'))
            .text(msg)
            .stop(true, true)
            .show();
        clearTimeout($notice.data('ps-timer'));
        $notice.data('ps-timer', setTimeout(function () {
            $notice.fadeOut(300);
        }, 2800));
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Compteur de caractères par carte
    // ──────────────────────────────────────────────────────────────────────────

    function updateCharCount($card) {
        var len = $card.find('.ps-textarea').val().length;
        $card.find('.ps-char-count').text(len.toLocaleString('fr-FR') + ' car.');
    }

    function initCharCounts() {
        $('.ps-card').each(function () {
            updateCharCount($(this));
        });
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Sauvegarde d'une carte
    // ──────────────────────────────────────────────────────────────────────────

    function savePrompt(key) {
        var $card    = $('#ps-card-' + key);
        var content  = $card.find('.ps-textarea').val().trim();
        var $spinner = $card.find('.ps-spinner-save').first();
        var $btn     = $card.find('.ps-btn-save[data-key="' + key + '"]');

        if (!content) {
            showCardNotice($card, 'Le prompt ne peut pas être vide.', 'error');
            return;
        }

        $btn.prop('disabled', true);
        $spinner.addClass('is-active');

        TechrappySEOAjax(
            'techrappy_save_prompt',
            { nonce: TechrappySEO.nonces.prompt_studio, prompt_key: key, content: content },
            function () {
                $btn.prop('disabled', false);
                $spinner.removeClass('is-active');
                // Incrémenter la version affichée
                var $ver = $card.find('.ps-card-version');
                var cur  = parseInt($ver.text().replace('v', ''), 10) || 1;
                $ver.text('v' + (cur + 1));
                showCardNotice($card, '✓ Sauvegardé', 'success');
            },
            function (err) {
                $btn.prop('disabled', false);
                $spinner.removeClass('is-active');
                showCardNotice($card, err.message || 'Erreur lors de la sauvegarde.', 'error');
            }
        );
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Réinitialiser UN prompt
    // ──────────────────────────────────────────────────────────────────────────

    function resetOnePrompt(key) {
        var $card = $('#ps-card-' + key);
        var label = $card.find('.ps-card-title').text() || key;

        if (!window.confirm('Remettre "' + label + '" à sa valeur par défaut ?\nVos modifications seront perdues.')) {
            return;
        }

        var $btn = $card.find('.ps-btn-reset-one[data-key="' + key + '"]');
        $btn.prop('disabled', true);

        TechrappySEOAjax(
            'techrappy_reset_prompt_one',
            { nonce: TechrappySEO.nonces.prompt_studio, prompt_key: key },
            function (data) {
                $btn.prop('disabled', false);
                var content = data.content || '';
                $card.find('.ps-textarea').val(content);
                $card.find('.ps-card-version').text('v1');
                updateCharCount($card);
                showCardNotice($card, '↺ Remis par défaut', 'success');
            },
            function (err) {
                $btn.prop('disabled', false);
                showCardNotice($card, err.message || 'Erreur lors de la réinitialisation.', 'error');
            }
        );
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Réinitialiser TOUS les prompts
    // ──────────────────────────────────────────────────────────────────────────

    function resetAllPrompts() {
        if (!window.confirm('Réinitialiser TOUS les prompts aux valeurs par défaut ?\nCette action est irréversible.')) {
            return;
        }

        var $btn     = $('#ps-btn-reset');
        var $spinner = $('#ps-spinner-reset');
        $btn.prop('disabled', true);
        $spinner.addClass('is-active');

        TechrappySEOAjax(
            'techrappy_reset_prompts',
            { nonce: TechrappySEO.nonces.prompt_studio },
            function () {
                showGlobalNotice('Tous les prompts ont été réinitialisés. Rechargement…', 'success');
                setTimeout(function () { location.reload(); }, 1500);
            },
            function (err) {
                $btn.prop('disabled', false);
                $spinner.removeClass('is-active');
                showGlobalNotice(err.message || 'Erreur lors de la réinitialisation.');
            }
        );
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Insertion d'une variable au curseur dans le textarea de la carte
    // ──────────────────────────────────────────────────────────────────────────

    function insertVariable(varName, targetId) {
        var ta    = document.getElementById(targetId);
        if (!ta) { return; }

        var token = '{{' + varName + '}}';
        var start = ta.selectionStart;
        var end   = ta.selectionEnd;
        var val   = ta.value;

        ta.value = val.substring(0, start) + token + val.substring(end);
        ta.selectionStart = ta.selectionEnd = start + token.length;
        ta.focus();

        updateCharCount($(ta).closest('.ps-card'));
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Panneau de test : afficher / masquer
    // ──────────────────────────────────────────────────────────────────────────

    function toggleTestPanel(key) {
        var $card  = $('#ps-card-' + key);
        var $panel = $card.find('.ps-test-panel').first();
        $panel.slideToggle(180, function () {
            if ($panel.is(':visible')) {
                $panel[0].scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
        });
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Lancer un test
    // ──────────────────────────────────────────────────────────────────────────

    function runTest(key) {
        var $card    = $('#ps-card-' + key);
        var content  = $card.find('.ps-textarea').val().trim();
        var $spinner = $card.find('.ps-spinner-run-test').first();
        var $btn     = $card.find('.ps-btn-run-test[data-key="' + key + '"]');
        var $output  = $card.find('.ps-test-output').first();
        var $result  = $card.find('.ps-test-result').first();
        var $dur     = $card.find('.ps-test-duration').first();

        var variables = {};
        $card.find('.ps-test-var-input').each(function () {
            variables[$(this).data('var')] = $(this).val();
        });

        $btn.prop('disabled', true);
        $spinner.addClass('is-active');
        $result.text('Génération en cours…');
        $dur.text('');
        $output.show();

        TechrappySEOAjax(
            'techrappy_test_prompt',
            {
                nonce:      TechrappySEO.nonces.prompt_studio,
                prompt_key: key,
                content:    content,
                variables:  JSON.stringify(variables),
            },
            function (data) {
                $btn.prop('disabled', false);
                $spinner.removeClass('is-active');
                var result = data.result;
                $result.text(typeof result === 'string' ? result : JSON.stringify(result, null, 2));
                if (data.duration_ms) {
                    $dur.text('⏱ ' + (data.duration_ms / 1000).toFixed(2) + 's');
                }
            },
            function (err) {
                $btn.prop('disabled', false);
                $spinner.removeClass('is-active');
                $result.text('Erreur : ' + (err.message || 'Inconnue'));
            }
        );
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Init
    // ──────────────────────────────────────────────────────────────────────────

    function init() {
        if (!$('#techrappy-prompt-studio').length) { return; }

        initCharCounts();

        // Sauvegarder
        $(document).on('click', '.ps-btn-save', function () {
            savePrompt($(this).data('key'));
        });

        // Réinitialiser une carte
        $(document).on('click', '.ps-btn-reset-one', function () {
            resetOnePrompt($(this).data('key'));
        });

        // Réinitialiser tout
        $(document).on('click', '#ps-btn-reset', resetAllPrompts);

        // Toggle panneau de test
        $(document).on('click', '.ps-btn-test-toggle', function () {
            toggleTestPanel($(this).data('key'));
        });

        // Lancer le test
        $(document).on('click', '.ps-btn-run-test', function () {
            runTest($(this).data('key'));
        });

        // Insérer une variable au curseur
        $(document).on('click', '.ps-var-chip', function () {
            insertVariable($(this).data('var'), $(this).data('target'));
        });

        // Mettre à jour le compteur à la saisie
        $(document).on('input', '.ps-textarea', function () {
            updateCharCount($(this).closest('.ps-card'));
        });

        // Ctrl+S / Cmd+S dans un textarea
        $(document).on('keydown', '.ps-textarea', function (e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                e.preventDefault();
                savePrompt($(this).data('key'));
            }
        });
    }

    $(document).ready(init);

})(jQuery);
