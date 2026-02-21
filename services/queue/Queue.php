<?php
/**
 * Async job queue — CRUD operations on the queue table.
 *
 * @package TechrappySEO\Services\Queue
 */

defined( 'ABSPATH' ) || exit;

namespace TechrappySEO\Services\Queue;

/**
 * Class Queue
 *
 * Provides push / pop / complete / fail operations on the
 * `{prefix}techrappy_seo_queue` database table created at plugin activation.
 *
 * All DB interactions go through $wpdb to respect WordPress conventions.
 */
class Queue {

	/**
	 * Full table name (with WordPress prefix).
	 *
	 * @var string
	 */
	private string $table;

	public function __construct() {
		global $wpdb;
		$this->table = $wpdb->prefix . 'techrappy_seo_queue';
	}

	/**
	 * Push a new job onto the queue.
	 *
	 * @param array{
	 *     post_id:  int,
	 *     action:   string,
	 *     payload?: array<string, mixed>,
	 *     priority?: int,
	 * } $job Job data.
	 *
	 * @return int|false Inserted row ID, or false on failure.
	 */
	public function push( array $job ): int|false {
		global $wpdb;

		$payload = isset( $job['payload'] ) ? wp_json_encode( $job['payload'] ) : null;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$result = $wpdb->insert(
			$this->table,
			array(
				'post_id'    => absint( $job['post_id'] ),
				'action'     => sanitize_key( $job['action'] ),
				'status'     => 'pending',
				'priority'   => isset( $job['priority'] ) ? absint( $job['priority'] ) : 10,
				'payload'    => $payload,
				'attempts'   => 0,
				'created_at' => current_time( 'mysql' ),
			),
			array( '%d', '%s', '%s', '%d', '%s', '%d', '%s' )
		);

		if ( false === $result ) {
			return false;
		}

		return (int) $wpdb->insert_id;
	}

	/**
	 * Fetch and lock a batch of pending jobs for processing.
	 *
	 * Sets their status to 'processing' atomically to prevent double-execution
	 * in concurrent cron runs.
	 *
	 * @param int $limit Maximum number of jobs to fetch.
	 *
	 * @return array<int, object> Rows from the queue table.
	 */
	public function pop( int $limit = 10 ): array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$jobs = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM `{$this->table}`
				 WHERE status = 'pending'
				 ORDER BY priority ASC, created_at ASC
				 LIMIT %d",
				$limit
			)
		);

		if ( empty( $jobs ) ) {
			return array();
		}

		$ids = array_column( $jobs, 'id' );
		$placeholders = implode( ', ', array_fill( 0, count( $ids ), '%d' ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE `{$this->table}` SET status = 'processing', attempts = attempts + 1
				 WHERE id IN ({$placeholders})",
				...$ids
			)
		);

		return $jobs;
	}

	/**
	 * Mark a job as completed and record the processing time.
	 *
	 * @param int $job_id Queue row ID.
	 *
	 * @return void
	 */
	public function complete( int $job_id ): void {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->update(
			$this->table,
			array(
				'status'       => 'completed',
				'processed_at' => current_time( 'mysql' ),
			),
			array( 'id' => $job_id ),
			array( '%s', '%s' ),
			array( '%d' )
		);
	}

	/**
	 * Mark a job as failed so it can be retried or inspected.
	 *
	 * @param int $job_id Queue row ID.
	 *
	 * @return void
	 */
	public function fail( int $job_id ): void {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->update(
			$this->table,
			array(
				'status'       => 'failed',
				'processed_at' => current_time( 'mysql' ),
			),
			array( 'id' => $job_id ),
			array( '%s', '%s' ),
			array( '%d' )
		);
	}

	/**
	 * Delete all completed jobs older than a given number of days.
	 *
	 * @param int $days_old Minimum age in days.
	 *
	 * @return int Number of rows deleted.
	 */
	public function purge_completed( int $days_old = 7 ): int {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM `{$this->table}`
				 WHERE status = 'completed'
				   AND processed_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
				$days_old
			)
		);

		return (int) $wpdb->rows_affected;
	}

	/**
	 * Count jobs by status.
	 *
	 * @param string $status 'pending', 'processing', 'completed', or 'failed'.
	 *
	 * @return int
	 */
	public function count( string $status = 'pending' ): int {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM `{$this->table}` WHERE status = %s",
				$status
			)
		);
	}
}
