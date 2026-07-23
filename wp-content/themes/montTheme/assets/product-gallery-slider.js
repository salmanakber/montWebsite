/**
 * Mobile/tablet product gallery slider.
 * Pixel-based widths so video intrinsic size cannot force a 2-up layout.
 */
(function () {
    'use strict';

    if (window.__montGallerySliderInit) return;
    window.__montGallerySliderInit = true;

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
        var slideW = 0;

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

        function measure() {
            slideW = Math.round(wrapper.getBoundingClientRect().width) || wrapper.offsetWidth || window.innerWidth;
            return slideW;
        }

        function applyMobileLayout() {
            wrapper.classList.add('is-mobile-slider');
            measure();

            wrapper.style.position = 'relative';
            wrapper.style.width = '100%';
            wrapper.style.overflow = 'hidden';
            wrapper.style.maxWidth = '100%';

            track.style.display = 'flex';
            track.style.flexDirection = 'row';
            track.style.flexWrap = 'nowrap';
            track.style.alignItems = 'stretch';
            track.style.gap = '0px';
            track.style.width = (slideW * slides.length) + 'px';
            track.style.maxWidth = 'none';
            track.style.transition = 'transform 0.35s ease';
            track.style.willChange = 'transform';
            track.style.overflow = 'visible';
            track.style.scrollSnapType = 'none';
            track.style.gridTemplateColumns = 'none';

            slides.forEach(function (slide) {
                slide.classList.remove('initially-hidden');
                slide.style.display = 'block';
                slide.style.flex = '0 0 ' + slideW + 'px';
                slide.style.flexGrow = '0';
                slide.style.flexShrink = '0';
                slide.style.flexBasis = slideW + 'px';
                slide.style.width = slideW + 'px';
                slide.style.minWidth = slideW + 'px';
                slide.style.maxWidth = slideW + 'px';
                slide.style.boxSizing = 'border-box';
                slide.style.position = 'relative';
                slide.style.overflow = 'hidden';
                slide.style.aspectRatio = '3 / 4';
                slide.style.height = 'auto';

                var media = slide.querySelector('video, img');
                if (media) {
                    media.style.position = 'absolute';
                    media.style.inset = '0';
                    media.style.top = '0';
                    media.style.left = '0';
                    media.style.right = '0';
                    media.style.bottom = '0';
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
        }

        function goTo(i, animate) {
            if (!isMobile()) return;
            if (typeof animate === 'undefined') animate = true;
            if (!slideW) measure();
            index = Math.max(0, Math.min(i, slides.length - 1));
            track.style.transition = animate ? 'transform 0.35s ease' : 'none';
            track.style.transform = 'translate3d(' + (-index * slideW) + 'px, 0, 0)';

            if (dotsWrap) {
                var dots = dotsWrap.querySelectorAll('.mont_gallery_dot');
                Array.prototype.forEach.call(dots, function (d, di) {
                    d.classList.toggle('is-active', di === index);
                });
            }
            if (prevBtn) prevBtn.disabled = index <= 0;
            if (nextBtn) nextBtn.disabled = index >= slides.length - 1;

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
            track.style.transform = 'translate3d(' + ((-index * slideW) + deltaX) + 'px, 0, 0)';
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

        // Run after layout so widths are correct (fonts/images)
        onResize();
        window.setTimeout(onResize, 50);
        window.setTimeout(onResize, 300);
    });
})();
