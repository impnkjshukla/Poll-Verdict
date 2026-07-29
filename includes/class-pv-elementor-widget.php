<?php
if ( ! defined( 'ABSPATH' ) ) exit;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Box_Shadow;

if ( ! class_exists( '\Elementor\Widget_Base' ) ) return;

class PV_Elementor_Widget extends Widget_Base {

	public function get_name() {
		return 'pv_poll_verdict';
	}

	public function get_title() {
		return __( 'Poll Verdict', 'poll-verdict' );
	}

	public function get_icon() {
		return 'eicon-post-list';
	}

	public function get_categories() {
		return [ 'poll-verdict' ];
	}

	public function get_keywords() {
		return [ 'poll', 'vote', 'survey', 'verdict', 'carousel' ];
	}

	public function get_script_depends() {
		return [ 'pv-script' ];
	}

	public function get_style_depends() {
		return [ 'pv-style' ];
	}

	protected function get_polls_list() {
		$polls = get_posts( [
			'post_type'      => 'pv_poll',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'date',
			'order'          => 'DESC',
		] );
		$options = [];
		foreach ( $polls as $p ) {
			$options[ $p->ID ] = $p->post_title;
		}
		return $options;
	}

	protected function register_controls() {

		$this->start_controls_section( 'pv_content_section', [
			'label' => __( 'Poll Selection', 'poll-verdict' ),
			'tab'   => Controls_Manager::TAB_CONTENT,
		] );

		$this->add_control( 'display_mode', [
			'label'   => __( 'Display Mode', 'poll-verdict' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'auto',
			'options' => [
				'auto'     => __( 'Auto (single poll shows once, 2+ shows carousel)', 'poll-verdict' ),
				'single'   => __( 'Single Poll', 'poll-verdict' ),
				'carousel' => __( 'Carousel (force)', 'poll-verdict' ),
			],
		] );

		$this->add_control( 'single_poll', [
			'label'     => __( 'Select Poll', 'poll-verdict' ),
			'type'      => Controls_Manager::SELECT2,
			'options'   => $this->get_polls_list(),
			'condition' => [ 'display_mode' => 'single' ],
		] );

		$this->add_control( 'selected_polls', [
			'label'       => __( 'Select Polls (leave empty for all/latest)', 'poll-verdict' ),
			'type'        => Controls_Manager::SELECT2,
			'multiple'    => true,
			'options'     => $this->get_polls_list(),
			'condition'   => [ 'display_mode' => [ 'auto', 'carousel' ] ],
		] );

		$this->add_control( 'limit', [
			'label'     => __( 'Max Number of Polls', 'poll-verdict' ),
			'type'      => Controls_Manager::NUMBER,
			'default'   => 5,
			'min'       => 1,
			'max'       => 20,
			'condition' => [ 'display_mode' => [ 'auto', 'carousel' ] ],
		] );

		$this->end_controls_section();

		$this->start_controls_section( 'pv_carousel_section', [
			'label'     => __( 'Carousel Settings', 'poll-verdict' ),
			'tab'       => Controls_Manager::TAB_CONTENT,
			'condition' => [ 'display_mode' => [ 'auto', 'carousel' ] ],
		] );

		$this->add_control( 'show_arrows', [
			'label'        => __( 'Show Arrows', 'poll-verdict' ),
			'type'         => Controls_Manager::SWITCHER,
			'default'      => 'yes',
			'label_on'     => __( 'Show', 'poll-verdict' ),
			'label_off'    => __( 'Hide', 'poll-verdict' ),
			'return_value' => 'yes',
		] );

		$this->add_control( 'show_dots', [
			'label'        => __( 'Show Dots', 'poll-verdict' ),
			'type'         => Controls_Manager::SWITCHER,
			'default'      => 'yes',
			'label_on'     => __( 'Show', 'poll-verdict' ),
			'label_off'    => __( 'Hide', 'poll-verdict' ),
			'return_value' => 'yes',
		] );

		$this->add_control( 'autoplay', [
			'label'        => __( 'Autoplay', 'poll-verdict' ),
			'type'         => Controls_Manager::SWITCHER,
			'default'      => '',
			'label_on'     => __( 'Yes', 'poll-verdict' ),
			'label_off'    => __( 'No', 'poll-verdict' ),
			'return_value' => 'yes',
		] );

		$this->add_control( 'interval', [
			'label'     => __( 'Autoplay Interval (ms)', 'poll-verdict' ),
			'type'      => Controls_Manager::NUMBER,
			'default'   => 6000,
			'condition' => [ 'autoplay' => 'yes' ],
		] );

		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$mode = ! empty( $settings['display_mode'] ) ? $settings['display_mode'] : 'auto';

		if ( 'single' === $mode ) {
			if ( empty( $settings['single_poll'] ) ) {
				echo '<p class="pv-empty">' . esc_html__( 'Please select a poll.', 'poll-verdict' ) . '</p>';
				return;
			}
			echo PV_Shortcodes::render_single_wrapped( intval( $settings['single_poll'] ) );
			return;
		}

		$atts = [
			'ids'         => ! empty( $settings['selected_polls'] ) ? implode( ',', (array) $settings['selected_polls'] ) : '',
			'limit'       => ! empty( $settings['limit'] ) ? intval( $settings['limit'] ) : 5,
			'show_arrows' => ! empty( $settings['show_arrows'] ) ? 'yes' : 'no',
			'show_dots'   => ! empty( $settings['show_dots'] ) ? 'yes' : 'no',
			'autoplay'    => ! empty( $settings['autoplay'] ) ? 'yes' : 'no',
			'interval'    => ! empty( $settings['interval'] ) ? intval( $settings['interval'] ) : 6000,
		];

		if ( 'carousel' === $mode ) {
			$ids = PV_Shortcodes::query_poll_ids( $atts );
			if ( empty( $ids ) ) {
				echo '<p class="pv-empty">' . esc_html__( 'No polls found.', 'poll-verdict' ) . '</p>';
				return;
			}
			echo PV_Shortcodes::render_carousel( $ids, $atts );
			return;
		}

		// auto mode
		$ids = PV_Shortcodes::query_poll_ids( $atts );
		if ( empty( $ids ) ) {
			echo '<p class="pv-empty">' . esc_html__( 'No polls found.', 'poll-verdict' ) . '</p>';
			return;
		}
		if ( 1 === count( $ids ) ) {
			echo PV_Shortcodes::render_single_wrapped( $ids[0] );
		} else {
			echo PV_Shortcodes::render_carousel( $ids, $atts );
		}
	}
}
