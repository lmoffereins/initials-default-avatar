<?php

/**
 * Initials Default Avatar Functions
 *
 * @package Initials Default Avatar
 * @subpackage Main
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/** Core **********************************************************************/

/**
 * Return the plugin's avatar key
 *
 * @since 1.1.0
 *
 * @return string Avatar key
 */
function initials_default_avatar_avatar_key() {
	return initials_default_avatar()->avatar_key;
}

/**
 * Return whether the plugin's avatar is set as the default
 *
 * @since 1.1.0
 *
 * @return bool Avatar is the default
 */
function initials_default_avatar_is_default() {
	return get_option( 'avatar_default' ) === initials_default_avatar_avatar_key();
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
	if ( initials_default_avatar_is_default() ) {
		update_option( 'avatar_default', get_option( 'initials_default_avatar_previous' ) );
		delete_option( 'initials_default_avatar_previous' );
	}
}

