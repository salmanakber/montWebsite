(function($) {
    'use strict';
    
    $(document).ready(function() {
        const searchInput = $('.mont-search-input');
        const resultsContainer = $('.mont-search-results');
        let searchTimeout = null;
        
        // Handle input changes
        searchInput.on('input', function() {
            const query = $(this).val();
            
            // Clear previous timeout
            if (searchTimeout) {
                clearTimeout(searchTimeout);
            }
            
            // Hide results if query is empty
            if (query.length < 2) {
                resultsContainer.removeClass('mont-search-active');
                return;
            }
            
            // Show loading indicator
            resultsContainer.html('<div class="mont-search-loading">Searching...</div>');
            resultsContainer.addClass('mont-search-active');
            
            // Set timeout to prevent too many requests
            searchTimeout = setTimeout(function() {
                $.ajax({
                    url: montSearch.ajax_url,
                    type: 'post',
                    data: {
                        action: 'mont_search_products',
                        nonce: montSearch.nonce,
                        query: query
                    },
                    success: function(response) {
                        if (response.success) {
                            if (response.data.products.length > 0) {
                                let html = '';
                                
                                response.data.products.forEach(function(product) {
                                    html += '<a href="' + product.permalink + '" class="mont-search-item">';
                                    html += '<img src="' + product.image + '" class="mont-search-item-image" alt="' + product.title + '">';
                                    html += '<div class="mont-search-item-content">';
                                    html += '<div class="mont-search-item-title">' + product.title + '</div>';
                                    html += '<div class="mont-search-item-price">' + product.price + '</div>';
                                    html += '</div>';
                                    html += '</a>';
                                });
                                
                                resultsContainer.html(html);
                            } else {
                                resultsContainer.html('<div class="mont-search-no-results">' + montSearch.no_results + '</div>');
                            }
                        } else {
                            resultsContainer.html('<div class="mont-search-no-results">Error loading results</div>');
                        }
                    },
                    error: function() {
                        resultsContainer.html('<div class="mont-search-no-results">Error loading results</div>');
                    }
                });
            }, 300);
        });
        
        // Close results when clicking outside
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.mont-search-container').length) {
                resultsContainer.removeClass('mont-search-active');
            }
        });
        
        // Prevent form submission on enter
        searchInput.closest('form').on('submit', function(e) {
            const query = searchInput.val();
            if (query.length < 2) {
                e.preventDefault();
            }
        });
    });
})(jQuery);