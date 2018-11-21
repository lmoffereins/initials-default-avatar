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

	/**
	 * Sanitize the service options
	 *
	 * @since 1.0.0
	 *
	 * @param array $value Service options
	 */
	return (array) apply_filters( 'initials_default_avatar_admin_sanitize_service_options', $value );
}

/**
 * Output settings field for the placeholder service
 *
 * @since 1.0.0
 *
 * @param array $args Settings field arguments
 */
function initials_default_avatar_admin_setting_placeholder_service( $args = array() ) {

	// Parse default arguments
	$args = wp_parse_args( $args, array(
		'setting'  => false,
		'callback' => 'get_option'
	) );

	// Bail when without setting name
	if ( empty( $args['setting'] ) )
		return;

	// Get selected value
	$selected = call_user_func_array( $args['callback'], array( $args['setting'] ) );

	?>

	<div id="initials-default-avatar-wrapper">
		<p><?php esc_html_e( 'The generated images for the Initials avatars are requested from an external online placeholder service. You can choose from multiple services. These services are not affiliated with this plugin.', 'initials-default-avatar' ); ?></p>

		<label for="placeholder-service">
			<select name="initials_default_avatar_service" id="placeholder-service">
				<option value=""><?php esc_html_e( 'Select a service', 'initials-default-avatar' ); ?></option>
				<?php foreach ( initials_default_avatar_get_services() as $service => $args ) : ?>

				<option value="<?php echo $service; ?>" <?php selected( $selected, $service ); ?>><?php echo $args->title; ?></option>

				<?php endforeach; ?>
			</select>
			<?php esc_html_e( 'Select a placeholder service.', 'initials-default-avatar' ); ?>
			<span class="learn-more"><?php printf( esc_html__( 'See %s for more information.', 'initials-default-avatar' ), sprintf( '<a class="service-url" target="_blank" href="http://%1$s">%1$s</a>', $service ) ); ?></span>
		</label>

		<?php initials_default_avatar_admin_setting_service_options( $selected ); ?>
	</div>

	<?php
}

/**
 * Output settings fields for service options
 *
 * @since 1.0.0
 */
function initials_default_avatar_admin_setting_service_options( $current = '' ) {

	// Get the selected service
	$current = $current ? initials_default_avatar_get_service( $current ) : false;

	// Define sample avatar details
	$details = initials_default_avatar_get_avatar_details( 0, _x( 'Sample', 'default avatar display name', 'initials-default-avatar' ) );
	$args    = array( 'size' => 100 );

	// Loop all services if they have options defined
	foreach ( initials_default_avatar_get_services() as $service ) :

		// Hide non-selected services
		$style = ( ! $current || $current->name !== $service->name ) ? 'style="display:none;"' : ''; ?>

		<div id="service-<?php echo $service->name; ?>" class="service-options-wrap" <?php echo $style; ?>>
			<h4 class="title"><?php esc_html_e( 'Service options', 'initials-default-avatar' ); ?></h4>

			<div class="avatar-preview">
				<img src="<?php echo initials_default_avatar_get_avatar_url( $details, $args, $service->name ); ?>" class="<?php echo initials_default_avatar_get_avatar_class( '', $args, $service->name ); ?>" width="100" height="100" />
			</div>

			<?php if ( isset( $service->options ) ) : ?>

			<div class="service-options">
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

	// Get current service
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
	$id    = "initials-default-avatar-service-options-{$service}-{$field}";
	$name  = "initials_default_avatar_service_options[{$service}][{$field}]";
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
	$markup = "<label for='{$id}' class='option-{$args['type']}'>{$input} <span class='description'>{$label}</span></label>";

	/**
	 * Filter the service option field
	 *
	 * @since 1.0.0
	 *
	 * @param string $markup Option field markup
	 * @param object $service Service data
	 * @param string $field Field name
	 * @param array $args Field arguments
	 */
	$markup = apply_filters( 'initials_default_avatar_service_option_field', $markup, $service, $field, $args );

	echo $markup;
}

/** Network *******************************************************************/

