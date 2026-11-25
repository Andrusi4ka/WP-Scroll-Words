<?php
/**
 * A text's settings: what is kept in its post meta and how it is cleaned.
 *
 * @package WP_Scroll_Words
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPSW_Settings {

	const META = '_wpsw_settings';
	const TAGS = array( 'p', 'div', 'h2', 'h3' );

	// Font size per device (px): tablet / mobile empty follow the larger screen (as WP Banner's).
	const SIZES = array(
		'font_size'        => array( 72, 8, 400 ),
		'font_size_tablet' => array( null, 8, 400 ),
		'font_size_mobile' => array( 34, 8, 250 ),
	);

	/**
	 * Empty font and color mean the page's own.
	 */
	public static function defaults() {
		return array(
			'text'             => '',
			'images'           => array(),
			'font_family'      => '',
			'font_weight'      => 400,
			'font_size'        => 72,
			'font_size_tablet' => null,
			'font_size_mobile' => 34,
			'color'            => '',
			'dim'              => '',
			'tag'              => 'p',
			'lightbox'         => false,
			'css_class'        => '',
		);
	}

	/**
	 * The settings of a text, merged with the defaults.
	 */
	public static function get( $post_id ) {
		$saved = get_post_meta( (int) $post_id, self::META, true );
		return self::sanitize( is_array( $saved ) ? $saved : array() );
	}

	/**
	 * $value unslashed (WPSW_Admin::save()). update_post_meta() unslashes what it is given, so it gets it slashed:
	 * a "\" in the text survives.
	 */
	public static function save( $post_id, array $value ) {
		$clean = self::sanitize( $value );
		// Only images: an ID typed into the hidden field that is not one is dropped.
		$clean['images'] = array_values( array_filter( $clean['images'], 'wp_attachment_is_image' ) );
		update_post_meta( (int) $post_id, self::META, wp_slash( $clean ) );
	}

	/**
	 * Cleans unslashed values; run on saving and on every read (get()).
	 */
	public static function sanitize( array $value ) {
		$clean = self::defaults();

		if ( isset( $value['text'] ) && is_scalar( $value['text'] ) ) {
			$clean['text'] = self::html( (string) $value['text'] );
		}

		if ( isset( $value['images'] ) ) {
			$ids             = is_array( $value['images'] ) ? $value['images'] : explode( ',', (string) $value['images'] );
			$clean['images'] = array_values( array_filter( array_map( 'absint', $ids ) ) );
		}

		if ( isset( $value['font_family'] ) && is_scalar( $value['font_family'] ) ) {
			// A font name, a stack or a theme variable (var(--wd-title-font)): nothing that could leave the CSS value.
			$clean['font_family'] = trim( preg_replace( '/[^A-Za-z0-9 ,\'"()_.-]/', '', (string) $value['font_family'] ) );
		}

		if ( isset( $value['font_weight'] ) && in_array( (int) $value['font_weight'], range( 100, 900, 100 ), true ) ) {
			$clean['font_weight'] = (int) $value['font_weight'];
		}

		foreach ( self::SIZES as $key => list( $default, $min, $max ) ) {
			if ( ! array_key_exists( $key, $value ) ) {
				continue;
			}
			$raw = is_scalar( $value[ $key ] ) ? trim( (string) $value[ $key ] ) : '';
			if ( is_numeric( $raw ) ) {
				$clean[ $key ] = max( $min, min( $max, (int) round( (float) $raw ) ) );
			} else {
				// Empty: tablet / mobile follow the larger screen; desktop keeps its default.
				$clean[ $key ] = 'font_size' === $key ? $default : null;
			}
		}

		if ( ! empty( $value['color'] ) && is_string( $value['color'] ) ) {
			$clean['color'] = (string) sanitize_hex_color( $value['color'] );
		}

		if ( isset( $value['dim'] ) && is_numeric( $value['dim'] ) ) {
			// Two decimals: never "1.0E-5", which CSS would not take.
			$clean['dim'] = (string) round( max( 0, min( 1, (float) $value['dim'] ) ), 2 );
		}

		if ( isset( $value['tag'] ) && in_array( $value['tag'], self::TAGS, true ) ) {
			$clean['tag'] = $value['tag'];
		}

		$clean['lightbox'] = ! empty( $value['lightbox'] );

		if ( isset( $value['css_class'] ) && is_scalar( $value['css_class'] ) ) {
			$clean['css_class'] = self::classes( (string) $value['css_class'] );
		}

		return $clean;
	}

	/**
	 * Class names separated by spaces, each made safe ("my-text  big" → "my-text big").
	 */
	public static function classes( $value ) {
		$out = array();
		foreach ( preg_split( '/\s+/', trim( $value ) ) as $class ) {
			$class = sanitize_html_class( $class );
			if ( '' !== $class ) {
				$out[ $class ] = true;
			}
		}
		return implode( ' ', array_keys( $out ) );
	}

	/**
	 * The tags the editor may keep: what its toolbar makes (bold, italic, link, text color, line break).
	 */
	public static function allowed_tags() {
		return array(
			'strong' => array(),
			'b'      => array(),
			'em'     => array(),
			'i'      => array(),
			'br'     => array(),
			'span'   => array( 'style' => true ),
			'a'      => array(
				'href'   => true,
				'target' => true,
				'rel'    => true,
			),
		);
	}

	/**
	 * The text as one paragraph: paragraphs become line breaks (the block is a <p> itself),
	 * other tags than allowed_tags() are dropped.
	 */
	public static function html( $text ) {
		$text = preg_replace( '#</p>\s*<p[^>]*>#i', '<br>', (string) $text );
		$text = preg_replace( '#</?p[^>]*>#i', '', $text );
		$text = wp_kses( $text, self::allowed_tags() );
		// A span keeps only its text color (the toolbar's color button): no other CSS from a pasted text.
		$text = preg_replace_callback(
			'/<span\b([^>]*)>/i',
			function ( $m ) {
				return preg_match( '/(?<![\w-])color\s*:\s*(#[0-9a-f]{3,8}|rgba?\(\s*[\d.,\s%]+\))/i', $m[1], $c ) ? '<span style="color:' . $c[1] . '">' : '<span>';
			},
			$text
		);
		return trim( $text );
	}
}
