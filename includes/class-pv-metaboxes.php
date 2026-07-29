<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class PV_Metaboxes {

	public static function init() {
		add_action( 'add_meta_boxes', [ __CLASS__, 'add_boxes' ] );
		add_action( 'save_post_pv_poll', [ __CLASS__, 'save' ], 10, 2 );
	}

	public static function add_boxes() {
		add_meta_box(
			'pv_poll_settings',
			__( 'Poll Settings', 'poll-verdict' ),
			[ __CLASS__, 'render_settings_box' ],
			'pv_poll',
			'normal',
			'high'
		);
		add_meta_box(
			'pv_poll_options',
			__( 'Poll Options / Choices', 'poll-verdict' ),
			[ __CLASS__, 'render_options_box' ],
			'pv_poll',
			'normal',
			'high'
		);
		add_meta_box(
			'pv_poll_shortcode',
			__( 'Shortcode', 'poll-verdict' ),
			[ __CLASS__, 'render_shortcode_box' ],
			'pv_poll',
			'side',
			'default'
		);
	}

	public static function render_shortcode_box( $post ) {
		if ( $post->ID ) {
			echo '<p>' . esc_html__( 'Show this specific poll:', 'poll-verdict' ) . '</p>';
			echo '<input type="text" readonly onclick="this.select()" style="width:100%" value="[poll_verdict id=&quot;' . intval( $post->ID ) . '&quot;]">';
			echo '<p style="margin-top:10px;">' . esc_html__( 'Show all polls (auto single/carousel):', 'poll-verdict' ) . '</p>';
			echo '<input type="text" readonly onclick="this.select()" style="width:100%" value="[poll_verdict]">';
			echo '<p style="margin-top:10px;">' . esc_html__( 'Force a carousel of specific polls:', 'poll-verdict' ) . '</p>';
			echo '<input type="text" readonly onclick="this.select()" style="width:100%" value="[poll_verdict_carousel ids=&quot;' . intval( $post->ID ) . ',2,3&quot;]">';
		} else {
			echo '<p>' . esc_html__( 'Save/publish the poll to get its shortcode.', 'poll-verdict' ) . '</p>';
		}
	}

	public static function render_settings_box( $post ) {
		wp_nonce_field( 'pv_save_poll', 'pv_poll_nonce' );

		$badge_text   = get_post_meta( $post->ID, '_pv_badge_text', true );
		$vote_btn     = get_post_meta( $post->ID, '_pv_vote_button_text', true );
		$end_date_raw = get_post_meta( $post->ID, '_pv_end_date', true ); // stored as Y-m-d\TH:i
		$show_sidebar = get_post_meta( $post->ID, '_pv_show_sidebar', true );
		$show_sidebar = ( '' === $show_sidebar ) ? '1' : $show_sidebar;

		if ( '' === $badge_text ) $badge_text = __( 'CURRENT VERDICT', 'poll-verdict' );
		if ( '' === $vote_btn )   $vote_btn   = __( 'VOTE NOW', 'poll-verdict' );

		$image_id  = intval( get_post_meta( $post->ID, '_pv_poll_image_id', true ) );
		$image_url = $image_id ? wp_get_attachment_image_url( $image_id, 'medium' ) : '';
		?>
		<table class="form-table">
			<tr>
				<th><label for="pv_poll_image_id"><?php esc_html_e( 'Poll Image', 'poll-verdict' ); ?></label></th>
				<td>
					<div class="pv-image-field-wrap">
						<img id="pv_poll_image_preview" class="pv-image-preview" src="<?php echo esc_url( $image_url ); ?>" style="<?php echo $image_url ? '' : 'display:none;'; ?>" />
						<input type="hidden" id="pv_poll_image_id" name="pv_poll_image_id" value="<?php echo esc_attr( $image_id ); ?>">
						<p>
							<button type="button" class="button pv-media-upload" data-target="pv_poll_image_id" data-preview="pv_poll_image_preview"><?php esc_html_e( 'Upload Image', 'poll-verdict' ); ?></button>
							<button type="button" class="button pv-media-remove" data-target="pv_poll_image_id" data-preview="pv_poll_image_preview" style="<?php echo $image_id ? '' : 'display:none;'; ?>"><?php esc_html_e( 'Remove', 'poll-verdict' ); ?></button>
						</p>
						<p class="description"><?php esc_html_e( 'Optional. Shown above the options for this poll. If left empty the plugin uses the Featured Image, then the site-wide fallback image (Polls > Settings), then a default scale icon.', 'poll-verdict' ); ?></p>
					</div>
				</td>
			</tr>
			<tr>
				<th><label for="pv_badge_text"><?php esc_html_e( 'Badge Label', 'poll-verdict' ); ?></label></th>
				<td><input type="text" id="pv_badge_text" name="pv_badge_text" class="regular-text" value="<?php echo esc_attr( $badge_text ); ?>"></td>
			</tr>
			<tr>
				<th><label for="pv_vote_button_text"><?php esc_html_e( 'Vote Button Text', 'poll-verdict' ); ?></label></th>
				<td><input type="text" id="pv_vote_button_text" name="pv_vote_button_text" class="regular-text" value="<?php echo esc_attr( $vote_btn ); ?>"></td>
			</tr>
			<tr>
				<th><label for="pv_end_date"><?php esc_html_e( 'Voting Ends On', 'poll-verdict' ); ?></label></th>
				<td>
					<input type="datetime-local" id="pv_end_date" name="pv_end_date" value="<?php echo esc_attr( $end_date_raw ); ?>">
					<p class="description"><?php esc_html_e( 'Leave blank for a poll with no countdown / no end date.', 'poll-verdict' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="pv_show_sidebar"><?php esc_html_e( 'Show Results Sidebar', 'poll-verdict' ); ?></label></th>
				<td>
					<label><input type="checkbox" id="pv_show_sidebar" name="pv_show_sidebar" value="1" <?php checked( $show_sidebar, '1' ); ?>> <?php esc_html_e( 'Show the countdown + live results panel', 'poll-verdict' ); ?></label>
				</td>
			</tr>
			<tr>
				<th><label for="pv_reset_votes"><?php esc_html_e( 'Reset Votes', 'poll-verdict' ); ?></label></th>
				<td>
					<label><input type="checkbox" id="pv_reset_votes" name="pv_reset_votes" value="1"> <?php esc_html_e( 'Reset all vote counts to 0 when this poll is saved', 'poll-verdict' ); ?></label>
				</td>
			</tr>
		</table>
		<?php
	}

	public static function render_options_box( $post ) {
		$options = get_post_meta( $post->ID, '_pv_options', true );
		if ( ! is_array( $options ) || empty( $options ) ) {
			$options = [
				[ 'label' => 'Yes', 'votes' => 0 ],
				[ 'label' => 'No',  'votes' => 0 ],
			];
		}
		?>
		<div id="pv-options-wrap">
			<table class="widefat" id="pv-options-table">
				<thead>
					<tr>
						<th style="width:50%"><?php esc_html_e( 'Option Label', 'poll-verdict' ); ?></th>
						<th><?php esc_html_e( 'Current Votes', 'poll-verdict' ); ?></th>
						<th></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $options as $i => $opt ) : ?>
					<tr class="pv-option-row">
						<td>
							<input type="text" name="pv_options[label][]" class="widefat" value="<?php echo esc_attr( $opt['label'] ); ?>">
						</td>
						<td>
							<input type="hidden" name="pv_options[votes][]" value="<?php echo intval( $opt['votes'] ); ?>">
							<?php echo intval( $opt['votes'] ); ?>
						</td>
						<td><button type="button" class="button pv-remove-row">&times;</button></td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<p>
				<button type="button" class="button" id="pv-add-row"><?php esc_html_e( '+ Add Option', 'poll-verdict' ); ?></button>
			</p>
			<p class="description"><?php esc_html_e( 'Tip: exactly two options named "Yes" and "No" will automatically use the thumbs-up / thumbs-down verdict style shown in the preview.', 'poll-verdict' ); ?></p>
		</div>
		<template id="pv-row-template">
			<tr class="pv-option-row">
				<td><input type="text" name="pv_options[label][]" class="widefat" value=""></td>
				<td><input type="hidden" name="pv_options[votes][]" value="0">0</td>
				<td><button type="button" class="button pv-remove-row">&times;</button></td>
			</tr>
		</template>
		<?php
	}

	public static function save( $post_id, $post ) {
		if ( ! isset( $_POST['pv_poll_nonce'] ) || ! wp_verify_nonce( $_POST['pv_poll_nonce'], 'pv_save_poll' ) ) return;
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
		if ( ! current_user_can( 'edit_post', $post_id ) ) return;

		$image_id = isset( $_POST['pv_poll_image_id'] ) ? intval( $_POST['pv_poll_image_id'] ) : 0;
		update_post_meta( $post_id, '_pv_poll_image_id', $image_id );

		$badge = isset( $_POST['pv_badge_text'] ) ? sanitize_text_field( wp_unslash( $_POST['pv_badge_text'] ) ) : '';
		update_post_meta( $post_id, '_pv_badge_text', $badge );

		$vote_btn = isset( $_POST['pv_vote_button_text'] ) ? sanitize_text_field( wp_unslash( $_POST['pv_vote_button_text'] ) ) : '';
		update_post_meta( $post_id, '_pv_vote_button_text', $vote_btn );

		$end_date = isset( $_POST['pv_end_date'] ) ? sanitize_text_field( wp_unslash( $_POST['pv_end_date'] ) ) : '';
		update_post_meta( $post_id, '_pv_end_date', $end_date );

		$show_sidebar = isset( $_POST['pv_show_sidebar'] ) ? '1' : '0';
		update_post_meta( $post_id, '_pv_show_sidebar', $show_sidebar );

		$reset_votes = isset( $_POST['pv_reset_votes'] ) && '1' === $_POST['pv_reset_votes'];

		$options = [];
		if ( isset( $_POST['pv_options']['label'] ) && is_array( $_POST['pv_options']['label'] ) ) {
			$labels = wp_unslash( $_POST['pv_options']['label'] );
			$votes  = isset( $_POST['pv_options']['votes'] ) ? wp_unslash( $_POST['pv_options']['votes'] ) : [];
			foreach ( $labels as $i => $label ) {
				$label = sanitize_text_field( $label );
				if ( '' === $label ) continue;
				$vote_count = $reset_votes ? 0 : ( isset( $votes[ $i ] ) ? max( 0, intval( $votes[ $i ] ) ) : 0 );
				$options[] = [ 'label' => $label, 'votes' => $vote_count ];
			}
		}
		if ( empty( $options ) ) {
			$options = [
				[ 'label' => 'Yes', 'votes' => 0 ],
				[ 'label' => 'No',  'votes' => 0 ],
			];
		}
		update_post_meta( $post_id, '_pv_options', $options );
	}
}
