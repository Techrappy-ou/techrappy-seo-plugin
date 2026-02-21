<?php
/**
 * The core plugin class — orchestrates all components.
 *
 * @package TechrappySEO
 */

defined( 'ABSPATH' ) || exit;

namespace TechrappySEO;

use TechrappySEO\Admin\Admin;
use TechrappySEO\Admin\Settings;
use TechrappySEO\Admin\MetaBox;
use TechrappySEO\Frontend;
use TechrappySEO\Services\AI\AIClient;
use TechrappySEO\Services\SEO\SEOEngine;
use TechrappySEO\Services\Queue\Queue;
use TechrappySEO\Services\Queue\QueueWorker;
use TechrappySEO\Services\Integrations\YoastIntegration;
use TechrappySEO\Services\Integrations\DiviIntegration;
use TechrappySEO\Services\Integrations\BulkProcessor;

/**
 * Class Plugin
 *
 * Defines and wires all components of the plugin. Uses the Loader to
 * register WordPress hooks without coupling components to each other.
 *
 * Lifecycle:
 *   techrappy_seo_init()  →  new Plugin()  →  Plugin::run()
 *                                                  │
 *                          ┌─────────────────────────┤
 *                          │  define_services()       │
 *                          │  define_admin_hooks()    │
 *                          │  define_public_hooks()   │
 *                          │  define_integrations()   │
 *                          │  loader->run()           │
 *                          └──────────────────────────┘
 */
class Plugin {

	/**
	 * The unique identifier / slug of this plugin.
	 *
	 * @var string
	 */
	private string $plugin_slug;

	/**
	 * The current version of the plugin.
	 *
	 * @var string
	 */
	private string $version;

	/**
	 * The hook loader instance.
	 *
	 * @var Loader
	 */
	private Loader $loader;

	/**
	 * Shared AI client instance (passed to components that need it).
	 *
	 * @var AIClient
	 */
	private AIClient $ai_client;

	/**
	 * Shared SEO engine instance.
	 *
	 * @var SEOEngine
	 */
	private SEOEngine $seo_engine;

	/**
	 * Shared queue instance.
	 *
	 * @var Queue
	 */
	private Queue $queue;

	/**
	 * Initialize all plugin components.
	 */
	public function __construct() {
		$this->plugin_slug = 'techrappy-seo';
		$this->version     = TECHRAPPY_SEO_VERSION;
		$this->loader      = new Loader();

		$this->define_services();
		$this->define_admin_hooks();
		$this->define_public_hooks();
		$this->define_integrations();
	}

	/**
	 * Instantiate service-layer objects.
	 *
	 * Services have no WordPress hook dependencies and are safe to
	 * instantiate before hooks are registered.
	 *
	 * @return void
	 */
	private function define_services(): void {
		$settings         = get_option( 'techrappy_seo_settings', array() );
		$this->ai_client  = new AIClient( $settings );
		$this->seo_engine = new SEOEngine( $this->ai_client );
		$this->queue      = new Queue();

		// Register the cron-driven queue worker.
		$worker = new QueueWorker( $this->queue, $this->seo_engine );
		$this->loader->add_action( 'techrappy_seo_run_queue', $worker, 'process' );
	}

	/**
	 * Register all hooks related to the admin area.
	 *
	 * @return void
	 */
	private function define_admin_hooks(): void {
		$admin    = new Admin( $this->plugin_slug, $this->version );
		$settings = new Settings( $this->plugin_slug );
		$meta_box = new MetaBox( $this->seo_engine, $this->queue );

		// Admin scripts & styles.
		$this->loader->add_action( 'admin_enqueue_scripts', $admin, 'enqueue_assets' );

		// Admin menu & pages.
		$this->loader->add_action( 'admin_menu', $admin, 'add_plugin_page' );

		// Settings API.
		$this->loader->add_action( 'admin_init', $settings, 'register_settings' );

		// Meta box on post edit screens.
		$this->loader->add_action( 'add_meta_boxes', $meta_box, 'register' );
		$this->loader->add_action( 'save_post', $meta_box, 'save', 10, 2 );

		// Plugin action links on the plugins list table.
		$this->loader->add_filter(
			'plugin_action_links_' . TECHRAPPY_SEO_PLUGIN_BASENAME,
			$admin,
			'add_action_links'
		);
	}

	/**
	 * Register all hooks related to the public-facing side.
	 *
	 * @return void
	 */
	private function define_public_hooks(): void {
		$frontend = new Frontend( $this->plugin_slug, $this->version );

		$this->loader->add_action( 'wp_head', $frontend, 'output_seo_tags', 1 );
	}

	/**
	 * Conditionally register third-party integrations based on settings.
	 *
	 * @return void
	 */
	private function define_integrations(): void {
		$settings = get_option( 'techrappy_seo_settings', array() );

		// Yoast SEO integration.
		if ( ! empty( $settings['yoast_integration'] ) && class_exists( 'WPSEO_Options' ) ) {
			$yoast = new YoastIntegration();
			$this->loader->add_action( 'wpseo_save_compare_data', $yoast, 'on_yoast_save', 10, 2 );
		}

		// Divi Builder integration.
		if ( ! empty( $settings['divi_integration'] ) && function_exists( 'et_pb_is_pagebuilder_used' ) ) {
			$divi = new DiviIntegration();
			$this->loader->add_filter( 'the_content', $divi, 'extract_divi_content' );
		}

		// Bulk processor (admin-only).
		if ( is_admin() ) {
			$bulk = new BulkProcessor( $this->queue );
			$this->loader->add_filter( 'bulk_actions-edit-post', $bulk, 'register_bulk_action' );
			$this->loader->add_filter( 'handle_bulk_actions-edit-post', $bulk, 'handle_bulk_action', 10, 3 );
		}
	}

	/**
	 * Execute the plugin by dispatching all registered hooks to WordPress.
	 *
	 * @return void
	 */
	public function run(): void {
		$this->loader->run();
	}

	/**
	 * Get the plugin slug.
	 *
	 * @return string
	 */
	public function get_plugin_slug(): string {
		return $this->plugin_slug;
	}

	/**
	 * Get the current plugin version.
	 *
	 * @return string
	 */
	public function get_version(): string {
		return $this->version;
	}
}
