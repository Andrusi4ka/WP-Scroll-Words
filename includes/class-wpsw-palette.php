<?php
/**
 * The colors the color picker offers (as WP Banner's WPBN_Palette): the theme's colors,
 * then black, white and greys. Any other color can still be picked.
 *
 * @package WP_Scroll_Words
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPSW_Palette {

	// "#rrggbb" (lowercase) from "#rgb" or "#rrggbb"; '' for anything else.
	private static function hex( $value ) {
		$value = strtolower( trim( (string) $value ) );
		if ( preg_match( '/^#([0-9a-f])([0-9a-f])([0-9a-f])$/', $value, $m ) ) {
			$value = '#' . $m[1] . $m[1] . $m[2] . $m[2] . $m[3] . $m[3];
		}
		return preg_match( '/^#[0-9a-f]{6}$/', $value ) ? $value : '';
	}

	// Woodmart's colors (Theme Settings) and any theme's palette (theme.json).
	private static function theme_colors() {
		$colors = array();
		if ( 'woodmart' === get_template() ) {
			$options = get_option( 'xts-woodmart-options' );
			foreach ( array( 'primary-color' => 'Primary color', 'secondary-color' => 'Secondary color', 'link-color' => 'Link color' ) as $key => $name ) {
				$value = is_array( $options ) && isset( $options[ $key ] ) ? $options[ $key ] : '';
				if ( is_array( $value ) ) {
					$value = isset( $value['idle'] ) ? $value['idle'] : '';
				}
				$colors[] = array( 'color' => self::hex( is_scalar( $value ) ? $value : '' ), 'name' => $name );
			}
		}
		if ( function_exists( 'wp_get_global_settings' ) ) {
			$palette = wp_get_global_settings( array( 'color', 'palette', 'theme' ) );
			foreach ( is_array( $palette ) ? $palette : array() as $entry ) {
				if ( is_array( $entry ) && isset( $entry['color'] ) && is_scalar( $entry['color'] ) ) {
					$colors[] = array(
						'color' => self::hex( $entry['color'] ),
						'name'  => isset( $entry['name'] ) && is_scalar( $entry['name'] ) ? sanitize_text_field( (string) $entry['name'] ) : '',
					);
				}
			}
		}
		return $colors;
	}

	/**
	 * The palette: the theme's colors, then the basic ones; each color once, a nameless one named by itself.
	 */
	public static function colors() {
		$basic = array(
			array( 'color' => '#000000', 'name' => 'Black' ),
			array( 'color' => '#444444', 'name' => 'Dark gray' ),
			array( 'color' => '#888888', 'name' => 'Gray' ),
			array( 'color' => '#cccccc', 'name' => 'Light gray' ),
			array( 'color' => '#ffffff', 'name' => 'White' ),
		);
		$all  = array();
		$seen = array();
		foreach ( array_merge( self::theme_colors(), $basic ) as $entry ) {
			if ( '' === $entry['color'] || isset( $seen[ $entry['color'] ] ) ) {
				continue;
			}
			$seen[ $entry['color'] ] = true;
			$all[]                   = array( 'color' => $entry['color'], 'name' => '' !== $entry['name'] ? $entry['name'] : $entry['color'] );
		}
		return $all;
	}
}
