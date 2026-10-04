<?php
/**
 * Construction des liens internes pour le contenu généré.
 *
 * @package TechrappySEO\SEO
 */

declare( strict_types=1 );

namespace TechrappySEO\SEO;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class InternalLinksBuilder
 *
 * Responsabilité : récupérer les pages existantes du site WordPress
 * et les fournir au prompt de liens internes (StepInternalLinks).
 */
class InternalLinksBuilder {

    /**
     * Retourne la liste des pages publiées pour le prompt de maillage interne.
     *
     * @param int    $limit      Nombre max de pages à retourner.
     * @param string $post_types Types de post à inclure (séparés par virgule).
     *
     * @return array<int, array{title: string, url: string}>
     */
    public function get_site_pages( int $limit = 50, string $post_types = 'page,post' ): array {
        $types = array_map( 'trim', explode( ',', $post_types ) );
        $types = array_filter( $types, 'strlen' );

        if ( empty( $types ) ) {
            $types = [ 'page', 'post' ];
        }

        $posts = get_posts( [
            'post_type'      => $types,
            'post_status'    => 'publish',
            'posts_per_page' => min( $limit, 200 ),
            'orderby'        => 'modified',
            'order'          => 'DESC',
        ] );

        return array_map( static function ( \WP_Post $post ): array {
            return [
                'title' => get_the_title( $post ),
                'url'   => (string) get_permalink( $post ),
            ];
        }, $posts );
    }

    /**
     * Retourne la liste des pages en JSON pour injection dans un prompt.
     *
     * @param int $limit Nombre max de pages.
     *
     * @return string JSON.
     */
    public function get_site_pages_json( int $limit = 50 ): string {
        $pages = $this->get_site_pages( $limit );
        return wp_json_encode( $pages ) ?: '[]';
    }
}
