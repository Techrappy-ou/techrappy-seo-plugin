<?php
/**
 * Handler AJAX : Génération pipeline (step-by-step et full).
 *
 * @package TechrappySEO\Admin\Ajax
 */

declare( strict_types=1 );

namespace TechrappySEO\Admin\Ajax;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class AjaxGeneration
 */
class AjaxGeneration {

    /**
     * Lance une étape unique du pipeline.
     *
     * @return void
     */
    public function handle_run_step(): void {
        check_ajax_referer( 'techrappy_seo_generation', 'nonce' );

        if ( ! current_user_can( TECHRAPPY_SEO_CAPABILITY ) ) {
            wp_send_json_error( [ 'message' => __( 'Accès non autorisé.', 'techrappy-seo' ) ], 403 );
        }

        // TODO : implémenter l'exécution d'une étape pipeline.
        wp_send_json_success( [ 'message' => 'Run step handler — à implémenter.' ] );
    }

    /**
     * Lance le pipeline complet.
     *
     * @return void
     */
    public function handle_run_pipeline(): void {
        check_ajax_referer( 'techrappy_seo_generation', 'nonce' );

        if ( ! current_user_can( TECHRAPPY_SEO_CAPABILITY ) ) {
            wp_send_json_error( [ 'message' => __( 'Accès non autorisé.', 'techrappy-seo' ) ], 403 );
        }

        // TODO : implémenter le pipeline complet.
        wp_send_json_success( [ 'message' => 'Run pipeline handler — à implémenter.' ] );
    }
}
