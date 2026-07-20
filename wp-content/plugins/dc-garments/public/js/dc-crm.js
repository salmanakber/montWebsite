jQuery(document).ready(function($) {
    'use strict';
    if (typeof dc_crm === 'undefined') {
        console.error('dc_crm object is not properly initialized');
        return;
    }

    // Handle search functionality
    var searchTimer;
    $('.dc-crm-search input').on('keyup', function() {
        clearTimeout(searchTimer);
        var query = $(this).val();
        
        searchTimer = setTimeout(function() {
            $.ajax({
                url: dc_crm.ajax_url,
                type: 'POST',
                data: {
                    action: 'dc_crm_search',
                    query: query,
                    nonce: dc_crm.nonce
                },
                beforeSend: function() {
                    $('.dc-crm-main').addClass('loading');
                },
                success: function(response) {
                    if (response.success) {
                        $('.dc-crm-main').html(response.data.content);
                    }
                },
                complete: function() {
                    $('.dc-crm-main').removeClass('loading');
                }
            });
        }, 500);
    });

    // Load products on page load if we're on the products tab
    if ($('.dc-crm-nav li.products').hasClass('active')) {
        loadProducts();
    }

    function loadProducts() {
        $.ajax({
            url: dc_crm.ajax_url,
            type: 'POST',
            data: {
                action: 'dc_get_products',
                nonce: dc_crm.nonce
            },
            success: function(response) {
                if (response.success) {
                    displayProducts(response.data);
                } else {
                    console.error('Error loading products:', response.data);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', error);
            }
        });
    }

    function displayProducts(products) {
        var $productList = $('#dc-product-list-body');
        if (!products || products.length === 0) {
            $productList.html('<div class="dc-no-products">No products found.</div>');
            return;
        }

        var html = '<div class="dc-product-grid">';
        products.forEach(function(product) {
            html += '<div class="dc-product-card">';
            html += '<div class="dc-product-image">';
            if (product.image) {
                html += '<img src="' + product.image + '" alt="' + product.title + '">';
            } else {
                html += '<div class="dc-no-image">No image</div>';
            }
            html += '</div>';
            html += '<div class="dc-product-info">';
            html += '<h3>' + product.title + '</h3>';
            html += '<div class="dc-product-meta">';
            html += '<span class="dc-product-sku">SKU: ' + product.sku + '</span>';
            html += '<span class="dc-product-price">' + product.price + '</span>';
            html += '<span class="dc-product-stock">Stock: ' + product.stock + '</span>';
            html += '</div>';
            html += '<div class="dc-product-actions">';
            html += '<a href="' + dc_crm.site_url + 'crm/product/' + product.id + '/edit" class="button">Edit</a>';
            html += '</div>';
            html += '</div>';
            html += '</div>';
        });
        html += '</div>';
        $productList.html(html);
    }
}); 