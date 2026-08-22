(function ($) {
    'use strict';

    var cfg = window.montReturnForm || null;

    function getForm(region) {
        if (!cfg || !cfg.forms) {
            return null;
        }
        region = region || cfg.current || '';
        if (region && cfg.forms[region]) {
            return cfg.forms[region];
        }
        return null;
    }

    function regionHasForm(region) {
        return !!getForm(region);
    }

    function toggleProductUi(region) {
        region = region || (cfg ? cfg.current : '');

        var $blocks = $('.mont_return-form-block, .mont_straight_line--b2b');
        var $buttons = $('[data-mont-return-open], [data-mont-return-form-link]');

        if (!regionHasForm(region)) {
            $blocks.addClass('mont_return-form-block--hidden').attr('hidden', true);
            $buttons.closest('.mont_return-form-block, .mont_pdp-doc-buttons').each(function () {
                var $wrap = $(this);
                if (!$wrap.find('[data-monte-size-guide]').length) {
                    $wrap.addClass('mont_return-form-block--hidden').attr('hidden', true);
                }
            });
            return;
        }

        $blocks.removeClass('mont_return-form-block--hidden').removeAttr('hidden');

        var form = getForm(region);
        var labels = (cfg && cfg.labels) ? cfg.labels : {};

        $('[data-mont-return-open]').each(function () {
            var $btn = $(this);
            $btn.attr('data-mont-return-form', region);
            $btn.attr('data-mont-return-url', form.url);
            $btn.find('span').last().text(labels.button || 'Return form');
            $btn.closest('.mont_return-form-block, .mont_pdp-doc-buttons, .mont_straight_line--b2b').removeClass('mont_return-form-block--hidden').removeAttr('hidden');
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
            '<button type="button" class="mont-return-form-popup__close" data-mont-return-close aria-label="' + escAttr(labels.close || 'Close') + '">&times;</button>' +
            '<h3 id="mont-return-form-title">' + escAttr(labels.popupTitle || '') + '</h3>' +
            '<p class="mont-return-form-popup__text">' + escAttr(labels.popupText || '') + '</p>' +
            '<div class="mont-return-form-popup__viewer-wrap">' +
            '<iframe class="mont-return-form-popup__viewer" title="' + escAttr(labels.viewerTitle || 'Return form') + '" src="about:blank"></iframe>' +
            '</div>' +
            '<div class="mont-return-form-popup__actions">' +
            '<a class="mont_return-form-btn mont_size-guide-btn mont-return-form-popup__download" href="#" target="_blank" rel="noopener noreferrer" download>' +
            escAttr(labels.download || 'Download PDF') + '</a>' +
            '</div>' +
            '</div></div>'
        );
        $('body').append($popup);
        return $popup;
    }

    function escAttr(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }

    function showPopup(region) {
        var form = getForm(region);
        if (!form || !form.url) {
            return;
        }

        var $popup = ensurePopup();
        var labels = (cfg && cfg.labels) ? cfg.labels : {};

        $popup.find('#mont-return-form-title').text(labels.popupTitle || '');
        $popup.find('.mont-return-form-popup__text').text(labels.popupText || '');
        $popup.find('.mont-return-form-popup__viewer').attr('src', form.url + '#view=FitH');
        $popup.find('.mont-return-form-popup__download')
            .attr('href', form.url)
            .text(labels.download || 'Download PDF');

        $popup.removeAttr('hidden').attr('aria-hidden', 'false');
        $('body').addClass('mont-return-form-open');
    }

    function closePopup() {
        var $popup = $('#mont-return-form-popup');
        $popup.attr('hidden', true).attr('aria-hidden', 'true');
        $popup.find('.mont-return-form-popup__viewer').attr('src', 'about:blank');
        $('body').removeClass('mont-return-form-open');
    }

    $(document).on('click', '[data-mont-return-close]', function (e) {
        e.preventDefault();
        closePopup();
    });

    $(document).on('click', '[data-mont-return-open]', function (e) {
        e.preventDefault();
        var region = $(this).attr('data-mont-return-form') || (cfg ? cfg.current : '');
        showPopup(region);
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

        toggleProductUi(cfg.current);

        var pending = sessionStorage.getItem('mont_show_return_form');
        if (pending) {
            sessionStorage.removeItem('mont_show_return_form');
            if (regionHasForm(pending)) {
                setTimeout(function () {
                    showPopup(pending);
                }, 400);
            }
        }
    });

    window.montReturnFormUI = {
        regionHasForm: regionHasForm,
        toggleProductUi: toggleProductUi,
        showPopup: showPopup,
        closePopup: closePopup
    };
})(jQuery);
