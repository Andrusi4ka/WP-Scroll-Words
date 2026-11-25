<?php
/**
 * WP Scroll Words → Settings: what each role may do with the texts, as checkboxes per action (as WP Banner's
 * WPBN_Access). Roles that manage the site (manage_options) always may do all of it, and only they open this page.
 *
 * The rights are the plugin's own capabilities, given at run time (user_has_cap) from the option, never written into
 * the roles: deleting the plugin leaves the roles as they were.
 *
 * @package WP_Scroll_Words
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPSW_Access {

	const OPTION     = 'wpsw_roles';
	const GROUP      = 'wpsw_roles';
	const PAGE       = 'wpsw-settings';
	const CAPABILITY = 'manage_options';

	const EDIT     = 'wpsw_edit_texts';
	const CREATE   = 'wpsw_create_texts';
	const DELETE   = 'wpsw_delete_texts';
	const WPBAKERY = 'wpsw_wpbakery';

	/** @var array|null rights() worked out once per request. */
	private static $rights = null;

	public static function init() {
		add_filter( 'user_has_cap', array( __CLASS__, 'grant' ), 10, 4 );
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( WPSW_FILE ), array( __CLASS__, 'plugin_link' ) );
		foreach ( array( 'add_option_', 'update_option_', 'delete_option_' ) as $hook ) {
			add_action( $hook . self::OPTION, array( __CLASS__, 'forget' ) );
		}
	}

	/**
	 * Key => [capability, name, what it allows]. All but Edit only together with Edit.
	 */
	public static function actions() {
		return array(
			'edit'     => array( self::EDIT, __( 'Edit texts', 'wp-scroll-words' ), __( 'WP Scroll Words in the menu: open any text and change it.', 'wp-scroll-words' ) ),
			'create'   => array( self::CREATE, __( 'Add', 'wp-scroll-words' ), __( 'New texts. With Edit texts only.', 'wp-scroll-words' ) ),
			'delete'   => array( self::DELETE, __( 'Delete', 'wp-scroll-words' ), __( 'Texts to the Trash and for good. With Edit texts only.', 'wp-scroll-words' ) ),
			'wpbakery' => array( self::WPBAKERY, __( 'WPBakery element', 'wp-scroll-words' ), __( 'Put a text on a page with the WPBakery element. With Edit texts only. Pages show the texts to everyone anyway.', 'wp-scroll-words' ) ),
		);
	}

	public static function can( $action ) {
		$actions = self::actions();
		return isset( $actions[ $action ] ) && current_user_can( $actions[ $action ][0] );
	}

	public static function is_full( $role ) {
		$object = get_role( $role );
		return $object && $object->has_cap( self::CAPABILITY );
	}

	public static function rights() {
		if ( null === self::$rights ) {
			$saved        = get_option( self::OPTION, array() );
			self::$rights = is_array( $saved ) ? self::sanitize( $saved ) : array();
		}
		return self::$rights;
	}

	public static function forget() {
		self::$rights = null;
	}

	/**
	 * Kept: the site's roles that do not manage it, the known actions; the others only with Edit.
	 */
	public static function sanitize( $value ) {
		$clean = array();
		if ( ! is_array( $value ) ) {
			return $clean;
		}
		$roles = wp_roles()->roles;
		foreach ( $value as $role => $actions ) {
			$role = (string) $role;
			if ( ! isset( $roles[ $role ] ) || self::is_full( $role ) || ! is_array( $actions ) ) {
				continue;
			}
			$on = array();
			foreach ( array_keys( self::actions() ) as $action ) {
				if ( ! empty( $actions[ $action ] ) && is_scalar( $actions[ $action ] ) ) {
					$on[ $action ] = true;
				}
			}
			if ( empty( $on['edit'] ) ) {
				$on = array();
			}
			if ( $on ) {
				$clean[ $role ] = $on;
			}
		}
		return $clean;
	}

	/**
	 * user_has_cap: the plugin's capabilities from the user's roles. Site managers have them all.
	 */
	public static function grant( $allcaps, $caps, $args, $user ) {
		$actions = self::actions();
		if ( ! empty( $allcaps[ self::CAPABILITY ] ) ) {
			foreach ( $actions as $action ) {
				$allcaps[ $action[0] ] = true;
			}
			return $allcaps;
		}
		$roles = isset( $user->roles ) ? (array) $user->roles : array();
		if ( ! $roles ) {
			return $allcaps;
		}
		$rights = self::rights();
		foreach ( $roles as $role ) {
			if ( empty( $rights[ $role ] ) ) {
				continue;
			}
			foreach ( array_keys( $rights[ $role ] ) as $action ) {
				$allcaps[ $actions[ $action ][0] ] = true;
			}
		}
		return $allcaps;
	}

	public static function register() {
		register_setting(
			self::GROUP,
			self::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize' ),
				'default'           => array(),
				'show_in_rest'      => false,
			)
		);
	}

	public static function menu() {
		add_submenu_page( 'edit.php?post_type=' . WPSW_POST_TYPE, __( 'WP Scroll Words settings', 'wp-scroll-words' ), __( 'Settings', 'wp-scroll-words' ), self::CAPABILITY, self::PAGE, array( __CLASS__, 'render' ), 2 );
	}

	public static function plugin_link( $links ) {
		if ( current_user_can( self::CAPABILITY ) ) {
			array_unshift( $links, '<a href="' . esc_url( admin_url( 'edit.php?post_type=' . WPSW_POST_TYPE . '&page=' . self::PAGE ) ) . '">' . esc_html__( 'Settings', 'wp-scroll-words' ) . '</a>' );
		}
		return $links;
	}

	public static function enqueue( $hook ) {
		if ( false === strpos( (string) $hook, self::PAGE ) ) {
			return;
		}
		wp_enqueue_style( 'wpsw-admin', WPSW_URL . 'assets/css/admin.css', array(), wpsw_asset( 'assets/css/admin.css' ) );
		wp_enqueue_script( 'wpsw-roles', WPSW_URL . 'assets/js/roles.js', array(), wpsw_asset( 'assets/js/roles.js' ), true );
	}

	/**
	 * The page: the site's roles × the actions, saved by options.php (its nonce and capability).
	 */
	public static function render() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			return;
		}
		$actions = self::actions();
		$rights  = self::rights();
		?>
		<div class="wrap wpsw-roles">
			<h1><?php esc_html_e( 'WP Scroll Words settings', 'wp-scroll-words' ); ?></h1>
			<?php settings_errors(); ?>
			<form method="post" action="options.php">
				<?php settings_fields( self::GROUP ); ?>
				<h2><?php esc_html_e( 'Roles', 'wp-scroll-words' ); ?></h2>
				<p class="description"><?php esc_html_e( 'What each role may do with the texts. Roles that manage the site (Administrators) may do all of it, and only they open this page. Visitors see the texts on the pages whatever is set here.', 'wp-scroll-words' ); ?></p>
				<table class="widefat striped wpsw-roles__table">
					<thead>
						<tr>
							<th scope="col"><?php esc_html_e( 'Role', 'wp-scroll-words' ); ?></th>
							<?php foreach ( $actions as $action ) : ?>
								<th scope="col" class="wpsw-roles__action" title="<?php echo esc_attr( $action[2] ); ?>"><?php echo esc_html( $action[1] ); ?></th>
							<?php endforeach; ?>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( wp_roles()->roles as $role => $data ) : ?>
							<?php $full = self::is_full( $role ); ?>
							<tr>
								<th scope="row">
									<?php echo esc_html( translate_user_role( $data['name'] ) ); ?>
									<?php if ( $full ) : ?>
										<span class="wpsw-roles__always"><?php esc_html_e( 'always all', 'wp-scroll-words' ); ?></span>
									<?php endif; ?>
								</th>
								<?php foreach ( $actions as $key => $action ) : ?>
									<td class="wpsw-roles__action">
										<input type="checkbox" data-action="<?php echo esc_attr( $key ); ?>"
											aria-label="<?php echo esc_attr( $action[1] . ': ' . translate_user_role( $data['name'] ) ); ?>"
											<?php if ( $full ) : ?>
												checked disabled
											<?php else : ?>
												name="<?php echo esc_attr( self::OPTION . '[' . $role . '][' . $key . ']' ); ?>" value="1" <?php checked( ! empty( $rights[ $role ][ $key ] ) ); ?>
											<?php endif; ?>>
									</td>
								<?php endforeach; ?>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
}
