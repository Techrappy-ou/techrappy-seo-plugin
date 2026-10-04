<?php
/**
 * Handler AJAX : Wizard de génération.
 *
 * @package TechrappySEO\Admin\Ajax
 */

declare( strict_types=1 );

namespace TechrappySEO\Admin\Ajax;

use TechrappySEO\Jobs\JobRepository;
use TechrappySEO\Jobs\QueueScheduler;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class AjaxWizard
 *
 * Gère les actions du wizard de génération :
 *  - get_templates  : liste les pages/posts disponibles comme template Divi
 *  - create_job     : crée le job et le planifie
 *  - get_job_status : retourne le statut + données du job
 */
class AjaxWizard {

    /**
     * Dispatch vers la bonne action wizard.
     *
     * @return void
     */
    public function handle_step(): void {
        check_ajax_referer( 'techrappy_seo_wizard', 'nonce' );

        if ( ! current_user_can( TECHRAPPY_SEO_CAPABILITY ) ) {
            wp_send_json_error( [ 'message' => __( 'Accès non autorisé.', 'techrappy-seo' ) ], 403 );
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $action = sanitize_key( $_POST['wizard_action'] ?? '' );

        switch ( $action ) {
            case 'get_templates':
                $this->action_get_templates();
                break;
            case 'create_job':
                $this->action_create_job();
                break;
            case 'get_job_status':
                $this->action_get_job_status();
                break;
            default:
                wp_send_json_error( [ 'message' => __( 'Action wizard inconnue.', 'techrappy-seo' ) ], 400 );
        }
    }

    // -------------------------------------------------------------------------
    // Actions privées
    // -------------------------------------------------------------------------

    /**
     * Retourne la liste des templates disponibles (pages/posts publiés).
     *
     * @return void
     */
    private function action_get_templates(): void {
        $posts = get_posts( [
            'post_type'      => [ 'page', 'post' ],
            'post_status'    => 'publish',
            'posts_per_page' => 200,
            'orderby'        => 'title',
            'order'          => 'ASC',
        ] );

        $templates = array_map( static function ( \WP_Post $post ): array {
            return [
                'id'    => $post->ID,
                'title' => get_the_title( $post ),
                'type'  => $post->post_type,
                'url'   => get_permalink( $post ),
            ];
        }, $posts );

        wp_send_json_success( [ 'templates' => $templates ] );
    }

    /**
     * Crée un job single et le planifie.
     *
     * @return void
     */
    private function action_create_job(): void {
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $post_data = $_POST;

        $keyword    = sanitize_text_field( $post_data['keyword']    ?? '' );
        $profession = sanitize_text_field( $post_data['profession'] ?? '' );

        if ( ! $keyword || ! $profession ) {
            wp_send_json_error( [ 'message' => __( 'Mot-clé et profession requis.', 'techrappy-seo' ) ], 400 );
        }

        $job_id = JobRepository::insert( [
            'mode'             => 'single',
            'type'             => sanitize_key( $post_data['type']            ?? 'page' ),
            'template_post_id' => absint( $post_data['template_post_id']      ?? 0 ),
            'keyword'          => $keyword,
            'city'             => sanitize_text_field( $post_data['city']     ?? '' ),
            'publish_status'   => sanitize_key( $post_data['publish_status']  ?? 'draft' ),
            'slug_rule'        => sanitize_key( $post_data['slug_rule']       ?? 'from_keyword' ),
            'wp_params'        => [
                'profession'     => $profession,
                'author_id'      => absint( $post_data['author_id']      ?? 0 ),
                'parent_id'      => absint( $post_data['parent_id']      ?? 0 ),
                'category_id'    => absint( $post_data['category_id']    ?? 0 ),
                'tags'           => array_map( 'absint', (array) ( $post_data['tags'] ?? [] ) ),
                'menu_action'    => sanitize_key(        $post_data['menu_action']    ?? 'none' ),
                'menu_id'        => absint(              $post_data['menu_id']        ?? 0 ),
                'menu_name'      => sanitize_text_field( $post_data['menu_name']      ?? '' ),
                'menu_location'  => sanitize_key(        $post_data['menu_location']  ?? '' ),
                'label_format'   => sanitize_key(        $post_data['label_format']   ?? 'post_title' ),
                'label_template' => sanitize_text_field( $post_data['label_template'] ?? '' ),
                'user_intent'    => sanitize_textarea_field( $post_data['user_intent'] ?? '' ),
            ],
        ] );

        if ( ! $job_id ) {
            wp_send_json_error( [ 'message' => __( 'Impossible de créer le job.', 'techrappy-seo' ) ], 500 );
        }

        QueueScheduler::schedule_single( $job_id );

        // Déclencher immédiatement le cron WordPress en arrière-plan.
        spawn_cron();

        wp_send_json_success( [
            'job_id'  => $job_id,
            'message' => __( 'Job créé et planifié.', 'techrappy-seo' ),
        ] );
    }

    /**
     * Retourne le statut et les données d'un job.
     *
     * @return void
     */
    private function action_get_job_status(): void {
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $job_id = sanitize_text_field( $_POST['job_id'] ?? '' );

        if ( ! $job_id ) {
            wp_send_json_error( [ 'message' => __( 'job_id manquant.', 'techrappy-seo' ) ], 400 );
        }

        $job = JobRepository::find( $job_id );

        if ( ! $job ) {
            wp_send_json_error( [ 'message' => __( 'Job introuvable.', 'techrappy-seo' ) ], 404 );
        }

        wp_send_json_success( [
            'job_id'      => $job['job_id'],
            'status'      => $job['status'],
            'steps'       => $job['steps_data']  ?? [],
            'result'      => $job['result_data']  ?? [],
            'logs'        => $job['logs']         ?? [],
            'created_at'  => $job['created_at']   ?? '',
        ] );
    }
}
