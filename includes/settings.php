<?php

/**
 * Initials Default Avatar Settings Functions
 *
 * @package Initials Default Avatar
 * @subpackage Administration
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Sanitize selected service setting
 *
 * @since 1.0.0
 * 
 * @param string $input Service selected
 * @return string|bool Sanitized input
 */
function initials_default_avatar_admin_sanitize_service( $input ) {

	// Service selected exists
	if ( ! initials_default_avatar_get_service( $input ) ) {
		$input = false;
	}

	return $input;
}

/**
 * Sanitize selected service options setting
 *
 * @since 1.0.0
 *
 * @todo Fix color input sanitization
 *
 * @param array $input Service options
 * @return array|false Sanitized value
 */
function initials_default_avatar_admin_sanitize_service_options( $input ) {

	// Setup clean value
	$value = array();

	// Loop all services
	foreach ( $input as $service => $options ) {
		$_service = initials_default_avatar_get_service( $service );
		$value[ $service ] = array();

		foreach ( $options as $option => $_input ) {

			// Indicate font size option type
			if ( 'fontsize' == $option )
				$_service->options[ $option ]['type'] = 'percentage';

			// Sanitize per option type
			switch ( $_service->options[ $option ]['type'] ) {

				case 'number'     :
				case 'percentage' :
					$_input = absint( $_input );
					break;

				case 'select' :
					if ( ! in_array( $_input, array_keys( $_service->options[ $option ]['options'] ) ) )
						$_input = false;
					break;

				case 'text'     :
				case 'textarea' :
					$_input = wp_kses( $_input );
					break;

				case 'color' :
					// preg_match('/#([0-9abcdef]+?){3,6}/i', $_input, $matches);
					// if ( $matches[1] )
					// 	$_input = $matches[1];
					// else
					// 	$_input = false;
					
					// Strip hex hash sign
					$_input = str_replace( '#', '', $_input );
					break;
			}

			$value[ $service ][ $option ] = $_input;
		}
	}

	return apply_filters( 'initials_default_avatar_admin_sanitize_service_options', $value );
}

/**
 * Output settings field for the placeholder service
 *
 * @since 1.0.0
 */
function initials_default_avatar_admin_setting_placeholder_service() {
	$selected = get_option( 'initials_default_avatar_service' ); ?>

	<div id="initials-default-avatar">
		<label for="placeholder-service">
			<select name="initials_default_avatar_service" id="placeholder-service">
				<option value=""><?php _e( 'Select a service', 'initials-default-avatar' ); ?></option>
				<?php foreach ( initials_default_avatar_get_services() as $service => $args ) : ?>

				<option value="<?php echo $service; ?>" <?php selected( $selected, $service ); ?>><?php echo $args->title; ?></option>

				<?php endforeach; ?>
			</select>
			<?php esc_html_e( 'Select a placeholder service.', 'initials-default-avatar' ); ?>
			<span class="learn-more"><?php printf( __( 'See %s for more information.', 'initials-default-avatar' ), sprintf( '<a class="service-url" target="_blank" href="http://%1$s">%1$s</a>', $service ) ); ?></span>
		</label>

		<?php initials_default_avatar_admin_setting_service_options(); ?>
	</div>

	<?php

	// Enqueue script. See wp-admin/options-discussion.php since WP 4.2.
	add_action( 'admin_print_footer_scripts', 'initials_default_avatar_admin_settings_js', 9 );
}

/**
 * Output settings fields for service options
 *
 * @since 1.0.0
 */
function initials_default_avatar_admin_setting_service_options() {
	$current = initials_default_avatar_get_service();

	// Define sample avatar details
	$details = initials_default_avatar_get_avatar_details( 0, _x( 'Sample', 'default avatar display name', 'initials-default-avatar' ) );
	$args    = array( 'size' => 100 );

	// Loop all services if they have options defined
	foreach ( initials_default_avatar_get_services() as $service ) :

		// Hide non-selected services
		$style = ( $current->name !== $service->name ) ? 'style="display:none;"' : ''; ?>

		<div id="service-<?php echo $service->name; ?>" class="service-options" <?php echo $style; ?>>
			<h4 class="title"><?php esc_html_e( 'Service options', 'initials-default-avatar' ); ?></h4>

			<div class="avatar-preview" style="float:left; margin-right: 10px;">
				<img src="<?php echo initials_default_avatar_get_avatar_url( $details, $args, $service->name ); ?>" class="<?php echo initials_default_avatar_get_avatar_class( '', $args, $service->name ); ?>" width="100" height="100" />
			</div>

			<?php if ( isset( $service->options ) ) : ?>

			<div class="options" style="float:left;">
				<?php foreach ( $service->options as $field => $option ) : ?>

				<?php initials_default_avatar_service_option_field( $service, $field, $option ); ?><br>

				<?php endforeach; ?>
			</div>

			<?php endif; ?>

		</div>

	<?php endforeach;
}

