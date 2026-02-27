<?php
/**
 * Handler AJAX : Bulk Jobs (villes, preview, lancement, statut).
 *
 * @package TechrappySEO\Admin\Ajax
 */

declare( strict_types=1 );

namespace TechrappySEO\Admin\Ajax;

use TechrappySEO\Geo\VillesVoisinesClient;
use TechrappySEO\Jobs\BulkJobManager;
use TechrappySEO\Jobs\JobRepository;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class AjaxBulk
 */
class AjaxBulk {

    /**
     * Récupère les villes à partir d'une ville principale et d'un rayon.
     *
     * @return void
     */
    public function handle_get_cities(): void {
        check_ajax_referer( 'techrappy_seo_bulk', 'nonce' );

        if ( ! current_user_can( TECHRAPPY_SEO_CAPABILITY ) ) {
            wp_send_json_error( [ 'message' => __( 'Accès non autorisé.', 'techrappy-seo' ) ], 403 );
        }

        // Accepte "cp" (nouveau) ou "ville_principale" (rétrocompat).
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $cp        = sanitize_text_field( $_POST['cp'] ?? $_POST['ville_principale'] ?? '' );
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $radius_km = absint( $_POST['radius_km'] ?? 30 );

        if ( ! $cp ) {
            wp_send_json_error( [ 'message' => __( 'Code postal requis.', 'techrappy-seo' ) ], 400 );
        }

        $limit  = (int) \TechrappySEO\Settings\SettingsRepository::get( 'bulk_max_cities', 50 );
        $client = new VillesVoisinesClient();
        $cities = $client->get_nearby_cities( $cp, $radius_km, $limit );

        /**
         * Filtre pour personnaliser la liste de villes.
         *
         * @param array<int, array{city: string, cp: string}> $cities
         * @param string $cp
         * @param int    $radius_km
         */
        $cities = apply_filters( 'techrappy_seo_bulk_cities', $cities, $cp, $radius_km );

        wp_send_json_success( [
            'cities' => $cities,
            'total'  => count( $cities ),
        ] );
    }

