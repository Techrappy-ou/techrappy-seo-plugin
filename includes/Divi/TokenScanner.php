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
 *
 * Deux patterns de template sont supportés :
 *   Pattern A — répétable  : {{BLOC_REPEAT}} + {{BLOC_H2}} + {{BLOC_HTML}}
 *   Pattern B — numérotés  : {{TITRE_1}} + {{TEXTE_1}}, {{TITRE_2}} + {{TEXTE_2}}…
 */
class TokenScanner {

    /**
     * Pattern regex pour détecter les tokens {{TOKEN}}.
     */
    const TOKEN_PATTERN = '/\{\{([A-Za-z0-9_]+)\}\}/';

    /**
     * Tokens toujours obligatoires (indépendants du pattern de blocs).
     *
     * @var array<string>
     */
    const REQUIRED_TOKENS = [
        'H1',
        'intro_longue_html',
        'conclusion_douce_html',
        'cta_html',
        'faq_visible_html',
    ];

    /**
     * Tokens du pattern "section répétable".
     * Exigés seulement si le template n'utilise pas le pattern numéroté.
     *
     * @var array<string>
     */
    const REPEATABLE_TOKENS = [
        'BLOC_REPEAT',
        'BLOC_H2',
        'BLOC_HTML',
    ];

    /**
     * Préfixes reconnus pour le pattern "tokens numérotés".
     *
     * @var array<string>
     */
    const NUMBERED_PREFIXES = [ 'TITRE_', 'TEXTE_', 'BLOC_', 'BLOCS_' ];

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
     * Logique :
     *   - Les tokens de REQUIRED_TOKENS sont toujours vérifiés.
     *   - Pour les blocs, le template est valide si :
     *     (a) il contient au moins un token BLOC_H2 / BLOC_REPEAT (pattern répétable)
     *     OU
     *     (b) il contient au moins un token numéroté (TITRE_1, TEXTE_1, BLOC_1…).
     *
     * @param array<string> $tokens_found Tokens détectés dans le template.
     *
     * @return array<string> Tokens manquants.
     */
    public function get_missing_required( array $tokens_found ): array {
        $missing = array_values( array_diff( self::REQUIRED_TOKENS, $tokens_found ) );

        // Vérifier si le template couvre les blocs de contenu.
        $has_repeatable = ! empty( array_intersect( self::REPEATABLE_TOKENS, $tokens_found ) );
        $has_numbered   = $this->has_numbered_tokens( $tokens_found );

        if ( ! $has_repeatable && ! $has_numbered ) {
            // Ni pattern répétable ni pattern numéroté : signaler BLOC_H2 comme manquant.
            $missing[] = 'BLOC_H2 ou TITRE_1';
        }

        return $missing;
    }

    /**
     * Vérifie si au moins un token numéroté est présent parmi les tokens trouvés.
     *
     * @param array<string> $tokens_found
     *
     * @return bool
     */
    private function has_numbered_tokens( array $tokens_found ): bool {
        foreach ( $tokens_found as $token ) {
            foreach ( self::NUMBERED_PREFIXES as $prefix ) {
                if ( str_starts_with( $token, $prefix ) && strlen( $token ) > strlen( $prefix ) ) {
                    return true;
                }
            }
        }
        return false;
    }
}
