<?php
/**
 * Helpers centralisés de sanitization et d'échappement.
 *
 * @package TechrappySEO\Utils
 */

declare( strict_types=1 );

namespace TechrappySEO\Utils;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Sanitizer
 */
class Sanitizer {

    /**
     * Sanitize un texte simple (pas de HTML).
     *
     * @param mixed $value Valeur brute.
     *
     * @return string
     */
    public static function text( mixed $value ): string {
        return sanitize_text_field( (string) $value );
    }

    /**
     * Sanitize un bloc HTML (contenu post WordPress).
     *
     * @param mixed $value Valeur brute.
     *
     * @return string
     */
    public static function html( mixed $value ): string {
        return wp_kses_post( (string) $value );
    }

    /**
     * Sanitize un slug WordPress.
     *
     * @param mixed $value Valeur brute.
     *
     * @return string
     */
    public static function slug( mixed $value ): string {
        return sanitize_title( (string) $value );
    }

    /**
     * Sanitize et valide un entier positif.
     *
     * @param mixed $value Valeur brute.
     *
     * @return int
     */
    public static function positive_int( mixed $value ): int {
        return absint( $value );
    }

    /**
     * Sanitize un JSON string — retourne le JSON décodé ou null.
     *
     * @param mixed $value Valeur brute.
     *
     * @return array<mixed>|null Tableau décodé ou null si JSON invalide.
     */
    public static function json( mixed $value ): ?array {
        $decoded = json_decode( (string) $value, true );
        return ( JSON_ERROR_NONE === json_last_error() && is_array( $decoded ) ) ? $decoded : null;
    }

    /**
     * Échappe une valeur pour affichage HTML.
     *
     * @param mixed $value Valeur brute.
     *
     * @return string
     */
    public static function esc_output( mixed $value ): string {
        return esc_html( (string) $value );
    }

    /**
     * Échappe une valeur pour un attribut HTML.
     *
     * @param mixed $value Valeur brute.
     *
     * @return string
     */
    public static function esc_attr_output( mixed $value ): string {
        return esc_attr( (string) $value );
    }
}
