<?php
/**
 * Handler AJAX : Génération pipeline (step-by-step et full).
 *
 * @package TechrappySEO\Admin\Ajax
 */

declare( strict_types=1 );

namespace TechrappySEO\Admin\Ajax;

use TechrappySEO\Jobs\JobRepository;
use TechrappySEO\Jobs\JobRunner;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class AjaxGeneration
 */
class AjaxGeneration {

    /**
     * Lance le pipeline complet pour un job existant ou en crée un nouveau.
     *
     * Paramètres POST attendus :
     * - nonce         (string) Nonce de sécurité.
     * - keyword       (string) Mot-clé principal (obligatoire).
     * - city          (string) Ville (optionnel).
     * - type          (string) 'page_seo' ou 'article_seo' (défaut: page_seo).
     * - profession    (string) Activité / profession (défaut: professionnel de santé).
     * - publish_status (string) 'draft' | 'publish' | 'pending'.
     * - slug_rule     (string) 'from_keyword' | 'from_h1'.
     * - pages_site    (string) JSON des pages {title, url}[] pour les liens internes.
     * - category_id   (int)    ID catégorie WP (pour type 'post').
     * - parent_page_id (int)   ID page parente WP (pour type 'page').
     *
     * @return void
     */
    public function handle_run_pipeline(): void {
        check_ajax_referer( 'techrappy_seo_generation', 'nonce' );

        if ( ! current_user_can( TECHRAPPY_SEO_CAPABILITY ) ) {
            wp_send_json_error( [ 'message' => __( 'Accès non autorisé.', 'techrappy-seo' ) ], 403 );
        }

        // ── Lecture et sanitisation des paramètres ──────────────────────

        $keyword = sanitize_text_field( trim( $_POST['keyword'] ?? '' ) );

        if ( empty( $keyword ) ) {
            wp_send_json_error( [
                'message' => __( 'Le mot-clé est obligatoire.', 'techrappy-seo' ),
            ], 400 );
        }

        $city           = sanitize_text_field( trim( $_POST['city'] ?? '' ) );
        $type           = sanitize_key( $_POST['type'] ?? 'page_seo' );
        $publish_status = sanitize_key( $_POST['publish_status'] ?? 'draft' );
        $slug_rule      = sanitize_key( $_POST['slug_rule'] ?? 'from_keyword' );

        // Pages site pour les liens internes.
        $pages_raw  = $_POST['pages_site'] ?? '[]';
        $pages_site = [];
        if ( is_string( $pages_raw ) ) {
            $decoded = json_decode( stripslashes( $pages_raw ), true );
            if ( is_array( $decoded ) ) {
                $pages_site = $decoded;
            }
        }

        $wp_params = [
            'profession'     => sanitize_text_field( $_POST['profession'] ?? 'professionnel de santé' ),
            'pages_site'     => $pages_site,
            'category_id'    => absint( $_POST['category_id'] ?? 0 ),
            'parent_page_id' => absint( $_POST['parent_page_id'] ?? 0 ),
        ];

        // ── Création du job en base ─────────────────────────────────────

        $job_id = JobRepository::insert( [
            'mode'           => 'single',
            'type'           => $type,
            'keyword'        => $keyword,
            'city'           => $city,
            'publish_status' => $publish_status,
            'slug_rule'      => $slug_rule,
            'wp_params'      => $wp_params,
        ] );

        if ( false === $job_id ) {
            wp_send_json_error( [
                'message' => __( 'Impossible de créer le job en base.', 'techrappy-seo' ),
            ], 500 );
        }

        // ── Exécution synchrone du pipeline ────────────────────────────
        // En V1, l'exécution est synchrone. Pour les gros volumes, utiliser
        // QueueScheduler avec Action Scheduler.

        $runner = new JobRunner();
        $result = $runner->run( $job_id );

        if ( null === $result ) {
            wp_send_json_error( [
                'message' => __( 'La génération a échoué. Consultez les logs du job.', 'techrappy-seo' ),
                'job_id'  => $job_id,
            ], 500 );
        }

        wp_send_json_success( [
            'job_id'    => $job_id,
            'post_id'   => $result['post_id'],
            'permalink' => $result['permalink'],
            'slug'      => $result['slug'],
            'message'   => __( 'Génération terminée avec succès.', 'techrappy-seo' ),
        ] );
    }

    /**
     * Lance une étape unique du pipeline (pour le mode step-by-step du wizard).
     *
     * Paramètres POST attendus :
     * - nonce    (string) Nonce de sécurité.
     * - job_id   (string) UUID du job existant.
     * - step     (string) Nom de l'étape à exécuter.
     *
     * @return void
     */
    public function handle_run_step(): void {
        check_ajax_referer( 'techrappy_seo_generation', 'nonce' );

        if ( ! current_user_can( TECHRAPPY_SEO_CAPABILITY ) ) {
            wp_send_json_error( [ 'message' => __( 'Accès non autorisé.', 'techrappy-seo' ) ], 403 );
        }

        $job_id = sanitize_text_field( $_POST['job_id'] ?? '' );

        if ( empty( $job_id ) ) {
            wp_send_json_error( [ 'message' => __( 'job_id manquant.', 'techrappy-seo' ) ], 400 );
        }

        $job = JobRepository::find( $job_id );

        if ( ! $job ) {
            wp_send_json_error( [ 'message' => __( 'Job introuvable.', 'techrappy-seo' ) ], 404 );
        }

        // Pour le mode step-by-step, on lance quand même le pipeline complet
        // mais on retourne uniquement les steps_data accumulées à ce stade.
        // En V2 : implémenter une exécution par étape individuelle.
        $runner = new JobRunner();
        $result = $runner->run( $job_id );

        if ( null === $result ) {
            $job_updated = JobRepository::find( $job_id );
            wp_send_json_error( [
                'message'    => __( 'Étape échouée. Consultez les logs.', 'techrappy-seo' ),
                'steps_data' => $job_updated['steps_data'] ?? [],
            ], 500 );
        }

        wp_send_json_success( [
            'job_id'    => $job_id,
            'post_id'   => $result['post_id'],
            'permalink' => $result['permalink'],
        ] );
    }
}
