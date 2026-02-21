<?php
/**
 * Contract for all AI provider implementations.
 *
 * @package TechrappySEO\Services\AI
 */

defined( 'ABSPATH' ) || exit;

namespace TechrappySEO\Services\AI;

/**
 * Interface ProviderInterface
 *
 * Every AI provider (OpenAI, Anthropic, etc.) must implement this interface.
 * This decouples the AIClient facade from any specific API vendor.
 */
interface ProviderInterface {

	/**
	 * Send a prompt to the AI model and return the generated text.
	 *
	 * @param string               $prompt  The full prompt to send.
	 * @param array<string, mixed> $options Optional provider-specific overrides
	 *                                      (e.g. temperature, max_tokens).
	 *
	 * @return string The AI-generated response text.
	 *
	 * @throws \RuntimeException If the API call fails.
	 */
	public function generate( string $prompt, array $options = [] ): string;

	/**
	 * Return the machine-readable provider identifier.
	 *
	 * Must match the value stored in `techrappy_seo_settings['ai_provider']`.
	 *
	 * @return string  e.g. 'openai', 'anthropic'
	 */
	public function get_name(): string;

	/**
	 * Validate that the provider is properly configured (API key set, etc.).
	 *
	 * @return bool True if the provider is ready to accept requests.
	 */
	public function is_configured(): bool;
}
