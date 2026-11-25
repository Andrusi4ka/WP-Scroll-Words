<?php
/**
 * The [scroll_words id="…"] shortcode: builds the markup and loads the assets.
 *
 * The words are split into spans here, on the server, so the script only switches classes
 * and the result does not depend on how an editor wraps the text.
 *
 * @package WP_Scroll_Words
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPSW_Render {

	const SHORTCODE = 'scroll_words';
	const HANDLE    = 'wp-scroll-words';

	/** @var bool Whether build() is inside an <a> from the editor. */
	private static $in_link = false;

	/** @var string The pills' "sizes" for the text being built. */
	private static $sizes = '200px';

	public static function init() {
		add_shortcode( self::SHORTCODE, array( __CLASS__, 'shortcode' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register_assets' ) );
	}

	public static function register_assets() {
		wp_register_style( self::HANDLE, WPSW_URL . 'assets/css/scroll-words.css', array(), wpsw_asset( 'assets/css/scroll-words.css' ) );
		wp_register_script( self::HANDLE, WPSW_URL . 'assets/js/scroll-words.js', array(), wpsw_asset( 'assets/js/scroll-words.js' ), true );
	}

	/**
	 * [scroll_words id="12"] — a text from WP Scroll Words; el_class adds a class.
	 */
	public static function shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'id'       => 0,
				'el_class' => '',
			),
			$atts,
			self::SHORTCODE
		);

		$post = get_post( absint( $atts['id'] ) );
		if ( ! $post || WPSW_POST_TYPE !== $post->post_type || 'publish' !== $post->post_status ) {
			return '';
		}

		$s = WPSW_Settings::get( $post->ID );
		if ( '' === trim( $s['text'] ) ) {
			return '';
		}

		// Tablet / mobile without a size of their own follow the larger screen (scroll-words.css).
		$style = array( '--wpsw-weight:' . (int) $s['font_weight'] );
		foreach ( array(
			'font_size'        => '--wpsw-size',
			'font_size_tablet' => '--wpsw-size-t',
			'font_size_mobile' => '--wpsw-size-m',
		) as $key => $var ) {
			if ( null !== $s[ $key ] ) {
				$style[] = $var . ':' . (int) $s[ $key ] . 'px';
			}
		}
		if ( '' !== $s['font_family'] ) {
			$style[] = '--wpsw-font:' . WPSW_Fonts::css( $s['font_family'] );
		}
		if ( '' !== $s['color'] ) {
			$style[] = '--wpsw-color:' . $s['color'];
		}
		if ( '' !== $s['dim'] ) {
			$style[] = '--wpsw-dim:' . $s['dim'];
		}
		// A Google font from the list: loaded through Woodmart, as its own elements load theirs.
		$link = '' !== $s['font_family'] ? WPSW_Fonts::link( $s['font_family'], $s['font_weight'], $post->ID ) : '';

		// The text's own classes (Text style → Extra class name), then the element's / shortcode's el_class.
		$classes = array( 'wpsw', 'wpsw-' . $post->ID );
		$extra   = WPSW_Settings::classes( $s['css_class'] . ' ' . (string) $atts['el_class'] );
		if ( '' !== $extra ) {
			$classes[] = $extra;
		}

		if ( ! wp_style_is( self::HANDLE, 'registered' ) ) {
			self::register_assets();
		}
		wp_enqueue_style( self::HANDLE );
		wp_enqueue_script( self::HANDLE );

		if ( $s['lightbox'] ) {
			$classes[] = 'wpsw--lightbox';
			self::enqueue_lightbox();
		}

		return $link . sprintf(
			'<%1$s class="%2$s"%3$s data-scroll-words>%4$s</%1$s>',
			$s['tag'],
			esc_attr( implode( ' ', $classes ) ),
			$style ? ' style="' . esc_attr( implode( ';', $style ) ) . '"' : '',
			self::build( $s['text'], $s['images'], $s['lightbox'], (int) $s['font_size'] )
		);
	}

	/**
	 * The lightbox's own files: only on pages with a text that has it on.
	 */
	private static function enqueue_lightbox() {
		wp_enqueue_style( 'wpsw-lightbox', WPSW_URL . 'assets/css/lightbox.css', array(), wpsw_asset( 'assets/css/lightbox.css' ) );
		wp_enqueue_script( 'wpsw-lightbox', WPSW_URL . 'assets/js/lightbox.js', array(), wpsw_asset( 'assets/js/lightbox.js' ), true );
		wp_localize_script(
			'wpsw-lightbox',
			'wpswLightbox',
			array(
				'close' => __( 'Close', 'wp-scroll-words' ),
				'prev'  => __( 'Previous photo', 'wp-scroll-words' ),
				'next'  => __( 'Next photo', 'wp-scroll-words' ),
				'label' => __( 'Photo', 'wp-scroll-words' ),
			)
		);
	}

	/**
	 * "word <em>word</em> {1}. word" → word spans and photo pills; the editor's tags stay where they are,
	 * only the text between them is split (so an italic word lights up with its italics).
	 */
	private static function build( $html, array $ids, $lightbox, $font_size ) {
		// $html is clean already: WPSW_Settings::get() runs it through wp_kses().
		$out           = '';
		self::$in_link = false;
		// A pill is 1.9em wide: the image size the browser picks follows the text's desktop size.
		self::$sizes = (int) ceil( 1.9 * max( 8, $font_size ) ) . 'px';
		foreach ( preg_split( '/(<[^>]*>)/', $html, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY ) as $chunk ) {
			if ( '<' === $chunk[0] ) {
				if ( preg_match( '#^<(/?)a\b#i', $chunk, $m ) ) {
					self::$in_link = '' === $m[1];
				}
				$out .= $chunk;
			} else {
				$out .= self::words( preg_replace( '/\s+/u', ' ', $chunk ), $ids, $lightbox );
			}
		}
		return trim( $out );
	}

	/**
	 * A photo pill; with the lightbox, inside a button that carries the large photo (lightbox.js).
	 * A pill inside a link from the editor stays a plain image: a button inside a link is not allowed.
	 */
	private static function pill( $id, $lightbox ) {
		$img = wp_get_attachment_image(
			$id,
			'medium_large',
			false,
			array(
				'class'    => 'wpsw-pill',
				'alt'      => '',
				'loading'  => 'lazy',
				'decoding' => 'async',
				'sizes'    => self::$sizes,
			)
		);
		if ( ! $img || ! $lightbox ) {
			return (string) $img;
		}
		$full = wp_get_attachment_image_src( $id, 'full' );
		if ( ! $full ) {
			return $img;
		}
		$alt     = trim( (string) get_post_meta( $id, '_wp_attachment_image_alt', true ) );
		$caption = trim( (string) wp_get_attachment_caption( $id ) );
		return sprintf(
			'<button type="button" class="wpsw-zoom" data-src="%1$s" data-srcset="%2$s" data-alt="%3$s" data-caption="%4$s" aria-label="%5$s" aria-haspopup="dialog">%6$s</button>',
			esc_url( $full[0] ),
			esc_attr( (string) wp_get_attachment_image_srcset( $id, 'full' ) ),
			esc_attr( $alt ),
			esc_attr( $caption ),
			esc_attr( '' !== $alt ? $alt : __( 'Open photo', 'wp-scroll-words' ) ),
			$img
		);
	}

	/**
	 * A piece of text between tags (already safe HTML from wp_kses): its words and photos.
	 */
	private static function words( $text, array $ids, $lightbox ) {
		$out   = '';
		$parts = preg_split( '/(\{\d+\})/', $text, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY );

		foreach ( $parts as $part ) {
			if ( preg_match( '/^\{(\d+)\}$/', $part, $m ) ) {
				$index = (int) $m[1] - 1;
				if ( isset( $ids[ $index ] ) ) {
					$out .= self::pill( $ids[ $index ], $lightbox && ! self::$in_link );
				}
				continue;
			}

			foreach ( preg_split( '/( )/', $part, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY ) as $word ) {
				$out .= ' ' === $word ? ' ' : '<span class="wpsw-w">' . $word . '</span>';
			}
		}

		return $out;
	}
}
