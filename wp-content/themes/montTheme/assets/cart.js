jQuery(document).ready(function ($) {
$('.custom-add-to-cart').on('click', function (e) {
        e.preventDefault();
        letThis = $(this);

        let product_id = $(this).data('product_id');
        let body_fit = $('input.pa_body-fit-checkbox:checked').val() || '';
        let size = $('input.pa_size-checkbox:checked').val() || '';
        let collar_type = $('input[name=collar-style]:checked').val() || '';
        let cup_type = $('input[name=cup-style]:checked').val() || '';

        let isValid = true;

        // Validate Body Fit
        if (!body_fit) {
            $('.pa_body-fit').css('border', '0.5px solid #ff000036'); // Add red border
            isValid = false;
        } else {
            $('.pa_body-fit').css('border', ''); // Remove red border
        }

        // Validate Size
        if (!size) {
            $('.pa_size').css('border', '0.5px solid #ff000036'); // Add red border
            isValid = false;
        } else {
            $('.pa_size').css('border', ''); // Remove red border
        }

        // Stop AJAX if validation fails
        if (!isValid) return;

        let formData = {};
$('#customizationForm input[type="hidden"][clicked="true"]').each(function () {
    // Convert the input name to a readable key
    let key = $(this).attr('name').replace(/_/g, ' ');
    key = key.replace(/\b\w/g, l => l.toUpperCase());

    // Add to formData
    formData[key] = $(this).val();
});

        letThis.addClass('dloader');

        $.ajax({
            type: 'POST',
            url: ajaxurl.url, // WordPress AJAX URL
            data: {
                action: 'custom_add_to_cart',
                product_id: product_id,
                body_fit: body_fit,
                size: size,
                collar_type: collar_type,
                cup_type: cup_type,
                form_data: formData,
				added_price: $("#added-price").val()
            },
            success: function (response) {
				console.log(response);
                letThis.removeClass('dloader');
                updateCartCount();
                window.location.href="cart";

            }
        });
    });

        function updateCartCount() {
        $.ajax({
            type: "POST",
            url: ajaxurl.url,
            data: { action: "update_cart_count" },
            success: function(response) {
                $(".mont_header_cart-counter").text(response.data.count); // Update badge with new count
            }
        });
    }
	
	
function calculateTotal(extraValue = 0) {
    let count = 0;

    $("input[name^='mont_sizes[']")
        .filter(function () {
            return !["mont_sizes[sleeve_length_left]", "mont_sizes[sleeve_length_right]", "mont_sizes[shirt_length]"].includes($(this).attr("name"));
        })
        .each(function () {
			if($(this).attr('clicked') == 'true'  && $(this).val() != $(this).data('value'))
				{
            if ($.trim($(this).val()) > 0 ) { // Only count non-empty fields
                count++;
            }
				}
        });

    let total = (count * 10) + extraValue; // Each valid field = 10 USD
    return total;
}
	function formatMoney(amount) {
    return amount.toLocaleString("de-DE", { minimumFractionDigits: 2 }) + " NOK";
}
	
$(document).on("click", ".mont_sizes-control-btn.mont_sizes-plus, .mont_sizes-control-btn.mont_sizes-minus", function () {    
	$(this).parents('.mont_sizes-controls').find('input').attr('clicked', 'true')
    let productPrice = parseFloat($("#actual-price").val()) || 0; // Ensure a valid number
    let totalPrice = (calculateTotal() + productPrice); // Ensure calculateTotal() returns a valid number
	$("#added-price").val(calculateTotal());
	$(".mont_product-price")
    .find("span bdi")
    .text(totalPrice.toFixed(2).replace(".", ",") + " NOK");
});



});
