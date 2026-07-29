<?php
/**
 * Plugin Name: Poll Verdict
 * Plugin URI:  
 * Description: Create interactive "verdict" style polls with live results, a countdown timer, and an automatic carousel when you have more than one poll. Ships with shortcodes and a native Elementor widget.
 * Version:     1.0.0
 * Author:      Pankaj Shukla
 * Text Domain: poll-verdict
 * License:     GPL v2 or later
 */

if ( ! defined( 'ABSPATH' ) ) exit; // No direct access.

define( 'PV_VERSION', '1.0.0' );
define( 'PV_PATH', plugin_dir_path( __FILE__ ) );
define( 'PV_URL', plugin_dir_url( __FILE__ ) );
define( 'PV_BASENAME', plugin_basename( __FILE__ ) );

require_once PV_PATH . 'includes/class-pv-cpt.php';
require_once PV_PATH . 'includes/class-pv-settings.php';
require_once PV_PATH . 'includes/class-pv-metaboxes.php';
require_once PV_PATH . 'includes/class-pv-ajax.php';
require_once PV_PATH . 'includes/class-pv-render.php';
require_once PV_PATH . 'includes/class-pv-shortcodes.php';

/**
 * Core plugin bootstrap.
 */
final class Poll_Verdict_Plugin {

	public static function init() {
		register_activation_hook( __FILE__, [ __CLASS__, 'activate' ] );
		register_deactivation_hook( __FILE__, [ __CLASS__, 'deactivate' ] );

		add_action( 'init', [ __CLASS__, 'load_components' ] );
		add_action( 'wp_enqueue_scripts', [ __CLASS__, 'register_assets' ] );
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'admin_assets' ] );

		// Elementor integration (only if Elementor is active).
		add_action( 'elementor/widgets/register', [ __CLASS__, 'register_elementor_widget' ] );
		// Fallback for older Elementor versions.
		add_action( 'elementor/widgets/widgets_registered', [ __CLASS__, 'register_elementor_widget_legacy' ] );
		add_action( 'elementor/elements/categories_registered', [ __CLASS__, 'register_elementor_category' ] );

		add_filter( 'plugin_action_links_' . PV_BASENAME, [ __CLASS__, 'settings_link' ] );
	}

	public static function activate() {
		PV_CPT::register_post_type();
		flush_rewrite_rules();
	}

	public static function deactivate() {
		flush_rewrite_rules();
	}

	public static function load_components() {
		PV_CPT::register_post_type();
		PV_Settings::init();
		PV_Metaboxes::init();
		PV_Ajax::init();
		PV_Shortcodes::init();
	}

	public static function register_assets() {
		wp_register_style( 'pv-style', PV_URL . 'assets/css/poll-verdict.css', [], PV_VERSION );
		wp_register_script( 'pv-script', PV_URL . 'assets/js/poll-verdict.js', [], PV_VERSION, true );
		wp_localize_script( 'pv-script', 'PV_Data', [
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'pv_vote_nonce' ),
			'i18n'     => [
				'closed' => __( 'Voting Closed', 'poll-verdict' ),
				'thanks' => __( 'Thanks for voting!', 'poll-verdict' ),
			],
		] );
	}

	public static function admin_assets( $hook ) {
		global $post_type;
		$is_settings_page = ( 'pv_poll_page_pv-poll-settings' === $hook );
		if ( 'pv_poll' === $post_type || $is_settings_page ) {
			wp_enqueue_style( 'pv-admin-style', PV_URL . 'assets/css/poll-verdict-admin.css', [], PV_VERSION );
			wp_enqueue_script( 'pv-admin-script', PV_URL . 'assets/js/poll-verdict-admin.js', [ 'jquery' ], PV_VERSION, true );
			wp_enqueue_media();
		}
	}

	public static function register_elementor_category( $elements_manager ) {
		$elements_manager->add_category( 'poll-verdict', [
			'title' => __( 'Poll Verdict', 'poll-verdict' ),
			'icon'  => 'fa fa-balance-scale',
		] );
	}

	public static function register_elementor_widget( $widgets_manager ) {
		if ( ! class_exists( '\Elementor\Widget_Base' ) ) return;
		require_once PV_PATH . 'includes/class-pv-elementor-widget.php';
		$widgets_manager->register( new PV_Elementor_Widget() );
	}

	// Elementor < 3.5 registered widgets differently.
	public static function register_elementor_widget_legacy( $widgets_manager ) {
		if ( ! class_exists( '\Elementor\Widget_Base' ) ) return;
		if ( did_action( 'elementor/widgets/register' ) ) return; // already handled by the new hook
		require_once PV_PATH . 'includes/class-pv-elementor-widget.php';
		if ( method_exists( $widgets_manager, 'register_widget_type' ) ) {
			$widgets_manager->register_widget_type( new PV_Elementor_Widget() );
		}
	}

	public static function settings_link( $links ) {
		$url = admin_url( 'edit.php?post_type=pv_poll' );
		$links[] = '<a href="' . esc_url( $url ) . '">' . __( 'Polls', 'poll-verdict' ) . '</a>';
		return $links;
	}
}

Poll_Verdict_Plugin::init();
