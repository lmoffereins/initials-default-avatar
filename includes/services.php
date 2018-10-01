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
