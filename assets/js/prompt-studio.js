/* assets/js/prompt-studio.js — Techrappy SEO Prompt Studio v2 */
/* global TechrappySEO, TechrappySEOAjax */
(function ($) {
    'use strict';

    var currentPromptKey = null;

    // ──────────────────────────────────────────────────────────────────────────
    // Notices
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
    // Compteur de caractères
    // ──────────────────────────────────────────────────────────────────────────

    function updateCharCount() {
        var len = $('#ps-prompt-content').val().length;
        $('#ps-char-count').text(len.toLocaleString('fr-FR') + ' car.');
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Sélection d'un prompt dans la liste
    // ──────────────────────────────────────────────────────────────────────────

    function selectPrompt(key) {
        currentPromptKey = key;

        $('.ps-prompt-item').removeClass('active');
        var $item = $('.ps-prompt-item[data-key="' + key + '"]').addClass('active');

        var content = $item.data('content') || '';
        var label   = $item.data('label')   || key;
        var desc    = $item.data('desc')    || '';
        var format  = $item.data('format')  || 'json_object';
        var version = $item.data('version') || 1;

        $('#ps-prompt-key').val(key);
        $('#ps-prompt-content').val(content);
        $('#ps-editor-title').text(label);
        $('#ps-editor-desc').text(desc);
        $('#ps-current-version').text('v' + version);

        var $fmt = $('#ps-current-format');
        $fmt.text(format === 'json_object' ? 'JSON' : 'TEXT')
            .removeClass('ps-fmt-json ps-fmt-txt')
            .addClass(format === 'json_object' ? 'ps-fmt-json' : 'ps-fmt-txt');

        buildVarsChips(key, $item);

        $('#ps-test-panel').hide();
        $('#ps-test-output').hide();
        buildTestVarInputs($item);

        $('#ps-editor-empty').hide();
        $('#ps-editor').show();

        updateCharCount();
        clearNotice();
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Variables chips (clic = insérer dans textarea)
    // ──────────────────────────────────────────────────────────────────────────

    function parseVars($item) {
        try {
            return JSON.parse($item.attr('data-vars') || '[]') || [];
        } catch (e) {
            return [];
        }
    }

    function buildVarsChips(key, $item) {
        var vars   = parseVars($item);
        var $bar   = $('#ps-vars-bar');
        var $chips = $('#ps-vars-chips');

        if (!vars.length) {
            $bar.hide();
            return;
        }

        var html = '';
        vars.forEach(function (v) {
            html += '<span class="ps-var-chip" data-var="' + v + '">{{' + v + '}}</span>';
        });
        $chips.html(html);
        $bar.show();
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Inputs de test (générés dynamiquement)
    // ──────────────────────────────────────────────────────────────────────────

    function buildTestVarInputs($item) {
        var vars  = parseVars($item);
        var $grid = $('#ps-test-vars-grid');

        if (!vars.length) {
            $grid.html('<p class="ps-test-hint" style="margin:0;">Ce prompt n\'a pas de variables à renseigner.</p>');
            return;
        }

        var html = '';
        vars.forEach(function (v) {
            html += '<div class="ps-test-var-wrap">'
                  + '<label>{{' + v + '}}</label>'
                  + '<input type="text" class="ps-test-var-input" data-var="' + v + '" placeholder="valeur de test…">'
                  + '</div>';
        });
        $grid.html(html);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Insertion de variable au curseur
    // ──────────────────────────────────────────────────────────────────────────

    function insertVariable(varName) {
        var $ta   = $('#ps-prompt-content');
        var ta    = $ta[0];
        var val   = $ta.val();
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
        updateCharCount();
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
                showNotice('Prompt "' + $('#ps-editor-title').text() + '" sauvegardé.', 'success');
                var $item  = $('.ps-prompt-item[data-key="' + key + '"]');
                $item.data('content', content);
                var newVer = (parseInt($item.data('version'), 10) || 1) + 1;
                $item.data('version', newVer);
                $('#ps-current-version').text('v' + newVer);
            },
            function (err) {
                $spinner.removeClass('is-active');
                $btn.prop('disabled', false);
                showNotice(err.message || 'Erreur lors de la sauvegarde.');
            }
        );
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Réinitialiser UN prompt
    // ──────────────────────────────────────────────────────────────────────────

    function resetOnePrompt() {
        var key   = $('#ps-prompt-key').val();
        var label = $('#ps-editor-title').text() || key;

        if (!key) { return; }
        if (!window.confirm('Remettre "' + label + '" à sa valeur par défaut ? Vos modifications seront perdues.')) {
            return;
        }

        var $btn = $('#ps-btn-reset-one');
        $btn.prop('disabled', true);

        TechrappySEOAjax(
            'techrappy_reset_prompt_one',
            { nonce: TechrappySEO.nonces.prompt_studio, prompt_key: key },
            function (data) {
                $btn.prop('disabled', false);
                var content = data.content || '';
                $('#ps-prompt-content').val(content);
                $('.ps-prompt-item[data-key="' + key + '"]').data('content', content);
                updateCharCount();
                showNotice('Prompt "' + label + '" réinitialisé aux valeurs par défaut.', 'success');
            },
            function (err) {
                $btn.prop('disabled', false);
                showNotice(err.message || 'Erreur lors de la réinitialisation.');
            }
        );
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Réinitialiser TOUS les prompts
    // ──────────────────────────────────────────────────────────────────────────

    function resetAllPrompts() {
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
    // Panneau de test
    // ──────────────────────────────────────────────────────────────────────────

    function toggleTestPanel() {
        var $panel = $('#ps-test-panel');
        if ($panel.is(':visible')) {
            $panel.hide();
        } else {
            $panel.show();
            $('#ps-test-output').hide();
        }
    }

    function runTest() {
        var key     = $('#ps-prompt-key').val();
        var content = $('#ps-prompt-content').val().trim();

        if (!key || !content) {
            showNotice('Sélectionnez un prompt avant de tester.');
            return;
        }

        var variables = {};
        $('#ps-test-vars-grid .ps-test-var-input').each(function () {
            variables[$(this).data('var')] = $(this).val();
        });

        var $spinner = $('#ps-spinner-run-test');
        var $btn     = $('#ps-btn-run-test');
        var $output  = $('#ps-test-output');

        $spinner.addClass('is-active');
        $btn.prop('disabled', true);
        $output.show();
        $('#ps-test-result').text('Génération en cours…');
        $('#ps-test-duration').text('');

        TechrappySEOAjax(
            'techrappy_test_prompt',
            {
                nonce:      TechrappySEO.nonces.prompt_studio,
                prompt_key: key,
                content:    content,
                variables:  JSON.stringify(variables),
            },
            function (data) {
                $spinner.removeClass('is-active');
                $btn.prop('disabled', false);
                var result = data.result;
                $('#ps-test-result').text(
                    typeof result === 'string' ? result : JSON.stringify(result, null, 2)
                );
                if (data.duration_ms) {
                    $('#ps-test-duration').text('Durée : ' + (data.duration_ms / 1000).toFixed(2) + 's');
                }
            },
            function (err) {
                $spinner.removeClass('is-active');
                $btn.prop('disabled', false);
                $('#ps-test-result').text('Erreur : ' + (err.message || 'Inconnue'));
            }
        );
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Init
    // ──────────────────────────────────────────────────────────────────────────

    function init() {
        if (!$('#techrappy-prompt-studio').length) { return; }

        var $first = $('.ps-prompt-item:first');
        if ($first.length) {
            selectPrompt($first.data('key'));
        }

        $(document).on('click', '.ps-prompt-item',         function () { selectPrompt($(this).data('key')); });
        $(document).on('click', '#ps-btn-save',             savePrompt);
        $(document).on('click', '#ps-btn-reset-one',        resetOnePrompt);
        $(document).on('click', '#ps-btn-reset',            resetAllPrompts);
        $(document).on('click', '#ps-btn-test',             toggleTestPanel);
        $(document).on('click', '#ps-btn-run-test',         runTest);
        $(document).on('click', '.ps-var-chip',             function () { insertVariable($(this).data('var')); });
        $(document).on('input keyup', '#ps-prompt-content', updateCharCount);

        $(document).on('keydown', '#ps-prompt-content', function (e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                e.preventDefault();
                savePrompt();
            }
        });
    }

    $(document).ready(init);

})(jQuery);
