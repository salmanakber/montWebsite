jQuery(document).ready(function($) {
	
	
	
	
	
	
    // Handle wishlist toggle
    $(document).on('click', '.wishlist-toggle', function(e) {
        e.preventDefault();
        e.stopPropagation();
		e.stopImmediatePropagation();
		
        
        const $button = $(this);
        const productId = $button.data('product-id');

        // Optimistically toggle class for instant feedback
        $button.toggleClass('in-wishlist');

        $.ajax({
            url: ajaxurl.url, // WordPress AJAX URL
            type: 'POST',
            data: {
                action: 'handle_wishlist_ajax',
                product_id: productId
            },
            success: function(response) {
                if (response.success) {
                    if (response.data.status === 'added') {
                        $button.addClass('in-wishlist'); // Ensure class is added
                    } else {
                        $button.removeClass('in-wishlist'); // Ensure class is removed
                    }
                } else {
                    // Revert class toggle if response isn't successful
                    $button.toggleClass('in-wishlist');
                }
            },
            error: function() {
                console.log('Error updating wishlist');
                // Revert class toggle on AJAX error
                $button.toggleClass('in-wishlist');
            }
        });
			return false; 
    });

    // Optional: Add hover effect for gallery images
    $('.product-image-wrapper').hover(
        function() {
            $(this).find('.hover-image').css('opacity', '1');
            $(this).find('.main-image').css('opacity', '0');
        },
        function() {
            $(this).find('.hover-image').css('opacity', '0');
            $(this).find('.main-image').css('opacity', '1');
        }
    );

     $(window).on('beforeunload', function() {
                sessionStorage.setItem('mont_previousPageTitle', document.title);
            });
            
            // Function to get the previous page title
            function getPreviousPageTitle() {
                // Try to get the stored title first
                const storedTitle = sessionStorage.getItem('mont_previousPageTitle');
                if (storedTitle) {
                    return storedTitle;
                }
                
                // If no stored title, try to get a friendly name from the referrer
                if (document.referrer) {
                    try {
                        const urlObj = new URL(document.referrer);
                        // Convert URL path to a friendly name
                        let pathName = urlObj.pathname
                            .split('/')
                            .filter(segment => segment) // Remove empty segments
                            .pop() // Get the last segment
                            || 'Home'; // Default to 'Home' if it's the root
                        
                        // Convert kebab-case or snake_case to Title Case
                        pathName = pathName
                            .replace(/[-_]/g, ' ')
                            .replace(/\b\w/g, letter => letter.toUpperCase())
                            .replace(/\..*$/, ''); // Remove file extension if any
                        
                        return pathName;
                    } catch(e) {
                        return "Previous Page";
                    }
                }
                
                return "No previous page";
            }
            
            // Set the popover content
            $("#mont_prevPageTitle").text(getPreviousPageTitle());
            
            // Handle click on back button
            $("#mont_backButton").click(function() {
                window.history.back();
            });
            
            // Optional: Enhanced hover effects
            $("#mont_backButton").hover(
                function() {
                    $("#mont_prevPagePopover").css({
                        "opacity": "1",
                        "visibility": "visible",
                        "transform": "translateX(0)"
                    });
                },
                function() {
                    $("#mont_prevPagePopover").css({
                        "opacity": "0",
                        "visibility": "hidden",
                        "transform": "translateX(20px)"
                    });
                }
            );
});
