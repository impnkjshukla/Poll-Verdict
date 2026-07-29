<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class PV_Render {

	/**
	 * Fetch normalized option data with vote counts + percentages.
	 */
	public static function get_options_data( $post_id ) {
		$options = get_post_meta( $post_id, '_pv_options', true );
		if ( ! is_array( $options ) || empty( $options ) ) {
			$options = [
				[ 'label' => 'Yes', 'votes' => 0 ],
				[ 'label' => 'No', 'votes' => 0 ],
			];
		}
		$total = 0;
		foreach ( $options as $opt ) {
			$total += intval( $opt['votes'] );
		}
		foreach ( $options as $i => $opt ) {
			$options[ $i ]['percent'] = $total > 0 ? round( ( intval( $opt['votes'] ) / $total ) * 100 ) : 0;
		}
		return [ 'options' => $options, 'total' => $total ];
	}

	public static function is_yes_no( $options ) {
		if ( count( $options ) !== 2 ) return false;
		$labels = array_map( function( $o ) {
			return strtolower( trim( $o['label'] ) );
		}, $options );
		sort( $labels );
		return $labels === [ 'no', 'yes' ];
	}

	/**
	 * Resolve which image (if any) to show above a poll's options.
	 * Priority: per-poll custom image -> featured image -> site-wide
	 * fallback image (Polls > Settings) -> default inline SVG scale icon.
	 */
	public static function get_poll_image_html( $post_id ) {
		$custom_id = intval( get_post_meta( $post_id, '_pv_poll_image_id', true ) );
		if ( $custom_id ) {
			$img = wp_get_attachment_image( $custom_id, 'medium', false, [ 'class' => 'pv-custom-image' ] );
			if ( $img ) return $img;
		}

		if ( has_post_thumbnail( $post_id ) ) {
			$img = get_the_post_thumbnail( $post_id, 'medium', [ 'class' => 'pv-custom-image' ] );
			if ( $img ) return $img;
		}

		if ( class_exists( 'PV_Settings' ) ) {
			$fallback_id = intval( PV_Settings::get_option( 'fallback_image_id', 0 ) );
			if ( $fallback_id ) {
				$img = wp_get_attachment_image( $fallback_id, 'medium', false, [ 'class' => 'pv-custom-image' ] );
				if ( $img ) return $img;
			}
		}

		return self::scale_icon_svg();
	}

	public static function get_end_timestamp_ms( $post_id ) {
		$raw = get_post_meta( $post_id, '_pv_end_date', true );
		if ( empty( $raw ) ) return 0;
		try {
			$dt = date_create( $raw, function_exists( 'wp_timezone' ) ? wp_timezone() : null );
			if ( ! $dt ) return 0;
			return $dt->getTimestamp() * 1000;
		} catch ( Exception $e ) {
			return 0;
		}
	}

	/**
	 * Renders a single poll card. Returns HTML.
	 */
	public static function render( $post_id, $atts = [] ) {
		$post = get_post( $post_id );
		if ( ! $post || 'pv_poll' !== $post->post_type ) return '';

		$data          = self::get_options_data( $post_id );
		$options       = $data['options'];
		$total         = $data['total'];
		$yes_no_style  = self::is_yes_no( $options );
		$badge_text    = get_post_meta( $post_id, '_pv_badge_text', true ) ?: __( 'CURRENT VERDICT', 'poll-verdict' );
		$vote_btn_text = get_post_meta( $post_id, '_pv_vote_button_text', true ) ?: __( 'VOTE NOW', 'poll-verdict' );
		$show_sidebar  = get_post_meta( $post_id, '_pv_show_sidebar', true );
		$show_sidebar  = ( '' === $show_sidebar || '1' === $show_sidebar );
		$end_ms        = self::get_end_timestamp_ms( $post_id );
		$description   = apply_filters( 'the_content', $post->post_content );

		$instance = 'pv-' . $post_id . '-' . wp_generate_password( 5, false, false );
		$voted_cookie = isset( $_COOKIE[ 'pv_voted_' . $post_id ] ) ? sanitize_text_field( $_COOKIE[ 'pv_voted_' . $post_id ] ) : '';
		$already_voted = ( '' !== $voted_cookie );

		ob_start();
		?>
		<div class="pv-poll-card <?php echo $yes_no_style ? 'pv-yesno' : 'pv-multi'; ?>"
			 id="<?php echo esc_attr( $instance ); ?>"
			 data-poll-id="<?php echo intval( $post_id ); ?>"
			 data-end="<?php echo intval( $end_ms ); ?>"
			 data-voted="<?php echo $already_voted ? '1' : '0'; ?>">

			<div class="pv-main-panel">
				<?php if ( $badge_text ) : ?>
					<div class="pv-badge"><?php echo esc_html( $badge_text ); ?></div>
				<?php endif; ?>

				<h3 class="pv-title"><?php echo esc_html( get_the_title( $post_id ) ); ?></h3>

				<?php if ( ! empty( $description ) ) : ?>
					<div class="pv-description"><?php echo wp_kses_post( $description ); ?></div>
				<?php endif; ?>

				<div class="pv-image">
					<?php echo self::get_poll_image_html( $post_id ); ?>
				</div>

				<div class="pv-you-decide">
					<span class="pv-arrow">&larr;</span> <?php esc_html_e( 'YOU DECIDE', 'poll-verdict' ); ?> <span class="pv-arrow">&rarr;</span>
				</div>

				<div class="pv-options <?php echo $yes_no_style ? 'pv-options-yesno' : 'pv-options-list'; ?>">
					<?php foreach ( $options as $i => $opt ) :
						$is_yes = $yes_no_style && 0 === strcasecmp( trim( $opt['label'] ), 'yes' );
						$is_no  = $yes_no_style && 0 === strcasecmp( trim( $opt['label'] ), 'no' );
						$extra_class = $is_yes ? 'pv-opt-yes' : ( $is_no ? 'pv-opt-no' : '' );
						$selected = ( $already_voted && (string) $i === (string) $voted_cookie ) ? 'pv-selected' : '';
					?>
						<button type="button" class="pv-option-btn <?php echo esc_attr( $extra_class . ' ' . $selected ); ?>" data-index="<?php echo intval( $i ); ?>">
							<?php if ( $is_yes ) : ?>
								<?php echo self::thumb_up_svg(); ?>
							<?php elseif ( $is_no ) : ?>
								<?php echo self::thumb_down_svg(); ?>
							<?php endif; ?>
							<span class="pv-opt-label"><?php echo esc_html( $opt['label'] ); ?></span>
						</button>
					<?php endforeach; ?>
				</div>

				<div class="pv-inline-results" style="<?php echo $show_sidebar ? 'display:none;' : ''; ?>">
					<?php echo self::results_markup( $options, $total ); ?>
				</div>

				<button type="button" class="pv-vote-now-btn" <?php disabled( $already_voted ); ?>>
					<?php echo self::gavel_svg(); ?>
					<span class="pv-vote-btn-text"><?php echo esc_html( $already_voted ? __( 'VOTE RECORDED', 'poll-verdict' ) : $vote_btn_text ); ?></span>
				</button>
				<p class="pv-message" aria-live="polite"></p>
			</div>

			<?php if ( $show_sidebar ) : ?>
			<div class="pv-side-panel">
				<?php if ( $end_ms > 0 ) : ?>
				<div class="pv-countdown-wrap">
					<div class="pv-countdown-label"><?php esc_html_e( 'VOTING ENDS IN', 'poll-verdict' ); ?></div>
					<div class="pv-countdown" data-role="countdown">
						<div class="pv-cd-box"><span class="pv-cd-num" data-unit="days">00</span><span class="pv-cd-unit"><?php esc_html_e( 'DAYS', 'poll-verdict' ); ?></span></div>
						<div class="pv-cd-box"><span class="pv-cd-num" data-unit="hours">00</span><span class="pv-cd-unit"><?php esc_html_e( 'HOURS', 'poll-verdict' ); ?></span></div>
						<div class="pv-cd-box"><span class="pv-cd-num" data-unit="mins">00</span><span class="pv-cd-unit"><?php esc_html_e( 'MINS', 'poll-verdict' ); ?></span></div>
						<div class="pv-cd-box"><span class="pv-cd-num" data-unit="secs">00</span><span class="pv-cd-unit"><?php esc_html_e( 'SECS', 'poll-verdict' ); ?></span></div>
					</div>
				</div>
				<hr class="pv-divider">
				<?php endif; ?>

				<div class="pv-live-label">
					<span class="pv-live-dot"></span> <?php esc_html_e( 'LIVE RESULTS', 'poll-verdict' ); ?>
				</div>
				<div class="pv-live-updated"><?php esc_html_e( 'Updated live', 'poll-verdict' ); ?></div>

				<div class="pv-results-sidebar" data-role="results">
					<?php echo self::results_markup( $options, $total ); ?>
				</div>

				<hr class="pv-divider">
				<div class="pv-total-votes">
					<?php echo self::people_svg(); ?>
					<span><?php esc_html_e( 'Total Votes', 'poll-verdict' ); ?></span>
					<strong data-role="total-votes"><?php echo esc_html( number_format_i18n( $total ) ); ?></strong>
				</div>
			</div>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Shared markup for a set of result bars, used both in the sidebar
	 * and as an inline fallback when the sidebar is disabled.
	 */
	public static function results_markup( $options, $total ) {
		ob_start();
		foreach ( $options as $i => $opt ) :
			$is_yes = 0 === strcasecmp( trim( $opt['label'] ), 'yes' );
			$bar_class = $is_yes ? 'pv-bar-fill-pos' : 'pv-bar-fill-neg';
			if ( ! in_array( strtolower( trim( $opt['label'] ) ), [ 'yes', 'no' ], true ) ) {
				$bar_class = 'pv-bar-fill-neutral pv-bar-color-' . ( $i % 5 );
			}
			?>
			<div class="pv-result-row" data-index="<?php echo intval( $i ); ?>">
				<div class="pv-result-top">
					<span class="pv-result-label"><?php echo esc_html( $opt['label'] ); ?></span>
					<span class="pv-result-percent"><span data-role="percent"><?php echo intval( $opt['percent'] ); ?></span>%</span>
				</div>
				<div class="pv-bar-track">
					<div class="pv-bar-fill <?php echo esc_attr( $bar_class ); ?>" style="width:<?php echo intval( $opt['percent'] ); ?>%"></div>
				</div>
				<div class="pv-result-count"><span data-role="votes"><?php echo esc_html( number_format_i18n( $opt['votes'] ) ); ?></span> <?php esc_html_e( 'Votes', 'poll-verdict' ); ?></div>
			</div>
		<?php endforeach;
		return ob_get_clean();
	}

	/* ---------------- Inline SVG icon helpers (no external image deps) ---------------- */

	public static function scale_icon_svg() {
		return '<svg viewBox="0 0 64 64" class="pv-scale-svg" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
			<circle cx="32" cy="8" r="3" fill="currentColor"/>
			<rect x="30.5" y="10" width="3" height="34" fill="currentColor"/>
			<rect x="10" y="14" width="44" height="3" rx="1.5" fill="currentColor"/>
			<rect x="20" y="44" width="24" height="3" rx="1.5" fill="currentColor"/>
			<rect x="24" y="47" width="16" height="4" rx="1" fill="currentColor"/>
			<path d="M10 17 L4 30 a8 8 0 0 0 12 0 Z" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linejoin="round"/>
			<path d="M54 17 L48 30 a8 8 0 0 0 12 0 Z" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linejoin="round"/>
		</svg>';
	}

	public static function thumb_up_svg() {
		return '<svg viewBox="0 0 24 24" class="pv-icon" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path fill="currentColor" d="M2 21h3V9H2v12zM22 10c0-1.1-.9-2-2-2h-6.31l.95-4.57.03-.32c0-.41-.17-.79-.44-1.06L12.17 1 6.59 6.59C6.22 6.95 6 7.45 6 8v11c0 1.1.9 2 2 2h9c.83 0 1.54-.5 1.84-1.22l3.02-7.05c.09-.23.14-.47.14-.73v-2z"/></svg>';
	}

	public static function thumb_down_svg() {
		return '<svg viewBox="0 0 24 24" class="pv-icon" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path fill="currentColor" d="M22 3h-3v12h3V3zM2 14c0 1.1.9 2 2 2h6.31l-.95 4.57-.03.32c0 .41.17.79.44 1.06L11.83 23l5.58-5.59c.37-.36.59-.86.59-1.41V5c0-1.1-.9-2-2-2H7c-.83 0-1.54.5-1.84 1.22L2.14 11.27C2.05 11.5 2 11.74 2 12v2z"/></svg>';
	}

	public static function gavel_svg() {
		return '<svg viewBox="0 0 24 24" class="pv-icon" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path fill="currentColor" d="M2 22h9v-1.5H2V22zm7.34-14.83l1.41-1.41 8.49 8.49-1.41 1.41zM12.87 4.6l1.41-1.41 3.54 3.54-1.41 1.41zm-8.72 12.02l4.24-4.24 3.54 3.54-4.24 4.24z"/></svg>';
	}

	public static function people_svg() {
		return '<svg viewBox="0 0 24 24" class="pv-icon-sm" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path fill="currentColor" d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5C15 14.17 10.33 13 8 13zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>';
	}
}
