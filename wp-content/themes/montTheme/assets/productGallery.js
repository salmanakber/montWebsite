jQuery(document).ready(function($) {
    let currentZoom = 1;
    const zoomStep = 0.2;

    // Parallax effect
    $('.mont_gallery_image-container, .mont_gallery_image-container-f').on('mouseenter', function() {
        $(this).find('.mont_gallery_main-image, .mont_gallery_main-image-f').css('transition', 'transform 0.2s ease-out');
    }).on('mousemove', function(e) {
        const $this = $(this);
        const $img = $this.find('.mont_gallery_main-image, .mont_gallery_main-image-f');
        const rect = this.getBoundingClientRect();
        const relX = e.clientX - rect.left;
        const relY = e.clientY - rect.top;
        const imgX = (relX - rect.width / 2) / rect.width * 5;
        const imgY = (relY - rect.height / 2) / rect.height * 5;
        $img.css('transform', `translateX(${imgX}px) translateY(${imgY}px) scale(1.05)`);
    }).on('mouseleave', function() {
        $(this).find('.mont_gallery_main-image, .mont_gallery_main-image-f').css({
            'transform': 'none',
            'transition': 'transform 0.2s ease-in'
        });
    });

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
        $('.mont_gallery_dot, .mont_gallery_dot-f').removeClass('active').eq(rowIndex).addClass('active');
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
        const src = $(this).data('gallerysrc');
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
});
