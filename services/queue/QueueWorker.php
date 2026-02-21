<?php
/**
 * Queue worker — processes pending jobs via WP-Cron.
 *
 * @package TechrappySEO\Services\Queue
 */

defined( 'ABSPATH' ) || exit;

namespace TechrappySEO\Services\Queue;

use TechrappySEO\Services\SEO\SEOEngine;

/**
 * Class QueueWorker
 *
 * Invoked by the `techrappy_seo_run_queue` WP-Cron event (twicedaily).
 * Fetches a batch of pending jobs from the Queue and dispatches each one
 * to the appropriate handler.
 *
 * To add a new job type, register a handler in self::$handlers below.
 */
class QueueWorker {

	/**
	 * @var Queue
	 */
	private Queue $queue;

	/**
	 * @var SEOEngine
	 */
	private SEOEngine $seo_engine;

	/**
	 * @param Queue     $queue      The queue storage.
	 * @param SEOEngine $seo_engine The SEO engine used by the 'analyze_seo' job type.
	 */
	public function __construct( Queue $queue, SEOEngine $seo_engine ) {
		$this->queue      = $queue;
		$this->seo_engine = $seo_engine;
	}

	/**
	 * Main entry point called by WP-Cron.
	 *
	 * Hook: techrappy_seo_run_queue
	 *
	 * @return void
	 */
	public function process(): void {
		$settings   = get_option( 'techrappy_seo_settings', array() );
		$batch_size = isset( $settings['queue_batch_size'] ) ? absint( $settings['queue_batch_size'] ) : 10;
		$batch_size = max( 1, min( 100, $batch_size ) );

		$jobs = $this->queue->pop( $batch_size );

		if ( empty( $jobs ) ) {
			return;
		}

		foreach ( $jobs as $job ) {
			$this->dispatch( $job );
		}

		// Housekeeping: remove completed jobs older than 7 days.
		$this->queue->purge_completed( 7 );
	}

	/**
	 * Route a single job to the correct handler method.
	 *
	 * @param object $job Row from the queue table.
	 *
	 * @return void
	 */
	private function dispatch( object $job ): void {
		try {
			$payload = $job->payload ? json_decode( $job->payload, true ) : array();

			switch ( $job->action ) {
				case 'analyze_seo':
					$this->handle_analyze_seo( (int) $job->post_id, (array) $payload );
					break;

				default:
					/**
					 * Allow third-party code to handle custom job types.
					 *
					 * @param object $job     Queue job object.
					 * @param array  $payload Decoded job payload.
					 */
					do_action( 'techrappy_seo_queue_handle_' . sanitize_key( $job->action ), $job, (array) $payload );
					break;
			}

			$this->queue->complete( (int) $job->id );

		} catch ( \Throwable $e ) {
			$this->queue->fail( (int) $job->id );

			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( sprintf(
					'[TechrappySEO][QueueWorker] Job #%d (%s) failed: %s',
					(int) $job->id,
					esc_html( $job->action ),
					$e->getMessage()
				) );
			}
		}
	}

	/**
	 * Handle the 'analyze_seo' job type.
	 *
	 * @param int                  $post_id Post to analyse.
	 * @param array<string, mixed> $payload Optional payload (currently unused).
	 *
	 * @return void
	 */
	private function handle_analyze_seo( int $post_id, array $payload ): void {
		if ( $post_id <= 0 ) {
			return;
		}

		$this->seo_engine->analyze( $post_id );
	}
}
