<?php
/**
 * Orchestrateur du pipeline de génération de contenu.
 *
 * @package TechrappySEO\AI\Pipeline
 */

declare( strict_types=1 );

namespace TechrappySEO\AI\Pipeline;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class PipelineRunner
 *
 * Responsabilité : orchestrer l'exécution séquentielle des étapes du pipeline.
 */
class PipelineRunner {

    /**
     * Données du job en cours.
     *
     * @var array<string, mixed>
     */
    private array $job;

    /**
     * Logger associé au job.
     *
     * @var \TechrappySEO\Utils\Logger
     */
    private \TechrappySEO\Utils\Logger $logger;

    /**
     * Constructeur.
     *
     * @param array<string, mixed>      $job    Données du job.
     * @param \TechrappySEO\Utils\Logger $logger Logger du job.
     */
    public function __construct( array $job, \TechrappySEO\Utils\Logger $logger ) {
        $this->job    = $job;
        $this->logger = $logger;
    }

    /**
     * Exécute toutes les étapes du pipeline.
     *
     * @return void
     */
    public function run(): void {
        // TODO : implémenter l'exécution séquentielle des étapes.
        $this->logger->info( 'pipeline', 'Pipeline démarré.' );
    }
}
