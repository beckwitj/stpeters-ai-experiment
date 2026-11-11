/**
 * Editor registration for the stpeters/worship-times dynamic block.
 *
 * The block is server-rendered (see stp_worship_times_render in the plugin's
 * main file); the editor only needs to recognize it and show a live preview,
 * which it gets from ServerSideRender.
 */
( function ( blocks, element, serverSideRender ) {
	'use strict';

	var el = element.createElement;

	blocks.registerBlockType( 'stpeters/worship-times', {
		title: 'Worship Times',
		icon: 'clock',
		category: 'widgets',
		description: 'The Sunday services list from the Service Times screen — time, rite and mode.',
		supports: {
			html: false,
			align: false
		},
		edit: function ( props ) {
			return el( serverSideRender, {
				block: 'stpeters/worship-times',
				attributes: props.attributes
			} );
		},
		save: function () {
			return null;
		}
	} );
} )( window.wp.blocks, window.wp.element, window.wp.serverSideRender );
