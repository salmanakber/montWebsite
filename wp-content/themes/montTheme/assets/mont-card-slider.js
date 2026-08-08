/**
 * Standalone product-card image sliders: dots, swipe, autoplay ~3s.
 */
(function () {
    'use strict';

    if (window.__montCardSliderInit) return;
    window.__montCardSliderInit = true;

    var AUTO_MS = 3000;
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
        var dots = Array.prototype.slice.call(root.querySelectorAll('.mont-card-slider__dot'));
        var index = 0;
        var timer = null;
        var startX = 0;
        var deltaX = 0;
        var dragging = false;
        var width = 0;
        var paused = false;

        function measure() {
            width = root.getBoundingClientRect().width || root.offsetWidth || 1;
            return width;
        }

        function goTo(i, animate) {
            if (typeof animate === 'undefined') animate = true;
            index = ((i % slides.length) + slides.length) % slides.length;
            track.style.transition = animate ? 'transform 0.35s ease' : 'none';
            track.style.transform = 'translate3d(' + (-index * 100) + '%, 0, 0)';
            dots.forEach(function (dot, di) {
                dot.classList.toggle('is-active', di === index);
            });
            // Prefetch next slide image.
            var next = slides[(index + 1) % slides.length];
            var img = next ? next.querySelector('img') : null;
            if (img) {
                img.loading = 'eager';
                if (!img.complete) {
                    var warm = new Image();
                    warm.src = img.currentSrc || img.src;
                }
            }
        }

        function next() {
            goTo(index + 1);
        }

        function stopAuto() {
            if (timer) {
                clearInterval(timer);
                timer = null;
            }
        }

        function startAuto() {
            stopAuto();
            if (paused || slides.length < 2) return;
            timer = setInterval(next, AUTO_MS);
        }

        dots.forEach(function (dot) {
            dot.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                goTo(parseInt(dot.getAttribute('data-index'), 10) || 0);
                startAuto();
            });
        });

        function onPointerDown(clientX) {
            dragging = true;
            paused = true;
            stopAuto();
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
            paused = false;
            startAuto();
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
            onPointerDown(e.clientX);
        });
        window.addEventListener('mousemove', function (e) {
            if (dragging) onPointerMove(e.clientX);
        });
        window.addEventListener('mouseup', onPointerUp);

        root.addEventListener('mouseenter', function () {
            paused = true;
            stopAuto();
        });
        root.addEventListener('mouseleave', function () {
            if (!dragging) {
                paused = false;
                startAuto();
            }
        });

        // Prevent card navigation when interacting with the slider chrome.
        root.addEventListener('click', function (e) {
            if (e.target.closest('.mont-card-slider__dot')) {
                e.preventDefault();
                e.stopPropagation();
            }
        });

        measure();
        goTo(0, false);
        startAuto();

        document.addEventListener('visibilitychange', function () {
            if (document.hidden) stopAuto();
            else if (!paused) startAuto();
        });
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

    // Support lazy-loaded grids.
    window.montInitCardSliders = boot;
})();
