<?php
/**
 * Vue : Audit de templates Divi.
 *
 * @package TechrappySEO
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

$templates = get_posts( [
    'post_type'      => [ 'page', 'post' ],
    'post_status'    => 'publish',
    'posts_per_page' => 200,
    'orderby'        => 'title',
    'order'          => 'ASC',
] );

include TECHRAPPY_SEO_VIEWS . 'partials/header.php';
?>
<div class="wrap techrappy-seo-wrap" id="techrappy-template-audit">

    <p><?php esc_html_e( 'Sélectionnez une page Divi pour vérifier la présence des tokens requis et configurer leur mapping.', 'techrappy-seo' ); ?></p>

    <div id="ta-notice"></div>

    <table class="form-table">
        <tr>
            <th scope="row"><label for="ta-post-select"><?php esc_html_e( 'Template à auditer', 'techrappy-seo' ); ?></label></th>
            <td>
                <select id="ta-post-select" class="regular-text">
                    <option value=""><?php esc_html_e( '— Choisir une page —', 'techrappy-seo' ); ?></option>
                    <?php foreach ( $templates as $t ) : ?>
                        <option value="<?php echo esc_attr( $t->ID ); ?>">
                            <?php echo esc_html( get_the_title( $t ) ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="button" class="button" id="ta-btn-scan" style="margin-left:8px;">
                    <?php esc_html_e( 'Scanner', 'techrappy-seo' ); ?>
                </button>
                <span class="spinner techrappy-spinner" id="ta-spinner"></span>
            </td>
        </tr>
    </table>

    <div id="ta-result" style="display:none;margin-top:20px;">

        <h3 id="ta-post-title"></h3>

        <div style="display:flex;gap:20px;margin-bottom:16px;">
            <div id="ta-box-found" style="flex:1;border:1px solid #c3e6cb;background:#d4edda;padding:14px;border-radius:4px;">
                <strong><?php esc_html_e( '✓ Tokens trouvés', 'techrappy-seo' ); ?></strong>
                <ul id="ta-tokens-found" style="margin:8px 0 0;padding-left:18px;"></ul>
            </div>
            <div id="ta-box-missing" style="flex:1;border:1px solid #f5c6cb;background:#f8d7da;padding:14px;border-radius:4px;">
                <strong><?php esc_html_e( '✗ Tokens manquants', 'techrappy-seo' ); ?></strong>
                <ul id="ta-tokens-missing" style="margin:8px 0 0;padding-left:18px;"></ul>
                <p id="ta-no-missing" style="display:none;color:#155724;margin:8px 0 0;">
                    <?php esc_html_e( 'Tous les tokens requis sont présents.', 'techrappy-seo' ); ?>
                </p>
            </div>
        </div>

        <div id="ta-validity" style="margin-bottom:16px;"></div>

        <?php /* ── Mapping personnalisé ────────────────────────────────── */ ?>
        <h3><?php esc_html_e( 'Mapping token → source', 'techrappy-seo' ); ?></h3>
        <p class="description">
            <?php esc_html_e( 'Optionnel. Permet d\'associer un token à une source custom (dot-path ex: plan.data.H1). Laisser vide pour la résolution automatique.', 'techrappy-seo' ); ?>
        </p>
        <table class="wp-list-table widefat fixed" id="ta-mapping-table">
            <thead><tr>
                <th><?php esc_html_e( 'Token', 'techrappy-seo' ); ?></th>
                <th><?php esc_html_e( 'Source (dot-path ou clé auto)', 'techrappy-seo' ); ?></th>
            </tr></thead>
            <tbody id="ta-mapping-rows"></tbody>
        </table>

        <p style="margin-top:10px;">
            <button type="button" class="button button-primary" id="ta-btn-save-mapping">
                <?php esc_html_e( 'Sauvegarder le mapping', 'techrappy-seo' ); ?>
            </button>
            <button type="button" class="button" id="ta-btn-export" style="margin-left:8px;">
                <?php esc_html_e( 'Exporter le template (JSON)', 'techrappy-seo' ); ?>
            </button>
            <button type="button" class="button" id="ta-btn-debug-tokens" style="margin-left:8px;border-color:#9b59b6;color:#9b59b6;">
                🔍 <?php esc_html_e( 'Diagnostiquer l\'encodage des tokens', 'techrappy-seo' ); ?>
            </button>
            <span class="spinner techrappy-spinner" id="ta-spinner-save"></span>
        </p>

        <?php /* ── Résultat du diagnostic d'encodage ─────────────────────── */ ?>
        <div id="ta-debug-result" style="display:none;margin-top:20px;padding:16px;background:#1e1e2e;border-radius:8px;color:#cdd6f4;font-family:monospace;font-size:12.5px;line-height:1.7;">
            <div style="font-weight:700;color:#cba6f7;margin-bottom:10px;font-size:13px;">
                🔍 <?php esc_html_e( 'Diagnostic encodage des tokens', 'techrappy-seo' ); ?>
            </div>
            <div id="ta-debug-content"></div>
        </div>

    </div>

    <hr style="margin:30px 0 20px;">

    <h3><?php esc_html_e( 'Importer un template', 'techrappy-seo' ); ?></h3>
    <p class="description">
        <?php esc_html_e( 'Collez ici le JSON d\'un template exporté. Un brouillon WordPress sera créé avec le contenu Divi et le mapping importés.', 'techrappy-seo' ); ?>
    </p>
    <textarea id="ta-import-json" rows="8" class="large-text code"
              placeholder='{"version":"1.0","post_content":"[et_pb_section ..."}'></textarea>
    <p style="margin-top:8px;">
        <button type="button" class="button button-secondary" id="ta-btn-import">
            <?php esc_html_e( 'Importer', 'techrappy-seo' ); ?>
        </button>
        <span class="spinner techrappy-spinner" id="ta-spinner-import"></span>
    </p>
    <div id="ta-import-result" style="display:none;margin-top:12px;"></div>

