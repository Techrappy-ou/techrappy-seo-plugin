<?php
/**
 * Génération de slugs SEO-friendly.
 *
 * @package TechrappySEO\SEO
 */

declare( strict_types=1 );

namespace TechrappySEO\SEO;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class SlugGenerator
 *
 * Responsabilité : générer un slug WordPress unique et SEO-friendly
 * à partir du mot-clé ou du H1.
 */
class SlugGenerator {

    /**
     * Génère un slug à partir d'une source (keyword ou H1).
     *
     * @param string $source   Texte source (mot-clé ou H1).
     * @param string $city     Ville (optionnel, ajoutée au slug si fournie).
     *
     * @return string Slug généré.
     */
    public function generate( string $source, string $city = '' ): string {
        $slug = sanitize_title( $source );

        if ( ! empty( $city ) ) {
            $slug .= '-' . sanitize_title( $city );
        }

        return $slug;
    }
}
