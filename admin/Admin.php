<?php
/**
 * Admin-facing functionality of the plugin.
 *
 * @package TechrappySEO\Admin
 */

defined( 'ABSPATH' ) || exit;

namespace TechrappySEO\Admin;

/**
 * Class Admin
 *
 * Handles:
 * - Registering the plugin menu page in wp-admin
 * - Enqueueing admin-specific scripts and styles
 * - Adding plugin action links on the Plugins list screen
 */
class Admin {

	/**
	 * Plugin slug used for asset handles and option names.
	 *
	 * @var string
	 */
	private string $plugin_slug;

	/**
	 * Plugin version for asset cache-busting.
	 *
	 * @var string
	 */
	private string $version;

	/**
	 * @param string $plugin_slug Plugin slug.
	 * @param string $version     Plugin version string.
	 */
	public function __construct( string $plugin_slug, string $version ) {
		$this->plugin_slug = $plugin_slug;
		$this->version     = $version;
	}

	/**
	 * Register the top-level plugin menu page in the WordPress admin.
	 *
	 * Hook: admin_menu
	 *
	 * @return void
	 */
	public function add_plugin_page(): void {
		add_menu_page(
			__( 'Techrappy SEO', 'techrappy-seo' ),           // Page title.
			__( 'Techrappy SEO', 'techrappy-seo' ),           // Menu title.
			'manage_options',                                  // Capability required.
			$this->plugin_slug,                                // Menu slug.
			array( $this, 'render_main_page' ),                // Callback.
			'dashicons-chart-area',                            // Icon.
			80                                                 // Position.
		);

		add_submenu_page(
			$this->plugin_slug,
			__( 'Tableau de bord', 'techrappy-seo' ),
			__( 'Tableau de bord', 'techrappy-seo' ),
			'manage_options',
			$this->plugin_slug,
			array( $this, 'render_main_page' )
		);

		add_submenu_page(
			$this->plugin_slug,
			__( 'Réglages', 'techrappy-seo' ),
			__( 'Réglages', 'techrappy-seo' ),
			'manage_options',
			$this->plugin_slug . '-settings',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Render the main dashboard page.
	 *
	 * @return void
	 */
	public function render_main_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permission refusée.', 'techrappy-seo' ) );
		}

		include TECHRAPPY_SEO_PLUGIN_DIR . 'admin/views/settings-page.php';
	}

	/**
	 * Render the settings page (delegates to Settings class view).
	 *
	 * @return void
	 */
	public function render_settings_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permission refusée.', 'techrappy-seo' ) );
		}

		include TECHRAPPY_SEO_PLUGIN_DIR . 'admin/views/settings-page.php';
	}

	/**
	 * Enqueue admin-specific styles and scripts.
	 *
	 * Hook: admin_enqueue_scripts
	 *
	 * @param string $hook_suffix The current admin page hook suffix.
	 *
	 * @return void
	 */
	public function enqueue_assets( string $hook_suffix ): void {
		// Only load on plugin pages and post edit screens.
		$plugin_pages = array(
			'toplevel_page_' . $this->plugin_slug,
			$this->plugin_slug . '_page_' . $this->plugin_slug . '-settings',
			'post.php',
			'post-new.php',
		);

		if ( ! in_array( $hook_suffix, $plugin_pages, true ) ) {
			return;
		}

		wp_enqueue_style(
			$this->plugin_slug . '-admin',
			TECHRAPPY_SEO_PLUGIN_URL . 'admin/css/admin.css',
			array(),
			$this->version
		);

		wp_enqueue_script(
			$this->plugin_slug . '-admin',
			TECHRAPPY_SEO_PLUGIN_URL . 'admin/js/admin.js',
			array( 'jquery' ),
			$this->version,
			true
		);

		// Pass data from PHP to JS.
		wp_localize_script(
			$this->plugin_slug . '-admin',
			'TechrappySEO',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'techrappy_seo_admin_nonce' ),
				'i18n'    => array(
					'analyzing'  => __( 'Analyse en cours…', 'techrappy-seo' ),
					'error'      => __( 'Une erreur est survenue.', 'techrappy-seo' ),
					'saved'      => __( 'Enregistré.', 'techrappy-seo' ),
				),
			)
		);
	}

	/**
	 * Add a "Réglages" link on the Plugins list screen.
	 *
	 * Hook: plugin_action_links_{TECHRAPPY_SEO_PLUGIN_BASENAME}
	 *
	 * @param array $links Existing action links.
	 *
	 * @return array Modified action links.
	 */
	public function add_action_links( array $links ): array {
		$settings_link = sprintf(
			'<a href="%s">%s</a>',
			esc_url( admin_url( 'admin.php?page=' . $this->plugin_slug . '-settings' ) ),
			esc_html__( 'Réglages', 'techrappy-seo' )
		);

		array_unshift( $links, $settings_link );

		return $links;
	}
}
