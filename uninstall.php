<?php

/**
 * Initials Default Avatar Uninstall Functions
 * 
 * @package Initials Default Avatar
 * @subpackage Core
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

// Exit if not uninstalling
defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

// Bail when this is not the uninstalled plugin
if ( 'initials-default-avatar/initials-default-avatar.php' === WP_UNINSTALL_PLUGIN )
	return;

/**
 * Fires before uninstalling this plugin
 *
 * @since 2.0.0
 */
do_action( 'initials_default_avatar_pre_uninstall' );

// Remove plugin options
foreach ( array(
	'initials_default_avatar_service',
	'initials_default_avatar_service_options',
	'initials_default_avatar_db_version'
) as $option ) {
	delete_option( $option );
}

// Remove plugin site options
foreach ( array(
	'initials_default_avatar_service',
	'initials_default_avatar_service_options'
) as $option ) {
	delete_site_option( $option );
}

/**
 * Fires after uninstalling this plugin
 *
 * @since 2.0.0
 */
do_action( 'initials_default_avatar_post_uninstall' );
