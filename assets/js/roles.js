/* WP Scroll Words → Settings: a role's other rights only with "Edit texts" (as WPSW_Access::sanitize()). */
( function () {
	'use strict';

	function sync( row ) {
		var edit = row.querySelector( 'input[data-action="edit"]' );
		if ( ! edit || edit.disabled ) {
			return;
		}
		[ 'create', 'delete', 'wpbakery' ].forEach( function ( action ) {
			var box = row.querySelector( 'input[data-action="' + action + '"]' );
			if ( ! box ) {
				return;
			}
			box.disabled = ! edit.checked;
			if ( ! edit.checked ) {
				box.checked = false;
			}
		} );
	}

	document.querySelectorAll( '.wpsw-roles__table tbody tr' ).forEach( function ( row ) {
		sync( row );
		row.addEventListener( 'change', function ( e ) {
			if ( e.target.matches( 'input[data-action="edit"]' ) ) {
				sync( row );
			}
		} );
	} );
}() );
