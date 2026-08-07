jQuery(document).ready(function ($) {

    var montActiveRequest = null;
    var montChartCache = {};
    var montSizeListCache = {};
    var drawerPendingFitSlug = '';
    var drawerPendingSizeSlug = '';
    var montDiagramPrefetch = {};
    var montPrefetchTimer = null;
    /** Client-side array/map: fit___size => { measurement: {thumb, full} } */
    var montDiagramCache = {};
    var montDiagramWarmStarted = false;

    function montImagesUsable(images) {
        if (!images || typeof images !== 'object') return false;
        var keys = ['shirt_length', 'sleeve_length', 'half_chest', 'half_waist', 'half_bottom', 'shoulder'];
        for (var i = 0; i < keys.length; i++) {
            var e = images[keys[i]];
            if (!e) continue;
            if (typeof e === 'string' && e) return true;
            if ((e.thumb && String(e.thumb)) || (e.full && String(e.full))) return true;
        }
        return false;
    }

    function montFitSlugAliases(fit) {
        var f = String(fit || '').toLowerCase();
        var groups = [
            ['slim', 'slimfit', 'slim-fit'],
            ['modern', 'modern-fit', 'regular', 'vanlig'],
            ['contemporary', 'contemporary-fit']
        ];
        for (var i = 0; i < groups.length; i++) {
            if (groups[i].indexOf(f) !== -1) return groups[i];
        }
        return f ? [f] : [];
    }

    function montDiagramKeyCandidates(key) {
        var parts = String(key || '').split('___');
        if (parts.length < 2) return key ? [String(key)] : [];
        var fit = parts[0];
        var size = parts.slice(1).join('___');
        var sizeCode = (String(size).match(/\d+/) || [size])[0];
        var out = [];
        var seen = {};
        function push(k) {
            if (!k || seen[k]) return;
            seen[k] = 1;
            out.push(k);
        }
        montFitSlugAliases(fit).forEach(function (f) {
            push(f + '___' + size);
            push(f + '___' + sizeCode);
            push(String(f).toLowerCase() + '___' + String(size).toLowerCase());
            push(String(f).toLowerCase() + '___' + String(sizeCode).toLowerCase());
        });
        push(key);
        push(String(key).toLowerCase());
        return out;
    }

    function montLookupDiagramMap(map, key) {
        if (!map || !key) return null;
        var candidates = montDiagramKeyCandidates(key);
        var i, c;
        for (i = 0; i < candidates.length; i++) {
            c = candidates[i];
            if (map[c] && montImagesUsable(map[c])) return map[c];
        }
        // Last resort: case-insensitive scan.
        var lk = String(key).toLowerCase();
        for (var k in map) {
            if (!Object.prototype.hasOwnProperty.call(map, k)) continue;
            if (String(k).toLowerCase() === lk && montImagesUsable(map[k])) return map[k];
        }
        return null;
    }

    /** Resolve diagrams from client cache, then page-embedded ajaxurl.diagrams. */
    function diagramsFromPage(key) {
        var cached = montLookupDiagramMap(montDiagramCache, key);
        if (cached) return cached;
        var map = (typeof ajaxurl !== 'undefined' && ajaxurl.diagrams) ? ajaxurl.diagrams : null;
        var found = montLookupDiagramMap(map, key);
        if (found) {
            montDiagramCache[key] = found;
            return found;
        }
        return null;
    }

    /**
     * On product page load: copy all backend diagram URLs into montDiagramCache
     * and download every thumb into the browser cache so size switches are instant.
     */
    function montSeedAndWarmAllDiagrams() {
        if (typeof ajaxurl === 'undefined' || !ajaxurl.diagrams) return;
        var map = ajaxurl.diagrams;
        var mapKeys = Object.keys(map);
        if (!mapKeys.length) return;
        if (montDiagramWarmStarted) return;

        montDiagramWarmStarted = true;
        var fitSizes = ajaxurl.fitSizes || {};
        var urlSet = {};

        function store(key, images) {
            if (!key || !montImagesUsable(images)) return;
            montDiagramCache[key] = images;
            if (!montChartCache[key]) montChartCache[key] = {};
            montChartCache[key].images = images;
            Object.keys(images).forEach(function (mk) {
                var e = images[mk];
                var url = typeof e === 'string' ? e : ((e && (e.thumb || e.full)) || '');
                if (url) urlSet[url] = 1;
            });
        }

        mapKeys.forEach(function (key) {
            store(key, map[key]);
        });

        // Also index under this product's exact WC fit/size slugs.
        Object.keys(fitSizes).forEach(function (fit) {
            (fitSizes[fit] || []).forEach(function (size) {
                var key = fit + '___' + size;
                if (montDiagramCache[key]) return;
                var imgs = montLookupDiagramMap(map, key);
                if (imgs) store(key, imgs);
            });
        });

        // Download all unique thumb/full URLs into browser HTTP cache.
        var urls = Object.keys(urlSet);
        var i = 0;
        function pumpBatch() {
            var n = 0;
            while (i < urls.length && n < 10) {
                (function (src) {
                    var img = new Image();
                    img.decoding = 'async';
                    img.src = src;
                })(urls[i++]);
                n++;
            }
            if (i < urls.length) {
                setTimeout(pumpBatch, 16);
            }
        }
        if (urls.length) {
            pumpBatch();
        }
    }

    // Warm immediately when the product page boots.
    montSeedAndWarmAllDiagrams();

    function montClearLoaders() {
        $('.mont_loading').each(function () {
            $(this).removeClass('mont_loading');
            $(this).children('.mont_loading-label').remove();
        });
    }

    function montShowLoader($el, message) {
        if (!$el || !$el.length) return;
        montClearLoaders();
        $el.addClass('mont_loading');
        if (!$el.children('.mont_loading-label').length) {
            $el.append('<span class="mont_loading-label"></span>');
        }
        $el.children('.mont_loading-label').text(message || 'Laster…');
    }

    function montHideLoader($el) {
        if ($el && $el.length) {
            $el.removeClass('mont_loading');
            $el.children('.mont_loading-label').remove();
            return;
        }
        montClearLoaders();
    }

    function montAbortActive() {
        if (montActiveRequest && montActiveRequest.readyState !== 4) {
            montActiveRequest.abort();
        }
        montActiveRequest = null;
    }

    function montFitSizeReady() {
        var fitOk = $('.pa_body-fit-option').has('input.pa_body-fit-checkbox:checked').length > 0
            || $('.pa_body-fit-checkbox:checked').length > 0;
        var sizeOk = $('.pa_size-option').has('input.pa_size-checkbox:checked').length > 0
            || $('.pa_size-checkbox:checked').length > 0;
        return !!(fitOk && sizeOk);
    }

    function montUpdateFitSizeSummary() {
        var fitLabel = '';
        var sizeLabel = '';
        var $fit = $('.pa_body-fit-option').has('input.pa_body-fit-checkbox:checked').first();
        if ($fit.length) fitLabel = $.trim($fit.find('.tobeSelected').text());
        var $size = $('.pa_size-option').has('input.pa_size-checkbox:checked').first();
        if ($size.length) sizeLabel = $.trim($size.find('.tobeSelected').text());
        var summary = '';
        if (fitLabel && sizeLabel) summary = fitLabel + ' · ' + sizeLabel;
        else if (fitLabel) summary = fitLabel;
        $('.mont-fit-size-summary, .pa_body-fit .dpName').html(summary ? '<b>' + summary + '</b>' : '');
        if (sizeLabel) {
            $('.pa_size .dpName').html('<b>' + sizeLabel + '</b>');
        }
        montUpdateStickyCta();
    }

    function montUpdateStickyCta() {
        var $bar = $('#mont-mobile-sticky-cta');
        if (!$bar.length) return;
        var ready = montFitSizeReady();
        $bar.toggleClass('is-ready', ready);
        $bar.find('.mont-mobile-sticky-cta__action.is-cart').attr('aria-disabled', ready ? 'false' : 'true');
        if (ready) {
            $('.pa_body-fit, .to-be-open-pa_body-fit .mont_variation-group').css({
                'background': '#b0b0b0',
                'color': 'white'
            });
        }
    }

    function montShowSizeSkeleton() {
        var $grid = $('#mont-drawer-sizes');
        var $hint = $('#mont-drawer-size-hint');
        $hint.prop('hidden', true);
        $grid.removeAttr('hidden').empty().addClass('is-loading');
        for (var i = 0; i < 6; i++) {
            $grid.append('<span class="mont-drawer-size-skeleton" aria-hidden="true"></span>');
        }
        $('#mont-fit-size-continue').prop('disabled', true);
    }

    function montOpenFitSizeDrawer() {
        var $drawer = $('#mont-fit-size-drawer');
        if (!$drawer.length) return;
        montBuildDrawerFits();
        $drawer.addClass('is-open').attr('aria-hidden', 'false');
        $('body').addClass('mont-fit-size-drawer-open');
        var $checkedFit = $('.pa_body-fit-option').has('input.pa_body-fit-checkbox:checked').first();
        if ($checkedFit.length) {
            drawerPendingFitSlug = String($checkedFit.data('slug') || '');
            montHighlightDrawerFit(drawerPendingFitSlug);
            montLoadSizesForFit($checkedFit, true);
        } else {
            drawerPendingFitSlug = '';
            drawerPendingSizeSlug = '';
            $('#mont-drawer-sizes').removeClass('is-loading').attr('hidden', true).empty();
            $('#mont-drawer-size-hint').text('Velg passform først').prop('hidden', false);
            $('#mont-fit-size-continue').prop('disabled', true);
        }
        var $checkedSize = $('.pa_size-option').has('input.pa_size-checkbox:checked').first();
        if ($checkedSize.length) {
            drawerPendingSizeSlug = String($checkedSize.data('slug') || '');
        }
    }
    window.montOpenFitSizeDrawer = montOpenFitSizeDrawer;

    function montCloseFitSizeDrawer() {
        $('#mont-fit-size-drawer').removeClass('is-open').attr('aria-hidden', 'true');
        $('body').removeClass('mont-fit-size-drawer-open');
    }

    function montBuildDrawerFits() {
        var $wrap = $('#mont-drawer-fits');
        if (!$wrap.length) return;
        $wrap.empty();
        $('.pa_body-fit-option').each(function () {
            var $li = $(this);
            var slug = String($li.data('slug') || '');
            var label = $.trim($li.find('.tobeSelected').text());
            var checked = $li.find('input.pa_body-fit-checkbox').is(':checked')
                || slug === drawerPendingFitSlug;
            var $btn = $('<button type="button" class="mont-drawer-fit-option' + (checked ? ' is-selected' : '') + '" role="radio" aria-checked="' + (checked ? 'true' : 'false') + '"></button>');
            $btn.attr('data-slug', slug);
            $btn.append(
                '<span class="mont-drawer-fit-option__check" aria-hidden="true">' +
                    '<svg class="mont-drawer-fit-option__tick" viewBox="0 0 16 16" width="12" height="12" fill="none">' +
                        '<path d="M3.5 8.2L6.4 11.1L12.5 4.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>' +
                    '</svg>' +
                '</span>'
            );
            $btn.append($('<span class="mont-drawer-fit-option__label"></span>').text(label));
            $wrap.append($btn);
        });
    }

    function montHighlightDrawerFit(slug) {
        $('#mont-drawer-fits .mont-drawer-fit-option').each(function () {
            var on = String($(this).data('slug')) === String(slug);
            $(this).toggleClass('is-selected', on).attr('aria-checked', on ? 'true' : 'false');
        });
    }

    function montRenderDrawerSizes(validSizes) {
        var $grid = $('#mont-drawer-sizes');
        var $hint = $('#mont-drawer-size-hint');
        $grid.removeClass('is-loading').empty();
        var visible = [];
        $('.pa_size-option').each(function () {
            var slug = String($(this).data('slug') || '');
            if (validSizes.indexOf(slug) === -1) return;
            visible.push({
                slug: slug,
                label: $.trim($(this).find('.tobeSelected').text())
            });
        });
        visible.sort(function (a, b) {
            var numA = parseFloat(a.slug);
            var numB = parseFloat(b.slug);
            if (!isNaN(numA) && !isNaN(numB)) return numA - numB;
            if (!isNaN(numA)) return -1;
            if (!isNaN(numB)) return 1;
            return String(a.slug).localeCompare(String(b.slug));
        });
        if (!visible.length) {
            $hint.text('Ingen størrelser for denne passformen').prop('hidden', false);
            $grid.attr('hidden', true);
            $('#mont-fit-size-continue').prop('disabled', true);
            return;
        }
        $hint.prop('hidden', true);
        $grid.removeAttr('hidden');
        visible.forEach(function (item) {
            var selected = String(item.slug) === String(drawerPendingSizeSlug);
            var $btn = $('<button type="button" class="mont-drawer-size-option' + (selected ? ' is-selected' : '') + '"></button>');
            $btn.attr('data-slug', item.slug).text(item.label);
            $grid.append($btn);
        });
        $('#mont-fit-size-continue').prop('disabled', !drawerPendingSizeSlug || validSizes.indexOf(String(drawerPendingSizeSlug)) === -1);
    }

    function sizesFromLocalMap(fitSlug) {
        var map = (typeof ajaxurl !== 'undefined' && ajaxurl.fitSizes) ? ajaxurl.fitSizes : null;
        if (!map || typeof map !== 'object') return null;
        if (Array.isArray(map[fitSlug])) return map[fitSlug].map(String);
        var needle = String(fitSlug).toLowerCase();
        var keys = Object.keys(map);
        for (var i = 0; i < keys.length; i++) {
            if (String(keys[i]).toLowerCase() === needle && Array.isArray(map[keys[i]])) {
                return map[keys[i]].map(String);
            }
        }
        return null;
    }

    function applyValidSizes(validSizes, openAccordion) {
        var $items = $('.pa_size .mont_option-item');
        $items.each(function () {
            var listSlug = $(this).data('slug').toString();
            if (validSizes.indexOf(listSlug) === -1) {
                $(this).hide();
            } else {
                $(this).show();
            }
        });
        var sortedItems = $items.filter(':visible').sort(function (a, b) {
            var aSlug = $(a).data('slug');
            var bSlug = $(b).data('slug');
            var numA = parseFloat(aSlug);
            var numB = parseFloat(bSlug);
            if (!isNaN(numA) && !isNaN(numB)) return numA - numB;
            if (!isNaN(numA)) return -1;
            if (!isNaN(numB)) return 1;
            return String(aSlug).localeCompare(String(bSlug));
        });
        $('.to-be-open-pa_size').find('.mont_option-list').append(sortedItems);
        $('.pa_body-fit').find('.mont_option-list').removeClass('mont_open');
        $('.pa_body-fit').removeClass('mont_open');
        if (openAccordion) {
            $('.pa_size').find('.mont_option-list').addClass('mont_open');
            $('.pa_size').addClass('mont_open');
        }
        if ($('#mont-fit-size-drawer').hasClass('is-open')) {
            if (drawerPendingSizeSlug && validSizes.indexOf(String(drawerPendingSizeSlug)) === -1) {
                drawerPendingSizeSlug = '';
            }
            montRenderDrawerSizes(validSizes);
        }

        // Warm diagram thumbs for this fit before the customer picks a size.
        var $fitChecked = $('.pa_body-fit-option').has('input.pa_body-fit-checkbox:checked').first();
        var fitSlug = $fitChecked.length ? String($fitChecked.data('slug') || '') : '';
        if (fitSlug && validSizes && validSizes.length) {
            montPrefetchFitDiagrams(fitSlug, validSizes);
        }
    }

    function montPrefetchFitDiagrams(fitSlug, sizeSlugs) {
        if (!fitSlug || !sizeSlugs || !sizeSlugs.length) return;
        if (montPrefetchTimer) {
            clearTimeout(montPrefetchTimer);
            montPrefetchTimer = null;
        }

        var queue = sizeSlugs.map(String).filter(Boolean);
        var i = 0;

        function pump() {
            if (i >= queue.length) return;
            var sizeSlug = queue[i++];
            var key = fitSlug + '___' + sizeSlug;
            if (montDiagramPrefetch[key] || (montChartCache[key] && montImagesUsable(montChartCache[key].images))) {
                montPrefetchTimer = setTimeout(pump, 40);
                return;
            }
            // Already on the page — seed cache, skip AJAX.
            var embedded = diagramsFromPage(key);
            if (embedded) {
                if (!montChartCache[key]) montChartCache[key] = {};
                montChartCache[key].images = embedded;
                montDiagramPrefetch[key] = true;
                montPrefetchTimer = setTimeout(pump, 40);
                return;
            }
            montDiagramPrefetch[key] = true;
            $.ajax({
                url: ajaxurl.url,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'mont_get_size_diagrams',
                    key: key
                },
                success: function (res) {
                    var images = res && res.success && res.data ? res.data.images : null;
                    if (montImagesUsable(images)) {
                        if (!montChartCache[key]) montChartCache[key] = {};
                        montChartCache[key].images = images;
                    }
                },
                complete: function () {
                    montPrefetchTimer = setTimeout(pump, 90);
                }
            });
        }

        pump();
    }

    function montLoadSizesForFit($fitOption, forDrawer) {
        var slug = String($fitOption.data('slug') || '');
        var selectedValue = $fitOption.find('.tobeSelected').text();
        var attributes = $('.pa_body-fit .mont_variation-header').data('attribute-key') || 'pa_body-fit';
        var pid = $fitOption.data('id');
        var $sizeGroup = forDrawer ? $('#mont-drawer-sizes') : $('.pa_size').first();
        var cacheKey = String(pid) + '|' + String(attributes) + '|' + String(slug);

        $('.pa_body-fit-option').find('.mont_checkbox_select').prop('checked', false).val('');
        $fitOption.find('.mont_checkbox_select').prop('checked', true).val(slug);
        $('.pa_body-fit').css({ background: '#b0b0b0', color: 'white' });

        drawerPendingFitSlug = slug;
        montHighlightDrawerFit(slug);

        if (!attributes) return;

        if (montSizeListCache[cacheKey]) {
            applyValidSizes(montSizeListCache[cacheKey], !forDrawer);
            return;
        }

        var localSizes = sizesFromLocalMap(slug);
        if (localSizes && localSizes.length) {
            montSizeListCache[cacheKey] = localSizes;
            applyValidSizes(localSizes, !forDrawer);
            return;
        }

        montAbortActive();
        if (forDrawer) {
            montShowSizeSkeleton();
        } else {
            montShowLoader($sizeGroup, 'Oppdaterer størrelser…');
        }

        montActiveRequest = $.ajax({
            url: ajaxurl.url,
            type: 'POST',
            data: {
                action: 'get_variation_details',
                attributes: attributes,
                product_id: pid,
                selected: selectedValue,
                slugValue: slug
            },
            success: function (response) {
                montHideLoader($sizeGroup);
                if (response.success) {
                    var validSizes = (response.data || []).map(function (item) {
                        if (typeof item === 'string') return item.toString();
                        if (item && item.attributes && item.attributes.attribute_pa_size != null) {
                            return item.attributes.attribute_pa_size.toString();
                        }
                        return '';
                    }).filter(Boolean);
                    montSizeListCache[cacheKey] = validSizes;
                    applyValidSizes(validSizes, !forDrawer);
                } else if (forDrawer) {
                    $('#mont-drawer-sizes').removeClass('is-loading').attr('hidden', true).empty();
                    $('#mont-drawer-size-hint').text('Kunne ikke laste størrelser').prop('hidden', false);
                }
            },
            error: function (xhr, status) {
                if (status !== 'abort') {
                    montHideLoader($sizeGroup);
                    if (forDrawer) {
                        $('#mont-drawer-sizes').removeClass('is-loading').attr('hidden', true).empty();
                        $('#mont-drawer-size-hint').text('Kunne ikke laste størrelser').prop('hidden', false);
                    }
                }
            },
            complete: function () {
                montActiveRequest = null;
            }
        });
    }

    // Keep legacy list clicks working (source list is hidden but may still fire).
    $(document).on('click', '.pa_body-fit-option', function (e) {
        if ($(e.target).closest('#mont-fit-size-drawer').length) return;
        montLoadSizesForFit($(this), $('#mont-fit-size-drawer').hasClass('is-open'));
        montUpdateFitSizeSummary();
    });

    $(document).on('click', '[data-open-fit-size-drawer]', function (e) {
        e.preventDefault();
        e.stopPropagation();
        montOpenFitSizeDrawer();
    });

    $(document).on('keydown', '[data-open-fit-size-drawer]', function (e) {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            montOpenFitSizeDrawer();
        }
    });

    $(document).on('click', '[data-close-fit-size-drawer]', function (e) {
        e.preventDefault();
        montCloseFitSizeDrawer();
    });

    $(document).on('keydown', function (e) {
        if (e.key === 'Escape' && $('#mont-fit-size-drawer').hasClass('is-open')) {
            montCloseFitSizeDrawer();
        }
    });

    $(document).on('click', '.mont-drawer-fit-option', function () {
        var slug = String($(this).data('slug') || '');
        var $opt = $('.pa_body-fit-option').filter(function () {
            return String($(this).data('slug')) === slug;
        }).first();
        if (!$opt.length) return;
        drawerPendingSizeSlug = '';
        $('.pa_size-option').find('.mont_checkbox_select').prop('checked', false).val('');
        $('#mont-fit-size-continue').prop('disabled', true);
        montLoadSizesForFit($opt, true);
        montUpdateFitSizeSummary();
    });

    $(document).on('click', '.mont-drawer-size-option', function () {
        drawerPendingSizeSlug = String($(this).data('slug') || '');
        $('#mont-drawer-sizes .mont-drawer-size-option').removeClass('is-selected');
        $(this).addClass('is-selected');
        $('#mont-fit-size-continue').prop('disabled', !drawerPendingSizeSlug);
    });

    $(document).on('click', '#mont-fit-size-continue', function () {
        if (!drawerPendingFitSlug || !drawerPendingSizeSlug) return;
        var $fitOpt = $('.pa_body-fit-option').filter(function () {
            return String($(this).data('slug')) === String(drawerPendingFitSlug);
        }).first();
        var $sizeOpt = $('.pa_size-option').filter(function () {
            return String($(this).data('slug')) === String(drawerPendingSizeSlug);
        }).first();
        if ($fitOpt.length) {
            $('.pa_body-fit-option').find('.mont_checkbox_select').prop('checked', false).val('');
            $fitOpt.find('.mont_checkbox_select').prop('checked', true).val(drawerPendingFitSlug);
        }
        if ($sizeOpt.length) {
            $sizeOpt.trigger('click');
        }
        montUpdateFitSizeSummary();
        montCloseFitSizeDrawer();
    });

    // Unhide sticky CTA on mobile widths.
    function montSyncStickyVisibility() {
        var $bar = $('#mont-mobile-sticky-cta');
        if (!$bar.length) return;
        if (window.matchMedia('(max-width: 1024px)').matches) {
            $bar.prop('hidden', false);
            $('body').addClass('has-mont-mobile-sticky');
        } else {
            $bar.prop('hidden', true);
            $('body').removeClass('has-mont-mobile-sticky');
        }
        montUpdateStickyCta();
    }
    montSyncStickyVisibility();
    $(window).on('resize', montSyncStickyVisibility);

    $('.collar-option input[type="radio"]').each(function () {
        if ($(this).is(':checked')) {
            $(this).closest('.collar-option').addClass('selected');
        }
    });

    $('.collar-option input[type="radio"]').change(function () {
        var $group = $(this).closest('.collar-options');
        $group.find('.collar-option').removeClass('selected');
        $(this).closest('.collar-option').addClass('selected');
    });

    $('.velg-snipp .collar-option').click(function (e) {
        if (!$(e.target).is('input')) {
            $(this).find('input[type="radio"]').prop('checked', true).trigger('change');
        }
        $('.velg-snipp').find(".mont_option-list").removeClass('mont_open');
        $('.velg-snipp').find(".mont_variation-group").removeClass('mont_open');
        $('.velg-mansjetter').find(".mont_option-list").addClass('mont_open');
        $('.velg-mansjetter').find(".mont_variation-group").addClass('mont_open');
    });

    $('.cup-option-click').click(function (e) {
        if (!$(e.target).is('input')) {
            $(this).find('input[type="radio"]').prop('checked', true).trigger('change');
        }
        $('.velg-mansjetter').find(".mont_option-list").removeClass('mont_open');
        $('.velg-mansjetter').find(".mont_variation-group").removeClass('mont_open');
        $('.skreddersydd').find(".mont_option-list").addClass('mont_open');
        $('.skreddersydd').find(".mont_variation-group").addClass('mont_open');
    });

    $(document).on("click", ".pa_size-option", function () {
        var $sizeItem = $(this);
        var sizeSlug = $sizeItem.data("slug");
        var bodyCheck = "";
        var $tailorGroup = $('.skreddersydd .mont_variation-group').first();
        if (!$tailorGroup.length) {
            $tailorGroup = $('.skreddersydd').first();
        }

        $('.pa_size-option').find('.mont_checkbox_select').prop('checked', false).val('');
        $sizeItem.find('.mont_checkbox_select').prop('checked', true).val(sizeSlug);
        $('.pa_size').css({ background: '#b0b0b0', color: 'white' });
        montUpdateFitSizeSummary();

        var $fitChecked = $('.pa_body-fit-option').has('input.pa_body-fit-checkbox:checked').first();
        if ($fitChecked.length) {
            bodyCheck = $fitChecked.data('slug') || $fitChecked.find('input.pa_body-fit-checkbox').val() || '';
        }
        if (!bodyCheck) {
            $(".pa_body-fit-checkbox").each(function () {
                if ($(this).is(":checked")) {
                    bodyCheck = $(this).closest('.pa_body-fit-option').data('slug') || $(this).val() || '';
                }
            });
        }

        if (!bodyCheck || !sizeSlug) {
            console.warn('Mont size chart: missing body fit or size', bodyCheck, sizeSlug);
            return;
        }

        var chartKey = bodyCheck + "___" + sizeSlug;

        $('.velg-snipp').find(".mont_option-list").addClass('mont_open');
        $('.velg-snipp').find(".mont_variation-group").addClass('mont_open');
        $sizeItem.parents('.mont_option-list').removeClass('mont_open');

        function openTailor() {
            $tailorGroup.find(".mont_option-list").addClass('mont_open');
            $tailorGroup.addClass('mont_open');
            $('.skreddersydd').find(".mont_option-list").addClass('mont_open');
            $('.skreddersydd').find(".mont_variation-group").addClass('mont_open');
        }

        function finishWithData(data) {
            applyMontCustomSizeChart(data);
            openTailor();
        }

        function chartHasNumbers(data) {
            if (!data) return false;
            var keys = ['shirt_length', 'sleeve_length', 'shoulder', 'half_chest', 'half_waist', 'half_bottom'];
            for (var i = 0; i < keys.length; i++) {
                var v = data[keys[i]];
                if (v !== undefined && v !== null && v !== '' && Number(v) !== 0) return true;
            }
            return false;
        }

        function chartFromPage(key) {
            var charts = (typeof ajaxurl !== 'undefined' && ajaxurl.charts) ? ajaxurl.charts : null;
            if (!charts || typeof charts !== 'object') return null;
            if (charts[key] && chartHasNumbers(charts[key])) return charts[key];
            // Case-insensitive key match
            var keys = Object.keys(charts);
            var needle = String(key).toLowerCase();
            for (var i = 0; i < keys.length; i++) {
                if (String(keys[i]).toLowerCase() === needle && chartHasNumbers(charts[keys[i]])) {
                    return charts[keys[i]];
                }
            }
            return null;
        }

        function setImageBoxLoaders(on) {
            $('.mont_sizes-measurement-item[data-mont-size]').each(function () {
                var $item = $(this);
                var $img = $item.find('.mont_sizes-measurement-icon');
                $item.toggleClass('is-img-loading', !!on);
                if (on && $img.length) {
                    var ph = $img.attr('data-placeholder') || '';
                    if (ph) {
                        $img.attr('src', ph)
                            .attr('data-full', '')
                            .addClass('is-placeholder')
                            .removeAttr('data-dynamic');
                    }
                }
            });
        }

        function loadDiagramsAsync(key) {
            // Instant: client cache (seeded + browser-preloaded on page load).
            var cachedImages = diagramsFromPage(key)
                || (montChartCache[key] && montImagesUsable(montChartCache[key].images)
                    ? montChartCache[key].images
                    : null);

            if (cachedImages) {
                if (!montChartCache[key]) montChartCache[key] = {};
                montChartCache[key].images = cachedImages;
                montDiagramCache[key] = cachedImages;
                applyMontCustomSizeChart({ images: cachedImages });
                setImageBoxLoaders(false);
                return;
            }

            setImageBoxLoaders(true);
            $.ajax({
                url: ajaxurl.url,
                type: "POST",
                dataType: "json",
                data: {
                    action: "mont_get_size_diagrams",
                    key: key
                },
                success: function (res) {
                    var images = res && res.success && res.data ? res.data.images : null;
                    if (montImagesUsable(images)) {
                        if (!montChartCache[key]) montChartCache[key] = {};
                        montChartCache[key].images = images;
                        montDiagramCache[key] = images;
                        applyMontCustomSizeChart({ images: images });
                    } else {
                        $('.mont_sizes-measurement-icon').each(function () {
                            var $img = $(this);
                            var ph = $img.attr('data-placeholder') || '';
                            if (ph) {
                                $img.attr('src', ph).attr('data-full', '').addClass('is-placeholder');
                            }
                        });
                    }
                },
                complete: function () {
                    setImageBoxLoaders(false);
                }
            });
        }

        function fetchMeasurements(key, done) {
            montAbortActive();
            montShowLoader($tailorGroup, 'Laster skreddersøm…');
            montActiveRequest = $.ajax({
                url: ajaxurl.url,
                type: "POST",
                dataType: "json",
                data: {
                    action: "get_all_variation",
                    key: key
                },
                success: function (response) {
                    montHideLoader($tailorGroup);
                    if (!response || !response.length) {
                        done(null);
                        return;
                    }
                    montChartCache[key] = response[0];
                    done(response[0]);
                },
                error: function (xhr, status) {
                    if (status !== 'abort') {
                        montHideLoader($tailorGroup);
                    }
                    done(null);
                },
                complete: function () {
                    montActiveRequest = null;
                }
            });
        }

        // Instant numbers if chart is embedded with real values; otherwise AJAX.
        var localChart = chartFromPage(chartKey);
        if (localChart) {
            montChartCache[chartKey] = localChart;
            finishWithData(localChart);
            loadDiagramsAsync(chartKey);
            return;
        }

        if (montChartCache[chartKey] && chartHasNumbers(montChartCache[chartKey])) {
            finishWithData(montChartCache[chartKey]);
            loadDiagramsAsync(chartKey);
            return;
        }

        fetchMeasurements(chartKey, function (data) {
            if (!data) return;
            finishWithData(data);
            loadDiagramsAsync(chartKey);
        });
    });

    function applyMontCustomSizeChart(data) {
        if (!data) return;

        var measurementKeys = [
            "shirt_length",
            "sleeve_length",
            "shoulder",
            "half_chest",
            "half_waist",
            "half_bottom"
        ];

        measurementKeys.forEach(function (key) {
            if (data[key] === undefined || data[key] === null || data[key] === '') return;

            var $item = $('.mont_sizes-measurement-item[data-mont-size="' + key + '"]');
            if (!$item.length) return;

            var num = data[key];
            var value = num + " cm";

            if (key === "sleeve_length") {
                $item.find(".mont_sizes-measurement-value").text("Left: " + num + " cm, Right: " + num + " cm");
                $item.find('.mont_sizes-control-value').text(num + " cm");
                $('input[name="mont_sizes[sleeve_length_left]"]').val(num).attr("data-value", num).attr("clicked", "false");
                $('input[name="mont_sizes[sleeve_length_right]"]').val(num).attr("data-value", num).attr("clicked", "false");
            } else {
                $item.find(".mont_sizes-measurement-value").text(value);
                $item.find(".mont_sizes-control-value").text(value);
                $item.find(".mont_sizes-hidden-input").val(num).attr("data-value", num).attr("clicked", "false");
            }
        });

        if (data.images && typeof data.images === "object") {
            var imageMap = {
                shirt_length: data.images.shirt_length,
                sleeve_length: data.images.sleeve_length,
                half_waist: data.images.half_waist,
                half_chest: data.images.half_chest,
                half_bottom: data.images.half_bottom,
                shoulder: data.images.shoulder
            };
            Object.keys(imageMap).forEach(function (montKey) {
                var $img = $('.mont_sizes-measurement-item[data-mont-size="' + montKey + '"] .mont_sizes-measurement-icon');
                if (!$img.length) return;

                var entry = imageMap[montKey];
                var thumb = '';
                var full = '';
                if (entry) {
                    if (typeof entry === 'string') {
                        full = entry;
                    } else {
                        thumb = entry.thumb || '';
                        full = entry.full || '';
                    }
                }

                var ph = $img.attr('data-placeholder') || '';
                // Prefer tiny thumb; fall back to full Size/ URL — never keep placeholder when a file exists.
                var src = thumb || full || '';
                if (src) {
                    $img.attr('src', src)
                        .attr('data-full', full || thumb || src)
                        .attr('data-dynamic', '1')
                        .removeClass('is-placeholder');
                } else {
                    $img.attr('src', ph || '')
                        .attr('data-full', '')
                        .addClass('is-placeholder')
                        .removeAttr('data-dynamic');
                }
            });
        }
    }

    $(document).on('click', '.mont_option-list li', function () {
        if ($(this).hasClass('pa_body-fit-option')) {
            $('.pa_body-fit-option').find('.mont_checkbox_select').prop('checked', false);
            $(this).find('.mont_checkbox_select').prop('checked', true);
            $('.pa_body-fit').css({
                'background': '#b0b0b0',
                'color': 'white'
            });
        }
        if ($(this).hasClass('pa_size-option')) {
            $('.pa_size-option').find('.mont_checkbox_select').prop('checked', false);
            $('.pa_size-option').find('.mont_checkbox_select').val('');
            $(this).find('.mont_checkbox_select').prop('checked', true);
            $(this).find('.mont_checkbox_select').val($(this).find('.tobeSelected').text());
            $('.pa_size').css({
                'background': '#b0b0b0',
                'color': 'white'
            });
        }
    });

    $('.radioTocheck').click(function () {
        var $option = $(this);
        var selectedValue = $option.find('input[type="radio"]:checked').val();
        $option.parents('.mont_variation-group').find('.skname b').html(selectedValue);

        var wasFactoryDefault = $option.find('input[type="radio"]').prop('defaultChecked');
        if (!wasFactoryDefault && typeof showMontCustomAlert === 'function') {
            showMontCustomAlert($option.closest('.mont_variation-group').get(0));
        } else if (wasFactoryDefault && typeof closeAlert === 'function') {
            var hasCustomSize = $('input.mont_sizes-hidden-input[clicked="true"]').length > 0;
            if (!hasCustomSize) closeAlert();
        }
    });
});
