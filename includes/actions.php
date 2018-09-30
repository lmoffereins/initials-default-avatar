<?php

/**
 * Initials Default Avatar Actions
 *
 * @package Initials Default Avatar
 * @subpackage Main
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/** Sub-actions ***************************************************************/

add_action( 'init', 'initials_default_avatar_init' );

/** Core **********************************************************************/

add_action( 'initials_default_avatar_init', 'initials_default_avatar_register_default_services', 5 );

/** Utility *******************************************************************/

add_action( 'initials_default_avatar_deactivation', 'initials_default_avatar_deactivate' );

/** Avatar ********************************************************************/

add_filter( 'get_avatar_data', 'initials_default_avatar_get_avatar_data', 10, 2 );

// Back-compat pre-WP 4.2.0
if ( ! function_exists( 'get_avatar_data' ) ) {
	add_filter( 'get_avatar', 'initials_default_avatar_get_avatar', 10, 5 );
}

/** Admin *********************************************************************/

if ( is_admin() ) {
	add_action( 'admin_init', 'initials_default_avatar_setup_updater', 999 );
}

/** Extend ********************************************************************/

add_action( 'bp_loaded', 'initials_default_avatar_setup_buddypress' );
