<?php
/**
 * Étape du pipeline : StepAntiDuplicate.
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
 * Class StepAntiDuplicate
 */
class StepAntiDuplicate implements StepInterface {

    /**
     * Exécute l'étape du pipeline.
     *
     * @param array<string, mixed>       $job    Données du job.
     * @param \TechrappySEO\Utils\Logger $logger Logger du job.
     *
     * @return array<string, mixed>
     */
    public function run( array $job, \TechrappySEO\Utils\Logger $logger ): array {
        // TODO : implémenter l'étape StepAntiDuplicate.
        $logger->info( 'StepAntiDuplicate', 'Étape à implémenter.' );
        return [];
    }

    /**
     * Retourne le nom de l'étape.
     *
     * @return string
     */
    public function get_name(): string {
        return 'StepAntiDuplicate';
    }
}
