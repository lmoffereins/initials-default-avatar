<?php

/**
 * Initials Default Avatar Uninstall Functions
 * 
 * @package Initials Default Avatar
 * @subpackage Uninstall
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

// Exit if not uninstalling
defined( 'WP_UNINSTALL_PLUGIN' ) && 'initials-default-avatar/initials-default-avatar.php' == WP_UNINSTALL_PLUGIN || exit;

// Remove plugin options
foreach ( array( 'initials_default_avatar_service', 'initials_default_avatar_options' ) as $option ) {
	delete_option( $option );
}

// Fire uninstall hook
do_action( 'initials_default_avatar_uninstall' );
