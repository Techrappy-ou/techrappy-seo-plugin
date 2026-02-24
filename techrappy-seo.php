<?php
/**
 * Plugin Name: Techrappy SEO
 * Plugin URI: https://techrappy.fr
 * Description: Génération automatique de pages et articles SEO-ready avec Divi Builder et OpenAI.
 * Version: 1.0.0
 * Requires at least: 6.2
 * Requires PHP: 8.0
 * Author: Techrappy
 * Author URI: https://techrappy.fr
 * License: Proprietary
 * Text Domain: techrappy-seo
 * Domain Path: /languages
 *
 * @package TechrappySEO
 */

declare( strict_types=1 );

// Sécurité : interdire l'accès direct au fichier.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ─────────────────────────────────────────────
// Constantes du plugin
// ─────────────────────────────────────────────

/** Version du plugin */
define( 'TECHRAPPY_SEO_VERSION', '1.0.0' );

/** Chemin absolu vers le répertoire racine du plugin (avec slash final) */
define( 'TECHRAPPY_SEO_PATH', plugin_dir_path( __FILE__ ) );

/** URL publique vers le répertoire racine du plugin (avec slash final) */
define( 'TECHRAPPY_SEO_URL', plugin_dir_url( __FILE__ ) );

/** Nom du fichier principal du plugin (pour register_activation_hook) */
define( 'TECHRAPPY_SEO_BASENAME', plugin_basename( __FILE__ ) );

/** Préfixe utilisé pour toutes les options WordPress du plugin */
define( 'TECHRAPPY_SEO_OPTION_PREFIX', 'techrappy_seo_' );

/** Préfixe des tables de base de données custom */
define( 'TECHRAPPY_SEO_DB_PREFIX', 'techrappy_seo_' );

/** Capacité WordPress requise pour accéder au plugin */
define( 'TECHRAPPY_SEO_CAPABILITY', 'manage_options' );

/** Chemin absolu vers le répertoire des vues (avec slash final) */
define( 'TECHRAPPY_SEO_VIEWS', TECHRAPPY_SEO_PATH . 'views/' );

// ─────────────────────────────────────────────
// Autoloader PSR-4 minimal (sans Composer)
// ─────────────────────────────────────────────

/**
 * Autoloader simple pour le namespace TechrappySEO\
 * Mappe TechrappySEO\Foo\Bar → includes/Foo/Bar.php
 */
spl_autoload_register( function ( string $class_name ): void {
	// Namespace racine du plugin.
	$namespace_prefix = 'TechrappySEO\\';
	$prefix_length    = strlen( $namespace_prefix );

	// Vérifier que la classe appartient à notre namespace.
	if ( strncmp( $namespace_prefix, $class_name, $prefix_length ) !== 0 ) {
		return;
	}

	// Extraire la partie relative du namespace.
	$relative_class = substr( $class_name, $prefix_length );

	// Construire le chemin complet du fichier.
	$file = TECHRAPPY_SEO_PATH . 'includes/' . str_replace( '\\', '/', $relative_class ) . '.php';

	if ( file_exists( $file ) ) {
		require_once $file;
	}
} );

// ─────────────────────────────────────────────
// Hooks d'activation / désactivation
// ─────────────────────────────────────────────

register_activation_hook( __FILE__, function (): void {
	require_once TECHRAPPY_SEO_PATH . 'includes/Core/Activator.php';
	TechrappySEO\Core\Activator::activate();
} );

register_deactivation_hook( __FILE__, function (): void {
	require_once TECHRAPPY_SEO_PATH . 'includes/Core/Deactivator.php';
	TechrappySEO\Core\Deactivator::deactivate();
} );

// ─────────────────────────────────────────────
// Lancement du plugin
// ─────────────────────────────────────────────

/**
 * Retourne l'instance unique du plugin (Singleton).
 * Appelé sur le hook 'plugins_loaded' pour garantir
 * que WordPress et les autres plugins sont chargés.
 */
add_action( 'plugins_loaded', function (): void {
	TechrappySEO\Core\Plugin::get_instance();
} );
