<?php
/**
 * Gestion des templates Divi Builder.
 *
 * @package TechrappySEO\Divi
 */

declare( strict_types=1 );

namespace TechrappySEO\Divi;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class DiviTemplateHandler
 *
 * Responsabilité : cloner et manipuler les templates Divi pour injection
 * du contenu généré.
 *
 * Workflow :
 *   1. Charger le post_content du template (post_id source)
 *   2. Expanser les sections répétables (RepeatableSectionHandler)
 *   3. Remplacer tous les tokens {{TOKEN}} par les valeurs assemblées (TokenReplacer)
 *   4. Retourner le post_content final prêt pour wp_insert_post()
 */
class DiviTemplateHandler {

    /**
     * Clone un template Divi et injecte le contenu assemblé.
     *
     * @param int   $template_post_id ID du post WordPress servant de template.
     * @param array{
     *   tokens: array<string, string>,
     *   blocks: array<int, array{H2: string, html: string, micro_transition: string}>,
     *   raw_html: string
     * }             $assembled        Contenu assemblé par ContentAssembler.
     *
     * @return string post_content final à injecter dans le nouveau post.
     *                Chaîne vide si le template n'existe pas.
     */
    public function clone_and_inject( int $template_post_id, array $assembled ): string {
        if ( $template_post_id <= 0 ) {
            return $assembled['raw_html'];
        }

        $template = get_post( $template_post_id );

        if ( ! $template || empty( $template->post_content ) ) {
            return $assembled['raw_html'];
        }

        $content = $template->post_content;

        // ── 1. Expanser les sections répétables ───────────────────────────────
        $repeatable = new RepeatableSectionHandler();
        $content    = $repeatable->expand( $content, $assembled['blocks'] );

        // ── 2. Remplacer les tokens ───────────────────────────────────────────
        $replacer = new TokenReplacer();
        $content  = $replacer->replace( $content, $assembled['tokens'] );

        return $content;
    }

    /**
     * Vérifie qu'un post est utilisable comme template Divi
     * (contient des shortcodes Divi et au moins un token).
     *
     * @param int $post_id ID du post.
     *
     * @return bool
     */
    public function is_valid_template( int $post_id ): bool {
        if ( $post_id <= 0 ) {
            return false;
        }

        $post = get_post( $post_id );

        if ( ! $post || empty( $post->post_content ) ) {
            return false;
        }

        // Vérifier la présence de shortcodes Divi.
        $has_divi = str_contains( $post->post_content, '[et_pb_' );

        // Vérifier la présence d'au moins un token.
        $scanner  = new TokenScanner();
        $tokens   = $scanner->scan( $post->post_content );
        $has_tokens = ! empty( $tokens );

        return $has_divi && $has_tokens;
    }
}
