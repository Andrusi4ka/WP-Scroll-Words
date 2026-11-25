/* WP Scroll Words: lights up words and photos as the block scrolls past. */
(function () {
	'use strict';

	var items = [];
	var ticking = false;
	var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

	function collect() {
		var blocks = document.querySelectorAll('[data-scroll-words]:not(.is-ready)');
		for (var b = 0; b < blocks.length; b++) {
			var el = blocks[b];
			el.classList.add('is-ready');
			if (reduce) continue;
			items.push({ el: el, parts: el.querySelectorAll('.wpsw-w, .wpsw-pill'), lit: -1 });
		}
	}

	function update() {
		ticking = false;
		var vh = window.innerHeight;
		for (var k = 0; k < items.length; k++) {
			var it = items[k];
			var r = it.el.getBoundingClientRect();
			if (r.bottom < -200 || r.top > vh + 200) continue;

			// 0 when the top is 85% down the screen, 1 when the block is almost past
			var p = (vh * 0.85 - r.top) / (r.height + vh * 0.35);
			p = Math.min(1, Math.max(0, p));
			var n = Math.round(p * it.parts.length);
			if (n === it.lit) continue;
			it.lit = n;
			for (var i = 0; i < it.parts.length; i++) {
				it.parts[i].classList.toggle('lit', i < n);
			}
		}
	}

	function onScroll() {
		if (!ticking) {
			ticking = true;
			requestAnimationFrame(update);
		}
	}

	var listening = false;

	// Blocks on the page now (again after WPBakery's frontend editor renders new ones): listeners once, when there is one.
	function init() {
		collect();
		if (reduce || !items.length) return;
		if (!listening) {
			listening = true;
			window.addEventListener('scroll', onScroll, { passive: true });
			window.addEventListener('resize', onScroll);
		}
		update();
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}

	document.addEventListener('vc-full-content-loaded', init);
})();
