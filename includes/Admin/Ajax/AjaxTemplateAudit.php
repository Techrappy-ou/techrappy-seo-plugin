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
     * Exporte un template Divi en JSON (structure + tokens + mapping).
     *
     * @return void
     */
    public function handle_export_template(): void {
        check_ajax_referer( 'techrappy_seo_audit', 'nonce' );

        if ( ! current_user_can( TECHRAPPY_SEO_CAPABILITY ) ) {
            wp_send_json_error( [ 'message' => __( 'Accès non autorisé.', 'techrappy-seo' ) ], 403 );
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $post_id = absint( $_POST['post_id'] ?? 0 );

        if ( ! $post_id ) {
            wp_send_json_error( [ 'message' => __( 'post_id manquant.', 'techrappy-seo' ) ], 400 );
        }

        $post = get_post( $post_id );
        if ( ! $post ) {
            wp_send_json_error( [ 'message' => __( 'Post introuvable.', 'techrappy-seo' ) ], 404 );
        }

        $auditor      = new TemplateAuditor();
        $audit        = $auditor->audit( $post_id );
        $mapping_repo = new TemplateMappingRepository();
        $mapping      = $mapping_repo->get( $post_id );

        $export = [
            'version'      => '1.0',
            'exported_at'  => gmdate( 'Y-m-d\TH:i:s\Z' ),
            'post_title'   => get_the_title( $post ),
            'post_type'    => $post->post_type,
            'post_content' => $post->post_content,
            'tokens_found' => $audit['tokens_found'],
            'has_divi'     => $audit['has_divi'],
            'mapping'      => $mapping,
        ];

        wp_send_json_success( [
            'filename' => 'techrappy-template-' . sanitize_title( get_the_title( $post ) ) . '.json',
            'data'     => $export,
        ] );
    }

    /**
     * Importe un template Divi depuis un JSON exporté.
     * Crée un nouveau brouillon WP avec le contenu importé.
     *
     * @return void
     */
    public function handle_import_template(): void {
        check_ajax_referer( 'techrappy_seo_audit', 'nonce' );

        if ( ! current_user_can( TECHRAPPY_SEO_CAPABILITY ) ) {
            wp_send_json_error( [ 'message' => __( 'Accès non autorisé.', 'techrappy-seo' ) ], 403 );
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $raw_json = sanitize_textarea_field( wp_unslash( $_POST['json_data'] ?? '' ) );

        if ( empty( $raw_json ) ) {
            wp_send_json_error( [ 'message' => __( 'Données JSON manquantes.', 'techrappy-seo' ) ], 400 );
        }

        $data = json_decode( $raw_json, true );

        if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $data ) ) {
            wp_send_json_error( [ 'message' => __( 'JSON invalide.', 'techrappy-seo' ) ], 422 );
        }

        if ( empty( $data['post_content'] ) ) {
            wp_send_json_error( [ 'message' => __( 'Contenu Divi absent dans le JSON.', 'techrappy-seo' ) ], 422 );
        }

        $post_title = sanitize_text_field( $data['post_title'] ?? 'Template importé' );
        $post_type  = sanitize_key( $data['post_type'] ?? 'page' );

        $post_id = wp_insert_post( [
            'post_title'   => $post_title . ' (importé)',
            'post_content' => $data['post_content'],
            'post_status'  => 'draft',
            'post_type'    => in_array( $post_type, [ 'page', 'post' ], true ) ? $post_type : 'page',
        ], true );

        if ( is_wp_error( $post_id ) ) {
            wp_send_json_error( [ 'message' => $post_id->get_error_message() ], 500 );
        }

        if ( ! empty( $data['mapping'] ) && is_array( $data['mapping'] ) ) {
            $mapping_repo = new TemplateMappingRepository();
            $mapping_repo->save( $post_id, $data['mapping'] );
        }

        $auditor = new TemplateAuditor();
        $audit   = $auditor->audit( $post_id );

        wp_send_json_success( [
            'post_id'        => $post_id,
            'post_title'     => get_the_title( $post_id ),
            'edit_url'       => get_edit_post_link( $post_id, 'raw' ),
            'tokens_found'   => $audit['tokens_found'],
            'tokens_missing' => $audit['tokens_missing'],
            'has_divi'       => $audit['has_divi'],
            'message'        => __( 'Template importé et créé en brouillon.', 'techrappy-seo' ),
        ] );
    }

    /**
     * Diagnostique le format d'encodage des tokens dans un template.
     *
     * Retourne pour chaque format connu (plain, HTML-entity, URL-encodé…)
     * combien de tokens sont trouvés et lesquels — essentiel pour comprendre
     * pourquoi le TokenReplacer ne les trouve pas.
     *
     * @return void
     */
    public function handle_debug_tokens(): void {
        check_ajax_referer( 'techrappy_seo_audit', 'nonce' );

        if ( ! current_user_can( TECHRAPPY_SEO_CAPABILITY ) ) {
            wp_send_json_error( [ 'message' => 'Accès non autorisé.' ], 403 );
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $post_id = absint( $_POST['post_id'] ?? 0 );

        if ( ! $post_id ) {
            wp_send_json_error( [ 'message' => 'post_id manquant.' ], 400 );
        }

        $post = get_post( $post_id );
        if ( ! $post || empty( $post->post_content ) ) {
            wp_send_json_error( [ 'message' => 'Post introuvable ou contenu vide.' ], 404 );
        }

        $content = $post->post_content;

        // ── Détecter chaque format d'encodage connu ───────────────────────
        $formats = [];

        // Format A : {{TOKEN}} brut.
        preg_match_all( '/\{\{([A-Za-z0-9_]+)\}\}/', $content, $m );
        $formats['plain'] = [
            'label'   => '{{TOKEN}} (brut)',
            'count'   => count( $m[0] ),
            'tokens'  => array_values( array_unique( $m[1] ) ),
            'handled' => true,
        ];

        // Format B : &#123;&#123;TOKEN&#125;&#125; (entités HTML décimales).
        preg_match_all( '/&#123;&#123;([A-Za-z0-9_]+)&#125;&#125;/', $content, $m );
        $formats['html_dec'] = [
            'label'   => '&#123;&#123;TOKEN&#125;&#125; (entités HTML décimales)',
            'count'   => count( $m[0] ),
            'tokens'  => array_values( array_unique( $m[1] ) ),
            'handled' => true,
        ];

        // Format C : %7B%7BTOKEN%7D%7D (URL-encodé).
        preg_match_all( '/%7B%7B([A-Za-z0-9_]+)%7D%7D/i', $content, $m );
        $formats['url_encoded'] = [
            'label'   => '%7B%7BTOKEN%7D%7D (URL-encodé)',
            'count'   => count( $m[0] ),
            'tokens'  => array_values( array_unique( $m[1] ) ),
            'handled' => true,
        ];

        // Format D : &#x7B;&#x7B;TOKEN&#x7D;&#x7D; (entités HTML hexadécimales).
        preg_match_all( '/&#x7[Bb];&#x7[Bb];([A-Za-z0-9_]+)&#x7[Dd];&#x7[Dd];/', $content, $m );
        $formats['html_hex'] = [
            'label'   => '&#x7B;&#x7B;TOKEN&#x7D;&#x7D; (entités HTML hex)',
            'count'   => count( $m[0] ),
            'tokens'  => array_values( array_unique( $m[1] ) ),
            'handled' => false,
        ];

        // Format E : \u007B\u007BTOKEN\u007D\u007D (unicode escape JS).
        preg_match_all( '/\\\\u007[Bb]\\\\u007[Bb]([A-Za-z0-9_]+)\\\\u007[Dd]\\\\u007[Dd]/', $content, $m );
        $formats['unicode_escape'] = [
            'label'   => '\\u007B\\u007BTOKEN\\u007D\\u007D (unicode escape)',
            'count'   => count( $m[0] ),
            'tokens'  => array_values( array_unique( $m[1] ) ),
            'handled' => false,
        ];

        // ── Extrait brut autour du premier { ─────────────────────────────
        $raw_excerpt  = '';
        $first_brace  = strpos( $content, '{' );
        $first_entity = strpos( $content, '&#' );
        $first_pct    = strpos( $content, '%7B' );
        $first_pos    = false;
        foreach ( [ $first_brace, $first_entity, $first_pct ] as $p ) {
            if ( false !== $p && ( false === $first_pos || $p < $first_pos ) ) {
                $first_pos = $p;
            }
        }

        if ( false !== $first_pos ) {
            $start      = max( 0, $first_pos - 30 );
            $raw_excerpt = substr( $content, $start, 300 );
        }

        wp_send_json_success( [
            'post_title'     => get_the_title( $post ),
            'content_length' => strlen( $content ),
            'has_divi'       => str_contains( $content, '[et_pb_' ),
            'formats'        => $formats,
            'raw_excerpt'    => $raw_excerpt,
        ] );
    }

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
