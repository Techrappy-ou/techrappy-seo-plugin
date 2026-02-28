<?php
/**
 * Gestion des sections répétables dans les templates Divi.
 *
 * @package TechrappySEO\Divi
 */

declare( strict_types=1 );

namespace TechrappySEO\Divi;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class RepeatableSectionHandler
 *
 * Responsabilité : détecter et dupliquer les sections répétables Divi
 * pour injecter plusieurs blocs de contenu H2.
 *
 * Conventions de tokens dans la section répétable :
 *   {{BLOC_REPEAT}}      — marque la row/section comme répétable (sera supprimé après expansion)
 *   {{BLOC_H2}}          — titre H2 du bloc
 *   {{BLOC_HTML}}        — contenu HTML du bloc
 *   {{BLOC_TRANSITION}}  — micro-transition de fin de bloc
 */
class RepeatableSectionHandler {

    /**
     * Marker qui identifie une section comme répétable.
     */
    const REPEAT_MARKER = '{{BLOC_REPEAT}}';

    /**
     * Balise Divi englobante (en priorité : row, puis section).
     *
     * @var array<string>
     */
    private const DIVI_WRAPPERS = [ 'et_pb_row', 'et_pb_section' ];

    /**
     * Expanse la section répétable en N copies (une par bloc).
     *
     * Supporte deux modes de template :
     *
     * Mode A — BLOC_REPEAT (section clonée) :
     *   Le template contient UNE section marquée {{BLOC_REPEAT}} → le handler
     *   duplique cette section N fois, une par bloc AI.
     *
     * Mode B — Blocs fixes (template multi-sections) :
     *   Le template contient N sections pré-stylées, chacune avec
     *   {{BLOC_H2}} / {{BLOC_HTML}} en dur (pas de {{BLOC_REPEAT}}).
     *   → remplacement séquentiel : 1re occurrence → bloc 1, 2e → bloc 2…
     *   Les sections sans bloc correspondant sont vidées de leurs tokens.
     *
     * @param string                                                              $content Contenu Divi.
     * @param array<int, array{H2: string, html: string, micro_transition: string}> $blocks  Blocs à injecter.
     *
     * @return string Contenu avec la section répétée.
     */
    public function expand( string $content, array $blocks ): string {
        if ( empty( $blocks ) ) {
            return str_replace( self::REPEAT_MARKER, '', $content );
        }

        // Trouver la position du marker.
        $marker_pos = strpos( $content, self::REPEAT_MARKER );
        if ( false === $marker_pos ) {
            // Mode B : pas de {{BLOC_REPEAT}} mais des {{BLOC_H2}} en dur ?
            if ( false !== strpos( $content, '{{BLOC_H2}}' ) ) {
                return $this->sequential_replace( $content, $blocks );
            }
            return $content;
        }

        // Extraire la section répétable (premier wrapper Divi contenant le marker).
        $section = $this->extract_wrapper_section( $content, $marker_pos );
        if ( null === $section ) {
            // Fallback : pas de wrapper Divi trouvé — on génère du HTML brut.
            return $this->fallback_expand( $content, $blocks );
        }

        [ $section_start, $section_end, $section_template ] = $section;

        // Générer N copies de la section, une par bloc.
        $copies = '';
        foreach ( $blocks as $block ) {
            $copy = str_replace( self::REPEAT_MARKER, '', $section_template );
            $copy = str_replace( '{{BLOC_H2}}',         $block['H2']               ?? '', $copy );
            $copy = str_replace( '{{BLOC_HTML}}',        $block['html']             ?? '', $copy );
            $copy = str_replace( '{{BLOC_TRANSITION}}',  $block['micro_transition'] ?? '', $copy );
            $copies .= $copy;
        }

        // Remplacer la section template par toutes les copies.
        return substr( $content, 0, $section_start )
            . $copies
            . substr( $content, $section_end );
    }

    /**
     * Trouve le wrapper Divi (row ou section) qui contient le marker.
     *
     * @param string $content    Contenu complet.
     * @param int    $marker_pos Position du marker.
     *
     * @return array{int, int, string}|null [start, end, template] ou null si non trouvé.
     */
    private function extract_wrapper_section( string $content, int $marker_pos ): ?array {
        foreach ( self::DIVI_WRAPPERS as $tag ) {
            $open_tag  = '[' . $tag;
            $close_tag = '[/' . $tag . ']';

            // Chercher le dernier [et_pb_row avant le marker.
            $before     = substr( $content, 0, $marker_pos );
            $start_pos  = strrpos( $before, $open_tag );

            if ( false === $start_pos ) {
                continue;
            }

            // Chercher le [/et_pb_row] après le marker.
            $end_pos = strpos( $content, $close_tag, $marker_pos );

            if ( false === $end_pos ) {
                continue;
            }

            $end_pos  += strlen( $close_tag );
            $template  = substr( $content, $start_pos, $end_pos - $start_pos );

            return [ $start_pos, $end_pos, $template ];
        }

        return null;
    }

    /**
     * Fallback : génère des blocs HTML bruts quand il n'y a pas de wrapper Divi.
     *
     * @param string                                                              $content
     * @param array<int, array{H2: string, html: string, micro_transition: string}> $blocks
     *
     * @return string
     */
    private function fallback_expand( string $content, array $blocks ): string {
        $html_blocks = '';
        foreach ( $blocks as $block ) {
            $html_blocks .= '<h2>' . esc_html( $block['H2'] ?? '' ) . '</h2>';
            $html_blocks .= $block['html'] ?? '';
            if ( ! empty( $block['micro_transition'] ) ) {
                $html_blocks .= '<p>' . esc_html( $block['micro_transition'] ) . '</p>';
            }
        }

        return str_replace( self::REPEAT_MARKER, $html_blocks, $content );
    }

    /**
     * Mode B : remplace séquentiellement les occurrences de {{BLOC_H2}} / {{BLOC_HTML}}
     * dans un template avec N sections fixes pré-stylées (sans {{BLOC_REPEAT}}).
     *
     * Chaque itération de la boucle consomme LA PREMIÈRE occurrence restante de chaque
     * token, ce qui permet d'affecter un bloc AI différent à chaque section du template.
     * Les occurrences excédentaires (sections sans bloc AI correspondant) sont vidées.
     *
     * @param string                                                              $content
     * @param array<int, array{H2: string, html: string, micro_transition: string}> $blocks
     *
     * @return string
     */
    private function sequential_replace( string $content, array $blocks ): string {
        foreach ( $blocks as $block ) {
            $content = $this->replace_first( '{{BLOC_H2}}',        $block['H2']               ?? '', $content );
            $content = $this->replace_first( '{{BLOC_HTML}}',       $block['html']             ?? '', $content );
            $content = $this->replace_first( '{{BLOC_TRANSITION}}', $block['micro_transition'] ?? '', $content );
        }

        // Supprimer les slots non remplis (template avait plus de sections que de blocs IA).
        $content = str_replace( [ '{{BLOC_H2}}', '{{BLOC_HTML}}', '{{BLOC_TRANSITION}}' ], '', $content );

        return $content;
    }

    /**
     * Remplace uniquement la PREMIÈRE occurrence de $search dans $subject.
     *
     * @param string $search
     * @param string $replace
     * @param string $subject
     *
     * @return string
     */
    private function replace_first( string $search, string $replace, string $subject ): string {
        $pos = strpos( $subject, $search );
        if ( false === $pos ) {
            return $subject;
        }
        return substr_replace( $subject, $replace, $pos, strlen( $search ) );
    }
}
