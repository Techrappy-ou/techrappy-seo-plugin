<?php
/**
 * Public-facing functionality of the plugin.
 *
 * @package TechrappySEO
 */

defined( 'ABSPATH' ) || exit;

namespace TechrappySEO;

/**
 * Class Frontend
 *
 * Handles all hooks that affect the public-facing side of the site:
 * - Outputting SEO meta tags in <head>
 * - Enqueueing front-end assets (when needed)
 */
class Frontend {

	/**
	 * @var string
	 */
	private string $plugin_slug;

	/**
	 * @var string
	 */
	private string $version;

	/**
	 * @param string $plugin_slug Plugin slug.
	 * @param string $version     Plugin version.
	 */
	public function __construct( string $plugin_slug, string $version ) {
		$this->plugin_slug = $plugin_slug;
		$this->version     = $version;
	}

	/**
	 * Output SEO meta tags in the <head> of the page.
	 *
	 * Hook: wp_head (priority 1)
	 *
	 * Only outputs tags on singular post/page views to avoid duplicate
	 * meta tags on archive or home pages (those are handled differently).
	 *
	 * @return void
	 */
	public function output_seo_tags(): void {
		if ( ! is_singular() ) {
			return;
		}

		$post_id = get_the_ID();

		if ( ! $post_id ) {
			return;
		}

		$meta_title       = get_post_meta( $post_id, '_techrappy_seo_meta_title', true );
		$meta_description = get_post_meta( $post_id, '_techrappy_seo_meta_description', true );

		// Output <title> override only if a custom title is set.
		if ( $meta_title ) {
			// Use wp_head title filter instead of echoing a second <title>.
			add_filter( 'pre_get_document_title', function () use ( $meta_title ) {
				return esc_html( $meta_title );
			} );
		}

		// Output <meta name="description"> if a value is stored.
		if ( $meta_description ) {
			printf(
				"\n<meta name=\"description\" content=\"%s\" />\n",
				esc_attr( $meta_description )
			);
		}

		// Allow other plugins/themes to append to our SEO tag output.
		do_action( 'techrappy_seo_after_meta_tags', $post_id );
	}
}
