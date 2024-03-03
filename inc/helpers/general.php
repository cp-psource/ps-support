<?php


/**
 * Translate dates
 * 
 * @param string $date The date
 * @return string Date
 */
function psource_support_get_translated_date( $date, $human_read = false ) {
	// get the date from gmt date in Y-m-d H:i:s
	$date_in_gmt = get_date_from_gmt($date);

	if ( $human_read ) {
		$from = mysql2date( 'U', $date_in_gmt, true );
		$transl_date = human_time_diff( $from, current_time( 'timestamp' ) );
	}
	else {
		$format = get_option("date_format") ." ". get_option("time_format");

		//get it localised
		$transl_date = mysql2date( $format, $date_in_gmt, true );
	}

	$transl_date = apply_filters( 'support_system_get_translated_date', $transl_date, $date, $human_read );
	return $transl_date;
}

function psource_support_get_model() {
	return MU_Support_System_Model::get_instance();
}

function psource_support_priority_dropdown( $args = array() ) {
	$defaults = array(
		'name' => 'ticket-priority',
		'id' => false,
		'show_empty' => __( '-- Priorität wählen --', 'psource-support' ),
		'selected' => null,
		'echo' => true
	);
	$args = wp_parse_args( $args, $defaults );

	extract( $args );

	if ( ! $id )
		$id = $name;

	if ( ! $echo )
		ob_start();

	$plugin_class = psource_support();
	$priorities = $plugin_class::$ticket_priority;
	?>
		<select name="<?php echo esc_attr( $name ); ?>" id="<?php echo esc_attr( $id ); ?>">
			<?php if ( ! empty( $show_empty ) ): ?>	
				<option value="" <?php selected( $selected === null ); ?>><?php echo esc_html( $show_empty ); ?></option>
			<?php endif; ?>

			<?php foreach ( $priorities as $key => $value ): ?>
				<option value="<?php echo $key; ?>" <?php selected( $selected === $key ); ?>><?php echo esc_html( $value ); ?></option>
			<?php endforeach; ?>

		</select>
	<?php

	if ( ! $echo )
		return ob_get_clean();
}

function psource_support_super_admins_dropdown( $args = array() ) {
	$defaults = array(
		'name' => 'super-admins',
		'id' => false,
		'show_empty' => __( 'Select a staff', 'psource-support' ),
		'selected' => null,
		'echo' => true,
		'value' => 'username' // Or integer
	);
	$args = wp_parse_args( $args, $defaults );

	$plugin = psource_support();
	$super_admins = MU_Support_System::get_super_admins();

	extract( $args );

	if ( ! $id )
		$id = $name;

	if ( ! $echo )
		ob_start();
	?>
		<select name="<?php echo $name; ?>" id="<?php echo $id; ?>">
			<?php if ( ! empty( $show_empty ) ): ?>	
				<option value="" <?php selected( empty( $selected ) ); ?>><?php echo esc_html( $show_empty ); ?></option>
			<?php endif; ?>
			<?php foreach ( $super_admins as $key => $user_name ): ?>
				<?php $user = get_user_by( 'login', $user_name ); ?>
				<?php $option_value = $value === 'username' ? $user_name : $key; ?>
				<?php $option_selected = selected( $selected, $option_value, false ); ?>
				<option value="<?php echo esc_attr( $option_value ); ?>" <?php echo $option_selected; ?>><?php echo $user->display_name; ?></option>
			<?php endforeach; ?>
		</select>
	<?php

	if ( ! $echo )
		return ob_get_clean();
}

function psource_support_get_errors($setting = null) {
    global $support_system_errors;

    if (!is_array($support_system_errors) || empty($support_system_errors)) {
        return array();
    }

    if ($setting) {
        $setting_errors = array();
        foreach ($support_system_errors as $details) {
            if (isset($details['setting']) && $setting === $details['setting']) {
                $setting_errors[] = $details;
            }
        }
        return $setting_errors;
    }

    return $support_system_errors;
}

function psource_support_add_error( $setting, $code, $message ) {
	global $support_system_errors;

	$support_system_errors[] = array(
		'setting' => $setting,
		'code'    => $code,
		'message' => $message,
	);
}

function psource_support_get_version() {
	return PSOURCE_SUPPORT_PLUGIN_VERSION;
}


function psource_support_register_main_script() {
	$suffix = '.min';
	if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG )
		$suffix = '';

	wp_register_script( 'support-system', PSOURCE_SUPPORT_PLUGIN_URL . 'assets/js/support-system' . $suffix . '.js', array( 'jquery' ), psource_support_get_version(), true );

	$l10n = array(
		'ajaxurl' => admin_url( 'admin-ajax.php' )
	);
	wp_localize_script( 'support-system', 'support_system_strings', $l10n );
}

function psource_support_enqueue_main_script() {
	if ( ! wp_script_is( 'support-system', 'registered' ) )
		psource_support_register_main_script();

	wp_enqueue_script( 'support-system' );

}

function psource_support_enqueue_foundation_scripts( $in_footer = true ) {
	$suffix = '.min';
	if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG )
		$suffix = '';

	wp_enqueue_script( 'support-system-foundation-js', PSOURCE_SUPPORT_PLUGIN_URL . 'assets/js/foundation' . $suffix . '.js', array( 'jquery' ), psource_support_get_version(), $in_footer );
}


/**
 * Adds an integration class to Support System
 *
 * @param $classname The class name of the integrator
 */
function psource_support_add_integrator( $classname ) {
	if ( class_exists( $classname ) ) {
		$plugin = psource_support();
		$r = new ReflectionClass( $classname );
		$plugin->add_integrator( $r->newInstance() );
	}
}

