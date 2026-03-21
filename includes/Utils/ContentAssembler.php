<?php
/**
 * Assemblage du contenu HTML final depuis les sorties du pipeline.
 *
 * @package TechrappySEO\Utils
 */

declare( strict_types=1 );

namespace TechrappySEO\Utils;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class ContentAssembler
 *
 * Assemble toutes les sorties des étapes pipeline en un contenu HTML final
 * prêt à l'injection dans post_content WordPress.
 *
 * Structure du HTML assemblé :
 *   <h1> H1 </h1>
 *   [anti_dup intro si ville]
 *   <div class="intro"> intro_longue_html </div>
 *   <h2> bloc.H2 </h2> <div> bloc.html </div>  (×N blocs)
 *   <h2> conclusion h2 </h2> conclusion_html
 *   <div class="cta"> cta_html </div>
 *   <section class="faq"> faq_visible_html </section>
 *   faq_jsonld
 *   [internal_links html]
 */
class ContentAssembler {

    /**
     * Assemble le contenu final depuis les données de toutes les étapes.
     *
     * @param array<string, mixed> $steps_data Données des étapes du job.
     *
     * @return array{
     *   tokens: array<string, string>,
     *   blocks: array<int, array{H2: string, html: string}>,
     *   raw_html: string
     * }
     */
    public function assemble( array $steps_data ): array {
        $parts  = [];
        $tokens = $this->build_token_map( $steps_data );
        $blocks = [];

        // ── H1 ─────────────────────────────────────────────────────────
        $h1 = $tokens['H1'] ?? '';
        if ( ! empty( $h1 ) ) {
            $parts[] = '<h1>' . esc_html( $h1 ) . '</h1>';
        }

        // ── Anti-duplicate intro (pages ville) ──────────────────────────
        $anti_dup = $steps_data['anti_duplicate'] ?? [];
        if ( ! empty( $anti_dup['intro_finale_html'] ) ) {
            $parts[] = '<div class="techrappy-intro">' . $anti_dup['intro_finale_html'] . '</div>';
        } elseif ( ! empty( $tokens['intro'] ) ) {
            // Intro standard.
            $parts[] = '<div class="techrappy-intro">' . $tokens['intro'] . '</div>';
        }

        // ── Blocs de contenu (H2 + texte) ───────────────────────────────
        $written_blocks = $steps_data['written_blocks'] ?? [];
        foreach ( $written_blocks as $index => $bloc ) {
            $h2   = $bloc['H2'] ?? '';
            $html = $bloc['html'] ?? '';

            if ( empty( $h2 ) && empty( $html ) ) {
                continue;
            }

            if ( ! empty( $h2 ) ) {
                $parts[] = '<h2>' . esc_html( $h2 ) . '</h2>';
            }

            if ( ! empty( $html ) ) {
                $parts[] = '<div class="techrappy-section">' . $html . '</div>';
            }

            $blocks[] = [ 'H2' => $h2, 'html' => $html ];
        }

        // ── Conclusion ──────────────────────────────────────────────────
        $conclusion   = $steps_data['conclusion'] ?? [];
        $h2_fin       = $conclusion['h2_fin_suggestions'][0] ?? '';
        $conclusion_html = $conclusion['conclusion_douce_html'] ?? '';
        $cta_html     = $conclusion['cta_html'] ?? '';

        if ( ! empty( $h2_fin ) ) {
            $parts[] = '<h2>' . esc_html( $h2_fin ) . '</h2>';
        }

        if ( ! empty( $conclusion_html ) ) {
            $parts[] = '<div class="techrappy-conclusion">' . $conclusion_html . '</div>';
        }

        if ( ! empty( $cta_html ) ) {
            $parts[] = '<div class="techrappy-cta">' . $cta_html . '</div>';
        }

        // ── FAQ ─────────────────────────────────────────────────────────
        $faq = $steps_data['faq'] ?? [];
        if ( ! empty( $faq['faq_visible_html'] ) ) {
            $parts[] = $faq['faq_visible_html'];
        }

        if ( ! empty( $faq['faq_jsonld'] ) ) {
            $parts[] = $faq['faq_jsonld'];
        }

        // ── Liens internes ───────────────────────────────────────────────
        $internal_links = $steps_data['internal_links'] ?? [];
        if ( ! empty( $internal_links ) ) {
            $parts[] = $this->build_internal_links_html( $internal_links );
        }

        $raw_html = implode( "\n", array_filter( $parts ) );

        return [
            'tokens'   => $tokens,
            'blocks'   => $blocks,
            'raw_html' => $raw_html,
        ];
    }

    /**
     * Construit le tableau de tokens simples (H1, intro, meta…)
     * pour le remplacement dans les templates Divi.
     *
     * @param array<string, mixed> $steps_data
     *
     * @return array<string, string>
     */
    public function build_token_map( array $steps_data ): array {
        $plan       = $steps_data['plan'] ?? [];
        $intro      = $steps_data['intro'] ?? [];
        $meta       = $steps_data['meta'] ?? [];
        $conclusion = $steps_data['conclusion'] ?? [];

        $tokens = [
            'H1'              => $plan['H1'] ?? '',
            'slug'            => $plan['slug_suggere'] ?? '',
            'intro'           => $intro['intro_longue_html'] ?? '',
            'intro_mobile'    => $intro['intro_courte_mobile'] ?? '',
            'metatitle'       => $meta['meta_title_1'] ?? '',
            'metadescription' => $meta['meta_desc_1'] ?? '',
            'cta_block'       => $conclusion['cta_html'] ?? '',
            'faq_block'       => $steps_data['faq']['faq_visible_html'] ?? '',
        ];

        // Tokens de blocs numérotés (h2_1, text_1, h2_2, text_2...).
        $written_blocks = $steps_data['written_blocks'] ?? [];
        foreach ( $written_blocks as $i => $bloc ) {
            $n                    = $i + 1;
            $tokens["h2_{$n}"]   = $bloc['H2'] ?? '';
            $tokens["text_{$n}"] = $bloc['html'] ?? '';
        }

        return $tokens;
    }

    // ─────────────────────────────────────────
    // Helpers privés
    // ─────────────────────────────────────────

    /**
     * Construit le HTML des liens internes suggérés.
     *
     * @param array<int, array{url: string, anchor: string, placement: string, why: string}> $links
     *
     * @return string
     */
    private function build_internal_links_html( array $links ): string {
        if ( empty( $links ) ) {
            return '';
        }

        $items = [];
        foreach ( $links as $link ) {
            $url    = esc_url( $link['url'] ?? '' );
            $anchor = esc_html( $link['anchor'] ?? '' );

            if ( empty( $url ) || empty( $anchor ) ) {
                continue;
            }

            $items[] = '<li><a href="' . $url . '">' . $anchor . '</a></li>';
        }

        if ( empty( $items ) ) {
            return '';
        }

        return '<nav class="techrappy-internal-links" aria-label="Articles connexes"><ul>'
            . implode( '', $items )
            . '</ul></nav>';
    }
}
