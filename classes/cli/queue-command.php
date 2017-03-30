<?php

/**
 * Manage queue and jobs.
 *
 * @package wp-cli
 */
class Queue_Command extends WP_CLI_Command {

	/**
	 * Creates the queue database tables.
	 *
	 * @subcommand create-tables
	 */
	public function create_tables( $args, $assoc_args = array() ) {
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		global $wpdb;

		$wpdb->hide_errors();

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$wpdb->prefix}queue (
				id bigint(20) NOT NULL AUTO_INCREMENT,
                job text NOT NULL,
                attempts tinyint(1) NOT NULL DEFAULT 0,
                locked tinyint(1) NOT NULL DEFAULT 0,
                locked_at datetime DEFAULT NULL,
                available_at datetime NOT NULL,
                created_at datetime NOT NULL,
                PRIMARY KEY  (id)
				) $charset_collate;";

		dbDelta( $sql );

		$sql = "CREATE TABLE {$wpdb->prefix}failed_jobs (
				id bigint(20) NOT NULL AUTO_INCREMENT,
                job text NOT NULL,
                failed_at datetime NOT NULL,
                PRIMARY KEY  (id)
				) $charset_collate;";

		dbDelta( $sql );

		WP_CLI::success( "Table {$wpdb->prefix}queue created." );
		WP_CLI::success( "Table {$wpdb->prefix}failed_jobs created." );
	}

	/**
	 * Listen to the queue.
	 */
	public function listen( $args, $assoc_args = array() ) {
		global $wp_queue;

		$worker = new WP_Cli_Worker( $wp_queue );

		$worker->listen();
	}

	/**
	 * Process the next job in the queue.
	 */
	public function work( $args, $assoc_args = array() ) {
		global $wp_queue;

		$worker = new WP_Cli_Worker( $wp_queue );

		$worker->work();
	}

	/**
	 * Show queue status.
	 */
	public function status( $args, $assoc_args = array() ) {
		global $wp_queue;

		WP_CLI::log( $wp_queue->available_jobs() . ' jobs in the queue' );
		WP_CLI::log( $wp_queue->failed_jobs() . ' failed jobs' );
	}

	/**
	 * Push failed jobs back onto the queue.
	 *
	 * @subcommand restart-failed
	 */
	public function restart_failed( $args, $assoc_args = array() ) {
		global $wp_queue;

		if ( ! $wp_queue->failed_jobs() ) {
			WP_CLI::log( 'No failed jobs to restart...' );

			return;
		}

		$count = $wp_queue->restart_failed_jobs();

		WP_CLI::success( $count . ' failed jobs pushed to the queue' );
	}

}