/**
 * Product-card image sliders: hover arrows, thin progress bar, swipe.
 * No autoplay — next/prev only via arrows or swipe.
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
            return;
        }

        root.__montSliderBound = true;
        var prevBtn = root.querySelector('.mont-card-slider__btn--prev');
        var nextBtn = root.querySelector('.mont-card-slider__btn--next');
        var progressFill = root.querySelector('.mont-card-slider__progress-fill');
        var index = 0;
        var startX = 0;
        var deltaX = 0;
        var dragging = false;
        var width = 0;
        var total = slides.length;

        function measure() {
            width = root.getBoundingClientRect().width || root.offsetWidth || 1;
            return width;
        }

        function updateProgress() {
            if (!progressFill) return;
            var pct = ((index + 1) / total) * 100;
            progressFill.style.width = pct + '%';
            root.setAttribute('data-slide', String(index + 1));
            root.setAttribute('data-slides', String(total));
        }

        function goTo(i, animate) {
            if (typeof animate === 'undefined') animate = true;
            index = ((i % total) + total) % total;
            track.style.transition = animate ? 'transform 0.45s cubic-bezier(0.22, 1, 0.36, 1)' : 'none';
            track.style.transform = 'translate3d(' + (-index * 100) + '%, 0, 0)';
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

        function onPointerDown(clientX) {
            dragging = true;
            startX = clientX;
            deltaX = 0;
            measure();
            track.style.transition = 'none';
            root.closest('.product-item') && root.closest('.product-item').setAttribute('data-mont-swiped', '0');
            var cardLink = root.closest('a.b2b-product-card-link');
            if (cardLink) cardLink.setAttribute('data-mont-swiped', '0');
        }

        function onPointerMove(clientX) {
            if (!dragging) return;
            deltaX = clientX - startX;
            var pct = (deltaX / width) * 100;
            track.style.transform = 'translate3d(' + ((-index * 100) + pct) + '%, 0, 0)';
        }

        function onPointerUp() {
            if (!dragging) return;
            dragging = false;
            var swiped = Math.abs(deltaX) > SWIPE_THRESHOLD;
            if (swiped) {
                goTo(deltaX < 0 ? index + 1 : index - 1);
                var host = root.closest('.product-item') || root.closest('a.b2b-product-card-link');
                if (host) host.setAttribute('data-mont-swiped', '1');
            } else {
                goTo(index);
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

        measure();
        goTo(0, false);
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
