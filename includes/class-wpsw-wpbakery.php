<?php
/**
 * The "Scroll Words" element in WPBakery: pick one of the texts, with "Edit text" and "Add text"
 * buttons under the list (as WP Swiper's element). Listed only for roles with "WPBakery element".
 *
 * @package WP_Scroll_Words
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPSW_WPBakery {

	// The buttons under the Text list (a param type of its own; saves nothing in the shortcode).
	const EDIT_PARAM = 'wpsw_edit';

	// The Text list again (wpbakery.js, once back from adding one).
	const LIST_ACTION = 'wpsw_vc_texts';

	public static function init() {
		add_action( 'vc_before_init', array( __CLASS__, 'map' ) );
		add_action( 'wp_ajax_' . self::LIST_ACTION, array( __CLASS__, 'ajax_texts' ) );
	}

	public static function map() {
		// Not an element for roles without the right. Pages still show the texts (the shortcode's own handler).
		if ( ! WPSW_Access::can( 'wpbakery' ) ) {
			return;
		}
		if ( function_exists( 'vc_add_shortcode_param' ) ) {
			vc_add_shortcode_param( self::EDIT_PARAM, array( __CLASS__, 'edit_param' ), WPSW_URL . 'assets/js/wpbakery.js?ver=' . rawurlencode( wpsw_asset( 'assets/js/wpbakery.js' ) ) );
		}
		if ( function_exists( 'vc_lean_map' ) ) {
			// Settings (and the texts query) load only when the editor needs them.
			vc_lean_map( WPSW_Render::SHORTCODE, array( __CLASS__, 'settings' ) );
		}
	}

	/**
	 * Woodmart's own "Theme elements" tab when present, otherwise WPBakery's "Content".
	 */
	private static function category() {
		if ( function_exists( 'woodmart_get_tab_title_category_for_wpb' ) ) {
			return woodmart_get_tab_title_category_for_wpb( esc_html__( 'Theme elements', 'woodmart' ) ); // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch -- the theme's own tab name.
		}
		return __( 'Content', 'wp-scroll-words' );
	}

	public static function settings() {
		return array(
			'name'        => __( 'Scroll Words', 'wp-scroll-words' ),
			'base'        => WPSW_Render::SHORTCODE,
			'icon'        => WPSW_URL . 'assets/images/wpbakery-icon.svg',
			'category'    => self::category(),
			'description' => __( 'Words light up as you scroll', 'wp-scroll-words' ),
			'params'      => array(
				array(
					'type'        => 'dropdown',
					'heading'     => __( 'Text', 'wp-scroll-words' ),
					'param_name'  => 'id',
					'value'       => self::options(),
					'admin_label' => true,
					'description' => sprintf(
						/* translators: %s: link to the texts screen */
						__( 'Create and edit texts under %s.', 'wp-scroll-words' ),
						'<a href="' . esc_url( admin_url( 'edit.php?post_type=' . WPSW_POST_TYPE ) ) . '" target="_blank">WP Scroll Words</a>'
					),
				),
				array(
					'type'       => self::EDIT_PARAM,
					'heading'    => '',
					'param_name' => self::EDIT_PARAM,
				),
				array(
					'type'       => 'textfield',
					'heading'    => __( 'Extra class name', 'wp-scroll-words' ),
					'param_name' => 'el_class',
				),
			),
		);
	}

	/**
	 * "Edit text" (wpbakery.js points it at the text chosen, hidden while none is) and "Add text"
	 * (for those who may add). Both open in a new tab. Its field is empty: nothing in the shortcode.
	 */
	public static function edit_param( $settings, $value ) {
		unset( $value );
		$name = isset( $settings['param_name'] ) ? $settings['param_name'] : self::EDIT_PARAM;
		$add  = '';
		if ( WPSW_Access::can( 'create' ) ) {
			$add = sprintf(
				' <a class="button wpsw-vc-edit__add" href="%1$s" target="_blank" rel="noopener">%2$s</a>',
				esc_url( admin_url( 'post-new.php?post_type=' . WPSW_POST_TYPE ) ),
				esc_html__( 'Add text', 'wp-scroll-words' )
			);
		}
		$list = add_query_arg(
			array(
				'action'   => self::LIST_ACTION,
				'_wpnonce' => wp_create_nonce( self::LIST_ACTION ),
			),
			admin_url( 'admin-ajax.php' )
		);
		return sprintf(
			'<div class="wpsw-vc-edit" data-url="%1$s" data-list="%2$s"><a class="button wpsw-vc-edit__link" href="#" target="_blank" rel="noopener" hidden>%3$s</a>%4$s<p class="description wpsw-vc-edit__note" style="margin:6px 0 0">%5$s</p><input type="hidden" name="%6$s" class="wpb_vc_param_value %6$s %7$s" value=""></div>',
			esc_url( admin_url( 'post.php?action=edit&post=' ) ),
			esc_url( $list ),
			esc_html__( 'Edit text', 'wp-scroll-words' ),
			$add, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped where it is built.
			esc_html__( 'Opens in a new tab. Back here, a text published there is in the list, chosen.', 'wp-scroll-words' ),
			esc_attr( $name ),
			esc_attr( self::EDIT_PARAM )
		);
	}

	/**
	 * The published texts by title: [ [ 'id' => int, 'label' => string ] ].
	 */
	public static function texts() {
		$posts = get_posts(
			array(
				'post_type'      => WPSW_POST_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);
		$list  = array();
		foreach ( $posts as $post ) {
			$title  = '' !== $post->post_title ? $post->post_title : __( '(no title)', 'wp-scroll-words' );
			$list[] = array(
				'id'    => (int) $post->ID,
				'label' => sprintf( '%s (#%d)', $title, $post->ID ),
			);
		}
		return $list;
	}

	private static function options() {
		$options = array( __( '— Choose a text —', 'wp-scroll-words' ) => '' );
		foreach ( self::texts() as $text ) {
			$options[ $text['label'] ] = (string) $text['id'];
		}
		return $options;
	}

	/**
	 * The Text list again, for wpbakery.js (with the nonce edit_param() gave).
	 */
	public static function ajax_texts() {
		check_ajax_referer( self::LIST_ACTION );
		if ( ! WPSW_Access::can( 'wpbakery' ) ) {
			wp_send_json_error( null, 403 );
		}
		wp_send_json_success( self::texts() );
	}
}
