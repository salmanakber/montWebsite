/**
 * Mobile/tablet product gallery slider.
 * Forces one-slide-at-a-time for images AND video (desktop grid untouched).
 */
(function () {
    'use strict';

    var MQ = window.matchMedia('(max-width: 1024px)');

    function ready(fn) {
        if (document.readyState !== 'loading') fn();
        else document.addEventListener('DOMContentLoaded', fn);
    }

    ready(function () {
        var wrapper = document.querySelector('.mont_gallery_wrapper-unified');
        var track = document.getElementById('mont_gallery_track');
        if (!wrapper || !track) return;

        var slides = Array.prototype.slice.call(track.querySelectorAll('.mont_gallery_item'));
        if (!slides.length) return;

        var prevBtn = wrapper.querySelector('.mont_gallery_prev');
        var nextBtn = wrapper.querySelector('.mont_gallery_next');
        var dotsWrap = document.getElementById('mont_gallery_dots');
        var index = 0;
        var startX = 0;
        var deltaX = 0;
        var dragging = false;

        function isMobile() {
            return MQ.matches;
        }

        function ensureDots() {
            if (!dotsWrap || dotsWrap.childElementCount) return;
            slides.forEach(function (_, i) {
                var dot = document.createElement('button');
                dot.type = 'button';
                dot.className = 'mont_gallery_dot' + (i === 0 ? ' is-active' : '');
                dot.setAttribute('aria-label', 'Slide ' + (i + 1));
                dot.addEventListener('click', function () { goTo(i); });
                dotsWrap.appendChild(dot);
            });
        }

        function applyMobileLayout() {
            wrapper.classList.add('is-mobile-slider');

            // Inline styles beat cached CSS
            wrapper.style.position = 'relative';
            wrapper.style.width = '100%';
            wrapper.style.overflow = 'hidden';

            track.style.display = 'flex';
            track.style.flexDirection = 'row';
            track.style.flexWrap = 'nowrap';
            track.style.gap = '0';
            track.style.width = '100%';
            track.style.transition = 'transform 0.35s ease';
            track.style.willChange = 'transform';
            // disable native scroll — we control via transform
            track.style.overflow = 'hidden';
            track.style.scrollSnapType = 'none';

            slides.forEach(function (slide) {
                slide.style.display = 'block';
                slide.style.flex = '0 0 100%';
                slide.style.width = '100%';
                slide.style.minWidth = '100%';
                slide.style.maxWidth = '100%';
                slide.style.boxSizing = 'border-box';
                slide.style.position = 'relative';
                slide.style.overflow = 'hidden';
                slide.style.aspectRatio = '3 / 4';

                var media = slide.querySelector('video, img');
                if (media) {
                    media.style.width = '100%';
                    media.style.height = '100%';
                    media.style.maxWidth = '100%';
                    media.style.minWidth = '0';
                    media.style.objectFit = 'cover';
                    media.style.display = 'block';
                }
                var video = slide.querySelector('video');
                if (video) {
                    video.style.pointerEvents = 'none';
                }
            });

            var seeMore = wrapper.querySelector('.mont_see_more_container');
            if (seeMore) seeMore.style.display = 'none';

            if (prevBtn && prevBtn.parentElement) {
                prevBtn.parentElement.style.display = 'flex';
            }
            if (dotsWrap) {
                dotsWrap.style.display = 'flex';
            }

            ensureDots();
            goTo(index, false);
        }

        function clearMobileLayout() {
            wrapper.classList.remove('is-mobile-slider');
            wrapper.style.cssText = '';
            track.style.cssText = '';
            slides.forEach(function (slide) {
                slide.style.cssText = '';
                var media = slide.querySelector('video, img');
                if (media) media.style.cssText = '';
            });
            if (prevBtn && prevBtn.parentElement) {
                prevBtn.parentElement.style.display = '';
            }
            if (dotsWrap) dotsWrap.style.display = '';
            var seeMore = wrapper.querySelector('.mont_see_more_container');
            if (seeMore) seeMore.style.display = '';
            track.style.transform = '';
        }

        function goTo(i, animate) {
            if (!isMobile()) return;
            if (typeof animate === 'undefined') animate = true;
            index = Math.max(0, Math.min(i, slides.length - 1));
            track.style.transition = animate ? 'transform 0.35s ease' : 'none';
            track.style.transform = 'translate3d(' + (-index * 100) + '%, 0, 0)';

            if (dotsWrap) {
                var dots = dotsWrap.querySelectorAll('.mont_gallery_dot');
                Array.prototype.forEach.call(dots, function (d, di) {
                    d.classList.toggle('is-active', di === index);
                });
            }
            if (prevBtn) prevBtn.disabled = index <= 0;
            if (nextBtn) nextBtn.disabled = index >= slides.length - 1;

            // Pause off-slide videos; leave active slide alone (autoplay / user control)
            slides.forEach(function (slide, si) {
                var vid = slide.querySelector('video');
                if (!vid) return;
                if (si !== index) {
                    try { vid.pause(); } catch (e) {}
                }
            });
        }

        function onResize() {
            if (isMobile()) {
                applyMobileLayout();
            } else {
                clearMobileLayout();
            }
        }

        if (prevBtn) {
            prevBtn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                goTo(index - 1);
            });
        }
        if (nextBtn) {
            nextBtn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                goTo(index + 1);
            });
        }

        // Touch swipe
        track.addEventListener('touchstart', function (e) {
            if (!isMobile()) return;
            dragging = true;
            startX = e.touches[0].clientX;
            deltaX = 0;
            track.style.transition = 'none';
        }, { passive: true });

        track.addEventListener('touchmove', function (e) {
            if (!dragging || !isMobile()) return;
            deltaX = e.touches[0].clientX - startX;
            var pct = (deltaX / track.clientWidth) * 100;
            track.style.transform = 'translate3d(' + ((-index * 100) + pct) + '%, 0, 0)';
        }, { passive: true });

        track.addEventListener('touchend', function () {
            if (!dragging || !isMobile()) return;
            dragging = false;
            if (Math.abs(deltaX) > 40) {
                goTo(deltaX < 0 ? index + 1 : index - 1);
            } else {
                goTo(index);
            }
            deltaX = 0;
        });

        if (typeof MQ.addEventListener === 'function') {
            MQ.addEventListener('change', onResize);
        } else if (typeof MQ.addListener === 'function') {
            MQ.addListener(onResize);
        }
        window.addEventListener('resize', onResize);

        onResize();
    });
})();
