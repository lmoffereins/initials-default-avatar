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
		$this->db_version   = 20180930;

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

		/** Core **************************************************************/

		require( $this->includes_dir . 'actions.php'     );
		require( $this->includes_dir . 'functions.php'   );
		require( $this->includes_dir . 'sub-actions.php' );
		require( $this->includes_dir . 'update.php'      );

		/** Admin *************************************************************/

		if ( is_admin() ) {
			require( $this->includes_dir . 'admin.php' );
		}

		/** Extensions ********************************************************/

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

		// Register assets
		add_action( 'initials_default_avatar_register_services', array( $this, 'register_services' ) );

		// Settings
		add_filter( 'avatar_defaults',                  array( $this, 'avatar_defaults'       )        );
		add_filter( 'pre_update_option_avatar_default', array( $this, 'save_previous_default' ), 10, 2 );
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

	/** Service ***************************************************************/

	/**
	 * Register default plugin placeholder services
	 *
	 * Default registered services (with options)
	 *  - dummyimage.com
	 *  - ipsumimage.appspot.com (font size)
	 *  - placehold.it
	 *  - fakeimg.pl
	 *
	 * Deprecated services
	 *  - cambelt.co (font, font size)
	 *  - getdummyimage.com (border color)
	 *  - imageholdr.com
	 *  - placebox.es (font size)
	 *
	 * Other unsupported services
	 *  - lorempixel.com: no color backgrounds
	 *  - placeIMG.com: no color backgrounds
	 *  - fpooimg.com: uses image dimensions for text
	 *  - p-hold.com: no color backgrounds, no text
	 *  - lorempics.com: no text
	 *  - xoart.link: no color backgrounds
	 *
	 * @since 1.1.0
	 */
	public function register_services() {

		/** Dummy Image ***********************************************************/

		initials_default_avatar_register_service(
			'dummyimage.com',
			array(
				'title'      => 'Dummy Image',
				'url'        => 'http://dummyimage.com/{width}x{height}/{bgcolor}/{color}',
				'format_pos' => 'height',
				'query_args' => array(
					'text' => 'text',
				),
			)
		);

		/** Ipsum Image ***********************************************************/

		initials_default_avatar_register_service(
			'ipsumimage.com',
			array(
				'title'      => 'Ipsum Image',
				'url'        => 'http://ipsumimage.appspot.com/{width}x{height}',
				'format_pos' => false,
				'query_args' => array(
					'b' => 'bgcolor',
					'f' => 'color',
					'l' => 'text',
					's' => 'fontsize',
					't' => 'format'
				),
				'options'    => array(
					'fontsize',
				),
			)
		);

		/** Placehold It **********************************************************/

		initials_default_avatar_register_service(
			'placehold.it',
			array(
				'title'      => 'Placehold It',
				'url'        => 'http://placehold.it/{width}x{height}/{bgcolor}/{color}',
				'format_pos' => 'height',
				'query_args' => array(
					'text' => 'text'
				),
			)
		);

		/** Fake Images Please ****************************************************/

		initials_default_avatar_register_service(
			'fakeimg.pl',
			array(
				'title'      => 'Fake Images Please',
				'url'        => 'http://fakeimg.pl/{width}x{height}/{bgcolor}/{color}/',
				'format_pos' => false,
				'query_args' => array(
					'font'      => 'font',
					'font_size' => 'fontsize',
					'text'      => 'text',
				),
				'options'    => array(
					'fontsize',
					'font'      => array(
						'label' => __( 'Font', 'initials-default-avatar' ),
						'type'  => 'select',
						'options' => array(
							'bebas'   => 'Bebas',
							'lobster' => 'Lobster',
							'museo'   => 'Museo',
						),
					)
				),
			)
		);
	}

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

	/**
	 * Add Initials to the default avatar alternatives
	 *
	 * @since 1.0.0
	 * 
	 * @param array $defaults Current defaults
	 * @return array $defaults
	 */
	public function avatar_defaults( $defaults ) {

		// Add our avatar option
		$defaults[ initials_default_avatar_get_avatar_key() ] = __( 'Initials (Generated)', 'initials-default-avatar' );

		// Register flag to signal the sample avatar
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
