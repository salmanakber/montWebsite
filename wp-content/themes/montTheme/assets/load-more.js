/**
 * WooCommerce Load More Products with Infinite Scroll
 */
(function($) {
    'use strict';
    
    // Initialize when document is ready
    $(document).ready(function() {
        
		
        // Variables
        var $button = $('.mont-load-more-button');
        var $container = $('.products'); // WooCommerce products container
        var loading = true;
        var currentPage = mont_load_more_params.current_page;

        var maxPage = mont_load_more_params.max_page;
        var infiniteScroll = true; // Set to false if you want button only
		
        
        // Hide default pagination if it exists
        $('.woocommerce-pagination').hide();
        
        // Load more button click event
        // $button.on('click', function() {

            
        //     loadMoreProducts();
        // });
        
        // Infinite scroll
        if (infiniteScroll) {
            $(window).on('scroll', function() {
           
                // Check if button is visible in viewport
                var buttonOffset = $button.offset().top;
                var windowHeight = $(window).height();
                var scrollY = $(window).scrollTop();
                
                if (scrollY + windowHeight > buttonOffset - 200) {
					 loadMoreProducts()
                }
            });
        }
        
        // Function to load more products
    function loadMoreProducts() {
    if (loading || currentPage >= maxPage) return;
    loading = true;

    // Add loading class
    $button.addClass('loading');
    $container.addClass('mont-products-loading');

    var catId = 0;
    if ($('body').hasClass('tax-product_cat')) {
        catId = $('body').data('category-id') || 0;
    }

    $.ajax({
        url: mont_load_more_params.ajaxurl,
        type: 'POST',
        data: {
            action: 'mont_load_more_products',
            nonce: mont_load_more_params.nonce,
            page: currentPage + 1, // Send next page
            cat_id: catId
        },
        success: function(response) {
            if (response.html) {
                var $newProducts = $(response.html);
                $newProducts.css('opacity', 0);
                $container.append($newProducts);

                setTimeout(function() {
                    $newProducts.animate({opacity: 1}, 300);
                }, 100);

                currentPage++; // Increment after successful response

                if (currentPage >= response.max_page) {
                    $button.replaceWith('<span class="mont-no-more-products">' + mont_load_more_params.no_more_text + '</span>');
                }
            }
        },
        error: function(xhr, status, error) {
            console.log('Error loading products:', error);
        },
        complete: function() {
            $button.removeClass('loading');
            $container.removeClass('mont-products-loading');
            loading = false;
        }
    });
}

        
        // Add category ID to body for AJAX requests
        if ($('body').hasClass('tax-product_cat')) {
            var categoryId = 0;
            var bodyClasses = $('body').attr('class').split(' ');
            
            $.each(bodyClasses, function(index, className) {
                if (className.indexOf('term-') === 0) {
                    categoryId = className.replace('term-', '');
                    return false;
                }
            });
            
            $('body').attr('data-category-id', categoryId);
        }
    });
    
})(jQuery);