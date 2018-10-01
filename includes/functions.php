<?php

/**
 * Initials Default Avatar Functions
 *
 * @package Initials Default Avatar
 * @subpackage Main
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/** Versions ******************************************************************/

/**
 * Output the plugin version
 *
 * @since 1.1.0
 */
function initials_default_avatar_version() {
	echo initials_default_avatar_get_version();
}

	/**
	 * Return the plugin version
	 *
	 * @since 1.1.0
	 *
	 * @return string The plugin version
	 */
	function initials_default_avatar_get_version() {
		return initials_default_avatar()->version;
	}

/**
 * Output the plugin database version
 *
 * @since 1.1.0
 */
function initials_default_avatar_db_version() {
	echo initials_default_avatar_get_db_version();
}

	/**
	 * Return the plugin database version
	 *
	 * @since 1.1.0
	 *
	 * @return string The plugin version
	 */
	function initials_default_avatar_get_db_version() {
		return initials_default_avatar()->db_version;
	}

/**
 * Output the plugin database version directly from the database
 *
 * @since 1.1.0
 */
function initials_default_avatar_db_version_raw() {
	echo initials_default_avatar_get_db_version_raw();
}

	/**
	 * Return the plugin database version directly from the database
	 *
	 * @since 1.1.0
	 *
	 * @return string The current plugin version
	 */
	function initials_default_avatar_get_db_version_raw() {
		return get_option( 'initials_default_avatar_db_version', '' );
	}

/** Core **********************************************************************/

/**
 * Return the plugin's avatar key
 *
 * @since 1.1.0
 *
 * @return string Avatar key
 */
function initials_default_avatar_get_avatar_key() {
	return initials_default_avatar()->avatar_key;
}

/**
 * Return whether the plugin's avatar is set as the default
 *
 * @since 1.1.0
 *
 * @return bool Avatar is the default
 */
function initials_default_avatar_is_initials_default() {
	return initials_default_avatar_is_initials_avatar( get_option( 'avatar_default' ) );
}

/**
 * Return whether the given avatar key is the plugin's avatar
 *
 * @since 1.1.0
 *
 * @param string $avatar_key Avatar key to check
 * @return bool Is this the plugin's avatar?
 */
function initials_default_avatar_is_initials_avatar( $avatar_key = '' ) {
	return $avatar_key === initials_default_avatar_get_avatar_key();
}

/** Service *******************************************************************/

/**
 * Register a single placeholder service
 *
 * @since 1.1.0
 *
 * @param string $service Service name
 * @param array $args Service arguments, {@see Initials_Default_Avatar::register_service()}.
 * @return bool Serivce was registered
 */
function initials_default_avatar_register_service( $service, $args = array() ) {
	return initials_default_avatar()->register_service( $service, $args );
}

/**
 * Unregister a single placeholder service
 *
 * @since 1.1.0
 *
 * @param string $service Service name
 * @return bool Service was unregistered
 */
function initials_default_avatar_unregister_service( $service ) {
	return initials_default_avatar()->unregister_service( $service );
}

/**
 * Return the selected placeholder service data
 *
 * @since 1.1.0
 *
 * @param string $service Optional. Service name. Defaults to the current service.
 * @return object|bool Service data or False when not found
 */
function initials_default_avatar_get_service( $service = '' ) {

	// Default to current service
	if ( empty( $service ) ) {
		$service = get_option( 'initials_default_avatar_service' );
	}

	$services = initials_default_avatar_get_services();

	if ( $service ) {
		if ( ! is_string( $service ) ) {
			$service = (object) $service;
			$name = $service->name;
		} else {
			$name = $service;
		}

		if ( isset( $services[ $name ] ) ) {
			return $services[ $name ];
		} else {
			return false;
		}
	}

	// Default to first service
	$service = reset( $services );

	return $service;
}

/**
 * Return all registered placeholder services
 *
 * @since 1.1.0
 *
 * @return array Services
 */
function initials_default_avatar_get_services() {
	return initials_default_avatar()->get_services();
}

/**
 * Return whether a service supports the feature
 *
 * @since 1.1.0
 *
 * @uses apply_filters() Calls 'initials_default_avatar_service_supports'
 *
 * @param string $feature Feature key
 * @param string|array $service Optional. Service name. Defaults to current service.
 * @return bool Service supports the feature
 */
