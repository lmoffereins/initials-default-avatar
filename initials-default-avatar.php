<?php

/**
 * The Initials Default Avatar Plugin
 *
 * @author Laurens Offereins
 *
 * @package Initials Default Avatar
 * @subpackage Main
 */

/**
 * Plugin Name: Initials Default Avatar
 * Plugin URI:  https://github.com/lmoffereins/initials-default-avatar
 * Description: Give your WP and default avatars some text and color love - as seen in Gmail.
 * Author:      Laurens Offereins
 * Author URI:  https://github.com/lmoffereins
 * Version:     1.0.0
 * Text Domain: initials-default-avatar
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'InitialsDefaultAvatar' ) ) :
/**
 * Initials Default Avatar plugin class
 *
 * Current services to choose from:
 *  - cambelt.co (font, font size)
 *  - dummyimage.com
 *  - getdummyimage.com
 *  - imageholdr.com
 *  - ipsumimage.appspot.com (font size)
 *  - placebox.es (font size)
 *  - placehold.it
 *   
 * @since 1.0.0
 *
 * @todo Setup uninstall procedure
 */
class InitialsDefaultAvatar {

	/**
	 * Holds initials default avatar name
	 *
	 * @since 1.0.0
	 * @var string 
	 */
	var $avatar_key = 'initials';

	/**
	 * Flag for the example intials default avatar
	 *
	 * @since 1.0.0
	 * @var boolean 
	 */
	var $picker = false;

	/**
	 * Holds all users on the page with their avatar params (colors)
	 *
	 * @since 1.0.0
	 * @var array 
	 */
	var $users = array();

	/**
	 * Admin notice option name
	 *
	 * @since 1.0.0
	 * @var string 
	 */
	var $notice = 'initials-default-avatar_notice';

	/**
	 * The selected placeholder service
	 *
	 * @since 1.0.0
	 * @var string 
	 */
	var $service = false;

	/**
	 * All service's options
	 *
	 * @since 1.0.0
	 * @var string 
	 */
	var $options = array();

	/**
	 * Setup plugin and hook main plugin actions
	 * 
	 * @since 1.0.0
	 */
	public function __construct() {

		// Service
		$this->service = get_option( 'initials_default_avatar_service' );
		if ( empty( $this->service ) )
			$this->service = key( $this->placeholder_services() );

		// Options
		$this->options = get_option( 'initials_default_avatar_options' );
		if ( empty( $this->options ) )
			$this->options = array();

		// Admin
		add_action( 'admin_init',               array( $this, 'hook_admin_message' ) );
		add_action( 'wp_ajax_' . $this->notice, array( $this, 'admin_store_notice' ) );
		add_action( 'admin_enqueue_scripts',    array( $this, 'enqueue_scripts'    ) );

		// Deactivation
		register_deactivation_hook( __FILE__, array( $this, 'deactivation' ) );

		// Avatar
		add_filter( 'get_avatar',      array( $this, 'get_avatar'      ), 10, 5 );
		add_filter( 'avatar_defaults', array( $this, 'avatar_defaults' )        );

		// Store previous avatar option
		add_filter( 'pre_update_option_avatar_default', array( $this, 'save_previous' ), 10, 2 );

		// Settings
		add_action( 'admin_init', array( $this, 'admin_init' ) );
	}

	/** Avatar ****************************************************************/

