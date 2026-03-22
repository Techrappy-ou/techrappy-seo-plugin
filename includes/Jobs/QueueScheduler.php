<?php
/**
 * Intégration Action Scheduler pour la queue de génération en masse.
 *
 * @package TechrappySEO\Jobs
 */

declare( strict_types=1 );

namespace TechrappySEO\Jobs;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class QueueScheduler
 */
class QueueScheduler {

    /**
     * Planifie l'exécution d'un job single via Action Scheduler.
     *
     * @param string $job_id UUID du job à exécuter.
     *
     * @return void
     */
    public static function schedule_single( string $job_id ): void {
        if ( ! function_exists( 'as_schedule_single_action' ) ) {
            // Fallback WP-Cron si Action Scheduler non disponible.
            wp_schedule_single_event( time(), 'techrappy_seo_process_single_job', [ $job_id ] );
            // Forcer l'exécution immédiate de WP-Cron sans attendre une visite.
            if ( ! defined( 'DOING_CRON' ) ) {
                spawn_cron();
            }
            return;
        }
        as_schedule_single_action( time(), 'techrappy_seo_process_single_job', [ 'job_id' => $job_id ], 'techrappy-seo' );
    }

    /**
     * Planifie la vérification de progression d'un job bulk.
     *
     * @param string $parent_job_id UUID du job parent.
     *
     * @return void
     */
    public static function schedule_progress_check( string $parent_job_id ): void {
        if ( ! function_exists( 'as_schedule_single_action' ) ) {
            wp_schedule_single_event( time() + 30, 'techrappy_seo_bulk_progress_check', [ $parent_job_id ] );
            return;
        }
        as_schedule_single_action( time() + 30, 'techrappy_seo_bulk_progress_check', [ 'parent_job_id' => $parent_job_id ], 'techrappy-seo' );
    }

    /**
     * Exécute un job single (appelé par Action Scheduler).
     *
     * @param string $job_id UUID du job.
     *
     * @return void
     */
    public function process_single_job( string $job_id ): void {
        // TODO : instancier JobRunner et exécuter le pipeline.
        $runner = new JobRunner();
        $runner->run( $job_id );
    }

    /**
     * Vérifie la progression d'un job bulk (appelé par Action Scheduler).
     *
     * @param string $parent_job_id UUID du job parent.
     *
     * @return void
     */
    public function check_bulk_progress( string $parent_job_id ): void {
        // TODO : implémenter via BulkJobManager.
        $manager = new BulkJobManager();
        $manager->check_progress( $parent_job_id );
    }

    /**
     * Annule l'action planifiée pour un job spécifique.
     * Utilisé lors d'une pause ou d'une suppression.
     *
     * @param string $job_id UUID du job.
     *
     * @return void
     */
    public static function cancel_single( string $job_id ): void {
        if ( function_exists( 'as_unschedule_action' ) ) {
            as_unschedule_action( 'techrappy_seo_process_single_job', [ 'job_id' => $job_id ], 'techrappy-seo' );
        } else {
            wp_clear_scheduled_hook( 'techrappy_seo_process_single_job', [ $job_id ] );
        }
    }

    /**
     * Supprime toutes les actions planifiées du plugin.
     * Appelé à la désactivation.
     *
     * @return void
     */
    public static function clear_all(): void {
        if ( ! function_exists( 'as_unschedule_all_actions' ) ) {
            wp_clear_scheduled_hook( 'techrappy_seo_process_single_job' );
            wp_clear_scheduled_hook( 'techrappy_seo_bulk_progress_check' );
            return;
        }
        as_unschedule_all_actions( 'techrappy_seo_process_single_job', [], 'techrappy-seo' );
        as_unschedule_all_actions( 'techrappy_seo_bulk_progress_check', [], 'techrappy-seo' );
    }
}
