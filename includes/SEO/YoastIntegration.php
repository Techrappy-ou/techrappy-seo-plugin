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
     * @param int                  $post_id  ID du post WordPress.
     * @param array<string, mixed> $meta_data Données SEO (title, description, keyphrase).
     *
     * @return void
     */
    public function write_meta( int $post_id, array $meta_data ): void {
        // TODO : implémenter l'écriture des métadonnées Yoast SEO.
    }
}
