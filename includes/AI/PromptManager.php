<?php
/**
 * Gestionnaire de prompts pour le pipeline IA.
 *
 * @package TechrappySEO\AI
 */

declare( strict_types=1 );

namespace TechrappySEO\AI;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class PromptManager
 *
 * Responsabilité : charger les prompts depuis la base de données
 * et les fournir au pipeline.
 */
class PromptManager {

    /**
     * Récupère un prompt par sa clé.
     *
     * @param string $key Clé du prompt.
     *
     * @return array<string, mixed>|null
     */
    public function get( string $key ): ?array {
        $repository = new \TechrappySEO\Prompts\PromptRepository();
        return $repository->find( $key );
    }
}
