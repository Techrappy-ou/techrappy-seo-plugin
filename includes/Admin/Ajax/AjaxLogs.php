<?php
/**
 * Handler AJAX : Logs de génération.
 *
 * @package TechrappySEO\Admin\Ajax
 */

declare( strict_types=1 );

namespace TechrappySEO\Admin\Ajax;

use TechrappySEO\Jobs\JobRepository;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class AjaxLogs
 */
class AjaxLogs {

    /**
     * Retourne les logs détaillés d'un job.
     *
     * @return void
     */
    public function handle_get_job_logs(): void {
        check_ajax_referer( 'techrappy_seo_logs', 'nonce' );

        if ( ! current_user_can( TECHRAPPY_SEO_CAPABILITY ) ) {
            wp_send_json_error( [ 'message' => __( 'Accès non autorisé.', 'techrappy-seo' ) ], 403 );
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $job_id = sanitize_text_field( $_POST['job_id'] ?? '' );

        if ( ! $job_id ) {
            wp_send_json_error( [ 'message' => __( 'job_id manquant.', 'techrappy-seo' ) ], 400 );
        }

        $job = JobRepository::find( $job_id );

        if ( ! $job ) {
            wp_send_json_error( [ 'message' => __( 'Job introuvable.', 'techrappy-seo' ) ], 404 );
        }

        $logs = is_array( $job['logs'] ) ? $job['logs'] : [];

        // Formater les timestamps lisibles.
        foreach ( $logs as &$entry ) {
            $entry['time_human'] = isset( $entry['t'] )
                ? wp_date( 'd/m/Y H:i:s', $entry['t'] )
                : '—';
        }
        unset( $entry );

        wp_send_json_success( [
            'job_id'  => $job_id,
            'keyword' => $job['keyword'] ?? '',
            'city'    => $job['city']    ?? '',
            'status'  => $job['status']  ?? '',
            'logs'    => $logs,
        ] );
    }

    /**
     * Supprime tous les jobs terminés ou échoués (nettoyage).
     *
     * @return void
     */
    public function handle_clear_logs(): void {
        check_ajax_referer( 'techrappy_seo_logs', 'nonce' );

        if ( ! current_user_can( TECHRAPPY_SEO_CAPABILITY ) ) {
            wp_send_json_error( [ 'message' => __( 'Accès non autorisé.', 'techrappy-seo' ) ], 403 );
        }

        global $wpdb;
        $table = $wpdb->prefix . 'techrappy_seo_jobs';

        // Supprime uniquement les jobs terminés ou échoués (pas les pending/running).
        $deleted = $wpdb->query(
            "DELETE FROM {$table} WHERE status IN ('done', 'done_with_errors', 'failed')"
        );

        wp_send_json_success( [
            'deleted' => (int) $deleted,
            'message' => sprintf(
                /* translators: %d = nombre de jobs supprimés */
                __( '%d job(s) supprimé(s).', 'techrappy-seo' ),
                (int) $deleted
            ),
        ] );
    }
}
