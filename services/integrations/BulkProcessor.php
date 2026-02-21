<?php
/**
 * Bulk SEO analysis via the WordPress posts list table.
 *
 * @package TechrappySEO\Services\Integrations
 */

defined( 'ABSPATH' ) || exit;

namespace TechrappySEO\Services\Integrations;

use TechrappySEO\Services\Queue\Queue;

/**
 * Class BulkProcessor
 *
 * Adds a "Analyser avec Techrappy SEO" entry to the bulk actions dropdown
 * on the Posts list screen. When triggered, it pushes one `analyze_seo`
 * queue job per selected post, which are then processed by QueueWorker
 * on the next cron run.
 *
 * Hooks registered in Plugin::define_integrations():
 *   - bulk_actions-edit-post          → register_bulk_action()
 *   - handle_bulk_actions-edit-post   → handle_bulk_action()
 */
class BulkProcessor {

	/**
	 * Bulk action identifier used in the select dropdown.
	 *
	 * @var string
	 */
	private const ACTION_SLUG = 'techrappy_seo_analyze';

	/**
	 * @var Queue
	 */
	private Queue $queue;

	/**
	 * @param Queue $queue Queue instance for pushing analysis jobs.
	 */
	public function __construct( Queue $queue ) {
		$this->queue = $queue;
	}

	/**
	 * Add the bulk action to the posts list dropdown.
	 *
	 * Hook: bulk_actions-edit-post
	 *
	 * @param array<string, string> $bulk_actions Existing bulk actions.
	 *
	 * @return array<string, string>
	 */
	public function register_bulk_action( array $bulk_actions ): array {
		$bulk_actions[ self::ACTION_SLUG ] = __( 'Analyser avec Techrappy SEO', 'techrappy-seo' );
		return $bulk_actions;
	}

	/**
	 * Handle the bulk action: push one queue job per selected post.
	 *
	 * Hook: handle_bulk_actions-edit-post (priority 10, 3 accepted args)
	 *
	 * @param string   $redirect_url Current redirect URL.
	 * @param string   $action       Current bulk action name.
	 * @param int[]    $post_ids     Array of selected post IDs.
	 *
	 * @return string Redirect URL (with query args added on success).
	 */
	public function handle_bulk_action( string $redirect_url, string $action, array $post_ids ): string {
		if ( self::ACTION_SLUG !== $action ) {
			return $redirect_url;
		}

		if ( ! current_user_can( 'edit_posts' ) ) {
			return $redirect_url;
		}

		$queued = 0;

		foreach ( $post_ids as $post_id ) {
			$post_id = absint( $post_id );
			if ( $post_id <= 0 ) {
				continue;
			}

			$result = $this->queue->push( array(
				'post_id'  => $post_id,
				'action'   => 'analyze_seo',
				'priority' => 5,
			) );

			if ( false !== $result ) {
				++$queued;
			}
		}

		if ( $queued > 0 ) {
			$redirect_url = add_query_arg(
				array(
					'techrappy_seo_bulk_queued' => $queued,
				),
				$redirect_url
			);
		}

		do_action( 'techrappy_seo_after_bulk_queue', $queued, $post_ids );

		return $redirect_url;
	}
}
