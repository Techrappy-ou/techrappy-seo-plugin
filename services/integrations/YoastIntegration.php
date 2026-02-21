<?php
/**
 * Bridge between Techrappy SEO and Yoast SEO.
 *
 * @package TechrappySEO\Services\Integrations
 */

defined( 'ABSPATH' ) || exit;

namespace TechrappySEO\Services\Integrations;

/**
 * Class YoastIntegration
 *
 * Provides two-way compatibility with the Yoast SEO plugin:
 * - Reads Yoast's focus keyword and meta fields when Techrappy's own fields
 *   are empty, preventing data loss for existing Yoast users.
 * - Optionally writes Techrappy analysis results back to Yoast fields
 *   so that Yoast's own score indicators stay in sync.
 *
 * This class is only instantiated when:
 *   1. The Yoast integration option is enabled in settings.
 *   2. The `WPSEO_Options` class exists (Yoast SEO is active).
 *
 * @see Plugin::define_integrations()
 */
class YoastIntegration {

	/**
	 * Sync Techrappy SEO data after Yoast saves its comparison data.
	 *
	 * Hook: wpseo_save_compare_data (priority 10, 2 accepted args)
	 *
	 * @param int    $post_id  The post ID being saved.
	 * @param object $presenter Yoast presenter object (not used in V1).
	 *
	 * @return void
	 */
	public function on_yoast_save( int $post_id, object $presenter ): void {
		// Bail on autosave and revisions.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}

		$this->import_yoast_fields( $post_id );
	}

	/**
	 * Read Yoast meta fields and populate Techrappy meta fields when empty.
	 *
	 * This ensures existing Yoast data is visible in the Techrappy meta box
	 * without forcing the user to re-enter it.
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return void
	 */
	public function import_yoast_fields( int $post_id ): void {
		// Focus keyword.
		if ( ! get_post_meta( $post_id, '_techrappy_seo_focus_keyword', true ) ) {
			$yoast_keyword = get_post_meta( $post_id, '_yoast_wpseo_focuskw', true );
			if ( $yoast_keyword ) {
				update_post_meta( $post_id, '_techrappy_seo_focus_keyword', sanitize_text_field( $yoast_keyword ) );
			}
		}

		// Meta title.
		if ( ! get_post_meta( $post_id, '_techrappy_seo_meta_title', true ) ) {
			$yoast_title = get_post_meta( $post_id, '_yoast_wpseo_title', true );
			if ( $yoast_title ) {
				update_post_meta( $post_id, '_techrappy_seo_meta_title', sanitize_text_field( $yoast_title ) );
			}
		}

		// Meta description.
		if ( ! get_post_meta( $post_id, '_techrappy_seo_meta_description', true ) ) {
			$yoast_desc = get_post_meta( $post_id, '_yoast_wpseo_metadesc', true );
			if ( $yoast_desc ) {
				update_post_meta( $post_id, '_techrappy_seo_meta_description', sanitize_textarea_field( $yoast_desc ) );
			}
		}

		do_action( 'techrappy_seo_after_yoast_import', $post_id );
	}

	/**
	 * Check whether Yoast SEO is active and available.
	 *
	 * @return bool
	 */
	public static function is_yoast_active(): bool {
		return class_exists( 'WPSEO_Options' );
	}
}
