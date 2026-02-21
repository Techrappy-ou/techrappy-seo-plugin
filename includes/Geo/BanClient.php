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
 * Responsabilité : géocoder des adresses et récupérer des codes postaux
 * via l'API BAN (api.gouv.fr/bases-locales/ban).
 */
class BanClient {

    /**
     * Recherche les informations d'une ville par nom.
     *
     * @param string $city_name Nom de la ville.
     *
     * @return array<string, mixed>|null
     */
    public function search_city( string $city_name ): ?array {
        // TODO : implémenter l'appel à l'API BAN.
        return null;
    }
}
