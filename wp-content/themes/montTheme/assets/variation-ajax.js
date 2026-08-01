jQuery(document).ready(function ($) {
    $(".pa_body-fit-option").on("click", function () {
        var slug = $(this).data('slug');
        var selectedValue =  $(this).find(".tobeSelected").text();
        var attributes = $(this).parents('.mont_variation-group').find('.mont_variation-header').data('attribute-key');
    let a = 1; // Start counter
    var pid = $(this).data('id');
    var letThis = $(this);
    $('.pa_body-fit-option').find('.mont_checkbox_select').attr('checked', false);
   $('.pa_body-fit-option').find('.mont_checkbox_select').val('');
    // letThis.find('.mont_checkbox_select').attr('checked', true);
    letThis.find('.mont_checkbox_select').val(slug);
    letThis.parents('.mont_variation-group').addClass('mont_loading');
    $('.pa_body-fit .dpName').html('<b>' + letThis.find('.tobeSelected').text() + '</b>');
    if (attributes) {
        $.ajax({
                url: ajaxurl.url, // WordPress AJAX URL
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
    let validSizes = response.data.map((item) => item.attributes.attribute_pa_size.toString());


    let $items = $('.pa_size .mont_option-item');

    // First, hide all items that are not in validSizes
    $items.each(function () {
        let listSlug = $(this).data("slug").toString();
        if (!validSizes.includes(listSlug)) {
            $(this).hide();
        } else {
            $(this).show();
        }
    });

    // Sort numerically while keeping non-numeric values intact
    let sortedItems = $items.filter(':visible').sort(function (a, b) {
        let aSlug = $(a).data("slug");
        let bSlug = $(b).data("slug");
        // Convert numeric strings to actual numbers, keep text as is
        let numA = parseFloat(aSlug);
        let numB = parseFloat(bSlug);

        // If both are numbers, sort numerically
        if (!isNaN(numA) && !isNaN(numB)) {
            return numA - numB;
        }

        // If one is a number and the other is text, prioritize numbers first
        if (!isNaN(numA)) return -1;
        if (!isNaN(numB)) return 1;

        // If both are text, sort alphabetically
        return aSlug.localeCompare(bSlug);
    });

    // Append the sorted items back
    $('.to-be-open-pa_size').find('.mont_option-list').append(sortedItems);
}


                    letThis.parents('.mont_variation-group').removeClass('mont_loading');
                    // letThis.parents('.mont_variation-group').removeClass('mont_open');
                    letThis.parents('.pa_body-fit').find('.mont_option-list').removeClass('mont_open');
                    letThis.parents('.pa_body-fit').removeClass('mont_open');
                    $('.pa_size').find('.mont_option-list').addClass('mont_open');
                    $('.pa_size').addClass('mont_open');

                },
                error: function () {
                    // $("#variation_details").html("<p>Error fetching details</p>");
                },
            });
    } else {
        $("#variation_details").html("");
    }
});

               // Initialize selected state based on radio buttons
    $('.collar-option input[type="radio"]').each(function() {
        if ($(this).is(':checked')) {
            $(this).closest('.collar-option').addClass('selected');
        }
    });

            // Handle radio button changes — scope to the same group (collar OR cuff)
    $('.collar-option input[type="radio"]').change(function() {
        var $group = $(this).closest('.collar-options');
        $group.find('.collar-option').removeClass('selected');
        $(this).closest('.collar-option').addClass('selected');
    });

            // Make the entire option clickable
    $('.velg-snipp .collar-option').click(function(e) {
        if (!$(e.target).is('input')) {
            $(this).find('input[type="radio"]').prop('checked', true).trigger('change');
        }
				$('.velg-snipp').find(".mont_option-list").removeClass('mont_open');
	  			$('.velg-snipp').find(".mont_variation-group").removeClass('mont_open');
                 $('.velg-mansjetter').find(".mont_option-list").addClass('mont_open');
	  			$('.velg-mansjetter').find(".mont_variation-group").addClass('mont_open');
    });
	
	    $('.cup-option-click').click(function(e) {
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

    $('.pa_size .dpName').html('<b>' + $sizeItem.find('.tobeSelected').text() + '</b>');

    // Prefer selected option data-slug (checkbox value can be empty in markup)
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

    // Open collar first, then tailor section after chart loads
    $('.velg-snipp').find(".mont_option-list").addClass('mont_open');
    $('.velg-snipp').find(".mont_variation-group").addClass('mont_open');
    $sizeItem.parents('.mont_option-list').removeClass('mont_open');

    $('.skreddersydd').addClass('mont_loading');

    $.ajax({
        url: ajaxurl.url,
        type: "POST",
        dataType: "json",
        data: {
            action: "get_all_variation",
            key: chartKey,
        },
        success: function (response) {
            $('.skreddersydd').removeClass('mont_loading');

            if (!response || !response.length) {
                return;
            }

            var data = response[0];
            applyMontCustomSizeChart(data);

            // Reveal Skreddersydd with fresh values/images
            $('.skreddersydd').find(".mont_option-list").addClass('mont_open');
            $('.skreddersydd').find(".mont_variation-group").addClass('mont_open');
        },
        error: function () {
            $('.skreddersydd').removeClass('mont_loading');
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

    // Dynamic Size/ diagrams for selected fit + size
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

        $(document).on('click', '.mont_option-list li', function(){
        if($(this).hasClass('pa_body-fit-option'))
        {   
            $('.pa_body-fit-option').find('.mont_checkbox_select').prop('checked', false)
            $(this).find('.mont_checkbox_select').prop('checked', true)
			$('.pa_body-fit').css({
            'background': '#b0b0b0',
            'color': 'white'
        });
        }
        if($(this).hasClass('pa_size-option'))
        {   
            $('.pa_size-option').find('.mont_checkbox_select').prop('checked', false)
            $('.pa_size-option').find('.mont_checkbox_select').val('')
            $(this).find('.mont_checkbox_select').prop('checked', true)
            $(this).find('.mont_checkbox_select').val($(this).find('.tobeSelected').text());
			$('.pa_size').css({
            'background': '#b0b0b0',
            'color': 'white'
        });
        }
    })
$('.radioTocheck').click(function() {
    // Find the selected radio button value
    var selectedValue = $(this).find('input[type="radio"]:checked').val();
    // Update the .skname text with the selected radio button value
    $(this).parents('.mont_variation-group').find('.skname b').html(selectedValue);
});
	
// 	    var selectedValue = $('.radioTocheck').find('input[type="radio"]:checked').val();
//     // Update the .skname text with the selected radio button value
//     $('.radioTocheck').parents('.mont_variation-group').find('.skname b').html(selectedValue);



    






	
	
	
	
});
