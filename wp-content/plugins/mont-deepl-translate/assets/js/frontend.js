/**
 * Frontend DeepL applicator.
 * Collects visible text nodes, asks the server (cache → DeepL), replaces in place.
 */
(function () {
    'use strict';

    var cfg = window.montDeepL || {};
    if (!cfg.enabled || !cfg.shouldTranslate || !cfg.targetLang) {
        return;
    }

    var SKIP_TAGS = {
        SCRIPT: 1,
        STYLE: 1,
        NOSCRIPT: 1,
        CODE: 1,
        PRE: 1,
        TEXTAREA: 1,
        INPUT: 1,
        SELECT: 1,
        OPTION: 1,
        SVG: 1,
        PATH: 1,
        MATH: 1,
        IFRAME: 1
    };

    var sessionCache = Object.create(null);
    var pending = Object.create(null);
    var queue = [];
    var flushTimer = null;
    var applied = false;

    function shouldSkipNode(node) {
        if (!node || node.nodeType !== 3) {
            return true;
        }
        var parent = node.parentElement;
        if (!parent) {
            return true;
        }
        if (SKIP_TAGS[parent.tagName]) {
            return true;
        }
        if (parent.isContentEditable) {
            return true;
        }
        if (parent.closest && parent.closest('.notranslate, [translate="no"], .dc-region-switcher, .goog-te-banner-frame, #google_translate_element2')) {
            return true;
        }
        var text = node.nodeValue;
        if (!text || !/\S/.test(text)) {
            return true;
        }
        // Skip strings with no letters (prices, SKUs, icons).
        if (!/[A-Za-zÀ-ÖØ-öø-ÿ]/.test(text)) {
            return true;
        }
        // Skip very short tokens that are usually brands / codes
        if (text.trim().length < 2) {
            return true;
        }
        return false;
    }

    function collectNodes(root) {
        var nodes = [];
        var walker = document.createTreeWalker(root || document.body, NodeFilter.SHOW_TEXT, {
            acceptNode: function (node) {
                return shouldSkipNode(node) ? NodeFilter.FILTER_REJECT : NodeFilter.FILTER_ACCEPT;
            }
        });
        var current;
        while ((current = walker.nextNode())) {
            nodes.push(current);
        }
        return nodes;
    }

    function requestTranslate(texts, done) {
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
        body.append('source_lang', cfg.sourceLang || 'NB');
        body.append('target_lang', cfg.targetLang);
        body.append('texts', JSON.stringify(unique));

        fetch(cfg.ajaxUrl, {
            method: 'POST',
            credentials: 'same-origin',
            body: body
        })
            .then(function (r) {
                return r.json();
            })
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

    function applyToNodes(nodes) {
        var texts = [];
        nodes.forEach(function (n) {
            var t = n.nodeValue;
            if (t && !sessionCache[t]) {
                texts.push(t);
            }
        });

        requestTranslate(texts, function (map) {
            nodes.forEach(function (n) {
                var src = n.nodeValue;
                if (map[src] && map[src] !== src) {
                    n.nodeValue = map[src];
                }
            });
            if (!applied) {
                document.documentElement.setAttribute('lang', (cfg.targetLang || 'en').toLowerCase());
                document.documentElement.classList.add('mont-deepl-translated');
                applied = true;
            }
        });
    }

    function translatePage() {
        if (!document.body) {
            return;
        }
        var nodes = collectNodes(document.body);
        if (!nodes.length) {
            return;
        }

        // Batch in chunks to keep payloads reasonable.
        var size = cfg.batchSize || 60;
        for (var i = 0; i < nodes.length; i += size) {
            applyToNodes(nodes.slice(i, i + size));
        }
    }

    function scheduleTranslate() {
        clearTimeout(flushTimer);
        flushTimer = setTimeout(translatePage, 80);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', scheduleTranslate);
    } else {
        scheduleTranslate();
    }

    // Re-run lightly after late content (Elementor / AJAX menus).
    window.addEventListener('load', function () {
        setTimeout(translatePage, 600);
    });

    // Observe major DOM additions (mobile menu, modals) without thrashing.
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
            if (!relevant) {
                return;
            }
            clearTimeout(obsTimer);
            obsTimer = setTimeout(translatePage, 400);
        });
        document.addEventListener('DOMContentLoaded', function () {
            observer.observe(document.body, { childList: true, subtree: true });
        });
    }
})();
