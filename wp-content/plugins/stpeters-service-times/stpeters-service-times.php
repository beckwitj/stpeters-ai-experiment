<?php
/**
 * Plugin Name:       St. Peter's Service Times
 * Description:       Single source of truth for Sunday service times. Provides a top-level "Service Times" admin screen (wp-admin sidebar) where parish staff maintain the roster — time, rite, mode — in one place, and exposes stp_service_times() for a future dynamic front-end block. Deliberately NO repeat-schedule engine: staff revise the data by hand when it changes.
 * Version:           0.1.0
 * Author:            St. Peter's Anglican Church
 * Text Domain:       stpeters-service-times
 * Requires at least: 6.7
 * Requires PHP:      7.2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Option name that holds the service roster (array of rows). */
define( 'STP_SERVICE_TIMES_OPTION', 'stp_service_times' );

/** Admin menu/page slug. */
define( 'STP_SERVICE_TIMES_SLUG', 'stp-service-times' );

/**
 * Capability required to view and edit the screen. Defaults to administrators
 * (manage_options). Override via the constant if parish staff (e.g. Editors)
 * should maintain times: define( 'STP_SERVICE_TIMES_CAPABILITY', 'edit_pages' )
 * in wp-config.php before this plugin loads.
 */
if ( ! defined( 'STP_SERVICE_TIMES_CAPABILITY' ) ) {
	define( 'STP_SERVICE_TIMES_CAPABILITY', 'manage_options' );
}

/** Allowed rites for a service. */
function stp_service_times_allowed_rites() {
	return array( 'BAS', 'BCP' );
}

/** Allowed modes for a service. */
function stp_service_times_allowed_modes() {
	return array( 'hybrid', 'zoom', 'in-person' );
}

/**
 * Default roster (2026 example, per DESIGN.md). Only a primary mode is
 * modeled; week-to-week nuance is deliberately not (no schedule engine).
 *
 * @return array[] List of rows with keys: time, rite, mode.
 */
function stp_service_times_defaults() {
	return array(
		array(
			'time' => '8:00 am',
			'rite' => 'BCP',
			'mode' => 'zoom',
		),
		array(
			'time' => '9:15 am',
			'rite' => 'BAS',
			'mode' => 'hybrid',
		),
		array(
			'time' => '11:00 am',
			'rite' => 'BCP',
			'mode' => 'hybrid',
		),
	);
}

/**
 * Sanitize one roster row, or null if the row is empty.
 *
 * @param mixed   $row   Raw row (array expected).
 * @param int|string $index Row index, for error context.
 * @return array|null Sanitized row, or null when entirely empty.
 */
function stp_service_times_sanitize_row( $row, $index ) {
	$row = is_array( $row ) ? $row : array();

	$time = isset( $row['time'] ) ? sanitize_text_field( wp_unslash( (string) $row['time'] ) ) : '';

	if ( '' === $time ) {
		return null; // Empty row: drop it.
	}

	$rite = isset( $row['rite'] ) ? sanitize_key( (string) $row['rite'] ) : 'BAS';
	if ( ! in_array( $rite, stp_service_times_allowed_rites(), true ) ) {
		$rite = 'BAS';
	}

	$mode = isset( $row['mode'] ) ? sanitize_key( (string) $row['mode'] ) : 'hybrid';
	if ( ! in_array( $mode, stp_service_times_allowed_modes(), true ) ) {
		$mode = 'hybrid';
	}

	return array(
		'time' => $time,
		'rite' => $rite,
		'mode' => $mode,
	);
}

/**
 * Sanitize a whole roster (array of rows), re-indexing contiguously.
 *
 * @param mixed $value Raw option value.
 * @return array[] Clean list of rows.
 */
function stp_service_times_sanitize( $value ) {
	$clean = array();
	if ( is_array( $value ) ) {
		foreach ( $value as $index => $row ) {
			$r = stp_service_times_sanitize_row( $row, $index );
			if ( null !== $r ) {
				$clean[] = $r;
			}
		}
	}
	return $clean;
}

/**
 * Public getter for the roster. Returns the option when set, otherwise the
 * defaults. This is the API a future front-end block reads from.
 *
 * @return array[] Normalized rows.
 */
function stp_service_times() {
	$value = get_option( STP_SERVICE_TIMES_OPTION );
	if ( ! is_array( $value ) || array() === $value ) {
		return stp_service_times_defaults();
	}
	return stp_service_times_sanitize( $value );
}

