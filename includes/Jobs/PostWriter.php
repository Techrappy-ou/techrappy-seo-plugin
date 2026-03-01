<?php
/**
 * Création et mise à jour des posts WordPress après génération.
 *
 * @package TechrappySEO\Jobs
 */

declare( strict_types=1 );

namespace TechrappySEO\Jobs;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class PostWriter
 *
 * Responsabilité : créer (ou mettre à jour) un post WordPress à partir
 * du contenu assemblé par ContentAssembler, puis écrire les meta Yoast.
 */
class PostWriter {

    /**
     * Crée un post WordPress à partir du contenu assemblé et des données du job.
     *
     * @param array<string, mixed> $job      Données du job.
     * @param array{
     *   tokens: array<string, string>,
     *   blocks: array<int, mixed>,
     *   raw_html: string
     * }                          $assembled Contenu assemblé par ContentAssembler.
     *
     * @return array{post_id: int, permalink: string, slug: string}|false
     */
    public function write( array $job, array $assembled ): array|false {
        $tokens = $assembled['tokens'];

        // ── 1. Déterminer le slug ─────────────────────────────────────────────
        $slug = $this->resolve_slug( $job, $tokens );

        // ── 2. Préparer les arguments wp_insert_post ─────────────────────────
        $wp_params = is_array( $job['wp_params'] ) ? $job['wp_params'] : [];
        $h1        = $tokens['H1'] ?? $job['keyword'] ?? '';

        // Si un template Divi est configuré, cloner et injecter les tokens.
        $template_post_id = absint( $job['template_post_id'] ?? 0 );
        if ( $template_post_id > 0 ) {
            $divi         = new \TechrappySEO\Divi\DiviTemplateHandler();
            $post_content = $divi->clone_and_inject( $template_post_id, $assembled );
        } else {
            $post_content = $assembled['raw_html'];
        }

        $post_args = [
            'post_title'   => sanitize_text_field( $h1 ),
            'post_content' => $post_content,
            'post_status'  => sanitize_key( $job['publish_status'] ?? 'draft' ),
            'post_type'    => sanitize_key( $job['type'] ?? 'page' ),
            'post_name'    => $slug,
        ];

        // Parent page (optionnel).
        if ( ! empty( $wp_params['parent_id'] ) ) {
            $post_args['post_parent'] = absint( $wp_params['parent_id'] );
        }

        // Catégorie (posts uniquement).
        if ( 'post' === $post_args['post_type'] && ! empty( $wp_params['category_id'] ) ) {
            $post_args['post_category'] = [ absint( $wp_params['category_id'] ) ];
        }

        // Tags (posts uniquement).
        if ( 'post' === $post_args['post_type'] && ! empty( $wp_params['tags'] ) ) {
            $post_args['tags_input'] = array_map( 'absint', (array) $wp_params['tags'] );
        }

        // ── 3. Créer ou mettre à jour le post ────────────────────────────────
        $existing_post_id = absint( $job['result_data']['post_id'] ?? 0 );

        if ( $existing_post_id > 0 ) {
            $post_args['ID'] = $existing_post_id;
            $post_id         = wp_update_post( $post_args, true );
        } else {
            $post_id = wp_insert_post( $post_args, true );
        }

        if ( is_wp_error( $post_id ) ) {
            return false;
        }

        // ── 3b. Mise en page Divi : pleine largeur (pas de barre latérale) ───
        // Divi utilise _et_pb_page_layout pour contrôler la sidebar.
        // et_no_sidebar = "Pas de barre latérale" dans les réglages Divi.
        update_post_meta( $post_id, '_et_pb_page_layout', 'et_no_sidebar' );
        // Activer le Divi Builder sur le post créé programmatiquement.
        if ( $template_post_id > 0 ) {
            update_post_meta( $post_id, '_et_pb_use_builder', '1' );
        }

        // ── 4. Écrire les meta Yoast SEO ─────────────────────────────────────
        $yoast = new \TechrappySEO\SEO\YoastIntegration();
        $yoast->write_meta( $post_id, [
            'meta_title'  => $tokens['meta_title_1'] ?? '',
            'meta_desc'   => $tokens['meta_desc_1']  ?? '',
            'keyphrase'   => $job['keyword']          ?? '',
        ] );

        // ── 4b. Stocker le schéma JSON-LD FAQ en post meta ───────────────────
        // Il sera injecté dans <head> via wp_head, jamais dans post_content.
        if ( ! empty( $assembled['tokens']['faq_jsonld'] ) ) {
            update_post_meta( $post_id, '_techrappy_faq_schema', $assembled['tokens']['faq_jsonld'] );
        } else {
            delete_post_meta( $post_id, '_techrappy_faq_schema' );
        }

        // ── 5. Gestion automatique du menu ────────────────────────────────────
        if ( ! empty( $wp_params['menu_action'] ) && 'none' !== $wp_params['menu_action'] ) {
            $menu_manager = new \TechrappySEO\Menu\MenuManager();
            $menu_manager->handle( $post_id, $wp_params, $job );
        }

        return [
            'post_id'   => $post_id,
            'permalink' => (string) get_permalink( $post_id ),
            'slug'      => get_post_field( 'post_name', $post_id ),
        ];
    }

    /**
     * Résout le slug final selon la règle configurée dans le job.
     *
     * @param array<string, mixed> $job    Données du job.
     * @param array<string, string> $tokens Tokens assemblés.
     *
     * @return string Slug sanitizé.
     */
    private function resolve_slug( array $job, array $tokens ): string {
        $generator = new \TechrappySEO\SEO\SlugGenerator();
        $rule      = $job['slug_rule'] ?? 'from_keyword';
        $city      = $job['city'] ?? '';

        // Priorité 1 : slug suggéré par l'IA (step plan).
        if ( ! empty( $tokens['slug_suggere'] ) ) {
            $raw = $tokens['slug_suggere'];
            // Ajouter la ville si mode bulk et pas déjà dans le slug.
            if ( $city && false === strpos( $raw, sanitize_title( $city ) ) ) {
                $raw .= '-' . sanitize_title( $city );
            }
            return sanitize_title( $raw );
        }

        // Priorité 2 : règle configurée.
        if ( 'from_h1' === $rule && ! empty( $tokens['H1'] ) ) {
            return $generator->generate( $tokens['H1'], $city );
        }

        return $generator->generate( $job['keyword'] ?? '', $city );
    }
}
