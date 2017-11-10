<?php

namespace LogBook;

use \WP_CLI\CommandWithDBObject;
use \WP_Query;

/**
 * Manages logs, content, and meta.
 *
 * ## EXAMPLES
 *
 *     # Create a new post.
 *     $ wp post create --post_type=post --post_title='A sample post'
 *     Success: Created post 123.
 *
 *     # Update an existing post.
 *     $ wp post update 123 --post_status=draft
 *     Success: Updated post 123.
 *
 *     # Delete an existing post.
 *     $ wp post delete 123
 *     Success: Trashed post 123.
 *
 * @package wp-cli
 */
class CLI extends CommandWithDBObject {

	protected $obj_type = 'logbook';
	protected $obj_fields = array(
		'date',
		'title',
		'level',
		'ip',
		'user',
	);

	/**
	 * Get a list of posts.
	 *
	 * ## OPTIONS
	 *
	 * [--<field>=<value>]
	 * : One or more args to pass to WP_Query.
	 *
	 * [--field=<field>]
	 * : Prints the value of a single field for each post.
	 *
	 * [--fields=<fields>]
	 * : Limit the output to specific object fields.
	 *
	 * [--format=<format>]
	 * : Render output in a particular format.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - csv
	 *   - ids
	 *   - json
	 *   - count
	 *   - yaml
	 * ---
	 *
	 * ## AVAILABLE FIELDS
	 *
	 * These fields will be displayed by default for each post:
	 *
	 * * ID
	 * * date
	 * * title
	 * * level
	 * * ip
	 * * label
	 * * user
	 *
	 * ## EXAMPLES
	 *
	 *     # List post
	 *     $ wp post list --field=ID
	 *     568
	 *     829
	 *     1329
	 *     1695
	 *
	 *     # List posts in JSON
	 *     $ wp post list --post_type=post --posts_per_page=5 --format=json
	 *     [{"ID":1,"post_title":"Hello world!","post_name":"hello-world","post_date":"2015-06-20 09:00:10","post_status":"publish"},{"ID":1178,"post_title":"Markup: HTML Tags and Formatting","post_name":"markup-html-tags-and-formatting","post_date":"2013-01-11 20:22:19","post_status":"draft"}]
	 *
	 *     # List all pages
	 *     $ wp post list --post_type=page --fields=post_title,post_status
	 *     +-------------+-------------+
	 *     | post_title  | post_status |
	 *     +-------------+-------------+
	 *     | Sample Page | publish     |
	 *     +-------------+-------------+
	 *
	 *     # List ids of all pages and posts
	 *     $ wp post list --post_type=page,post --format=ids
	 *     15 25 34 37 198
	 *
	 *     # List given posts
	 *     $ wp post list --post__in=1,3
	 *     +----+--------------+-------------+---------------------+-------------+
	 *     | ID | post_title   | post_name   | post_date           | post_status |
	 *     +----+--------------+-------------+---------------------+-------------+
	 *     | 3  | Lorem Ipsum  | lorem-ipsum | 2016-06-01 14:34:36 | publish     |
	 *     | 1  | Hello world! | hello-world | 2016-06-01 14:31:12 | publish     |
	 *     +----+--------------+-------------+---------------------+-------------+
	 *
	 * @subcommand list
	 */
	public function list_( $_, $assoc_args ) {
		$formatter = $this->get_formatter( $assoc_args );

		$defaults = array(
			'post_type' => 'logbook',
			'posts_per_page' => 20,
			'post_status'    => 'any',
		);
		$query_args = array_merge( $defaults, $assoc_args );
		$query_args = self::process_csv_arguments_to_arrays( $query_args );

		if ( 'ids' == $formatter->format ) {
			$query_args['fields'] = 'ids';
			$query = new WP_Query( $query_args );
			echo implode( ' ', $query->posts );
		} else if ( 'count' === $formatter->format ) {
			$query_args['fields'] = 'ids';
			$query = new WP_Query( $query_args );
			$formatter->display_items( $query->posts );
		} else {
			$query = new WP_Query( $query_args );
			/**
			 * @param \WP_Post $post
			 */
			$posts = array_map( function( $post ) {
				$log = new \stdClass();
				$log->ID = $post->ID;
				$log->date = get_date_from_gmt( $post->post_date_gmt, 'Y-m-d H:i:s' );
				$log->title = $post->post_title;
				$meta = get_post_meta( $post->ID, '_logbook', true );
				$log->level = $meta['log_level'];
				$log->ip = $meta['ip'];
				$log->label = $meta['label'];
				if ( $post->post_author && $u = get_userdata( $post->post_author ) ) {
					$log->user = $u->user_login;
				} else {
					$log->user = '';
				}
				return $log;
			}, $query->posts );
			$formatter->display_items( $posts );
		}
	}
}