/**
 * Register the top-level "Service Times" menu in the wp-admin sidebar.
 */
function stp_service_times_admin_menu() {
	add_menu_page(
		__( 'Service Times', 'stpeters-service-times' ),
		__( 'Service Times', 'stpeters-service-times' ),
		STP_SERVICE_TIMES_CAPABILITY,
		STP_SERVICE_TIMES_SLUG,
		'stp_service_times_admin_page',
		'dashicons-clock',
		26
	);
}
add_action( 'admin_menu', 'stp_service_times_admin_menu' );

/**
 * Render one table row for a roster entry.
 *
 * @param int   $index Row index (form array key).
 * @param array $row   Normalized row.
 * @return string Table row HTML.
 */
function stp_service_times_row_markup( $index, $row ) {
	$rite_opts = '';
	foreach ( stp_service_times_allowed_rites() as $rite ) {
		$rite_opts .= sprintf(
			'<option value="%1$s"%2$s>%1$s</option>',
			esc_attr( $rite ),
			selected( $rite, $row['rite'], false )
		);
	}
	$mode_opts = '';
	foreach ( stp_service_times_allowed_modes() as $mode ) {
		$mode_opts .= sprintf(
			'<option value="%1$s"%2$s>%1$s</option>',
			esc_attr( $mode ),
			selected( $mode, $row['mode'], false )
		);
	}

	return '<tr class="stp-st-row">'
		. '<td><input type="text" name="stp_service_times[' . esc_attr( $index ) . '][time]" value="' . esc_attr( $row['time'] ) . '" class="regular-text" placeholder="e.g. 8:00 am" /></td>'
		. '<td><select name="stp_service_times[' . esc_attr( $index ) . '][rite]">' . $rite_opts . '</select></td>'
		. '<td><select name="stp_service_times[' . esc_attr( $index ) . '][mode]">' . $mode_opts . '</select></td>'
		. '<td><button type="button" class="button-link stp-st-remove" aria-label="Remove service">Remove</button></td>'
		. '</tr>';
}

/**
 * Render the Service Times admin screen.
 */
