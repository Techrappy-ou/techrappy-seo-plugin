<?php
/**
 * Création et gestion des jobs de génération en masse.
 *
 * @package TechrappySEO\Jobs
 */

declare( strict_types=1 );

namespace TechrappySEO\Jobs;

use TechrappySEO\Settings\SettingsRepository;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class BulkJobManager
 *
 * Responsabilité : créer un job parent bulk, dispatcher N jobs enfants
 * (un par ville), et suivre leur progression.
 */
class BulkJobManager {

    /**
     * Crée un job parent bulk et dispatche N jobs enfants.
     *
     * @param array<string, mixed>                      $bulk_params Paramètres communs (keyword_base, profession, type...).
     * @param array<int, array{city: string, cp: string}> $cities    Liste des villes.
     *
     * @return string|false UUID du job parent ou false si erreur.
     */
    public function create_and_dispatch( array $bulk_params, array $cities ): string|false {
        $max_cities = (int) SettingsRepository::get( 'bulk_max_cities', 50 );
        $cities     = array_slice( $cities, 0, $max_cities );

        if ( empty( $cities ) ) {
            return false;
        }

        // ── 1. Créer le job parent ────────────────────────────────────────────
        $parent_id = JobRepository::insert( [
            'mode'             => 'bulk',
            'type'             => $bulk_params['type']             ?? 'page',
            'template_post_id' => $bulk_params['template_post_id'] ?? 0,
            'keyword'          => $bulk_params['keyword_base']     ?? '',
            'publish_status'   => $bulk_params['publish_status']   ?? 'draft',
            'slug_rule'        => $bulk_params['slug_rule']        ?? 'from_keyword',
            'wp_params'        => $bulk_params['wp_params']        ?? [],
            'steps_data'       => [
                '_bulk_total'     => count( $cities ),
                '_bulk_done'      => 0,
                '_bulk_failed'    => 0,
            ],
        ] );

        if ( ! $parent_id ) {
            return false;
        }

        $keyword_base = $bulk_params['keyword_base'] ?? '';
        $wp_params    = $bulk_params['wp_params']    ?? [];

        // ── 2. Créer + planifier un job enfant par ville ──────────────────────
        foreach ( $cities as $city_data ) {
            $city    = sanitize_text_field( $city_data['city'] ?? '' );
            $cp      = sanitize_text_field( $city_data['cp']   ?? '' );
            $keyword = trim( $keyword_base . ' ' . $city );

            $child_id = JobRepository::insert( [
                'mode'             => 'bulk',
                'type'             => $bulk_params['type']             ?? 'page',
                'template_post_id' => $bulk_params['template_post_id'] ?? 0,
                'keyword'          => $keyword,
                'city'             => $city,
                'publish_status'   => $bulk_params['publish_status']   ?? 'draft',
                'slug_rule'        => $bulk_params['slug_rule']        ?? 'from_keyword',
                'parent_job_id'    => $parent_id,
                'wp_params'        => array_merge( $wp_params, [
                    'city' => $city,
                    'cp'   => $cp,
                ] ),
            ] );

            if ( $child_id ) {
                QueueScheduler::schedule_single( $child_id );
            }
        }

        // Passer le job parent en running dès que les enfants sont dispatchés.
        JobRepository::update_status( $parent_id, 'running' );

        return $parent_id;
    }

    /**
     * Crée un job parent bulk et dispatche N jobs enfants (un par mot-clé).
     * Contrairement à create_and_dispatch(), chaque keyword est utilisé tel quel
     * sans concaténation avec une ville.
     *
     * @param array<string, mixed> $bulk_params Paramètres communs (profession, type, template...).
     * @param array<int, string>   $keywords    Liste des mots-clés complets.
     *
     * @return string|false UUID du job parent ou false si erreur.
     */
    public function create_and_dispatch_keywords( array $bulk_params, array $keywords ): string|false {
        $max = (int) SettingsRepository::get( 'bulk_max_cities', 50 );
        $keywords = array_slice( array_unique( $keywords ), 0, $max );

        if ( empty( $keywords ) ) {
            return false;
        }

        // ── 1. Créer le job parent ────────────────────────────────────────────
        $parent_id = JobRepository::insert( [
            'mode'             => 'bulk',
            'type'             => $bulk_params['type']             ?? 'page',
            'template_post_id' => $bulk_params['template_post_id'] ?? 0,
            'keyword'          => sprintf( '%d mots-clés', count( $keywords ) ),
            'publish_status'   => $bulk_params['publish_status']   ?? 'draft',
            'slug_rule'        => $bulk_params['slug_rule']        ?? 'from_keyword',
            'wp_params'        => $bulk_params['wp_params']        ?? [],
            'steps_data'       => [
                '_bulk_total'     => count( $keywords ),
                '_bulk_done'      => 0,
                '_bulk_failed'    => 0,
                '_bulk_submode'   => 'keywords',
            ],
        ] );

        if ( ! $parent_id ) {
            return false;
        }

        $wp_params = $bulk_params['wp_params'] ?? [];

        // ── 2. Créer + planifier un job enfant par mot-clé ────────────────────
        foreach ( $keywords as $keyword ) {
            $keyword = sanitize_text_field( $keyword );

            if ( '' === $keyword ) {
                continue;
            }

            $child_id = JobRepository::insert( [
                'mode'             => 'bulk',
                'type'             => $bulk_params['type']             ?? 'page',
                'template_post_id' => $bulk_params['template_post_id'] ?? 0,
                'keyword'          => $keyword,
                'city'             => '',
                'publish_status'   => $bulk_params['publish_status']   ?? 'draft',
                'slug_rule'        => $bulk_params['slug_rule']        ?? 'from_keyword',
                'parent_job_id'    => $parent_id,
                'wp_params'        => $wp_params,
            ] );

            if ( $child_id ) {
                QueueScheduler::schedule_single( $child_id );
            }
        }

        JobRepository::update_status( $parent_id, 'running' );

        return $parent_id;
    }

    /**
     * Vérifie la progression d'un job bulk et met à jour le job parent.
     *
     * @param string $parent_job_id UUID du job parent.
     *
     * @return void
     */
    public function check_progress( string $parent_job_id ): void {
        $children = JobRepository::list( [
            'parent_job_id' => $parent_job_id,
            'limit'         => 500,
        ] );

        if ( empty( $children ) ) {
            return;
        }

        $total  = count( $children );
        $done   = 0;
        $failed = 0;

        foreach ( $children as $child ) {
            if ( 'done' === $child['status'] ) {
                $done++;
            } elseif ( 'failed' === $child['status'] ) {
                $failed++;
                $done++;  // On compte les échecs comme terminés pour le % d'avancement.
            }
        }

        $parent = JobRepository::find( $parent_job_id );
        if ( ! $parent ) {
            return;
        }

        $steps_data                   = is_array( $parent['steps_data'] ) ? $parent['steps_data'] : [];
        $steps_data['_bulk_total']    = $total;
        $steps_data['_bulk_done']     = $done;
        $steps_data['_bulk_failed']   = $failed;
        $steps_data['_bulk_progress'] = $total > 0 ? round( $done / $total * 100 ) : 0;

        JobRepository::update_steps( $parent_job_id, $steps_data, [] );

        // Marquer le parent terminé si tous les enfants ont fini.
        if ( $done >= $total ) {
            $final_status = $failed > 0 ? 'done_with_errors' : 'done';
            JobRepository::update_status( $parent_job_id, $final_status );
            return;
        }

        // Re-planifier une vérification dans 30 secondes si pas terminé.
        QueueScheduler::schedule_progress_check( $parent_job_id );
    }
}
