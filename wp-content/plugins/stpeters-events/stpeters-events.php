<?php
/**
 * Plugin Name:       St. Peter's Events
 * Description:       Event metadata and date-based queries for St. Peter's Anglican Church, Erindale. Registers event_date and display_until post meta, registers Query Loop block variations, and applies date-relative filtering so events flow Upcoming -> Recent Activity -> Archive.
 * Version:           0.1.0
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
 * Activity" when no explicit display_until date is set. Editable via the
 * STP_EVENTS_DEFAULT_DISPLAY_WINDOW constant (0 disables the default window).
 */
if ( ! defined( 'STP_EVENTS_DEFAULT_DISPLAY_WINDOW' ) ) {
	define( 'STP_EVENTS_DEFAULT_DISPLAY_WINDOW', 7 );
}

/**
 * The "Events" category slug used to scope event queries.
 * (Stage 2 will register/verify this; kept here as a single source of truth.)
 */
if ( ! defined( 'STP_EVENTS_CATEGORY' ) ) {
	define( 'STP_EVENTS_CATEGORY', 'events' );
}

// Stage 2 will add: post meta registration, Query Loop block variations,
// the front-end date-filter hook, and the editor date fields.
