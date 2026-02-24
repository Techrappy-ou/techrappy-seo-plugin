<?php
/**
 * Bootstrap pour les tests PHPUnit.
 * Définit les constantes WordPress et les stubs de fonctions WP
 * sans charger WordPress.
 */

// ── Constantes WordPress minimales ────────────────────────────────────────
define( 'ABSPATH',                  '/' );
define( 'TECHRAPPY_SEO_VERSION',    '1.0.0' );
define( 'TECHRAPPY_SEO_PATH',       dirname( __DIR__ ) . '/' );
define( 'TECHRAPPY_SEO_URL',        'http://localhost/wp-content/plugins/techrappy-seo/' );
define( 'TECHRAPPY_SEO_VIEWS',      TECHRAPPY_SEO_PATH . 'views/' );
define( 'TECHRAPPY_SEO_CAPABILITY', 'manage_options' );
define( 'TECHRAPPY_SEO_DB_PREFIX',  'techrappy_seo_' );

// ── Autoloader Composer ───────────────────────────────────────────────────
require_once dirname( __DIR__ ) . '/vendor/autoload.php';

// ── Autoloader plugin (PSR-4 manuel) ─────────────────────────────────────
spl_autoload_register( function ( string $class_name ): void {
    $prefix = 'TechrappySEO\\';
    if ( strncmp( $prefix, $class_name, strlen( $prefix ) ) !== 0 ) {
        return;
    }
    $relative = substr( $class_name, strlen( $prefix ) );
    $file     = TECHRAPPY_SEO_PATH . 'includes/' . str_replace( '\\', '/', $relative ) . '.php';
    if ( file_exists( $file ) ) {
        require_once $file;
    }
} );

// ── Stubs de fonctions WordPress utilisées dans les classes ──────────────
// (Brain\Monkey fournit les mocks per-test ; ces stubs couvrent les cas
//  où la fonction n'est pas explicitement mockée dans un test.)

if ( ! function_exists( 'sanitize_title' ) ) {
    function sanitize_title( string $title ): string {
        $title = mb_strtolower( $title, 'UTF-8' );
        $title = preg_replace( '/[àáâãäå]/u', 'a', $title ) ?? $title;
        $title = preg_replace( '/[èéêë]/u',   'e', $title ) ?? $title;
        $title = preg_replace( '/[ìíîï]/u',   'i', $title ) ?? $title;
        $title = preg_replace( '/[òóôõö]/u',  'o', $title ) ?? $title;
        $title = preg_replace( '/[ùúûü]/u',   'u', $title ) ?? $title;
        $title = preg_replace( '/[ç]/u',       'c', $title ) ?? $title;
        $title = preg_replace( '/[^a-z0-9\-]/u', '-', $title ) ?? $title;
        $title = preg_replace( '/-+/', '-', $title ) ?? $title;
        return trim( $title, '-' );
    }
}

if ( ! function_exists( 'wp_json_encode' ) ) {
    function wp_json_encode( mixed $data ): string|false {
        return json_encode( $data, JSON_UNESCAPED_UNICODE );
    }
}

if ( ! function_exists( 'absint' ) ) {
    function absint( mixed $maybeint ): int {
        return abs( (int) $maybeint );
    }
}

if ( ! function_exists( 'trailingslashit' ) ) {
    function trailingslashit( string $string ): string {
        return rtrim( $string, '/\\' ) . '/';
    }
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
    function sanitize_text_field( string $str ): string {
        return strip_tags( trim( $str ) );
    }
}

if ( ! function_exists( 'sanitize_key' ) ) {
    function sanitize_key( string $key ): string {
        return strtolower( preg_replace( '/[^a-z0-9_\-]/i', '', $key ) ?? $key );
    }
}

if ( ! function_exists( 'get_post_status' ) ) {
    function get_post_status( int $post_id ): string {
        return 'publish';
    }
}

if ( ! function_exists( 'get_permalink' ) ) {
    function get_permalink( int $post_id ): string {
        return 'http://localhost/?p=' . $post_id;
    }
}

if ( ! function_exists( 'get_post_type' ) ) {
    function get_post_type( int $post_id ): string {
        return 'page';
    }
}

if ( ! function_exists( 'get_the_title' ) ) {
    function get_the_title( int|object $post ): string {
        return 'Test Post';
    }
}

if ( ! function_exists( 'wp_get_nav_menu_items' ) ) {
    function wp_get_nav_menu_items( int $menu_id ): array {
        return [];
    }
}

if ( ! function_exists( 'wp_update_nav_menu_item' ) ) {
    function wp_update_nav_menu_item( int $menu_id, int $menu_item_db_id, array $menu_item_data ): int|bool {
        return 1;
    }
}

if ( ! function_exists( 'wp_get_nav_menu_object' ) ) {
    function wp_get_nav_menu_object( string $menu ): object|false {
        return false;
    }
}

if ( ! function_exists( 'wp_create_nav_menu' ) ) {
    function wp_create_nav_menu( string $menu_name ): int|WP_Error {
        return 1;
    }
}

if ( ! function_exists( 'get_theme_mod' ) ) {
    function get_theme_mod( string $name, mixed $default = false ): mixed {
        return $default;
    }
}

if ( ! function_exists( 'set_theme_mod' ) ) {
    function set_theme_mod( string $name, mixed $value ): void {}
}

if ( ! function_exists( 'get_registered_nav_menus' ) ) {
    function get_registered_nav_menus(): array {
        return [ 'primary' => 'Menu principal', 'footer' => 'Pied de page' ];
    }
}

if ( ! function_exists( 'is_wp_error' ) ) {
    function is_wp_error( mixed $thing ): bool {
        return $thing instanceof WP_Error;
    }
}

if ( ! class_exists( 'WP_Error' ) ) {
    class WP_Error {
        private string $message;
        public function __construct( string $code = '', string $message = '' ) {
            $this->message = $message;
        }
        public function get_error_message(): string { return $this->message; }
    }
}
