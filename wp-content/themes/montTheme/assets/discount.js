jQuery(document).ready(function($) {
    console.log("Popup Status:", getCookie("subscribed_popup"));

    function setCookie(name, value, days) {
        var expires = "";
        if (days) {
            var date = new Date();
            date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
            expires = "; expires=" + date.toUTCString();
        }
        document.cookie = name + "=" + value + "; path=/" + expires;
    }

    function getCookie(name) {
        var nameEQ = name + "=";
        var ca = document.cookie.split(';');
        for (var i = 0; i < ca.length; i++) {
            var c = ca[i].trim(); // Trim spaces properly
            if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length, c.length);
        }
        return null;
    }

    // Show popup or sticky icon based on cookie
    if (!getCookie("subscribed_popup")) {
				if(getCookie('primary_closed'))
			{
				$("body").removeClass("overly-modal");
				$("#sticky-popup-btn").show();
				 $("#discount-popup").hide();
			}
		else{
        $("#discount-popup").fadeIn();
        $("body").addClass("overly-modal");
        $("#sticky-popup-btn").show();
		}
    } else {
        $("#discount-popup").hide();
        $("body").removeClass("overly-modal");
        $("#sticky-popup-btn").hide();
    }

    // Close popup and show sticky icon
    $("#close-popup").on("click", function() {
        $("#discount-popup").fadeOut();
        $("#sticky-popup-icon").fadeIn();
        $("body").removeClass("overly-modal");
		setCookie("primary_closed", "1", 30);
    });

    // Handle form submission
    $("#subscribe-form").on("submit", function(e) {
        e.preventDefault();
        var email = $("#email-input").val();
		var ThisElement= $(this);
		 ThisElement.find('.popup-button').addClass('dloader');
        $.ajax({
            url: ajaxurl.url,
            type: "POST",
            data: {
                action: "generate_coupon",
                email: email
            },
            success: function(response) {
			 ThisElement.find('.popup-button').removeClass('dloader');
                if (response.success) {
					  $(".error-code").css('style', 'color:green');
                    $(".error-code").html("Coupon code applied on all product: " + response.data.coupon);
					$('.error-code').text(response.data.message);
                    setCookie("subscribed_popup", "1", 30); // Store subscription in cookies (30 days)
                    $("#sticky-popup-btn").hide();
                    $("#discount-popup").fadeOut();
                    $("body").removeClass("overly-modal");
                    $("#announcement-bar").slideDown(); // Show announcement bar
                } else {
                    $('.error-code').text(response.data.message);
                }
            }
        });
    });

    // Show popup when clicking sticky icon
    $("#sticky-popup-btn").on("click", function() {
        $("#discount-popup").fadeIn();
        $("body").addClass("overly-modal");
    });
});
