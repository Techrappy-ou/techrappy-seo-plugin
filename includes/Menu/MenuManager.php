<?php
/**
 * Gestion automatique des menus WordPress après génération bulk.
 *
 * @package TechrappySEO\Menu
 */

declare( strict_types=1 );

namespace TechrappySEO\Menu;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class MenuManager
 *
 * Responsabilité : ajouter les pages générées à un menu WordPress
 * (existant ou nouveau) selon les paramètres passés par l'admin.
 *
 * Paramètres attendus dans $menu_params :
 * - action      : 'none' | 'add_existing' | 'create_new'
 * - menu_id     : int (si action = add_existing)
 * - menu_name   : string (si action = create_new)
 * - menu_location: string (si action = create_new, ex: 'header' | 'footer')
 * - label_format: 'post_title' | 'keyword_city' | 'custom'
 * - label_template: string (si label_format = 'custom', ex: '{keyword} – {city}')
 */
class MenuManager {

    /**
     * Traite l'ajout d'un post à un menu selon les paramètres fournis.
     *
     * @param int                  $post_id     ID du post créé.
     * @param array<string, mixed> $menu_params Paramètres de menu passés dans wp_params.
     * @param array<string, mixed> $job         Données du job (keyword, city, etc.).
     *
     * @return bool True si l'ajout a été effectué ou n'était pas nécessaire, false si erreur.
     */
    public function handle( int $post_id, array $menu_params, array $job ): bool {
        $action = sanitize_key( $menu_params['action'] ?? 'none' );

        if ( 'none' === $action ) {
            return true;
        }

        // Uniquement pour les posts publiés.
        if ( 'publish' !== get_post_status( $post_id ) ) {
            return true;
        }

        $menu_id = $this->resolve_menu_id( $action, $menu_params );

        if ( ! $menu_id ) {
            return false;
        }

        // Anti-duplication : ne pas ajouter si l'URL est déjà dans le menu.
        $permalink = get_permalink( $post_id );
        if ( $this->url_already_in_menu( $menu_id, $permalink ) ) {
            return true;
        }

        $label = $this->build_label( $menu_params, $job, $post_id );

        $item_id = wp_update_nav_menu_item( $menu_id, 0, [
            'menu-item-object-id'   => $post_id,
            'menu-item-object'      => get_post_type( $post_id ),
            'menu-item-type'        => 'post_type',
            'menu-item-title'       => $label,
            'menu-item-status'      => 'publish',
        ] );

        return ! is_wp_error( $item_id );
    }

    /**
     * Résout l'ID du menu cible selon l'action demandée.
     *
     * @param string               $action      'add_existing' | 'create_new'.
     * @param array<string, mixed> $menu_params Paramètres de menu.
     *
     * @return int|false ID du menu, ou false si impossible.
     */
    private function resolve_menu_id( string $action, array $menu_params ): int|false {
        if ( 'add_existing' === $action ) {
            $menu_id = absint( $menu_params['menu_id'] ?? 0 );
            return $menu_id > 0 ? $menu_id : false;
        }

        if ( 'create_new' === $action ) {
            return $this->get_or_create_menu(
                sanitize_text_field( $menu_params['menu_name']     ?? 'Techrappy SEO' ),
                sanitize_key(        $menu_params['menu_location'] ?? '' )
            );
        }

        return false;
    }

    /**
     * Crée un menu s'il n'existe pas déjà, et l'assigne à un emplacement.
     * Retourne l'ID du menu (existant ou créé).
     *
     * @param string $name     Nom du menu.
     * @param string $location Emplacement de thème (ex: 'header').
     *
     * @return int|false
     */
    private function get_or_create_menu( string $name, string $location ): int|false {
        // Chercher un menu existant avec ce nom.
        $existing = wp_get_nav_menu_object( $name );

        if ( $existing ) {
            $menu_id = (int) $existing->term_id;
        } else {
            $menu_id = wp_create_nav_menu( $name );

            if ( is_wp_error( $menu_id ) ) {
                return false;
            }

            $menu_id = (int) $menu_id;
        }

        // Assigner à un emplacement si demandé et valide.
        if ( $location ) {
            $locations = get_theme_mod( 'nav_menu_locations', [] );
            if ( array_key_exists( $location, (array) get_registered_nav_menus() ) ) {
                $locations[ $location ] = $menu_id;
                set_theme_mod( 'nav_menu_locations', $locations );
            }
        }

        return $menu_id;
    }

    /**
     * Vérifie si une URL est déjà présente dans le menu (anti-duplication).
     *
     * @param int    $menu_id   ID du menu.
     * @param string $permalink URL du post.
     *
     * @return bool
     */
    private function url_already_in_menu( int $menu_id, string $permalink ): bool {
        $items = wp_get_nav_menu_items( $menu_id );

        if ( ! $items ) {
            return false;
        }

        foreach ( $items as $item ) {
            if ( trailingslashit( $item->url ) === trailingslashit( $permalink ) ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Construit le libellé du lien de menu selon le format configuré.
     *
     * @param array<string, mixed> $menu_params Paramètres de menu.
     * @param array<string, mixed> $job         Données du job.
     * @param int                  $post_id     ID du post.
     *
     * @return string Libellé du lien de menu.
     */
    private function build_label( array $menu_params, array $job, int $post_id ): string {
        $format = sanitize_key( $menu_params['label_format'] ?? 'post_title' );

        if ( 'keyword_city' === $format ) {
            $keyword = $job['keyword'] ?? '';
            $city    = $job['city']    ?? '';
            return trim( $keyword . ( $city ? ' – ' . $city : '' ) );
        }

        if ( 'custom' === $format ) {
            $template = sanitize_text_field( $menu_params['label_template'] ?? '{keyword}' );
            $label    = str_replace(
                [ '{keyword}', '{city}', '{title}' ],
                [
                    $job['keyword'] ?? '',
                    $job['city']    ?? '',
                    get_the_title( $post_id ),
                ],
                $template
            );
            return trim( $label );
        }

        // Par défaut : titre du post.
        return get_the_title( $post_id );
    }
}