/**
 * Output the service option field
 *
 * @since 1.0.0
 *
 * @todo Position label next to color input
 *
 * @param string $service Service name
 * @param string $field Option field name
 * @param array $args Option args
 */
function initials_default_avatar_service_option_field( $service, $field, $args ) {
	$service = initials_default_avatar_get_service( $service );

	// Bail when the service does not exist
	if ( ! $service )
		return;

	// Field is set without arguments
	if ( ! is_array( $args ) ) {
		$field = $args;
		$args  = array();
	}

	$s       = $service;
	$service = $service->name;

	// Setup field atts
	$id    = "initials-default-avatar-options-{$service}-{$field}";
	$name  = "initials_default_avatar_options[{$service}][{$field}]";
	$value = initials_default_avatar_get_service_option( $field, $service );
	if ( empty( $value ) ) {
		$value = '';
	}
	$label = isset( $args['label'] ) ? $args['label'] : '';

	// Setup font size vars
	if ( 'fontsize' == $field ) {
		$label = __( 'Font size in percentage', 'initials-default-avatar' );
		$args['type'] = 'percentage';
	}

	// What type is this field?
	switch ( $args['type'] ) {

		case 'select' :
			$value = esc_attr( $value );
			if ( empty( $value ) ) {
				$value = key( $s->options[ $field ]['options'] );
			}

			$input  = "<select name='$name' id='$id' class='service-option'>";
			$input .=	'<option>' . __( 'Select an option', 'initials-default-avatar' ) . '</option>';
			foreach ( $args['options'] as $option => $option_label ) {
				$input .= "<option value='$option'" . selected( $value, $option, false ) . ">$option_label</option>";
			}
			$input .= '</select>';
			break;

		case 'text' :
			$value = esc_attr( $value );
			$input = "<input type='text' name='{$name}' id='{$id}' class='service-option regular-text' value='{$value}' />";
			break;

		case 'number'  :
			$value = esc_attr( $value );
			$input = "<input type='number' name='{$name}' id='{$id}' class='service-option small-text' value='{$value}' />";
			break;

		case 'percentage'  :
			$value = esc_attr( $value );
			if ( empty( $value ) && 'fontsize' == $field ) {
				$value = 65;
			}

			$input = "<input type='number' name='{$name}' id='{$id}' class='service-option small-text' value='{$value}' step='1' min='0' max='99' />";
			break;

		case 'textarea' :
			$value = esc_textarea( $value );
			$input = "<textarea name='{$name}' id='{$id}' class='service-option'>{$value}</textarea>";
			break;

		case 'color' :
			$value = esc_attr( $value );
			$input = "<input type='text' name='{$name}' id='{$id}' class='service-option ida-wp-color-picker' value='#{$value}' />";
			break;

		default :
			$input = apply_filters( 'initials_default_avatar_service_option_field_input', '', $service, $field, compact( 'args', 'id', 'name', 'value' ) );
			break;
	}

	// Setup field with input label
	$_field = "<label for='{$id}' class='option-{$args['type']}'>{$input} <span class='description'>{$label}</span></label>";

	// Output break, input, label
	echo apply_filters( 'initials_default_avatar_service_option_field', $_field, $service, $field, $args );
}

/**
 * Output js to bring our field in parity with WP 4.2's discussion js
 *
 * @see options_discussion_add_js()
 *
 * @since 1.1.0
 */
function initials_default_avatar_admin_settings_js() { ?>
	<script>
	( function($) {
		var show_avatars = $( '#show_avatars' ),
		    avatar_default = $( 'input[name="avatar_default"]' ),
		    avatar_key     = '<?php echo initials_default_avatar_get_avatar_key(); ?>',
		    settings_field = $( '#initials-default-avatar' ).parents( 'tr' ).first();

		// Add classes to our field's parent <tr>
		settings_field.addClass( function() {
			var c = 'avatar-settings';

			// Hide field when avatars are not in use
			if ( ! show_avatars.is( ':checked' ) ) {
				c += ' hide-if-js';
			}

			// Hide field when our default is not selected
			if ( avatar_default.filter( ':checked' ).val() !== avatar_key ) {
				c += ' hidden';
			}

			return c;
		});

		// Show service settings on default selection
		avatar_default.change( function() {
			settings_field.toggleClass( 'hidden', this.value !== avatar_key );
		});

	})( jQuery );
	</script>
	<?php
}
