<?php
/**
 * Intégration avec le plugin Yoast SEO.
 *
 * @package TechrappySEO\SEO
 */

declare( strict_types=1 );

namespace TechrappySEO\SEO;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class YoastIntegration
 *
 * Écrit les métadonnées SEO (title, description, focus keyphrase)
 * dans les post meta Yoast SEO standard.
 *
 * Clés Yoast utilisées :
 * - _yoast_wpseo_focuskw       → focus keyphrase
 * - _yoast_wpseo_title         → SEO title (peut contenir %%title%% etc.)
 * - _yoast_wpseo_metadesc      → meta description
 */
class YoastIntegration {

    /**
     * Écrit les métadonnées SEO pour un post via Yoast.
     *
     * @param int                  $post_id   ID du post WordPress.
     * @param array<string, mixed> $meta_data Données SEO : keyphrase, title, description.
     *
     * @return void
     */
    public function write_meta( int $post_id, array $meta_data ): void {
        if ( $post_id <= 0 ) {
            return;
        }

        $keyphrase   = sanitize_text_field( $meta_data['keyphrase']   ?? '' );
        $title       = sanitize_text_field( $meta_data['title']       ?? '' );
        $description = sanitize_text_field( $meta_data['description'] ?? '' );

        if ( ! empty( $keyphrase ) ) {
            update_post_meta( $post_id, '_yoast_wpseo_focuskw', $keyphrase );
        }

        if ( ! empty( $title ) ) {
            update_post_meta( $post_id, '_yoast_wpseo_title', $title );
        }

        if ( ! empty( $description ) ) {
            update_post_meta( $post_id, '_yoast_wpseo_metadesc', $description );
        }
    }
}
