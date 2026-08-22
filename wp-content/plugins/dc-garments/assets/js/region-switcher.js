(function ($) {
    'use strict';

    function deeplHandlesTranslation() {
        return !!(window.montDeepL && window.montDeepL.disableGoogle);
    }

    /**
     * Map region language codes to Google Translate codes.
     * Google uses "no" for Norwegian (not "nb").
     */
    function toGoogleLang(lang) {
        if (!lang || lang === 'en') {
            return 'en';
        }
        if (lang === 'nb') {
            return 'no';
        }
        return lang;
    }

    function clearGoogTransCookie() {
        var hostname = window.location.hostname;
        var expires = 'Thu, 01 Jan 1970 00:00:00 GMT';
        document.cookie = 'googtrans=; path=/; expires=' + expires;
        document.cookie = 'googtrans=; path=/; domain=' + hostname + '; expires=' + expires;
        document.cookie = 'googtrans=; path=/; domain=.' + hostname.replace(/^www\./, '') + '; expires=' + expires;
    }

    function setGoogTransCookie(lang) {
        var target = toGoogleLang(lang);
        clearGoogTransCookie();

        if (target === 'en') {
            return;
        }

        var value = '/auto/' + target;
        var hostname = window.location.hostname;
        document.cookie = 'googtrans=' + value + '; path=/';
        document.cookie = 'googtrans=' + value + '; path=/; domain=' + hostname;
        document.cookie = 'googtrans=' + value + '; path=/; domain=.' + hostname.replace(/^www\./, '');
    }

    function ensureTranslateElement() {
        if (!document.getElementById('google_translate_element2')) {
            var el = document.createElement('div');
            el.id = 'google_translate_element2';
            el.setAttribute('aria-hidden', 'true');
            document.body.appendChild(el);
        }
    }

    function loadGoogleTranslateLib() {
        if (window.gt_translate_script || window.googleTranslateElementInit2) {
            return;
        }

        window.googleTranslateElementInit2 = function () {
            if (window.google && google.translate) {
                new google.translate.TranslateElement({
                    pageLanguage: 'auto',
                    autoDisplay: false
                }, 'google_translate_element2');
            }
        };

        ensureTranslateElement();
        window.gt_translate_script = document.createElement('script');
        window.gt_translate_script.src = 'https://translate.google.com/translate_a/element.js?cb=googleTranslateElementInit2';
        document.body.appendChild(window.gt_translate_script);
    }

    /**
     * Prefer DeepL when configured; otherwise GTranslate / Google cookie.
     */
    function applySiteLanguage(lang) {
        if (deeplHandlesTranslation()) {
            clearGoogTransCookie();
            return;
        }

        var target = toGoogleLang(lang);

        setGoogTransCookie(target);

        if (typeof window.doGTranslate === 'function') {
            window.doGTranslate('auto|' + target);
            return;
        }

        if (target !== 'en') {
            loadGoogleTranslateLib();
        }
    }

    function syncLanguageForCurrentRegion() {
        if (deeplHandlesTranslation()) {
            clearGoogTransCookie();
            return;
        }

        if (typeof dc_region === 'undefined' || !dc_region.currentRegion || !dc_region.regions) {
            return;
        }

        var region = dc_region.regions[dc_region.currentRegion];
        if (!region || !region.lang) {
            return;
        }

        var target = toGoogleLang(region.lang);
        var match = document.cookie.match(/(?:^|; )googtrans=([^;]*)/);
        var current = match ? decodeURIComponent(match[1]).split('/')[2] : null;

        if (target === 'en') {
            if (current && current !== 'en') {
                clearGoogTransCookie();
            }
            return;
        }

        if (current !== target) {
            setGoogTransCookie(target);
        }

        if (typeof window.doGTranslate === 'function') {
            window.doGTranslate('auto|' + target);
        } else {
            loadGoogleTranslateLib();
        }
    }

    function openPanel($switcher) {
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
        if (!$('.dc-region-trigger[aria-expanded="true"]').length) {
            $('body').removeClass('dc-region-open');
        }
    }

    function switchRegion(region, $switcher) {
        if (typeof dc_region === 'undefined' || !dc_region.regions[region]) {
            return;
        }

        var lang = dc_region.regions[region].lang || 'en';
        applySiteLanguage(lang);

        $switcher.addClass('dc-region-loading');

        try {
            sessionStorage.setItem('mont_show_return_form', region);
        } catch (err) {
            // ignore
        }

        $.post(dc_region.ajaxUrl, {
            action: 'dc_switch_region',
            nonce: dc_region.nonce,
            region: region,
            redirect_url: window.location.href
        }).done(function (response) {
            if (response && response.success && response.data && response.data.redirect) {
                window.location.href = response.data.redirect;
            } else {
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

    $(document).on('click', function (e) {
        if ($(e.target).closest('.dc-region-switcher').length) {
            return;
        }
        $('.dc-region-switcher--desktop').each(function () {
            closePanel($(this));
        });
    });

    $(function () {
        setTimeout(syncLanguageForCurrentRegion, 400);
    });

})(jQuery);
