<?php
/**
 * Interface pour les étapes du pipeline de génération.
 *
 * @package TechrappySEO\AI\Pipeline
 */

declare( strict_types=1 );

namespace TechrappySEO\AI\Pipeline;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Interface StepInterface
 *
 * Contrat que chaque étape du pipeline doit respecter.
 */
interface StepInterface {

    /**
     * Exécute l'étape du pipeline.
     *
     * @param array<string, mixed>      $job    Données du job.
     * @param \TechrappySEO\Utils\Logger $logger Logger du job.
     *
     * @return array<string, mixed> Données produites par l'étape.
     */
    public function run( array $job, \TechrappySEO\Utils\Logger $logger ): array;

    /**
     * Retourne le nom de l'étape.
     *
     * @return string
     */
    public function get_name(): string;
}
