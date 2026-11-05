<?php
/**
 * Plugin Name:       St. Peter's Events
 * Description:       Event metadata and date-based queries for St. Peter's Anglican Church, Erindale. Registers event_date and display_until post meta with an automatic/manual "display until" distinction, registers Query Loop block variations, and injects date-relative filtering on the front end so events flow Upcoming -> Recent Parish Activity -> Archive.
 * Version:           0.3.0
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
 * Activity" when Display until is Automatic. Editable via the
 * STP_EVENTS_DEFAULT_DISPLAY_WINDOW constant (0 disables the automatic window).
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

/** Meta key holding the automatic/manual flag for display_until. */
define( 'STP_EVENTS_AUTO_FLAG', '_stp_events_display_auto' );

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
 * The automatic display-until for an event date, or '' when auto is disabled.
 *
 * @param string $event_date YYYY-MM-DD.
 * @return string
 */
function stp_events_compute_auto_until( $event_date ) {
	$window = (int) STP_EVENTS_DEFAULT_DISPLAY_WINDOW;
	if ( $window <= 0 || '' === $event_date ) {
		return '';
	}
	return ( new DateTimeImmutable( $event_date ) )->modify( '+' . $window . ' days' )->format( 'Y-m-d' );
}

/**
 * Register the event date fields and the auto flag as post meta.
 */
