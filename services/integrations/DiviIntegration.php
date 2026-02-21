<?php
/**
 * Bridge between Techrappy SEO and the Divi Builder.
 *
 * @package TechrappySEO\Services\Integrations
 */

defined( 'ABSPATH' ) || exit;

namespace TechrappySEO\Services\Integrations;

/**
 * Class DiviIntegration
 *
 * Divi Builder stores page content as shortcode markup inside post_content.
 * Raw shortcodes are nearly meaningless for SEO analysis (keyword density,
 * word count, etc.).
 *
 * This integration hooks into `the_content` and the
 * `techrappy_seo_analyze_content` filter to extract readable plain text
 * from Divi's serialized shortcode markup before analysis.
 *
 * Only active when:
 *   1. The Divi integration setting is enabled.
 *   2. The `et_pb_is_pagebuilder_used` function exists (Divi is active).
 *
 * @see Plugin::define_integrations()
 */
class DiviIntegration {

	/**
	 * Strip Divi shortcodes and return clean text content.
	 *
	 * Hook: the_content (via Loader in Plugin::define_integrations)
	 * Also used via `techrappy_seo_analyze_content` filter in SEOEngine.
	 *
	 * @param string $content Raw post content possibly containing Divi shortcodes.
	 *
	 * @return string Cleaned content suitable for SEO analysis.
	 */
	public function extract_divi_content( string $content ): string {
		if ( ! $this->is_divi_content( $content ) ) {
			return $content;
		}

		// Process Divi shortcodes to render their inner text values.
		$processed = do_shortcode( $content );

		// Strip any remaining HTML tags.
		$plain = wp_strip_all_tags( $processed );

		// Normalise whitespace.
		$plain = preg_replace( '/\s+/', ' ', $plain );

		return trim( (string) $plain );
	}

	/**
	 * Detect whether the given content appears to use Divi Builder shortcodes.
	 *
	 * @param string $content Post content.
	 *
	 * @return bool
	 */
	private function is_divi_content( string $content ): bool {
		return str_contains( $content, '[et_pb_section' );
	}

	/**
	 * Check whether the Divi Builder is active on the current site.
	 *
	 * @return bool
	 */
	public static function is_divi_active(): bool {
		return function_exists( 'et_pb_is_pagebuilder_used' );
	}
}
