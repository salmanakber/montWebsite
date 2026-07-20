jQuery(document).ready(function($) {
    // Title preview functionality
    function updateTitlePreview() {
        var fabricColor = $('#_fabric_color').val();
        var monteNapoleoneNo = $('#_monte_napoleone_no').val();
        var categorySelect = $('#product_cat');
        var categoryName = categorySelect.find('option:selected').text();

        if (fabricColor && monteNapoleoneNo && categoryName) {
            $.ajax({
                url: dcProductManager.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'preview_product_title',
                    nonce: dcProductManager.nonce,
                    fabric_color: fabricColor,
                    category_name: categoryName,
                    monte_napoleone_no: monteNapoleoneNo
                },
                success: function(response) {
                    if (response.success) {
                        $('#title-preview').text(response.data.title);
                    }
                }
            });
        }
    }

    // Bind title preview events
    $('#_fabric_color, #_monte_napoleone_no, #product_cat').on('change', updateTitlePreview);

    // Supplier management
    $('#add_new_supplier').on('click', function(e) {
        e.preventDefault();
        
        // Create modal HTML
        var modal = $('<div class="dc-modal">' +
            '<div class="dc-modal-content">' +
                '<h2>' + dcProductManager.i18n.addNewSupplier + '</h2>' +
                '<form id="new-supplier-form">' +
                    '<p>' +
                        '<label for="supplier_name">' + dcProductManager.i18n.supplierName + '</label>' +
                        '<input type="text" id="supplier_name" name="supplier_name" required />' +
                    '</p>' +
                    '<p>' +
                        '<label for="supplier_sku">' + dcProductManager.i18n.supplierSku + '</label>' +
                        '<input type="text" id="supplier_sku" name="supplier_sku" />' +
                    '</p>' +
                    '<p>' +
                        '<label for="supplier_quality">' + dcProductManager.i18n.supplierQuality + '</label>' +
                        '<select id="supplier_quality" name="supplier_quality">' +
                            '<option value="premium">' + dcProductManager.i18n.premium + '</option>' +
                            '<option value="standard">' + dcProductManager.i18n.standard + '</option>' +
                        '</select>' +
                    '</p>' +
                    '<p>' +
                        '<label for="supplier_fabric_width">' + dcProductManager.i18n.fabricWidth + '</label>' +
                        '<input type="number" id="supplier_fabric_width" name="supplier_fabric_width" step="0.1" min="0" />' +
                    '</p>' +
                    '<p>' +
                        '<label for="supplier_weight">' + dcProductManager.i18n.weight + '</label>' +
                        '<input type="number" id="supplier_weight" name="supplier_weight" step="1" min="0" />' +
                    '</p>' +
                    '<p>' +
                        '<label for="supplier_price">' + dcProductManager.i18n.price + '</label>' +
                        '<input type="number" id="supplier_price" name="supplier_price" step="0.01" min="0" />' +
                    '</p>' +
                    '<p class="submit">' +
                        '<button type="submit" class="button button-primary">' + dcProductManager.i18n.save + '</button>' +
                        '<button type="button" class="button dc-modal-close">' + dcProductManager.i18n.cancel + '</button>' +
                    '</p>' +
                '</form>' +
            '</div>' +
        '</div>');

        // Add modal to body
        $('body').append(modal);

        // Handle form submission
        $('#new-supplier-form').on('submit', function(e) {
            e.preventDefault();
            
            var formData = {
                action: 'create_supplier',
                nonce: dcProductManager.nonce,
                name: $('#supplier_name').val(),
                sku: $('#supplier_sku').val(),
                quality: $('#supplier_quality').val(),
                fabric_width: $('#supplier_fabric_width').val(),
                weight: $('#supplier_weight').val(),
                price: $('#supplier_price').val()
            };

            $.ajax({
                url: dcProductManager.ajaxUrl,
                type: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        // Add new supplier to dropdown
                        var option = new Option(response.data.name, response.data.id, true, true);
                        $('#_supplier_id').append(option).trigger('change');
                        
                        // Close modal
                        modal.remove();
                    } else {
                        alert(response.data);
                    }
                }
            });
        });

        // Handle modal close
        $('.dc-modal-close').on('click', function() {
            modal.remove();
        });
    });

    // Stock level updates
    $('.stock-pcs-input').on('change', function() {
        var input = $(this);
        var variationId = input.data('variation-id');
        var stockPcs = input.val();

        $.ajax({
            url: dcProductManager.ajaxUrl,
            type: 'POST',
            data: {
                action: 'update_stock_level',
                nonce: dcProductManager.nonce,
                variation_id: variationId,
                stock_pcs: stockPcs
            },
            beforeSend: function() {
                input.addClass('updating');
            },
            success: function(response) {
                if (response.success) {
                    input.removeClass('updating').addClass('updated');
                    setTimeout(function() {
                        input.removeClass('updated');
                    }, 2000);
                } else {
                    alert(response.data);
                }
            },
            error: function() {
                input.removeClass('updating');
                alert(dcProductManager.i18n.error);
            }
        });
    });

    // Price updates
    $('#_regular_price').on('change', function() {
        var price = $(this).val();
        var productId = $('#post_ID').val();

        $.ajax({
            url: dcProductManager.ajaxUrl,
            type: 'POST',
            data: {
                action: 'update_product_price',
                nonce: dcProductManager.nonce,
                product_id: productId,
                price: price
            },
            beforeSend: function() {
                $(this).addClass('updating');
            },
            success: function(response) {
                if (response.success) {
                    $(this).removeClass('updating').addClass('updated');
                    setTimeout(function() {
                        $(this).removeClass('updated');
                    }, 2000);
                } else {
                    alert(response.data);
                }
            },
            error: function() {
                $(this).removeClass('updating');
                alert(dcProductManager.i18n.error);
            }
        });
    });
}); 