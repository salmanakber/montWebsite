/**
 * Product-card image sliders: hover zoom, arrows, thin progress bar, swipe.
 * Slides fade (no full-track slide animation). No autoplay.
 */
(function () {
    'use strict';

    if (window.__montCardSliderInit) return;
    window.__montCardSliderInit = true;

    var SWIPE_THRESHOLD = 36;

    function initSlider(root) {
        if (!root || root.__montSliderBound) return;
        var track = root.querySelector('.mont-card-slider__track');
        var slides = track ? Array.prototype.slice.call(track.children) : [];
        if (!track || slides.length < 2) {
            root.__montSliderBound = true;
            root.classList.add('is-single');
            return;
        }

        root.__montSliderBound = true;
        root.classList.add('is-fade');
        var prevBtn = root.querySelector('.mont-card-slider__btn--prev');
        var nextBtn = root.querySelector('.mont-card-slider__btn--next');
        var progressFill = root.querySelector('.mont-card-slider__progress-fill');
        var index = 0;
        var startX = 0;
        var deltaX = 0;
        var dragging = false;
        var total = slides.length;

        function updateProgress() {
            if (!progressFill) return;
            progressFill.style.width = (((index + 1) / total) * 100) + '%';
            root.setAttribute('data-slide', String(index + 1));
            root.setAttribute('data-slides', String(total));
        }

        function goTo(i) {
            index = ((i % total) + total) % total;
            slides.forEach(function (slide, di) {
                slide.classList.toggle('is-active', di === index);
            });
            updateProgress();
            var nextSlide = slides[(index + 1) % total];
            var img = nextSlide ? nextSlide.querySelector('img') : null;
            if (img) {
                img.loading = 'eager';
                if (!img.complete) {
                    var warm = new Image();
                    warm.src = img.currentSrc || img.src;
                }
            }
        }

        function prev(e) {
            if (e) {
                e.preventDefault();
                e.stopPropagation();
            }
            goTo(index - 1);
        }

        function next(e) {
            if (e) {
                e.preventDefault();
                e.stopPropagation();
            }
            goTo(index + 1);
        }

        if (prevBtn) prevBtn.addEventListener('click', prev);
        if (nextBtn) nextBtn.addEventListener('click', next);

        function markSwipeHost(value) {
            var item = root.closest('.product-item');
            if (item) item.setAttribute('data-mont-swiped', value);
            var cardLink = root.closest('a.b2b-product-card-link');
            if (cardLink) cardLink.setAttribute('data-mont-swiped', value);
        }

        function onPointerDown(clientX) {
            dragging = true;
            startX = clientX;
            deltaX = 0;
            markSwipeHost('0');
        }

        function onPointerMove(clientX) {
            if (!dragging) return;
            deltaX = clientX - startX;
        }

        function onPointerUp() {
            if (!dragging) return;
            dragging = false;
            if (Math.abs(deltaX) > SWIPE_THRESHOLD) {
                goTo(deltaX < 0 ? index + 1 : index - 1);
                markSwipeHost('1');
            }
        }

        root.addEventListener('touchstart', function (e) {
            if (!e.touches || !e.touches.length) return;
            onPointerDown(e.touches[0].clientX);
        }, { passive: true });

        root.addEventListener('touchmove', function (e) {
            if (!dragging || !e.touches || !e.touches.length) return;
            onPointerMove(e.touches[0].clientX);
        }, { passive: true });

        root.addEventListener('touchend', onPointerUp);
        root.addEventListener('touchcancel', onPointerUp);

        root.addEventListener('mousedown', function (e) {
            if (e.button !== 0) return;
            if (e.target.closest('.mont-card-slider__btn')) return;
            onPointerDown(e.clientX);
        });
        window.addEventListener('mousemove', function (e) {
            if (dragging) onPointerMove(e.clientX);
        });
        window.addEventListener('mouseup', onPointerUp);

        root.addEventListener('click', function (e) {
            if (e.target.closest('.mont-card-slider__btn, .mont-card-slider__progress')) {
                e.preventDefault();
                e.stopPropagation();
            }
        });

        goTo(0);
    }

    function boot(scope) {
        var root = scope && scope.querySelectorAll ? scope : document;
        Array.prototype.forEach.call(root.querySelectorAll('[data-mont-card-slider]'), initSlider);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { boot(document); });
    } else {
        boot(document);
    }

    window.montInitCardSliders = boot;
})();
