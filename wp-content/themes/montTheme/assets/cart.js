jQuery(document).ready(function ($) {

    var sizeLabels = {
        'mont_sizes[shirt_length]': 'Skjortelengde',
        'mont_sizes[sleeve_length_left]': 'Ermelengde (Venstre)',
        'mont_sizes[sleeve_length_right]': 'Ermelengde (Høyre)',
        'mont_sizes[waist]': 'Midje',
        'mont_sizes[chest]': 'Bryststørrelse',
        'mont_sizes[half_bottom]': 'Nederst kant',
        'mont_sizes[shoulder]': 'Skulder'
    };

    function getSelectedBodyFit() {
        var $checked = $('input.pa_body-fit-checkbox:checked');
        if ($checked.length) {
            var label = $.trim($checked.closest('.mont_option-item').find('.tobeSelected').text());
            return label || $checked.val() || '';
        }
        var dp = $.trim($('.pa_body-fit .dpName b').text());
        return dp || '';
    }

    function getSelectedSize() {
        var $checked = $('input.pa_size-checkbox:checked');
        if ($checked.length) {
            var label = $.trim($checked.closest('.mont_option-item').find('.tobeSelected').text());
            return label || $checked.val() || '';
        }
        var dp = $.trim($('.pa_size .dpName b').text());
        return dp || '';
    }

    function getSelectedCollar() {
        var $radio = $('input[name="collar-style"]:checked');
        if (!$radio.length) return '';
        // Prefer visible name text on the option
        var name = $.trim($radio.closest('.collar-option').find('.collar-name').first().text());
        return name || $radio.val() || '';
    }

    function getSelectedCuff() {
        var $radio = $('input[name="cuff-style"]:checked');
        if (!$radio.length) return '';
        var name = $.trim($radio.closest('.collar-option').find('.collar-name').first().text());
        return name || $radio.val() || '';
    }

    /**
     * Collect all custom sizing values currently shown to the customer.
     * Sends every measurement with a positive value (chart default or adjusted).
     */
    function getCustomSizingData() {
        var formData = {};
        $('#customizationForm input[name^="mont_sizes["]').each(function () {
            var name = $(this).attr('name');
            var val = $.trim($(this).val());
            if (!val || val === '0') return;

            var label = sizeLabels[name] || name
                .replace('mont_sizes[', '')
                .replace(']', '')
                .replace(/_/g, ' ')
                .replace(/\b\w/g, function (l) { return l.toUpperCase(); });

            // Mark customized vs chart default for admin clarity
            var isCustomized = $(this).attr('clicked') === 'true' &&
                String($(this).val()) !== String($(this).data('value'));

            formData[label] = val + (isCustomized ? ' cm (tilpasset)' : ' cm');
        });
        return formData;
    }

    $('.custom-add-to-cart').on('click', function (e) {
        e.preventDefault();
        var letThis = $(this);

        var product_id = $(this).data('product_id');
        var body_fit = getSelectedBodyFit();
        var size = getSelectedSize();
        var collar_type = getSelectedCollar();
        var cuff_type = getSelectedCuff();
        var formData = getCustomSizingData();

        var isValid = true;

        if (!$('input.pa_body-fit-checkbox:checked').length && !body_fit) {
            $('.pa_body-fit').css('border', '0.5px solid #ff000036');
            isValid = false;
        } else {
            $('.pa_body-fit').css('border', '');
        }

        if (!$('input.pa_size-checkbox:checked').length && !size) {
            $('.pa_size').css('border', '0.5px solid #ff000036');
            isValid = false;
        } else {
            $('.pa_size').css('border', '');
        }

        if (!isValid) return;

        letThis.addClass('dloader');

        $.ajax({
            type: 'POST',
            url: ajaxurl.url,
            data: {
                action: 'custom_add_to_cart',
                product_id: product_id,
                body_fit: body_fit,
                size: size,
                collar_type: collar_type,
                cuff_type: cuff_type,
                form_data: formData,
                added_price: $('#added-price').val() || 0
            },
            success: function (response) {
                letThis.removeClass('dloader');
                if (response && response.success) {
                    updateCartCount();
                    window.location.href = '/cart';
                } else {
                    alert((response && response.data && response.data.message) || 'Could not add to cart.');
                }
            },
            error: function () {
                letThis.removeClass('dloader');
                alert('Could not add to cart. Please try again.');
            }
        });
    });

    function updateCartCount() {
        $.ajax({
            type: 'POST',
            url: ajaxurl.url,
            data: { action: 'update_cart_count' },
            success: function (response) {
                if (response && response.data && typeof response.data.count !== 'undefined') {
                    $('.mont_header_cart-counter').text(response.data.count);
                }
            }
        });
    }

    function calculateTotal(extraValue) {
        extraValue = extraValue || 0;
        var count = 0;

        $("input[name^='mont_sizes[']")
            .filter(function () {
                return !['mont_sizes[sleeve_length_left]', 'mont_sizes[sleeve_length_right]', 'mont_sizes[shirt_length]'].includes($(this).attr('name'));
            })
            .each(function () {
                if ($(this).attr('clicked') == 'true' && $(this).val() != $(this).data('value')) {
                    if ($.trim($(this).val()) > 0) {
                        count++;
                    }
                }
            });

        return (count * 10) + extraValue;
    }

    function formatMoney(amount) {
        var currency = (typeof dc_region !== 'undefined' && dc_region.regions && dc_region.currentRegion)
            ? (dc_region.regions[dc_region.currentRegion] || {}).currency
            : 'NOK';
        currency = currency || 'NOK';
        try {
            return new Intl.NumberFormat('nb-NO', {
                style: 'currency',
                currency: currency,
                minimumFractionDigits: currency === 'VND' ? 0 : 2
            }).format(amount);
        } catch (e) {
            return amount.toLocaleString('de-DE', { minimumFractionDigits: 2 }) + ' ' + currency;
        }
    }

    $(document).on('click', '.mont_sizes-control-btn.mont_sizes-plus, .mont_sizes-control-btn.mont_sizes-minus', function () {
        $(this).parents('.mont_sizes-controls').find('input').attr('clicked', 'true');
        var productPrice = parseFloat($('#actual-price').val()) || 0;
        var totalPrice = calculateTotal() + productPrice;
        $('#added-price').val(calculateTotal());
        $('.mont_product-price').find('span bdi').text(totalPrice.toFixed(2).replace('.', ',') + ' NOK');
    });
});
