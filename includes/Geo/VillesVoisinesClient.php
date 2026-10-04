<?php
/**
 * Client pour la récupération des communes voisines via villes-voisines.fr.
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
 * Responsabilité : récupérer les communes voisines d'un code postal donné
 * via l'API villes-voisines.fr (gratuite, sans clé).
 *
 * Endpoint : https://www.villes-voisines.fr/getcp.php?cp=CP&rayon=RAYON
 * Réponse  : JSON array [ { code_postal, nom_commune, distance }, … ]
 */
class VillesVoisinesClient {

    /**
     * Endpoint de l'API Villes Voisines.
     */
    const API_BASE = 'https://www.villes-voisines.fr/getcp.php';

    /**
     * Timeout HTTP en secondes.
     */
    const HTTP_TIMEOUT = 10;

    /**
     * Récupère les communes voisines d'un code postal.
     *
     * @param string $cp     Code postal de référence (ex : "31000").
     * @param int    $radius Rayon en km (max 50).
     * @param int    $limit  Nombre max de communes retournées.
     *
     * @return array<int, array{city: string, cp: string, distance: float}>
     */
    public function get_nearby_cities( string $cp, int $radius = 30, int $limit = 50 ): array {
        $cache     = new GeoCache();
        $cache_key = "vv_{$cp}_{$radius}_{$limit}";

        $cached = $cache->get( $cache_key );
        if ( false !== $cached && is_array( $cached ) ) {
            return $cached;
        }

        // Rayon limité à 50 km (contrainte API).
        $rayon = min( $radius, 50 );

        $url = add_query_arg(
            [
                'cp'    => rawurlencode( $cp ),
                'rayon' => $rayon,
            ],
            self::API_BASE
        );

        $response = wp_remote_get( $url, [
            'timeout' => self::HTTP_TIMEOUT,
            'headers' => [ 'Accept' => 'application/json' ],
        ] );

        if ( is_wp_error( $response ) ) {
            return [];
        }

        $code = wp_remote_retrieve_response_code( $response );
        if ( 200 !== (int) $code ) {
            return [];
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( ! is_array( $body ) ) {
            return [];
        }

        $cities = [];
        foreach ( $body as $commune ) {
            $nom_commune = trim( $commune['nom_commune'] ?? '' );
            $code_postal = trim( $commune['code_postal'] ?? '' );
            $distance    = (float) ( $commune['distance'] ?? 0.0 );

            if ( $nom_commune && $code_postal ) {
                $cities[] = [
                    'city'     => $nom_commune,
                    'cp'       => $code_postal,
                    'distance' => $distance,
                ];
            }

            if ( count( $cities ) >= $limit ) {
                break;
            }
        }

        $cache->set( $cache_key, $cities );

        return $cities;
    }
}
