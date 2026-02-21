<?php
/**
 * Plugin Name:       Techrappy SEO
 * Plugin URI:        https://techrappy.com/plugins/techrappy-seo
 * Description:       Plugin SEO intelligent avec intégration IA pour WordPress. Analyse, suggestions et optimisation automatisée du contenu.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Author:            Techrappy
 * Author URI:        https://techrappy.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       techrappy-seo
 * Domain Path:       /languages
 *
 * @package TechrappySEO
 */

defined( 'ABSPATH' ) || exit;

// Plugin version.
define( 'TECHRAPPY_SEO_VERSION', '1.0.0' );

// Absolute path to the plugin file.
define( 'TECHRAPPY_SEO_PLUGIN_FILE', __FILE__ );

// Absolute path to the plugin directory (with trailing slash).
define( 'TECHRAPPY_SEO_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

// URL to the plugin directory (with trailing slash).
define( 'TECHRAPPY_SEO_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Plugin basename: folder/file.php.
define( 'TECHRAPPY_SEO_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Load all plugin classes manually (no Composer required in V1).
 *
 * Classes are loaded in dependency order:
 * 1. Loader (no dependencies)
 * 2. Activator / Deactivator (no dependencies)
 * 3. Services (no WordPress hook dependencies)
 * 4. Admin / Frontend
 * 5. Plugin (depends on all of the above)
 */
function techrappy_seo_autoload(): void {
	$classes = array(
		// Core.
		TECHRAPPY_SEO_PLUGIN_DIR . 'includes/Loader.php',
		TECHRAPPY_SEO_PLUGIN_DIR . 'includes/Activator.php',
		TECHRAPPY_SEO_PLUGIN_DIR . 'includes/Deactivator.php',

		// Services — AI.
		TECHRAPPY_SEO_PLUGIN_DIR . 'services/ai/ProviderInterface.php',
		TECHRAPPY_SEO_PLUGIN_DIR . 'services/ai/AIClient.php',

		// Services — SEO.
		TECHRAPPY_SEO_PLUGIN_DIR . 'services/seo/ContentAnalyzer.php',
		TECHRAPPY_SEO_PLUGIN_DIR . 'services/seo/SEOEngine.php',

		// Services — Queue.
		TECHRAPPY_SEO_PLUGIN_DIR . 'services/queue/Queue.php',
		TECHRAPPY_SEO_PLUGIN_DIR . 'services/queue/QueueWorker.php',

		// Services — Integrations.
		TECHRAPPY_SEO_PLUGIN_DIR . 'services/integrations/YoastIntegration.php',
		TECHRAPPY_SEO_PLUGIN_DIR . 'services/integrations/DiviIntegration.php',
		TECHRAPPY_SEO_PLUGIN_DIR . 'services/integrations/BulkProcessor.php',

		// Admin.
		TECHRAPPY_SEO_PLUGIN_DIR . 'admin/MetaBox.php',
		TECHRAPPY_SEO_PLUGIN_DIR . 'admin/Settings.php',
		TECHRAPPY_SEO_PLUGIN_DIR . 'admin/Admin.php',

		// Public / Frontend.
		TECHRAPPY_SEO_PLUGIN_DIR . 'public/Frontend.php',

		// Plugin orchestrator (must be last).
		TECHRAPPY_SEO_PLUGIN_DIR . 'includes/Plugin.php',
	);

	foreach ( $classes as $file ) {
		if ( file_exists( $file ) ) {
			require_once $file;
		}
	}
}
techrappy_seo_autoload();

/**
 * Activation hook.
 *
 * @return void
 */
function techrappy_seo_activate(): void {
	\TechrappySEO\Activator::activate();
}
register_activation_hook( TECHRAPPY_SEO_PLUGIN_FILE, 'techrappy_seo_activate' );

/**
 * Deactivation hook.
 *
 * @return void
 */
function techrappy_seo_deactivate(): void {
	\TechrappySEO\Deactivator::deactivate();
}
register_deactivation_hook( TECHRAPPY_SEO_PLUGIN_FILE, 'techrappy_seo_deactivate' );

/**
 * Bootstrap the plugin after all plugins are loaded.
 *
 * Using `plugins_loaded` ensures compatibility with plugins this one integrates
 * with (Yoast SEO, Divi Builder, etc.).
 *
 * @return void
 */
function techrappy_seo_init(): void {
	// Load plugin textdomain for translations.
	load_plugin_textdomain(
		'techrappy-seo',
		false,
		dirname( TECHRAPPY_SEO_PLUGIN_BASENAME ) . '/languages/'
	);

	// Run the plugin.
	$plugin = new \TechrappySEO\Plugin();
	$plugin->run();
}
add_action( 'plugins_loaded', 'techrappy_seo_init' );
