/**
 * Scroll Words element in WPBakery (as WP Swiper's wpbakery.js): under the Text list, "Edit text"
 * opens the chosen text in a new tab (hidden while none is chosen); "Add text" opens the new text
 * screen. Back on this tab, the list is asked for again and the text added there is chosen.
 *
 * WPBakery may attach this file each time a form opens: handlers are bound once.
 */
( function ( $ ) {
	'use strict';

	function formOf( node ) {
		var $form = $( node ).closest( '.vc_edit_form_elements, .vc_ui-panel-content, form' );
		return $form.length ? $form : $( document );
	}

	function textSelect( box ) {
		return formOf( box ).find( 'select.wpb_vc_param_value[name="id"]' ).first();
	}

	function sync( box ) {
		var $box = $( box );
		var id = parseInt( textSelect( box ).val(), 10 );
		var $link = $box.find( '.wpsw-vc-edit__link' );
		if ( id > 0 ) {
			$link.attr( 'href', $box.attr( 'data-url' ) + id ).prop( 'hidden', false );
		} else {
			$link.attr( 'href', '#' ).prop( 'hidden', true );
		}
		$box.find( '.wpsw-vc-edit__note' ).prop( 'hidden', ! ( id > 0 ) && ! $box.find( '.wpsw-vc-edit__add' ).length );
	}

	function syncAll() {
		$( '.wpsw-vc-edit' ).each( function () {
			sync( this );
		} );
	}

	/**
	 * The list as the site has it now (the first option kept, the choice kept);
	 * with `choose`, the newest text not there before is chosen.
	 */
	function refresh( box, choose ) {
		var $select = textSelect( box );
		var url = $( box ).attr( 'data-list' );
		if ( ! $select.length || ! url ) {
			return;
		}
		$.getJSON( url ).done( function ( response ) {
			if ( ! response || ! response.success || ! Array.isArray( response.data ) ) {
				return;
			}
			var known = {};
			$select.find( 'option' ).each( function () {
				known[ this.value ] = true;
			} );
			var chosen = $select.val();
			var added = 0;
			$select.find( 'option' ).slice( 1 ).remove();
			response.data.forEach( function ( text ) {
				var id = String( text.id );
				$( '<option>' ).val( id ).text( text.label ).addClass( id ).appendTo( $select );
				if ( ! known[ id ] ) {
					added = Math.max( added, text.id );
				}
			} );
			if ( choose && added ) {
				chosen = String( added );
			}
			$select.val( $select.find( 'option[value="' + chosen + '"]' ).length ? chosen : '' );
			$select.trigger( 'change' );
		} );
	}

	if ( ! window.wpswVcEdit ) {
		window.wpswVcEdit = true;

		$( document ).on( 'change', 'select.wpb_vc_param_value[name="id"]', function () {
			formOf( this ).find( '.wpsw-vc-edit' ).each( function () {
				sync( this );
			} );
		} );

		$( document ).on( 'click', '.wpsw-vc-edit__add', function () {
			$( this ).closest( '.wpsw-vc-edit' ).addClass( 'is-adding' );
		} );

		var back = function () {
			if ( document.hidden ) {
				return;
			}
			$( '.wpsw-vc-edit' ).each( function () {
				var adding = $( this ).hasClass( 'is-adding' );
				$( this ).removeClass( 'is-adding' );
				refresh( this, adding );
			} );
		};
		document.addEventListener( 'visibilitychange', back );
		window.addEventListener( 'focus', function () {
			if ( $( '.wpsw-vc-edit.is-adding' ).length ) {
				back();
			}
		} );

		// Settings forms rendered later, as they open.
		if ( 'MutationObserver' in window ) {
			new MutationObserver( function ( records ) {
				for ( var i = 0; i < records.length; i++ ) {
					for ( var j = 0; j < records[ i ].addedNodes.length; j++ ) {
						var node = records[ i ].addedNodes[ j ];
						if ( node.nodeType === 1 && ( node.matches( '.wpsw-vc-edit' ) || node.querySelector( '.wpsw-vc-edit' ) ) ) {
							syncAll();
							return;
						}
					}
				}
			} ).observe( document.body, { childList: true, subtree: true } );
		}
	}

	$( syncAll );
}( window.jQuery ) );
