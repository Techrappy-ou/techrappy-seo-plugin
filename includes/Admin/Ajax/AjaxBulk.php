<?php
/**
 * Handler AJAX : Bulk Jobs (villes, preview, lancement, statut).
 *
 * @package TechrappySEO\Admin\Ajax
 */

declare( strict_types=1 );

namespace TechrappySEO\Admin\Ajax;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class AjaxBulk
 */
class AjaxBulk {

    /**
     * Récupère les villes via API Villes-Voisines + BAN.
     *
     * @return void
     */
    public function handle_get_cities(): void {
        check_ajax_referer( 'techrappy_seo_bulk', 'nonce' );

        if ( ! current_user_can( TECHRAPPY_SEO_CAPABILITY ) ) {
            wp_send_json_error( [ 'message' => __( 'Accès non autorisé.', 'techrappy-seo' ) ], 403 );
        }

        // TODO : implémenter récupération villes.
        wp_send_json_success( [ 'cities' => [] ] );
    }

    /**
     * Génère la prévisualisation pour une ville exemple.
     *
     * @return void
     */
    public function handle_preview_city(): void {
        check_ajax_referer( 'techrappy_seo_bulk', 'nonce' );

        if ( ! current_user_can( TECHRAPPY_SEO_CAPABILITY ) ) {
            wp_send_json_error( [ 'message' => __( 'Accès non autorisé.', 'techrappy-seo' ) ], 403 );
        }

        // TODO : implémenter preview city.
        wp_send_json_success( [ 'preview' => '' ] );
    }

    /**
     * Lance la génération en masse (enqueue Action Scheduler).
     *
     * @return void
     */
    public function handle_launch_bulk(): void {
        check_ajax_referer( 'techrappy_seo_bulk', 'nonce' );

        if ( ! current_user_can( TECHRAPPY_SEO_CAPABILITY ) ) {
            wp_send_json_error( [ 'message' => __( 'Accès non autorisé.', 'techrappy-seo' ) ], 403 );
        }

        // TODO : implémenter lancement bulk.
        wp_send_json_success( [ 'job_id' => '' ] );
    }

    /**
     * Retourne le statut et la progression d'un job.
     *
     * @return void
     */
    public function handle_get_job_status(): void {
        check_ajax_referer( 'techrappy_seo_bulk', 'nonce' );

        if ( ! current_user_can( TECHRAPPY_SEO_CAPABILITY ) ) {
            wp_send_json_error( [ 'message' => __( 'Accès non autorisé.', 'techrappy-seo' ) ], 403 );
        }

        // TODO : implémenter récupération statut job.
        wp_send_json_success( [ 'status' => 'pending', 'progress' => 0 ] );
    }
}
