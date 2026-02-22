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
 * Les tokens utilisent la convention {{NOM_TOKEN}} en majuscules ou minuscules.
 */
class TokenScanner {

    /**
     * Pattern regex pour détecter les tokens {{TOKEN}}.
     */
    const TOKEN_PATTERN = '/\{\{([A-Za-z0-9_]+)\}\}/';

    /**
     * Tokens obligatoires pour qu'un template soit valide.
     *
     * @var array<string>
     */
    const REQUIRED_TOKENS = [
        'H1',
        'intro_longue_html',
        'BLOC_REPEAT',
        'BLOC_H2',
        'BLOC_HTML',
        'conclusion_douce_html',
        'cta_html',
        'faq_visible_html',
    ];

    /**
     * Scanne un contenu post pour trouver tous les tokens {{TOKEN}}.
     *
     * @param string $content Contenu du post (post_content Divi).
     *
     * @return array<string> Liste des noms de tokens trouvés (sans doublons).
     */
    public function scan( string $content ): array {
        preg_match_all( self::TOKEN_PATTERN, $content, $matches );
        return array_values( array_unique( $matches[1] ?? [] ) );
    }

    /**
     * Retourne les tokens requis absents du contenu.
     *
     * @param array<string> $tokens_found Tokens détectés dans le template.
     *
     * @return array<string> Tokens manquants.
     */
    public function get_missing_required( array $tokens_found ): array {
        return array_values( array_diff( self::REQUIRED_TOKENS, $tokens_found ) );
    }
}
