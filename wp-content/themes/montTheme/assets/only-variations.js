jQuery(document).ready(function ($) {
    function toast(msg, isError) {
        var $t = $('#mont-vs-toast');
        $t.text(msg).prop('hidden', false).toggleClass('is-error', !!isError);
        clearTimeout(window._montVsToast);
        window._montVsToast = setTimeout(function () {
            $t.prop('hidden', true);
        }, 3200);
    }

    // Fit tabs
    $(document).on('click', '.mont-vs-fit-tab', function () {
        var fit = $(this).data('fit');
        $('.mont-vs-fit-tab').removeClass('is-active');
        $(this).addClass('is-active');
        $('.mont-vs-panel').removeClass('is-active');
        $('.mont-vs-panel[data-fit-panel="' + fit + '"]').addClass('is-active');
    });

    // Mark dirty on edit
    $(document).on('input change', '.mont-vs-input', function () {
        $(this).closest('.mont-vs-row').addClass('is-dirty');
    });

    function collectDirtyRows() {
        var rows = [];
        $('.mont-vs-row.is-dirty').each(function () {
            var $row = $(this);
            var item = {
                id: parseInt($row.data('id'), 10) || 0,
                body_fit: $row.data('fit'),
                size_slug: $row.data('size')
            };
            $row.find('.mont-vs-input').each(function () {
                item[$(this).data('field')] = $(this).val();
            });
            rows.push(item);
        });
        return rows;
    }

    function collectAllFilledRows() {
        var rows = [];
        $('.mont-vs-row').each(function () {
            var $row = $(this);
            var hasValue = false;
            var item = {
                id: parseInt($row.data('id'), 10) || 0,
                body_fit: $row.data('fit'),
                size_slug: $row.data('size')
            };
            $row.find('.mont-vs-input').each(function () {
                var v = $(this).val();
                item[$(this).data('field')] = v;
                if (v !== '' && v !== null) hasValue = true;
            });
            if (hasValue || item.id) {
                rows.push(item);
            }
        });
        return rows;
    }

    $('#mont-vs-save-bulk').on('click', function () {
        var $btn = $(this);
        var rows = collectDirtyRows();
        if (!rows.length) {
            // Save all panels that have any values (first-time fill)
            rows = collectAllFilledRows();
        }
        if (!rows.length) {
            toast('Nothing to save.', true);
            return;
        }

        $btn.prop('disabled', true).text('Saving…');
        $.ajax({
            url: variationSettings.ajaxurl,
            type: 'POST',
            data: {
                action: 'save_variation_bulk',
                nonce: variationSettings.nonce,
                rows: JSON.stringify(rows)
            },
            success: function (res) {
                $btn.prop('disabled', false).text('Save all changes');
                if (res && res.success) {
                    $('.mont-vs-row.is-dirty').removeClass('is-dirty');
                    toast((variationSettings.i18n && variationSettings.i18n.saved) || 'Saved.');
                    setTimeout(function () { location.reload(); }, 700);
                } else {
                    toast((variationSettings.i18n && variationSettings.i18n.error) || 'Error', true);
                }
            },
            error: function () {
                $btn.prop('disabled', false).text('Save all changes');
                toast((variationSettings.i18n && variationSettings.i18n.error) || 'Error', true);
            }
        });
    });

    $(document).on('click', '.mont-vs-delete', function () {
        if (!confirm((variationSettings.i18n && variationSettings.i18n.confirm) || 'Delete?')) return;
        var id = $(this).data('id');
        var $row = $(this).closest('.mont-vs-row');
        $.post(variationSettings.ajaxurl, {
            action: 'delete_variation',
            id: id,
            nonce: variationSettings.nonce
        }, function (res) {
            if (res && res.success) {
                $row.fadeOut(200, function () { $(this).remove(); });
                toast('Row deleted.');
            }
        });
    });

    $('#mont-vs-scan-images').on('click', function () {
        var $btn = $(this);
        $btn.prop('disabled', true).text('Scanning…');
        $.post(variationSettings.ajaxurl, {
            action: 'mont_scan_size_images',
            nonce: variationSettings.nonce
        }, function (res) {
            $btn.prop('disabled', false).text('Refresh image library');
            if (res && res.success) {
                toast('Image library refreshed.');
                setTimeout(function () { location.reload(); }, 500);
            } else {
                toast('Scan failed', true);
            }
        }).fail(function () {
            $btn.prop('disabled', false).text('Refresh image library');
            toast('Scan failed', true);
        });
    });

    // Keyboard shortcut: Cmd/Ctrl+S
    $(document).on('keydown', function (e) {
        if ((e.metaKey || e.ctrlKey) && e.key === 's') {
            e.preventDefault();
            $('#mont-vs-save-bulk').trigger('click');
        }
    });
});
