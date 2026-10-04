/* assets/js/template-audit.js — Techrappy SEO Template Audit */
/* global TechrappySEO, TechrappySEOAjax */
(function ($) {
    'use strict';

    var currentPostId  = null;
    var currentMapping = {};

    // ──────────────────────────────────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────────────────────────────────

    function showNotice(msg, type) {
        type = type || 'error';
        $('#ta-notice').html('<div class="notice notice-' + type + ' is-dismissible"><p>' + msg + '</p></div>');
    }

    function clearNotice() {
        $('#ta-notice').html('');
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Scan du template sélectionné
    // ──────────────────────────────────────────────────────────────────────────

    function scanTemplate() {
        var postId = $('#ta-post-select').val();

        if (!postId) {
            showNotice('Sélectionnez d\'abord un template.');
            return;
        }

        currentPostId = postId;

        var $spinner = $('#ta-spinner');
        var $btn     = $('#ta-btn-scan');
        var $result  = $('#ta-result');

        $spinner.addClass('is-active');
        $btn.prop('disabled', true);
        $result.hide();
        clearNotice();

        TechrappySEOAjax(
            'techrappy_scan_template',
            { nonce: TechrappySEO.nonces.audit, post_id: postId },
            function (data) {
                $spinner.removeClass('is-active');
                $btn.prop('disabled', false);

                // Titre
                $('#ta-post-title').text(data.post_title || '');

                // Tokens trouvés
                var $found = $('#ta-tokens-found').empty();
                (data.tokens_found || []).forEach(function (t) {
                    $found.append('<li><code>{{' + t + '}}</code></li>');
                });

                // Tokens manquants
                var $missing = $('#ta-tokens-missing').empty();
                var missing  = data.tokens_missing || [];
                if (missing.length) {
                    missing.forEach(function (t) {
                        $missing.append('<li><code>{{' + t + '}}</code></li>');
                    });
                    $('#ta-no-missing').hide();
                } else {
                    $('#ta-no-missing').show();
                }

                // Validité
                var $validity = $('#ta-validity').empty();
                if (data.is_valid) {
                    $validity.html('<div class="notice notice-success inline" style="padding:8px;"><p>✓ Template valide — tous les tokens requis sont présents.</p></div>');
                } else if (!data.has_divi) {
                    $validity.html('<div class="notice notice-warning inline" style="padding:8px;"><p>⚠ Ce template ne contient pas de shortcodes Divi détectables.</p></div>');
                } else {
                    $validity.html('<div class="notice notice-warning inline" style="padding:8px;"><p>⚠ Template incomplet : ' + missing.length + ' token(s) manquant(s).</p></div>');
                }

                // Mapping existant
                currentMapping = data.mapping || {};
                buildMappingTable(data.tokens_found || []);

                $result.show();
            },
            function (err) {
                $spinner.removeClass('is-active');
                $btn.prop('disabled', false);
                showNotice(err.message || 'Erreur lors du scan.');
            }
        );
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Construction du tableau de mapping
    // ──────────────────────────────────────────────────────────────────────────

    function buildMappingTable(tokens) {
        var $tbody = $('#ta-mapping-rows').empty();

        if (!tokens.length) {
            $tbody.append('<tr><td colspan="2"><em>Aucun token détecté.</em></td></tr>');
            return;
        }

        tokens.forEach(function (token) {
            var existingValue = currentMapping[token] || '';
            var row = '<tr>' +
                '<td><code>{{' + token + '}}</code></td>' +
                '<td><input type="text" class="regular-text ta-mapping-input"' +
                ' data-token="' + token + '"' +
                ' value="' + existingValue.replace(/"/g, '&quot;') + '"' +
                ' placeholder="auto (résolution automatique)"></td>' +
                '</tr>';
            $tbody.append(row);
        });
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Sauvegarde du mapping
    // ──────────────────────────────────────────────────────────────────────────

    function saveMapping() {
        if (!currentPostId) {
            showNotice('Scannez d\'abord un template.');
            return;
        }

        var mapping = {};
        $('.ta-mapping-input').each(function () {
            var token = $(this).data('token');
            var val   = $(this).val().trim();
            if (val) {
                mapping[token] = val;
            }
        });

        var $spinner = $('#ta-spinner-save');
        var $btn     = $('#ta-btn-save-mapping');

        $spinner.addClass('is-active');
        $btn.prop('disabled', true);
        clearNotice();

        TechrappySEOAjax(
            'techrappy_save_mapping',
            { nonce: TechrappySEO.nonces.audit, post_id: currentPostId, mapping: mapping },
            function () {
                $spinner.removeClass('is-active');
                $btn.prop('disabled', false);
                currentMapping = mapping;
                showNotice('Mapping sauvegardé avec succès.', 'success');
            },
            function (err) {
                $spinner.removeClass('is-active');
                $btn.prop('disabled', false);
                showNotice(err.message || 'Erreur lors de la sauvegarde du mapping.');
            }
        );
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Init
    // ──────────────────────────────────────────────────────────────────────────

    function init() {
        if (!$('#techrappy-template-audit').length) { return; }

        $('#ta-btn-scan').on('click', scanTemplate);

        $(document).on('click', '#ta-btn-save-mapping', saveMapping);

        // Scan automatique si un template était déjà sélectionné
        var preselected = $('#ta-post-select').val();
        if (preselected) {
            scanTemplate();
        }
    }

    $(document).ready(init);

})(jQuery);
