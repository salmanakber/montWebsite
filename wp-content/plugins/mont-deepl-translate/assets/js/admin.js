(function () {
    'use strict';

    var cfg = window.montDeepLAdmin || {};

    function $(sel) {
        return document.querySelector(sel);
    }

    function renderResult(box, ok, html) {
        box.style.display = 'block';
        box.style.background = ok ? '#edfaef' : '#fcf0f1';
        box.style.border = '1px solid ' + (ok ? '#00a32a' : '#d63638');
        box.style.color = '#1d2327';
        box.innerHTML = html;
    }

    function init() {
        var btn = $('#mont_deepl_test_btn');
        var box = $('#mont_deepl_test_result');
        if (!btn || !box || !cfg.ajaxUrl) {
            return;
        }

        btn.addEventListener('click', function () {
            var apiKeyEl = $('#mont_deepl_api_key');
            var targetEl = $('#mont_deepl_test_target');
            var sourceEl = $('#mont_deepl_source');
            var apiKey = apiKeyEl ? apiKeyEl.value : '';
            var target = targetEl ? targetEl.value : 'EN-US';
            var source = sourceEl ? sourceEl.value : 'NB';

            btn.disabled = true;
            btn.textContent = (cfg.i18n && cfg.i18n.testing) || 'Testing…';
            box.style.display = 'none';

            var body = new FormData();
            body.append('action', 'mont_deepl_test_api');
            body.append('nonce', cfg.nonce || '');
            body.append('api_key', apiKey);
            body.append('source_lang', source);
            body.append('target_lang', target);

            fetch(cfg.ajaxUrl, {
                method: 'POST',
                credentials: 'same-origin',
                body: body
            })
                .then(function (response) {
                    return response.json().then(function (json) {
                        return { ok: response.ok, json: json };
                    }).catch(function () {
                        return { ok: false, json: null };
                    });
                })
                .then(function (result) {
                    var res = result.json;
                    if (!result.ok || !res || !res.success) {
                        var msg = (res && res.data && res.data.message) ? res.data.message : 'Unknown error';
                        renderResult(box, false, '<strong>' + ((cfg.i18n && cfg.i18n.failed) || 'Failed') + '</strong><br>' + msg);
                        return;
                    }

                    var d = res.data || {};
                    var html = '<strong>' + ((cfg.i18n && cfg.i18n.success) || 'Success') + '</strong><br>';
                    html += 'Endpoint: <code>' + (d.endpoint || '') + '</code><br>';
                    html += 'Plan: <strong>' + (d.plan || '') + '</strong><br>';
                    html += 'Source (' + (d.source_lang || '') + '): <em>' + (d.source || '') + '</em><br>';
                    html += 'Translated (' + (d.target_lang || '') + '): <strong>' + (d.translated || '') + '</strong>';

                    if (d.remote_usage && d.remote_usage.character_count !== undefined) {
                        html += '<br><br>DeepL dashboard usage: <strong>' + Number(d.remote_usage.character_count).toLocaleString() + '</strong>';
                        if (d.remote_usage.character_limit) {
                            html += ' / ' + Number(d.remote_usage.character_limit).toLocaleString();
                        }
                        html += ' characters this billing period';
                    }

                    renderResult(box, true, html);
                })
                .catch(function (err) {
                    renderResult(box, false, '<strong>' + ((cfg.i18n && cfg.i18n.failed) || 'Failed') + '</strong><br>' + (err && err.message ? err.message : 'Network error'));
                })
                .finally(function () {
                    btn.disabled = false;
                    btn.textContent = (cfg.i18n && cfg.i18n.testBtn) || 'Test DeepL API';
                });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
