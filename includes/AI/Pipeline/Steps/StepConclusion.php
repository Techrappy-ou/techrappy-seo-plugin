<?php
/**
 * Étape du pipeline : StepConclusion.
 *
 * @package TechrappySEO\AI\Pipeline\Steps
 */

declare( strict_types=1 );

namespace TechrappySEO\AI\Pipeline\Steps;

use TechrappySEO\AI\Pipeline\StepInterface;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class StepConclusion
 */
class StepConclusion implements StepInterface {

    /**
     * Exécute l'étape du pipeline.
     *
     * @param array<string, mixed>       $job    Données du job.
     * @param \TechrappySEO\Utils\Logger $logger Logger du job.
     *
     * @return array<string, mixed>
     */
    public function run( array $job, \TechrappySEO\Utils\Logger $logger ): array {
        // TODO : implémenter l'étape StepConclusion.
        $logger->info( 'StepConclusion', 'Étape à implémenter.' );
        return [];
    }

    /**
     * Retourne le nom de l'étape.
     *
     * @return string
     */
    public function get_name(): string {
        return 'StepConclusion';
    }
}
