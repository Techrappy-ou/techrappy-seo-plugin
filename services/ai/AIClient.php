<?php
/**
 * Facade for calling the configured AI provider.
 *
 * @package TechrappySEO\Services\AI
 */

defined( 'ABSPATH' ) || exit;

namespace TechrappySEO\Services\AI;

/**
 * Class AIClient
 *
 * Acts as the single point of entry for all AI interactions in the plugin.
 * It reads the active provider from the plugin settings, instantiates the
 * correct ProviderInterface implementation, and delegates calls to it.
 *
 * Usage:
 *   $client   = new AIClient( get_option('techrappy_seo_settings', []) );
 *   $response = $client->generate( 'Suggest SEO improvements for: ' . $content );
 */
class AIClient {

	/**
	 * Plugin settings array.
	 *
	 * @var array<string, mixed>
	 */
	private array $settings;

	/**
	 * Resolved provider instance (lazy-loaded).
	 *
	 * @var ProviderInterface|null
	 */
	private ?ProviderInterface $provider = null;

	/**
	 * @param array<string, mixed> $settings Plugin settings from get_option().
	 */
	public function __construct( array $settings ) {
		$this->settings = $settings;
	}

	/**
	 * Generate text from the configured AI provider.
	 *
	 * @param string               $prompt  The prompt to send.
	 * @param array<string, mixed> $options Optional overrides.
	 *
	 * @return string Generated text, or empty string if provider not available.
	 */
	public function generate( string $prompt, array $options = [] ): string {
		$provider = $this->get_provider();

		if ( null === $provider || ! $provider->is_configured() ) {
			return '';
		}

		try {
			return $provider->generate( $prompt, $options );
		} catch ( \Throwable $e ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( '[TechrappySEO][AIClient] ' . $e->getMessage() );
			}
			return '';
		}
	}

	/**
	 * Check whether the currently configured AI provider is ready to use.
	 *
	 * @return bool
	 */
	public function is_available(): bool {
		$provider = $this->get_provider();
		return null !== $provider && $provider->is_configured();
	}

	/**
	 * Return the active provider name from settings.
	 *
	 * @return string  e.g. 'openai', 'anthropic', or '' if not set.
	 */
	public function get_active_provider_name(): string {
		return $this->settings['ai_provider'] ?? '';
	}

	/**
	 * Resolve and return the ProviderInterface implementation for the
	 * currently configured AI provider. Returns null if none is configured
	 * or the provider class is not registered.
	 *
	 * To register a new provider, add its class mapping to $providers below
	 * and implement the ProviderInterface in services/ai/providers/.
	 *
	 * @return ProviderInterface|null
	 */
	private function get_provider(): ?ProviderInterface {
		if ( null !== $this->provider ) {
			return $this->provider;
		}

		$active = $this->settings['ai_provider'] ?? '';

		if ( '' === $active ) {
			return null;
		}

		/**
		 * Map provider slugs to their class names.
		 * Add entries here when new providers are implemented.
		 *
		 * @var array<string, class-string<ProviderInterface>>
		 */
		$providers = apply_filters(
			'techrappy_seo_ai_providers',
			array(
				// 'openai'    => \TechrappySEO\Services\AI\Providers\OpenAIProvider::class,
				// 'anthropic' => \TechrappySEO\Services\AI\Providers\AnthropicProvider::class,
			)
		);

		if ( ! isset( $providers[ $active ] ) || ! class_exists( $providers[ $active ] ) ) {
			return null;
		}

		$class          = $providers[ $active ];
		$this->provider = new $class( $this->settings );

		return $this->provider;
	}
}
