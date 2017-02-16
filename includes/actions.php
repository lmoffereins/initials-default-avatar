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

add_action( 'init',      'initials_default_avatar_init' );

/** Utility *******************************************************************/

add_action( 'initials_default_avatar_deactivation', 'initials_default_avatar_deactivate' );

/** Extend ********************************************************************/

add_action( 'bp_loaded', 'initials_default_avatar_buddypress' );
