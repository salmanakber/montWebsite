(function ($) {
    'use strict';

    function renderResult($box, ok, html) {
        $box
            .show()
            .css({
                background: ok ? '#edfaef' : '#fcf0f1',
                border: '1px solid ' + (ok ? '#00a32a' : '#d63638'),
                color: '#1d2327'
            })
            .html(html);
    }

    $('#mont_deepl_test_btn').on('click', function () {
        var $btn = $(this);
        var $box = $('#mont_deepl_test_result');
        var apiKey = $('#mont_deepl_api_key').val();
        var target = $('#mont_deepl_test_target').val();
        var source = $('#mont_deepl_source').val() || 'NB';

        $btn.prop('disabled', true).text(montDeepLAdmin.i18n.testing);
        $box.hide();

        $.post(montDeepLAdmin.ajaxUrl, {
            action: 'mont_deepl_test_api',
            nonce: montDeepLAdmin.nonce,
            api_key: apiKey,
            source_lang: source,
            target_lang: target
        })
            .done(function (res) {
                if (!res || !res.success) {
                    renderResult($box, false, '<strong>' + montDeepLAdmin.i18n.failed + '</strong><br>' + (res && res.data && res.data.message ? res.data.message : 'Unknown error'));
                    return;
                }

                var d = res.data;
                var html = '<strong>' + montDeepLAdmin.i18n.success + '</strong><br>';
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
                } else if (d.remote_usage && d.remote_usage.message) {
                    html += '<br><br>Usage API: ' + d.remote_usage.message;
                }

                renderResult($box, true, html);
            })
            .fail(function (xhr) {
                var msg = montDeepLAdmin.i18n.failed;
                if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                    msg = xhr.responseJSON.data.message;
                }
                renderResult($box, false, '<strong>' + montDeepLAdmin.i18n.failed + '</strong><br>' + msg);
            })
            .always(function () {
                $btn.prop('disabled', false).text(montDeepLAdmin.i18n.testBtn);
            });
    });
})(jQuery);
