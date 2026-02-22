<?php
/**
 * Client pour la récupération des communes voisines (API Geo gouv.fr).
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
 * Responsabilité : récupérer les communes voisines d'une ville donnée
 * via l'API officielle geo.api.gouv.fr (gratuite, sans clé).
 *
 * Workflow :
 *   1. Trouver les coordonnées GPS de la ville de référence
 *   2. Lister les communes dans un rayon donné
 */
class VillesVoisinesClient {

    /**
     * Endpoint de l'API Géo Gouv.
     */
    const GEO_API_BASE = 'https://geo.api.gouv.fr';

    /**
     * Timeout HTTP en secondes.
     */
    const HTTP_TIMEOUT = 10;

    /**
     * Récupère les communes voisines d'une ville.
     *
     * @param string $city   Nom de la ville de référence.
     * @param int    $radius Rayon en km (max 50 recommandé).
     * @param int    $limit  Nombre max de communes retournées.
     *
     * @return array<int, array{city: string, cp: string, distance: float}>
     */
    public function get_nearby_cities( string $city, int $radius = 30, int $limit = 50 ): array {
        $cache = new GeoCache();
        $cache_key = "nearby_{$city}_{$radius}_{$limit}";

        $cached = $cache->get( $cache_key );
        if ( false !== $cached && is_array( $cached ) ) {
            return $cached;
        }

        // ── 1. Obtenir les coordonnées de la ville de référence ───────────────
        $coordinates = $this->get_city_coordinates( $city );
        if ( null === $coordinates ) {
            return [];
        }

        [ $lat, $lon ] = $coordinates;

        // ── 2. Lister les communes dans le rayon ──────────────────────────────
        $radius_meters = $radius * 1000;
        $url = add_query_arg( [
            'lat'    => $lat,
            'lon'    => $lon,
            'distance' => $radius_meters,
            'fields' => 'nom,codesPostaux',
            'limit'  => $limit,
            'boost'  => 'population',
        ], self::GEO_API_BASE . '/communes' );

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
            $nom = $commune['nom'] ?? '';
            $cp  = $commune['codesPostaux'][0] ?? '';
            if ( $nom ) {
                $cities[] = [
                    'city'     => $nom,
                    'cp'       => $cp,
                    'distance' => 0.0,
                ];
            }
        }

        $cache->set( $cache_key, $cities );

        return $cities;
    }

    /**
     * Récupère les coordonnées GPS d'une ville.
     *
     * @param string $city Nom de la ville.
     *
     * @return array{float, float}|null [lat, lon] ou null si non trouvée.
     */
    private function get_city_coordinates( string $city ): ?array {
        $cache = new GeoCache();
        $cache_key = "coords_{$city}";

        $cached = $cache->get( $cache_key );
        if ( false !== $cached && is_array( $cached ) ) {
            return $cached;
        }

        $url = add_query_arg( [
            'nom'    => rawurlencode( $city ),
            'fields' => 'nom,centre',
            'limit'  => 1,
            'boost'  => 'population',
        ], self::GEO_API_BASE . '/communes' );

        $response = wp_remote_get( $url, [
            'timeout' => self::HTTP_TIMEOUT,
            'headers' => [ 'Accept' => 'application/json' ],
        ] );

        if ( is_wp_error( $response ) ) {
            return null;
        }

        $code = wp_remote_retrieve_response_code( $response );
        if ( 200 !== (int) $code ) {
            return null;
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( empty( $body[0]['centre']['coordinates'] ) ) {
            return null;
        }

        // GeoJSON : coordinates = [lon, lat]
        $lon    = (float) $body[0]['centre']['coordinates'][0];
        $lat    = (float) $body[0]['centre']['coordinates'][1];
        $coords = [ $lat, $lon ];

        $cache->set( $cache_key, $coords );

        return $coords;
    }
}
