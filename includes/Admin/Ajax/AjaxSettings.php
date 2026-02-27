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
     * Teste la connexion à l'API OpenAI avec la clé configurée.
     *
     * @return void
     */
    public function handle_test_connection(): void {
        check_ajax_referer( 'techrappy_seo_settings', 'nonce' );

        if ( ! current_user_can( TECHRAPPY_SEO_CAPABILITY ) ) {
            wp_send_json_error( [ 'message' => __( 'Accès non autorisé.', 'techrappy-seo' ) ], 403 );
        }

        $client = new \TechrappySEO\AI\AIClient();

        if ( ! $client->has_valid_api_key() ) {
            wp_send_json_error( [
                'message' => __( 'Clé API non configurée ou format invalide (doit commencer par "sk-").', 'techrappy-seo' ),
            ] );
        }

        // Appel minimal pour vérifier la connexion (peu de tokens).
        $result = $client->complete_json(
            'Réponds uniquement avec ce JSON exact : {"ok":true}',
            'Tu es un assistant. Réponds toujours en JSON valide.'
        );

        if ( null === $result ) {
            wp_send_json_error( [
                'message' => __( 'Connexion échouée. Vérifiez votre clé API OpenAI et vos droits d\'accès.', 'techrappy-seo' ),
            ] );
        }

        wp_send_json_success( [
            'message' => __( '✓ Connexion OpenAI réussie — clé valide.', 'techrappy-seo' ),
        ] );
    }

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
