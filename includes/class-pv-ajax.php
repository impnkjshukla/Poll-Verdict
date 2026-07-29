<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class PV_Ajax {

	public static function init() {
		add_action( 'wp_ajax_pv_vote', [ __CLASS__, 'handle_vote' ] );
		add_action( 'wp_ajax_nopriv_pv_vote', [ __CLASS__, 'handle_vote' ] );
	}

	public static function handle_vote() {
		check_ajax_referer( 'pv_vote_nonce', 'nonce' );

		$poll_id = isset( $_POST['poll_id'] ) ? intval( $_POST['poll_id'] ) : 0;
		$index   = isset( $_POST['option_index'] ) ? intval( $_POST['option_index'] ) : -1;

		$post = get_post( $poll_id );
		if ( ! $post || 'pv_poll' !== $post->post_type || 'publish' !== $post->post_status ) {
			wp_send_json_error( [ 'message' => __( 'Poll not found.', 'poll-verdict' ) ] );
		}

		// Respect end date if set.
		$end_ms = PV_Render::get_end_timestamp_ms( $poll_id );
		if ( $end_ms > 0 && $end_ms < ( time() * 1000 ) ) {
			$data = PV_Render::get_options_data( $poll_id );
			wp_send_json_error( [
				'message' => __( 'Voting has closed for this poll.', 'poll-verdict' ),
				'closed'  => true,
				'options' => $data['options'],
				'total'   => $data['total'],
			] );
		}

		$cookie_name = 'pv_voted_' . $poll_id;
		$already_voted = isset( $_COOKIE[ $cookie_name ] ) && '' !== $_COOKIE[ $cookie_name ];

		$options = get_post_meta( $poll_id, '_pv_options', true );
		if ( ! is_array( $options ) || empty( $options ) ) {
			wp_send_json_error( [ 'message' => __( 'This poll has no options.', 'poll-verdict' ) ] );
		}

		if ( $already_voted ) {
			$data = PV_Render::get_options_data( $poll_id );
			wp_send_json_success( [
				'already_voted' => true,
				'message'       => __( 'You already voted on this poll.', 'poll-verdict' ),
				'options'       => $data['options'],
				'total'         => $data['total'],
			] );
		}

		if ( $index < 0 || ! isset( $options[ $index ] ) ) {
			wp_send_json_error( [ 'message' => __( 'Invalid option.', 'poll-verdict' ) ] );
		}

		$options[ $index ]['votes'] = intval( $options[ $index ]['votes'] ) + 1;
		update_post_meta( $poll_id, '_pv_options', $options );

		// Remember this vote for a year so the visitor can't vote again.
		setcookie( $cookie_name, (string) $index, time() + YEAR_IN_SECONDS, COOKIEPATH ?: '/', COOKIE_DOMAIN );

		$data = PV_Render::get_options_data( $poll_id );
		wp_send_json_success( [
			'already_voted' => false,
			'message'       => __( 'Thanks for voting!', 'poll-verdict' ),
			'voted_index'   => $index,
			'options'       => $data['options'],
			'total'         => $data['total'],
		] );
	}
}