	/**
	 * Return the default avatar when requested
	 *
	 * Since external default services besides Gravatar cannot insert their own
	 * image source, we'll replace the image src and class attributes with DOMDocument.
	 * 
	 * @since 1.0.0
	 *
	 * @uses apply_filters() Calls 'initials_default_avatar_user' with the user avatar 
	 *                        params and user identifier
	 * 
	 * @param string $avatar Previously created avatar html
	 * @param string|int|object $id_or_email User identifier or comment object
	 * @param string|int $size Avatar size
	 * @param string $default Default avatar name
	 * @param string|boolean $alt Alternative avatar text
	 * @return string $avatar
	 */
	public function get_avatar( $avatar, $id_or_email, $size, $default, $alt ) {

		// No need to be here
		if ( $this->avatar_key != $default ) 
			return $avatar;

		// Bail if valid gravatar exists
		preg_match( '/avatar\/([^&]+)\?/', $avatar, $matches );
		if ( ! get_option( $this->notice ) && isset( $matches[1] ) && $response = wp_remote_head( sprintf( 'http://%d.gravatar.com/avatar/%s?d=404', hexdec( $matches[1][0] ) % 2, $matches[1] ) ) ) {
			// if ( is_wp_error( $response ) )
			// 	var_dump( $response->get_error_messages() );
			if ( '404' != wp_remote_retrieve_response_code( $response ) && ! is_wp_error( $response ) )
				return $avatar;
		}

		// Setup local vars
		$user_id = $user_name = '';

		// This is the sample avatar
		if ( $this->picker ) {
			$user_id      = 'avatar';
			$user_name    = 'A';
			$this->picker = false;

		// Identify user by ID
		} elseif ( is_numeric( $id_or_email ) ) {
			$id = (int) $id_or_email;

			// Check if user is already stored
			if ( isset( $this->users[$id] ) ) {
				$user_id = $id;	
			} else {
				$user = get_userdata( $id );
				if ( $user ) {
					$user_id   = $user->ID;
					$user_name = $user->display_name;
				}				
			}

		// Identify user by user object
		} elseif ( is_object( $id_or_email ) ) {

			// No avatar for pingbacks or trackbacks
			$allowed_comment_types = apply_filters( 'get_avatar_comment_types', array( 'comment' ) );
			if ( ! empty( $id_or_email->comment_type ) && ! in_array( $id_or_email->comment_type, (array) $allowed_comment_types ) )
				return false;

			if ( ! empty( $id_or_email->user_id ) ) {
				$id = (int) $id_or_email->user_id;

				// Check if user is already stored
				if ( isset( $this->users[$id] ) ) {
					$user_id = $id;	
				} else {
					$user = get_userdata( $id );
					if ( $user) {
						$user_id   = $user->ID;
						$user_name = $user->display_name;
					}
				}

			// User is comment author with name
			} elseif ( ! empty( $id_or_email->comment_author ) ) {
				$user_id   = ! empty( $id_or_email->comment_author_email ) ? $id_or_email->comment_author_email : $id_or_email->comment_author;
				$user_name = $id_or_email->comment_author;

			// User is comment author without name
			} elseif ( ! empty( $id_or_email->comment_author_email ) ) {
				$user_id   = $user_name = $id_or_email->comment_author_email;
			}

		// Identify user by email
		} else {
			$user = get_user_by( $id_or_email, 'email' );
			if ( $user ) {
				$user_id   = $user->ID;
				$user_name = $user->display_name;
			} elseif ( is_email( $id_or_email ) ) {
				$user_id   = $user_name = $id_or_email;
			}
		}

		// Bail if user is unidentifiable
		if ( empty( $user_id ) )
			return $avatar;

		// Setup user data if it isn't registered yet
		if ( ! isset( $this->users[$user_id] ) ) {

			// Generate user colors
			$colors = $this->generate_colors( $user_id );

			// Setup and register user avatar data
			$args = array(
				'initial' => ucfirst( $this->get_first_char( $user_name ) ),
				'bgcolor' => $colors['bgcolor'],
				'color'   => $colors['color']
			);

			$this->users[$user_id] = apply_filters( 'initials_default_avatar_user', $args, $user_id );
		}

		// Get user data and placeholder service
		$user_data = $this->users[$user_id];
		$service   = $this->get_current_service();

		// Setup avatar image attributes
		$class     = $this->get_avatar_class( $service, $user_data, $size );
		$src       = $this->get_avatar_src(   $service, $user_data, $size );

		// Replace avatar src and class attribute with DOMDocument
		$dom = new DOMDocument;
		$dom->loadHTML( $avatar );
		foreach ( $dom->getElementsByTagName( 'img' ) as $i ) {
			$i->setAttribute( 'src',   $src   );
			$i->setAttribute( 'class', $class );
		}
		$avatar = $dom->saveHTML( $i );
		
		return $avatar;
	}

