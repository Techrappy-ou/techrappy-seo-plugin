<?php
/**
 * Cache des données géographiques via les transients WordPress.
 *
 * @package TechrappySEO\Geo
 */

declare( strict_types=1 );

namespace TechrappySEO\Geo;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class GeoCache
 *
 * Responsabilité : mettre en cache les réponses des API géographiques
 * pour éviter les appels répétés.
 */
class GeoCache {

    /**
     * Préfixe des clés de transient.
     */
    const TRANSIENT_PREFIX = 'techrappy_seo_geo_';

    /**
     * Durée de vie du cache en secondes (24h par défaut).
     */
    const CACHE_TTL = 86400;

    /**
     * Récupère une valeur depuis le cache.
     *
     * @param string $key Clé de cache.
     *
     * @return mixed|false Valeur ou false si non trouvée.
     */
    public function get( string $key ): mixed {
        return get_transient( self::TRANSIENT_PREFIX . md5( $key ) );
    }

    /**
     * Stocke une valeur dans le cache.
     *
     * @param string $key   Clé de cache.
     * @param mixed  $value Valeur à stocker.
     * @param int    $ttl   Durée de vie en secondes.
     *
     * @return void
     */
    public function set( string $key, mixed $value, int $ttl = self::CACHE_TTL ): void {
        set_transient( self::TRANSIENT_PREFIX . md5( $key ), $value, $ttl );
    }
}
