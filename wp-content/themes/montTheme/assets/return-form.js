(function ($) {
    'use strict';

    var cfg = window.montReturnForm || null;

    function getForm(region) {
        if (!cfg || !cfg.forms) {
            return null;
        }
        region = region || cfg.current || 'intl';
        if (cfg.forms[region]) {
            return cfg.forms[region];
        }
        return cfg.forms.intl || null;
    }

    function applyLinks(region) {
        var form = getForm(region);
        if (!form) {
            return;
        }

        $('[data-mont-return-form-link], [data-mont-return-form]').each(function () {
            var $el = $(this);
            $el.attr('href', form.url);
            $el.attr('data-mont-return-form', region);
            if ($el.is('a')) {
                $el.find('span').last().text(cfg.labels && cfg.labels.button ? cfg.labels.button : 'Return form');
            }
        });
    }

    function ensurePopup() {
        var $popup = $('#mont-return-form-popup');
        if ($popup.length) {
            return $popup;
        }

        var labels = (cfg && cfg.labels) ? cfg.labels : {};
        $popup = $(
            '<div id="mont-return-form-popup" class="mont-return-form-popup" hidden aria-hidden="true">' +
            '<div class="mont-return-form-popup__overlay" data-mont-return-close></div>' +
            '<div class="mont-return-form-popup__panel" role="dialog" aria-modal="true" aria-labelledby="mont-return-form-title">' +
            '<button type="button" class="mont-return-form-popup__close" data-mont-return-close aria-label="' + (labels.close || 'Close') + '">&times;</button>' +
            '<h3 id="mont-return-form-title">' + (labels.popupTitle || 'Return form for your region') + '</h3>' +
            '<p class="mont-return-form-popup__text">' + (labels.popupText || 'Download the return form for your selected region:') + '</p>' +
            '<p class="mont-return-form-popup__region"></p>' +
            '<a class="mont_return-form-btn mont_size-guide-btn mont-return-form-popup__download" href="#" target="_blank" rel="noopener noreferrer">' +
            (labels.download || 'Download PDF') + '</a>' +
            '</div></div>'
        );
        $('body').append($popup);
        return $popup;
    }

    function showPopup(region) {
        var form = getForm(region);
        if (!form) {
            return;
        }

        var $popup = ensurePopup();
        var regionLabel = '';
        if (window.dc_region && dc_region.regions && dc_region.regions[region]) {
            regionLabel = dc_region.regions[region].label || region;
        }

        $popup.find('.mont-return-form-popup__region').text(regionLabel);
        $popup.find('.mont-return-form-popup__download')
            .attr('href', form.url)
            .text((cfg.labels && cfg.labels.download ? cfg.labels.download : 'Download PDF') + ' — ' + form.label);

        $popup.removeAttr('hidden').attr('aria-hidden', 'false');
        $('body').addClass('mont-return-form-open');
    }

    function closePopup() {
        $('#mont-return-form-popup').attr('hidden', true).attr('aria-hidden', 'true');
        $('body').removeClass('mont-return-form-open');
    }

    $(document).on('click', '[data-mont-return-close]', function (e) {
        e.preventDefault();
        closePopup();
    });

    $(document).on('keydown', function (e) {
        if (e.key === 'Escape') {
            closePopup();
        }
    });

    $(function () {
        if (!cfg) {
            return;
        }

        applyLinks(cfg.current);

        var pending = sessionStorage.getItem('mont_show_return_form');
        if (pending) {
            sessionStorage.removeItem('mont_show_return_form');
            setTimeout(function () {
                showPopup(pending);
            }, 400);
        }
    });

    window.montReturnFormUI = {
        applyLinks: applyLinks,
        showPopup: showPopup,
        closePopup: closePopup
    };
})(jQuery);
