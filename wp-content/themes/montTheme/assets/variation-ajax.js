jQuery(document).ready(function ($) {

    function montShowLoader($el, message) {
        if (!$el || !$el.length) return;
        $el.addClass('mont_loading');
        if (!$el.children('.mont_loading-label').length) {
            $el.append('<span class="mont_loading-label"></span>');
        }
        $el.children('.mont_loading-label').text(message || 'Laster…');
    }

    function montHideLoader($el) {
        if (!$el || !$el.length) return;
        $el.removeClass('mont_loading');
        $el.children('.mont_loading-label').remove();
    }

    $(".pa_body-fit-option").on("click", function () {
        var slug = $(this).data('slug');
        var selectedValue = $(this).find(".tobeSelected").text();
        var attributes = $(this).parents('.mont_variation-group').find('.mont_variation-header').data('attribute-key');
        var pid = $(this).data('id');
        var letThis = $(this);
        var $fitGroup = letThis.parents('.mont_variation-group');
        var $sizeGroup = $('.pa_size');

        $('.pa_body-fit-option').find('.mont_checkbox_select').attr('checked', false);
        $('.pa_body-fit-option').find('.mont_checkbox_select').val('');
        letThis.find('.mont_checkbox_select').val(slug);
        $('.pa_body-fit .dpName').html('<b>' + letThis.find('.tobeSelected').text() + '</b>');

        montShowLoader($fitGroup, 'Laster størrelser…');
        montShowLoader($sizeGroup, 'Oppdaterer størrelser…');

        if (attributes) {
            $.ajax({
                url: ajaxurl.url,
                type: "POST",
                data: {
                    action: "get_variation_details",
                    attributes: attributes,
                    product_id: pid,
                    selected: selectedValue,
                    slugValue: slug
                },
                success: function (response) {
                    if (response.success) {
                        var validSizes = response.data.map(function (item) {
                            return item.attributes.attribute_pa_size.toString();
                        });
                        var $items = $('.pa_size .mont_option-item');

                        $items.each(function () {
                            var listSlug = $(this).data("slug").toString();
                            if (!validSizes.includes(listSlug)) {
                                $(this).hide();
                            } else {
                                $(this).show();
                            }
                        });

                        var sortedItems = $items.filter(':visible').sort(function (a, b) {
                            var aSlug = $(a).data("slug");
                            var bSlug = $(b).data("slug");
                            var numA = parseFloat(aSlug);
                            var numB = parseFloat(bSlug);

                            if (!isNaN(numA) && !isNaN(numB)) {
                                return numA - numB;
                            }
                            if (!isNaN(numA)) return -1;
                            if (!isNaN(numB)) return 1;
                            return aSlug.localeCompare(bSlug);
                        });

                        $('.to-be-open-pa_size').find('.mont_option-list').append(sortedItems);
                    }

                    montHideLoader($fitGroup);
                    montHideLoader($sizeGroup);
                    letThis.parents('.pa_body-fit').find('.mont_option-list').removeClass('mont_open');
                    letThis.parents('.pa_body-fit').removeClass('mont_open');
                    $('.pa_size').find('.mont_option-list').addClass('mont_open');
                    $('.pa_size').addClass('mont_open');
                },
                error: function () {
                    montHideLoader($fitGroup);
                    montHideLoader($sizeGroup);
                }
            });
        } else {
            montHideLoader($fitGroup);
            montHideLoader($sizeGroup);
            $("#variation_details").html("");
        }
    });

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
        var $sizeGroup = $sizeItem.parents('.mont_variation-group');
        var $tailor = $('.skreddersydd');

        $('.pa_size .dpName').html('<b>' + $sizeItem.find('.tobeSelected').text() + '</b>');

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

        montShowLoader($sizeGroup, 'Laster mål…');
        montShowLoader($tailor, 'Laster skreddersøm…');

        $.ajax({
            url: ajaxurl.url,
            type: "POST",
            dataType: "json",
            data: {
                action: "get_all_variation",
                key: chartKey
            },
            success: function (response) {
                montHideLoader($sizeGroup);
                montHideLoader($tailor);

                if (!response || !response.length) {
                    return;
                }

                applyMontCustomSizeChart(response[0]);

                $tailor.find(".mont_option-list").addClass('mont_open');
                $tailor.find(".mont_variation-group").addClass('mont_open');
            },
            error: function () {
                montHideLoader($sizeGroup);
                montHideLoader($tailor);
            }
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
                if (!imageMap[montKey]) return;
                var $img = $('.mont_sizes-measurement-item[data-mont-size="' + montKey + '"] .mont_sizes-measurement-icon');
                if ($img.length) {
                    $img.attr("src", imageMap[montKey]).attr("data-dynamic", "1");
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
        var selectedValue = $(this).find('input[type="radio"]:checked').val();
        $(this).parents('.mont_variation-group').find('.skname b').html(selectedValue);
    });
});
