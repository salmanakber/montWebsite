/**
 * Frontend DeepL applicator.
 */
(function () {
    'use strict';

    var cfg = window.montDeepL || {};
    var debug = /[?&]mont_deepl_debug=1/.test(window.location.search);

    function log() {
        if (debug && window.console && console.log) {
            console.log.apply(console, ['[Mont DeepL]'].concat([].slice.call(arguments)));
        }
    }

    if (!cfg.enabled) {
        log('Disabled — enable in Settings → DeepL Translate');
        return;
    }

    if (!cfg.targetLang) {
        log('No target language for current region');
        return;
    }

    if (!cfg.shouldTranslate) {
        log('Skipped — source (' + cfg.sourceLang + ') equals target (' + cfg.targetLang + '). Switch region to Italy or International.');
        return;
    }

    log('Active', cfg);

    var SKIP_TAGS = {
        SCRIPT: 1, STYLE: 1, NOSCRIPT: 1, CODE: 1, PRE: 1,
        TEXTAREA: 1, INPUT: 1, SELECT: 1, OPTION: 1,
        SVG: 1, PATH: 1, MATH: 1, IFRAME: 1
    };

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

    function shouldSkipNode(node) {
        if (!node || node.nodeType !== 3) return true;
        var parent = node.parentElement;
        if (!parent) return true;
        if (SKIP_TAGS[parent.tagName]) return true;
        if (parent.isContentEditable) return true;
        if (parent.closest && parent.closest('.notranslate, [translate="no"], .dc-region-switcher, .goog-te-banner-frame, #google_translate_element2')) {
            return true;
        }
        var text = node.nodeValue;
        if (!text || !/\S/.test(text)) return true;
        if (!hasLetters(text)) return true;
        if (text.trim().length < 2) return true;
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

        log('Requesting', unique.length, 'strings via AJAX');

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
                return r.text().then(function (text) {
                    try {
                        return { ok: r.ok, json: JSON.parse(text) };
                    } catch (e) {
                        log('Non-JSON response', text.slice(0, 200));
                        return { ok: false, json: null };
                    }
                });
            })
            .then(function (result) {
                var json = result.json;
                if (json && json.success && json.data && json.data.translations) {
                    var count = Object.keys(json.data.translations).length;
                    log('Received', count, 'translations');
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

    function applyToNodes(nodes) {
        var texts = [];
        nodes.forEach(function (n) {
            var t = n.nodeValue;
            if (t && !sessionCache[t]) texts.push(t);
        });

        requestTranslate(texts, function (map) {
            var changed = 0;
            nodes.forEach(function (n) {
                var src = n.nodeValue;
                if (map[src] && map[src] !== src) {
                    n.nodeValue = map[src];
                    changed++;
                }
            });
            if (changed > 0) {
                log('Applied', changed, 'text nodes');
            }
            if (!applied) {
                document.documentElement.setAttribute('lang', (cfg.targetLang || 'en').toLowerCase());
                document.documentElement.classList.add('mont-deepl-translated');
                applied = true;
            }
        });
    }

    function translatePage() {
        if (!document.body) return;
        var nodes = collectNodes(document.body);
        if (!nodes.length) {
            log('No translatable text nodes found');
            return;
        }
        log('Found', nodes.length, 'text nodes');
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

    window.addEventListener('load', function () {
        setTimeout(translatePage, 600);
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
            obsTimer = setTimeout(translatePage, 400);
        });
        document.addEventListener('DOMContentLoaded', function () {
            if (document.body) {
                observer.observe(document.body, { childList: true, subtree: true });
            }
        });
    }
})();
