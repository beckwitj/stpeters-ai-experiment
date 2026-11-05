<?php
/**
 * Plugin Name:       St. Peter's Events
 * Description:       Event metadata and date-based queries for St. Peter's Anglican Church, Erindale. Registers event_date and display_until post meta, registers Query Loop block variations, applies a save-time default display window, and injects date-relative filtering on the front end so events flow Upcoming -> Recent Parish Activity -> Archive.
 * Version:           0.2.0
 * Author:            St. Peter's Anglican Church
 * Text Domain:       stpeters-events
 * Requires at least: 6.7
 * Requires PHP:      7.2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * How many days after the event an announcement stays in "Recent Parish
 * Activity" when no explicit display_until is set. Editable via the
 * STP_EVENTS_DEFAULT_DISPLAY_WINDOW constant (0 disables the default window).
 */
if ( ! defined( 'STP_EVENTS_DEFAULT_DISPLAY_WINDOW' ) ) {
	define( 'STP_EVENTS_DEFAULT_DISPLAY_WINDOW', 7 );
}

/**
 * The "Events" category slug that scopes event queries.
 */
if ( ! defined( 'STP_EVENTS_CATEGORY' ) ) {
	define( 'STP_EVENTS_CATEGORY', 'events' );
}

/**
 * Sanitize a value to a YYYY-MM-DD date string, or empty string.
 */
function stp_events_sanitize_date( $value ) {
	if ( is_string( $value ) && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $value ) ) {
		return $value;
	}
	return '';
}

/**
 * Register the two event date fields as post meta (single, REST-visible so the
 * block editor and block bindings can use them).
 */
function stp_events_register_meta() {
	foreach ( array( 'event_date', 'display_until' ) as $key ) {
		register_post_meta(
			'post',
			$key,
			array(
				'type'              => 'string',
				'single'            => true,
				'sanitize_callback' => 'stp_events_sanitize_date',
				'auth_callback'     => function () {
					return current_user_can( 'edit_posts' );
				},
				'show_in_rest'      => true,
			)
		);
	}
}
add_action( 'init', 'stp_events_register_meta' );

/**
 * Render the "Event dates" meta box in the post editor.
 *
 * @param WP_Post $post Current post.
 */
function stp_events_dates_meta_box( $post ) {
	wp_nonce_field( 'stp_events_save_dates', 'stp_events_dates_nonce' );
	$event_date   = get_post_meta( $post->ID, 'event_date', true );
	$display_until = get_post_meta( $post->ID, 'display_until', true );
	?>
	<p>
		<label for="stp_event_date">
			<strong><?php esc_html_e( 'Event date', 'stpeters-events' ); ?></strong>
		</label><br />
		<input type="date" id="stp_event_date" name="stp_event_date" value="<?php echo esc_attr( $event_date ); ?>" />
		<span class="description"><?php esc_html_e( 'Date the event takes place.', 'stpeters-events' ); ?></span>
	</p>
	<p>
		<label for="stp_display_until">
			<strong><?php esc_html_e( 'Display until', 'stpeters-events' ); ?></strong>
		</label><br />
		<input type="date" id="stp_display_until" name="stp_display_until" value="<?php echo esc_attr( $display_until ); ?>" />
		<span class="description">
			<?php esc_html_e( 'Keep visible in recent activity until this date. Leave blank for default (7 days after the event).', 'stpeters-events' ); ?>
		</span>
	</p>
	<?php
}
add_action( 'add_meta_boxes', function () {
	add_meta_box( 'stp_events_dates', __( 'Event dates', 'stpeters-events' ), 'stp_events_dates_meta_box', 'post', 'side', 'default' );
} );

/**
 * Apply the event dates on save.
 *
 * - Persists the two fields from the editor form when submitted.
 * - Applies the save-time default display window: if an event has an
 *   event_date but no display_until, display_until is computed as
 *   event_date + STP_EVENTS_DEFAULT_DISPLAY_WINDOW and saved.
 * - Editors override simply by entering a display_until.
 *
 * @param int     $post_id Post ID.
 * @param WP_Post $post    Post object.
 */
