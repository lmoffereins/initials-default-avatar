<?php

/**
 * The Initials Default Avatar Plugin
 *
 * @package Initials Default Avatar
 * @subpackage Main
 */

/**
 * Plugin Name:       Initials Default Avatar
 * Description:       Use colorful inital-based default avatars
 * Plugin URI:        https://github.com/lmoffereins/initials-default-avatar
 * Author:            Laurens Offereins
 * Author URI:        https://github.com/lmoffereins
 * Version:           1.0.0
 * Text Domain:       initials-default-avatar
 * Domain Path:       /languages/
 * GitHub Plugin URI: lmoffereins/initials-default-avatar
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Initials_Default_Avatar' ) ) :
/**
 * The Initials Default Avatar Plugin Class
 *
 * @since 1.0.0
 *
 * @todo Setup uninstall procedure
 */
final class Initials_Default_Avatar {

	/**
	 * Holds initials default avatar key
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public $avatar_key = 'initials';

	/**
	 * Flag for the sample intials default avatar
	 *
	 * @since 1.0.0
	 * @var bool
	 */
	public $is_sample = false;

	/**
	 * Holds all users on the page with their avatar params (colors)
	 *
	 * @since 1.0.0
	 * @var array
	 */
	private $avatars = array();

	/**
	 * Admin notice option key
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public $notice = 'initials-default-avatar_notice';

	/**
	 * The selected placeholder service name
	 *
	 * @since 1.0.0
	 * @var bool|string
	 */
	public $service = false;

	/**
	 * Collection of registerd services
	 *
	 * @since 1.1.0
	 * @var array
	 */
	private $services = array();

	/**
	 * All saved services options
	 *
	 * @since 1.0.0
	 * @var array
	 */
	public $options = array();

	/**
	 * Default font size
	 *
	 * @since 1.0.0
	 * @var int
	 */
	public $default_fontsize = 65;

	/**
	 * Holds all processed email hashes
	 *
	 * @since 1.1.0
	 * @var array
	 */
	public $email_hashes = array();

	/**
	 * Setup and return the singleton pattern
	 *
	 * @since 1.0.0
	 *
	 * @uses Initials_Default_Avatar::setup_globals()
	 * @uses Initials_Default_Avatar::requires()
	 * @uses Initials_Default_Avatar::setup_actions()
	 * @return The single Initials_Default_Avatar
	 */
	public static function instance() {

		// Store instance locally
		static $instance = null;

		if ( null === $instance ) {
			$instance = new Initials_Default_Avatar;
			$instance->setup_globals();
			$instance->requires();
			$instance->setup_actions();
		}

		return $instance;
	}

	/**
	 * Prevent the plugin class from being loaded more than once
	 */
	private function __construct() { /* Nothing to do */ }

	/** Private methods *************************************************/

	/**
	 * Setup default class globals
	 *
	 * @since 1.0.0
	 */
	private function setup_globals() {

		/** Versions **********************************************************/

		$this->version      = '1.0.0';

		/** Paths *************************************************************/

		// Setup some base path and URL information
		$this->file         = __FILE__;
		$this->basename     = plugin_basename( $this->file );
		$this->plugin_dir   = plugin_dir_path( $this->file );
		$this->plugin_url   = plugin_dir_url ( $this->file );

		// Includes
		$this->includes_dir = trailingslashit( $this->plugin_dir . 'includes' );
		$this->includes_url = trailingslashit( $this->plugin_url . 'includes' );

		// Assets
		$this->assets_dir   = trailingslashit( $this->plugin_dir . 'assets' );
		$this->assets_url   = trailingslashit( $this->plugin_url . 'assets' );

		// Languages
		$this->lang_dir     = trailingslashit( $this->plugin_dir . 'languages' );

		/** Misc **************************************************************/

		$this->extend       = new stdClass();
		$this->domain       = 'initials-default-avatar';
	}

