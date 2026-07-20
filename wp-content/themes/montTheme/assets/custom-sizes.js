jQuery(document).ready(function($) {
            // Toggle additional measurements
    $('.mont_sizes-toggle-more').click(function(e) {
        e.preventDefault();
        $('.mont_sizes-additional-measurements').toggleClass('mont_sizes-hidden');
        const isHidden = $('.mont_sizes-additional-measurements').hasClass('mont_sizes-hidden');
        $(this).text(isHidden ? $(this).data('show-text') : $(this).data('hide-text'));
    });

            // Handle change button click
    $('.mont_sizes-change-btn, .mont_sizes-close-btn').click(function(e) {
        e.preventDefault();
        const item = $(this).closest('.mont_sizes-measurement-item');
        const controls = item.find('.mont_sizes-controls');

        if ($(this).hasClass('mont_sizes-close-btn')) {
            item.removeClass('mont_sizes-active');
            controls.removeClass('active');
        } else {
                    // Close other open controls
            $('.mont_sizes-measurement-item').not(item).removeClass('mont_sizes-active');
            $('.mont_sizes-controls').not(controls).removeClass('active');

                    // Toggle current controls
            item.addClass('mont_sizes-active');
            controls.addClass('active');
        }
    });

            // Handle plus/minus buttons
    $('.mont_sizes-control-btn').click(function() {
        const isPlus = $(this).hasClass('mont_sizes-plus');
        const measurementItem = $(this).closest('.mont_sizes-measurement-item');
        const measurementValue = measurementItem.find('.mont_sizes-measurement-value');
        const hiddenInput = measurementItem.find('.mont_sizes-hidden-input');
        $('.mont_alert').show();

        if (measurementItem.data('mont-size') === 'sleeve_length') {
            const side = $(this).data('side');
            const valueSpan = $(this).siblings('.mont_sizes-control-value[data-side="' + side + '"]');
            let currentValue = parseInt(valueSpan.text());
            const newValue = isPlus ? currentValue + 1 : Math.max(currentValue - 1, 0);

            valueSpan.text(newValue + ' cm');

            const leftValue = $('.mont_sizes-control-value[data-side="left"]').text();
            const rightValue = $('.mont_sizes-control-value[data-side="right"]').text();
            measurementValue.text(`Left: ${leftValue}, Right: ${rightValue}`);

                    // Update hidden inputs for sleeve length
            measurementItem.find('.mont_sizes-hidden-input[name="mont_sizes[sleeve_length_left]"]').val(parseInt(leftValue));
            measurementItem.find('.mont_sizes-hidden-input[name="mont_sizes[sleeve_length_right]"]').val(parseInt(rightValue));
        } else {
            const valueSpan = $(this).siblings('.mont_sizes-control-value');
            let currentValue = parseInt(valueSpan.text());
            const newValue = isPlus ? currentValue + 1 : Math.max(currentValue - 1, 0);

            valueSpan.text(newValue + ' cm');
            measurementValue.text(`${newValue} cm`);

                    // Update hidden input
            hiddenInput.val(newValue);
        }
    });

    $(".mont_show_hide_desc_text").click(function() {
        // Toggle visibility of preview and full description
        $(".desc_preview").toggle();
        $(".desc_full").toggle();

        // Change button text based on the visibility of the full description
        if ($(".desc_full").is(":visible")) {
            $(".mont_show_hide_desc_text").text("Skjul tekst");
        } else {
            $(".mont_show_hide_desc_text").text("Les mer...");
        }
    });




});