	/**
	 * Return the avatar class attribute value
	 *
	 * @since 1.0.0
	 * 
	 * @param array $service Service data
	 * @param array $user_data User avatar args
	 * @param int   $size Avatar size
	 * @return string Class
	 */
	public function get_avatar_class( $service, $user_data, $size ) {
		
		// Collect avatar classes
		$classes = apply_filters( 'initials_default_avatar_avatar_class', array(
			'avatar',
			"avatar-{$size}",
			'photo',
			'avatar-default', 
			"avatar-{$this->avatar_key}"
		) );

		// Create class string
		$class = implode( ' ', array_unique( $classes ) );

		return $class;
	}

	/**
	 * Return the avatar src attribute value
	 *
	 * @since 1.0.0
	 * 
	 * @param array $service Service data
	 * @param array $user_data User avatar args
	 * @param int   $size Avatar size
	 * @return array Src args
	 */
	public function get_avatar_src( $service, $user_data, $size ) {

		// Setup default args
		$args = array(
			'width'   => $size, 
			'height'  => $size, 
			'bgcolor' => $user_data['bgcolor'], 
			'color'   => $user_data['color'],
			'text'    => $user_data['initial'],
			'format'  => 'png',
		);
		
		// Fetch service data
		if ( isset( $service['options'] ) ) {

			// Walk options
			foreach ( (array) $service['options'] as $option => $opt_args ) {

				// Format has specified position
				if ( ! empty( $service['format_pos'] ) ) {
					$args[$service['format_pos']] .= '.' . trim( $args['format'] );
					unset( $args['format'] );
				}
			}
		}

		// Handle font when requested
		if ( $this->service_supports( 'font', $service ) ) {
			$args['font'] = $this->get_service_option('font');
		}

		// Handle font size when requested
		if ( $this->service_supports( 'fontsize', $service ) ) {

			// Get selected font size percentage
			$perc = $this->get_service_option('fontsize');

			// Calculate size
			$size = (int) ceil( $args['height'] * ( $perc / 100 ) );

			// Limit size
			if ( isset( $opt_args['fontsize'] ) && ! empty( $opt_args['fontsize']['limit'] ) && $size > $opt_args['fontsize']['limit'] )
				$size = $limit;

			$args['fontsize'] = $size;
		}

		// Per service
		switch ( $service['name'] ) {

			case 'getdummyimage.com' :
				$args['bgcolor'] = '%23' . $args['bgcolor'];
				$args['color']   = '%23' . $args['color'];
				break;

			case 'cambelt.co' :
				$args['color'] = $args['bgcolor'] . ',' . $args['color'];
				break;

			case 'getdummyimage.com' :
				// Fix border color with &border=on to activate
				break;
		}

		// Filter src arguments
		$src_args = (array) apply_filters( 'initials_default_avatar_avatar_src_args', $args, $service, $user_data, $size );

		// Setup the avatar src
		$src = $service['url'];

		// Fill all url variables
		foreach ( $src_args as $r_key => $r_value ) {
			$src = preg_replace( '/' . $r_key . '/', $r_value, $src );
		}

		// Add url query args
		foreach ( $service['query_args'] as $query_key => $value_key ) {
			if ( isset( $src_args[$value_key] ) )
				$src = add_query_arg( $query_key, $src_args[$value_key], $src );
		}

		return apply_filters( 'initials_default_avatar_avatar_src', $src, $service, $user_data, $size, $args );
	}

	/**
	 * Return randomly generated avatar colors
	 * 
	 * @since 1.0.0
	 * 
	 * @uses apply_filters() Calls 'initials_default_avatar_colors' with the hexcode for
	 *                        the background color and the font color
	 * 
	 * @param string|int $user_id User identifier used in $this->users
	 * @return object With bgcolor and color
	 */
	public function generate_colors( $user_id ) {

		// Only select color values that matter: between 60 and 230
		$red   = (int) mt_rand( 60, 230 );
		$blue  = (int) mt_rand( 60, 230 );
		$green = (int) mt_rand( 60, 230 );

		$bgcolor = dechex( $red ) . dechex( $blue ) . dechex( $green );
		$color   = 'ffffff';

		return apply_filters( 'initials_default_avatar_colors', compact( 'bgcolor', 'color' ), $user_id );
	}

