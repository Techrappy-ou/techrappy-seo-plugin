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
 */
class TokenReplacer {

    /**
     * Remplace les tokens dans le contenu Divi.
     *
     * @param string               $content    Contenu du post Divi.
     * @param array<string, string> $token_map  Tableau token → valeur.
     *
     * @return string Contenu avec tokens remplacés.
     */
    public function replace( string $content, array $token_map ): string {
        // TODO : implémenter le remplacement des tokens.
        return $content;
    }
}
