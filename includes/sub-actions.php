<?php

/**
 * Initials Default Avatar Sub-actions
 *
 * @package Initials Default Avatar
 * @subpackage Main
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Run dedicated activation hook for this plugin
 *
 * @since 1.0.0
 *
 * @uses do_action() Calls 'initials_default_avatar_activation'
 */
function initials_default_avatar_activation() {
	do_action( 'initials_default_avatar_activation' );
}

/**
 * Run dedicated deactivation hook for this plugin
 *
 * @since 1.0.0
 *
 * @uses do_action() Calls 'initials_default_avatar_deactivation'
 */
function initials_default_avatar_deactivation() {
	do_action( 'initials_default_avatar_deactivation' );
}

/**
 * Run dedicated init hook for this plugin
 *
 * @since 1.0.0
 *
 * @uses do_action() Calls 'initials_default_avatar_init'
 */
function initials_default_avatar_init() {
	do_action( 'initials_default_avatar_init' );
}
