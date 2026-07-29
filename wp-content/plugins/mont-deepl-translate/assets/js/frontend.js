/**
 * Frontend DeepL applicator.
 */
(function () {
    'use strict';

    var cfg = window.montDeepL || {};
    var debug = /[?&]mont_deepl_debug=1/.test(window.location.search);
    var normalizeMixed = !!cfg.normalizeMixedToSource;
    var sameLanguage = (cfg.sourceLang && cfg.targetLang && cfg.sourceLang === cfg.targetLang);
    var shouldNormalizeMixed = sameLanguage && normalizeMixed;
    var includeSelectors = Array.isArray(cfg.includeSelectors) ? cfg.includeSelectors : [];
    var VARIATION_ROOT = '.mont_custom_options, .mont_variation-selector, .skreddersydd';

    function log() {
        if (debug && window.console && console.log) {
            console.log.apply(console, ['[Mont DeepL]'].concat([].slice.call(arguments)));
        }
    }

    if (!cfg.enabled) {
        return;
    }

    if (!cfg.targetLang) {
        return;
    }

    if (!cfg.shouldTranslate && !shouldNormalizeMixed) {
        return;
    }

    var SKIP_TAGS = {
        SCRIPT: 1, STYLE: 1, NOSCRIPT: 1, CODE: 1, PRE: 1,
        TEXTAREA: 1, SVG: 1, PATH: 1, MATH: 1, IFRAME: 1
    };
    var ATTRIBUTE_LIST = ['placeholder', 'title', 'aria-label', 'alt'];
    var sessionCache = Object.create(null);
    var flushTimer = null;
    var applied = false;
    var nodeId = 0;

    function hasLetters(text) {
        try {
            return /\p{L}/u.test(text);
        } catch (e) {
            return /[A-Za-zÀ-ÖØ-öø-ÿæåÆÅ]/.test(text);
        }
    }

    function looksEnglish(text) {
        if (!text || !/[A-Za-z]/.test(text)) return false;
        if (/[æøåÆØÅ]/.test(text)) return false;
        return /\b(the|and|for|with|search|wishlist|account|store|location|about|shirts|shirt|size|color|cart|checkout|back|home|shop|menu|add|view|read|more|free|shipping|sale|new|collection|left|right|charge|optional|sleeve|shoulder|waist|chest|bottom|length|passform|collar|cuff|snipp|mansjetter)\b/i.test(text);
    }

    function shouldSkipElement(el) {
        if (!el) return true;
        if (SKIP_TAGS[el.tagName]) return true;
        if (el.isContentEditable) return true;
        if (el.closest && el.closest('.notranslate, [translate="no"], .dc-region-switcher, .goog-te-banner-frame, #google_translate_element2')) {
            return true;
        }
        return false;
    }

    function shouldTranslateText(text, force) {
        if (!text || !/\S/.test(text)) return false;
        if (!hasLetters(text)) return false;
        if (text.trim().length < 2) return false;
        if (force) return true;
        if (shouldNormalizeMixed) {
            return looksEnglish(text);
        }
        return true;
    }

    function collectTextEntries(root, force) {
        var entries = [];
        var walker = document.createTreeWalker(root || document.body, NodeFilter.SHOW_TEXT, {
            acceptNode: function (node) {
                var parent = node.parentElement;
                if (!parent || shouldSkipElement(parent)) return NodeFilter.FILTER_REJECT;
                return shouldTranslateText(node.nodeValue, force) ? NodeFilter.FILTER_ACCEPT : NodeFilter.FILTER_REJECT;
            }
        });
        var current;
        while ((current = walker.nextNode())) {
            if (!current._montDeepLId) {
                current._montDeepLId = 'n' + (++nodeId);
            }
            entries.push({ type: 'text', node: current, source: current.nodeValue, force: !!force });
        }
        return entries;
    }

    function collectAttributeEntries(root, force) {
        var entries = [];
        var scope = root || document;
        var elements = scope.querySelectorAll ? scope.querySelectorAll('*') : [];

        for (var i = 0; i < elements.length; i++) {
            var el = elements[i];
            if (shouldSkipElement(el)) continue;

            for (var j = 0; j < ATTRIBUTE_LIST.length; j++) {
                var attr = ATTRIBUTE_LIST[j];
                if (!el.hasAttribute(attr)) continue;
                var val = el.getAttribute(attr);
                if (shouldTranslateText(val, force)) {
                    entries.push({ type: 'attr', el: el, attr: attr, source: val, force: !!force });
                }
            }

            if ((el.tagName === 'INPUT' || el.tagName === 'BUTTON') && el.hasAttribute('value')) {
                var value = el.getAttribute('value');
                if (shouldTranslateText(value, force)) {
                    entries.push({ type: 'attr', el: el, attr: 'value', source: value, force: !!force });
                }
            }
        }
        return entries;
    }

    function collectSelectorEntries(root) {
        var entries = [];
        var scope = root || document;
        if (!includeSelectors.length) return entries;

        for (var s = 0; s < includeSelectors.length; s++) {
            var selector = includeSelectors[s];
            if (!selector) continue;

            var nodes;
            try {
                nodes = scope.querySelectorAll(selector);
            } catch (e) {
                continue;
            }

            for (var i = 0; i < nodes.length; i++) {
                var node = nodes[i];
                if (shouldSkipElement(node)) continue;
                entries = entries
                    .concat(collectTextEntries(node, true))
                    .concat(collectAttributeEntries(node, true));
            }
        }

        return entries;
    }

    function collectEntries(root) {
        var scope = root || document.body;
        if (!scope) return [];
        return collectTextEntries(scope, false)
            .concat(collectAttributeEntries(scope, false))
            .concat(collectSelectorEntries(scope));
    }

    function requestTranslate(texts, overrideSourceLang, done) {
        var unique = [];
        var seen = Object.create(null);
        texts.forEach(function (t) {
            if (!seen[t] && !sessionCache[t]) {
                seen[t] = 1;
                unique.push(t);
            }
        });

        if (!unique.length) {
            done(sessionCache);
            return;
        }

        var body = new FormData();
        body.append('action', 'mont_deepl_translate');
        body.append('nonce', cfg.nonce);
        body.append('source_lang', overrideSourceLang || cfg.sourceLang || 'NB');
        body.append('target_lang', cfg.targetLang);
        body.append('texts', JSON.stringify(unique));

        fetch(cfg.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: body })
            .then(function (r) { return r.json(); })
            .then(function (json) {
                if (json && json.success && json.data && json.data.translations) {
                    Object.keys(json.data.translations).forEach(function (k) {
                        sessionCache[k] = json.data.translations[k];
                    });
                }
                done(sessionCache);
            })
            .catch(function () {
                done(sessionCache);
            });
    }

    function applyEntriesWithSource(entries, overrideSourceLang) {
        if (!entries.length) return;

        var texts = [];
        for (var i = 0; i < entries.length; i++) {
            if (!sessionCache[entries[i].source]) {
                texts.push(entries[i].source);
            }
        }

        requestTranslate(texts, overrideSourceLang, function (map) {
            var changed = 0;
            for (var j = 0; j < entries.length; j++) {
                var e = entries[j];
                var translated = map[e.source];
                if (!translated || translated === e.source) continue;

                if (e.type === 'text') {
                    e.node.nodeValue = translated;
                    changed++;
                } else if (e.type === 'attr' && e.el && e.el.getAttribute(e.attr) === e.source) {
                    e.el.setAttribute(e.attr, translated);
                    changed++;
                }
            }

            if (!applied && changed > 0) {
                document.documentElement.setAttribute('lang', (cfg.targetLang || 'en').toLowerCase());
                document.documentElement.classList.add('mont-deepl-translated');
                applied = true;
            }
            log('Applied', changed, overrideSourceLang || cfg.sourceLang);
        });
    }

    function applyEntries(entries) {
        var regular = [];
        var englishSource = [];

        for (var i = 0; i < entries.length; i++) {
            if (entries[i].force && shouldNormalizeMixed) {
                englishSource.push(entries[i]);
            } else {
                regular.push(entries[i]);
            }
        }

        if (regular.length) applyEntriesWithSource(regular, null);
        if (englishSource.length) applyEntriesWithSource(englishSource, 'EN-US');
    }

    function translateRoot(root) {
        if (!root) return;
        var entries = collectEntries(root);
        if (!entries.length) return;
        log('Entries in scope:', entries.length, root.className || root.tagName);
        var size = cfg.batchSize || 60;
        for (var i = 0; i < entries.length; i += size) {
            applyEntries(entries.slice(i, i + size));
        }
    }

    function translatePage() {
        if (!document.body) return;
        translateRoot(document.body);
    }

    function translateVariations() {
        var roots = document.querySelectorAll(VARIATION_ROOT);
        if (!roots.length) {
            translatePage();
            return;
        }
        for (var i = 0; i < roots.length; i++) {
            translateRoot(roots[i]);
        }
    }

    function scheduleTranslate() {
        clearTimeout(flushTimer);
        flushTimer = setTimeout(translatePage, 90);
    }

    function scheduleVariationTranslate() {
        clearTimeout(flushTimer);
        flushTimer = setTimeout(translateVariations, 120);
    }

    window.montDeepLRetranslate = scheduleTranslate;
    window.montDeepLRetranslateVariations = scheduleVariationTranslate;

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', scheduleTranslate);
    } else {
        scheduleTranslate();
    }

    window.addEventListener('load', function () {
        setTimeout(translatePage, 700);
        setTimeout(translateVariations, 1500);
        setTimeout(translateVariations, 3500);
    });

    document.addEventListener('click', function (e) {
        var t = e.target;
        if (!t || !t.closest) return;
        if (t.closest('.mont_variation-header, .mont_option-item, .collar-option, .mont_sizes-change-btn, .mont_sizes-close-btn')) {
            scheduleVariationTranslate();
        }
    }, true);

    if (typeof jQuery !== 'undefined') {
        jQuery(document).ajaxComplete(function (_event, _xhr, settings) {
            var data = settings && settings.data ? String(settings.data) : '';
            if (
                data.indexOf('get_variation_details') !== -1 ||
                data.indexOf('get_all_variation') !== -1
            ) {
                scheduleVariationTranslate();
            }
        });
    }

    if (typeof MutationObserver !== 'undefined') {
        var obsTimer = null;
        var observer = new MutationObserver(function (mutations) {
            var relevant = false;
            for (var i = 0; i < mutations.length; i++) {
                var m = mutations[i];
                if (m.type === 'childList' && m.addedNodes && m.addedNodes.length) {
                    relevant = true;
                    break;
                }
                if (m.type === 'characterData') {
                    relevant = true;
                    break;
                }
                if (m.type === 'attributes' && m.attributeName === 'class') {
                    var el = m.target;
                    if (el && el.closest && el.closest(VARIATION_ROOT)) {
                        relevant = true;
                        break;
                    }
                }
            }
            if (!relevant) return;
            clearTimeout(obsTimer);
            obsTimer = setTimeout(function () {
                translateVariations();
            }, 300);
        });

        document.addEventListener('DOMContentLoaded', function () {
            var watchRoot = document.querySelector('.mont_custom_options') || document.body;
            if (watchRoot) {
                observer.observe(watchRoot, {
                    childList: true,
                    subtree: true,
                    characterData: true,
                    attributes: true,
                    attributeFilter: ['class']
                });
            }
        });
    }
})();
