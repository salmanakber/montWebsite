(function ($) {
    'use strict';

    function openPanel($switcher) {
        $switcher.find('.dc-region-panel, .dc-region-overlay').removeAttr('hidden');
        $switcher.find('.dc-region-trigger').attr('aria-expanded', 'true');
        $('body').addClass('dc-region-open');
    }

    function closePanel($switcher) {
        $switcher.find('.dc-region-panel, .dc-region-overlay').attr('hidden', true);
        $switcher.find('.dc-region-trigger').attr('aria-expanded', 'false');
        $('body').removeClass('dc-region-open');
    }

    function updateTrigger($switcher, region) {
        if (!dc_region.regions[region]) return;
        var r = dc_region.regions[region];
        var label = r.label + ' • ' + r.display;
        $switcher.find('.dc-region-trigger-value').contents().filter(function () {
            return this.nodeType === 3;
        }).first().replaceWith(label + ' ');
        $switcher.find('.dc-region-panel-selected').text(label);
        $switcher.attr('data-current', region);
    }

    function switchRegion(region, $switcher) {
        if (!dc_region.regions[region]) return;

        $switcher.addClass('dc-region-loading');

        $.post(dc_region.ajaxUrl, {
            action: 'dc_switch_region',
            nonce: dc_region.nonce,
            region: region,
            redirect_url: window.location.href
        }).done(function (response) {
            if (response.success && response.data.redirect) {
                window.location.href = response.data.redirect;
            } else {
                window.location.reload();
            }
        }).fail(function () {
            $switcher.removeClass('dc-region-loading');
            window.location.reload();
        });
    }

    $(document).on('click', '.dc-region-trigger', function (e) {
        e.preventDefault();
        e.stopPropagation();
        var $switcher = $(this).closest('.dc-region-switcher');
        openPanel($switcher);
    });

    $(document).on('click', '.dc-region-close, .dc-region-overlay', function (e) {
        e.preventDefault();
        closePanel($(this).closest('.dc-region-switcher'));
    });

    $(document).on('click', '.dc-region-option', function (e) {
        e.preventDefault();
        var region = $(this).data('region');
        var $switcher = $(this).closest('.dc-region-switcher');

        if (region === $switcher.attr('data-current')) {
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
