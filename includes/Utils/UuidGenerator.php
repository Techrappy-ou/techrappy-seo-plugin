<?php
/**
 * Générateur UUID v4 pur PHP sans dépendance externe.
 *
 * @package TechrappySEO\Utils
 */

declare( strict_types=1 );

namespace TechrappySEO\Utils;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class UuidGenerator
 */
class UuidGenerator {

    /**
     * Génère un UUID v4 aléatoire.
     *
     * @return string UUID au format xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx
     */
    public static function generate(): string {
        $data = random_bytes( 16 );

        // Positionner la version (4) et le variant (RFC 4122).
        $data[6] = chr( ( ord( $data[6] ) & 0x0F ) | 0x40 );
        $data[8] = chr( ( ord( $data[8] ) & 0x3F ) | 0x80 );

        return vsprintf( '%s%s-%s-%s-%s-%s%s%s', str_split( bin2hex( $data ), 4 ) );
    }
}