    /**
     * Génère une prévisualisation rapide pour le premier job d'une série bulk.
     *
     * @return void
     */
    public function handle_preview_city(): void {
        check_ajax_referer( 'techrappy_seo_bulk', 'nonce' );

        if ( ! current_user_can( TECHRAPPY_SEO_CAPABILITY ) ) {
            wp_send_json_error( [ 'message' => __( 'Accès non autorisé.', 'techrappy-seo' ) ], 403 );
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $keyword_base = sanitize_text_field( $_POST['keyword_base'] ?? '' );
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $city = sanitize_text_field( $_POST['city'] ?? '' );

        if ( ! $keyword_base ) {
            wp_send_json_error( [ 'message' => __( 'Mot-clé de base requis.', 'techrappy-seo' ) ], 400 );
        }

        $keyword_complet = trim( $keyword_base . ( $city ? ' ' . $city : '' ) );
        $slug_preview    = sanitize_title( $keyword_complet );

        wp_send_json_success( [
            'keyword_complet' => $keyword_complet,
            'slug_preview'    => $slug_preview,
            'example_h1'      => ucfirst( $keyword_complet ),
        ] );
    }

    /**
     * Lance la génération en masse.
     *
     * @return void
     */
    public function handle_launch_bulk(): void {
        check_ajax_referer( 'techrappy_seo_bulk', 'nonce' );

        if ( ! current_user_can( TECHRAPPY_SEO_CAPABILITY ) ) {
            wp_send_json_error( [ 'message' => __( 'Accès non autorisé.', 'techrappy-seo' ) ], 403 );
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $post_data    = $_POST;
        $keyword_base = sanitize_text_field( $post_data['keyword_base'] ?? '' );
        $profession   = sanitize_text_field( $post_data['profession']   ?? '' );

        if ( ! $keyword_base || ! $profession ) {
            wp_send_json_error( [ 'message' => __( 'Mot-clé de base et profession requis.', 'techrappy-seo' ) ], 400 );
        }

        $cities_raw = $post_data['cities'] ?? [];
        if ( ! is_array( $cities_raw ) || empty( $cities_raw ) ) {
            wp_send_json_error( [ 'message' => __( 'Liste de villes vide.', 'techrappy-seo' ) ], 400 );
        }

        // Sanitizer les villes — accepte string "Toulouse" OU objet {city, cp}.
        $cities = [];
        foreach ( $cities_raw as $city_data ) {
            if ( is_string( $city_data ) && '' !== trim( $city_data ) ) {
                // Format ancien : juste le nom de ville (string).
                $cities[] = [
                    'city' => sanitize_text_field( $city_data ),
                    'cp'   => '',
                ];
            } elseif ( is_array( $city_data ) && ! empty( $city_data['city'] ) ) {
                // Format nouveau : objet {city, cp}.
                $cities[] = [
                    'city' => sanitize_text_field( $city_data['city'] ),
                    'cp'   => sanitize_text_field( $city_data['cp'] ?? '' ),
                ];
            }
        }

        if ( empty( $cities ) ) {
            wp_send_json_error( [ 'message' => __( 'Aucune ville valide.', 'techrappy-seo' ) ], 400 );
        }

        $bulk_params = [
            'keyword_base'     => $keyword_base,
            'type'             => sanitize_key( $post_data['type']           ?? 'page' ),
            'template_post_id' => absint( $post_data['template_post_id']     ?? 0 ),
            'publish_status'   => sanitize_key( $post_data['publish_status'] ?? 'draft' ),
            'slug_rule'        => sanitize_key( $post_data['slug_rule']      ?? 'from_keyword' ),
            'wp_params'        => [
                'profession'     => $profession,
                'parent_id'      => absint(              $post_data['parent_id']      ?? 0 ),
                'category_id'    => absint(              $post_data['category_id']    ?? 0 ),
                'tags'           => array_map( 'absint', (array) ( $post_data['tags'] ?? [] ) ),
                'menu_action'    => sanitize_key(        $post_data['menu_action']    ?? 'none' ),
                'menu_id'        => absint(              $post_data['menu_id']        ?? 0 ),
                'menu_name'      => sanitize_text_field( $post_data['menu_name']      ?? '' ),
                'menu_location'  => sanitize_key(        $post_data['menu_location']  ?? '' ),
                'label_format'   => sanitize_key(        $post_data['label_format']   ?? 'post_title' ),
                'label_template' => sanitize_text_field( $post_data['label_template'] ?? '' ),
            ],
        ];

        $manager   = new BulkJobManager();
        $parent_id = $manager->create_and_dispatch( $bulk_params, $cities );

        if ( ! $parent_id ) {
            wp_send_json_error( [ 'message' => __( 'Échec de la création du job bulk.', 'techrappy-seo' ) ], 500 );
        }

        // Planifier la première vérification de progression.
        \TechrappySEO\Jobs\QueueScheduler::schedule_progress_check( $parent_id );

        // Forcer l'exécution immédiate du cron en arrière-plan.
        spawn_cron();

        wp_send_json_success( [
            'job_id'  => $parent_id,
            'total'   => count( $cities ),
            'message' => sprintf(
                /* translators: %d = nombre de villes */
                __( 'Génération bulk lancée pour %d villes.', 'techrappy-seo' ),
                count( $cities )
            ),
        ] );
    }

    /**
     * Relance un job échoué (single ou bulk).
     *
     * @return void
     */
    public function handle_retry_job(): void {
        check_ajax_referer( 'techrappy_seo_bulk', 'nonce' );

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

        // ── Cas bulk parent : relancer uniquement les jobs enfants en échec ──
        if ( 'bulk' === $job['mode'] && empty( $job['parent_job_id'] ) ) {
            $children = JobRepository::list( [
                'parent_job_id' => $job_id,
                'status'        => 'failed',
                'limit'         => 200,
            ] );

            $retried = 0;
            foreach ( $children as $child ) {
                if ( JobRepository::reset_for_retry( $child['job_id'] ) ) {
                    \TechrappySEO\Jobs\QueueScheduler::schedule_single( $child['job_id'] );
                    $retried++;
                }
            }

            // Remettre le job parent en running pour reprendre le suivi.
            if ( $retried > 0 ) {
                $steps                 = is_array( $job['steps_data'] ) ? $job['steps_data'] : [];
                $steps['_bulk_failed'] = 0;
                global $wpdb;
                $wpdb->update(
                    $wpdb->prefix . 'techrappy_seo_jobs',
                    [
                        'status'     => 'running',
                        'steps_data' => wp_json_encode( $steps ),
                    ],
                    [ 'job_id' => $job_id ],
                    [ '%s', '%s' ],
                    [ '%s' ]
                );
                \TechrappySEO\Jobs\QueueScheduler::schedule_progress_check( $job_id );
            }

            wp_send_json_success( [
                'retried' => $retried,
                'message' => sprintf(
                    /* translators: %d = nombre de jobs relancés */
                    _n( '%d job relancé.', '%d jobs relancés.', $retried, 'techrappy-seo' ),
                    $retried
                ),
            ] );
        }

        // ── Cas single (ou enfant bulk) ──────────────────────────────────────
        if ( ! JobRepository::reset_for_retry( $job_id ) ) {
            wp_send_json_error( [ 'message' => __( 'Échec de la remise en file.', 'techrappy-seo' ) ], 500 );
        }

        \TechrappySEO\Jobs\QueueScheduler::schedule_single( $job_id );

        wp_send_json_success( [ 'message' => __( 'Job remis en file d\'attente.', 'techrappy-seo' ) ] );
    }

    /**
     * Retourne un échantillon d'erreurs des jobs enfants d'un bulk parent.
     *
     * @return void
     */
    public function handle_get_bulk_errors(): void {
        check_ajax_referer( 'techrappy_seo_bulk', 'nonce' );

        if ( ! current_user_can( TECHRAPPY_SEO_CAPABILITY ) ) {
            wp_send_json_error( [ 'message' => __( 'Accès non autorisé.', 'techrappy-seo' ) ], 403 );
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $parent_id = sanitize_text_field( $_POST['job_id'] ?? '' );

        if ( ! $parent_id ) {
            wp_send_json_error( [ 'message' => __( 'job_id manquant.', 'techrappy-seo' ) ], 400 );
        }

        $children = JobRepository::list( [
            'parent_job_id' => $parent_id,
            'status'        => 'failed',
            'limit'         => 10,
        ] );

        $errors = [];
        foreach ( $children as $child ) {
            $logs = is_array( $child['logs'] ) ? $child['logs'] : [];
            $last_error = '';
            foreach ( array_reverse( $logs ) as $log ) {
                if ( ( $log['level'] ?? '' ) === 'error' ) {
                    $last_error = $log['step'] . ' → ' . $log['msg'];
                    break;
                }
            }
            $errors[] = [
                'keyword'    => $child['keyword'] ?? '',
                'city'       => $child['city']    ?? '',
                'last_error' => $last_error ?: __( 'Aucun log d\'erreur enregistré.', 'techrappy-seo' ),
            ];
        }

        wp_send_json_success( [ 'errors' => $errors ] );
    }

    /**
     * Retourne le statut et la progression d'un job (single ou bulk).
     *
     * @return void
     */
    public function handle_get_job_status(): void {
        check_ajax_referer( 'techrappy_seo_bulk', 'nonce' );

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

        $response = [
            'job_id'  => $job['job_id'],
            'status'  => $job['status'],
            'mode'    => $job['mode'],
            'result'  => $job['result_data'] ?? [],
        ];

        // Pour les jobs bulk : progression.
        if ( 'bulk' === $job['mode'] && empty( $job['parent_job_id'] ) ) {
            $steps = is_array( $job['steps_data'] ) ? $job['steps_data'] : [];
            $response['progress'] = [
                'total'    => $steps['_bulk_total']    ?? 0,
                'done'     => $steps['_bulk_done']     ?? 0,
                'failed'   => $steps['_bulk_failed']   ?? 0,
                'percent'  => $steps['_bulk_progress'] ?? 0,
            ];
        }

        wp_send_json_success( $response );
    }
}
