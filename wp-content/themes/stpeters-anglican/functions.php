<?php
/**
 * St. Peter's Anglican Erindale child theme functions.
 *
 * @package stpeters-anglican
 */

/**
 * Version string for a child-theme asset, based on the file's mtime so the URL
 * changes whenever the file changes — this busts browser caches (Safari in
 * particular) automatically without any manual purge or URL change, and keeps
 * the site host-independent.
 *
 * @param string $rel_path Path relative to the stylesheet directory, e.g. '/style.css'.
 * @return string
 */
function stp_anglican_asset_version( $rel_path ) {
	$path = get_stylesheet_directory() . $rel_path;
	$mtime = filemtime( $path );
	return ( false !== $mtime ) ? (string) $mtime : wp_get_theme()->get( 'Version' );
}

add_action( 'wp_enqueue_scripts', function () {
	// Parents that enqueue their stylesheet via get_stylesheet_uri() would load
	// the child's near-empty style.css instead — enqueue the parent's directly.
	wp_enqueue_style(
		'twentytwentyfive-parent-style',
		get_template_directory_uri() . '/style.css',
		array(),
		wp_get_theme( get_template() )->get( 'Version' )
	);
	wp_enqueue_style(
		'stpeters-anglican-style',
		get_stylesheet_directory_uri() . '/style.css',
		array( 'twentytwentyfive-parent-style' ),
		stp_anglican_asset_version( '/style.css' )
	);
} );

add_action( 'after_setup_theme', function () {
	add_editor_style( 'style.css' );
} );

// Reusable block style variations.
add_action( 'init', function () {
	register_block_style(
		'core/group',
		array(
			'name'  => 'card',
			'label' => 'Card',
		)
	);
	register_block_style(
		'core/group',
		array(
			'name'  => 'eyebrow',
			'label' => 'Eyebrow label',
		)
	);
} );

/**
 * Format the bound event occurrence date (event_date) as a readable label.
 *
 * The Recent Parish Activity Query Loop binds the post's `event_date` meta to a
 * `core/paragraph` with class `activity-date`; core resolves that binding with the
 * correct loop post context (unlike a shortcode, which only sees the global post).
 * We reformat the raw ISO date into a human-readable label.
 *
 * We display the occurrence date, not the post's publication date: for a
 * recently-held event the question a visitor is really asking is "when did this
 * happen?", and it keeps the Recent surface consistent with the Upcoming surface,
 * which is also keyed to the occurrence date. The plugin stores the two dates
 * separately; publication date is the announcement/editorial date and is not shown.
 */
add_filter( 'render_block', function ( $block_content, $parsed_block ) {
	if ( ! is_string( $block_content ) ) {
		return $block_content;
	}
	// Match the loop's bound date paragraph by class only (the binding's block
	// metadata is not reliably present in attrs at this point).
	if ( ! preg_match( '#<p[^>]*class="[^"]*activity-date[^"]*"[^>]*>(.*?)</p>#is', $block_content, $m ) ) {
		return $block_content;
	}
	$raw = trim( wp_strip_all_tags( $m[1] ) );
	if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $raw ) ) {
		return $block_content;
	}
	$ts   = strtotime( $raw . ' 12:00:00 UTC' );
	$date = ( false === $ts ) ? esc_html( $raw ) : esc_html( wp_date( 'F j, Y', $ts ) );
	return '<p class="activity-date"><time datetime="' . esc_attr( $raw ) . '">' . $date . '</time></p>';
}, 10, 2 );
