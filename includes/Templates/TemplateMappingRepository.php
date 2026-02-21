<?php
/**
 * Référentiel des mappings token→source pour les templates.
 *
 * @package TechrappySEO\Templates
 */

declare( strict_types=1 );

namespace TechrappySEO\Templates;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class TemplateMappingRepository
 *
 * Responsabilité : stocker et récupérer les mappings token→source
 * pour chaque template Divi (stockés dans wp_options).
 */
class TemplateMappingRepository {

    /**
     * Préfixe des options de mapping.
     */
    const OPTION_PREFIX = 'techrappy_seo_template_map_';

    /**
     * Récupère le mapping pour un template donné.
     *
     * @param int $post_id ID du post template.
     *
     * @return array<string, string>
     */
    public function get( int $post_id ): array {
        $stored = get_option( self::OPTION_PREFIX . $post_id, [] );
        return is_array( $stored ) ? $stored : [];
    }

    /**
     * Sauvegarde le mapping pour un template.
     *
     * @param int                   $post_id ID du post template.
     * @param array<string, string> $mapping Tableau token → source.
     *
     * @return bool
     */
    public function save( int $post_id, array $mapping ): bool {
        return update_option( self::OPTION_PREFIX . $post_id, $mapping, false );
    }
}