function stp_events_register_meta() {
	register_post_meta(
		'post',
		'event_date',
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
	register_post_meta(
		'post',
		'display_until',
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
	register_post_meta(
		'post',
		STP_EVENTS_AUTO_FLAG,
		array(
			'type'              => 'string',
			'single'            => true,
			// No 'default': an unset flag must read as '' (not '0') so that
			// legacy events without the flag are migrated by reconcile_display.
			'sanitize_callback' => function ( $value ) {
				return ( '1' === $value ) ? '1' : '0';
			},
			'auth_callback'     => function () {
				return current_user_can( 'edit_posts' );
			},
			'show_in_rest'      => true,
		)
	);
}
add_action( 'init', 'stp_events_register_meta' );

/**
 * Reconcile display_until with the automatic/manual flag.
 *
 * Single source of truth, safe to call on any save channel (editor form,
 * REST, or WP-CLI):
 *  - Automatic (flag "1"): display_until always follows the event date.
 *  - Manual (flag "0"): display_until is left untouched.
 *  - No display_until yet -> becomes Automatic and is computed.
 *  - Legacy post (no flag stored) whose display_until equals the default is
 *    adopted as Automatic; anything else is treated as Manual.
 *
 * @param int $post_id Post ID.
 */
function stp_events_reconcile_display( $post_id ) {
	if ( 'post' !== get_post_type( $post_id ) || wp_is_post_revision( $post_id ) ) {
		return;
	}

	$event = (string) get_post_meta( $post_id, 'event_date', true );
	$until = (string) get_post_meta( $post_id, 'display_until', true );
	$auto  = (string) get_post_meta( $post_id, STP_EVENTS_AUTO_FLAG, true );

	if ( '' === $event ) {
		if ( '' !== $until ) {
			delete_post_meta( $post_id, 'display_until' );
		}
		return;
	}

	$computed = stp_events_compute_auto_until( $event );
	if ( '' === $computed ) {
		return; // Automatic window disabled; leave any manual value alone.
	}

	if ( '' === $until ) {
		update_post_meta( $post_id, 'display_until', $computed );
		update_post_meta( $post_id, STP_EVENTS_AUTO_FLAG, '1' );
		return;
	}

	if ( '1' === $auto ) {
		if ( $until !== $computed ) {
			update_post_meta( $post_id, 'display_until', $computed );
		}
		update_post_meta( $post_id, STP_EVENTS_AUTO_FLAG, '1' );
		return;
	}

	// Flag is '0' (explicit manual) or '' (legacy).
	if ( '' === $auto && $until === $computed ) {
		// Legacy value that already equals the default -> adopt as Automatic.
		update_post_meta( $post_id, STP_EVENTS_AUTO_FLAG, '1' );
		return;
	}

	// Manual: preserve the value and mark it explicitly.
	if ( '0' !== $auto ) {
		update_post_meta( $post_id, STP_EVENTS_AUTO_FLAG, '0' );
	}
}
add_action( 'save_post', 'stp_events_reconcile_display', 20, 1 );

/**
 * Reconcile again after the REST API has applied post meta (the REST controller
 * applies meta after save_post fires, so the save_post pass may run on a stale
 * value for REST-updated events).
 *
 * @param WP_Post $post Updated post.
 */
function stp_events_rest_reconcile( $post ) {
	if ( $post instanceof WP_Post ) {
		stp_events_reconcile_display( $post->ID );
	}
}
add_action( 'rest_after_insert_post', 'stp_events_rest_reconcile', 10, 1 );

/**
 * Render the "Event dates" meta box in the post editor.
 *
 * @param WP_Post $post Current post.
 */
function stp_events_dates_meta_box( $post ) {
	wp_nonce_field( 'stp_events_save_dates', 'stp_events_dates_nonce' );
	$event_date = (string) get_post_meta( $post->ID, 'event_date', true );
	$until      = (string) get_post_meta( $post->ID, 'display_until', true );
	$auto       = (string) get_post_meta( $post->ID, STP_EVENTS_AUTO_FLAG, true );
	// Automatic when flagged, or when no value and an event date exists.
	$is_auto  = ( '1' === $auto ) || ( '' === $until && '' !== $event_date );
	$autotitle = $is_auto ? esc_attr( stp_events_compute_auto_until( $event_date ) ) : '';
	?>
	<p>
		<label for="stp_event_date">
			<strong><?php esc_html_e( 'Event date', 'stpeters-events' ); ?></strong>
		</label><br />
		<input type="date" id="stp_event_date" name="stp_event_date" value="<?php echo esc_attr( $event_date ); ?>" />
	</p>

	<p>
		<label>
			<input type="checkbox" name="stp_display_auto" value="1" id="stp_display_auto" <?php checked( $is_auto ); ?> />
			<strong><?php esc_html_e( 'Display until — Automatic', 'stpeters-events' ); ?></strong>
		</label><br />
		<span class="description" id="stp_auto_note">
			<?php
			if ( '' !== $event_date ) {
				echo esc_html( $is_auto ? sprintf( __( 'Shown as recent activity until %s (auto, follows the event date).', 'stpeters-events' ), $autotitle ) : '' );
			}
			?>
		</span>
	</p>

	<p>
		<label for="stp_display_until">
			<?php esc_html_e( 'Or set a specific date:', 'stpeters-events' ); ?>
		</label><br />
		<input type="date" id="stp_display_until" name="stp_display_until" value="<?php echo esc_attr( $until ); ?>" <?php disabled( $is_auto ); ?> />
		<span class="description"><?php esc_html_e( 'Uncheck Automatic, then pick a date to keep it visible until a fixed day.', 'stpeters-events' ); ?></span>
	</p>
	<script>
	( function () {
		var box = document.getElementById( 'stp_event_date' ).closest( '.postbox' ) || document;
		var auto = box.querySelector( '#stp_display_auto' );
		var until = box.querySelector( '#stp_display_until' );
		if ( ! auto || ! until ) { return; }
		function sync() {
			until.disabled = auto.checked;
			if ( auto.checked ) { until.value = ''; }
		}
		auto.addEventListener( 'change', sync );
	} )();
	</script>
	<?php
}
add_action( 'add_meta_boxes', function () {
	add_meta_box( 'stp_events_dates', __( 'Event dates', 'stpeters-events' ), 'stp_events_dates_meta_box', 'post', 'side', 'default' );
} );

/**
 * Persist the two date fields and the automatic flag from the editor form.
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

	if ( isset( $_POST['stp_event_date'] ) ) {
		$event_date = stp_events_sanitize_date( wp_unslash( $_POST['stp_event_date'] ) );
		if ( '' !== $event_date ) {
			update_post_meta( $post_id, 'event_date', $event_date );
		} else {
			delete_post_meta( $post_id, 'event_date' );
		}
	}

	$is_auto = isset( $_POST['stp_display_auto'] ) && '1' === $_POST['stp_display_auto'];

	if ( $is_auto ) {
		// Automatic: drop any typed value so reconcile recomputes from the date.
		delete_post_meta( $post_id, 'display_until' );
		update_post_meta( $post_id, STP_EVENTS_AUTO_FLAG, '1' );
	} else {
		// Manual: persist the typed value (or none) and mark it manual.
		$until = isset( $_POST['stp_display_until'] ) ? stp_events_sanitize_date( wp_unslash( $_POST['stp_display_until'] ) ) : '';
		if ( '' !== $until ) {
			update_post_meta( $post_id, 'display_until', $until );
		} else {
			delete_post_meta( $post_id, 'display_until' );
		}
		update_post_meta( $post_id, STP_EVENTS_AUTO_FLAG, '0' );
	}
}
add_action( 'save_post', 'stp_events_save_dates', 10, 2 );

/**
 * Enqueue the block editor script that registers the Query Loop variations.
 */
function stp_events_register_editor_assets() {
	wp_enqueue_script(
		'stp-events-query-variations',
		plugin_dir_url( __FILE__ ) . 'assets/query-variations.js',
		array( 'wp-blocks' ),
		'0.3.0',
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