	/**
	 * Return the first character (letter or number) of a string
	 *
	 * @since 1.0.0
	 * 
	 * @param string $string
	 * @return string First char
	 */
	public function get_first_char( $string = '' ) {

		// Bail if empty
		if ( '' == $string )
			return $string;

		// Find first character or number, pass all non-whitespace or underscores
		$pattern = '/^[\W_]*([a-zA-Z0-9])/';
		preg_match( $pattern, $string, $matches );

		return apply_filters( 'initials_default_avatar_first_char', $matches[1], $string );
	}

	/** Admin *****************************************************************/

	/**
	 * Alert when system cannot connect to Gravatar.com
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

		// Check if user can connect to Gravatar.com
		if ( $response = wp_remote_head( sprintf( 'http://%d.gravatar.com/avatar/%s?d=404', hexdec( $user->user_email ) % 2, md5( $user->user_email ) ) ) ) {
			
			// Connection failed
			if ( is_wp_error( $response ) )
				add_action( 'admin_notices', array( $this, 'admin_notice' ) );		
		}
	}

	/**
	 * Output admin message to remind user of Gravatar.com connectivity fail
	 *
	 * @since 1.0.0
	 */
	public function admin_notice() {
	?>

		<div id="initials-default-avatar_notice">
			<div id="initials-default-avatar_note1" class="error">
				<p>
					<?php _e("It seems that your site cannot connect to gravatar.com to check user profiles. Note that Initials Default Avatar may overwrite a users gravatar displaying a default avatar.", 'initials-default-avatar'); ?>
					<a class="dismiss" href="#"><?php _e('Accept', 'initials-default-avatar'); ?></a><img class="hidden" src="<?php echo admin_url('/images/wpspin_light.gif'); ?>" style="vertical-align: middle; margin-left: 3px;" />
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
					<?php _e('Initials Default Avatar has calls to gravatar.com now disabled. Reactivate the plugin to retry.', 'initials-default-avatar'); ?>
					<a class="close" href="#"><?php _e('Close', 'initials-default-avatar'); ?></a>
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
	 * Do stuff on deactivation
	 *
	 * @since 1.0.0
	 *
	 * @uses update_option()
	 * @uses delete_option()
	 */
	public function deactivation() {

		// Remove notice option
		delete_option( $this->notice );

		// Restore previous avatar default
		if ( get_option( 'avatar_default' ) == $this->avatar_key ) {
			update_option( 'avatar_default', get_option( 'initials_default_avatar_previous' ) );
			delete_option( 'initials_default_avatar_previous' );
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

		// Set default avatar picker flag
		$this->picker = true;

		// Add initials default avatar
		$defaults[$this->avatar_key] = __('Initials (Generated)', 'initials-default-avatar');

		return $defaults;
	}

	/**
	 * Store previous default avatar when switching to initials
	 *
	 * @since 1.0.0
	 *
	 * @uses update_option()
	 *
	 * @param string $new Current avatar selection
	 * @param string $old Previous avatar selection
	 */
	public function save_previous( $new, $old ) {

		// Save the previous avatar selection for later
		if ( $this->avatar_key == $new && $new !== $old )
			update_option( 'initials_default_avatar_previous', $old );

		return $new;
	}

	/** Settings **************************************************************/

	/**
	 * Register plugin settings
	 *
	 * @since 1.0.0
	 *
	 * @uses register_setting()
	 * @uses add_settings_field()
	 */
	public function admin_init() {
		
		// Bail if initials default avatar is not selected
		if ( get_option( 'avatar_default' ) != $this->avatar_key ) 
			return;

		// Placeholder service
		register_setting( 'discussion', 'initials_default_avatar_service', array( $this, 'sanitize_service' ) );
		add_settings_field( 'initials-default-avatar-service', __('Initials Default Avatar', 'initials-default-avatar'), array( $this, 'admin_setting_placeholder_service' ), 'discussion', 'avatars' );

		// Service settings
		register_setting( 'discussion', 'initials_default_avatar_options', array( $this, 'sanitize_service_options' ) );
	}

	/**
	 * Enqueue scripts in the admin head on settings pages
	 *
	 * @since 1.0.0
	 */
	public function enqueue_scripts( $hook_suffix ) {

		// Bail if not on the Discussion page
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
	 * @uses InitialsDefaultAvatar::placeholder_services()
	 */
	public function admin_setting_placeholder_service() {
		$selected = get_option( 'initials_default_avatar_service' ); ?>

		<label for="initials_default_avatar_service">
			<select name="initials_default_avatar_service" id="initials-default-avatar-service">

				<option value=""><?php _e('Select a service', 'initials-default-avatar'); ?></option>
				<?php foreach ( $this->placeholder_services() as $service => $args ) : ?>

					<option value="<?php echo $service; ?>" <?php selected( $selected, $service ); ?>><?php echo $args['title']; ?></option>

				<?php endforeach; ?>

			</select>
			<?php _e('Select a placeholder service.', 'initials-default-avatar'); ?>
			<span class="learn-more"><?php printf( __('See %s for more information.', 'initials-default-avatar'), sprintf('<a class="service-url" target="_blank" href="http://%1$s">%1$s</a>', $selected ) ); ?></span>
		</label>

		<?php // Output settings fields for service options ?>
		<?php $this->admin_setting_service_options(); ?>

	<?php
	}

	/**
	 * Output settings fields for service options
	 *
	 * @since 1.0.0
	 *
	 * @uses InitialsDefaultAvatar::placeholder_services()
	 */
	public function admin_setting_service_options() {

		// Loop all services if they have options defined
		foreach ( $this->placeholder_services() as $service => $s_args ) : if ( isset( $s_args['options'] ) ) : ?>
			<?php $style = ( $this->service != $service ) ? 'style="display:none;"' : ''; ?>

			<div id="initials-default-avatar-service-<?php echo $service; ?>" class="initials-default-avatar-service-options" <?php echo $style; ?>>
				<h4 class="title"><?php _e('Service options', 'initials-default-avatar'); ?></h4>
	
				<?php foreach ( $s_args['options'] as $option => $o_args ) : ?>

				<?php $this->admin_setting_service_option_field( $service, $option, $o_args ); ?><br>

				<?php endforeach; ?>
			</div>

		<?php endif; endforeach;
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
		$value = $this->get_service_option( $field );
		if ( empty( $value ) )
			$value = '';
		$label = isset( $args['label'] ) ? $args['label'] : '';

		// Font size
		if ( 'fontsize' == $field ) {
			$label = __('Font size in percentage', 'initials-default-avatar');
			$args['type'] = 'percentage';
		}

		// What type is this field?
		switch ( $args['type'] ) {

			case 'select' :
				$value = esc_attr( $value );
				$input  = "<select name='$name' id='$id' class=''>";
				$input .=	'<option>' . __('Select an option', 'initials-default-avatar') . '</option>';
				foreach ( $args['options'] as $option => $font ) {
					$input .= "<option value='$option'" . selected( $value, $option, false ) . ">$font</option>";
				}
				$input .= '</select>';
				break;

			case 'text' :
				$value = esc_attr( $value );
				$input = "<input type='text' name='{$name}' id='{$id}' class='regular-text' value='{$value}' />";
				break;

			case 'number'  :
				$value = esc_attr( $value );
				$input = "<input type='number' name='{$name}' id='{$id}' class='small-text' value='{$value}' />";
				break;

			case 'percentage'  :
				$value = esc_attr( $value );
				$input = "<input type='number' name='{$name}' id='{$id}' class='small-text' value='{$value}' step='1' min='0' max='100' />";
				break;

			case 'textarea' :
				$value = esc_textarea( $value );
				$input = "<textarea name='{$name}' id='{$id}' class=''>{$value}</textarea>";
				break;

			case 'color' :
				$value = esc_attr( $value );
				$input = "<input type='text' name='{$name}' id='{$id}' class='ida-wp-color-picker' value='#{$value}' />";
				break;

			default :
				$input = apply_filters( 'initials_default_avatar_service_option_field_input', '', $service, $field, compact( $args, $id, $name, $value ) );
				break;
		}

		// Setup field with input label
		$_field = "<label for='{$id}'>{$input} <span class='description'>{$label}</span></label>";

		// Output break, input, label
		echo apply_filters( 'initials_default_avatar_service_option_field', $_field, $service, $field, $args );
	}

	/**
	 * Sanitize selected service setting
	 *
	 * @since 1.0.0
	 * 
	 * @uses InitialsDefaultAvatar::placeholder_services()
	 * @param string $input Service selected
	 * @return string|bool Sanitized input
	 */
	public function sanitize_service( $input ) {

		// Service selected exists
		if ( ! $this->placeholder_services( $input ) )
			$input = false;

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
	 * @uses InitialsDefaultAvatar::placeholder_services()
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

			// Cambelt.co
			'cambelt.co' => array(
				'title'      => 'Cambelt',
				'url'        => 'http://cambelt.co/widthxheight/text',
				'format_pos' => false,
				'query_args' => array(
					'font'      => 'font',
					'font_size' => 'fontsize',
					'format'    => 'format',
					'color'     => 'color',
				),
				'options'    => array(
					'font'      => array(
						'label' => __('Font', 'initials-default-avatar'),
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

			// Dummyimage.com
			'dummyimage.com' => array(
				'title'      => 'Dummy Image',
				'url'        => 'http://dummyimage.com/widthxheight/bgcolor/color',
				'format_pos' => 'height',
				'query_args' => array(
					'text' => 'text',
				),
			),

			// Getdummyimage.com
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
						'label' => __('Border Color', 'initials-default-avatar'),
						'type'  => 'color',
					),
				),
			),

			// Imageholdr.com
			'imageholdr.com' => array(
				'title'      => 'Imageholdr',
				'url'        => 'http://imageholdr.com/widthxheight',
				'format_pos' => false,
				'query_args' => array(
					'background' => 'bgcolor',
					'color'      => 'color',
					'text'       => 'text',
					'format'     => 'format',
				),
			),

			// Ipsumimage.com
			'ipsumimage.com' => array(
				'title'      => 'Ipsum Image',
				'url'        => 'http://ipsumimage.appspot.com/widthxheight',
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

			// Placebox.es
			'placebox.es' => array(
				'title'      => 'Placeboxes',
				'url'        => 'http://placebox.es/widthxheight/bgcolor/color/text,fontsize/',
				'format_pos' => 'height',
				'query_args' => array(),
				'options'    => array(
					'fontsize',
				),
			),

			// Placehold.it
			'placehold.it' => array(
				'title'      => 'Placehold It',
				'url'        => 'http://placehold.it/widthxheight/bgcolor/color',
				'format_pos' => 'height',
				'query_args' => array(
					'text' => 'text',
				),
			),

		) );

		// Set service name argument
		foreach ( $services as $name => $args )
			$services[$name]['name'] = $name;

		// Return all, one or no service
		if ( empty( $service ) )
			return $services;
		if ( isset( $services[$service] ) )
			return $services[$service];
		else
			return false;
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
			if ( preg_match( "/{$feature}/", $service['url'] ) || in_array( $feature, $service['query_args'] ) )
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
	 * @return mixed|bool Value on success, false if not found
	 */
	public function get_service_option( $key = '' ) {

		// Continue if valid key
		if ( ! empty( $key ) ) {

			// Find option value		
			if ( isset( $this->options[$this->service][$key] ) )
				return $this->options[$this->service][$key];
		}
		
		// Default false
		return false;
	}

}

// Initiate plugin
$_GLOBALS['initials_default_avatar'] = new InitialsDefaultAvatar;

endif; // class_exists
