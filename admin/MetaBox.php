<?php
/**
 * SEO meta box displayed on post/page edit screens.
 *
 * @package TechrappySEO\Admin
 */

defined( 'ABSPATH' ) || exit;

namespace TechrappySEO\Admin;

use TechrappySEO\Services\SEO\SEOEngine;
use TechrappySEO\Services\Queue\Queue;

/**
 * Class MetaBox
 *
 * Registers and renders the "Techrappy SEO" meta box on the post edit screen.
 * Handles saving SEO meta fields and optionally queuing an IA analysis job.
 */
class MetaBox {

	/**
	 * @var SEOEngine
	 */
	private SEOEngine $seo_engine;

	/**
	 * @var Queue
	 */
	private Queue $queue;

	/**
	 * @param SEOEngine $seo_engine SEO engine instance.
	 * @param Queue     $queue      Queue instance.
	 */
	public function __construct( SEOEngine $seo_engine, Queue $queue ) {
		$this->seo_engine = $seo_engine;
		$this->queue      = $queue;
	}

	/**
	 * Register the meta box on supported post types.
	 *
	 * Hook: add_meta_boxes
	 *
	 * @return void
	 */
	public function register(): void {
		$post_types = apply_filters( 'techrappy_seo_meta_box_post_types', array( 'post', 'page' ) );

		foreach ( $post_types as $post_type ) {
			add_meta_box(
				'techrappy-seo-meta-box',
				__( 'Techrappy SEO', 'techrappy-seo' ),
				array( $this, 'render' ),
				$post_type,
				'normal',
				'high'
			);
		}
	}

	/**
	 * Render the meta box HTML.
	 *
	 * @param \WP_Post $post Current post object.
	 *
	 * @return void
	 */
	public function render( \WP_Post $post ): void {
		// Nonce field for security verification on save.
		wp_nonce_field( 'techrappy_seo_meta_box_save', 'techrappy_seo_meta_box_nonce' );

		include TECHRAPPY_SEO_PLUGIN_DIR . 'admin/views/meta-box.php';
	}

	/**
	 * Save the meta box fields when a post is saved.
	 *
	 * Hook: save_post (priority 10, 2 accepted args)
	 *
	 * @param int      $post_id Post ID being saved.
	 * @param \WP_Post $post    Post object.
	 *
	 * @return void
	 */
	public function save( int $post_id, \WP_Post $post ): void {
		// Bail on autosave.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Bail on revisions.
		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}

		// Verify nonce.
		if (
			! isset( $_POST['techrappy_seo_meta_box_nonce'] ) ||
			! wp_verify_nonce(
				sanitize_text_field( wp_unslash( $_POST['techrappy_seo_meta_box_nonce'] ) ),
				'techrappy_seo_meta_box_save'
			)
		) {
			return;
		}

		// Check permissions.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Save focus keyword.
		if ( isset( $_POST['techrappy_seo_focus_keyword'] ) ) {
			update_post_meta(
				$post_id,
				'_techrappy_seo_focus_keyword',
				sanitize_text_field( wp_unslash( $_POST['techrappy_seo_focus_keyword'] ) )
			);
		}

		// Save meta title.
		if ( isset( $_POST['techrappy_seo_meta_title'] ) ) {
			update_post_meta(
				$post_id,
				'_techrappy_seo_meta_title',
				sanitize_text_field( wp_unslash( $_POST['techrappy_seo_meta_title'] ) )
			);
		}

		// Save meta description.
		if ( isset( $_POST['techrappy_seo_meta_description'] ) ) {
			update_post_meta(
				$post_id,
				'_techrappy_seo_meta_description',
				sanitize_textarea_field( wp_unslash( $_POST['techrappy_seo_meta_description'] ) )
			);
		}

		// Optionally queue an AI analysis job.
		$settings = get_option( 'techrappy_seo_settings', array() );
		if ( ! empty( $settings['seo_auto_analyze'] ) && 'publish' === $post->post_status ) {
			$this->queue->push( array(
				'post_id' => $post_id,
				'action'  => 'analyze_seo',
				'payload' => array(
					'focus_keyword' => get_post_meta( $post_id, '_techrappy_seo_focus_keyword', true ),
				),
			) );
		}
	}
}
