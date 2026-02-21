<?php
/**
 * Résolution du mapping token→valeur pour l'injection dans Divi.
 *
 * @package TechrappySEO\Templates
 */

declare( strict_types=1 );

namespace TechrappySEO\Templates;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class TokenMappingResolver
 *
 * Responsabilité : résoudre les valeurs de chaque token
 * à partir des données du pipeline et du mapping configuré.
 */
class TokenMappingResolver {

    /**
     * Résout les valeurs de tous les tokens d'un template.
     *
     * @param array<string, string> $mapping    Mapping token → source.
     * @param array<string, mixed>  $steps_data Données des étapes du pipeline.
     *
     * @return array<string, string> Tableau token → valeur résolue.
     */
    public function resolve( array $mapping, array $steps_data ): array {
        // TODO : implémenter la résolution des tokens.
        return [];
    }
}
