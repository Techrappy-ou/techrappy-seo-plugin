<?php
// views/partials/footer.php
// Rôle : pied de page commun des pages admin du plugin.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="techrappy-seo-footer">
    <p><?php
        printf(
            /* translators: %s: version number */
            esc_html__( 'Techrappy SEO v%s', 'techrappy-seo' ),
            esc_html( TECHRAPPY_SEO_VERSION )
        );
    ?></p>
</div>
