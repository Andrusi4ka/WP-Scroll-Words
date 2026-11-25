<?php
/**
 * The "Text style" card under the text (drawn as WP Banner's fields): Font (Woodmart's fonts, searchable),
 * Weight (the font's own), Font size per device, Color (Pickr with the theme's palette), HTML tag,
 * Dimmed opacity, Extra class name and the lightbox switch.
 * Its behavior: assets/js/admin-text.js.
 *
 * @package WP_Scroll_Words
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPSW_Admin_Text {

	const WEIGHTS = array(
		100 => '100 Thin',
		200 => '200 Extra-light',
		300 => '300 Light',
		400 => '400 Regular',
		500 => '500 Medium',
		600 => '600 Semi-bold',
		700 => '700 Bold',
		800 => '800 Extra-bold',
		900 => '900 Black',
	);

	const DEVICES = array(
		'desktop' => array( 'desktop', 'Desktop', 'font_size' ),
		'tablet'  => array( 'tablet', 'Tablet', 'font_size_tablet' ),
		'mobile'  => array( 'smartphone', 'Mobile', 'font_size_mobile' ),
	);

	public static function enqueue() {
		wp_enqueue_style( 'wpsw-pickr', WPSW_URL . 'assets/vendor/pickr/classic.min.css', array(), '1.9.1' );
		wp_enqueue_script( 'wpsw-pickr', WPSW_URL . 'assets/vendor/pickr/pickr.min.js', array(), '1.9.1', true );
		wp_enqueue_script( 'wpsw-admin-text', WPSW_URL . 'assets/js/admin-text.js', array( 'wpsw-pickr' ), wpsw_asset( 'assets/js/admin-text.js' ), true );
		wp_localize_script(
			'wpsw-admin-text',
			'wpswText',
			array(
				'fonts'   => WPSW_Fonts::groups(),
				'palette' => WPSW_Palette::colors(),
			)
		);
	}

	private static function head( $id, $label, $extra = '' ) {
		return '<div class="wpsw-field__head"><label for="' . esc_attr( $id ) . '">' . esc_html( $label ) . '</label>' . $extra . '</div>';
	}

	public static function render( array $s ) {
		echo '<div class="wpsw-card"><p class="wpsw-subheading">' . esc_html__( 'Text style', 'wp-scroll-words' ) . '</p><div class="wpsw-card__grid">';
		self::font( $s );
		self::weight( $s );
		self::size( $s );
		self::color( $s );
		self::tag( $s );
		self::dim( $s );
		self::css_class( $s );
		self::lightbox( $s );
		echo '</div></div>';
	}

	// Classes for the site's own CSS, on the text's block (as WPBakery's "Extra class name").
	private static function css_class( array $s ) {
		$id = 'wpsw-f-class';
		echo '<div class="wpsw-field">' . self::head( $id, __( 'Extra class name', 'wp-scroll-words' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- head() escapes.
		printf(
			'<input id="%1$s" type="text" name="wpsw[css_class]" value="%2$s" placeholder="my-text" autocomplete="off" spellcheck="false"><p class="description">%3$s</p></div>',
			esc_attr( $id ),
			esc_attr( $s['css_class'] ),
			esc_html__( 'For your CSS. Several: separate with spaces.', 'wp-scroll-words' )
		);
	}

	// A switch, as WP Banner's: a hidden 0 first, so an unchecked box is saved as off too.
	private static function lightbox( array $s ) {
		printf(
			'<div class="wpsw-field wpsw-field--switch"><input type="hidden" name="wpsw[lightbox]" value="0"><label class="wpsw-switch" for="wpsw-f-lightbox"><input id="wpsw-f-lightbox" type="checkbox" name="wpsw[lightbox]" value="1"%1$s><span class="wpsw-switch__track" aria-hidden="true"></span><span>%2$s</span></label><p class="description">%3$s</p></div>',
			checked( $s['lightbox'], true, false ),
			esc_html__( 'Open photos in lightbox', 'wp-scroll-words' ),
			esc_html__( 'A click on a photo shows it large, with arrows between the photos of this text.', 'wp-scroll-words' )
		);
	}

	// How visible the words are before they light up on scroll.
	private static function dim( array $s ) {
		$id = 'wpsw-f-dim';
		echo '<div class="wpsw-field">' . self::head( $id, __( 'Dimmed opacity', 'wp-scroll-words' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- head() escapes.
		printf(
			'<input id="%1$s" type="number" min="0" max="1" step="0.01" name="wpsw[dim]" value="%2$s" placeholder="0.14"><p class="description">%3$s</p></div>',
			esc_attr( $id ),
			esc_attr( $s['dim'] ),
			esc_html__( 'Words not lit yet: 0 hidden, 1 no effect.', 'wp-scroll-words' )
		);
	}

	private static function font( array $s ) {
		$id = 'wpsw-f-font';
		echo '<div class="wpsw-field wpsw-field--font">' . self::head( $id, __( 'Font', 'wp-scroll-words' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- head() escapes.
		if ( WPSW_Fonts::available() ) {
			printf(
				'<div class="wpsw-font"><input type="hidden" name="wpsw[font_family]" value="%1$s" data-wpsw-font-value><button type="button" class="wpsw-font__toggle" id="%2$s" aria-haspopup="listbox" aria-expanded="false"><span class="wpsw-font__name"></span><span class="dashicons dashicons-arrow-down-alt2" aria-hidden="true"></span></button><div class="wpsw-font__panel" hidden><input type="search" class="wpsw-font__search" placeholder="%3$s" aria-label="%3$s" autocomplete="off"><div class="wpsw-font__list" role="listbox"></div></div></div>',
				esc_attr( $s['font_family'] ),
				esc_attr( $id ),
				esc_attr__( 'Search fonts', 'wp-scroll-words' )
			);
		} else {
			printf(
				'<input id="%1$s" type="text" name="wpsw[font_family]" value="%2$s" placeholder="\'Instrument Serif\', serif" data-wpsw-font-value><p class="description">%3$s</p>',
				esc_attr( $id ),
				esc_attr( $s['font_family'] ),
				esc_html__( 'Empty = the font of the page. The font must be loaded on the site.', 'wp-scroll-words' )
			);
		}
		echo '</div>';
	}

	private static function weight( array $s ) {
		$id = 'wpsw-f-weight';
		echo '<div class="wpsw-field">' . self::head( $id, __( 'Weight', 'wp-scroll-words' ) ) . '<select id="' . esc_attr( $id ) . '" name="wpsw[font_weight]" data-wpsw-weight>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- head() escapes.
		foreach ( self::WEIGHTS as $value => $name ) {
			echo '<option value="' . (int) $value . '"' . selected( (int) $s['font_weight'], $value, false ) . '>' . esc_html( $name ) . '</option>';
		}
		echo '</select></div>';
	}

	// One number per device; the icons switch which one is shown (a green dot where tablet / mobile has its own).
	private static function size( array $s ) {
		$devices = '<span class="wpsw-devices" role="group" aria-label="Device">';
		foreach ( self::DEVICES as $device => list( $icon, $name, $key ) ) {
			$own      = 'desktop' !== $device && null !== $s[ $key ];
			$devices .= sprintf(
				'<button type="button" class="wpsw-devices__btn%1$s%2$s" data-wpsw-device="%3$s" aria-pressed="%4$s" title="%5$s" aria-label="%5$s"><span class="dashicons dashicons-%6$s" aria-hidden="true"></span></button>',
				'desktop' === $device ? ' is-active' : '',
				$own ? ' has-value' : '',
				esc_attr( $device ),
				'desktop' === $device ? 'true' : 'false',
				esc_attr( $name ),
				esc_attr( $icon )
			);
		}
		$devices .= '</span>';

		echo '<div class="wpsw-field wpsw-field--size">' . self::head( 'wpsw-f-size-desktop', __( 'Font size', 'wp-scroll-words' ), $devices ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built above, escaped.
		foreach ( self::DEVICES as $device => list( $icon, $name, $key ) ) {
			list( $default, $min, $max ) = WPSW_Settings::SIZES[ $key ];
			// Desktop's placeholder: its default (an empty desktop is saved as it). Tablet / mobile: admin-text.js shows the larger screen's.
			printf(
				'<span class="wpsw-number-unit" data-wpsw-size="%1$s"%2$s><input id="wpsw-f-size-%1$s" type="number" min="%3$d" max="%4$d" step="1" name="wpsw[%5$s]" value="%6$s" placeholder="%8$s" aria-label="%7$s"><span class="wpsw-number-unit__unit" aria-hidden="true">px</span></span>',
				esc_attr( $device ),
				'desktop' === $device ? '' : ' hidden',
				(int) $min,
				(int) $max,
				esc_attr( $key ),
				null === $s[ $key ] ? '' : (int) $s[ $key ],
				esc_attr( __( 'Font size', 'wp-scroll-words' ) . ' — ' . $name ),
				null === $default ? '' : (int) $default
			);
		}
		echo '<p class="description">' . esc_html__( 'Tablet and mobile: empty follows the larger screen.', 'wp-scroll-words' ) . '</p></div>';
	}

	private static function color( array $s ) {
		$id = 'wpsw-f-color';
		echo '<div class="wpsw-field">' . self::head( $id, __( 'Color', 'wp-scroll-words' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- head() escapes.
		printf(
			'<span class="wpsw-color"><input type="hidden" name="wpsw[color]" value="%1$s"><button type="button" class="wpsw-color__swatch" id="%2$s" aria-expanded="false"><span class="wpsw-color__chip" aria-hidden="true"></span></button></span><p class="description">%3$s</p></div>',
			esc_attr( $s['color'] ),
			esc_attr( $id ),
			esc_html__( 'Default = the color of the page.', 'wp-scroll-words' )
		);
	}

	private static function tag( array $s ) {
		$id = 'wpsw-f-tag';
		echo '<div class="wpsw-field">' . self::head( $id, __( 'HTML tag', 'wp-scroll-words' ) ) . '<select id="' . esc_attr( $id ) . '" name="wpsw[tag]">'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- head() escapes.
		foreach ( WPSW_Settings::TAGS as $tag ) {
			echo '<option value="' . esc_attr( $tag ) . '"' . selected( $s['tag'], $tag, false ) . '>' . esc_html( $tag ) . '</option>';
		}
		echo '</select></div>';
	}
}
