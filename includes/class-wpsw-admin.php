<?php
/**
 * The edit screen of a text (its fields and the shortcode) and the list's shortcode column.
 *
 * @package WP_Scroll_Words
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPSW_Admin {

	const NONCE = 'wpsw_save';

	public static function init() {
		add_action( 'add_meta_boxes_' . WPSW_POST_TYPE, array( __CLASS__, 'boxes' ) );
		add_action( 'save_post_' . WPSW_POST_TYPE, array( __CLASS__, 'save' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
		add_filter( 'manage_' . WPSW_POST_TYPE . '_posts_columns', array( __CLASS__, 'columns' ) );
		add_action( 'manage_' . WPSW_POST_TYPE . '_posts_custom_column', array( __CLASS__, 'column' ), 10, 2 );
	}

	public static function shortcode( $post_id ) {
		return '[' . WPSW_Render::SHORTCODE . ' id="' . (int) $post_id . '"]';
	}

	public static function boxes() {
		add_meta_box( 'wpsw-text', __( 'Text and photos', 'wp-scroll-words' ), array( __CLASS__, 'box_text' ), null, 'normal', 'high' );
		add_meta_box( 'wpsw-shortcode', __( 'Shortcode', 'wp-scroll-words' ), array( __CLASS__, 'box_shortcode' ), null, 'side', 'high' );
	}

	public static function box_text( $post ) {
		$s = WPSW_Settings::get( $post->ID );
		wp_nonce_field( self::NONCE, self::NONCE );
		?>
		<p><strong><?php esc_html_e( 'Text', 'wp-scroll-words' ); ?></strong></p>
		<?php
		// One paragraph: Enter makes a line break, the toolbar only what WPSW_Settings::allowed_tags() keeps.
		wp_editor(
			$s['text'],
			'wpswtext',
			array(
				'textarea_name' => 'wpsw[text]',
				'textarea_rows' => 6,
				'media_buttons' => false,
				'wpautop'       => false,
				'tinymce'       => array(
					'toolbar1'          => 'bold,italic,link,forecolor,removeformat',
					'toolbar2'          => '',
					'forced_root_block' => false,
				),
				'quicktags'     => array( 'buttons' => 'strong,em,link' ),
			)
		);
		?>
		<p class="description"><?php esc_html_e( 'Write {1}, {2}… where a photo goes. Put punctuation right after it: "kaffe {4}. Vi baker".', 'wp-scroll-words' ); ?></p>
		<?php WPSW_Admin_Text::render( $s ); ?>
		<p><strong><?php esc_html_e( 'Photos', 'wp-scroll-words' ); ?></strong></p>
		<ul class="wpsw-images" data-wpsw-images>
			<?php foreach ( $s['images'] as $id ) : ?>
				<li data-id="<?php echo (int) $id; ?>">
					<?php echo wp_get_attachment_image( $id, 'thumbnail' ); ?>
					<span class="wpsw-images__num"></span>
					<button type="button" class="wpsw-images__remove" aria-label="<?php esc_attr_e( 'Remove photo', 'wp-scroll-words' ); ?>">&times;</button>
				</li>
			<?php endforeach; ?>
		</ul>
		<input type="hidden" name="wpsw[images]" value="<?php echo esc_attr( implode( ',', $s['images'] ) ); ?>" data-wpsw-images-input>
		<p>
			<button type="button" class="button" data-wpsw-add><?php esc_html_e( 'Add photos', 'wp-scroll-words' ); ?></button>
			<span class="description"><?php esc_html_e( 'Drag to change the order. The number on a photo is its {n}.', 'wp-scroll-words' ); ?></span>
		</p>
		<?php
	}

	public static function box_shortcode( $post ) {
		if ( 'auto-draft' === $post->post_status ) {
			echo '<p>' . esc_html__( 'Save the text to get its shortcode.', 'wp-scroll-words' ) . '</p>';
			return;
		}
		self::copy_field( $post->ID );
	}

	private static function copy_field( $post_id ) {
		printf(
			'<input type="text" class="wpsw-code" readonly value="%1$s" onfocus="this.select()"> <button type="button" class="button button-small" data-wpsw-copy="%1$s">%2$s</button>',
			esc_attr( self::shortcode( $post_id ) ),
			esc_html__( 'Copy', 'wp-scroll-words' )
		);
	}

	public static function save( $post_id, $post ) {
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) ) {
			return;
		}
		if ( ! isset( $_POST[ self::NONCE ], $_POST['wpsw'] ) || ! wp_verify_nonce( sanitize_key( $_POST[ self::NONCE ] ), self::NONCE ) ) {
			return;
		}
		if ( ! WPSW_Access::can( 'edit' ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		WPSW_Settings::save( $post_id, (array) wp_unslash( $_POST['wpsw'] ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- WPSW_Settings::sanitize().
	}

	public static function enqueue( $hook ) {
		$screen = get_current_screen();
		if ( ! $screen || WPSW_POST_TYPE !== $screen->post_type || ! in_array( $hook, array( 'post.php', 'post-new.php', 'edit.php' ), true ) ) {
			return;
		}
		wp_enqueue_style( 'wpsw-admin', WPSW_URL . 'assets/css/admin.css', array(), wpsw_asset( 'assets/css/admin.css' ) );
		if ( 'edit.php' !== $hook ) {
			wp_enqueue_media();
			WPSW_Admin_Text::enqueue();
		}
		wp_enqueue_script( 'wpsw-admin', WPSW_URL . 'assets/js/admin.js', array( 'jquery', 'jquery-ui-sortable' ), wpsw_asset( 'assets/js/admin.js' ), true );
		wp_localize_script(
			'wpsw-admin',
			'wpswAdmin',
			array(
				'title'  => __( 'Photos for the text', 'wp-scroll-words' ),
				'button' => __( 'Add', 'wp-scroll-words' ),
				'copied' => __( 'Copied', 'wp-scroll-words' ),
			)
		);
	}

	public static function columns( $columns ) {
		$date = isset( $columns['date'] ) ? $columns['date'] : null;
		unset( $columns['date'] );
		$columns['wpsw_shortcode'] = __( 'Shortcode', 'wp-scroll-words' );
		if ( $date ) {
			$columns['date'] = $date;
		}
		return $columns;
	}

	public static function column( $column, $post_id ) {
		if ( 'wpsw_shortcode' === $column ) {
			self::copy_field( $post_id );
		}
	}
}
