/**
 * Initials Default Avatar script
 *
 * @package Initials Default Avatar
 * @subpackage Scripts
 */

jQuery(document).ready( function($) {

	// Get our settings field
	var $ida = $('#initials-default-avatar');

	// Setup WordPress color pickers. Hide picker labels
	$ida.find('.ida-wp-color-picker').wpColorPicker();

	// On placeholder service selection change
	$ida.find('#placeholder-service').on( 'change', function() {
		var $this   = $(this),
		    service = $this.val(),
		    dot     = service.indexOf( '.' ),
		    opts    = $ida.find('.service-options');

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
		// Since dots will match class names, not ids
		if ( dot != -1 )
			service = service.substring( 0, dot ) + '\\' + service.substring( dot );

		// Hide all service options and show selected one
		opts.hide().filter('#service-' + service).show();
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
		    $img  = $this.parents('.service-options').find('.avatar-preview img');

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
		if ( str.indexOf( replace ) === -1 )
			return str;

		return str.substr(0, str.indexOf( replace )) + replaceWith + str.substr(str.indexOf( replace ) + replace.length );
	};

});