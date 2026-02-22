<?php
/**
 * Handler AJAX : Prompt Studio (sauvegarde, reset, test).
 *
 * @package TechrappySEO\Admin\Ajax
 */

declare( strict_types=1 );

namespace TechrappySEO\Admin\Ajax;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class AjaxPromptStudio
 */
class AjaxPromptStudio {

    /**
     * Sauvegarde un prompt modifié.
     *
     * @return void
     */
    public function handle_save(): void {
        check_ajax_referer( 'techrappy_seo_prompt_studio', 'nonce' );

        if ( ! current_user_can( TECHRAPPY_SEO_CAPABILITY ) ) {
            wp_send_json_error( [ 'message' => __( 'Accès non autorisé.', 'techrappy-seo' ) ], 403 );
        }

        // TODO : implémenter sauvegarde prompt.
        wp_send_json_success( [ 'saved' => true ] );
    }

    /**
     * Remet tous les prompts aux valeurs par défaut.
     *
     * @return void
     */
    public function handle_reset(): void {
        check_ajax_referer( 'techrappy_seo_prompt_studio', 'nonce' );

        if ( ! current_user_can( TECHRAPPY_SEO_CAPABILITY ) ) {
            wp_send_json_error( [ 'message' => __( 'Accès non autorisé.', 'techrappy-seo' ) ], 403 );
        }

        // TODO : implémenter reset prompts.
        wp_send_json_success( [ 'reset' => true ] );
    }

    /**
     * Teste un prompt avec des variables fournies.
     *
     * @return void
     */
    public function handle_test(): void {
        check_ajax_referer( 'techrappy_seo_prompt_studio', 'nonce' );

        if ( ! current_user_can( TECHRAPPY_SEO_CAPABILITY ) ) {
            wp_send_json_error( [ 'message' => __( 'Accès non autorisé.', 'techrappy-seo' ) ], 403 );
        }

        // TODO : implémenter test prompt via AIClient.
        wp_send_json_success( [ 'result' => null, 'tokens_used' => 0, 'duration_ms' => 0 ] );
    }
}
