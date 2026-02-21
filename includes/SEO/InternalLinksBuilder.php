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
 * Responsabilité : récupérer les pages existantes du site
 * et les fournir au prompt de liens internes.
 */
class InternalLinksBuilder {

    /**
     * Retourne la liste des pages existantes pour le prompt.
     *
     * @param int $limit Nombre max de pages à retourner.
     *
     * @return array<int, array{title: string, url: string}>
     */
    public function get_site_pages( int $limit = 50 ): array {
        // TODO : implémenter la récupération des pages du site.
        return [];
    }
}
