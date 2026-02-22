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
            <span class="spinner techrappy-spinner" id="ta-spinner-save"></span>
        </p>

    </div>

</div>
<?php include TECHRAPPY_SEO_VIEWS . 'partials/footer.php'; ?>
