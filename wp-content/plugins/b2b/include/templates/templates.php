<?php
ob_start();
/*
Template Name: Monte Connected B2B
*/

?>
<div id="primary" class="content-area">
    <main id="main" class="site-main" role="main">

        {{data}}

    </main>
    <script type="text/javascript">
        jQuery(document).ready(function($){
            checkDevice();
            $(window).resize(function(){
                checkDevice();
            });

            function checkDevice() {
                var width = $(window).width();

                // Assuming a width of 768 pixels or less represents a mobile device
                if (width <= 768) {
                    // You can pass this information to PHP using AJAX or by setting a cookie
                    // For simplicity, let's just redirect to a different PHP page for mobile view

                    $('#b2bmenu').addClass('mobile-view-b2b-tabs loop owl-carousel owl-theme');
                   $('#b2bmenu').find('.nav-item-b2b').addClass('item');

                } else {
                    $('#b2bmenu').removeClass('mobile-view-b2b-tabs loop owl-carousel owl-theme');
                    $('#b2bmenu').find('.nav-item-b2b').removeClass('item');
                }
            }

            $('.tab-pane-monte-b2b').not(':first').hide();

    // Add click event handler for tab navigation
            $('.nav-link-monte-b2b').on('click', function(e) {
                e.preventDefault();

        // Remove active class from all tabs
                $('.nav-link-monte-b2b').removeClass('active');

        // Add active class to the clicked tab
                $(this).addClass('active');

        // Hide all tab contents
                $('.tab-pane-monte-b2b').hide();

        // Show the corresponding tab content
                var target = $(this).data('bs-target');
                $(target).show();
                $(target).css('display','block');
            });
        });
    </script>
</div>
