<?php
/**
 * Plugin Name:       St. Peter's Service Times
 * Description:       Single source of truth for Sunday service times. Provides a top-level "Service Times" admin screen (wp-admin sidebar) where parish staff maintain the roster — time, service, how to attend — in one place, and exposes stp_service_times() for the front-end block. Deliberately NO repeat-schedule engine: staff revise the data by hand when it changes.
 * Version:           0.1.3
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

/** Option name for the optional visitor-visible note under the roster. */
define( 'STP_SERVICE_TIMES_NOTE', 'stp_service_times_note' );

/** Admin menu/page slug. */
define( 'STP_SERVICE_TIMES_SLUG', 'stp-service-times' );

/**
 * Capability required to view and edit the screen. Defaults to Editors
 * (edit_pages) so parish staff with Editor access can maintain times, while
 * Authors and below cannot. Override via the constant if a different set
 * should be used: define( 'STP_SERVICE_TIMES_CAPABILITY', 'manage_options' )
 * in wp-config.php before this plugin loads.
 */
if ( ! defined( 'STP_SERVICE_TIMES_CAPABILITY' ) ) {
	define( 'STP_SERVICE_TIMES_CAPABILITY', 'edit_pages' );
}

/**
 * Visitor-visible service types: slug => display label.
 *
 * The value stored on each roster row is a stable slug; the label is what staff
 * see in the dropdown and visitors see on the site. Because they are separate,
 * renaming a label (or adding a type) never breaks saved data. Overridable via
 * the 'stp_service_times_service_types' filter (see README) so options can be
 * tuned without editing the plugin.
 *
 * @return array $slug => $label
 */
function stp_service_times_service_types() {
	$types = array(
		'bcp-service'        => 'BCP Service',
		'bcp-communion'      => 'BCP Communion',
		'bcp-morning-prayer' => 'BCP Morning Prayer',
		'bcp-evening-prayer' => 'BCP Evening Prayer',
		'bas-eucharist'      => 'BAS Eucharist',
	);
	return apply_filters( 'stp_service_times_service_types', $types );
}

/**
 * Visitor-visible ways to attend: slug => display label.
 *
 * Same stable-slug model as service types; filterable via
 * 'stp_service_times_attendance_modes'.
 *
 * @return array $slug => $label
 */
function stp_service_times_attendance_modes() {
	$modes = array(
		'online'          => 'Online',
		'in-person'       => 'In-person',
		'online-inperson' => 'Online and In-person',
	);
	return apply_filters( 'stp_service_times_attendance_modes', $modes );
}

/**
 * Display label for a stored service-type slug.
 *
 * @param string $slug Stored service-type key.
 * @return string
 */
function stp_service_times_service_label( $slug ) {
	$types = stp_service_times_service_types();
	return isset( $types[ $slug ] ) ? $types[ $slug ] : $slug;
}

/**
 * Display label for a stored attendance-mode slug.
 *
 * @param string $slug Stored attendance key.
 * @return string
 */
function stp_service_times_attendance_label( $slug ) {
	$modes = stp_service_times_attendance_modes();
	return isset( $modes[ $slug ] ) ? $modes[ $slug ] : $slug;
}

/**
 * Map a legacy 'rite' value (pre-0.1.3 rows) onto a service-type slug.
 *
 * @param string $rite Lowercased legacy rite ('bas'|'bcp').
 * @return string
 */
function stp_service_times_legacy_rite_to_service( $rite ) {
	switch ( $rite ) {
		case 'bas':
			return 'bas-eucharist';
		case 'bcp':
			return 'bcp-communion';
		default:
			return '';
	}
}

/**
 * Map a legacy 'mode' value (pre-0.1.3 rows) onto an attendance slug.
 *
 * @param string $mode Lowercased legacy mode ('zoom'|'hybrid'|'in-person').
 * @return string
 */
function stp_service_times_legacy_mode_to_attendance( $mode ) {
	switch ( $mode ) {
		case 'zoom':
			return 'online';
		case 'in-person':
			return 'in-person';
		case 'hybrid':
			return 'online-inperson';
		default:
			return '';
	}
}

