/**
 * Registers the "Upcoming Events" and "Recent Parish Activity" Query Loop
 * block variations for the events plugin.
 *
 * The variations carry a `stpSurface` marker in the query context, which the
 * plugin's `query_loop_block_query_vars` PHP filter translates into a
 * date-relative meta_query on the FRONT END. The editor preview uses the REST
 * API and is not affected by that filter, so it shows a broader, unordered
 * set — a known and documented difference (see the plugin README).
 */
( function ( wp ) {
	const { registerBlockTypeVariation } = wp.blocks;

	const baseQuery = {
		postType: 'post',
		inherit: false,
		perPage: 8,
		order: 'ASC',
		orderBy: 'meta_value',
		metaKey: 'event_date',
	};

	registerBlockTypeVariation( 'core/query', [
		{
			name: 'stp-events/upcoming',
			title: 'Upcoming Events',
			description: 'Events with an event date today or later, soonest first.',
			category: 'stp-events',
			attributes: {
				query: Object.assign( {}, baseQuery, { stpSurface: 'upcoming', order: 'ASC' } ),
			},
		},
		{
			name: 'stp-events/recent',
			title: 'Recent Parish Activity',
			description: 'Recently completed events still within their display-until window, newest first.',
			category: 'stp-events',
			attributes: {
				query: Object.assign( {}, baseQuery, { stpSurface: 'recent', order: 'DESC' } ),
			},
		},
	] );
} )( window.wp );
