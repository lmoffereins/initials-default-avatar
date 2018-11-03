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
 * @since 1.1.0
 */
class Initials_Default_Avatar_Admin {

	/**
	 * Setup this class
	 *
	 * @since 1.1.0
	 */
	public function __construct() {
		$this->setup_globals();
		$this->setup_actions();
	}

	/**
	 * Define default class globals
	 *
	 * @since 1.1.0
	 */
	private function setup_globals() {
		$this->notice = 'initials-default-avatar_notice';
		$this->minimum_capability = initials_default_avatar_is_network_default() ? 'manage_network_options' : 'manage_options';
	}

	/**
	 * Define default actions and filters
	 *
	 * @since 1.1.0
	 */
	private function setup_actions() {

		add_action( 'plugin_action_links',               array( $this, 'plugin_action_links' ), 10, 2 );
		add_filter( 'network_admin_plugin_action_links', array( $this, 'plugin_action_links' ), 10, 2 );

		/** Core *************************************************************/

		add_action( 'admin_init',               array( $this, 'register_settings'  ) );
		add_action( 'admin_init',               array( $this, 'hook_admin_message' ) );
		add_action( 'wp_ajax_' . $this->notice, array( $this, 'admin_store_notice' ) );
		add_action( 'admin_enqueue_scripts',    array( $this, 'enqueue_scripts'    ) );

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
	 * @since 1.1.0
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
	 * Hook admin error when system cannot connect to Gravatar.com
	 *
	 * @since 1.0.0
	 */
	public function hook_admin_message() {

		// Bail if user cannot (de)activate plugins
		if ( ! current_user_can( 'activate_plugins' ) || get_option( $this->notice ) ) 
			return;

		$user = get_userdata( get_current_user_id() );

		// Check if the system can connect to Gravatar.com
		if ( $response = wp_remote_head( sprintf( 'http://%d.gravatar.com/avatar/%s?d=404', hexdec( $user->user_email ) % 2, md5( $user->user_email ) ) ) ) {
			
			// Connection failed
			if ( is_wp_error( $response ) ) {
				add_action( 'admin_notices', array( $this, 'admin_notice' ) );		
			}
		}
	}

	/**
	 * Output admin message to remind user of Gravatar.com connectivity fail
	 *
	 * @since 1.0.0
	 */
	public function admin_notice() { ?>

		<div id="initials-default-avatar_notice">
			<div id="initials-default-avatar_note1" class="error">
				<p>
					<?php esc_html_e( 'It seems that your site cannot connect to gravatar.com to check user profiles. Note that Initials Default Avatar may overwrite a valid gravatar with a default avatar.', 'initials-default-avatar' ); ?>
					<a class="dismiss" href="#"><?php esc_html_e( 'Accept', 'initials-default-avatar' ); ?></a><img class="hidden" src="<?php echo admin_url('/images/wpspin_light.gif'); ?>" style="vertical-align: middle; margin-left: 3px;" />
					<script type="text/javascript">
						jQuery(document).ready( function($){
							var $this = $('#initials-default-avatar_note1');
							$this.on('click', '.dismiss', function(e){
								e.preventDefault();
								$this.find('.hidden').show();
								$.post( ajaxurl, { 'action': '<?php echo $this->notice; ?>' }, function(){
									$this.hide().siblings('.error').show();
								} );
							});
						});
					</script>
				</p>
			</div>

			<div id="initials-default-avatar_note2" class="error hidden">
				<p>
					<?php esc_html_e( 'Initials Default Avatar has calls to gravatar.com now disabled. Reactivate the plugin to undo.', 'initials-default-avatar' ); ?>
					<a class="close" href="#"><?php esc_html_e( 'Close', 'initials-default-avatar' ); ?></a>
					<script type="text/javascript">
						jQuery(document).ready( function($){
							$('#initials-default-avatar_note2').on('click', 'close', function(e){
								e.preventDefault();
								$('#initials-default-avatar_notice').remove();
							});
						});
					</script>
				</p>
			</div>
		</div>

		<?php
	}

	/**
	 * Store notice option in database
	 *
	 * @since 1.0.0
	 */
	public function admin_store_notice() {
		update_option( $this->notice, true );
	}
}

/**
 * Setup the plugin admin class
 *
 * @since 1.1.0
 *
 * @uses Initials_Default_Avatar_Admin
 */
function initials_default_avatar_admin() {
	initials_default_avatar()->admin = new Initials_Default_Avatar_Admin;
}

endif; // class_exists
