<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class PV_CPT {

	public static function register_post_type() {
		$labels = [
			'name'               => __( 'Polls', 'poll-verdict' ),
			'singular_name'      => __( 'Poll', 'poll-verdict' ),
			'add_new'            => __( 'Add New Poll', 'poll-verdict' ),
			'add_new_item'       => __( 'Add New Poll', 'poll-verdict' ),
			'edit_item'          => __( 'Edit Poll', 'poll-verdict' ),
			'new_item'           => __( 'New Poll', 'poll-verdict' ),
			'view_item'          => __( 'View Poll', 'poll-verdict' ),
			'search_items'       => __( 'Search Polls', 'poll-verdict' ),
			'not_found'          => __( 'No polls found', 'poll-verdict' ),
			'not_found_in_trash' => __( 'No polls found in Trash', 'poll-verdict' ),
			'menu_name'          => __( 'Polls', 'poll-verdict' ),
		];

		$args = [
			'labels'        => $labels,
			'public'        => true,
			'show_ui'       => true,
			'show_in_menu'  => true,
			'menu_icon'     => 'dashicons-chart-bar',
			'supports'      => [ 'title', 'editor', 'thumbnail' ],
			'has_archive'   => false,
			'rewrite'       => [ 'slug' => 'poll' ],
			'show_in_rest'  => true,
			'capability_type' => 'post',
		];

		register_post_type( 'pv_poll', $args );
	}
}