/**
 * Default roster (2026 example, per DESIGN.md). Only one way-to-attend is
 * modeled per row; week-to-week nuance is deliberately not (no schedule
 * engine) and is handled by the visitor note when needed.
 *
 * @return array[] List of rows with keys: time, service, attendance.
 */
function stp_service_times_defaults() {
	return array(
		array(
			'time'       => '8:00 am',
			'service'    => 'bcp-communion',
			'attendance' => 'online',
		),
		array(
			'time'       => '9:15 am',
			'service'    => 'bas-eucharist',
			'attendance' => 'online-inperson',
		),
		array(
			'time'       => '11:00 am',
			'service'    => 'bcp-communion',
			'attendance' => 'online-inperson',
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

	$type_keys = array_keys( stp_service_times_service_types() );
	$mode_keys = array_keys( stp_service_times_attendance_modes() );

	$service    = isset( $row['service'] ) ? sanitize_key( (string) $row['service'] ) : '';
	$attendance = isset( $row['attendance'] ) ? sanitize_key( (string) $row['attendance'] ) : '';

	/* Legacy shim: rows saved with the old 'rite'/'mode' keys (pre-0.1.3) map
	   onto the new slugs so no staff-visible change is needed. */
	if ( '' === $service && isset( $row['rite'] ) ) {
		$service = stp_service_times_legacy_rite_to_service( sanitize_key( (string) $row['rite'] ) );
	}
	if ( '' === $attendance && isset( $row['mode'] ) ) {
		$attendance = stp_service_times_legacy_mode_to_attendance( sanitize_key( (string) $row['mode'] ) );
	}

	if ( ! in_array( $service, $type_keys, true ) ) {
		$service = $type_keys ? $type_keys[0] : '';
	}
	if ( ! in_array( $attendance, $mode_keys, true ) ) {
		$attendance = $mode_keys ? $mode_keys[0] : '';
	}

	return array(
		'time'       => $time,
		'service'    => $service,
		'attendance' => $attendance,
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
 * Public getter for the visitor-visible roster note, or empty string.
 *
 * A free-text qualifier shown under the service list on every public page
 * that renders the Worship Times block. Staff maintain it on the same admin
 * screen as the roster; it lets a schedule exception (e.g. the first-Sunday
 * in-person option) be communicated without any scheduling engine.
 *
 * @return string Trimmed note text.
 */
function stp_service_times_note() {
	return trim( (string) get_option( STP_SERVICE_TIMES_NOTE, '' ) );
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
	$service_opts = '';
	foreach ( stp_service_times_service_types() as $slug => $label ) {
		$service_opts .= sprintf(
			'<option value="%1$s"%2$s>%3$s</option>',
			esc_attr( $slug ),
			selected( $slug, $row['service'], false ),
			esc_html( $label )
		);
	}
	$attendance_opts = '';
	foreach ( stp_service_times_attendance_modes() as $slug => $label ) {
		$attendance_opts .= sprintf(
			'<option value="%1$s"%2$s>%3$s</option>',
			esc_attr( $slug ),
			selected( $slug, $row['attendance'], false ),
			esc_html( $label )
		);
	}

	return '<tr class="stp-st-row">'
		. '<td><input type="text" name="stp_service_times[' . esc_attr( $index ) . '][time]" value="' . esc_attr( $row['time'] ) . '" class="regular-text" placeholder="e.g. 8:00 am" /></td>'
		. '<td><select name="stp_service_times[' . esc_attr( $index ) . '][service]">' . $service_opts . '</select></td>'
		. '<td><select name="stp_service_times[' . esc_attr( $index ) . '][attendance]">' . $attendance_opts . '</select></td>'
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
		if ( current_user_can( STP_SERVICE_TIMES_CAPABILITY ) ) {
			// Optional visitor-visible note, saved independently of the rows.
			if ( isset( $_POST['stp_service_times_note'] ) ) {
				update_option(
					STP_SERVICE_TIMES_NOTE,
					sanitize_textarea_field( wp_unslash( (string) $_POST['stp_service_times_note'] ) )
				);
			}
			if ( isset( $_POST['stp_service_times'] ) ) {
				$rows = stp_service_times_sanitize( wp_unslash( (array) $_POST['stp_service_times'] ) );
				if ( array() === $rows ) {
					$errors[] = __( 'At least one service with a time is required.', 'stpeters-service-times' );
				} else {
					update_option( STP_SERVICE_TIMES_OPTION, $rows );
					$updated = true;
				}
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
						<th scope="col" style="width:24%"><?php esc_html_e( 'Time', 'stpeters-service-times' ); ?></th>
						<th scope="col" style="width:32%"><?php esc_html_e( 'Service', 'stpeters-service-times' ); ?></th>
						<th scope="col" style="width:30%"><?php esc_html_e( 'How to attend', 'stpeters-service-times' ); ?></th>
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

			<p>
				<label for="stp-service-times-note"><strong><?php esc_html_e( 'Visitor note (optional)', 'stpeters-service-times' ); ?></strong></label><br>
				<textarea id="stp-service-times-note" name="stp_service_times_note" class="large-text" rows="2" placeholder="<?php esc_attr_e( 'e.g. On the first Sunday of the month the 8 am service is also held in person.', 'stpeters-service-times' ); ?>"><?php echo esc_textarea( stp_service_times_note() ); ?></textarea>
				<span class="description"><?php esc_html_e( 'A short line shown under the service times on both public pages. Use it for a schedule qualifier such as the first-Sunday arrangement.', 'stpeters-service-times' ); ?></span>
			</p>

			<?php submit_button( __( 'Save service times', 'stpeters-service-times' ) ); ?>
		</form>

		<template id="stp-service-times-row-tpl">
			<?php
			echo stp_service_times_row_markup( 0, array( 'time' => '', 'service' => 'bas-eucharist', 'attendance' => 'online-inperson' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
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
/**
 * Version string for a plugin asset, from the file's mtime so the URL changes
 * whenever the file changes (cache-busting without a manual purge or URL edit).
 *
 * @param string $rel_path Path relative to the plugin dir, e.g. '/assets/admin.js'.
 * @return string
 */
function stp_service_times_asset_version( $rel_path ) {
	$mtime = filemtime( plugin_dir_path( __FILE__ ) . ltrim( $rel_path, '/' ) );
	return ( false !== $mtime ) ? (string) $mtime : '0.1.0';
}

function stp_service_times_admin_assets( $hook ) {
	if ( 'toplevel_page_stp-service-times' !== $hook ) {
		return;
	}
	wp_enqueue_script(
		'stp-service-times-admin',
		plugin_dir_url( __FILE__ ) . 'assets/admin.js',
		array(),
		stp_service_times_asset_version( '/assets/admin.js' ),
		true
	);
}
add_action( 'admin_enqueue_scripts', 'stp_service_times_admin_assets' );

/* ----------------------------------------------------------------------- */
/* Front-end dynamic block: stpeters/worship-times                          */
/* ----------------------------------------------------------------------- */

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
	$note = stp_service_times_note();
	foreach ( $rows as $row ) {
		$time    = '<strong>' . esc_html( $row['time'] ) . '</strong>';
		$service = esc_html( stp_service_times_service_label( $row['service'] ) );
		$attend  = esc_html( stp_service_times_attendance_label( $row['attendance'] ) );
		$lines[] = $time . ' · ' . $service . ' · ' . $attend;
	}
	/* A single block-level wrapper (carries the block's own class) so the list
	   sits centered in its band via the theme's .wp-block-stpeters-worship-times
	   rule, instead of inheriting the parent group's full-bleed flow layout. */
	$markup = '<div class="wp-block-stpeters-worship-times"><p class="stp-service-line">' . implode( '<br>', $lines ) . '</p>';
	if ( '' !== $note ) {
		$markup .= '<p class="stp-service-note">' . esc_html( $note ) . '</p>';
	}
	return $markup . '</div>';
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
		stp_service_times_asset_version( '/assets/block.js' ),
		true
	);
}
add_action( 'enqueue_block_editor_assets', 'stp_worship_times_editor_assets' );
