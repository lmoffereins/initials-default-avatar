<?php

/**
 * Initials Default Avatar BuddyPress Functions
 * 
 * @package Initials Default Avatar
 * @subpackage BuddyPress
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Initials_Default_Avatar_BuddyPress' ) ) :
/**
 * The main plugin class
 *
 * @since 1.1.0
 */
class Initials_Default_Avatar_BuddyPress {

	/**
	 * Setup the class
	 *
	 * @since 1.1.0
	 */
	public function __construct() {
		$this->setup_actions();	
	}

	/** Private methods *************************************************/

	/**
	 * Setup default actions and filters
	 *
	 * @since 1.1.0
	 */
	private function setup_actions() {

		// Buddypress
		add_filter( 'bp_core_fetch_avatar',     array( $this, 'bp_get_avatar' ), 10, 9 );
		add_filter( 'bp_core_fetch_avatar_url', array( $this, 'bp_get_avatar' ), 10, 2 );
	}

	/** Public methods **************************************************/

	/**
	 * Return default initials avatar for buddypress avatars
	 *
	 * @since 1.1.0
	 * 
	 * @param string $avatar Avatar string
	 * @param array $args
	 * @return string Avatar
	 */
	public function bp_get_avatar( $avatar, $args, $item_id = 0, $avatar_dir = '', $css_id = '', $html_width = '', $html_height = '', $avatar_folder_url = '', $avatar_folder_dir = '' ) {
		$bp  = buddypress();
		$iad = initials_default_avatar();

		// We are not the default avatar, so no need to be here
		if ( $iad->avatar_key != $bp->grav_default->{$args['object']} )
			return $avatar;

		// Not a gravatar or valid gravatar found
		if ( false === strpos( $avatar, 'gravatar' ) || $iad->is_valid_gravatar( $avatar ) )
			return $avatar;

		// Get all args
		extract( $args, EXTR_OVERWRITE );

		// Setup user data if it isn't registered yet
		if ( ! $this->bp_has_item_data( $item_id, $object ) ) {

			$item = $this->bp_identify_item( $item_id, $object );

			// Filter item name to be based on something else
			$item_name = apply_filters( 'initials_default_avatar_bp_item_name', $item['item_name'], $item_id );

			// Require item name
			if ( ! empty( $item_name ) ) {
				$this->bp_set_item_data( $item_id, $item_name, $object );
			} else {
				return $avatar;
			}
		}

		// Get user data
		$item_setup = $this->bp_get_item_data( $item_id, $object );

		// Set size var
		if ( false != $width ) {
			$size = $width;
		} elseif ( 'thumb' == $type ) {
			$size = bp_core_avatar_thumb_width();
		} else {
			$size = bp_core_avatar_full_width();
		}

		// Return <img>
		if ( true === $html ) {

			// Build avatar <img>
			$class  = $iad->get_avatar_class( $item_setup, $size );
			$src    = $iad->get_avatar_src(   $item_setup, $size );

			/** 
			 * Inject avatar string with our class and src
			 *
			 * Since we cannot insert an image url with a querystring into the 
			 * Gravatar's image src default query arg, we just completely rewrite it.
			 */
			$avatar = $iad->write_avatar( $avatar, compact( 'class', 'src' ) );

			return apply_filters( 'initials_default_avatar_bp_get_avatar', $avatar, $args, $item_id, $avatar_dir, $css_id, $html_width, $html_height, $avatar_folder_url, $avatar_folder_dir );

		// Return url
		} else {

			// Build avatar src
			$avatar = $iad->get_avatar_src( $item_setup, $size );

			return apply_filters( 'initials_default_avatar_bp_get_avatar_url', $avatar, $args );
		}
	}

	/**
	 * Return avatar object ID and object name
	 *
	 * @since 1.1.0
	 * 
	 * @param int $item_id Item ID
	 * @param string $object Object type
	 * @return array object data
	 */
	public function bp_identify_item( $item_id, $object ) {
		$iad = initials_default_avatar();

		// What object are we looking at?
		switch ( $object ) {

			case 'blog' :
				$data = array( 'item_id' => $item_id, 'item_name' => get_blog_option( $item_id, 'blogname' ) );
				break;

			case 'group' :
				if ( bp_is_active( 'groups' ) ) {
					$data = array( 'item_id' => $item_id, 'item_name' => bp_get_group_name( groups_get_group( array( 'group_id' => $item_id ) ) ) );
				} else {
					$data = false;
				}

				break;

			case 'user' :
			default :
				$data = $iad->identify_user( $item_id );
				$data = array( 'item_id' => $data['user_id'], 'item_name' => $data['user_name'] );
				break;
		}

		return apply_filters( 'bp_identify_item', $data, $item_id, $object );
	}

	/**
	 * Return whether the given item is in our objects array
	 *
	 * @since 1.1.0 
	 * 
	 * @param int $item_id Item ID
	 * @param string $object Object type
	 * @return bool Item is registered
	 */
	public function bp_has_item_data( $item_id, $object ) {
		$iad = initials_default_avatar();

		// What object are we looking at?
		switch ( $object ) {

			case 'user' :
				$has = isset( $iad->users[$item_id] );
				break;

			default :
				$has = isset( $iad->{$object}[$item_id] );
				break;
		}

		return apply_filters( 'bp_has_item_data', $has, $item_id, $object );
	}

	/**
	 * Return the given object item setup
	 *
	 * @since 1.1.0
	 * 
	 * @param int $item_id Item ID
	 * @return array Object item setup
	 */
	public function bp_get_item_data( $item_id, $object ) {
		$iad = initials_default_avatar();

		// What object are we looking at?
		switch ( $object ) {

			case 'user' :
				$data = $iad->users[$item_id];
				break;

			default :
				$data = $iad->{$object}[$item_id];
				break;
		}

		return apply_filters( 'bp_get_item_data', $data, $item_id, $object );
	}

	/**
	 * Setup object item data for given item
	 *
	 * @since 1.1.0
	 * 
	 * @param int $item_id Item ID
	 * @param string $item_name Item name
	 */
	public function bp_set_item_data( $item_id, $item_name, $object ) {
		$iad = initials_default_avatar();

		// Could not identify user
		if ( empty( $item_id ) || empty( $item_name ) )
			return;

		// Generate user colors
		$colors  = $iad->generate_colors();
		$initial = $iad->get_first_char( $item_name );

		// Setup and filter user avatar data
		$data = apply_filters( 'initials_default_avatar_bp_item_data', array(
			'initial' => ucfirst( $initial ),
			'bgcolor' => $colors['bgcolor'],
			'color'   => $colors['color']
		), $item_id, $item_name );

		// Manipulate object type name for users
		if ( 'user' == $object )
			$object = 'users';

		// Store data
		$iad->{$object}[$item_id] = $data;
	}
}

/**
 * Return single instance of this main plugin class
 *
 * @since 1.0.0
 * 
 * @return Initials_Default_Avatar_BuddyPress
 */
function initials_default_avatar_buddypress() {
	initials_default_avatar()->extend->bp = new Initials_Default_Avatar_BuddyPress;
}

endif; // class_exists
