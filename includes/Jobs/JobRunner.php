<?php
/**
 * Exécuteur d'un job single — appelle le pipeline complet.
 *
 * @package TechrappySEO\Jobs
 */

declare( strict_types=1 );

namespace TechrappySEO\Jobs;

use TechrappySEO\AI\Pipeline\PipelineRunner;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class JobRunner
 *
 * Charge un job depuis la base, instancie le PipelineRunner et exécute le pipeline.
 */
class JobRunner {

    /**
     * Exécute le pipeline complet pour un job donné.
     *
     * @param string $job_id UUID du job.
     *
     * @return array{post_id: int, permalink: string, slug: string}|null Résultat ou null.
     */
    public function run( string $job_id ): ?array {
        $job = JobRepository::find( $job_id );

        if ( ! $job ) {
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
            error_log( "[TechrappySEO] JobRunner: job {$job_id} introuvable." );
            return null;
        }

        // Marquer le job comme en cours.
        JobRepository::update_status( $job_id, 'running' );

        $logger = new \TechrappySEO\Utils\Logger( $job_id );
        $logger->info( 'runner', 'Démarrage du job.' );

        $pipeline = new PipelineRunner( $job, $logger );
        $result   = $pipeline->run();

        // Persister les logs finaux.
        JobRepository::update_steps( $job_id, $job['steps_data'] ?? [], $logger->get_logs() );

        if ( null === $result ) {
            JobRepository::update_status( $job_id, 'failed' );
        }

        return $result;
    }
}