function stp_events_save_dates( $post_id, $post ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( 'post' !== $post->post_type || wp_is_post_revision( $post_id ) ) {
		return;
	}
	if ( ! isset( $_POST['stp_events_dates_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['stp_events_dates_nonce'] ), 'stp_events_save_dates' ) ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	// Persist the two fields when submitted from the editor form. Editorial
	// overrides win here; the default window is applied by the separate hook
	// below for any save that results in a date but no display_until.
	if ( isset( $_POST['stp_event_date'] ) ) {
		$event_date = stp_events_sanitize_date( wp_unslash( $_POST['stp_event_date'] ) );
		if ( '' !== $event_date ) {
			update_post_meta( $post_id, 'event_date', $event_date );
		} else {
			delete_post_meta( $post_id, 'event_date' );
		}
	}
	if ( isset( $_POST['stp_display_until'] ) ) {
		$display_until = stp_events_sanitize_date( wp_unslash( $_POST['stp_display_until'] ) );
		if ( '' !== $display_until ) {
			update_post_meta( $post_id, 'display_until', $display_until );
		} else {
			delete_post_meta( $post_id, 'display_until' );
		}
	}
}
add_action( 'save_post', 'stp_events_save_dates', 10, 2 );

/**
 * Applies the save-time default display window on ANY save (editor form,
 * REST, or WP-CLI) when a post has an event_date but no display_until.
 * Editors override by setting display_until explicitly. An editor who wants an
 * announcement to stay visible indefinitely sets a far-future display_until.
 *
 * @param int     $post_id Post ID.
 * @param WP_Post $post    Post object.
 */
function stp_events_apply_default_display( $post_id, $post ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( 'post' !== $post->post_type || wp_is_post_revision( $post_id ) ) {
		return;
	}

	$event_date    = (string) get_post_meta( $post_id, 'event_date', true );
	$display_until = (string) get_post_meta( $post_id, 'display_until', true );

	if ( '' === $event_date ) {
		return;
	}
	if ( '' !== $display_until ) {
		return; // Editorial override already present.
	}

	$default_window = (int) STP_EVENTS_DEFAULT_DISPLAY_WINDOW;
	if ( 0 === $default_window ) {
		return;
	}
	$computed = ( new DateTimeImmutable( $event_date ) )->modify( '+' . $default_window . ' days' )->format( 'Y-m-d' );
	update_post_meta( $post_id, 'display_until', $computed );
}
add_action( 'save_post', 'stp_events_apply_default_display', 20, 2 );

/**
 * Register "Upcoming Events" and "Recent Parish Activity" Query Loop block
 * variations in the block editor.
 */
function stp_events_register_editor_assets() {
	$asset = array(
		'dependencies' => array( 'wp-blocks' ),
		'version'      => '0.2.0',
	);
	wp_enqueue_script(
		'stp-events-query-variations',
		plugin_dir_url( __FILE__ ) . 'assets/query-variations.js',
		$asset['dependencies'],
		$asset['version'],
		true
	);
}
add_action( 'enqueue_block_editor_assets', 'stp_events_register_editor_assets' );

/**
 * Inject date-relative filtering/ordering for our event Query Loop surfaces.
 *
 * The Query Loop block's editor preview queries via the REST API and is NOT
 * affected by this filter; only the front-end render is (per core's own docs).
 * Each variation carries a `stpSurface` marker in its query context ("upcoming"
 * or "recent"), which we translate into a WP_Query meta_query with today's date.
 *
 * Ordering uses `meta_value` (string/LIKE) on ISO dates so they sort
 * chronologically; we deliberately do NOT use `meta_value_num`, which would
 * numeric-cast "2026-03-08" to 2026 and break ordering within a year.
 *
 * @param array    $query WP_Query args.
 * @param WP_Block $block Block instance.
 * @return array
 */
function stp_events_query_loop_vars( $query, $block ) {
	$q = isset( $block->context['query'] ) ? $block->context['query'] : array();
	if ( empty( $q['stpSurface'] ) || ! in_array( (string) $q['stpSurface'], array( 'upcoming', 'recent' ), true ) ) {
		return $query;
	}

	$today = current_time( 'Y-m-d' ); // Site timezone.

	// Scope to the events category (by slug, portable across IDs).
	$query['post_type'] = 'post';
	if ( ! isset( $query['posts_per_page'] ) ) {
		$query['posts_per_page'] = 10;
	}
	$query['tax_query'] = array(
		array(
			'taxonomy' => 'category',
			'field'    => 'slug',
			'terms'    => array( STP_EVENTS_CATEGORY ),
		),
	);

	if ( 'upcoming' === $q['stpSurface'] ) {
		$query['meta_key']   = 'event_date';
		$query['orderby']    = 'meta_value';
		$query['order']      = 'ASC';
		$query['meta_query'] = array(
			array(
				'key'     => 'event_date',
				'value'   => $today,
				'compare' => '>=',
				'type'    => 'CHAR',
			),
		);
	} else { // recent parish activity: completed but not yet expired.
		$query['meta_key']   = 'event_date';
		$query['orderby']    = 'meta_value';
		$query['order']      = 'DESC';
		$query['meta_query'] = array(
			'relation' => 'AND',
			array(
				'key'     => 'event_date',
				'value'   => $today,
				'compare' => '<',
				'type'    => 'CHAR',
			),
			array(
				'key'     => 'display_until',
				'value'   => $today,
				'compare' => '>=',
				'type'    => 'CHAR',
			),
		);
	}

	return $query;
}
add_filter( 'query_loop_block_query_vars', 'stp_events_query_loop_vars', 10, 2 );
