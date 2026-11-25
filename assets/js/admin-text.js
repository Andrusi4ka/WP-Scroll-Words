/*
 * WP Scroll Words → the "Text style" card (class-wpsw-admin-text.php), as WP Banner's fields:
 * Font size per device, the Font list (Woodmart's fonts, searchable) with Weight offering the
 * font's own weights, and Color (Pickr with the theme's palette, Default, Cancel, eyedropper).
 */
( function () {
	'use strict';

	var card = document.querySelector( '.wpsw-card' );
	if ( ! card ) {
		return;
	}
	var data = window.wpswText || {};
	var escape = function ( v ) {
		return String( v ).replace( /[&<>"']/g, function ( c ) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ c ]; } );
	};

	/* ---------- Font size per device ---------- */
	var sizes = {};
	card.querySelectorAll( '[data-wpsw-size]' ).forEach( function ( box ) { sizes[ box.dataset.wpswSize ] = box.querySelector( 'input' ); } );

	function syncSizes() {
		var desktop = sizes.desktop.value || sizes.desktop.placeholder;
		sizes.tablet.placeholder = desktop;
		sizes.mobile.placeholder = sizes.tablet.value || desktop;
		card.querySelectorAll( '[data-wpsw-device]' ).forEach( function ( btn ) {
			var d = btn.dataset.wpswDevice;
			btn.classList.toggle( 'has-value', d !== 'desktop' && sizes[ d ].value !== '' );
		} );
	}
	card.addEventListener( 'click', function ( e ) {
		var btn = e.target.closest( '[data-wpsw-device]' );
		if ( ! btn ) {
			return;
		}
		var device = btn.dataset.wpswDevice;
		card.querySelectorAll( '[data-wpsw-device]' ).forEach( function ( b ) {
			var on = b === btn;
			b.classList.toggle( 'is-active', on );
			b.setAttribute( 'aria-pressed', String( on ) );
		} );
		card.querySelectorAll( '[data-wpsw-size]' ).forEach( function ( box ) { box.hidden = box.dataset.wpswSize !== device; } );
		sizes[ device ].focus();
	} );
	card.addEventListener( 'input', function ( e ) {
		if ( e.target.closest( '[data-wpsw-size]' ) ) {
			syncSizes();
		}
	} );
	syncSizes();

	/* ---------- Font and Weight ---------- */
	var FONTS = data.fonts && Array.isArray( data.fonts.groups ) ? data.fonts : null;
	var INDEX = {};
	if ( FONTS ) {
		FONTS.groups.forEach( function ( group ) {
			group[ 1 ].forEach( function ( item ) { INDEX[ item[ 0 ] ] = [ item[ 1 ], item[ 2 ] ]; } );
		} );
		Object.keys( FONTS.google || {} ).forEach( function ( name ) {
			if ( ! INDEX[ name ] ) {
				INDEX[ name ] = [ name, FONTS.google[ name ] ];
			}
		} );
	}
	var fontInput = card.querySelector( '[data-wpsw-font-value]' );
	var weight = card.querySelector( '[data-wpsw-weight]' );
	var box = card.querySelector( '.wpsw-font' );
	var DEFAULT_NAME = 'Default (page font)';

	var fontName = function ( v ) { return v === '' ? DEFAULT_NAME : ( INDEX[ v ] ? INDEX[ v ][ 0 ] : v ); };
	var fontWeights = function ( v ) { return ( v !== '' && INDEX[ v ] ? INDEX[ v ][ 1 ] : '123456789' ).split( '' ).map( function ( d ) { return Number( d ) * 100; } ); };

	// Weight offers the font's weights only; one it does not have: the nearest it has.
	function syncWeights() {
		var list = fontWeights( fontInput.value );
		var w = Number( weight.value );
		Array.prototype.forEach.call( weight.options, function ( o ) {
			var ok = list.indexOf( Number( o.value ) ) !== -1;
			o.hidden = ! ok;
			o.disabled = ! ok;
		} );
		if ( list.indexOf( w ) === -1 ) {
			weight.value = String( list.reduce( function ( best, x ) { return Math.abs( x - w ) < Math.abs( best - w ) ? x : best; }, list[ 0 ] ) );
		}
	}

	if ( box ) {
		var toggle = box.querySelector( '.wpsw-font__toggle' );
		var panel = box.querySelector( '.wpsw-font__panel' );
		var search = box.querySelector( '.wpsw-font__search' );
		var list = box.querySelector( '.wpsw-font__list' );

		var showName = function () { box.querySelector( '.wpsw-font__name' ).textContent = fontName( fontInput.value ); };

		var listFonts = function () {
			var q = search.value.trim().toLowerCase(), chosen = fontInput.value;
			var match = function ( name ) { return ! q || name.toLowerCase().indexOf( q ) !== -1; };
			var option = function ( value, name ) {
				return '<div class="wpsw-font__option' + ( value === chosen ? ' is-chosen' : '' ) + '" role="option" data-family="' + escape( value ) + '" aria-selected="' + ( value === chosen ) + '">' + escape( name ) + '</div>';
			};
			var html = match( DEFAULT_NAME ) ? option( '', DEFAULT_NAME ) : '';
			if ( chosen && ! INDEX[ chosen ] && match( chosen ) ) {
				html += option( chosen, chosen );
			}
			FONTS.groups.forEach( function ( group ) {
				var rows = group[ 1 ].filter( function ( item ) { return match( item[ 1 ] ); } ).map( function ( item ) { return option( item[ 0 ], item[ 1 ] ); } ).join( '' );
				if ( rows ) {
					html += '<div class="wpsw-font__group">' + escape( group[ 0 ] ) + '</div>' + rows;
				}
			} );
			var google = Object.keys( FONTS.google || {} ).filter( match ).map( function ( name ) { return option( name, name ); } ).join( '' );
			if ( google ) {
				html += '<div class="wpsw-font__group">Google fonts</div>' + google;
			}
			list.innerHTML = html || '<p class="wpsw-font__empty">No fonts found.</p>';
			var active = list.querySelector( '.is-chosen' ) || list.querySelector( '.wpsw-font__option' );
			if ( active ) {
				active.classList.add( 'is-active' );
				active.scrollIntoView( { block: 'nearest' } );
			}
		};

		var close = function ( focus ) {
			panel.hidden = true;
			toggle.setAttribute( 'aria-expanded', 'false' );
			if ( focus ) {
				toggle.focus();
			}
		};
		var open = function () {
			panel.hidden = false;
			toggle.setAttribute( 'aria-expanded', 'true' );
			search.value = '';
			listFonts();
			search.focus();
		};
		var choose = function ( value ) {
			fontInput.value = value;
			showName();
			syncWeights();
			close( true );
		};

		toggle.addEventListener( 'click', function () {
			if ( panel.hidden ) {
				open();
			} else {
				close( true );
			}
		} );
		search.addEventListener( 'input', listFonts );
		list.addEventListener( 'click', function ( e ) {
			var o = e.target.closest( '.wpsw-font__option' );
			if ( o ) {
				choose( o.dataset.family );
			}
		} );
		box.addEventListener( 'keydown', function ( e ) {
			if ( panel.hidden ) {
				return;
			}
			var options = Array.prototype.slice.call( list.querySelectorAll( '.wpsw-font__option' ) );
			var i = options.indexOf( list.querySelector( '.is-active' ) );
			if ( e.key === 'Escape' ) {
				e.preventDefault();
				close( true );
			} else if ( e.key === 'Enter' ) {
				e.preventDefault(); // Not the post form.
				if ( options[ i ] ) {
					choose( options[ i ].dataset.family );
				}
			} else if ( e.key === 'ArrowDown' || e.key === 'ArrowUp' ) {
				e.preventDefault();
				var next = options[ Math.max( 0, Math.min( options.length - 1, i + ( e.key === 'ArrowDown' ? 1 : -1 ) ) ) ];
				if ( next ) {
					options.forEach( function ( o ) { o.classList.remove( 'is-active' ); } );
					next.classList.add( 'is-active' );
					next.scrollIntoView( { block: 'nearest' } );
				}
			}
		} );
		document.addEventListener( 'click', function ( e ) {
			if ( ! panel.hidden && ! box.contains( e.target ) ) {
				close();
			}
		} );
		showName();
	}
	// Without Woodmart (a text field, no list) every weight stays offered.
	syncWeights();

	/* ---------- Color (Pickr) ---------- */
	var colorBox = card.querySelector( '.wpsw-color' );
	var palette = ( Array.isArray( data.palette ) ? data.palette : [] ).filter( function ( p ) { return p && /^#[0-9a-f]{6}$/.test( p.color ); } );
	var picker = null;

	function paint() {
		var v = colorBox.querySelector( 'input' ).value;
		var chip = colorBox.querySelector( '.wpsw-color__chip' );
		chip.style.boxShadow = v ? 'inset 0 0 0 60px ' + v : '';
		chip.classList.toggle( 'is-default', ! v );
		colorBox.querySelector( 'button' ).title = v || 'Default';
	}

	function openPicker( button ) {
		if ( ! window.Pickr || picker ) {
			return;
		}
		var input = colorBox.querySelector( 'input' );
		var opened = input.value;
		var store = function ( value ) {
			input.value = value;
			paint();
		};
		picker = window.Pickr.create( {
			el: button,
			useAsButton: true,
			theme: 'classic',
			appClass: 'wpsw-pcr',
			position: 'bottom-start',
			default: input.value || '#412b24',
			defaultRepresentation: 'HEXA',
			lockOpacity: true,
			swatches: palette.map( function ( p ) { return p.color; } ),
			components: { preview: false, opacity: false, hue: true, interaction: { hex: true, rgba: true, hsla: true, input: true, cancel: true } }
		} );
		picker.on( 'init', function ( instance ) {
			var root = instance.getRoot().app;
			root.querySelectorAll( '.pcr-swatches > button' ).forEach( function ( s, i ) {
				if ( palette[ i ] ) {
					s.title = palette[ i ].name;
					s.setAttribute( 'aria-label', palette[ i ].name );
				}
			} );
			var reset = document.createElement( 'input' );
			reset.type = 'button';
			reset.className = 'pcr-clear';
			reset.value = 'Default';
			reset.addEventListener( 'click', function () { store( '' ); instance.hide(); } );
			root.querySelector( '.pcr-interaction' ).append( reset );
			if ( window.EyeDropper ) {
				var drop = document.createElement( 'button' );
				drop.type = 'button';
				drop.className = 'wpsw-pcr__dropper';
				drop.title = 'Pick a color from the screen';
				drop.innerHTML = '<span class="dashicons dashicons-admin-customizer" aria-hidden="true"></span>';
				drop.addEventListener( 'click', function () {
					new window.EyeDropper().open().then( function ( r ) {
						if ( r && /^#[0-9a-f]{6}$/i.test( r.sRGBHex ) ) {
							store( r.sRGBHex.toLowerCase() );
							instance.setColor( r.sRGBHex, true );
						}
					} ).catch( function () {} );
				} );
				root.querySelector( '.pcr-result' ).after( drop );
			}
			instance.show();
		} );
		picker.on( 'show', function () { opened = input.value; button.setAttribute( 'aria-expanded', 'true' ); } );
		picker.on( 'change', function ( color ) {
			if ( color ) {
				store( color.toHEXA().toString().slice( 0, 7 ).toLowerCase() );
			}
		} );
		picker.on( 'cancel', function ( instance ) { store( opened ); instance.hide(); } );
		picker.on( 'hide', function ( instance ) {
			if ( instance.wpswGone ) {
				return;
			}
			instance.wpswGone = true;
			button.setAttribute( 'aria-expanded', 'false' );
			if ( picker === instance ) {
				picker = null;
			}
			// Made again on the next click, with the color the field has then.
			setTimeout( function () {
				var root = instance.getRoot().app;
				instance.destroy();
				if ( root ) {
					root.remove();
				}
			} );
		} );
	}

	if ( colorBox ) {
		colorBox.querySelector( 'button' ).addEventListener( 'click', function () { openPicker( this ); } );
		paint();
	}
}() );