function stp_service_times_admin_page() {
	if ( ! current_user_can( STP_SERVICE_TIMES_CAPABILITY ) ) {
		wp_die( esc_html__( 'You do not have permission to edit service times.', 'stpeters-service-times' ) );
	}

	$updated        = false;
	$errors         = array();

	// Handle a save posted to this same screen.
	if ( isset( $_POST['stp_service_times_nonce'] ) && wp_verify_nonce( sanitize_key( $_POST['stp_service_times_nonce'] ), 'stp_service_times_save' ) ) {
		if ( current_user_can( STP_SERVICE_TIMES_CAPABILITY ) && isset( $_POST['stp_service_times'] ) ) {
			$rows = stp_service_times_sanitize( wp_unslash( (array) $_POST['stp_service_times'] ) );
			if ( array() === $rows ) {
				$errors[] = __( 'At least one service with a time is required.', 'stpeters-service-times' );
			} else {
				update_option( STP_SERVICE_TIMES_OPTION, $rows );
				$updated = true;
			}
		}
	}

	$rows = stp_service_times();
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Service Times', 'stpeters-service-times' ); ?></h1>
		<p class="description">
			<?php esc_html_e( 'This is the single place to maintain Sunday service times. Saving here updates every page that shows them — no page editing needed. This is a manual roster revised by hand when it changes; nothing is auto-scheduled.', 'stpeters-service-times' ); ?>
		</p>

		<?php if ( $updated ) : ?>
			<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Service times saved.', 'stpeters-service-times' ); ?></p></div>
		<?php endif; ?>
		<?php foreach ( $errors as $error ) : ?>
			<div class="notice notice-error"><p><?php echo esc_html( $error ); ?></p></div>
		<?php endforeach; ?>

		<form method="post" action="" id="stp-service-times-form">
			<?php wp_nonce_field( 'stp_service_times_save', 'stp_service_times_nonce' ); ?>
			<table class="widefat stp-service-times" style="max-width:860px">
				<thead>
					<tr>
						<th scope="col" style="width:26%"><?php esc_html_e( 'Time', 'stpeters-service-times' ); ?></th>
						<th scope="col" style="width:14%"><?php esc_html_e( 'Rite', 'stpeters-service-times' ); ?></th>
						<th scope="col" style="width:17%"><?php esc_html_e( 'Mode', 'stpeters-service-times' ); ?></th>
						<th scope="col"><?php esc_html_e( '', 'stpeters-service-times' ); ?></th>
					</tr>
				</thead>
				<tbody id="stp-service-times-rows">
					<?php
					foreach ( $rows as $index => $row ) {
						echo stp_service_times_row_markup( $index, $row ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- markup is escaped internally.
					}
					?>
				</tbody>
			</table>

			<p>
				<button type="button" class="button" id="stp-service-times-add">
					<?php esc_html_e( 'Add service', 'stpeters-service-times' ); ?>
				</button>
			</p>

			<?php submit_button( __( 'Save service times', 'stpeters-service-times' ) ); ?>
		</form>

		<template id="stp-service-times-row-tpl">
			<?php
			echo stp_service_times_row_markup( 0, array( 'time' => '', 'rite' => 'BAS', 'mode' => 'hybrid' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			?>
		</template>
	</div>
	<?php
}

/**
 * Enqueue the small admin script that handles Add/Remove rows.
 *
 * @param string $hook Current admin page hook.
 */
function stp_service_times_admin_assets( $hook ) {
	if ( 'toplevel_page_stp-service-times' !== $hook ) {
		return;
	}
	wp_enqueue_script(
		'stp-service-times-admin',
		plugin_dir_url( __FILE__ ) . 'assets/admin.js',
		array(),
		'0.1.0',
		true
	);
}
add_action( 'admin_enqueue_scripts', 'stp_service_times_admin_assets' );

/* ----------------------------------------------------------------------- */
/* Front-end dynamic block: stpeters/worship-times                          */
/* ----------------------------------------------------------------------- */

/**
 * Human-label a stored mode for display.
 *
 * 'zoom' is the Zoom product name (capitals); 'hybrid' and 'in person' are
 * lowercase adverbs. The rest of the stored value passes through as-is.
 *
 * @param string $mode Stored mode (zoom|hybrid|in-person).
 * @return string Display label.
 */
function stp_service_times_display_mode( $mode ) {
	switch ( $mode ) {
		case 'zoom':
			return 'Zoom';
		case 'in-person':
			return 'in person';
		default:
			return $mode; // 'hybrid' and anything else.
	}
}

/**
 * Render the worship-times block. One line per service: bold time, then
 * " · rite · mode". Wrapped in a single paragraph with <br> separators so the
 * rows read as a tight, scannable list (same look as the original homepage
 * band). The visual presentation (size, centering, on-dark color) lives in the
 * child theme's style.css under .stp-service-line.
 *
 * @param array  $attributes Block attributes.
 * @param string $content    Inner blocks content (unused).
 * @return string Markup, or empty when no services are defined.
 */
function stp_worship_times_render( $attributes = array(), $content = '' ) {
	$rows = stp_service_times();
	if ( empty( $rows ) ) {
		return '';
	}
	$lines = array();
	foreach ( $rows as $row ) {
		$time   = '<strong>' . esc_html( $row['time'] ) . '</strong>';
		$rite   = esc_html( $row['rite'] );
		$mode   = esc_html( stp_service_times_display_mode( $row['mode'] ) );
		$lines[] = $time . ' · ' . $rite . ' · ' . $mode;
	}
	return '<p class="stp-service-line">' . implode( '<br>', $lines ) . '</p>';
}

/**
 * Register the block and its editor-only script.
 */
function stp_worship_times_register_block() {
	register_block_type(
		'stpeters/worship-times',
		array(
			'render_callback' => 'stp_worship_times_render',
			'attributes'      => array(),
		)
	);
}
add_action( 'init', 'stp_worship_times_register_block' );

/**
 * Enqueue the block's editor script so the block editor recognizes it as a
 * dynamic block (save returns null; the preview is server-rendered).
 */
function stp_worship_times_editor_assets() {
	wp_enqueue_script(
		'stp-worship-times-block',
		plugin_dir_url( __FILE__ ) . 'assets/block.js',
		array( 'wp-blocks', 'wp-element', 'wp-server-side-render', 'wp-i18n' ),
		'0.1.0',
		true
	);
}
add_action( 'enqueue_block_editor_assets', 'stp_worship_times_editor_assets' );
