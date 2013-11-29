/**
 * Initials Default Avatar script
 *
 * @package Initials Default Avatar
 * @subpackage Scripts
 */

jQuery(document).ready( function($) {

	// Setup WordPress color pickers. Hide picker labels
	$('.ida-wp-color-picker').wpColorPicker();

	// On placeholder service selection
	$('#initials-default-avatar-service').on( 'change', function() {
		var $this   = $(this),
		    service = $this.val(),
		    dot     = service.indexOf( '.' ),
		    opts    = $('.initials-default-avatar-service-options');

		// Change learn-more link src and text
		if ( service.length > 0 ) {
			$this.parent()
				.find('.learn-more').show()
					.find('.service-url').attr({ 'href': 'http://' + service }).text( service );
		} else {
			$this.parent()
				.find('.learn-more').hide();
		}

		// Backslash single dot in service name
		if ( dot != -1 )
			service = service.substring( 0, dot ) + '\\' + service.substring( dot );

		// Hide all service options and show selected one
		opts.hide().filter('#initials-default-avatar-service-' + service).show();
	});

});