/*
 * WP Scroll Words: the photos of a text in a lightbox (texts with "Open photos in lightbox").
 * One dialog for the page, made on the first click. Arrows / swipe between the photos of the
 * same text, Esc or the background closes, focus stays inside and goes back to the photo.
 */
( function () {
	'use strict';

	var t = window.wpswLightbox || {};
	var reduce = window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
	var box, img, caption, count, prevBtn, nextBtn, closeBtn;
	var items = [];
	var index = 0;
	var opener = null;
	var hideTimer = 0;

	function button( cls, label, icon ) {
		var b = document.createElement( 'button' );
		b.type = 'button';
		b.className = 'wpsw-lb__btn ' + cls;
		b.setAttribute( 'aria-label', label );
		b.innerHTML = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="' + icon + '"/></svg>';
		return b;
	}

	function build() {
		box = document.createElement( 'div' );
		box.className = 'wpsw-lb' + ( reduce ? ' wpsw-lb--still' : '' );
		box.setAttribute( 'role', 'dialog' );
		box.setAttribute( 'aria-modal', 'true' );
		box.setAttribute( 'aria-label', t.label || 'Photo' );
		box.hidden = true;
		box.innerHTML = '<figure class="wpsw-lb__figure"><img class="wpsw-lb__img" alt=""><figcaption class="wpsw-lb__caption"></figcaption></figure><span class="wpsw-lb__count" aria-live="polite"></span>';
		closeBtn = button( 'wpsw-lb__close', t.close || 'Close', 'M6 6l12 12M18 6L6 18' );
		prevBtn = button( 'wpsw-lb__prev', t.prev || 'Previous photo', 'M15 5l-7 7 7 7' );
		nextBtn = button( 'wpsw-lb__next', t.next || 'Next photo', 'M9 5l7 7-7 7' );
		box.append( closeBtn, prevBtn, nextBtn );
		document.body.append( box );

		img = box.querySelector( '.wpsw-lb__img' );
		caption = box.querySelector( '.wpsw-lb__caption' );
		count = box.querySelector( '.wpsw-lb__count' );

		closeBtn.addEventListener( 'click', close );
		prevBtn.addEventListener( 'click', function () { go( -1 ); } );
		nextBtn.addEventListener( 'click', function () { go( 1 ); } );
		// The dark background closes; the photo itself does not.
		box.addEventListener( 'click', function ( e ) {
			if ( e.target === box || e.target.classList.contains( 'wpsw-lb__figure' ) ) {
				close();
			}
		} );
		box.addEventListener( 'keydown', keys );
		swipe();
	}

	function show() {
		var b = items[ index ];
		img.removeAttribute( 'srcset' );
		img.src = b.dataset.src;
		if ( b.dataset.srcset ) {
			img.srcset = b.dataset.srcset;
			img.sizes = '100vw';
		}
		img.alt = b.dataset.alt || '';
		caption.textContent = b.dataset.caption || '';
		caption.hidden = ! b.dataset.caption;
		var many = items.length > 1;
		count.textContent = many ? ( index + 1 ) + ' / ' + items.length : '';
		prevBtn.hidden = ! many;
		nextBtn.hidden = ! many;
	}

	function go( step ) {
		index = ( index + step + items.length ) % items.length;
		show();
	}

	function open( zoom ) {
		if ( ! box ) {
			build();
		}
		var root = zoom.closest( '.wpsw' );
		items = Array.prototype.slice.call( ( root || document ).querySelectorAll( '.wpsw-zoom' ) );
		index = Math.max( 0, items.indexOf( zoom ) );
		opener = zoom;
		// Opened again while the last close was fading: that close must not hide it.
		clearTimeout( hideTimer );
		show();
		box.hidden = false;
		document.documentElement.classList.add( 'wpsw-lb-open' );
		// Next frame: the open transition runs from the closed state.
		requestAnimationFrame( function () { box.classList.add( 'is-open' ); } );
		closeBtn.focus();
	}

	function close() {
		if ( ! box || box.hidden ) {
			return;
		}
		box.classList.remove( 'is-open' );
		document.documentElement.classList.remove( 'wpsw-lb-open' );
		hideTimer = setTimeout( function () { box.hidden = true; img.removeAttribute( 'src' ); img.removeAttribute( 'srcset' ); }, reduce ? 0 : 200 );
		if ( opener ) {
			opener.focus();
		}
	}

	function keys( e ) {
		if ( e.key === 'Escape' ) {
			close();
		} else if ( e.key === 'ArrowLeft' && items.length > 1 ) {
			go( -1 );
		} else if ( e.key === 'ArrowRight' && items.length > 1 ) {
			go( 1 );
		} else if ( e.key === 'Tab' ) {
			// Focus stays on the dialog's buttons.
			var f = [ closeBtn, prevBtn, nextBtn ].filter( function ( b ) { return ! b.hidden; } );
			var i = f.indexOf( document.activeElement );
			e.preventDefault();
			f[ ( i + ( e.shiftKey ? -1 : 1 ) + f.length ) % f.length ].focus();
		}
	}

	// Swipe left / right on touch screens.
	function swipe() {
		var x = null;
		var y = null;
		box.addEventListener( 'touchstart', function ( e ) {
			x = e.touches[ 0 ].clientX;
			y = e.touches[ 0 ].clientY;
		}, { passive: true } );
		box.addEventListener( 'touchend', function ( e ) {
			if ( x === null || items.length < 2 ) {
				return;
			}
			var dx = e.changedTouches[ 0 ].clientX - x;
			var dy = e.changedTouches[ 0 ].clientY - y;
			x = null;
			if ( Math.abs( dx ) > 50 && Math.abs( dx ) > Math.abs( dy ) ) {
				go( dx < 0 ? 1 : -1 );
			}
		} );
	}

	document.addEventListener( 'click', function ( e ) {
		var zoom = e.target.closest( '.wpsw-zoom' );
		if ( ! zoom ) {
			return;
		}
		e.preventDefault();
		// A photo not shown yet (it pops in on scroll): no lightbox for something invisible.
		var pill = zoom.querySelector( '.wpsw-pill' );
		if ( pill && parseFloat( getComputedStyle( pill ).opacity ) < 0.5 ) {
			return;
		}
		open( zoom );
	} );
}() );
