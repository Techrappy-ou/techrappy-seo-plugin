<?php
/**
 * Handler AJAX : Génération pipeline (step-by-step et full).
 *
 * @package TechrappySEO\Admin\Ajax
 */

declare( strict_types=1 );

namespace TechrappySEO\Admin\Ajax;

use TechrappySEO\AI\Pipeline\PipelineRunner;
use TechrappySEO\Jobs\JobRepository;
use TechrappySEO\Jobs\QueueScheduler;
use TechrappySEO\Utils\Logger;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class AjaxGeneration
 */
class AjaxGeneration {

    /**
     * Lance une étape unique du pipeline sur un job existant.
     *
     * @return void
     */
    public function handle_run_step(): void {
        check_ajax_referer( 'techrappy_seo_generation', 'nonce' );

        if ( ! current_user_can( TECHRAPPY_SEO_CAPABILITY ) ) {
            wp_send_json_error( [ 'message' => __( 'Accès non autorisé.', 'techrappy-seo' ) ], 403 );
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $job_id    = sanitize_text_field( $_POST['job_id']    ?? '' );
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $step_name = sanitize_key( $_POST['step_name'] ?? '' );

        if ( ! $job_id || ! $step_name ) {
            wp_send_json_error( [ 'message' => __( 'Paramètres manquants.', 'techrappy-seo' ) ], 400 );
        }

        $db_job = JobRepository::find( $job_id );
        if ( ! $db_job ) {
            wp_send_json_error( [ 'message' => __( 'Job introuvable.', 'techrappy-seo' ) ], 404 );
        }

        $step_class = $this->resolve_step_class( $step_name );
        if ( ! $step_class ) {
            wp_send_json_error( [ 'message' => __( 'Étape inconnue.', 'techrappy-seo' ) ], 400 );
        }

        $logger = new Logger( $job_id );
        if ( ! empty( $db_job['logs'] ) && is_array( $db_job['logs'] ) ) {
            $logger->load( $db_job['logs'] );
        }

        $wp_params    = is_array( $db_job['wp_params'] ) ? $db_job['wp_params'] : [];
        $pipeline_job = $this->build_pipeline_job( $db_job, $wp_params );

        $step = new $step_class();
        $data = $step->run( $pipeline_job, $logger );

        // Mettre à jour les steps en base.
        $steps = $pipeline_job['steps'];
        $steps[ $step_name ] = [
            'status' => ! empty( $data ) ? 'ok' : 'error',
            'data'   => $data,
        ];
        JobRepository::update_steps( $job_id, $steps, $logger->get_logs() );

        wp_send_json_success( [
            'step'   => $step_name,
            'status' => ! empty( $data ) ? 'ok' : 'error',
            'data'   => $data,
        ] );
    }

    /**
     * Crée un job et le planifie via Action Scheduler.
     *
     * @return void
     */
    public function handle_run_pipeline(): void {
        check_ajax_referer( 'techrappy_seo_generation', 'nonce' );

        if ( ! current_user_can( TECHRAPPY_SEO_CAPABILITY ) ) {
            wp_send_json_error( [ 'message' => __( 'Accès non autorisé.', 'techrappy-seo' ) ], 403 );
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $post_data = $_POST;

        $keyword    = sanitize_text_field( $post_data['keyword']    ?? '' );
        $profession = sanitize_text_field( $post_data['profession'] ?? '' );

        if ( ! $keyword || ! $profession ) {
            wp_send_json_error( [ 'message' => __( 'Le mot-clé et la profession sont requis.', 'techrappy-seo' ) ], 400 );
        }

        $job_id = JobRepository::insert( [
            'mode'             => 'single',
            'type'             => sanitize_key( $post_data['type']           ?? 'page' ),
            'template_post_id' => absint( $post_data['template_post_id']     ?? 0 ),
            'keyword'          => $keyword,
            'city'             => sanitize_text_field( $post_data['city']    ?? '' ),
            'publish_status'   => sanitize_key( $post_data['publish_status'] ?? 'draft' ),
            'slug_rule'        => sanitize_key( $post_data['slug_rule']      ?? 'from_keyword' ),
            'wp_params'        => [
                'profession'  => $profession,
                'parent_id'   => absint( $post_data['parent_id']    ?? 0 ),
                'category_id' => absint( $post_data['category_id']  ?? 0 ),
                'tags'        => array_map( 'absint', (array) ( $post_data['tags'] ?? [] ) ),
            ],
        ] );

        if ( ! $job_id ) {
            wp_send_json_error( [ 'message' => __( 'Erreur lors de la création du job.', 'techrappy-seo' ) ], 500 );
        }

        QueueScheduler::schedule_single( $job_id );

        wp_send_json_success( [
            'job_id'  => $job_id,
            'message' => __( 'Génération planifiée.', 'techrappy-seo' ),
        ] );
    }

    /**
     * Retourne le FQCN de la classe Step correspondant au nom.
     *
     * @param string $step_name Nom de l'étape.
     *
     * @return class-string|null
     */
    private function resolve_step_class( string $step_name ): ?string {
        $map = [
            'intent'         => \TechrappySEO\AI\Pipeline\Steps\StepIntent::class,
            'plan'           => \TechrappySEO\AI\Pipeline\Steps\StepPlan::class,
            'blocks_list'    => \TechrappySEO\AI\Pipeline\Steps\StepBlocksList::class,
            'intro'          => \TechrappySEO\AI\Pipeline\Steps\StepIntro::class,
            'conclusion_cta' => \TechrappySEO\AI\Pipeline\Steps\StepConclusion::class,
            'meta'           => \TechrappySEO\AI\Pipeline\Steps\StepMeta::class,
            'faq'            => \TechrappySEO\AI\Pipeline\Steps\StepFaq::class,
            'internal_links' => \TechrappySEO\AI\Pipeline\Steps\StepInternalLinks::class,
            'anti_duplicate' => \TechrappySEO\AI\Pipeline\Steps\StepAntiDuplicate::class,
            'qa'             => \TechrappySEO\AI\Pipeline\Steps\StepQA::class,
        ];

        return $map[ $step_name ] ?? null;
    }

    /**
     * Construit le tableau job compatible PipelineRunner depuis la ligne DB.
     *
     * @param array<string, mixed> $db_job    Ligne DB décodée.
     * @param array<string, mixed> $wp_params Paramètres WordPress décodés.
     *
     * @return array<string, mixed>
     */
    private function build_pipeline_job( array $db_job, array $wp_params ): array {
        return [
            'job_id'           => $db_job['job_id'],
            'mode'             => $db_job['mode']           ?? 'single',
            'type'             => $db_job['type']           ?? 'page',
            'template_post_id' => absint( $db_job['template_post_id'] ?? 0 ),
            'keyword'          => $db_job['keyword']        ?? '',
            'keyword_base'     => $db_job['keyword']        ?? '',
            'city'             => $db_job['city']           ?? '',
            'profession'       => $wp_params['profession']  ?? '',
            'publish_status'   => $db_job['publish_status'] ?? 'draft',
            'wp_params'        => $wp_params,
            'slug_rule'        => $db_job['slug_rule']      ?? 'from_keyword',
            'steps'            => is_array( $db_job['steps_data'] ) ? $db_job['steps_data'] : [],
            'result_data'      => is_array( $db_job['result_data'] ) ? $db_job['result_data'] : [],
            '_system_prompt'   => '',
        ];
    }
}
