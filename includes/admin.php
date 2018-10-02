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
	}

	/**
	 * Define default actions and filters
	 *
	 * @since 1.1.0
	 */
	private function setup_actions() {
		add_action( 'plugin_action_links',      array( $this, 'plugin_action_links' ), 10, 2 );
		add_action( 'admin_init',               array( $this, 'register_settings'   )        );
		add_action( 'admin_init',               array( $this, 'hook_admin_message'  )        );
		add_action( 'wp_ajax_' . $this->notice, array( $this, 'admin_store_notice'  )        );
		add_action( 'admin_enqueue_scripts',    array( $this, 'enqueue_scripts'     )        );
	}

	/** Plugin ****************************************************************/

	/**
	 * Add some to the plugin action links
	 *
	 * @since 1.0.0
	 * 
	 * @param array $links
	 * @param string $file Plugin basename
	 * @return array Links
	 */
	public function plugin_action_links( $links, $file ) {

		// Add links to our plugin actions
		if ( initials_default_avatar()->basename === $file ) {
			$links['settings'] = '<a href="' . admin_url( 'options-discussion.php' ) . '">' . esc_html__( 'Settings', 'initials-default-avatar' ) . '</a>';
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

		// Register settings field
		add_settings_field(
			'initials-default-avatar-service',
			__( 'Initials Default Avatar', 'initials-default-avatar' ),
			'initials_default_avatar_admin_setting_placeholder_service',
			'discussion',
			'avatars'
		);

		// Register setting for selected service with options
		register_setting( 'discussion', 'initials_default_avatar_service', 'initials_default_avatar_admin_sanitize_service'         );
		register_setting( 'discussion', 'initials_default_avatar_options', 'initials_default_avatar_admin_sanitize_service_options' );
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
		if ( 'options-discussion.php' !== $hook_suffix )
			return;

		// Enqueue admin scripts 'n styles
		wp_enqueue_script( 'initials-default-avatar-admin' );
		wp_localize_script( 'initials-default-avatar-admin', 'initialsDefaultAvatarAdmin', array(
			'settings' => array(
				'avatarKey' => initials_default_avatar_get_avatar_key()
			)
		) );
		wp_enqueue_style( 'initials-default-avatar-admin' );

		// Enqueue color picker
		wp_enqueue_script( 'wp-color-picker' );
		wp_enqueue_style( 'wp-color-picker' );
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
					<?php _e( 'It seems that your site cannot connect to gravatar.com to check user profiles. Note that Initials Default Avatar may overwrite a valid gravatar with a default avatar.', 'initials-default-avatar' ); ?>
					<a class="dismiss" href="#"><?php _e( 'Accept', 'initials-default-avatar' ); ?></a><img class="hidden" src="<?php echo admin_url('/images/wpspin_light.gif'); ?>" style="vertical-align: middle; margin-left: 3px;" />
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
					<?php _e( 'Initials Default Avatar has calls to gravatar.com now disabled. Reactivate the plugin to undo.', 'initials-default-avatar' ); ?>
					<a class="close" href="#"><?php _e( 'Close', 'initials-default-avatar' ); ?></a>
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
