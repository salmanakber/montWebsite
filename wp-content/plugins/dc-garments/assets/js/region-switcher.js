(function ($) {
    'use strict';

    function openPanel($switcher) {
        // Close any other open panels first.
        $('.dc-region-switcher').each(function () {
            closePanel($(this));
        });

        $switcher.find('.dc-region-panel, .dc-region-overlay')
            .removeAttr('hidden')
            .attr('aria-hidden', 'false');
        $switcher.find('.dc-region-trigger').attr('aria-expanded', 'true');
        $('body').addClass('dc-region-open');
    }

    function closePanel($switcher) {
        $switcher.find('.dc-region-panel, .dc-region-overlay')
            .attr('hidden', true)
            .attr('aria-hidden', 'true');
        $switcher.find('.dc-region-trigger').attr('aria-expanded', 'false');
        $('body').removeClass('dc-region-open');
    }

    function switchRegion(region, $switcher) {
        if (typeof dc_region === 'undefined' || !dc_region.regions[region]) {
            return;
        }

        $switcher.addClass('dc-region-loading');

        $.post(dc_region.ajaxUrl, {
            action: 'dc_switch_region',
            nonce: dc_region.nonce,
            region: region,
            redirect_url: window.location.href
        }).done(function (response) {
            if (response && response.success && response.data && response.data.redirect) {
                window.location.href = response.data.redirect;
            } else {
                // Fallback: same page with query arg.
                var q = (dc_region.queryVar || 'dc_region') + '=' + encodeURIComponent(region);
                var url = window.location.href.split('#')[0];
                url = url.replace(new RegExp('([?&])' + (dc_region.queryVar || 'dc_region') + '=[^&]*', 'i'), '$1');
                url = url.replace(/[?&]$/, '');
                url += (url.indexOf('?') === -1 ? '?' : '&') + q;
                window.location.href = url;
            }
        }).fail(function () {
            $switcher.removeClass('dc-region-loading');
            var q = (dc_region.queryVar || 'dc_region') + '=' + encodeURIComponent(region);
            var url = window.location.href.split('#')[0];
            url += (url.indexOf('?') === -1 ? '?' : '&') + q;
            window.location.href = url;
        });
    }

    $(document).on('click', '.dc-region-trigger', function (e) {
        e.preventDefault();
        e.stopPropagation();
        var $switcher = $(this).closest('.dc-region-switcher');
        var isOpen = $(this).attr('aria-expanded') === 'true';
        if (isOpen) {
            closePanel($switcher);
        } else {
            openPanel($switcher);
        }
    });

    $(document).on('click', '.dc-region-close, .dc-region-overlay', function (e) {
        e.preventDefault();
        closePanel($(this).closest('.dc-region-switcher'));
    });

    $(document).on('click', '.dc-region-option', function (e) {
        e.preventDefault();
        var region = $(this).data('region');
        var $switcher = $(this).closest('.dc-region-switcher');

        if (String(region) === String($switcher.attr('data-current'))) {
            closePanel($switcher);
            return;
        }

        switchRegion(region, $switcher);
    });

    $(document).on('keydown', function (e) {
        if (e.key === 'Escape') {
            $('.dc-region-switcher').each(function () {
                closePanel($(this));
            });
        }
    });

})(jQuery);