function initials_default_avatar_service_supports( $feature = '', $service = '' ) {
	$service = initials_default_avatar_get_service( $service );
	$support = false;

	if ( $service && $feature ) {

		// Find feature in url string
		if ( preg_match( "/\{$feature\}/", $service->url ) ) {
			$support = true;

		// Find feature in query args mapping
		} elseif ( in_array( $feature, $service->query_args ) ) {
			$support = true;
		}
	}

	return (bool) apply_filters( 'initials_default_avatar_service_supports', $support, $feature, $service );
}

/**
 * Return the requested service option
 *
 * @since 1.1.0
 *
 * @param string $option Option name
 * @param string $service Optional. Service name. Defaults to the current service.
 * @return mixed Option value on success, null if not found
 */
function initials_default_avatar_get_service_option( $option = '', $service = '' ) {
	$service = initials_default_avatar_get_service( $service );
	$option  = null;

	// Continue if valid key
	if ( $service && $option ) {
		$options  = get_option( 'initials_default_avatar_options', array() );

		// Get option value
		if ( isset( $options[ $service->name ] ) && isset( $options[ $service->name ][ $option ] ) ) {
			$option = $options[ $service->name ][ $option ];
		}
	}

	return $option;
}

/** Avatar ********************************************************************/

/**
 * Return the default avatar when requested
 *
 * For WP pre-4.2, external default services besides Gravatar cannot insert their own
 * image source, so we'll replace the image src and class attributes with DOMDocument.
 *
 * @since 1.1.0
 *
 * @uses apply_filters() Calls 'initials_default_avatar_get_avatar'
 *
 * @param string $avatar Previously created avatar html
 * @param string|int|object $id_or_email User identifier or comment object
 * @param string|int $size Avatar size
 * @param string $default Default avatar name
 * @param string|boolean $alt Alternative avatar text
 * @return string $avatar
 */
function initials_default_avatar_get_avatar( $avatar, $id_or_email, $size, $default, $alt ) {

	// Bail when we're not serving the avatar default
	if ( $default !== initials_default_avatar_avatar_key() ) 
		return $avatar;

	$data = initials_default_avatar_get_avatar_data( array(
		'size'    => $size,
		'default' => $default,
		'alt'     => $alt
	), $id_or_email );

	/** 
	 * Inject avatar string with our class and url
	 *
	 * Since we cannot insert an image url with a querystring into the 
	 * Gravatar's image url default query arg, we just completely rewrite it.
	 */
	$avatar = initials_default_avatar_build_avatar( $avatar, array(
		'src'   => $data['url'],
		'class' => $data['class']
	) );

	return apply_filters( 'initials_default_avatar_get_avatar', $avatar, $id_or_email, $size, $alt );
}

/**
 * Return avatar string with inserted attributes
 *
 * @since 1.1.0
 *
 * @uses apply_filters() Calls 'initials_default_avatar_setup_avatar_attrs'
 * @uses DOMDocument
 *
 * @param string $avatar Avatar HTML string
 * @param array $args Image HTML attributes
 * @return string Avatar
 */
