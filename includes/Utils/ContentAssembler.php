<?php
/**
 * Assemblage du contenu final à partir des sorties du pipeline.
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
 * Responsabilité : assembler toutes les sorties des étapes pipeline
 * en un contenu HTML final prêt à l'injection dans post_content.
 */
class ContentAssembler {

    /**
     * Assemble le contenu final depuis les données de toutes les étapes.
     *
     * @param array<string, mixed> $steps_data Données des étapes du job ($job['steps']).
     *
     * @return array{
     *   tokens: array<string, string>,
     *   blocks: array<int, array{H2: string, html: string}>,
     *   raw_html: string
     * }
     */
    public function assemble( array $steps_data ): array {
        $tokens = $this->build_token_map( $steps_data );
        $blocks = $this->extract_blocks( $steps_data );
        $html   = $this->build_raw_html( $tokens, $blocks, $steps_data );

        return [
            'tokens'   => $tokens,
            'blocks'   => $blocks,
            'raw_html' => $html,
        ];
    }

    /**
     * Construit le tableau de tokens simples (H1, intro, meta...)
     * à partir des données des étapes.
     *
     * @param array<string, mixed> $steps_data
     *
     * @return array<string, string>
     */
    public function build_token_map( array $steps_data ): array {
        $tokens = [];

        // ── Plan : H1, slug suggéré ───────────────────────────────────────────
        $plan = $steps_data['plan']['data'] ?? [];
        $tokens['H1']           = $plan['H1']            ?? '';
        $tokens['slug_suggere'] = $plan['slug_suggere']  ?? '';

        // ── Intro ─────────────────────────────────────────────────────────────
        $intro = $steps_data['intro']['data'] ?? [];
        $tokens['intro_longue_html']    = $intro['intro_longue_html']    ?? '';
        $tokens['intro_courte_mobile']  = $intro['intro_courte_mobile']  ?? '';

        // ── Conclusion + CTA ─────────────────────────────────────────────────
        $conclusion = $steps_data['conclusion_cta']['data'] ?? [];
        $tokens['conclusion_douce_html'] = $conclusion['conclusion_douce_html'] ?? '';
        $tokens['conclusion_pro_html']   = $conclusion['conclusion_pro_html']   ?? '';
        $tokens['cta_html']              = $conclusion['cta_html']              ?? '';

        // ── Meta ──────────────────────────────────────────────────────────────
        $meta = $steps_data['meta']['data'] ?? [];
        $tokens['meta_title_1'] = $meta['meta_title_1'] ?? '';
        $tokens['meta_title_2'] = $meta['meta_title_2'] ?? '';
        $tokens['meta_desc_1']  = $meta['meta_desc_1']  ?? '';
        $tokens['meta_desc_2']  = $meta['meta_desc_2']  ?? '';

        // ── FAQ ───────────────────────────────────────────────────────────────
        $faq = $steps_data['faq']['data'] ?? [];
        $tokens['faq_visible_html'] = $faq['faq_visible_html'] ?? '';
        $tokens['faq_jsonld']       = $faq['faq_jsonld']       ?? '';

        // ── Anti-duplicate (bulk) ─────────────────────────────────────────────
        $anti = $steps_data['anti_duplicate']['data'] ?? [];
        $tokens['intro_finale_html'] = $anti['intro_finale_html'] ?? '';

        return array_filter( $tokens, static fn( $v ) => '' !== $v );
    }

    /**
     * Extrait la liste ordonnée des blocs H2 rédigés.
     *
     * @param array<string, mixed> $steps_data
     *
     * @return array<int, array{H2: string, html: string, micro_transition: string}>
     */
    private function extract_blocks( array $steps_data ): array {
        $raw_blocks = $steps_data['blocks']['data'] ?? [];
        $blocks     = [];

        foreach ( $raw_blocks as $block ) {
            if ( empty( $block['html'] ) ) {
                continue;
            }
            $blocks[] = [
                'H2'               => $block['H2']               ?? '',
                'html'             => $block['html']             ?? '',
                'micro_transition' => $block['micro_transition'] ?? '',
            ];
        }

        return $blocks;
    }

    /**
     * Construit le HTML brut complet dans l'ordre de lecture naturel.
     *
     * @param array<string, string>                                               $tokens
     * @param array<int, array{H2: string, html: string, micro_transition: string}> $blocks
     * @param array<string, mixed>                                                $steps_data
     *
     * @return string
     */
    private function build_raw_html( array $tokens, array $blocks, array $steps_data ): string {
        $parts = [];

        // H1
        if ( ! empty( $tokens['H1'] ) ) {
            $parts[] = '<h1>' . esc_html( $tokens['H1'] ) . '</h1>';
        }

        // Introduction (bulk : intro finale remplace intro standard)
        $intro_html = ! empty( $tokens['intro_finale_html'] )
            ? $tokens['intro_finale_html']
            : ( $tokens['intro_longue_html'] ?? '' );

        if ( $intro_html ) {
            $parts[] = $intro_html;
        }

        // Blocs H2
        foreach ( $blocks as $block ) {
            if ( ! empty( $block['H2'] ) ) {
                $parts[] = '<h2>' . esc_html( $block['H2'] ) . '</h2>';
            }
            $parts[] = $block['html'];
            if ( ! empty( $block['micro_transition'] ) ) {
                $parts[] = '<p class="techrappy-transition">' . esc_html( $block['micro_transition'] ) . '</p>';
            }
        }

        // Conclusion
        if ( ! empty( $tokens['conclusion_douce_html'] ) ) {
            $parts[] = $tokens['conclusion_douce_html'];
        }

        // CTA
        if ( ! empty( $tokens['cta_html'] ) ) {
            $parts[] = $tokens['cta_html'];
        }

        // FAQ
        if ( ! empty( $tokens['faq_visible_html'] ) ) {
            $parts[] = $tokens['faq_visible_html'];
        }

        // JSON-LD FAQ schema
        if ( ! empty( $tokens['faq_jsonld'] ) ) {
            $parts[] = $tokens['faq_jsonld'];
        }

        return implode( "\n\n", array_filter( $parts ) );
    }
}
