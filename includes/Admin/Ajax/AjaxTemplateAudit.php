<?php
/**
 * Handler AJAX : Audit de templates Divi.
 *
 * @package TechrappySEO\Admin\Ajax
 */

declare( strict_types=1 );

namespace TechrappySEO\Admin\Ajax;

use TechrappySEO\Divi\DiviTemplateHandler;
use TechrappySEO\Templates\TemplateAuditor;
use TechrappySEO\Templates\TemplateMappingRepository;

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

        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $post_id = absint( $_POST['post_id'] ?? 0 );

        if ( ! $post_id ) {
            wp_send_json_error( [ 'message' => __( 'post_id manquant.', 'techrappy-seo' ) ], 400 );
        }

        $auditor = new TemplateAuditor();
        $result  = $auditor->audit( $post_id );

        // Récupérer le mapping existant pour ce template.
        $mapping_repo = new TemplateMappingRepository();
        $result['existing_mapping'] = $mapping_repo->get( $post_id );

        wp_send_json_success( $result );
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

        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $post_id = absint( $_POST['post_id'] ?? 0 );
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $raw_mapping = $_POST['mapping'] ?? [];

        if ( ! $post_id ) {
            wp_send_json_error( [ 'message' => __( 'post_id manquant.', 'techrappy-seo' ) ], 400 );
        }

        if ( ! is_array( $raw_mapping ) ) {
            wp_send_json_error( [ 'message' => __( 'Mapping invalide.', 'techrappy-seo' ) ], 400 );
        }

        // Sanitizer le mapping.
        $mapping = [];
        foreach ( $raw_mapping as $token => $source ) {
            $token = sanitize_key( $token );
            $source = sanitize_text_field( $source );
            if ( $token && $source ) {
                $mapping[ $token ] = $source;
            }
        }

        $repo   = new TemplateMappingRepository();
        $saved  = $repo->save( $post_id, $mapping );

        if ( ! $saved ) {
            wp_send_json_error( [ 'message' => __( 'Erreur lors de la sauvegarde.', 'techrappy-seo' ) ], 500 );
        }

        wp_send_json_success( [
            'saved'   => true,
            'post_id' => $post_id,
            'mapping' => $mapping,
        ] );
    }
}
