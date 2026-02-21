<?php
/**
 * Plugin settings page — WordPress Settings API.
 *
 * @package TechrappySEO\Admin
 */

defined( 'ABSPATH' ) || exit;

namespace TechrappySEO\Admin;

/**
 * Class Settings
 *
 * Registers all plugin settings with the WordPress Settings API.
 * All settings are stored in a single serialized option: `techrappy_seo_settings`.
 */
class Settings {

	/**
	 * Plugin slug used for settings page and option group names.
	 *
	 * @var string
	 */
	private string $plugin_slug;

	/**
	 * Option name under which all settings are stored.
	 *
	 * @var string
	 */
	private const OPTION_NAME = 'techrappy_seo_settings';

	/**
	 * @param string $plugin_slug Plugin slug.
	 */
	public function __construct( string $plugin_slug ) {
		$this->plugin_slug = $plugin_slug;
	}

	/**
	 * Register settings, sections, and fields via the WordPress Settings API.
	 *
	 * Hook: admin_init
	 *
	 * @return void
	 */
	public function register_settings(): void {
		register_setting(
			$this->plugin_slug . '-settings-group',
			self::OPTION_NAME,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
				'default'           => array(),
			)
		);

		$this->add_section_ai();
		$this->add_section_integrations();
		$this->add_section_advanced();
	}

	// -------------------------------------------------------------------------
	// Sections
	// -------------------------------------------------------------------------

	/**
	 * Register the "Intelligence Artificielle" settings section and its fields.
	 *
	 * @return void
	 */
	private function add_section_ai(): void {
		$section_id = 'techrappy_seo_section_ai';
		$page       = $this->plugin_slug . '-settings';

		add_settings_section(
			$section_id,
			__( 'Intelligence Artificielle', 'techrappy-seo' ),
			array( $this, 'render_section_ai' ),
			$page
		);

		add_settings_field(
			'ai_provider',
			__( 'Fournisseur IA', 'techrappy-seo' ),
			array( $this, 'render_field_ai_provider' ),
			$page,
			$section_id
		);

		add_settings_field(
			'ai_api_key',
			__( 'Clé API', 'techrappy-seo' ),
			array( $this, 'render_field_ai_api_key' ),
			$page,
			$section_id
		);

		add_settings_field(
			'ai_model',
			__( 'Modèle IA', 'techrappy-seo' ),
			array( $this, 'render_field_ai_model' ),
			$page,
			$section_id
		);

		add_settings_field(
			'seo_auto_analyze',
			__( 'Analyse automatique', 'techrappy-seo' ),
			array( $this, 'render_field_auto_analyze' ),
			$page,
			$section_id
		);
	}

	/**
	 * Register the "Intégrations" settings section and its fields.
	 *
	 * @return void
	 */
	private function add_section_integrations(): void {
		$section_id = 'techrappy_seo_section_integrations';
		$page       = $this->plugin_slug . '-settings';

		add_settings_section(
			$section_id,
			__( 'Intégrations', 'techrappy-seo' ),
			array( $this, 'render_section_integrations' ),
			$page
		);

		add_settings_field(
			'yoast_integration',
			__( 'Yoast SEO', 'techrappy-seo' ),
			array( $this, 'render_field_yoast' ),
			$page,
			$section_id
		);

		add_settings_field(
			'divi_integration',
			__( 'Divi Builder', 'techrappy-seo' ),
			array( $this, 'render_field_divi' ),
			$page,
			$section_id
		);
	}

	/**
	 * Register the "Avancé" settings section and its fields.
	 *
	 * @return void
	 */
	private function add_section_advanced(): void {
		$section_id = 'techrappy_seo_section_advanced';
		$page       = $this->plugin_slug . '-settings';

		add_settings_section(
			$section_id,
			__( 'Avancé', 'techrappy-seo' ),
			array( $this, 'render_section_advanced' ),
			$page
		);

		add_settings_field(
			'queue_batch_size',
			__( 'Taille du lot (queue)', 'techrappy-seo' ),
			array( $this, 'render_field_batch_size' ),
			$page,
			$section_id
		);
	}

	// -------------------------------------------------------------------------
	// Section description renderers
	// -------------------------------------------------------------------------

	/** @return void */
	public function render_section_ai(): void {
		echo '<p>' . esc_html__( 'Configurez le fournisseur IA et les paramètres d\'analyse automatique.', 'techrappy-seo' ) . '</p>';
	}

	/** @return void */
	public function render_section_integrations(): void {
		echo '<p>' . esc_html__( 'Activez les ponts vers les plugins tiers détectés.', 'techrappy-seo' ) . '</p>';
	}

	/** @return void */
	public function render_section_advanced(): void {
		echo '<p>' . esc_html__( 'Paramètres avancés pour la file d\'attente et les performances.', 'techrappy-seo' ) . '</p>';
	}

	// -------------------------------------------------------------------------
	// Field renderers
	// -------------------------------------------------------------------------

	/** @return void */
	public function render_field_ai_provider(): void {
		$settings = get_option( self::OPTION_NAME, array() );
		$value    = $settings['ai_provider'] ?? '';
		$providers = array(
			''         => __( '— Sélectionner —', 'techrappy-seo' ),
			'openai'   => 'OpenAI',
			'anthropic' => 'Anthropic',
		);
		echo '<select name="' . esc_attr( self::OPTION_NAME . '[ai_provider]' ) . '">';
		foreach ( $providers as $key => $label ) {
			printf(
				'<option value="%s"%s>%s</option>',
				esc_attr( $key ),
				selected( $value, $key, false ),
				esc_html( $label )
			);
		}
		echo '</select>';
	}

	/** @return void */
	public function render_field_ai_api_key(): void {
		$settings = get_option( self::OPTION_NAME, array() );
		$value    = $settings['ai_api_key'] ?? '';
		printf(
			'<input type="password" name="%s" value="%s" class="regular-text" autocomplete="off" />',
			esc_attr( self::OPTION_NAME . '[ai_api_key]' ),
			esc_attr( $value )
		);
	}

	/** @return void */
	public function render_field_ai_model(): void {
		$settings = get_option( self::OPTION_NAME, array() );
		$value    = $settings['ai_model'] ?? '';
		printf(
			'<input type="text" name="%s" value="%s" class="regular-text" placeholder="gpt-4o" />',
			esc_attr( self::OPTION_NAME . '[ai_model]' ),
			esc_attr( $value )
		);
	}

	/** @return void */
	public function render_field_auto_analyze(): void {
		$settings = get_option( self::OPTION_NAME, array() );
		$checked  = ! empty( $settings['seo_auto_analyze'] );
		printf(
			'<label><input type="checkbox" name="%s" value="1"%s /> %s</label>',
			esc_attr( self::OPTION_NAME . '[seo_auto_analyze]' ),
			checked( $checked, true, false ),
			esc_html__( 'Analyser automatiquement à la sauvegarde d\'un article', 'techrappy-seo' )
		);
	}

	/** @return void */
	public function render_field_yoast(): void {
		$settings = get_option( self::OPTION_NAME, array() );
		$checked  = ! empty( $settings['yoast_integration'] );
		$disabled = ! class_exists( 'WPSEO_Options' ) ? ' disabled' : '';
		printf(
			'<label><input type="checkbox" name="%s" value="1"%s%s /> %s</label>',
			esc_attr( self::OPTION_NAME . '[yoast_integration]' ),
			checked( $checked, true, false ),
			esc_attr( $disabled ),
			esc_html__( 'Activer le bridge Yoast SEO', 'techrappy-seo' )
		);
		if ( $disabled ) {
			echo ' <em>' . esc_html__( '(Yoast SEO non détecté)', 'techrappy-seo' ) . '</em>';
		}
	}

	/** @return void */
	public function render_field_divi(): void {
		$settings = get_option( self::OPTION_NAME, array() );
		$checked  = ! empty( $settings['divi_integration'] );
		$disabled = ! function_exists( 'et_pb_is_pagebuilder_used' ) ? ' disabled' : '';
		printf(
			'<label><input type="checkbox" name="%s" value="1"%s%s /> %s</label>',
			esc_attr( self::OPTION_NAME . '[divi_integration]' ),
			checked( $checked, true, false ),
			esc_attr( $disabled ),
			esc_html__( 'Activer le bridge Divi Builder', 'techrappy-seo' )
		);
		if ( $disabled ) {
			echo ' <em>' . esc_html__( '(Divi Builder non détecté)', 'techrappy-seo' ) . '</em>';
		}
	}

	/** @return void */
	public function render_field_batch_size(): void {
		$settings = get_option( self::OPTION_NAME, array() );
		$value    = isset( $settings['queue_batch_size'] ) ? (int) $settings['queue_batch_size'] : 10;
		printf(
			'<input type="number" name="%s" value="%d" min="1" max="100" class="small-text" />',
			esc_attr( self::OPTION_NAME . '[queue_batch_size]' ),
			$value
		);
		echo '<p class="description">' . esc_html__( 'Nombre d\'articles traités par exécution du cron.', 'techrappy-seo' ) . '</p>';
	}

	// -------------------------------------------------------------------------
	// Sanitization
	// -------------------------------------------------------------------------

	/**
	 * Sanitize all settings before saving.
	 *
	 * @param mixed $input Raw input from the settings form.
	 *
	 * @return array Sanitized settings array.
	 */
	public function sanitize_settings( $input ): array {
		$clean = array();

		$clean['ai_provider']       = isset( $input['ai_provider'] ) ? sanitize_key( $input['ai_provider'] ) : '';
		$clean['ai_api_key']        = isset( $input['ai_api_key'] ) ? sanitize_text_field( $input['ai_api_key'] ) : '';
		$clean['ai_model']          = isset( $input['ai_model'] ) ? sanitize_text_field( $input['ai_model'] ) : '';
		$clean['seo_auto_analyze']  = ! empty( $input['seo_auto_analyze'] );
		$clean['yoast_integration'] = ! empty( $input['yoast_integration'] );
		$clean['divi_integration']  = ! empty( $input['divi_integration'] );
		$clean['queue_batch_size']  = isset( $input['queue_batch_size'] ) ? absint( $input['queue_batch_size'] ) : 10;

		return $clean;
	}
}
