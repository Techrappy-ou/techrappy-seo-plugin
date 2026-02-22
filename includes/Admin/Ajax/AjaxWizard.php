<?php
/**
 * Handler AJAX : Wizard de génération.
 *
 * @package TechrappySEO\Admin\Ajax
 */

declare( strict_types=1 );

namespace TechrappySEO\Admin\Ajax;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class AjaxWizard
 */
class AjaxWizard {

    /**
     * Gère les étapes du wizard via AJAX.
     *
     * @return void
     */
    public function handle_step(): void {
        // Sécurité : vérification nonce + capacité.
        check_ajax_referer( 'techrappy_seo_wizard', 'nonce' );

        if ( ! current_user_can( TECHRAPPY_SEO_CAPABILITY ) ) {
            wp_send_json_error( [ 'message' => __( 'Accès non autorisé.', 'techrappy-seo' ) ], 403 );
        }

        // TODO : implémenter la logique des étapes wizard.
        wp_send_json_success( [ 'message' => 'Wizard handler — à implémenter.' ] );
    }
}
