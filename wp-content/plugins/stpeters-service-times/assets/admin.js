/**
 * Service Times admin screen: add/remove roster rows.
 *
 * Rows are rendered server-side (and once in a <template> for cloning). Adding
 * clones the template, assigns a fresh array index, clears values, and wires
 * the remove button. Removing just drops the <tr>; PHP re-indexes on save, so
 * index gaps are fine.
 */
( function () {
	'use strict';

	var tbody = document.getElementById( 'stp-service-times-rows' );
	var tpl   = document.getElementById( 'stp-service-times-row-tpl' );
	var addBtn = document.getElementById( 'stp-service-times-add' );

	if ( ! tbody || ! tpl || ! addBtn ) {
		return;
	}

	/**
	 * Largest existing row index + 1, so new rows never collide.
	 */
	function nextIndex() {
		var max = -1;
		Array.prototype.forEach.call( tbody.querySelectorAll( '[name]' ), function ( el ) {
			var m = /stp_service_times\[(\d+)\]/.exec( el.name );
			if ( m ) {
				max = Math.max( max, parseInt( m[1], 10 ) );
			}
		} );
		return max + 1;
	}

	/**
	 * Wire a row's remove button. Kept separate from rebind so server-rendered
	 * rows can have their button wired WITHOUT re-keying: their server-assigned
	 * indices (0,1,2…) are already correct and unique. Re-keying them all to 0
	 * would collapse every row onto one form key and lose rows on save.
	 */
	function wireRemove( tr ) {
		var removeBtn = tr.querySelector( '.stp-st-remove' );
		if ( removeBtn ) {
			removeBtn.addEventListener( 'click', function () {
				tr.remove();
			} );
		}
	}

	/**
	 * Re-key a row's form fields to a given index and wire its remove button.
	 */
	function rebind( tr, index ) {
		Array.prototype.forEach.call( tr.querySelectorAll( '[name]' ), function ( el ) {
			el.name = el.name.replace( /stp_service_times\[\d+\]/, 'stp_service_times[' + index + ']' );
		} );
		wireRemove( tr );
	}

	// Wire remove buttons on server-rendered rows, keeping their indices.
	Array.prototype.forEach.call( tbody.querySelectorAll( '.stp-st-row' ), function ( tr ) {
		wireRemove( tr );
	} );

	addBtn.addEventListener( 'click', function () {
		var node = document.importNode( tpl.content, true );
		var tr   = node.querySelector( 'tr' );
		// Fresh row starts blank (template already carries BAS/hybrid defaults).
		Array.prototype.forEach.call( tr.querySelectorAll( 'input[type="text"]' ), function ( el ) {
			el.value = '';
		} );
		rebind( tr, nextIndex() );
		tbody.appendChild( node );
	} );
} )();