/**
 * Return the network settings
 *
 * @since 2.0.0
 *
 * @uses apply_filters() Calls 'initials_default_avatar_network_admin_settings'
 * @return array Network settings
 */
function initials_default_avatar_network_admin_settings() {

	/**
	 * Filter the plugin network admin settings
	 *
	 * @since 2.0.0
	 *
	 * @param array $args Network admin settings
	 */
	return (array) apply_filters( 'initials_default_avatar_network_admin_settings', array(

		// Default avatar
		'initials_default_avatar_network_default' => array(
			'title'             => esc_html__( 'Network default', 'initials-default-avatar' ),
			'callback'          => 'initials_default_avatar_admin_setting_callback_checkbox',
			'sanitize_callback' => 'intval',
			'args'              => array(
				'setting'     => 'initials_default_avatar_network_default',
				'description' => esc_html__( "Use the Initials avatar as your network's default avatar. Overrides any site avatar settings.", 'initials-default-avatar' ),
				'callback'    => 'get_site_option'
			)
		),

		// Placeholder service
		'initials_default_avatar_service' => array(
			'title'             => esc_html__( 'Placeholder service', 'initials-default-avatar' ),
			'callback'          => 'initials_default_avatar_admin_setting_placeholder_service',
			'sanitize_callback' => 'initials_default_avatar_admin_sanitize_service',
			'args'              => array(
				'setting'     => 'initials_default_avatar_service',
				'callback'    => 'get_site_option'
			)
		),

		// Service options
		'initials_default_avatar_service_options' => array(
			'title'             => esc_html__( 'Service options', 'initials-default-avatar' ),
			'callback'          => false,
			'sanitize_callback' => 'initials_default_avatar_admin_sanitize_service_options',
			'args'              => array()
		)
	) );
}

/**
 * Display the network settings fields
 *
 * @since 2.0.0
 */
function initials_default_avatar_network_admin_settings_section() {

	// Get the settings to render
	$settings = initials_default_avatar_network_admin_settings();
	$settings = wp_list_filter( $settings, array( 'callback' => false ), 'NOT' );

	// Bail when there are no settings registered
	if ( ! $settings )
		return;

	?>

	<h2 id="initials-default-avatar"><?php esc_html_e( 'Initials Default Avatar Settings', 'initials-default-avatar' ); ?></h2>
	<table id="menu" class="form-table">
		<?php foreach ( $settings as $setting ) : ?>
		<tr>
			<th scope="row"><?php echo $setting['title']; ?></th>
			<td><?php call_user_func_array( $setting['callback'], array( $setting['args'] ) ); ?></td>
		</tr>
		<?php endforeach; ?>
	</table>

	<?php
}

/**
 * Save the plugin network settings
 *
 * @since 2.0.0
 */
function initials_default_avatar_network_admin_save_settings() {

	// Walk all network settings
	foreach ( initials_default_avatar_network_admin_settings() as $setting => $args ) {

		// Get saved value
		$value = isset( $_REQUEST[ $setting ] ) ? $_REQUEST[ $setting ] : null;

		// Sanitize network value
		if ( isset( $args['sanitize_callback'] ) ) {
			$value = call_user_func_array( $args['sanitize_callback'], array( $value ) );
		}

		// Save sanitized value
		update_site_option( $setting, $value );
	}
}

/**
 * Display the settings field for Network Default
 *
 * @since 2.0.0
 *
 * @param array $args Settings field arguments
 */
function initials_default_avatar_admin_setting_callback_checkbox( $args = array() ) {

	// Parse default arguments
	$args = wp_parse_args( $args, array(
		'setting'     => false,
		'description' => '',
		'callback'    => 'get_option'
	) );

	// Bail when without setting name
	if ( empty( $args['setting'] ) )
		return;

	?>

	<input name="<?php echo $args['setting']; ?>" id="<?php echo $args['setting']; ?>" type="checkbox" value="1" <?php checked( 1, call_user_func_array( $args['callback'], array( $args['setting'] ) ) ); ?>/>
	<label for="<?php echo $args['setting']; ?>"><?php echo $args['description']; ?></label>

	<?php
}
