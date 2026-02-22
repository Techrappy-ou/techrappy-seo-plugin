<?php
/**
 * Résolution du mapping token→valeur pour l'injection dans Divi.
 *
 * @package TechrappySEO\Templates
 */

declare( strict_types=1 );

namespace TechrappySEO\Templates;

use TechrappySEO\Utils\ContentAssembler;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class TokenMappingResolver
 *
 * Responsabilité : résoudre les valeurs de chaque token
 * à partir des données du pipeline et du mapping configuré.
 *
 * Deux modes :
 *  - Auto (sans mapping) : utilise directement le token_map de ContentAssembler.
 *  - Custom (avec mapping) : supporte la notation dot-path pour accéder à des
 *    champs imbriqués (ex: "intent.data.intent_principale").
 */
class TokenMappingResolver {

    /**
     * Résout les valeurs de tous les tokens d'un template.
     *
     * @param array<string, string> $mapping    Mapping token → source (dot-path ou nom direct).
     *                                          Tableau vide = résolution auto.
     * @param array<string, mixed>  $steps_data Données des étapes du pipeline ($job['steps']).
     *
     * @return array<string, string> Tableau token → valeur résolue.
     */
    public function resolve( array $mapping, array $steps_data ): array {
        $assembler = new ContentAssembler();
        $token_map = $assembler->build_token_map( $steps_data );

        // Si pas de mapping personnalisé : retourner le token_map auto.
        if ( empty( $mapping ) ) {
            return $token_map;
        }

        $resolved = [];

        foreach ( $mapping as $token => $source ) {
            // Notation dot-path : "intent.data.intent_principale"
            if ( str_contains( $source, '.' ) ) {
                $value = $this->extract_dot_path( $steps_data, $source );
                $resolved[ $token ] = is_string( $value ) ? $value : ( $token_map[ $token ] ?? '' );
                continue;
            }

            // Clé directe dans le token_map auto.
            $resolved[ $token ] = $token_map[ $source ] ?? $token_map[ $token ] ?? '';
        }

        return $resolved;
    }

    /**
     * Extrait une valeur depuis un tableau imbriqué via notation dot-path.
     *
     * @param array<string, mixed> $data Données source.
     * @param string               $path Chemin dot (ex: "plan.data.H1").
     *
     * @return mixed Valeur ou null si non trouvée.
     */
    private function extract_dot_path( array $data, string $path ): mixed {
        $keys    = explode( '.', $path );
        $current = $data;

        foreach ( $keys as $key ) {
            if ( ! is_array( $current ) || ! isset( $current[ $key ] ) ) {
                return null;
            }
            $current = $current[ $key ];
        }

        return $current;
    }
}
