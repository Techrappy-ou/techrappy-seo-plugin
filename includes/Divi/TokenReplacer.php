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
            $val = (string) $value;

            // Remplacement standard {{TOKEN}}.
            $content = str_replace( '{{' . $token . '}}', $val, $content );

            // Remplacement encodé HTML : Divi encode parfois {{ → &#123;&#123;
            // ce qui empêche le remplacement standard et laisse le token en brut.
            $content = str_replace(
                '&#123;&#123;' . $token . '&#125;&#125;',
                esc_attr( $val ),
                $content
            );
        }

        // Nettoyer les tokens non résolus (laissés vides).
        $content = preg_replace( TokenScanner::TOKEN_PATTERN, '', $content ) ?? $content;

        // Nettoyer aussi les tokens encodés HTML non résolus.
        $content = preg_replace( '/&#123;&#123;[A-Za-z0-9_]+&#125;&#125;/', '', $content ) ?? $content;

        return $content;
    }
}
