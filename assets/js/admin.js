/* WP Scroll Words: the photos picker (media library, drag to order) and the shortcode's Copy buttons. */
( function ( $ ) {
	'use strict';

	var $list = $( '[data-wpsw-images]' );
	var $input = $( '[data-wpsw-images-input]' );
	var frame;

	// Numbers on the photos ({1}, {2}…) and the hidden field, in the list's order.
	function sync() {
		var ids = [];
		$list.children( 'li' ).each( function ( i ) {
			ids.push( $( this ).data( 'id' ) );
			$( this ).find( '.wpsw-images__num' ).text( '{' + ( i + 1 ) + '}' );
		} );
		$input.val( ids.join( ',' ) );
	}

	function item( attachment ) {
		var sizes = attachment.sizes || {};
		var src = ( sizes.thumbnail || sizes.medium || sizes.full || { url: attachment.url } ).url;
		return $( '<li>' ).attr( 'data-id', attachment.id ).data( 'id', attachment.id ).append(
			$( '<img>' ).attr( { src: src, alt: '' } ),
			$( '<span class="wpsw-images__num">' ),
			$( '<button type="button" class="wpsw-images__remove">&times;</button>' ).attr( 'aria-label', 'Remove' )
		);
	}

	if ( $list.length ) {
		$list.sortable( { update: sync, tolerance: 'pointer' } );
		sync();

		$list.on( 'click', '.wpsw-images__remove', function () {
			$( this ).closest( 'li' ).remove();
			sync();
		} );

		$( '[data-wpsw-add]' ).on( 'click', function () {
			if ( ! frame ) {
				frame = wp.media( {
					title: wpswAdmin.title,
					button: { text: wpswAdmin.button },
					library: { type: 'image' },
					multiple: 'add'
				} );
				frame.on( 'select', function () {
					frame.state().get( 'selection' ).each( function ( model ) {
						$list.append( item( model.toJSON() ) );
					} );
					sync();
				} );
			}
			frame.open();
		} );
	}

	$( document ).on( 'click', '[data-wpsw-copy]', function () {
		var $btn = $( this );
		var text = $btn.attr( 'data-wpsw-copy' );
		var done = function () {
			var label = $btn.text();
			$btn.text( wpswAdmin.copied );
			setTimeout( function () { $btn.text( label ); }, 1500 );
		};
		if ( navigator.clipboard && window.isSecureContext ) {
			navigator.clipboard.writeText( text ).then( done );
		} else {
			$btn.prev( 'input' ).trigger( 'select' );
			document.execCommand( 'copy' );
			done();
		}
	} );
}( jQuery ) );
