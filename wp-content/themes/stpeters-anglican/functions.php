<?php
/**
 * St. Peter's Anglican Erindale child theme functions.
 *
 * @package stpeters-anglican
 */

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
		wp_get_theme()->get( 'Version' )
	);
} );

add_action( 'after_setup_theme', function () {
	add_editor_style( 'style.css' );
} );
