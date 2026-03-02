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
 *
 * Un seul preg_replace_callback couvre TOUS les encodages de { et } connus :
 *   • brut          {{TOKEN}}
 *   • HTML décimal  &#123;&#123;TOKEN&#125;&#125;
 *   • HTML hex      &#x7B;&#x7B;TOKEN&#x7D;&#x7D;  (casse insensible)
 *   • URL-encodé    %7B%7BTOKEN%7D%7D               (casse insensible)
 *   • Unicode JS    \u007B\u007BTOKEN\u007D\u007D   (casse insensible)
 *
 * Les tokens présents dans le template mais ABSENTS de la map sont
 * automatiquement supprimés (remplacés par '').
 */
class TokenReplacer {

    /**
     * Fragment regex : une accolade ouvrante dans n'importe quel encodage.
     *
     * Décomposé :
     *   \{            → brut
     *   &#123;        → entité HTML décimale
     *   &#x7[Bb];     → entité HTML hexadécimale (&#x7B; ou &#x7b;)
     *   %7B           → URL-encodé (casse insensible via flag /i)
     *   \\u007[Bb]    → littéral \u007B (unicode escape stocké en base)
     */
    private const BRACE_OPEN = '(?:\{|&#123;|&#x7[Bb];|%7B|\\\\u007[Bb])';

    /**
     * Fragment regex : une accolade fermante dans n'importe quel encodage.
     */
    private const BRACE_CLOSE = '(?:\}|&#125;|&#x7[Dd];|%7D|\\\\u007[Dd])';

    /**
     * Remplace les tokens dans le contenu Divi.
     *
     * @param string                $content   Contenu brut du post Divi (post_content).
     * @param array<string, string> $token_map Tableau token → valeur (peut contenir des chaînes vides).
     *
     * @return string Contenu avec tous les tokens résolus (ou supprimés s'ils sont absents de la map).
     */
    public function replace( string $content, array $token_map ): string {
        if ( '' === $content ) {
            return $content;
        }

        // Pattern : {{ TOKEN }} dans n'importe quelle combinaison d'encodage.
        $pattern = '/' . self::BRACE_OPEN . '{2}([A-Za-z0-9_]+)' . self::BRACE_CLOSE . '{2}/i';

        $replaced = preg_replace_callback(
            $pattern,
            static function ( array $m ) use ( $token_map ): string {
                $token = $m[1];
                // Token connu → remplacer par sa valeur (même vide '').
                // Token inconnu → supprimer le placeholder.
                return array_key_exists( $token, $token_map )
                    ? (string) $token_map[ $token ]
                    : '';
            },
            $content
        );

        return $replaced ?? $content;
    }
}