</div>

<script>
(function($){
    var nonce = <?php echo wp_json_encode( wp_create_nonce( 'techrappy_seo_audit' ) ); ?>;
    var ajaxUrl = <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>;

    // Debug encodage tokens.
    $('#ta-btn-debug-tokens').on('click', function(){
        var postId = $('#ta-post-select').val();
        if ( !postId ) { alert( '<?php echo esc_js( __( 'Sélectionnez d\'abord un template.', 'techrappy-seo' ) ); ?>' ); return; }
        $('#ta-spinner-save').addClass('is-active');
        $('#ta-debug-result').hide();
        $.post( ajaxUrl, {
            action  : 'techrappy_debug_template_tokens',
            nonce   : nonce,
            post_id : postId
        }, function(resp){
            $('#ta-spinner-save').removeClass('is-active');
            if ( !resp.success ) { alert( resp.data.message ); return; }
            var d = resp.data;
            var html = '';
            html += '<div style="color:#a6e3a1;margin-bottom:8px;">Post : <strong>' + d.post_title + '</strong> — ' + d.content_length + ' car. — Divi : ' + (d.has_divi ? '✅' : '❌') + '</div>';
            html += '<div style="margin-bottom:10px;color:#89b4fa;font-weight:700;">Formats de tokens détectés :</div>';
            $.each( d.formats, function(key, fmt){
                var statusColor = fmt.count > 0 ? '#a6e3a1' : '#585b70';
                var handledBadge = fmt.handled ? '<span style="color:#a6e3a1;">[géré ✓]</span>' : '<span style="color:#f38ba8;">[NON géré ✗]</span>';
                html += '<div style="margin-bottom:4px;color:' + statusColor + ';">';
                html += '<strong>' + fmt.count + '</strong> token(s) en format ' + fmt.label + ' ' + handledBadge;
                if ( fmt.tokens.length ) {
                    html += ' → ' + fmt.tokens.map(function(t){ return '<code style="background:#313244;padding:1px 5px;border-radius:3px;color:#cba6f7;">{{' + t + '}}</code>'; }).join(' ');
                }
                html += '</div>';
            });
            if ( d.raw_excerpt ) {
                html += '<div style="margin-top:12px;color:#89b4fa;font-weight:700;">Extrait brut du post_content (autour du premier { trouvé) :</div>';
                html += '<pre style="background:#11111b;color:#f9e2af;padding:10px;border-radius:6px;overflow:auto;margin-top:6px;font-size:11.5px;white-space:pre-wrap;word-break:break-all;">' + $('<div>').text(d.raw_excerpt).html() + '</pre>';
            }
            $('#ta-debug-content').html(html);
            $('#ta-debug-result').show();
        });
    });

    // Export.
    $('#ta-btn-export').on('click', function(){
        var postId = $('#ta-post-select').val();
        if ( !postId ) { alert( '<?php echo esc_js( __( 'Sélectionnez d\'abord un template.', 'techrappy-seo' ) ); ?>' ); return; }
        $('#ta-spinner-save').addClass('is-active');
        $.post( ajaxUrl, {
            action  : 'techrappy_export_template',
            nonce   : nonce,
            post_id : postId
        }, function(resp){
            $('#ta-spinner-save').removeClass('is-active');
            if ( !resp.success ) { alert( resp.data.message ); return; }
            var blob = new Blob( [ JSON.stringify( resp.data.data, null, 2 ) ], { type: 'application/json' } );
            var url  = URL.createObjectURL( blob );
            var a    = document.createElement('a');
            a.href   = url;
            a.download = resp.data.filename;
            a.click();
            URL.revokeObjectURL( url );
        });
    });

    // Import.
    $('#ta-btn-import').on('click', function(){
        var json = $('#ta-import-json').val().trim();
        if ( !json ) { alert( '<?php echo esc_js( __( 'Collez un JSON à importer.', 'techrappy-seo' ) ); ?>' ); return; }
        $('#ta-spinner-import').addClass('is-active');
        $('#ta-import-result').hide();
        $.post( ajaxUrl, {
            action    : 'techrappy_import_template',
            nonce     : nonce,
            json_data : json
        }, function(resp){
            $('#ta-spinner-import').removeClass('is-active');
            if ( !resp.success ) {
                $('#ta-import-result').html('<div class="notice notice-error"><p>' + resp.data.message + '</p></div>').show();
                return;
            }
            var d = resp.data;
            var missing = d.tokens_missing && d.tokens_missing.length
                ? '<br><strong><?php echo esc_js( __( 'Tokens manquants :', 'techrappy-seo' ) ); ?></strong> ' + d.tokens_missing.join(', ')
                : '<br><span style="color:green;"><?php echo esc_js( __( 'Tous les tokens requis sont présents.', 'techrappy-seo' ) ); ?></span>';
            var html = '<div class="notice notice-success"><p>'
                + d.message + ' &mdash; <a href="' + d.edit_url + '" target="_blank">' + d.post_title + '</a>'
                + missing + '</p></div>';
            $('#ta-import-result').html(html).show();
            $('#ta-import-json').val('');
        });
    });
})(jQuery);
</script>

<?php include TECHRAPPY_SEO_VIEWS . 'partials/footer.php'; ?>
