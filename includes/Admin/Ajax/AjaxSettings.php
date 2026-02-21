<?php
/**
 * Handler AJAX : Sauvegarde des réglages.
 *
 * @package TechrappySEO\Admin\Ajax
 */

declare( strict_types=1 );

namespace TechrappySEO\Admin\Ajax;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class AjaxSettings
 */
class AjaxSettings {

    /**
     * Sauvegarde les réglages du plugin.
     *
     * @return void
     */
    public function handle_save(): void {
        check_ajax_referer( 'techrappy_seo_settings', 'nonce' );

        if ( ! current_user_can( TECHRAPPY_SEO_CAPABILITY ) ) {
            wp_send_json_error( [ 'message' => __( 'Accès non autorisé.', 'techrappy-seo' ) ], 403 );
        }

        // TODO : implémenter sauvegarde settings via SettingsValidator.
        wp_send_json_success( [ 'saved' => true ] );
    }
}
