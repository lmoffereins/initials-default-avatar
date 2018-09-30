<?php

/**
 * Initials Default Avatar Services Functions
 *
 * @package Initials Default Avatar
 * @subpackage Main
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Register initial plugin placeholder services
 *
 * Default registered services (with options)
 *  - dummyimage.com
 *  - getdummyimage.com (border color)
 *  - ipsumimage.appspot.com (font size)
 *  - placehold.it
 *  - fakeimg.pl
 *
 * Deprecated services
 *  - cambelt.co (font, font size)
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
function initials_default_avatar_register_default_services() {

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

	/** Get Dummy Image *******************************************************/

	initials_default_avatar_register_service(
		'getdummyimage.com',
		array(
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
 * @param array $args Array of service arguments {@see Initials_Default_Avatar::register_service()}.
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
	
	return apply_filters( 'initials_default_avatar_service_supports', $support, $feature, $service );
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
