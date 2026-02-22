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
 * Responsabilité : écrire les métadonnées SEO (title, description, focus keyphrase)
 * dans les champs Yoast SEO via les post meta appropriés.
 */
class YoastIntegration {

    /**
     * Écrit les métadonnées SEO pour un post via Yoast.
     *
     * @param int                  $post_id   ID du post WordPress.
     * @param array<string, mixed> $meta_data Données SEO (meta_title, meta_desc, keyphrase).
     *
     * @return void
     */
    public function write_meta( int $post_id, array $meta_data ): void {
        if ( $post_id <= 0 ) {
            return;
        }

        // Meta title (Yoast : _yoast_wpseo_title).
        if ( ! empty( $meta_data['meta_title'] ) ) {
            update_post_meta( $post_id, '_yoast_wpseo_title', sanitize_text_field( $meta_data['meta_title'] ) );
        }

        // Meta description (Yoast : _yoast_wpseo_metadesc).
        if ( ! empty( $meta_data['meta_desc'] ) ) {
            update_post_meta( $post_id, '_yoast_wpseo_metadesc', sanitize_textarea_field( $meta_data['meta_desc'] ) );
        }

        // Focus keyphrase (Yoast : _yoast_wpseo_focuskw).
        if ( ! empty( $meta_data['keyphrase'] ) ) {
            update_post_meta( $post_id, '_yoast_wpseo_focuskw', sanitize_text_field( $meta_data['keyphrase'] ) );
        }
    }
}
