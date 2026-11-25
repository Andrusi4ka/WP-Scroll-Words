<?php
/**
 * Plugin Name:       WP Scroll Words
 * Plugin URI:        https://github.com/Andrusi4ka/WP-Scroll-Words
 * Description:       Large statement texts whose words light up one by one as the page scrolls, with small rounded photos between the words. Build them under WP Scroll Words and insert them with a shortcode or the WPBakery element.
 * Version:           1.0.1
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Relevant AS
 * Author URI:        https://relevant.no/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wp-scroll-words
 * Domain Path:       /languages
 * Update URI:        https://github.com/Andrusi4ka/WP-Scroll-Words
 *
 * @package WP_Scroll_Words
 */

// "Update URI": updates come from this plugin's GitHub releases (wpsw_updates()), never from wordpress.org.

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WPSW_VERSION', '1.0.1' );
define( 'WPSW_REPOSITORY', 'https://github.com/Andrusi4ka/WP-Scroll-Words/' );
define( 'WPSW_POST_TYPE', 'wpsw_text' );
define( 'WPSW_FILE', __FILE__ );
define( 'WPSW_DIR', plugin_dir_path( __FILE__ ) );
define( 'WPSW_URL', plugin_dir_url( __FILE__ ) );

/**
 * Version for an asset: the plugin version plus the file's time, so a changed file is fetched again.
 *
 * @param string $path Path relative to the plugin folder.
 * @return string
 */
function wpsw_asset( $path ) {
	$time = @filemtime( WPSW_DIR . $path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- a missing file keeps the plain version.
	return $time ? WPSW_VERSION . '.' . $time : WPSW_VERSION;
}

require_once WPSW_DIR . 'includes/class-wpsw-access.php';
require_once WPSW_DIR . 'includes/class-wpsw-settings.php';
require_once WPSW_DIR . 'includes/class-wpsw-fonts.php';
require_once WPSW_DIR . 'includes/class-wpsw-palette.php';
require_once WPSW_DIR . 'includes/class-wpsw-admin.php';
require_once WPSW_DIR . 'includes/class-wpsw-admin-text.php';
require_once WPSW_DIR . 'includes/class-wpsw-render.php';
require_once WPSW_DIR . 'includes/class-wpsw-wpbakery.php';

/**
 * The texts' post type. Its capabilities are the plugin's own (WP Scroll Words → Settings, per role).
 */
function wpsw_register_post_type() {
	load_plugin_textdomain( 'wp-scroll-words', false, dirname( plugin_basename( WPSW_FILE ) ) . '/languages' );

	register_post_type(
		WPSW_POST_TYPE,
		array(
			'labels'        => array(
				'name'               => __( 'Scroll texts', 'wp-scroll-words' ),
				'singular_name'      => __( 'Scroll text', 'wp-scroll-words' ),
				'menu_name'          => __( 'WP Scroll Words', 'wp-scroll-words' ),
				'all_items'          => __( 'All texts', 'wp-scroll-words' ),
				'add_new'            => __( 'Add text', 'wp-scroll-words' ),
				'add_new_item'       => __( 'Add new text', 'wp-scroll-words' ),
				'edit_item'          => __( 'Edit text', 'wp-scroll-words' ),
				'search_items'       => __( 'Search texts', 'wp-scroll-words' ),
				'not_found'          => __( 'No texts found.', 'wp-scroll-words' ),
				'not_found_in_trash' => __( 'No texts found in Trash.', 'wp-scroll-words' ),
			),
			'public'        => false,
			'show_ui'       => true,
			'show_in_menu'  => true,
			'show_in_rest'  => false,
			'menu_position' => 26,
			'menu_icon'     => 'dashicons-editor-paragraph',
			'supports'      => array( 'title' ),
			'map_meta_cap'  => true,
			'rewrite'       => false,
			'capabilities'  => array_merge(
				array_fill_keys( array( 'edit_posts', 'edit_others_posts', 'edit_private_posts', 'edit_published_posts', 'read_private_posts' ), WPSW_Access::EDIT ),
				array_fill_keys( array( 'create_posts', 'publish_posts' ), WPSW_Access::CREATE ),
				array_fill_keys( array( 'delete_posts', 'delete_others_posts', 'delete_private_posts', 'delete_published_posts' ), WPSW_Access::DELETE )
			),
		)
	);
}
add_action( 'init', 'wpsw_register_post_type' );

/**
 * Updates from GitHub: a release (tag "vX.Y.Z" with the wp-scroll-words.zip built by
 * .github/workflows/release.yml) shows up under Plugins like any other update.
 */
function wpsw_updates() {
	$library = WPSW_DIR . 'vendor/plugin-update-checker/plugin-update-checker.php';
	// Only where WordPress looks for updates: the admin and its cron.
	if ( ( ! is_admin() && ! wp_doing_cron() ) || ! file_exists( $library ) ) {
		return;
	}
	require_once $library;

	$checker = YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker( WPSW_REPOSITORY, WPSW_FILE, 'wp-scroll-words' );
	// The release's zip, which holds just the plugin (not the repository's dev files).
	$checker->getVcsApi()->enableReleaseAssets( '/wp-scroll-words\.zip($|[?&#])/i' );
}
add_action( 'plugins_loaded', 'wpsw_updates' );

WPSW_Access::init();
WPSW_Admin::init();
WPSW_Render::init();
WPSW_WPBakery::init();
