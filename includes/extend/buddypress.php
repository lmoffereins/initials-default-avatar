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
		add_filter( 'bp_core_fetch_avatar',     array( $this, 'get_avatar'     ), 10, 9 );
		add_filter( 'bp_core_fetch_avatar_url', array( $this, 'get_avatar_url' ), 10, 2 );

		// IDA
		add_filter( 'initials_default_avatar_user_name', array( $this, 'filter_avatar_name' ), 10, 4 );
	}

	/** Public methods **************************************************/

	/**
	 * Filter BuddyPress avatar for our default
	 *
	 * @since 1.1.0
	 * 
	 * @uses Initials_Default_Avatar_BuddyPress::get_avatar_data()
	 * @uses Initials_Default_Avatar::build_avatar()
	 *
	 * @param string $avatar Avatar image html
	 * @param array $args
	 * @return string Avatar image html
	 */
	public function get_avatar( $avatar, $args, $item_id, $avatar_dir, $css_id, $html_width, $html_height, $avatar_folder_url, $avatar_folder_dir ) {

		// Get the data for this avatar
		$data = $this->get_avatar_data( $avatar, $args );
		if ( empty( $data ) )
			return $avatar;

		/** 
		 * Inject avatar string with our class and src
		 *
		 * Since we cannot insert an image url with a querystring into the 
		 * Gravatar's image src default query arg, we just completely rewrite it.
		 */
		$avatar = initials_default_avatar()->build_avatar( $avatar, array( 'src' => $data['url'], 'class' => $data['class'] ) );

		return apply_filters( 'initials_default_avatar_bp_get_avatar', $avatar, $args, $item_id, $avatar_dir, $css_id, $html_width, $html_height, $avatar_folder_url, $avatar_folder_dir );
	}

	/**
	 * Filter BuddyPress avatar url for our default
	 *
	 * @since 1.1.0
	 *
	 * @uses Initials_Default_Avatar_BuddyPress::get_avatar_data()
	 * 
	 * @param string $url Avatar url
	 * @param array $args
	 * @return string Avatar url
	 */
	public function get_avatar_url( $url, $args ) {

		// Get the data for this avatar
		$data = $this->get_avatar_data( $url, $args );
		if ( empty( $data ) )
			return $url;

		// Filter non-html url
		return apply_filters( 'initials_default_avatar_bp_get_avatar_url', $data['url'], $args );
	}

	/**
	 * Return the avatar data for BuddyPress avatars
	 *
	 * @since 1.1.0
	 *
	 * @uses Initials_Default_Avatar::is_valid_gravatar()
	 * @uses Initials_Default_Avatar::get_avatar_details()
	 * @uses Initials_Default_Avatar::get_avatar_url()
	 * @uses Initials_Default_Avatar::get_avatar_class()
	 * @uses bp_core_avatar_thumb_width()
	 * @uses bp_core_avatar_full_width()
	 * 
	 * @param string $avatar Avatar
	 * @param array $args Avatar args
	 * @return array Avatar data
	 */
	public function get_avatar_data( $avatar, $args ) {
		$ida = initials_default_avatar();

		// Bail when we're not serving the avatar default
		if ( $ida->avatar_key !== buddypress()->grav_default->{$args['object']} )
			return false;

		// Since the avatar url could have been forged from uploaded images,
		// we only return a default avatar for failing gravatars. So, bail
		// when this is not a gravatar or when it is not a valid gravatar.
		if ( false === strpos( $avatar, 'gravatar.com' ) || $ida->is_valid_gravatar( $avatar ) )
			return false;

		// Define BP avatar id
		if ( empty( $args['object'] ) || 'user' === $args['object'] ) {
			$avatar_id = $args['item_id'];
		} else {
			$avatar_id = "bp-{$args['object']}-{$args['item_id']}";
		}

		// Get avatar details
		$details = $ida->get_avatar_details( $avatar_id );

		// Set size var
		if ( false !== $args['width'] ) {
			$args['size'] = $args['width'];
		} elseif ( 'thumb' === $args['type'] ) {
			$args['size'] = bp_core_avatar_thumb_width();
		} else {
			$args['size'] = bp_core_avatar_full_width();
		}

		// Get avatar url and class
		$data = array(
			'url'   => $ida->get_avatar_url  ( $details,       $args ),
			'class' => $ida->get_avatar_class( $args['class'], $args )
		);

		return $data;
	}

	/**
	 * Filter the name of the current avatar
	 *
	 * @since 1.1.0
	 *
	 * @uses get_blog_option()
	 * @uses bp_get_group_name()
	 * 
	 * @param string $name Avatar name
	 * @param int|string $avatar_id Avatar ID
	 * @return string Avatar name
	 */
	public function filter_avatar_name( $name, $avatar_id ) {

		// Users are handled by IDA by default.
		// Should we use bp_core_get_user_displayname()?
		// Should we provide support for XProfile fields?

		if ( is_string( $avatar_id ) ) {

			// Blog
			if ( 0 === strpos( $avatar_id, 'bp-blog-' ) ) {
				$name = get_blog_option( (int) str_replace( 'bp-blog-', '', $avatar_id ), 'blogname' );

			// Group
			} elseif ( 0 === strpos( $avatar_id, 'bp-group-' ) ) {
				$name = bp_get_group_name( groups_get_group( array( 'group_id' => (int) str_replace( 'bp-group-', '', $avatar_id ) ) ) );
			}
		}

		return $name;
	}
}

/**
 * Return single instance of this main plugin class
 *
 * @since 1.1.0
 * 
 * @return Initials_Default_Avatar_BuddyPress
 */
function initials_default_avatar_buddypress() {
	initials_default_avatar()->extend->buddypress = new Initials_Default_Avatar_BuddyPress;
}

endif; // class_exists
