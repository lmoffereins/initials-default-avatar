<?php

/**
 * Initials Default Avatar Admin Functions
 *
 * @package Initials Default Avatar
 * @subpackage Administration
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Initials_Default_Avatar_Admin' ) ) :
/**
 * The Initials Default Avatar Admin class
 *
 * @since 2.0.0
 */
class Initials_Default_Avatar_Admin {

	/**
	 * Setup this class
	 *
	 * @since 2.0.0
	 */
	public function __construct() {
		$this->setup_globals();
		$this->setup_actions();
	}

	/**
	 * Define default class globals
	 *
	 * @since 2.0.0
	 */
	private function setup_globals() {
		$this->gravatar_notice = initials_default_avatar_gravatar_notice_key();
		$this->minimum_capability = initials_default_avatar_is_network_default() ? 'manage_network_options' : 'manage_options';
	}

	/**
	 * Define default actions and filters
	 *
	 * @since 2.0.0
	 */
	private function setup_actions() {

		add_action( 'plugin_action_links',               array( $this, 'plugin_action_links' ), 10, 2 );
		add_filter( 'network_admin_plugin_action_links', array( $this, 'plugin_action_links' ), 10, 2 );

		/** Core *************************************************************/

		add_action( 'admin_init',                       array( $this, 'register_settings'          ) );
		add_action( 'admin_init',                       array( $this, 'hook_admin_gravatar_notice' ) );
		add_action( "wp_ajax_{$this->gravatar_notice}", array( $this, 'save_admin_gravatar_notice' ) );
		add_action( 'admin_enqueue_scripts',            array( $this, 'enqueue_scripts'            ) );

		/** Network **********************************************************/

		add_action( 'wpmu_options',        'initials_default_avatar_network_admin_settings_section' );
		add_action( 'update_wpmu_options', 'initials_default_avatar_network_admin_save_settings'    );
	}

	/** Plugin ****************************************************************/

	/**
	 * Add some to the plugin action links
	 *
	 * @since 1.0.0
	 * 
	 * @param array $links
	 * @param string $basename Plugin basename
	 * @return array Links
	 */
	public function plugin_action_links( $links, $basename ) {

		// Add plugin action links fot this plugin
		if ( $basename === initials_default_avatar()->basename && current_user_can( $this->minimum_capability ) ) {

			// Determine admin url
			$admin_url = initials_default_avatar_is_network_default() || is_network_admin()
				? network_admin_url( 'settings.php#initials-default-avatar' )
				: admin_url( 'options-discussion.php' );

			$links['settings'] = '<a href="' . esc_url( $admin_url ) . '">' . esc_html__( 'Settings', 'initials-default-avatar' ) . '</a>';
		}

		return $links;
	}

	/** Settings **************************************************************/

	/**
	 * Register plugin settings
	 *
	 * @since 1.0.0
	 */
	public function register_settings() {

		// Bail when the default is network-defined
		if ( initials_default_avatar_is_network_default() ) {

			// Modify single site settings pages
			add_filter( 'avatar_defaults',       '__return_empty_array',              99 );
			add_filter( 'default_avatar_select', '__return_empty_string',             99 );
			add_filter( 'whitelist_options',     array( $this, 'whitelist_options' ), 99 );

			return;
		}

		// Register settings field
		add_settings_field(
			'initials-default-avatar-service',
			__( 'Initials Default Avatar', 'initials-default-avatar' ),
			'initials_default_avatar_admin_setting_placeholder_service',
			'discussion',
			'avatars',
			array(
				'setting' => 'initials_default_avatar_service'
			)
		);

		// Register setting for selected service with options
		register_setting( 'discussion', 'initials_default_avatar_service', 'initials_default_avatar_admin_sanitize_service' );
		register_setting( 'discussion', 'initials_default_avatar_service_options', 'initials_default_avatar_admin_sanitize_service_options' );
	}

