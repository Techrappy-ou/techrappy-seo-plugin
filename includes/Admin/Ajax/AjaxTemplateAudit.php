<?php
/**
 * Handler AJAX : Audit de templates Divi.
 *
 * @package TechrappySEO\Admin\Ajax
 */

declare( strict_types=1 );

namespace TechrappySEO\Admin\Ajax;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class AjaxTemplateAudit
 */
class AjaxTemplateAudit {

    /**
     * Lance le scan d'un template pour détecter les tokens.
     *
     * @return void
     */
    public function handle_scan(): void {
        check_ajax_referer( 'techrappy_seo_audit', 'nonce' );

        if ( ! current_user_can( TECHRAPPY_SEO_CAPABILITY ) ) {
            wp_send_json_error( [ 'message' => __( 'Accès non autorisé.', 'techrappy-seo' ) ], 403 );
        }

        // TODO : implémenter le scan de template.
        wp_send_json_success( [ 'tokens_found' => [], 'tokens_missing' => [] ] );
    }

    /**
     * Sauvegarde le mapping token→source pour un template.
     *
     * @return void
     */
    public function handle_save_mapping(): void {
        check_ajax_referer( 'techrappy_seo_audit', 'nonce' );

        if ( ! current_user_can( TECHRAPPY_SEO_CAPABILITY ) ) {
            wp_send_json_error( [ 'message' => __( 'Accès non autorisé.', 'techrappy-seo' ) ], 403 );
        }

        // TODO : implémenter sauvegarde mapping.
        wp_send_json_success( [ 'saved' => true ] );
    }
}
