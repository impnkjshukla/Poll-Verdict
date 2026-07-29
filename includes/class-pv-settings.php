<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class PV_Settings {

	const OPTION_KEY = 'pv_settings';

	public static function init() {
		add_action( 'admin_menu', [ __CLASS__, 'add_menu' ] );
		add_action( 'admin_init', [ __CLASS__, 'register_settings' ] );
	}

	public static function add_menu() {
		add_submenu_page(
			'edit.php?post_type=pv_poll',
			__( 'Poll Verdict Settings', 'poll-verdict' ),
			__( 'Settings', 'poll-verdict' ),
			'manage_options',
			'pv-poll-settings',
			[ __CLASS__, 'render_page' ]
		);
	}

	public static function register_settings() {
		register_setting( 'pv_settings_group', self::OPTION_KEY, [ __CLASS__, 'sanitize' ] );
	}

	public static function sanitize( $input ) {
		$out = [];
		$out['fallback_image_id'] = isset( $input['fallback_image_id'] ) ? intval( $input['fallback_image_id'] ) : 0;
		return $out;
	}

	public static function get_option( $key, $default = '' ) {
		$opts = get_option( self::OPTION_KEY, [] );
		return isset( $opts[ $key ] ) && '' !== $opts[ $key ] ? $opts[ $key ] : $default;
	}

	public static function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) return;

		$fallback_id  = intval( self::get_option( 'fallback_image_id', 0 ) );
		$fallback_url = $fallback_id ? wp_get_attachment_image_url( $fallback_id, 'medium' ) : '';
		?>
		<div class="wrap pv-settings-wrap">
			<h1><?php esc_html_e( 'Poll Verdict Settings', 'poll-verdict' ); ?></h1>
			<form method="post" action="options.php">
				<?php settings_fields( 'pv_settings_group' ); ?>
				<table class="form-table">
					<tr>
						<th scope="row"><?php esc_html_e( 'Fallback Poll Image', 'poll-verdict' ); ?></th>
						<td>
							<div class="pv-image-field-wrap">
								<img id="pv_fallback_image_preview" class="pv-image-preview" src="<?php echo esc_url( $fallback_url ); ?>" style="<?php echo $fallback_url ? '' : 'display:none;'; ?>" />
								<input type="hidden" id="pv_fallback_image_id" name="pv_settings[fallback_image_id]" value="<?php echo esc_attr( $fallback_id ); ?>">
								<p>
									<button type="button" class="button pv-media-upload" data-target="pv_fallback_image_id" data-preview="pv_fallback_image_preview"><?php esc_html_e( 'Upload Image', 'poll-verdict' ); ?></button>
									<button type="button" class="button pv-media-remove" data-target="pv_fallback_image_id" data-preview="pv_fallback_image_preview" style="<?php echo $fallback_id ? '' : 'display:none;'; ?>"><?php esc_html_e( 'Remove', 'poll-verdict' ); ?></button>
								</p>
								<p class="description">
									<?php esc_html_e( 'Shown on any poll that has no dedicated Poll Image and no Featured Image. If this is left empty too, a default scale icon is used.', 'poll-verdict' ); ?>
								</p>
							</div>
						</td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
}
