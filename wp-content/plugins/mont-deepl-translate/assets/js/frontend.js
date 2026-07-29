/**
 * Frontend DeepL applicator.
 * Translates visible text nodes + common UI attributes.
 */
(function () {
    'use strict';

    var cfg = window.montDeepL || {};
    var debug = /[?&]mont_deepl_debug=1/.test(window.location.search);
    var normalizeMixed = !!cfg.normalizeMixedToSource;
    var sameLanguage = (cfg.sourceLang && cfg.targetLang && cfg.sourceLang === cfg.targetLang);
    var shouldNormalizeMixed = sameLanguage && normalizeMixed;

    function log() {
        if (debug && window.console && console.log) {
            console.log.apply(console, ['[Mont DeepL]'].concat([].slice.call(arguments)));
        }
    }

    if (!cfg.enabled) {
        log('Disabled');
        return;
    }

    if (!cfg.targetLang) {
        log('No target language for region');
        return;
    }

    if (!cfg.shouldTranslate && !shouldNormalizeMixed) {
        log('Skipped');
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
        return /\b(the|and|for|with|search|wishlist|account|store|location|about|shirts|shirt|size|color|cart|checkout|back|home|shop|menu)\b/i.test(text);
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

    function shouldTranslateText(text) {
        if (!text || !/\S/.test(text)) return false;
        if (!hasLetters(text)) return false;
        if (text.trim().length < 2) return false;
        if (shouldNormalizeMixed) {
            return looksEnglish(text);
        }
        return true;
    }

    function collectTextEntries(root) {
        var entries = [];
        var walker = document.createTreeWalker(root || document.body, NodeFilter.SHOW_TEXT, {
            acceptNode: function (node) {
                var parent = node.parentElement;
                if (!parent || shouldSkipElement(parent)) return NodeFilter.FILTER_REJECT;
                return shouldTranslateText(node.nodeValue) ? NodeFilter.FILTER_ACCEPT : NodeFilter.FILTER_REJECT;
            }
        });
        var current;
        while ((current = walker.nextNode())) {
            entries.push({ type: 'text', node: current, source: current.nodeValue });
        }
        return entries;
    }

    function collectAttributeEntries(root) {
        var entries = [];
        var elements = (root || document).querySelectorAll('*');
        for (var i = 0; i < elements.length; i++) {
            var el = elements[i];
            if (shouldSkipElement(el)) continue;
            for (var j = 0; j < ATTRIBUTE_LIST.length; j++) {
                var attr = ATTRIBUTE_LIST[j];
                if (!el.hasAttribute(attr)) continue;
                var val = el.getAttribute(attr);
                if (shouldTranslateText(val)) {
                    entries.push({ type: 'attr', el: el, attr: attr, source: val });
                }
            }
            if ((el.tagName === 'INPUT' || el.tagName === 'BUTTON') && el.hasAttribute('value')) {
                var value = el.getAttribute('value');
                if (shouldTranslateText(value)) {
                    entries.push({ type: 'attr', el: el, attr: 'value', source: value });
                }
            }
        }
        return entries;
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
                } else if (json && !json.success) {
                    log('API error:', json.data && json.data.message ? json.data.message : json);
                }
                done(sessionCache);
            })
            .catch(function (err) {
                log('Fetch error', err);
                done(sessionCache);
            });
    }

    function applyEntries(entries) {
        if (!entries.length) return;
        var texts = [];
        for (var i = 0; i < entries.length; i++) {
            if (!sessionCache[entries[i].source]) texts.push(entries[i].source);
        }

        var sourceOverride = shouldNormalizeMixed ? 'EN-US' : null;
        requestTranslate(texts, sourceOverride, function (map) {
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
            log('Applied entries:', changed);
        });
    }

    function translatePage() {
        if (!document.body) return;
        var entries = collectTextEntries(document.body).concat(collectAttributeEntries(document.body));
        if (!entries.length) {
            log('No entries');
            return;
        }
        log('Translatable entries:', entries.length);
        var size = cfg.batchSize || 60;
        for (var i = 0; i < entries.length; i += size) {
            applyEntries(entries.slice(i, i + size));
        }
    }

    function scheduleTranslate() {
        clearTimeout(flushTimer);
        flushTimer = setTimeout(translatePage, 90);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', scheduleTranslate);
    } else {
        scheduleTranslate();
    }

    window.addEventListener('load', function () {
        setTimeout(translatePage, 700);
    });

    if (typeof MutationObserver !== 'undefined') {
        var obsTimer = null;
        var observer = new MutationObserver(function (mutations) {
            var relevant = false;
            for (var i = 0; i < mutations.length; i++) {
                if (mutations[i].addedNodes && mutations[i].addedNodes.length) {
                    relevant = true;
                    break;
                }
            }
            if (!relevant) return;
            clearTimeout(obsTimer);
            obsTimer = setTimeout(translatePage, 450);
        });
        document.addEventListener('DOMContentLoaded', function () {
            if (document.body) observer.observe(document.body, { childList: true, subtree: true });
        });
    }
})();
