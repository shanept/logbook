<?php

namespace Talog;

class Logger
{
	const post_type = 'talog';
	private $hooks;

	public function __construct()
	{

	}

	/**
	 * Registers the logger to the specific hooks.
	 *
	 * @param array $hooks      An array of hooks to save log.
	 * @param string $log       The log message.
	 * @param string $log_level The Log level.
	 */
	public function watch( $hooks, $log, $log_level = 'normal' )
	{
		foreach ( $hooks as $hook ) {
			add_action( $hook, function() use ( $log, $log_level ) {
				$this->save( $log, $log_level );
			} );
		}
	}

	/**
	 * Callback function to save log.
	 *
	 * @param string $log       The log message.
	 * @param string $log_level The log level.
	 *
	 * @return int|\WP_Error
	 */
	public function save( $log, $log_level = 'normal' )
	{
		$user = $this->get_user();

		$post_id = wp_insert_post( array(
			'post_type' => self::post_type,
			'post_title' => $log,
			'post_status' => 'publish',
			'post_author' => $user->ID
		) );

		update_post_meta( $post_id, "_log_level", $log_level );
		update_post_meta( $post_id, "_last_error", $this->error_get_last() );
		update_post_meta( $post_id, "_hook", $this->get_current_hook() );

		return $post_id;
	}

	protected function get_current_hook()
	{
		return current_filter();
	}

	protected function get_user()
	{
		return wp_get_current_user();
	}

	protected function error_get_last()
	{
		return error_get_last();
	}
}