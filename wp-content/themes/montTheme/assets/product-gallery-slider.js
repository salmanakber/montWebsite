/**
 * Mobile/tablet product gallery slider (B2C unified gallery).
 * Slide width is measured from the viewport element in px — never 100% of the growing track,
 * and never mixed with mismatched 100vw / innerWidth (that blanks slides 2+ on iOS).
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

        /** Visible carousel width — prefer wrapper box, not 100vw / innerWidth alone. */
        function measureW() {
            var rect = wrapper.getBoundingClientRect();
            var w = Math.round(rect.width);
            if (w < 2) {
                w = Math.round(document.documentElement.clientWidth || window.innerWidth || 1);
            }
            return Math.max(1, w);
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

        function warmImg(media) {
            if (!media || media.tagName !== 'IMG') return;
            try { media.loading = 'eager'; } catch (e) {}
            media.removeAttribute('loading');
            media.classList.remove('lazyload', 'lazyloading');
            var src =
                media.getAttribute('data-src') ||
                media.getAttribute('data-full-src') ||
                media.getAttribute('src') ||
                media.currentSrc;
            if (!src) return;
            if (media.getAttribute('src') !== src) {
                media.setAttribute('src', src);
            }
            media.removeAttribute('srcset');
            media.removeAttribute('sizes');
            if (typeof media.decode === 'function') {
                media.decode().catch(function () {});
            }
            var warm = new Image();
            warm.decoding = 'async';
            warm.src = src;
        }

        function sizeSlides(w) {
            wrapper.style.setProperty('--mont-slide-w', w + 'px');
            track.style.setProperty('display', 'flex', 'important');
            track.style.setProperty('flex-direction', 'row', 'important');
            track.style.setProperty('flex-wrap', 'nowrap', 'important');
            track.style.setProperty('gap', '0px', 'important');
            track.style.setProperty('width', (w * slides.length) + 'px', 'important');
            track.style.setProperty('max-width', 'none', 'important');
            track.style.setProperty('min-width', (w * slides.length) + 'px', 'important');
            track.style.transition = dragging ? 'none' : 'transform 0.35s ease';
            track.style.willChange = 'transform';
            track.style.overflow = 'visible';
            track.style.scrollSnapType = 'none';
            track.style.gridTemplateColumns = 'none';

            slides.forEach(function (slide) {
                slide.classList.remove('initially-hidden');
                slide.style.setProperty('display', 'block', 'important');
                slide.style.setProperty('flex', '0 0 ' + w + 'px', 'important');
                slide.style.setProperty('flex-basis', w + 'px', 'important');
                slide.style.setProperty('width', w + 'px', 'important');
                slide.style.setProperty('min-width', w + 'px', 'important');
                slide.style.setProperty('max-width', w + 'px', 'important');
                slide.style.setProperty('box-sizing', 'border-box', 'important');
                slide.style.setProperty('position', 'relative', 'important');
                slide.style.setProperty('overflow', 'hidden', 'important');
                slide.style.setProperty('margin', '0', 'important');
                slide.style.setProperty('padding', '0', 'important');
                slide.style.setProperty('opacity', '1', 'important');
                slide.style.setProperty('visibility', 'visible', 'important');
                slide.style.aspectRatio = '3 / 4';
                slide.style.height = 'auto';

                var media = slide.querySelector('video, img');
                if (media) {
                    media.style.setProperty('position', 'absolute', 'important');
                    media.style.setProperty('inset', '0', 'important');
                    media.style.setProperty('width', '100%', 'important');
                    media.style.setProperty('height', '100%', 'important');
                    media.style.setProperty('max-width', '100%', 'important');
                    media.style.setProperty('min-width', '0', 'important');
                    media.style.setProperty('object-fit', 'cover', 'important');
                    media.style.setProperty('display', 'block', 'important');
                    media.style.setProperty('opacity', '1', 'important');
                    media.style.setProperty('visibility', 'visible', 'important');
                    media.style.setProperty('transform', 'none', 'important');
                    media.style.setProperty('transition', 'none', 'important');
                }
                var video = slide.querySelector('video');
                if (video) {
                    video.style.pointerEvents = 'none';
                }
            });
        }

        function applyMobileLayout() {
            wrapper.classList.add('is-mobile-slider');
            slideW = measureW();

            wrapper.style.position = 'relative';
            wrapper.style.width = '100%';
            wrapper.style.maxWidth = '100vw';
            wrapper.style.overflow = 'hidden';
            wrapper.style.marginLeft = '0';
            wrapper.style.marginRight = '0';
            wrapper.style.boxSizing = 'border-box';

            // Full-bleed only when parent allows; keep measured width from layout box
            var parent = wrapper.parentElement;
            if (parent) {
                var pw = parent.getBoundingClientRect().width;
                if (pw > 0 && Math.abs(pw - (document.documentElement.clientWidth || 0)) < 4) {
                    wrapper.style.width = '100vw';
                    wrapper.style.marginLeft = 'calc(50% - 50vw)';
                    wrapper.style.marginRight = 'calc(50% - 50vw)';
                }
            }

            slideW = measureW();
            sizeSlides(slideW);

            var seeMore = wrapper.querySelector('.mont_see_more_container');
            if (seeMore) seeMore.style.display = 'none';

            if (prevBtn && prevBtn.parentElement) {
                prevBtn.parentElement.style.display = 'flex';
            }
            if (dotsWrap) {
                dotsWrap.style.display = 'flex';
            }

            ensureDots();
            // Warm every image once so swipe-2 is never empty after hard refresh
            slides.forEach(function (slide) {
                warmImg(slide.querySelector('img'));
            });
            goTo(index, false);
        }

        function clearMobileLayout() {
            wrapper.classList.remove('is-mobile-slider');
            wrapper.style.cssText = '';
            wrapper.style.removeProperty('--mont-slide-w');
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
            slideW = measureW();
            sizeSlides(slideW);

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

            [index - 1, index, index + 1, index + 2].forEach(function (si) {
                if (si < 0 || si >= slides.length) return;
                warmImg(slides[si].querySelector('img'));
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
        window.addEventListener('orientationchange', function () {
            window.setTimeout(onResize, 100);
        });

        onResize();
        window.setTimeout(onResize, 50);
        window.setTimeout(onResize, 300);
        // After fonts/images may have shifted layout
        window.addEventListener('load', onResize);
    });
})();
