<?php
/**
 * Client pour l'API Villes-Voisines.
 *
 * @package TechrappySEO\Geo
 */

declare( strict_types=1 );

namespace TechrappySEO\Geo;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class VillesVoisinesClient
 *
 * Responsabilité : récupérer les villes voisines d'une ville donnée
 * via l'API Villes-Voisines.
 */
class VillesVoisinesClient {

    /**
     * Récupère les villes voisines d'une ville.
     *
     * @param string $city   Nom de la ville de référence.
     * @param int    $radius Rayon en km.
     * @param int    $limit  Nombre max de villes.
     *
     * @return array<int, array{city: string, cp: string, distance: float}>
     */
    public function get_nearby_cities( string $city, int $radius = 30, int $limit = 50 ): array {
        // TODO : implémenter l'appel à l'API Villes-Voisines.
        return [];
    }
}
