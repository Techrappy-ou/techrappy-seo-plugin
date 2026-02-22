<?php
/**
 * Exécuteur d'un job single — appelle le pipeline complet.
 *
 * @package TechrappySEO\Jobs
 */

declare( strict_types=1 );

namespace TechrappySEO\Jobs;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class JobRunner
 */
class JobRunner {

    /**
     * Exécute le pipeline complet pour un job donné.
     *
     * @param string $job_id UUID du job.
     *
     * @return void
     */
    public function run( string $job_id ): void {
        $job = JobRepository::find( $job_id );

        if ( ! $job ) {
            return;
        }

        // Marquer le job comme en cours d'exécution.
        JobRepository::update_status( $job_id, 'running' );

        $logger = new \TechrappySEO\Utils\Logger( $job_id );
        $logger->info( 'runner', 'Démarrage du job.' );

        // TODO : instancier PipelineRunner et exécuter.
        // $pipeline = new \TechrappySEO\AI\Pipeline\PipelineRunner( $job, $logger );
        // $pipeline->run();
    }
}
