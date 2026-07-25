/**
 * Category tabs — mobile left/right scroll arrows (B2B + B2C)
 */
(function () {
	function initTabScroller(container) {
		var scroller =
			container.querySelector('.mont-cat-tabs__scroller') ||
			container.querySelector('.category-slider-wrapper') ||
			container.querySelector('.category-slider');
		var prev = container.querySelector('.prev-arrow');
		var next = container.querySelector('.next-arrow');

		if (!scroller || !prev || !next) {
			return;
		}

		function maxScroll() {
			return Math.max(0, scroller.scrollWidth - scroller.clientWidth);
		}

		function updateArrows() {
			var max = maxScroll();
			var left = scroller.scrollLeft;
			var hasOverflow = max > 4;

			container.classList.toggle('has-tab-overflow', hasOverflow);
			prev.classList.toggle('is-disabled', left <= 2);
			next.classList.toggle('is-disabled', left >= max - 2);
			prev.setAttribute('aria-disabled', left <= 2 ? 'true' : 'false');
			next.setAttribute('aria-disabled', left >= max - 2 ? 'true' : 'false');
		}

		function scrollByDir(dir) {
			var amount = Math.max(120, Math.round(scroller.clientWidth * 0.55));
			scroller.scrollBy({ left: dir * amount, behavior: 'smooth' });
		}

		prev.addEventListener('click', function (e) {
			e.preventDefault();
			if (prev.classList.contains('is-disabled')) return;
			scrollByDir(-1);
		});

		next.addEventListener('click', function (e) {
			e.preventDefault();
			if (next.classList.contains('is-disabled')) return;
			scrollByDir(1);
		});

		scroller.addEventListener('scroll', updateArrows, { passive: true });
		window.addEventListener('resize', updateArrows);

		var active =
			scroller.querySelector('.category-active') ||
			scroller.querySelector('.is-active') ||
			scroller.querySelector('.active-li');
		if (active && typeof active.scrollIntoView === 'function') {
			active.scrollIntoView({ behavior: 'auto', block: 'nearest', inline: 'center' });
		}

		updateArrows();
		requestAnimationFrame(updateArrows);
		setTimeout(updateArrows, 150);
	}

	function boot() {
		document
			.querySelectorAll('.category-slider-container, .mont-cat-tabs')
			.forEach(initTabScroller);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', boot);
	} else {
		boot();
	}
})();
