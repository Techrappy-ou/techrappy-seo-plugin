<?php
/**
 * Scanner de tokens dans les templates Divi.
 *
 * @package TechrappySEO\Divi
 */

declare( strict_types=1 );

namespace TechrappySEO\Divi;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class TokenScanner
 *
 * Responsabilité : détecter les tokens {{TOKEN}} dans le contenu d'un post Divi.
 */
class TokenScanner {

    /**
     * Scanne un contenu post pour trouver tous les tokens.
     *
     * @param string $content Contenu du post (post_content Divi).
     *
     * @return array<string> Liste des tokens trouvés.
     */
    public function scan( string $content ): array {
        // TODO : implémenter le scan des tokens dans le contenu Divi.
        return [];
    }
}
