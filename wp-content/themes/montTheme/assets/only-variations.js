jQuery(document).ready(function ($) {
    function toast(msg, isError) {
        var $t = $('#mont-vs-toast');
        $t.text(msg).prop('hidden', false).toggleClass('is-error', !!isError);
        clearTimeout(window._montVsToast);
        window._montVsToast = setTimeout(function () {
            $t.prop('hidden', true);
        }, 3200);
    }

    function parseJsonAttr($el, name) {
        try {
            var raw = $el.attr(name) || '{}';
            return JSON.parse(raw);
        } catch (e) {
            return {};
        }
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
            rows.push(serializeRow($(this)));
        });
        return rows;
    }

    function collectAllFilledRows() {
        var rows = [];
        $('.mont-vs-row').each(function () {
            var $row = $(this);
            var item = serializeRow($row);
            var hasValue = false;
            Object.keys(item).forEach(function (k) {
                if (variationSettings.fields && variationSettings.fields.indexOf(k) !== -1 && item[k] !== '' && item[k] !== null) {
                    hasValue = true;
                }
            });
            if (hasValue || item.id || (item.diagram_images && item.diagram_images !== '{}')) {
                rows.push(item);
            }
        });
        return rows;
    }

    function serializeRow($row) {
        var item = {
            id: parseInt($row.data('id'), 10) || 0,
            body_fit: $row.data('fit'),
            size_slug: $row.data('size'),
            diagram_images: $row.attr('data-overrides') || '{}'
        };
        $row.find('.mont-vs-input').each(function () {
            item[$(this).data('field')] = $(this).val();
        });
        return item;
    }

    $('#mont-vs-save-bulk').on('click', function () {
        var $btn = $(this);
        var rows = collectDirtyRows();
        if (!rows.length) {
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

    // ---------- Diagram modal (upload / replace / clear) ----------
    var $activeDiagramRow = null;
    var draftOverrides = {};
    var draftAuto = {};
    var mediaFrame = null;
    var mediaTargetKey = '';

    function diagramKeys() {
        return Object.keys(variationSettings.diagrams || {
            shirt_length: 1,
            sleeve_length: 1,
            half_waist: 1,
            half_chest: 1,
            half_bottom: 1,
            shoulder: 1,
            neck_collar: 1,
            armhole: 1
        });
    }

    function openDiagramModal($row) {
        $activeDiagramRow = $row;
        draftOverrides = parseJsonAttr($row, 'data-overrides');
        draftAuto = parseJsonAttr($row, 'data-auto');
        $('#mont-vs-diagram-sub').text($row.data('fit') + ' / ' + $row.data('size'));
        renderDiagramSlots();
        $('#mont-vs-diagram-modal').prop('hidden', false);
        $('body').addClass('mont-vs-modal-open');
    }

    function closeDiagramModal() {
        $('#mont-vs-diagram-modal').prop('hidden', true);
        $('body').removeClass('mont-vs-modal-open');
        $activeDiagramRow = null;
    }

    function renderDiagramSlots() {
        var html = '';
        var labels = variationSettings.labels || {};
        diagramKeys().forEach(function (key) {
            var custom = draftOverrides[key] || '';
            var auto = draftAuto[key] || '';
            var shown = custom || auto || '';
            var source = custom ? 'custom' : (auto ? 'auto' : 'missing');
            var badge = source === 'custom'
                ? (variationSettings.i18n.custom || 'Custom')
                : (source === 'auto' ? (variationSettings.i18n.auto || 'Auto') : (variationSettings.i18n.missing || 'Missing'));
            html += '<div class="mont-vs-slot" data-key="' + key + '">' +
                '<div class="mont-vs-slot__preview">' +
                    (shown
                        ? '<img src="' + shown + '" alt="">'
                        : '<div class="mont-vs-slot__empty">No image</div>') +
                '</div>' +
                '<div class="mont-vs-slot__meta">' +
                    '<strong>' + (labels[key] || key) + '</strong>' +
                    '<span class="mont-vs-badge ' + source + '">' + badge + '</span>' +
                    '<div class="mont-vs-slot__actions">' +
                        '<button type="button" class="button button-small mont-vs-slot-upload">' +
                            (variationSettings.i18n.upload || 'Upload / Replace') +
                        '</button>' +
                        (custom
                            ? '<button type="button" class="button button-small mont-vs-slot-clear">' +
                                (variationSettings.i18n.clear || 'Use auto') +
                              '</button>'
                            : '') +
                    '</div>' +
                '</div>' +
            '</div>';
        });
        $('#mont-vs-diagram-slots').html(html);
    }

    function openMediaPicker(key) {
        mediaTargetKey = key;

        if (typeof wp === 'undefined' || !wp.media) {
            toast((variationSettings.i18n && variationSettings.i18n.noMedia) || 'Media library unavailable', true);
            return;
        }

        // Keep WP media above our custom modal.
        $('#mont-vs-diagram-modal').addClass('is-picking-media');

        if (mediaFrame) {
            mediaFrame.open();
            return;
        }

        mediaFrame = wp.media({
            title: 'Select diagram image',
            button: { text: 'Use this image' },
            multiple: false,
            library: { type: 'image' }
        });

        mediaFrame.on('select', function () {
            var selection = mediaFrame.state().get('selection');
            if (!selection || !selection.first()) return;
            var attachment = selection.first().toJSON();
            if (attachment && attachment.url && mediaTargetKey) {
                draftOverrides[mediaTargetKey] = attachment.url;
                renderDiagramSlots();
            }
            $('#mont-vs-diagram-modal').removeClass('is-picking-media');
        });

        mediaFrame.on('close', function () {
            $('#mont-vs-diagram-modal').removeClass('is-picking-media');
        });

        mediaFrame.on('escape', function () {
            $('#mont-vs-diagram-modal').removeClass('is-picking-media');
        });

        mediaFrame.open();
    }

    $(document).on('click', '.mont-vs-edit-diagrams', function (e) {
        e.preventDefault();
        e.stopPropagation();
        openDiagramModal($(this).closest('.mont-vs-row'));
    });

    $(document).on('click', '[data-close-diagrams]', function () {
        closeDiagramModal();
    });

    $(document).on('click', '.mont-vs-slot-upload', function (e) {
        e.preventDefault();
        e.stopPropagation();
        var key = $(this).closest('.mont-vs-slot').data('key');
        openMediaPicker(key);
    });

    $(document).on('click', '.mont-vs-slot-clear', function (e) {
        e.preventDefault();
        e.stopPropagation();
        var key = $(this).closest('.mont-vs-slot').data('key');
        delete draftOverrides[key];
        renderDiagramSlots();
    });

    // Stop backdrop clicks while media library is open.
    $(document).on('click', '#mont-vs-diagram-modal.is-picking-media .mont-vs-modal__backdrop', function (e) {
        e.preventDefault();
        e.stopPropagation();
    });

    $('#mont-vs-diagram-apply').on('click', function () {
        if (!$activeDiagramRow || !$activeDiagramRow.length) {
            closeDiagramModal();
            return;
        }
        var cleaned = {};
        Object.keys(draftOverrides).forEach(function (k) {
            if (draftOverrides[k]) cleaned[k] = draftOverrides[k];
        });
        $activeDiagramRow.attr('data-overrides', JSON.stringify(cleaned));
        $activeDiagramRow.addClass('is-dirty');

        // Refresh thumbs preview in the table cell
        var merged = $.extend({}, draftAuto, cleaned);
        var urls = Object.keys(merged).map(function (k) { return merged[k]; }).filter(Boolean);
        var $cell = $activeDiagramRow.find('.mont-vs-thumbs');
        var thumbs = '';
        urls.slice(0, 4).forEach(function (u) {
            thumbs += '<img src="' + u + '" alt="">';
        });
        if (urls.length) {
            thumbs += '<span class="mont-vs-badge ok">' + urls.length + ' linked</span>';
        } else {
            thumbs += '<span class="mont-vs-badge warn">No Size/ match</span>';
        }
        var customCount = Object.keys(cleaned).length;
        if (customCount) {
            thumbs += '<span class="mont-vs-badge custom">' + customCount + ' custom</span>';
        }
        thumbs += '<button type="button" class="button button-small mont-vs-edit-diagrams">' +
            (urls.length ? 'Replace / Upload' : 'Upload diagrams') + '</button>';
        $cell.html(thumbs);

        closeDiagramModal();
        toast('Diagrams applied — click Save all changes to store them.');
    });

    // Keyboard shortcut: Cmd/Ctrl+S
    $(document).on('keydown', function (e) {
        if ((e.metaKey || e.ctrlKey) && e.key === 's') {
            e.preventDefault();
            $('#mont-vs-save-bulk').trigger('click');
        }
        if (e.key === 'Escape' && !$('#mont-vs-diagram-modal').prop('hidden') && !$('#mont-vs-diagram-modal').hasClass('is-picking-media')) {
            closeDiagramModal();
        }
    });
});