	/**
	 * Include the required files
	 *
	 * @since 1.1.0
	 */
	private function requires() {
		require( $this->includes_dir . 'actions.php'           );
		require( $this->includes_dir . 'functions.php'         );
		require( $this->includes_dir . 'services.php'          );
		require( $this->includes_dir . 'sub-actions.php'       );
		require( $this->includes_dir . 'extend/buddypress.php' );
	}

	/**
	 * Setup default actions and filters
	 *
	 * @since 1.0.0
	 */
	private function setup_actions() {

		// (de)Activation
		add_action( 'activate_'   . $this->basename, 'initials_default_avatar_activation'   );
		add_action( 'deactivate_' . $this->basename, 'initials_default_avatar_deactivation' );

		// Load textdomain
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );

		// Settings
		add_filter( 'avatar_defaults',                  array( $this, 'avatar_defaults'       )        );
		add_action( 'admin_init',                       array( $this, 'register_settings'     )        );
		add_filter( 'pre_update_option_avatar_default', array( $this, 'save_previous_default' ), 10, 2 );

		// Admin
		add_action( 'admin_init',                       array( $this, 'hook_admin_message'  )        );
		add_action( 'wp_ajax_' . $this->notice,         array( $this, 'admin_store_notice'  )        );
		add_action( 'admin_enqueue_scripts',            array( $this, 'enqueue_scripts'     )        );
		add_action( 'plugin_action_links',              array( $this, 'plugin_action_links' ), 10, 2 );
	}

	/** Plugin **********************************************************/

	/**
	 * Load the translation file for current language. Checks the languages
	 * folder inside the plugin first, and then the default WordPress
	 * languages folder.
	 *
	 * Note that custom translation files inside the plugin folder will be
	 * removed on plugin updates. If you're creating custom translation
	 * files, please use the global language folder.
	 *
	 * @since 1.0.0
	 */
	public function load_textdomain() {

		// Traditional WordPress plugin locale filter
		$locale        = apply_filters( 'plugin_locale', get_locale(), $this->domain );
		$mofile        = sprintf( '%1$s-%2$s.mo', $this->domain, $locale );

		// Setup paths to current locale file
		$mofile_local  = $this->lang_dir . $mofile;
		$mofile_global = WP_LANG_DIR . '/initials-default-avatar/' . $mofile;

		// Look in global /wp-content/languages/initials-default-avatar folder
		load_textdomain( $this->domain, $mofile_global );

		// Look in local /wp-content/plugins/initials-default-avatar/languages/ folder
		load_textdomain( $this->domain, $mofile_local );

		// Look in global /wp-content/languages/plugins/
		load_plugin_textdomain( $this->domain );
	}

	/**
	 * Magic getter
	 *
	 * @since 1.1.0
	 *
	 * @param string $key Property key
	 * @return mixed|null Property value or null when not found
	 */
	public function __get( $key ) {
		if ( isset( $this->data[ $key ] ) ) {
			return $this->data[ $key ];
		}

		return null;
	}

	/** Avatar ****************************************************************/

	/**
	 * Return the details of the given avatar (user)
	 *
	 * @since 1.1.0
	 *
	 * @uses apply_filters() Calls 'initials_default_avatar_user_name'
	 * @uses apply_filters() Calls 'initials_default_avatar_user_data'
	 * @uses apply_filters() Calls 'initials_default_avatar_get_avatar_details'
	 * 
	 * @param int|string $avatar_id Avatar identifier
	 * @param string $name Suggested avatar holder name
	 * @return array Avatar details
	 */
	public function get_avatar_details( $avatar_id = 0, $name = '' ) {
		$user = false;

		// Accept WP_User objects
		if ( $avatar_id instanceof WP_User ) {
			$user = $avatar_id;
			$avatar_id = $avatar_id->ID;
		}

		// Is avatar not yet created? Let's do this then
		if ( ! $details = $this->_get_avatar_details( $avatar_id ) ) {

			// User
			if ( $avatar_id && is_numeric( $avatar_id ) ) {
				if ( ! $user ) {
					$user = get_user_by( 'id', (int) $avatar_id );
				}
				if ( empty( $name ) && $user ) {
					$name = trim( $user->first_name . ' ' . $user->last_name );
					if ( empty( $name ) ) {
						$name = ! empty( $user->display_name ) ? $user->display_name : $user->user_login;
					}
				}

			// Email address
			} elseif ( empty( $name ) && is_email( $avatar_id ) ) {
				$name = $avatar_id;
			}

			// Filter name for back-compat
			$name = apply_filters( 'initials_default_avatar_user_name', $name, $avatar_id );

			// Default to the unknown
			if ( empty( $name ) ) {
				$name = _x( 'X', 'Initial(s) for the unknown', 'initials-default-avatar' );
			}

			// Define base details
			$details             = initials_default_avatar_generate_colors();
			$details['id']       = $avatar_id;
			$details['initials'] = initials_default_avatar_get_initials( $name );

			// Filter details for back-compat
			$details = apply_filters( 'initials_default_avatar_user_data', $details, $avatar_id, $name );

			// Set avatar details
			$this->avatars[ $avatar_id ] = $details;
		}

		// Filter and return avatar details
		return apply_filters( 'initials_default_avatar_get_avatar_details', $details, $avatar_id, $name, $user );
	}

	/**
	 * Return avatar details internallly
	 *
	 * @since 1.1.0
	 * 
	 * @param int|string $id Avatar key
	 * @return array Avatar details
	 */
	private function _get_avatar_details( $id ) {
		if ( isset( $this->avatars[ $id ] ) ) {
			return (array) $this->avatars[ $id ];
		} else {
			return false;
		}
	}

	/** Service ***************************************************************/

	/**
	 * Register a single placeholder service
	 *
	 * @since 1.1.0
	 *
	 * @param string $service Service name
	 * @param array $args {
	 *     Array of service arguments
	 *
	 *     @type string      $title       Service title. Required.
	 *     @type string      $url         Service url which will return the service's image. Required.
	 *                                    Optionally one or more url parameters may be included, but they must be
	 *                                    wrapped in {}. Accepts 'width', 'height', 'color', 'bgcolor', and 'text'.
	 *     @type bool|string $format_pos  Position after which the image format parameter is positioned.
	 *     @type array       $query_args  An array of additional query arguments as key => parameter name. Accepts
	 *                                    'width', 'height', 'font', 'color', 'bgcolor', 'bordercolor', and 'text'.
	 *     @type array       $options     An array of configurable service options. Each option should be an array
	 *                                    containing at least a label and a type, and optionally additional parameters.
	 * }
	 * @return bool Serivce was registered
	 */
	public function register_service( $service, $args = array() ) {

		// Parse defaults
		$args = wp_parse_args( $args, array(
			'name'       => '',
			'title'      => '',
			'url'        => '',
			'query_args' => array(),
			'options'    => array()
		) );

		// Bail when parameters are missing
		if ( empty( $service ) || empty( $args['title'] ) || empty( $args['url'] ) )
			return false;

		$args['name'] = $service;

		// Add to the services collection
		$this->services[ $service ] = (object) $args;

		return true;
	}

	/**
	 * Unregister a single placeholder service
	 *
	 * @since 1.1.0
	 *
	 * @param string $service Service name
	 * @return bool Service was unregistered
	 */
	public function unregister_service( $service ) {
		if ( isset( $this->services[ $service ] ) ) {
			unset( $this->services[ $service ] );
			return true;
		}

		return false;
	}

	/**
	 * Return all registered placeholder services
	 *
	 * @since 1.1.0
	 *
	 * @uses apply_filters() Calls 'initials_default_avatar_services'
	 * @return array Service objects
	 */
	public function get_services() {
		return (array) apply_filters( 'initials_default_avatar_services', $this->services );
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

	/**
	 * Add Initials to the default avatar alternatives
	 *
	 * @since 1.0.0
	 * 
	 * @param array $defaults Current defaults
	 * @return array $defaults
	 */
	public function avatar_defaults( $defaults ) {

		// Add initials default avatar and set sample flag
		$defaults[ initials_default_avatar_get_avatar_key() ] = __( 'Initials (Generated)', 'initials-default-avatar' );
		$this->is_sample = true;

		return $defaults;
	}

	/**
	 * Store previous default avatar when switching to initials
	 *
	 * @since 1.0.0
	 *
	 * @param string $new_value Current avatar selection
	 * @param string $old_value Previous avatar selection
	 */
	public function save_previous_default( $new_value, $old_value ) {

		// Save the previous avatar selection for later
		if ( initials_default_avatar_is_initials_avatar( $new_value ) && $new_value !== $old_value ) {
			update_option( 'initials_default_avatar_previous', $old_value );
		}

		return $new_value;
	}

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
		if ( $this->basename === $file ) {
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
			array( $this, 'admin_setting_placeholder_service' ),
			'discussion',
			'avatars'
		);

		// Register settings: service and service settings.
		register_setting( 'discussion', 'initials_default_avatar_service', array( $this, 'sanitize_service'         ) );
		register_setting( 'discussion', 'initials_default_avatar_options', array( $this, 'sanitize_service_options' ) );
	}

	/**
	 * Output JavaScript to bring our field in parity with WP 4.2's discussion js
	 *
	 * @since 1.1.0
	 *
	 * @see options_discussion_add_js()
	 */
	public function settings_add_js() { ?>
		<script>
		( function($) {
			var show_avatars = $( '#show_avatars' ),
			    avatar_default = $( 'input[name="avatar_default"]' ),
			    avatar_key     = '<?php echo initials_default_avatar_get_avatar_key(); ?>',
			    settings_field = $( '#initials-default-avatar' ).parents( 'tr' ).first();

			// Add classes to our field's parent <tr>
			settings_field.addClass( function() {
				var c = 'avatar-settings';

				// Hide field when avatars are not in use
				if ( ! show_avatars.is( ':checked' ) ) {
					c += ' hide-if-js';
				}

				// Hide field when our default is not selected
				if ( avatar_default.filter( ':checked' ).val() != avatar_key ) {
					c += ' hidden';
				}

				return c;
			});

			// Show service settings on default selection
			avatar_default.change( function() {
				settings_field.toggleClass( 'hidden', this.value != avatar_key );
			});

		})( jQuery );
		</script>
		<?php
	}

	/**
	 * Enqueue scripts in the admin head on settings pages
	 *
	 * @since 1.0.0
	 */
	public function enqueue_scripts( $hook_suffix ) {

		// Bail if not on the discussion page
		if ( 'options-discussion.php' != $hook_suffix )
			return;

		// Register
		wp_register_script( 'initials-default-avatar', $this->assets_url . 'js/initials-default-avatar.js', array( 'jquery' ), $this->version, true );

		// Enqueue
		wp_enqueue_script( 'initials-default-avatar' );
		wp_enqueue_script( 'wp-color-picker' );
		wp_enqueue_style( 'wp-color-picker' );
	}

	/**
	 * Output settings field for the placeholder service
	 *
	 * @since 1.0.0
	 */
	public function admin_setting_placeholder_service() {
		$selected = get_option( 'initials_default_avatar_service' ); ?>

		<div id="initials-default-avatar">
			<label for="placeholder-service">
				<select name="initials_default_avatar_service" id="placeholder-service">
					<option value=""><?php _e( 'Select a service', 'initials-default-avatar' ); ?></option>
					<?php foreach ( initials_default_avatar_get_services() as $service => $args ) : ?>

					<option value="<?php echo $service; ?>" <?php selected( $selected, $service ); ?>><?php echo $args->title; ?></option>

					<?php endforeach; ?>
				</select>
				<?php _e( 'Select a placeholder service.', 'initials-default-avatar' ); ?>
				<span class="learn-more"><?php printf( __( 'See %s for more information.', 'initials-default-avatar' ), sprintf( '<a class="service-url" target="_blank" href="http://%1$s">%1$s</a>', $service ) ); ?></span>
			</label>

			<?php $this->admin_setting_service_options(); ?>
		</div>

		<?php

		// Enqueue script. See wp-admin/options-discussion.php since WP 4.2.
		add_action( 'admin_print_footer_scripts', array( $this, 'settings_add_js' ), 9 );
	}

	/**
	 * Output settings fields for service options
	 *
	 * @since 1.0.0
	 */
	public function admin_setting_service_options() {
		$current = initials_default_avatar_get_service();

		// Define sample avatar details
		$details = initials_default_avatar_get_avatar_details( 0, _x( 'Sample', 'default avatar display name', 'initials-default-avatar' ) );
		$args    = array( 'size' => 100 );

		// Loop all services if they have options defined
		foreach ( initials_default_avatar_get_services() as $service ) :

			// Hide non-selected services
			$style = ( $current->name !== $service->name ) ? 'style="display:none;"' : ''; ?>

			<div id="service-<?php echo $service->name; ?>" class="service-options" <?php echo $style; ?>>
				<h4 class="title"><?php _e( 'Service options', 'initials-default-avatar' ); ?></h4>

				<div class="avatar-preview" style="float:left; margin-right: 10px;">
					<img src="<?php echo initials_default_avatar_get_avatar_url( $details, $args, $service->name ); ?>" class="<?php echo initials_default_avatar_get_avatar_class( '', $args, $service->name ); ?>" width="100" height="100" />
				</div>

				<?php if ( isset( $service->options ) ) : ?>

				<div class="options" style="float:left;">
					<?php foreach ( $service->options as $field => $option ) : ?>

					<?php $this->admin_setting_service_option_field( $service, $field, $option ); ?><br>

					<?php endforeach; ?>
				</div>

				<?php endif; ?>

			</div>

		<?php endforeach;
	}

	/**
	 * Output the service option field
	 *
	 * @since 1.0.0
	 *
	 * @todo Position label next to color input
	 *
	 * @param string $service Service name
	 * @param string $field Option field name
	 * @param array $args Option args
	 */
	public function admin_setting_service_option_field( $service, $field, $args ) {
		$service = initials_default_avatar_get_service( $service );

		// Bail when the service does not exist
		if ( ! $service )
			return;

		// Field is set without arguments
		if ( ! is_array( $args ) ) {
			$field = $args;
			$args  = array();
		}

		$s       = $service;
		$service = $service->name;

		// Setup field atts
		$id    = "initials-default-avatar-options-{$service}-{$field}";
		$name  = "initials_default_avatar_options[{$service}][{$field}]";
		$value = initials_default_avatar_get_service_option( $field, $service );
		if ( empty( $value ) ) {
			$value = '';
		}
		$label = isset( $args['label'] ) ? $args['label'] : '';

		// Setup font size vars
		if ( 'fontsize' == $field ) {
			$label = __( 'Font size in percentage', 'initials-default-avatar' );
			$args['type'] = 'percentage';
		}

		// What type is this field?
		switch ( $args['type'] ) {

			case 'select' :
				$value = esc_attr( $value );
				if ( empty( $value ) ) {
					$value = key( $s->options[ $field ]['options'] );
				}

				$input  = "<select name='$name' id='$id' class='service-option'>";
				$input .=	'<option>' . __( 'Select an option', 'initials-default-avatar' ) . '</option>';
				foreach ( $args['options'] as $option => $option_label ) {
					$input .= "<option value='$option'" . selected( $value, $option, false ) . ">$option_label</option>";
				}
				$input .= '</select>';
				break;

			case 'text' :
				$value = esc_attr( $value );
				$input = "<input type='text' name='{$name}' id='{$id}' class='service-option regular-text' value='{$value}' />";
				break;

			case 'number'  :
				$value = esc_attr( $value );
				$input = "<input type='number' name='{$name}' id='{$id}' class='service-option small-text' value='{$value}' />";
				break;

			case 'percentage'  :
				$value = esc_attr( $value );
				if ( empty( $value ) && 'fontsize' == $field )
					$value = $this->default_fontsize;

				$input = "<input type='number' name='{$name}' id='{$id}' class='service-option small-text' value='{$value}' step='1' min='0' max='99' />";
				break;

			case 'textarea' :
				$value = esc_textarea( $value );
				$input = "<textarea name='{$name}' id='{$id}' class='service-option'>{$value}</textarea>";
				break;

			case 'color' :
				$value = esc_attr( $value );
				$input = "<input type='text' name='{$name}' id='{$id}' class='service-option ida-wp-color-picker' value='#{$value}' />";
				break;

			default :
				$input = apply_filters( 'initials_default_avatar_service_option_field_input', '', $service, $field, compact( 'args', 'id', 'name', 'value' ) );
				break;
		}

		// Setup field with input label
		$_field = "<label for='{$id}' class='option-{$args['type']}'>{$input} <span class='description'>{$label}</span></label>";

		// Output break, input, label
		echo apply_filters( 'initials_default_avatar_service_option_field', $_field, $service, $field, $args );
	}

	/**
	 * Sanitize selected service setting
	 *
	 * @since 1.0.0
	 * 
	 * @param string $input Service selected
	 * @return string|bool Sanitized input
	 */
	public function sanitize_service( $input ) {

		// Service selected exists
		if ( ! initials_default_avatar_get_service( $input ) ) {
			$input = false;
		}

		return $input;
	}

	/**
	 * Sanitize selected service options setting
	 *
	 * @since 1.0.0
	 *
	 * @todo Fix color input sanitization
	 *
	 * @param array $input Service options
	 * @return array|false Sanitized value
	 */
	public function sanitize_service_options( $input ) {

		// Setup clean value
		$value = array();

		// Loop all services
		foreach ( $input as $service => $options ) {
			$_service = initials_default_avatar_get_service( $service );
			$value[ $service ] = array();

			foreach ( $options as $option => $_input ) {

				// Indicate font size option type
				if ( 'fontsize' == $option )
					$_service->options[ $option ]['type'] = 'percentage';

				// Sanitize per option type
				switch ( $_service->options[ $option ]['type'] ) {

					case 'number'     :
					case 'percentage' :
						$_input = absint( $_input );
						break;

					case 'select' :
						if ( ! in_array( $_input, array_keys( $_service->options[ $option ]['options'] ) ) )
							$_input = false;
						break;

					case 'text'     :
					case 'textarea' :
						$_input = wp_kses( $_input );
						break;

					case 'color' :
						// preg_match('/#([0-9abcdef]+?){3,6}/i', $_input, $matches);
						// if ( $matches[1] )
						// 	$_input = $matches[1];
						// else
						// 	$_input = false;
						
						// Strip hex hash sign
						$_input = str_replace( '#', '', $_input );
						break;
				}

				$value[ $service ][ $option ] = $_input;
			}
		}

		return apply_filters( 'initials_default_avatar_sanitize_service_options', $value );
	}
}

/**
 * Return single instance of this main plugin class
 *
 * @since 1.1.0
 * 
 * @return Initials_Default_Avatar
 */
function initials_default_avatar() {
	return Initials_Default_Avatar::instance();
}

// Initiate plugin. Set global for back-compat
$GLOBALS['initials_default_avatar'] = initials_default_avatar();

endif; // class_exists
