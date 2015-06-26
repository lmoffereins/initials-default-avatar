<?php

/**
 * The Initials Default Avatar Plugin
 *
 * @package Initials Default Avatar
 * @subpackage Main
 */

/**
 * Plugin Name:       Initials Default Avatar
 * Description:       Give your default avatars some text and random color love (inspired by Gmail).
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
 * Current services to choose from (options):
 *  - cambelt.co (font, font size)
 *  - dummyimage.com
 *  - getdummyimage.com (border color)
 *  - imageholdr.com
 *  - ipsumimage.appspot.com (font size)
 *  - placebox.es (font size)
 *  - placehold.it
 *
 * Not supported services
 *  - lorempixel.com: no color backgrounds
 *  - placeIMG.com: no color backgrounds
 *  - fpooimg.com: includes image dimensions as text
 *  - p-hold.com: no color backgrounds, no text
 *  - lorempics.com: no text
 *  - xoart.link: no color backgrounds
 *
 * @since 1.0.0
 *
 * @todo Check whether placeholder service is still live
 * @todo Support: fakeimg.pl (font, font size, retina): https://github.com/Rydgel/Fake-images-please
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
	 * @var boolean 
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
	public $notice = '';

	/**
	 * The selected placeholder service name
	 *
	 * @since 1.0.0
	 * @var string 
	 */
	public $service = false;

	/**
	 * All saved services options
	 *
	 * @since 1.0.0
	 * @var string 
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
	 * Setup and return the singleton pattern
	 *
	 * @since 1.0.0
	 *
	 * @uses Initials_Default_Avatar::setup_globals()
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

		// Languages
		$this->lang_dir     = trailingslashit( $this->plugin_dir . 'languages' );

		/** Misc **************************************************************/

		$this->extend       = new stdClass();
		$this->domain       = 'initials-default-avatar';

		$this->define_class_globals();
	}

	/**
	 * Define default class globals
	 *
	 * @since 1.0.0
	 */
	private function define_class_globals() {

		// Selected service
		$this->service = get_option( 'initials_default_avatar_service' );
		if ( empty( $this->service ) ) {
			$this->service = key( $this->placeholder_services() );
		}

		// Options
		$this->options = get_option( 'initials_default_avatar_options' );
		if ( empty( $this->options ) ) {
			$this->options = array();
		}

		// Notice
		$this->notice = 'initials-default-avatar_notice';

		// Avatar default details
		$this->avatars = array( 'user' => array() );

		// Checked email hashes
		$this->email_hashes = array();
	}

	/**
	 * Include the required files
	 *
	 * @since 1.1.0
	 */
	private function requires() {
		require( $this->includes_dir . 'buddypress.php' );
	}

	/**
	 * Setup default actions and filters
	 *
	 * @since 1.0.0
	 *
	 * @uses add_action()
	 * @uses add_filter()
	 */
	private function setup_actions() {

		// Load textdomain
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );

		// Avatar
		add_filter( 'get_avatar_data', array( $this, 'get_avatar_data' ), 10, 2 );

		// Back-compat < WP 4.2.0
		if ( ! function_exists( 'get_avatar_data' ) ) {
			add_filter( 'get_avatar', array( $this, 'get_avatar' ), 10, 5 );
		}

		// Settings
		add_filter( 'avatar_defaults',                  array( $this, 'avatar_defaults'       )        );
		add_action( 'admin_init',                       array( $this, 'register_settings'     )        );
		add_filter( 'pre_update_option_avatar_default', array( $this, 'save_previous_default' ), 10, 2 );

		// Admin
		add_action( 'admin_init',                       array( $this, 'hook_admin_message'  )        );
		add_action( 'wp_ajax_' . $this->notice,         array( $this, 'admin_store_notice'  )        );
		add_action( 'admin_enqueue_scripts',            array( $this, 'enqueue_scripts'     )        );
		add_action( 'plugin_action_links',              array( $this, 'plugin_action_links' ), 10, 2 );

		// Extensions
		add_action( 'bp_init', 'initials_default_avatar_buddypress' );

		// Deactivation
		add_action( 'deactivate_' . $this->basename, array( $this, 'deactivate' ) );
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
	 *
	 * @uses apply_filters() Calls 'plugin_locale' with {@link get_locale()} value
	 * @uses load_textdomain() To load the textdomain
	 * @uses load_plugin_textdomain() To load the textdomain
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

	/** Avatar ****************************************************************/

	/**
	 * Return avatar data when we serve a default avatar
	 *
	 * @since 1.1.0
	 *
	 * @uses Initials_Default_Avatar::is_valid_gravatar()
	 * @uses get_user_by()
	 * @uses Initials_Default_Avatar::get_avatar_details()
	 * @uses Initials_Default_Avatar::get_avatar_url()
	 * @uses Initials_Default_Avatar::get_avatar_class()
	 * 
	 * @param array $args Avatar data
	 * @param mixed $id_or_email Avatar object identifier
	 * @return array $args
	 */
	public function get_avatar_data( $args, $id_or_email ) {

		// Bail when we're not serving the avatar default
		if ( $args['default'] !== $this->avatar_key )
			return $args;

		/**
		 * NOTE: $args['found_avatar'] may be true, but it only says an email
		 * was hashed from the $id_or_email var. We do not know if the avatar
		 * really exists with the Gravatar service.
		 */

		// Bail when we do not need step in
		if ( ! $this->is_sample && $args['found_avatar'] && $this->is_valid_gravatar( $args['url'] ) )
			return $args;

		// Define local variable(s)
		$user = $email = $name = false;

		// Process the user identifier.
		if ( is_numeric( $id_or_email ) ) {
			$user = get_user_by( 'id', absint( $id_or_email ) );

		// Email address
		} elseif ( is_string( $id_or_email ) ) {
			$email = $id_or_email;

		// User Object
		} elseif ( $id_or_email instanceof WP_User ) {
			$user = $id_or_email;

		// Post Object
		} elseif ( $id_or_email instanceof WP_Post ) {
			$user = get_user_by( 'id', (int) $id_or_email->post_author );

		// Comment Object
		} elseif ( is_object( $id_or_email ) && isset( $id_or_email->comment_ID ) ) {
			$allowed_comment_types = apply_filters( 'get_avatar_comment_types', array( 'comment' ) );
			if ( ! empty( $id_or_email->comment_type ) && ! in_array( $id_or_email->comment_type, (array) $allowed_comment_types ) ) {
				return $args;
			}

			if ( ! empty( $id_or_email->user_id ) ) {
				$user = get_user_by( 'id', (int) $id_or_email->user_id );
			}
			if ( ( ! $user || is_wp_error( $user ) ) && ! empty( $id_or_email->comment_author_email ) ) {
				$email = $id_or_email->comment_author_email;
				if ( ! empty( $id_or_email->comment_author ) ) {
					$name = $id_or_email->comment_author;
				}
			}
		}

		// Do we know this email?
		if ( $email ) {
			$user = get_user_by( 'email', $email );
		}

		// Get avatar details
		$details = $this->get_avatar_details( $user ? $user : $email, $name );

		// Redefine avatar data
		$args['found_avatar'] = false; // !
		$args['url']          = $this->get_avatar_url  ( $details,       $args );
		$args['class']        = $this->get_avatar_class( $args['class'], $args );

		return $args;
	}

	/**
	 * Check if we're not overwriting a valid Gravatar
	 *
	 * Since we don't know yet if we're here for a default avatar fallback or 
	 * Gravatar will match with an existing Gravatar account, we should check 
	 * which is the case. We cannot know from the <img> src attribute what
	 * image Gravatar will send us back. So first, we'll ask Gravatar if it
	 * knows the current email. If not, guaranteed we'll recieve a default.
	 *
	 * @since 1.0.0
	 *
	 * @uses wp_remote_head()
	 * @uses wp_remote_retrieve_response_code()
	 *
	 * @param string $avatar HTML image or image url
	 * @return bool Whether the given avatar is a valid gravatar
	 */
	public function is_valid_gravatar( $avatar ) {

		// Bail when the user chose to pass this test, but we might be overwritin'
		if ( get_option( $this->notice ) )
			return false;

		// Read the {/avatar/email_hash} part from the current avatar
		preg_match( '/avatar\/([^&]+)\?/', $avatar, $matches );

		// No email_hash found
		if ( ! isset( $matches[1] ) )
			return false;

		// Bail when we checked this email hash before
		if ( in_array( $matches[1], array_keys( $this->email_hashes ) ) )
			return $this->email_hashes[ $matches[1] ];

		// Setup 404 url to check what Gravatar knows
		$url = sprintf( 'http://%d.gravatar.com/avatar/%s?d=404', hexdec( $matches[1][0] ) % 2, $matches[1] );

		// Catch whether the email_hash is recognized by Gravatar
		$response = wp_remote_head( $url );

		// Expect 404 code when Gravatar does not know the email_hash
		if ( 404 === (int) wp_remote_retrieve_response_code( $response ) || is_wp_error( $response ) ) {
			$is_valid = false;
		} else {
			$is_valid = true;
		}

		// Register email hash checked
		$this->email_hashes[ $matches[1] ] = $is_valid;

		return $is_valid;
	}

	/**
	 * Return the details of the given avatar (user)
	 *
	 * @since 1.1.0
	 *
	 * @uses Initials_Default_Avatar::_get_avatar_details()
	 * @uses apply_filters() Calls 'initials_default_avatar_user_name'
	 * @uses apply_filters() Calls 'initials_default_avatar_user_data'
	 * @uses Initials_Default_Avatar::get_initials()
	 * @uses Initials_Default_Avatar::generate_colors()
	 * @uses apply_filters() Calls 'ida_get_avatar_details'
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
			$details = wp_parse_args( array(
				'initials' => $this->get_initials( $name )
			), $this->generate_colors() );

			// Filter details for back-compat
			$details = apply_filters( 'initials_default_avatar_user_data', $details, $avatar_id, $name );

			// Set avatar details
			$this->avatars[ $avatar_id ] = $details;
		}

		// Filter and return avatar details
		return apply_filters( 'ida_get_avatar_details', $details, $avatar_id, $name, $user );
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
	 * Setup and return the avatar url
	 *
	 * @since 1.1.0
	 *
	 * @param array $details Avatar details. See Initials_Default_Avatar::get_avatar_details()
	 * @param array $args Avatar data args
	 * @param string $service Optional. Service name
	 * @return string $avatar
	 */
	public function get_avatar_url( $details, $args, $service = '' ) {

		// Default to current service
		if ( ! empty( $service ) && is_string( $service ) ) {
			$service = $this->placeholder_services( $service );
		} elseif ( empty( $service ) ) {
			$service = $this->get_current_service();
		}

		$size = $args['size'];

		// Setup default args
		$args = array(
			'width'   => $size, 
			'height'  => $size, 
			'bgcolor' => $details['bgcolor'], 
			'color'   => $details['color'],
			'text'    => $details['initials'],
			'format'  => 'png',
		);
		
		// Fetch service data
		if ( isset( $service['options'] ) ) {

			// Walk options
			foreach ( (array) $service['options'] as $option => $opt_args ) {

				// Format has specified position
				if ( ! empty( $service['format_pos'] ) ) {
					$args[$service['format_pos']] .= '.' . $args['format'];
					unset( $args['format'] );
				}
			}
		}

		// Handle font when requested
		if ( $this->service_supports( 'font', $service ) ) {
			$font = $this->get_service_option('font', $service['name'] );

			// Default to first option
			if ( null === $font && 'select' == $service['options']['font']['type'] ) {
				$font = key( $service['options']['font']['options'] );
			}

			$args['font'] = $font;
		}

		// Handle font size when requested
		if ( $this->service_supports( 'fontsize', $service ) ) {

			// Get selected font size percentage
			$perc = $this->get_service_option('fontsize', $service['name'] );

			// Default font size
			if ( null === $perc ) {
				$perc = $this->default_fontsize;
			}

			// Calculate size
			$size = (int) ceil( (int) $args['height'] * ( $perc / 100 ) );

			// Limit size
			if ( isset( $opt_args['fontsize'] ) && ! empty( $opt_args['fontsize']['limit'] ) && $size > $opt_args['fontsize']['limit'] ) {
				$size = $limit;
			}

			$args['fontsize'] = $size;
		}

		// Per service
		switch ( $service['name'] ) {

			// Cambelt
			case 'cambelt.co' :

				// Combine background-color with font color
				$args['color'] = $args['bgcolor'] . ',' . $args['color'];
				break;

			// Get Dummy Image
			case 'getdummyimage.com' :

				// Colors need encoded hash sign
				$args['bgcolor'] = '%23' . $args['bgcolor'];
				$args['color']   = '%23' . $args['color'];

				// Border color
				$bordercolor = $this->get_service_option( 'bordercolor', $service['name'] );
				if ( $bordercolor ) {
					$args['bordercolor'] = '%23' . $bordercolor . '&border=on';
				}
				break;
		}

		// Setup the avatar url
		$url = $service['url'];

		// Filter src arguments
		$url_args = (array) apply_filters( 'initials_default_avatar_avatar_src_args', $args, $service, $details, $size );

		// Fill all url variables
		foreach ( $url_args as $r_key => $r_value ) {
			$url = preg_replace( '/{' . $r_key . '}/', $r_value, $url );
		}

		// Add url query args
		foreach ( $service['query_args'] as $query_key => $value_key ) {
			if ( isset( $url_args[ $value_key ] ) ) {
				$url = add_query_arg( $query_key, rawurlencode_deep( $url_args[ $value_key ] ), $url );
			}
		}

		return apply_filters( 'initials_default_avatar_avatar_src', $url, $service, $details, $size, $args );
	}

	/**
	 * Return the avatar class attribute value
	 *
	 * @since 1.0.0
	 * 
	 * @param string $class Current avatar class
	 * @param array $args Avatar args
	 * @param array $service Defaults to current service
	 * @return string Class
	 */
	public function get_avatar_class( $class, $args, $service = '' ) {

		// Default to current service
		if ( ! empty( $service ) && is_string( $service ) ) {
			$service = $this->placeholder_services( $service );
		} else {
			$service = $this->get_current_service();
		}
		
		// Collect avatar classes
		$classes = explode( ' ', $class );
		$classes = apply_filters( 'initials_default_avatar_avatar_class', array_merge( (array) $classes, array(
			'avatar', 
			'photo', 
			"avatar-{$args['size']}",
			"avatar-{$this->avatar_key}",
			"service-{$service['name']}"
		) ), $args, $service );

		// Create class string
		$classes = array_map( 'sanitize_html_class', array_unique( array_filter( $classes ) ) );
		$class = implode( ' ', $classes );

		return $class;
	}

	/**
	 * Return the first characters of all the name's words
	 *
	 * @since 1.1.0
	 *
	 * @uses Initials_Default_Avatar::get_first_char()
	 * 
	 * @param string $name Name to get initials from
	 * @return string Initials
	 */
	public function get_initials( $name ) {
		$initials = array();
		foreach ( preg_split( '/[^a-zA-Z\?\!]/', $name ) as $word ) {
			$initials[] = $this->get_first_char( $word );
		}

		return strtoupper( implode( '', $initials ) );
	}

	/**
	 * Return the first character (letter or number) of a string
	 *
	 * @since 1.0.0
	 *
	 * @uses apply_filters() Calls 'ida_get_first_char'
	 * 
	 * @param string $string
	 * @return string First char
	 */
	public function get_first_char( $string = '' ) {

		// Bail when empty
		if ( empty( $string ) )
			return '';

		// Get the first safe character
		$char = mb_substr( $string, 0, 1, 'utf-8' );

		return apply_filters( 'ida_get_first_char', $char, $string );
	}

	/**
	 * Return randomly generated avatar colors
	 * 
	 * @since 1.0.0
	 * 
	 * @return array Background color and color
	 */
	public function generate_colors() {

		// Only select color values that matter: between 60 and 195
		// Creating a happy palet
		$red   = (int) mt_rand( 60, 195 );
		$blue  = (int) mt_rand( 60, 195 );
		$green = (int) mt_rand( 60, 195 );

		$bgcolor = dechex( $red ) . dechex( $blue ) . dechex( $green );
		$color   = 'ffffff';

		return compact( 'bgcolor', 'color' );
	}

	/** Admin *****************************************************************/

	/**
	 * Hook admin error when system cannot connect to Gravatar.com
	 *
	 * @since 1.0.0
	 *
	 * @uses get_userdata()
	 * @uses wp_remote_head()
	 * @uses is_wp_error()
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
	 *
	 * @uses update_option()
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
		$defaults[ $this->avatar_key ] = __( 'Initials (Generated)', 'initials-default-avatar' );
		$this->is_sample = true;

		return $defaults;
	}

	/**
	 * Store previous default avatar when switching to initials
	 *
	 * @since 1.0.0
	 *
	 * @uses update_option()
	 *
	 * @param string $new_value Current avatar selection
	 * @param string $old_value Previous avatar selection
	 */
	public function save_previous_default( $new_value, $old_value ) {

		// Save the previous avatar selection for later
		if ( $this->avatar_key === $new_value && $new_value !== $old_value ) {
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
		if ( plugin_basename( __FILE__ ) == $file ) {
			$links['settings'] = '<a href="' . admin_url( 'options-discussion.php' ) . '">' . esc_html__( 'Settings', 'initials-default-avatar' ) . '</a>';
		}

		return $links;
	}

	/** Settings **************************************************************/

	/**
	 * Register plugin settings
	 *
	 * @since 1.0.0
	 *
	 * @uses add_settings_field()
	 * @uses register_setting()
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
			    avatar_key     = '<?php echo $this->avatar_key; ?>',
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
	 *
	 * @uses wp_register_script()
	 * @uses wp_enqueue_script()
	 * @uses wp_enqueue_style()
	 */
	public function enqueue_scripts( $hook_suffix ) {

		// Bail if not on the discussion page
		if ( 'options-discussion.php' != $hook_suffix )
			return;

		// Register
		wp_register_script( 'initials-default-avatar', trailingslashit( plugin_dir_url( __FILE__ ) ) . 'js/initials-default-avatar.js', array( 'jquery' ), '1.0.0', true );

		// Enqueue
		wp_enqueue_script( 'initials-default-avatar' );
		wp_enqueue_script( 'wp-color-picker' );
		wp_enqueue_style( 'wp-color-picker' );
	}

	/**
	 * Output settings field for the placeholder service
	 *
	 * @since 1.0.0
	 *
	 * @uses Initials_Default_Avatar::placeholder_services()
	 */
	public function admin_setting_placeholder_service() {
		$selected = $this->service; ?>

		<div id="initials-default-avatar">
			<label for="placeholder-service">
				<select name="initials_default_avatar_service" id="placeholder-service">

					<option value=""><?php _e( 'Select a service', 'initials-default-avatar' ); ?></option>
					<?php foreach ( $this->placeholder_services() as $service => $args ) : ?>

						<option value="<?php echo $service; ?>" <?php selected( $selected, $service ); ?>><?php echo $args['title']; ?></option>

					<?php endforeach; ?>

				</select>
				<?php _e( 'Select a placeholder service.', 'initials-default-avatar' ); ?>
				<span class="learn-more"><?php printf( __( 'See %s for more information.', 'initials-default-avatar' ), sprintf('<a class="service-url" target="_blank" href="http://%1$s">%1$s</a>', $selected ) ); ?></span>
			</label>

			<?php // Output settings fields for service options ?>
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
	 *
	 * @uses Initials_Default_Avatar::placeholder_services()
	 */
	public function admin_setting_service_options() {

		// Define sample avatar details
		$details = $this->get_avatar_details( 0, _x( 'Sample', 'Sample default avatar display name', 'initials-default-avatar' ) );
		$args    = array( 'size' => 100 );

		// Loop all services if they have options defined
		foreach ( $this->placeholder_services() as $service => $s_args ) : 

			// Hide non-selected services
			$style = ( $this->service != $service ) ? 'style="display:none;"' : ''; ?>

			<div id="service-<?php echo $service; ?>" class="service-options" <?php echo $style; ?>>
				<h4 class="title"><?php _e( 'Service options', 'initials-default-avatar' ); ?></h4>

				<div class="avatar-preview" style="float:left; margin-right: 10px;">
					<img src="<?php echo $this->get_avatar_url( $details, $args, $service ); ?>" class="<?php echo $this->get_avatar_class( '', $args, $service ); ?>" width="100" height="100" />
				</div>

				<?php if ( isset( $s_args['options'] ) ) : ?>

				<div class="options" style="float:left;">
					<?php foreach ( $s_args['options'] as $option => $o_args ) : ?>

					<?php $this->admin_setting_service_option_field( $service, $option, $o_args ); ?><br>

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

		// Field is set without arguments
		if ( ! is_array( $args ) ) {
			$field = $args;
			$args  = array();
		}

		// Setup field atts
		$id    = "initials-default-avatar-options-{$service}-{$field}";
		$name  = "initials_default_avatar_options[{$service}][{$field}]";
		$value = $this->get_service_option( $field, $service );
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
				if ( empty( $value ) && $s = $this->placeholder_services( $service ) ) {
					$value = key( $s['options'][$field]['options'] );
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
	 * @uses Initials_Default_Avatar::placeholder_services()
	 * @param string $input Service selected
	 * @return string|bool Sanitized input
	 */
	public function sanitize_service( $input ) {

		// Service selected exists
		if ( ! $this->placeholder_services( $input ) ) {
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
			$_service = $this->placeholder_services( $service );
			$value[$service] = array();

			foreach ( $options as $option => $_input ) {

				// Indicate font size option type
				if ( 'fontsize' == $option )
					$_service['options'][$option]['type'] = 'percentage';

				// Sanitize per option type
				switch ( $_service['options'][$option]['type'] ) {

					case 'number'     :
					case 'percentage' :
						$_input = absint( $_input );
						break;

					case 'select' :
						if ( ! in_array( $_input, array_keys( $_service['options'][$option]['options'] ) ) )
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

				$value[$service][$option] = $_input;
			}
		}

		return apply_filters( 'initials_default_avatar_sanitize_service_options', $value );
	}

	/** Services **************************************************************/

	/**
	 * Return the selected placeholder service data
	 *
	 * @since 1.0.0
	 *
	 * @uses Initials_Default_Avatar::placeholder_services()
	 * @return array Service
	 */
	public function get_current_service() {
		return $this->placeholder_services( $this->service );
	}

	/**
	 * Return the placeholder services that can be used
	 *
	 * @since 1.0.0
	 *
	 * @param string $service Requested service
	 * @return array|string|bool All services, one service or false
	 */
	public function placeholder_services( $service = '' ) {
		
		// Setup services
		$services = (array) apply_filters( 'initials_default_avatar_services', array(

			// Cambelt
			'cambelt.co' => array(
				'title'      => 'Cambelt',
				'url'        => 'http://cambelt.co/{width}x{height}/{text}',
				'format_pos' => false,
				'query_args' => array(
					'font'      => 'font',
					'font_size' => 'fontsize',
					'format'    => 'format',
					'color'     => 'color',
				),
				'options'    => array(
					'font'      => array(
						'label' => __( 'Font', 'initials-default-avatar' ),
						'type'  => 'select',

						/**
						 * Font list last checked: 29-11-2013
						 */
						'options' => array(
							'Cabin-Bold'         => 'Cabin Bold',
							'Cabin-Regular'      => 'Cabin Regular',
							'Carton-Slab'        => 'Carton Slab',
							'Cedarville-Cursive' => 'Cedarville Cursive',
							'Dyno-Bold'          => 'Dyno Bold',
							'Enriqueta-Regular'  => 'Enriqueta Regular',
							'Fredericka'         => 'Fredericka',
							'Fell-English'       => 'Fell English',
							'Governor'           => 'Governor',
							'Haymaker'           => 'Haymaker',
							'Homestead-Display'  => 'Homestead Display',
							'Homestead-Regular'  => 'Homestead Regular',
							'Junction'           => 'Junction',
							'Kameron-Regular'    => 'Kameron Regular',
							'Kuro-Regular'       => 'Kuro Regular',
							'Lancelot-Regular'   => 'Lancelot Regular',
							'LeagueScript'       => 'LeagueScript',
							'Lobster-Regular'    => 'Lobster Regular',
							'NewsCycle'          => 'NewsCycle',
							'Nomed'              => 'Nomed',
							'Oldenburg'          => 'Oldenburg',
							'Ostrich-Black'      => 'Ostrich Black',
							'Ostrich-Regular'    => 'Ostrich Regular',
							'Otama'              => 'Otama',
							'Prociono'           => 'Prociono',
							'Questrial'          => 'Questrial',
							'RBN'                => 'RBN',
							'Raleway-Thin'       => 'Raleway Thin',
							'Ribbon'             => 'Ribbon',
							'Sheep-Sans'         => 'Sheep Sans',
							'Sonus-Light'        => 'Sonus Light',
							'Speedway'           => 'Speedway',
							'Swanky'             => 'Swanky',
							'Zeyada'             => 'Zeyada',
						),
					),
					'fontsize' => array(
						'limit' => 127
					)
				),
			),

			// Dummy Image
			'dummyimage.com' => array(
				'title'      => 'Dummy Image',
				'url'        => 'http://dummyimage.com/{width}x{height}/{bgcolor}/{color}',
				'format_pos' => 'height',
				'query_args' => array(
					'text' => 'text',
				),
			),

			// Get Dummy Image
			'getdummyimage.com' => array(
				'title'      => 'Get Dummy Image',
				'url'        => 'http://getdummyimage.com/image',
				'format_pos' => false,
				'query_args' => array(
					'width'       => 'width',
					'height'      => 'height',
					'bgcolor'     => 'bgcolor',
					'color'       => 'color',
					'text'        => 'text',
					'bordercolor' => 'bordercolor',
				),
				'options'    => array(
					'bordercolor' => array(
						'label' => __( 'Border Color', 'initials-default-avatar' ),
						'type'  => 'color',
					),
				),
			),

			// Imageholdr
			'imageholdr.com' => array(
				'title'      => 'Imageholdr',
				'url'        => 'http://imageholdr.com/{width}x{height}',
				'format_pos' => false,
				'query_args' => array(
					'background' => 'bgcolor',
					'color'      => 'color',
					'text'       => 'text',
					'format'     => 'format',
				),
			),

			// Ipsum Image
			'ipsumimage.com' => array(
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
			),

			// Placeboxes
			'placebox.es' => array(
				'title'      => 'Placeboxes',
				'url'        => 'http://placebox.es/{width}x{height}/{bgcolor}/{color}/{text},{fontsize}/',
				'format_pos' => false,
				'query_args' => array(),
				'options'    => array(
					'fontsize',
				),
			),

			// Placehold It
			'placehold.it' => array(
				'title'      => 'Placehold It',
				'url'        => 'http://placehold.it/{width}x{height}/{bgcolor}/{color}',
				'format_pos' => 'height',
				'query_args' => array(
					'text' => 'text'
				),
			),

			// Fake Images Please
			'fakeimg.pl' => array(
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

		) );

		// Set service name argument
		foreach ( $services as $name => $args ) {
			$services[$name]['name'] = $name;
		}

		// Return all, one or no service
		if ( empty( $service ) ) {
			return $services;
		} elseif ( isset( $services[$service] ) ) {
			return $services[$service];
		} else {
			return false;
		}
	}

	/**
	 * Return whether a service supports the feature
	 *
	 * @since 1.0.0
	 * 
	 * @param string $feature Feature key
	 * @param string|array $service Service name or data. Defaults to current service
	 * @return bool Service supports feature
	 */
	public function service_supports( $feature = '', $service = '' ) {

		// Assume no support
		$support = false;

		// No service data passed
		if ( ! is_array( $service ) ) {
			
			// Service name passed
			if ( ! empty( $service ) ) {
				$service = $this->placeholder_services( $service );
			
			// Default to current service
			} else {
				$service = $this->get_current_service();
			}
		}

		// Continue if valid key
		if ( ! empty( $feature ) ) {

			// Find option value		
			if ( preg_match( "/\{$feature\}/", $service['url'] ) || in_array( $feature, $service['query_args'] ) )
				$support = true;
		}
		
		return apply_filters( 'initials_default_avatar_service_supports', $support, $feature, $service );
	}

	/**
	 * Return the requested service option
	 *
	 * @since 1.0.0
	 * 
	 * @param string $key Option key
	 * @param string $service Service name
	 * @return mixed|bool Value on success, false if not found
	 */
	public function get_service_option( $key = '', $service = '' ) {

		// Default to selected service
		if ( empty( $service ) )
			$service = $this->service;

		// Continue if valid key
		if ( ! empty( $key ) ) {

			// Find option value		
			if ( isset( $this->options[$service][$key] ) )
				return $this->options[$service][$key];
		}

		// Default false
		return null;
	}

	/** Utility ***************************************************************/

	/**
	 * Act on plugin deactivation
	 *
	 * @since 1.0.0
	 *
	 * @uses delete_option()
	 * @uses do_action() Calls 'initials_default_vatar_deactivation'
	 * @uses update_option()
	 */
	public function deactivate() {

		// Remove notice option
		delete_option( $this->notice );

		// Fire deactivation hook
		do_action( 'initials_default_avatar_deactivation' );

		// Restore previous avatar default
		if ( get_option( 'avatar_default' ) === $this->avatar_key ) {
			update_option( 'avatar_default', get_option( 'initials_default_avatar_previous' ) );
			delete_option( 'initials_default_avatar_previous' );
		}
	}

	/** Back-compat ***********************************************************/

	/**
	 * Return the default avatar when requested. Pre-WP 4.2.0.
	 *
	 * Since external default services besides Gravatar cannot insert their own
	 * image source, we'll replace the image src and class attributes with DOMDocument.
	 * 
	 * @since 1.0.0
	 *
	 * @uses Initials_Default_Avatar::get_avatar_data()
	 * @uses Initials_Default_Avatar::build_avatar()
	 * @uses apply_filters() Calls 'initials_default_avatar_get_avatar'
	 * 
	 * @param string $avatar Previously created avatar html
	 * @param string|int|object $id_or_email User identifier or comment object
	 * @param string|int $size Avatar size
	 * @param string $default Default avatar name
	 * @param string|boolean $alt Alternative avatar text
	 * @return string $avatar
	 */
	public function get_avatar( $avatar, $id_or_email, $size, $default, $alt ) {

		// Bail when we're not serving the avatar default
		if ( $default !== $this->avatar_key ) 
			return $avatar;

		$args = array( 'size' => $size, 'default' => $default, 'alt' => $alt );

		$data = $this->get_avatar_data( $args, $id_or_email );

		/** 
		 * Inject avatar string with our class and url
		 *
		 * Since we cannot insert an image url with a querystring into the 
		 * Gravatar's image url default query arg, we just completely rewrite it.
		 */
		$avatar = $this->build_avatar( $avatar, array( 'src' => $data['url'], 'class' => $data['class'] ) );

		return apply_filters( 'initials_default_avatar_get_avatar', $avatar, $id_or_email, $size, $alt );
	}

	/**
	 * Return avatar string with inserted attributes
	 *
	 * @since 1.0.0
	 *
	 * @uses apply_filters() Calls 'initials_default_avatar_setup_avatar_attrs'
	 * @uses DOMDocument
	 * 
	 * @param string $avatar Avatar HTML string
	 * @param array $args Image HTML attributes
	 * @return string Avatar
	 */
	public function build_avatar( $avatar, $attrs ) {

		// Bail if no valid params
		if ( empty( $avatar ) || empty( $attrs ) )
			return false;

		$attrs = apply_filters( 'initials_default_avatar_setup_avatar_attrs', (array) $attrs );

		// Define DOMDocument elements
		$img = '';
		$dom = new DOMDocument;
		$dom->loadHTML( $avatar );

		// Get <img> tag
		foreach ( $dom->getElementsByTagName( 'img' ) as $img ) {

			// Inject img with all attributes
			foreach ( $attrs as $key => $value ) {
				switch ( $key ) {
					case 'url' :
						$key = 'src';
					case 'src' :
						// $value = esc_url( $value ); // Breaks &amp/&#038 in query string
						break;
					default :
						$value = esc_attr( $value );
				}

				$img->setAttribute( $key, $value );
			}
		}

		// Rebuild HTML string
		if ( ! empty( $img ) ) {
			$avatar = $dom->saveHTML();
		}
		
		return $avatar;
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
$_GLOBALS['initials_default_avatar'] = initials_default_avatar();

endif; // class_exists
