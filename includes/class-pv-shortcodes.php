<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class PV_Shortcodes {

	public static function init() {
		add_shortcode( 'poll_verdict', [ __CLASS__, 'smart_shortcode' ] );
		add_shortcode( 'poll_verdict_carousel', [ __CLASS__, 'carousel_shortcode' ] );
	}

	/**
	 * Query a list of poll post IDs based on shared attributes.
	 */
	public static function query_poll_ids( $atts ) {
		if ( ! empty( $atts['ids'] ) ) {
			$ids = array_map( 'intval', array_filter( array_map( 'trim', explode( ',', $atts['ids'] ) ) ) );
			// Preserve the order the user specified.
			return $ids;
		}

		$args = [
			'post_type'      => 'pv_poll',
			'post_status'    => 'publish',
			'posts_per_page' => ! empty( $atts['limit'] ) ? intval( $atts['limit'] ) : -1,
			'orderby'        => ! empty( $atts['order'] ) ? sanitize_text_field( $atts['order'] ) : 'date',
			'order'          => ! empty( $atts['sort'] ) ? sanitize_text_field( $atts['sort'] ) : 'DESC',
			'fields'         => 'ids',
		];
		$q = new WP_Query( $args );
		return $q->posts;
	}

	/**
	 * [poll_verdict] or [poll_verdict id="123"]
	 * Smart behaviour: a specific id always renders single. Otherwise, if more
	 * than one poll is found it automatically renders as a carousel.
	 */
	public static function smart_shortcode( $atts ) {
		$atts = shortcode_atts( [
			'id'    => '',
			'ids'   => '',
			'limit' => 5,
			'order' => 'date',
			'sort'  => 'DESC',
		], $atts, 'poll_verdict' );

		if ( ! empty( $atts['id'] ) ) {
			return self::render_single_wrapped( intval( $atts['id'] ) );
		}

		$ids = self::query_poll_ids( $atts );
		if ( empty( $ids ) ) {
			return '<p class="pv-empty">' . esc_html__( 'No polls found.', 'poll-verdict' ) . '</p>';
		}
		if ( 1 === count( $ids ) ) {
			return self::render_single_wrapped( $ids[0] );
		}
		return self::render_carousel( $ids );
	}

	/**
	 * [poll_verdict_carousel ids="1,2,3" limit="5"]
	 * Always forces carousel mode, even for a single poll.
	 */
	public static function carousel_shortcode( $atts ) {
		$atts = shortcode_atts( [
			'ids'          => '',
			'limit'        => 5,
			'order'        => 'date',
			'sort'         => 'DESC',
			'autoplay'     => 'no',
			'interval'     => 6000,
			'show_arrows'  => 'yes',
			'show_dots'    => 'yes',
		], $atts, 'poll_verdict_carousel' );

		$ids = self::query_poll_ids( $atts );
		if ( empty( $ids ) ) {
			return '<p class="pv-empty">' . esc_html__( 'No polls found.', 'poll-verdict' ) . '</p>';
		}
		return self::render_carousel( $ids, $atts );
	}

	public static function render_single_wrapped( $id ) {
		wp_enqueue_style( 'pv-style' );
		wp_enqueue_script( 'pv-script' );
		$html = PV_Render::render( $id );
		if ( empty( $html ) ) {
			return '<p class="pv-empty">' . esc_html__( 'Poll not found.', 'poll-verdict' ) . '</p>';
		}
		return '<div class="pv-wrapper pv-single-wrapper">' . $html . '</div>';
	}

	public static function render_carousel( $ids, $atts = [] ) {
		wp_enqueue_style( 'pv-style' );
		wp_enqueue_script( 'pv-script' );

		$autoplay    = ! empty( $atts['autoplay'] ) && 'yes' === $atts['autoplay'];
		$interval    = ! empty( $atts['interval'] ) ? intval( $atts['interval'] ) : 6000;
		$show_arrows = empty( $atts['show_arrows'] ) || 'yes' === $atts['show_arrows'];
		$show_dots   = empty( $atts['show_dots'] ) || 'yes' === $atts['show_dots'];

		$slides = [];
		foreach ( $ids as $id ) {
			$html = PV_Render::render( $id );
			if ( ! empty( $html ) ) $slides[] = $html;
		}
		if ( empty( $slides ) ) {
			return '<p class="pv-empty">' . esc_html__( 'No polls found.', 'poll-verdict' ) . '</p>';
		}

		ob_start();
		?>
		<div class="pv-wrapper pv-carousel"
			 data-autoplay="<?php echo $autoplay ? '1' : '0'; ?>"
			 data-interval="<?php echo intval( $interval ); ?>">
			<div class="pv-carousel-track" data-role="track">
				<?php foreach ( $slides as $slide ) : ?>
					<div class="pv-carousel-slide"><?php echo $slide; ?></div>
				<?php endforeach; ?>
			</div>

			<?php if ( $show_arrows && count( $slides ) > 1 ) : ?>
				<button type="button" class="pv-carousel-arrow pv-prev" aria-label="<?php esc_attr_e( 'Previous poll', 'poll-verdict' ); ?>">&#10094;</button>
				<button type="button" class="pv-carousel-arrow pv-next" aria-label="<?php esc_attr_e( 'Next poll', 'poll-verdict' ); ?>">&#10095;</button>
			<?php endif; ?>

			<?php if ( $show_dots && count( $slides ) > 1 ) : ?>
				<div class="pv-carousel-dots" data-role="dots">
					<?php foreach ( $slides as $i => $slide ) : ?>
						<button type="button" class="pv-dot <?php echo 0 === $i ? 'pv-dot-active' : ''; ?>" data-slide="<?php echo intval( $i ); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Go to poll %d', 'poll-verdict' ), $i + 1 ) ); ?>"></button>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}
}
