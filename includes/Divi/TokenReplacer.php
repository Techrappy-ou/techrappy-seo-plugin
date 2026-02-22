<?php
/**
 * Remplacement des tokens dans les templates Divi.
 *
 * @package TechrappySEO\Divi
 */

declare( strict_types=1 );

namespace TechrappySEO\Divi;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class TokenReplacer
 *
 * Responsabilité : remplacer les tokens {{TOKEN}} par les valeurs générées.
 * Supporte à la fois les tokens scalaires et les tokens HTML multi-lignes.
 */
class TokenReplacer {

    /**
     * Remplace les tokens dans le contenu Divi.
     *
     * @param string               $content   Contenu du post Divi.
     * @param array<string, string> $token_map Tableau token → valeur.
     *
     * @return string Contenu avec tokens remplacés.
     */
    public function replace( string $content, array $token_map ): string {
        foreach ( $token_map as $token => $value ) {
            $content = str_replace(
                '{{' . $token . '}}',
                (string) $value,
                $content
            );
        }

        // Nettoyer les tokens non résolus (laissés vides).
        $content = preg_replace( TokenScanner::TOKEN_PATTERN, '', $content ) ?? $content;

        return $content;
    }
}