	/**
	 * Enqueue scripts in the admin head on settings pages
	 *
	 * @since 1.0.0
	 */
	public function enqueue_scripts( $hook_suffix ) {

		// Register scripts 'n styles
		wp_register_script( 'initials-default-avatar-admin', initials_default_avatar()->assets_url . 'js/admin.js', array( 'jquery' ), initials_default_avatar_get_version(), true );
		wp_register_style( 'initials-default-avatar-admin', initials_default_avatar()->assets_url . 'css/admin.css', array(), initials_default_avatar_get_version() );

		// Bail if not on the discussion page
		if ( 'options-discussion.php' !== $hook_suffix && ( is_network_admin() && 'settings.php' !== $hook_suffix ) )
			return;

		// Enqueue admin scripts 'n styles
		wp_enqueue_script( 'initials-default-avatar-admin' );
		wp_localize_script( 'initials-default-avatar-admin', 'initialsDefaultAvatarAdmin', array(
			'settings' => array(
				'avatarKey'      => initials_default_avatar_get_avatar_key(),
				'enabled'        => ! initials_default_avatar_is_network_default() || is_network_admin(),
				'networkDefault' => array(
					'isActive' => initials_default_avatar_is_network_default(),
					'heading'  => __( 'Avatars' ), // Using the default domain
					'message'  => esc_html__( "Avatars are managed by the administrator of your site's network. There are no settings for you here.", 'initials-default-avatar' )
				)
			)
		) );

		// When the default is not network-defined
		if ( ! initials_default_avatar_is_network_default() || is_network_admin() ) {
			wp_enqueue_style( 'initials-default-avatar-admin' );

			// Enqueue color picker
			wp_enqueue_script( 'wp-color-picker' );
			wp_enqueue_style( 'wp-color-picker' );
		}
	}

	/**
	 * Modify the options whitelist
	 *
	 * @since 2.0.0
	 *
	 * @param array $options Options whitelist
	 * @return array Options whitelist
	 */
	public function whitelist_options( $options ) {

		// When the default is network-defined
		if ( initials_default_avatar_is_network_default() ) {

			// Un-whitelist avatar options
			$options['discussion'] = array_diff( $options['discussion'], array(
				'show_avatars',
				'avatar_rating',
				'avatar_default'
			) );
		}

		return $options;
	}

	/** Admin *****************************************************************/

	/**
	 * Hook admin notice when the connection to Gravatar.com failed
	 *
	 * @since 1.0.0
	 */
	public function hook_admin_gravatar_notice() {

		// Bail if user cannot (de)activate plugins
		if ( ! current_user_can( 'activate_plugins' ) || get_transient( $this->gravatar_notice ) )
			return;

		// Setup Gravatar url
		$user       = get_userdata( get_current_user_id() );
		$email_hash = md5( strtolower( trim( $user->user_email ) ) );
		$url        = is_ssl()
			? sprintf( 'https://secure.gravatar.com/avatar/%s?d=404', $email_hash )
			: sprintf( 'http://%d.gravatar.com/avatar/%s?d=404', hexdec( $email_hash[0] ) % 3, $email_hash );

		// Check Gravatar.com for a response
		$response = wp_remote_head( $url );

		// Connection failed, hook the admin notice
		if ( $response && is_wp_error( $response ) ) {

			// Site notice
			add_action( 'admin_notices', array( $this, 'site_admin_gravatar_notice' ) );

			// Network notice
			if ( initials_default_avatar_is_network_default() ) {
				add_action( 'network_admin_notices', array( $this, 'site_admin_gravatar_notice' ) );
			}
		}
	}

	/**
	 * Output admin notice that the connection to Gravatar.com failed
	 *
	 * @since 1.0.0
	 */
	public function site_admin_gravatar_notice() { ?>

		<div id="<?php echo $this->gravatar_notice; ?>" class="notice error is-dismissible">
			<p><?php esc_html_e( 'It seems that your site cannot connect to gravatar.com to check user profiles. Note that Initials Default Avatar may overwrite a valid gravatar with a default avatar.', 'initials-default-avatar' ); ?></p>

			<script type="text/javascript">
				(function( $ ) {
					/* global ajaxurl */
					$( '#<?php echo $this->gravatar_notice; ?>' ).on( 'click', '.notice-dismiss', function() {
						$.post( ajaxurl, {
							"action": '<?php echo $this->gravatar_notice; ?>',
							"_ajax_nonce": '<?php echo wp_create_nonce( $this->gravatar_notice ); ?>'
						});
					});
				})( jQuery );
			</script>
		</div>

		<?php
	}

	/**
	 * Store Gravatar notice option
	 *
	 * @since 1.0.0
	 */
	public function save_admin_gravatar_notice() {

		// Verify intent
		check_ajax_referer( $this->gravatar_notice );

		// Update transient for a day
		set_transient( $this->gravatar_notice, true, DAY_IN_SECONDS );
	}
}

/**
 * Setup the plugin admin class
 *
 * @since 2.0.0
 *
 * @uses Initials_Default_Avatar_Admin
 */
function initials_default_avatar_admin() {
	initials_default_avatar()->admin = new Initials_Default_Avatar_Admin;
}

endif; // class_exists
