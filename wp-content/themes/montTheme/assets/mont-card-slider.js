/**
 * Standalone product-card image sliders: dots, swipe, slower autoplay.
 * Each card gets its own random interval so slides don't sync.
 */
(function () {
    'use strict';

    if (window.__montCardSliderInit) return;
    window.__montCardSliderInit = true;

    var AUTO_MIN = 5200;
    var AUTO_MAX = 9200;
    var SWIPE_THRESHOLD = 36;

    function randomInterval() {
        return Math.floor(AUTO_MIN + Math.random() * (AUTO_MAX - AUTO_MIN));
    }

    function randomStartDelay() {
        return Math.floor(400 + Math.random() * 2800);
    }

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
        var startDelay = null;
        var autoMs = randomInterval();
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
            track.style.transition = animate ? 'transform 0.7s cubic-bezier(0.22, 1, 0.36, 1)' : 'none';
            track.style.transform = 'translate3d(' + (-index * 100) + '%, 0, 0)';
            dots.forEach(function (dot, di) {
                dot.classList.toggle('is-active', di === index);
            });
            var nextSlide = slides[(index + 1) % slides.length];
            var img = nextSlide ? nextSlide.querySelector('img') : null;
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
            if (startDelay) {
                clearTimeout(startDelay);
                startDelay = null;
            }
        }

        function startAuto() {
            stopAuto();
            if (paused || slides.length < 2) return;
            autoMs = randomInterval();
            startDelay = setTimeout(function () {
                startDelay = null;
                if (paused) return;
                timer = setInterval(next, autoMs);
            }, randomStartDelay());
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

    window.montInitCardSliders = boot;
})();
