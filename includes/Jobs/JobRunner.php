<?php
/**
 * Exécuteur d'un job single — appelle le pipeline complet.
 *
 * @package TechrappySEO\Jobs
 */

declare( strict_types=1 );

namespace TechrappySEO\Jobs;

use TechrappySEO\AI\Pipeline\PipelineRunner;
use TechrappySEO\Utils\ContentAssembler;
use TechrappySEO\Utils\Logger;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class JobRunner
 *
 * Orchestre l'exécution complète d'un job :
 *   1. Pipeline IA (10 étapes)
 *   2. Assemblage du contenu
 *   3. Création du post WordPress
 *   4. Sauvegarde du résultat en base
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
        // ── 1. Charger le job ─────────────────────────────────────────────────
        $db_job = JobRepository::find( $job_id );

        if ( ! $db_job ) {
            return;
        }

        // ── 2. Marquer en cours ───────────────────────────────────────────────
        JobRepository::update_status( $job_id, 'running' );

        // ── 3. Préparer le logger ─────────────────────────────────────────────
        $logger = new Logger( $job_id );
        if ( ! empty( $db_job['logs'] ) && is_array( $db_job['logs'] ) ) {
            $logger->load( $db_job['logs'] );
        }
        $logger->info( 'runner', 'Démarrage du job.' );

        // ── 4. Construire le tableau job pour le pipeline ─────────────────────
        $wp_params = is_array( $db_job['wp_params'] ) ? $db_job['wp_params'] : [];

        $pipeline_job = [
            'job_id'           => $db_job['job_id'],
            'mode'             => $db_job['mode']             ?? 'single',
            'type'             => $db_job['type']             ?? 'page',
            'template_post_id' => absint( $db_job['template_post_id'] ?? 0 ),
            'keyword'          => $db_job['keyword']          ?? '',
            'keyword_base'     => $db_job['keyword']          ?? '',
            'city'             => $db_job['city']             ?? '',
            'profession'       => $wp_params['profession']    ?? '',
            'publish_status'   => $db_job['publish_status']   ?? 'draft',
            'wp_params'        => $wp_params,
            'slug_rule'        => $db_job['slug_rule']        ?? 'from_keyword',
            'steps'            => is_array( $db_job['steps_data'] ) ? $db_job['steps_data'] : [],
            'result_data'      => is_array( $db_job['result_data'] ) ? $db_job['result_data'] : [],
            'user_intent'      => $wp_params['user_intent']   ?? '',
        ];

        // ── 5. Exécuter le pipeline ───────────────────────────────────────────
        try {
            $pipeline = new PipelineRunner( $pipeline_job, $logger, $job_id );
            $pipeline_job = $pipeline->run();

            // Sauvegarder les steps en base après le pipeline.
            JobRepository::update_steps(
                $job_id,
                $pipeline_job['steps'] ?? [],
                $logger->get_logs()
            );

        } catch ( \Throwable $e ) {
            $logger->error( 'runner', 'Pipeline échoué : ' . $e->getMessage() );
            JobRepository::update_status( $job_id, 'failed' );
            JobRepository::update_steps( $job_id, $pipeline_job['steps'] ?? [], $logger->get_logs() );
            return;
        }

        // ── 6. Assembler le contenu HTML ──────────────────────────────────────
        $assembler = new ContentAssembler();
        $assembled = $assembler->assemble( $pipeline_job['steps'] );

        if ( empty( $assembled['raw_html'] ) ) {
            $logger->error( 'runner', 'Contenu assemblé vide. Annulation.' );
            JobRepository::update_status( $job_id, 'failed' );
            JobRepository::update_steps( $job_id, $pipeline_job['steps'], $logger->get_logs() );
            return;
        }

        // ── 7. Créer le post WordPress ────────────────────────────────────────
        $writer = new PostWriter();
        $result = $writer->write( $pipeline_job, $assembled );

        if ( false === $result ) {
            $logger->error( 'runner', 'Échec de la création du post WordPress.' );
            JobRepository::update_status( $job_id, 'failed' );
            JobRepository::update_steps( $job_id, $pipeline_job['steps'], $logger->get_logs() );
            return;
        }

        // ── 8. Sauvegarder le résultat ────────────────────────────────────────
        $logger->info( 'runner', sprintf(
            'Post créé — ID %d : %s',
            $result['post_id'],
            $result['permalink']
        ) );

        JobRepository::update_result( $job_id, $result );
        JobRepository::update_steps( $job_id, $pipeline_job['steps'], $logger->get_logs() );
    }
}
