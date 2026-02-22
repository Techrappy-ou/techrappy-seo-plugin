<?php
/**
 * Client pour l'API BAN (Base Adresse Nationale).
 *
 * @package TechrappySEO\Geo
 */

declare( strict_types=1 );

namespace TechrappySEO\Geo;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class BanClient
 *
 * Responsabilité : géocoder des adresses et récupérer des informations
 * communales via l'API BAN (api-adresse.data.gouv.fr — gratuite, sans clé).
 */
class BanClient {

    /**
     * Endpoint de l'API BAN.
     */
    const BAN_API_BASE = 'https://api-adresse.data.gouv.fr';

    /**
     * Timeout HTTP en secondes.
     */
    const HTTP_TIMEOUT = 10;

    /**
     * Recherche les informations d'une ville par nom.
     *
     * @param string $city_name Nom de la ville.
     *
     * @return array{
     *   city: string,
     *   cp: string,
     *   lat: float,
     *   lon: float,
     *   score: float
     * }|null null si non trouvée.
     */
    public function search_city( string $city_name ): ?array {
        $cache     = new GeoCache();
        $cache_key = 'ban_' . $city_name;

        $cached = $cache->get( $cache_key );
        if ( false !== $cached && is_array( $cached ) ) {
            return $cached;
        }

        $url = add_query_arg( [
            'q'      => rawurlencode( $city_name ),
            'type'   => 'municipality',
            'limit'  => 1,
        ], self::BAN_API_BASE . '/search/' );

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

        if ( empty( $body['features'][0] ) ) {
            return null;
        }

        $feature  = $body['features'][0];
        $props    = $feature['properties'] ?? [];
        $geometry = $feature['geometry']['coordinates'] ?? [ 0, 0 ];

        $result = [
            'city'  => $props['city']       ?? $city_name,
            'cp'    => $props['postcode']   ?? '',
            'lat'   => (float) $geometry[1],
            'lon'   => (float) $geometry[0],
            'score' => (float) ( $props['score'] ?? 0 ),
        ];

        $cache->set( $cache_key, $result );

        return $result;
    }
}
