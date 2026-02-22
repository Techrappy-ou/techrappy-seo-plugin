<?php
/**
 * Handler AJAX : Sauvegarde des réglages.
 *
 * @package TechrappySEO\Admin\Ajax
 */

declare( strict_types=1 );

namespace TechrappySEO\Admin\Ajax;

use TechrappySEO\Settings\SettingsRepository;
use TechrappySEO\Settings\SettingsValidator;

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

        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $raw = $_POST['settings'] ?? [];
        if ( ! is_array( $raw ) ) {
            wp_send_json_error( [ 'message' => __( 'Données invalides.', 'techrappy-seo' ) ], 400 );
        }

        $validator = new SettingsValidator();

        if ( ! $validator->validate( $raw ) ) {
            wp_send_json_error( [
                'message' => __( 'Erreurs de validation.', 'techrappy-seo' ),
                'errors'  => $validator->get_errors(),
            ], 422 );
        }

        $sanitized = $validator->get_sanitized();

        // Gérer la clé API séparément (obfuscation).
        if ( isset( $sanitized['openai_api_key'] ) ) {
            SettingsRepository::set_api_key( $sanitized['openai_api_key'] );
            unset( $sanitized['openai_api_key'] );
        }

        // Sauvegarder les autres réglages.
        if ( ! empty( $sanitized ) ) {
            SettingsRepository::update( $sanitized );
        }

        wp_send_json_success( [ 'message' => __( 'Réglages sauvegardés.', 'techrappy-seo' ) ] );
    }
}
