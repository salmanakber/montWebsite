jQuery(document).ready(function($) {
    let currentZoom = 1;
    const zoomStep = 0.2;

    /**
     * Hover zoom — B2B grid + B2C unified gallery (desktop / devices with hover)
     */
    function bindHoverZoom(selector) {
        $(document).on('mouseenter', selector, function () {
            if (window.matchMedia('(hover: hover) and (min-width: 1025px)').matches === false) {
                return;
            }
            $(this).addClass('is-zooming');
            const $img = $(this).find('img').first();
            $img.css('transition', 'transform 0.12s ease-out');
        }).on('mousemove', selector, function (e) {
            if (window.matchMedia('(hover: hover) and (min-width: 1025px)').matches === false) {
                return;
            }
            const rect = this.getBoundingClientRect();
            const x = ((e.clientX - rect.left) / rect.width) * 100;
            const y = ((e.clientY - rect.top) / rect.height) * 100;
            const $img = $(this).find('img').first();
            $img.css({
                'transform-origin': x + '% ' + y + '%',
                'transform': 'scale(1.4)'
            });
        }).on('mouseleave', selector, function () {
            $(this).removeClass('is-zooming');
            $(this).find('img').first().css({
                'transform': 'scale(1)',
                'transform-origin': 'center center',
                'transition': 'transform 0.3s ease'
            });
        });
    }

    bindHoverZoom('.mont_gallery_image-container, .mont_gallery_image-container-f');
    bindHoverZoom('.mont_gallery_item:not(.video-trigger)');

    // Update dot visibility
    function updateDotVisibility() {
        const containerHeight = $('.mont_gallery_wrapper, .mont_gallery_wrapper-f').height();
        const imageContainers = $('.mont_gallery_image-container, .mont_gallery_image-container-f');
        const dots = $('.mont_gallery_dot, .mont_gallery_dot-f');

        let visibleRowIndex = -1;

        imageContainers.each(function(index) {
            const isFullWidth = $(this).hasClass('full-width');
            const rowIndex = isFullWidth ? Math.floor(index / 2) : Math.floor(index / 2);
            const imageTop = $(this).position().top;
            const imageBottom = imageTop + $(this).height();

            if (imageTop < containerHeight && imageBottom > 0) {
                dots.eq(rowIndex).show();
                if (visibleRowIndex === -1) visibleRowIndex = rowIndex;
            }
        });

        if (visibleRowIndex !== -1) {
            dots.removeClass('active');
            dots.eq(visibleRowIndex).addClass('active');
        }
    }

    updateDotVisibility();

    // Scroll events
    $('.mont_gallery_image-grid, .mont_gallery_image-grid-f').on('scroll', updateDotVisibility);

    // Dot hover scroll
    let scrollInterval;
    $('.mont_gallery_navigation-dots, .mont_gallery_navigation-dots-f').on('mouseenter', function() {
        scrollInterval = setInterval(function() {
            const scrollAmount = 1;
            $('.mont_gallery_image-grid, .mont_gallery_image-grid-f').scrollTop(function(i, val) {
                return val + scrollAmount;
            });
            updateDotVisibility();
        }, 50);
    }).on('mouseleave', function() {
        clearInterval(scrollInterval);
    });

    // Dot click scroll
    $('.mont_gallery_dot, .mont_gallery_dot-f').click(function() {
        const rowIndex = $(this).data('row');
        const targetRow = $('.mont_gallery_image-container, .mont_gallery_image-container-f').eq(rowIndex * 2);
        $('.mont_gallery_image-grid, .mont_gallery_image-grid-f').animate({
            scrollTop: targetRow.position().top
        }, 500);
    });

    // Lightbox open
    $('.mont_gallery_main-image, .mont_gallery_main-image-f').click(function() {
        const src = $(this).attr('data-gallerysrc');
        const index = $(this).data('index');
        const rowIndex = Math.floor(index / 2);

        $('.mont_gallery_lightbox-image, .mont_gallery_lightbox-image-f').attr('src', src);
        $('.mont_gallery_thumbnail, .mont_gallery_thumbnail-f').removeClass('active').eq(index).addClass('active');
        $('.mont_gallery_dot, .mont_gallery_dot-f').removeClass('active').eq(index).addClass('active');
        $('.mont_gallery_lightbox, .mont_gallery_lightbox-f').fadeIn();
        $('body').css('overflow', 'hidden');
    });

    // Lightbox close
    $('.mont_gallery_close-btn, .mont_gallery_close-btn-f').click(function() {
        $('.mont_gallery_lightbox, .mont_gallery_lightbox-f').fadeOut();
        $('body').css('overflow', 'auto');
        resetZoom();
    });

    // Thumbnail click
    $('.mont_gallery_thumbnail, .mont_gallery_thumbnail-f').click(function() {
        const src = $(this).data('gallerysrc') || $(this).attr('src');
        const index = $(this).data('index');
        const rowIndex = Math.floor(index / 2);
        $('.mont_gallery_lightbox-image, .mont_gallery_lightbox-image-f').attr('src', src);
        $('.mont_gallery_thumbnail, .mont_gallery_thumbnail-f').removeClass('active');
        $(this).addClass('active');
        $('.mont_gallery_dot, .mont_gallery_dot-f').removeClass('active').eq(rowIndex).addClass('active');
    });

    // Zoom
    $('.mont_gallery_zoom-in, .mont_gallery_zoom-in-f').click(function() {
        if (currentZoom < 3) {
            currentZoom += zoomStep;
            updateZoom();
        }
    });

    $('.mont_gallery_zoom-out, .mont_gallery_zoom-out-f').click(function() {
        if (currentZoom > 1) {
            currentZoom -= zoomStep;
            updateZoom();
        }
    });

    $('.mont_gallery_restore, .mont_gallery_restore-f').click(function() {
        resetZoom();
    });

    function updateZoom() {
        $('.mont_gallery_lightbox-image, .mont_gallery_lightbox-image-f').css('transform', `scale(${currentZoom})`);
    }

    function resetZoom() {
        currentZoom = 1;
        updateZoom();
    }

    /**
     * B2B mobile gallery — thin chevron arrows + single-slide scroll
     */
    function initB2bMobileGallery() {
        var $wrap = $('.b2b-pdp .mont_gallery_wrapper').first();
        if (!$wrap.length) return;

        var $grid = $wrap.find('.mont_gallery_image-grid').first();
        var $items = $grid.find('.mont_gallery_image-container');
        if ($items.length < 2) return;

        if (!$wrap.find('.mont_gallery_nav').length) {
            $wrap.append(
                '<div class="mont_gallery_nav" aria-label="Gallery navigation">' +
                '<button type="button" class="mont_gallery_nav_btn mont_gallery_prev" aria-label="Previous">' +
                '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M15 5L8 12L15 19" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>' +
                '</button>' +
                '<button type="button" class="mont_gallery_nav_btn mont_gallery_next" aria-label="Next">' +
                '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M9 5L16 12L9 19" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>' +
                '</button>' +
                '</div>'
            );
        }

        var index = 0;
        var $prev = $wrap.find('.mont_gallery_prev');
        var $next = $wrap.find('.mont_gallery_next');

        function isMobile() {
            return window.matchMedia('(max-width: 991px)').matches;
        }

        function goTo(i) {
            if (!isMobile()) return;
            index = Math.max(0, Math.min(i, $items.length - 1));
            var el = $items.get(index);
            if (el && el.scrollIntoView) {
                el.scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' });
            }
            $prev.prop('disabled', index <= 0);
            $next.prop('disabled', index >= $items.length - 1);
        }

        $prev.off('click.b2bgallery').on('click.b2bgallery', function (e) {
            e.preventDefault();
            e.stopPropagation();
            goTo(index - 1);
        });
        $next.off('click.b2bgallery').on('click.b2bgallery', function (e) {
            e.preventDefault();
            e.stopPropagation();
            goTo(index + 1);
        });

        $grid.off('scroll.b2bgallery').on('scroll.b2bgallery', function () {
            if (!isMobile()) return;
            var scrollLeft = $grid[0].scrollLeft;
            var width = $grid[0].clientWidth || 1;
            index = Math.round(scrollLeft / width);
            $prev.prop('disabled', index <= 0);
            $next.prop('disabled', index >= $items.length - 1);
        });

        goTo(0);
    }

    initB2bMobileGallery();
    $(window).on('resize', function () {
        initB2bMobileGallery();
    });
});