function initials_default_avatar_build_avatar( $avatar = '', $attrs = array() ) {

	// Bail when no parameters are not valid
	if ( empty( $avatar ) || empty( $attrs ) )
		return false;

	$attrs = apply_filters( 'initials_default_avatar_setup_avatar_attrs', (array) $attrs );

	// Define DOMDocument elements
	$img = '';
	$dom = new DOMDocument;

	// Load avatar in fragment
	$fragment = $dom->createDocumentFragment();
	$fragment->appendXML( $avatar );
	$dom->appendChild( $fragment );

	// Get `img` tag
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

/**
 * Return avatar data when we serve a default avatar
 *
 * @since 1.1.0
 *
 * @param array $data Avatar data
 * @param mixed $id_or_email Avatar object identifier
 * @return array Avatar data
 */
function initials_default_avatar_get_avatar_data( $data, $id_or_email ) {

	// Parse defaults
	$data = wp_parse_args( $data, array(
		'size'    => 96,
		'default' => '',
		'alt'     => '',
		'url'     => '',
		'class'   => ''
	) );

	// Bail when we're not serving the avatar default
	if ( ! initials_default_avatar_is_initials_avatar( $data['default'] ) )
		return $data;

	/**
	 * NOTE: $data['found_avatar'] may be true, but it only says an email
	 * was hashed from the $id_or_email var. We do not know if the avatar
	 * really exists with the Gravatar service.
	 */

	// Bail when we do not need step in because this is not the
	// sample avatar, and there a valid gravatar was found.
	if ( ! initials_default_avatar()->is_sample && $data['found_avatar'] && initials_default_avatar_is_valid_gravatar( $data['url'] ) )
		return $data;

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
			return $data;
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
	if ( ! $user && $email ) {
		$user = get_user_by( 'email', $email );
	}

	// Get avatar details
	$details = initials_default_avatar_get_avatar_details( $user ? $user : $email, $name );

	// Redefine avatar data
	$data['found_avatar'] = false; // !
	$data['url']          = initials_default_avatar_get_avatar_url  ( $details,       $data );
	$data['class']        = initials_default_avatar_get_avatar_class( $data['class'], $data );

	return $data;
}

/**
 * Check if we're not overwriting a valid Gravatar
 *
 * Since we don't know yet if we're here for a default avatar fallback or 
 * Gravatar will match with an existing Gravatar account, we should check 
 * which is the case. We cannot know from the `img` src attribute what
 * image Gravatar will send us back. So first, we'll ask Gravatar if it
 * knows the current email. If not, guaranteed we'll recieve a default.
 *
 * @since 1.1.0
 *
 * @param string $avatar HTML image or image url
 * @return bool Whether the given avatar is a valid gravatar
 */
function initials_default_avatar_is_valid_gravatar( $avatar ) {

	// Get IDA
	$plugin = initials_default_avatar();

	// Bail when the user chose to pass this test, but we might be overwritin'
	if ( get_option( $plugin->notice ) )
		return false;

	// Read the {/avatar/email_hash} part from the current avatar
	preg_match( '/avatar\/([^&]+)\?/', $avatar, $matches );

	// No email_hash found
	if ( ! isset( $matches[1] ) )
		return false;

	// Bail when we checked this email hash before
	if ( in_array( $matches[1], array_keys( $plugin->email_hashes ) ) )
		return $plugin->email_hashes[ $matches[1] ];

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
	$plugin->email_hashes[ $matches[1] ] = $is_valid;

	return $is_valid;
}

/**
 * Return the details of the given avatar (user)
 *
 * @since 1.1.0
 *
 * @param int|string $avatar_id Avatar identifier
 * @param string $name Suggested avatar holder name
 * @return array Avatar details
 */
function initials_default_avatar_get_avatar_details( $avatar_id = 0, $name = '' ) {
	return initials_default_avatar()->get_avatar_details( $avatar_id, $name );
}

/**
 * Setup and return the avatar url
 *
 * @since 1.1.0
 *
 * @param array $details Avatar details. See Initials_Default_Avatar::get_avatar_details()
 * @param array $args Avatar data args
 * @param string $service Optional. Service name. Defaults to the current service.
 * @return string $avatar
 */
function initials_default_avatar_get_avatar_url( $details, $args, $service = '' ) {
	$service = initials_default_avatar_get_service( $service );

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
	if ( isset( $service->options ) ) {

		// Walk options
		foreach ( (array) $service->options as $option => $opt_args ) {

			// Format has specified position
			if ( ! empty( $service->format_pos ) ) {
				$args[$service->format_pos] .= '.' . $args['format'];
				unset( $args['format'] );
			}
		}
	}

	// Handle font when requested
	if ( initials_default_avatar_service_supports( 'font', $service ) ) {
		$font = initials_default_avatar_get_service_option('font', $service->name );

		// Default to first option
		if ( null === $font && 'select' == $service->options['font']['type'] ) {
			$font = key( $service->options['font']['options'] );
		}

		$args['font'] = $font;
	}

	// Handle font size when requested
	if ( initials_default_avatar_service_supports( 'fontsize', $service ) ) {

		// Get selected font size percentage
		$perc = initials_default_avatar_get_service_option('fontsize', $service->name );

		// Default font size
		if ( null === $perc ) {
			$perc = 65;
		}

		// Calculate size
		$font_size = (int) ceil( (int) $args['height'] * ( $perc / 100 ) );

		// Limit size
		if ( isset( $opt_args['fontsize'] ) && ! empty( $opt_args['fontsize']['limit'] ) && $font_size > $opt_args['fontsize']['limit'] ) {
			$font_size = $limit;
		}

		$args['fontsize'] = $font_size;
	}

	// Per service
	switch ( $service->name ) {

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
			$bordercolor = initials_default_avatar_get_service_option( 'bordercolor', $service->name );
			if ( $bordercolor ) {
				$args['bordercolor'] = '%23' . $bordercolor . '&border=on';
			}
			break;
	}

	// Setup the avatar url
	$url = $service->url;

	// Filter src arguments
	$url_args = (array) apply_filters( 'initials_default_avatar_avatar_src_args', $args, $service, $details, $size );

	// Fill all url variables
	foreach ( $url_args as $r_key => $r_value ) {
		$url = preg_replace( '/{' . $r_key . '}/', $r_value, $url );
	}

	// Add url query args
	foreach ( $service->query_args as $query_key => $value_key ) {
		if ( isset( $url_args[ $value_key ] ) ) {
			$url = add_query_arg( $query_key, rawurlencode_deep( $url_args[ $value_key ] ), $url );
		}
	}

	return apply_filters( 'initials_default_avatar_avatar_src', $url, $service, $details, $size, $args );
}

/**
 * Return the avatar class attribute value
 *
 * @since 1.1.0
 *
 * @param string $class Current avatar classes
 * @param array $args Avatar arguments
 * @param array $service Optional. Service name. Defaults to the current service.
 * @return string Classes
 */
function initials_default_avatar_get_avatar_class( $class, $args, $service = '' ) {
	$service = initials_default_avatar_get_service( $service );

	// Collect avatar classes
	if ( $service ) {
		$classes = explode( ' ', $class );
		$classes = array_merge( (array) $classes, array(
			'avatar', 
			'photo', 
			"avatar-{$args['size']}",
			"avatar-" . initials_default_avatar_get_avatar_key(),
			"service-{$service->name}"
		) );

		$classes = apply_filters( 'initials_default_avatar_avatar_class', $classes, $args, $service );
		$classes = array_map( 'sanitize_html_class', array_unique( array_filter( $classes ) ) );
		$class   = implode( ' ', $classes );
	}

	return $class;
}

/**
 * Return the first characters of all the name's words
 *
 * @since 1.1.0
 *
 * @param string $name Name to get initials from
 * @return string Initials
 */
function initials_default_avatar_get_initials( $name ) {
	$initials = array();
	foreach ( preg_split( '/[^a-zA-Z\?\!]/', $name ) as $word ) {
		$initials[] = initials_default_avatar_get_first_char( $word );
	}

	return strtoupper( implode( '', $initials ) );
}

/**
 * Return the first character (letter or number) of a string
 *
 * @since 1.1.0
 *
 * @uses apply_filters() Calls 'initials_default_avatar_get_first_char'
 *
 * @param string $string
 * @return string First char
 */
function initials_default_avatar_get_first_char( $string = '' ) {

	// Bail when empty
	if ( empty( $string ) )
		return '';

	// Get the first safe character
	$char = mb_substr( $string, 0, 1, 'utf-8' );

	return apply_filters( 'initials_default_avatar_get_first_char', $char, $string );
}

/**
 * Return randomly generated avatar colors
 *
 * @since 1.1.0
 *
 * @return array Background color and font color
 */
function initials_default_avatar_generate_colors() {

	// Create a happy palette: select only colors between 60 and 195
	$red   = (int) mt_rand( 60, 195 );
	$blue  = (int) mt_rand( 60, 195 );
	$green = (int) mt_rand( 60, 195 );

	$bgcolor = dechex( $red ) . dechex( $blue ) . dechex( $green );
	$color   = 'ffffff';

	return compact( 'bgcolor', 'color' );
}

/** Utility *******************************************************************/

/**
 * Act on plugin deactivation
 *
 * @since 1.1.0
 */
function initials_default_avatar_deactivate() {

	// Remove notice option
	delete_option( initials_default_avatar()->notice );

	// Restore previous avatar default
	if ( initials_default_avatar_is_initials_default() ) {
		update_option( 'avatar_default', get_option( 'initials_default_avatar_previous' ) );
		delete_option( 'initials_default_avatar_previous' );
	}
}
