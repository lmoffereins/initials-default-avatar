/**
 * Initials Default Avatar Admin scripts
 *
 * @package Initials Default Avatar
 * @subpackage Administration
 */

/* globals initialsDefaultAvatarAdmin */
( function( $ ) {

	// Bail when the default is network-defined
	if ( ! initialsDefaultAvatarAdmin.settings.enabled ) {
		return;
	}

	// Get our settings field
	var $ida = $( '#initials-default-avatar-wrapper' );

	// Setup WordPress color pickers. Hide picker labels
	$ida.find( '.ida-wp-color-picker' ).wpColorPicker();

	// On placeholder service selection change
	$ida.find( '#placeholder-service' ).on( 'change', function() {
		var $this   = $(this),
		    service = $this.val(),
		    dot     = service.indexOf( '.' ),
		    opts    = $ida.find( '.service-options-wrap' );

		// Change learn-more link src and text
		if ( service.length > 0 ) {
			$this.parent()
				.find( '.learn-more' ).show()
					.find( '.service-url' ).attr({
						'href': 'http://' + service
					}).text( service );
		} else {
			$this.parent()
				.find( '.learn-more' ).hide();
		}

		// Backslash single dot in service name
		// Since dots will match class names, not ids
		if ( dot !== -1 ) {
			service = service.substring( 0, dot ) + '\\' + service.substring( dot );
		}

		// Hide all service options and show selected one
		opts.hide().filter( '#service-' + service ).show();
	});

	/**
	 * Reload image with new options input.
	 * This method isn't solid. Testing and tweaking required.
	 */
	$ida.find('.service-option').on('focus', function() {
		$(this).data('prev', this.value);
	}).on( 'change', function() {
		var $this = $(this),
		    prev  = $this.data('prev'),
		    $img  = $this.parents('.service-options-wrap').find('.avatar-preview img');

		// Update src paramater by finding previous value and replace it with new input
		$img.attr({'src': replace( prev, this.value, $img.attr('src') ) });

		// Update previous value
		$this.data('prev', this.value);
	});

	/**
	 * Replace a substring with a given other string
	 *
	 * @since 1.0.0
	 *
	 * @todo Switch for different option types and positions, like multiple colors
	 * 
	 * @param  {String} replace Part to replace
	 * @param  {String} replaceWith Part to place
	 * @param  {String} str String to replace in
	 * @return {String}
	 */
	var replace = function( replace, replaceWith, str ) {

		// Bail if no replace found
		if ( str.indexOf( replace ) === -1 ) {
			return str;
		}

		return str.substr(0, str.indexOf( replace )) + replaceWith + str.substr(str.indexOf( replace ) + replace.length );
	};

})( jQuery );

/**
 * Handles service selection and service options display
 */
( function( $ ) {

	// Bail when we're not toggling settings
	if ( ! initialsDefaultAvatarAdmin.settings.enabled ) {
		return;
	}

	var networkAdmin = $( 'body' ).is( '.network-admin' ),
	    avatar_default = networkAdmin ? $( 'input[name="initials_default_avatar_network_default"]' ) : $( 'input[name="avatar_default"]' ),
	    settings_field = $( '#initials-default-avatar-wrapper' ).parents( 'tr' ).first();

	/**
	 * Return whether the avatar default is selected
	 *
	 * @since 1.0.0
	 *
	 * @param  {String} value Selected value
	 * @return {Boolean}
	 */
	var isDefaultSelected = function() {
		return networkAdmin ? avatar_default.is( ':checked' ) : avatar_default.filter( ':checked' ).val() === initialsDefaultAvatarAdmin.settings.avatarKey;
	};

	// Add classes to our field's parent <tr>
	settings_field.addClass( function() {
		var c = 'avatar-settings';

		// Hide field when avatars are not in use
		if ( ! networkAdmin && ! $( '#show_avatars' ).is( ':checked' ) ) {
			c += ' hide-if-js';
		}

		// Hide field when our default is not selected
		if ( ! isDefaultSelected() ) {
			c += ' hidden';
		}

		return c;
	});

	// Show service settings on default selection
	avatar_default.change( function() {
		settings_field.toggleClass( 'hidden', ! isDefaultSelected() );
	});

})( jQuery );

/**
 * Handles removing setting content when the avatar default is network-defined
 */
( function( $ ) {

	// Bail when the default is not network-defined
	if ( ! initialsDefaultAvatarAdmin.settings.networkDefault.isActive ) {
		return;
	}

	// Find the 'Avatars' heading
	$( 'h2.title' ).filter( function() {
		return this.innerHTML === initialsDefaultAvatarAdmin.settings.networkDefault.heading;
	})

		// Replace the section description
		.next( 'p' ).html( initialsDefaultAvatarAdmin.settings.networkDefault.message )

		// Remove the settings table
		.next( 'table' ).remove();

})( jQuery );
