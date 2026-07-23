jQuery(document).ready(function($) {

    $(document).on('click', '.add-to-cart-button-bubble' , function(){
        $('#monte-b2b-form').show();
        $('.monte-b2b-modal-content').addClass('model-loader');
        $.ajax({
        type: "POST",
        dataType: 'json',
        url:  ajaxurl.url, // Use the global ajax_url variable provided by WordPress
        data: {
            action: 'show_cart_data_hook' // Action name to be handled by the server-side function
        },
        success: function(response) {
            $('.cart-data').html(response.html);
            $('.monte-b2b-modal-content').removeClass('model-loader');
        },
        error: function(xhr, status, error) {
            console.error(xhr.responseText); // Log error message
            $('.monte-b2b-modal-content').removeClass('model-loader');
        }
    });
})
 $('.monte-b2b-close').on('click', function(event) {
    $('.monte-b2b-modal').hide();
    });


    $('[data-monte-b2b-modal-trigger]').on('click', function() {
        var modalContentSelector = $(this).data('monte-b2b-modal-trigger');
        $(modalContentSelector).show(); // Show the specified modal content
        $('#monte-b2b-form').show();
    });

    // Close the modal when clicking on the close button or outside the modal



  $(document).on('click', '.b2b-check-to-go-collar', function(e) {
        e.preventDefault();
        var $label = $(this);
        $('.b2b-check-to-go-collar').removeClass('is-selected');
        $('.b2b-check-to-go-collar .blank-check').removeClass('checkbtn');
        $label.addClass('is-selected');
        $label.find('.blank-check').addClass('checkbtn');
        $label.find('input[type=radio]').prop('checked', true);
});
    $(document).on('click', '.b2b-check-to-go-cuff', function(e) {
        e.preventDefault();
        var $label = $(this);
        $('.b2b-check-to-go-cuff').removeClass('is-selected');
        $('.b2b-check-to-go-cuff .blank-check').removeClass('checkbtn');
        $label.addClass('is-selected');
        $label.find('.blank-check').addClass('checkbtn');
        $label.find('input[type=radio]').prop('checked', true);
});

        function updateSum() {
        var sum = 0;
        $('.b2b-size-input').each(function() {
            sum += parseFloat($(this).val()) || 0;
        });
        $('.price-b2b').val(sum +' '+ "Shirts" );
    }
    $('.b2b-size-input').on('input', function() {
        updateSum();
    });


$(document).on('click', '.send-it-to-cart', function() {
    // Check if any of the given fields are filled
    var isAnyFieldFilled = false;
    var thisE = $(this);
    // Check if any of the b2b-size-input fields are filled
    $('.b2b-size-input').each(function() {
        if ($(this).val().trim() !== '') {
            isAnyFieldFilled = true;
            return false; // Exit the loop if a filled input is found
        }
    });

    // Check if other required fields are filled
    var comments = $('#s_comment').val().trim();
    var price = $('.price-b2b').val().trim();
    var checkedForms = $('.b2b-checked-form:checked').length;
    var collarTypeValue = $('.collar-type-b2b input[type=radio]:checked').val();
    var cuffTypeValue = $('.cuff-type-b2b input[type=radio]:checked').val();

    if (!isAnyFieldFilled) {
        $.notify("Please fill in any of the size fields.", { type: "danger", align: "left", verticalAlign: "bottom" });
        return; // Stop further processing if no field is filled
    }

    if (comments !== '' || price !== '' || checkedForms > 0 || collarTypeValue !== undefined || cuffTypeValue !== undefined) {
        isAnyFieldFilled = true;
    }

    // Collect form data
    var formData = {
        'size': [],
        'comments': comments,
        'price': price,
        // Add other fields as needed
        'checkedForms': [],
        'sizeInputDataValues': [],
        'fabircDetails' : []
    };

    // Collect data-value attribute from b2b-size-input fields
    $('.b2b-size-input').each(function() {
        var fieldValue = $(this).val().trim();
        if (fieldValue !== '') {
            var dataValue = $(this).data('value');
            formData['size'].push({
                'value': fieldValue,
                'dataValue': dataValue
            });
        }
    });

    // Collect checkbox values
    $('.b2b-checked-form').each(function() {
        if ($(this).is(':checked')) {
            formData['checkedForms'].push($(this).attr('name'));
        }
    });

    // Collect radio button values (collar-type-b2b)
    formData['collarType'] = collarTypeValue;

    // Collect radio button values (cuff-type-b2b)
    formData['cuffType'] = cuffTypeValue;
    formData['fabircDetails'].push
    ({
        'moq' : $("#moq").val(),
		'fabricName' : $("#pname").val(),
        'fabircColor': $("#pcolor").val(),
        'fabricWeight': $("#pweight").val(),
        'fabricQuality': $("#pquality").val()
    })

    thisE.addClass('btn-loader');
    // Send formData via AJAX with a custom key
    $.ajax({
        type: "POST",
        url: ajaxurl.url, // Use the global ajax_url variable provided by WordPress
        data: {
            action: 'add_to_car_b2b_hook', // Action name to be handled by the server-side function
            productData: formData // Use a custom key 'productData' to send formData
        },
        success: function(response) {
			console.log(response + 'Yes');
            thisE.removeClass('btn-loader');
			if(response.sizeError)
				{
				$.notify(response.message, { type: "danger", align: "left", verticalAlign: "bottom" });
				}
            if(response.data.count > 0)
            {
				$('.submit-it-directly').addClass('add-to-cart-button-bubble');
                $.notify("Product added to cart.", { type: "toast", align: "left", verticalAlign: "bottom" });
                $('.add-to-cart-button-bubble').removeClass('d-none');
                $('.count-item-b2b').text(response.data.count < 10 ? '0' + response.data.count : response.data.count);
				window.location.href = "monte-connected-b2b";
				$('.b2b-details').find('input').val('');
			$('.b2b-details').find('input[type="checkbox"]').prop('checked', false);
            }

        },
        error: function(xhr, status, error) {
            thisE.removeClass('btn-loader');
            console.error(xhr.responseText); // Log error message
        }
    });
});




$(document).on('click', '.monte-b2b-remove-item' , function(){
    thisE = $(this);
      $('.monte-b2b-modal-content').addClass('model-loader');
        $.ajax({
        type: "POST",
        url:  ajaxurl.url, // Use the global ajax_url variable provided by WordPress
        data: {
            action: 'removeKey', // Action name to be handled by the server-side function
            key: $(this).data('id')
        },
        success: function(response) {
            thisE.parents('.accordion-item-monte-b2b').remove();
            if(response.data.count < 1)
            {
				$('.submit-it-directly').removeClass('add-to-cart-button-bubble');
                $('#monte-b2b-form').hide();
                $('.add-to-cart-button-bubble').addClass('d-none');
               $('.count-item-b2b').text(response.data.count < 10 ? '0' + response.data.count : response.data.count);
            }
            else
            {
                $('.add-to-cart-button-bubble').removeClass('d-none');
                $('.count-item-b2b').text(response.data.count < 10 ? '0' + response.data.count : response.data.count);
            }
            $('.monte-b2b-modal-content').removeClass('model-loader');
        },
        error: function(xhr, status, error) {
            console.error(xhr.responseText); // Log error message
            $('.monte-b2b-modal-content').removeClass('model-loader');
        }
    });

})

    $(document).on('click' , '.accordion-button-monte-b2b' , function(){
        // Toggle the visibility of accordion content
        var accordionContent = $(this).closest('.accordion-item-monte-b2b').find('.accordion-collapse-monte-b2b');
        accordionContent.toggleClass('d-none');
        
        // Hide other open accordion items
        $('.accordion-collapse-monte-b2b').not(accordionContent).addClass('d-none');
        var caretIcon = $(this).find('.fa');
        caretIcon.toggleClass('fa-caret-down fa-caret-up');
    });

$(document).on('click', '.order-btn', function() {
    var thisE = $(this);
    var formValid = true;
    // Reset border color and remove existing error messages
    $('.order-form input').css('border-color', '');
    $('.order-form .error-message').remove();
    
    // Iterate through each required input field
    $('.order-form input[data-required]').each(function() {
        var value = $(this).val().trim();
        var errorMessage = $(this).data('required');
        
        // Check if the field is empty
        if (value === '') {
            // Add error message and border color
            $(this).after('<span class="error-message">' + errorMessage + '</span>');
            $(this).css('border-color', 'red');
            formValid = false;
        } else {
            // Remove error message
            $(this).next('.error-message').remove();
            
            // Check if the field is email type and validate email format
            if ($(this).attr('type') === 'email' && !isValidEmail(value)) {
                $(this).after('<span class="error-message">Invalid email format</span>');
                $(this).css('border-color', 'red');
                formValid = false;
            }
        }
    });

    // If form is valid, serialize and send data
    if (formValid) {
        var formData = $('.order-form').serializeArray();
      thisE.addClass('btn-loader');
    // Send formData via AJAX with a custom key
    $.ajax({
        type: "POST",
        url:  ajaxurl.url, // Use the global ajax_url variable provided by WordPress
        data: {
            action: 'placed_order', // Action name to be handled by the server-side function
            productData: formData // Use a custom key 'productData' to send formData
        },
        success: function(response) {
            if(response.success)
            {
                $('#monte-b2b-form').hide();
                $('.add-to-cart-button-bubble').addClass('d-none');
                $.notify(response.message, { type: "toast", align: "left", verticalAlign: "bottom" });
            }
			else
				{
					$.notify(response.message, { type: "toast", align: "left", verticalAlign: "bottom" });
				}
            thisE.removeClass('btn-loader');
        },
        error: function(xhr, status, error) {
            thisE.removeClass('btn-loader');
            console.error(xhr.responseText); // Log error message
        }
    });
    }
});


// Real-time validation
$('.order-form input[data-required]').on('input', function() {
    var value = $(this).val().trim();
    var errorMessage = $(this).data('required');
    
    if (value === '') {
        $(this).after('<span class="error-message">' + errorMessage + '</span>');
        $(this).css('border-color', 'red');
    } else {
        $(this).next('.error-message').remove();
        
        // Check if the field is email type and validate email format
        if ($(this).attr('type') === 'email' && !isValidEmail(value)) {
            $(this).after('<span class="error-message">Invalid email format</span>');
            $(this).css('border-color', 'red');
        } else {
            $(this).css('border-color', '');
        }
    }
});

// Function to validate email format
function isValidEmail(email) {
    var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

$(document).on('click', '.b2b-size-guide' , function(){
	$('#monte-b2b-size').show();
})

$(document).on('click', '.this-hide' , function (){
	$(this).parents('.'+$(this).data('tag')).remove();
})
//   $('.mobile-view-b2b').owlCarousel({
//     center: true,
//     items:1,
//     loop:true,
//     margin:0
// });

//    $('.mobile-view-b2b-tabs').owlCarousel({
//     center: false,
//     items:2,
//     loop:true,
//     margin:0,
//     dots: false,
//     nav: true
// });

});



document.addEventListener("DOMContentLoaded", () => {
  const sliderWrapper = document.querySelector(".category-slider-wrapper")
  const slider = document.querySelector(".category-slider")
  const prevArrow = document.querySelector(".prev-arrow")
  const nextArrow = document.querySelector(".next-arrow")
  const items = document.querySelectorAll(".category-item")

  let scrollPosition = 0
  const itemWidth = items[0]?.offsetWidth || 0

  // Function to update arrow visibility
  function updateArrowVisibility() {
    // Hide prev arrow if at the beginning
    prevArrow.classList.toggle("hidden", scrollPosition <= 0)

    // Hide next arrow if at the end
    const maxScroll = slider.scrollWidth - sliderWrapper.offsetWidth
    nextArrow.classList.toggle("hidden", scrollPosition >= maxScroll)
  }

  // Initialize arrow visibility
  updateArrowVisibility()

  // Scroll to the active tab on page load
  const activeItem = document.querySelector(".active-li")
  if (activeItem) {
    const activeIndex = Array.from(items).indexOf(activeItem)
    if (activeIndex > 0) {
      // Calculate position to center the active item if possible
      const centerPosition = activeItem.offsetLeft - sliderWrapper.offsetWidth / 2 + activeItem.offsetWidth / 2
      scrollPosition = Math.max(0, Math.min(centerPosition, slider.scrollWidth - sliderWrapper.offsetWidth))
      slider.style.transform = `translateX(-${scrollPosition}px)`
      updateArrowVisibility()
    }
  }

  // Previous button click handler
  prevArrow.addEventListener("click", () => {
    // Scroll by the width of one item, or to the beginning
    scrollPosition = Math.max(0, scrollPosition - sliderWrapper.offsetWidth / 2)
    slider.style.transform = `translateX(-${scrollPosition}px)`
    updateArrowVisibility()
  })

  // Next button click handler
  nextArrow.addEventListener("click", () => {
    // Scroll by the width of one item, or to the end
    const maxScroll = slider.scrollWidth - sliderWrapper.offsetWidth
    scrollPosition = Math.min(maxScroll, scrollPosition + sliderWrapper.offsetWidth / 2)
    slider.style.transform = `translateX(-${scrollPosition}px)`
    updateArrowVisibility()
  })

  // Handle tab clicks to update active state
  const tabButtons = document.querySelectorAll(".nav-link-monte-b2b")
  tabButtons.forEach((button) => {
    button.addEventListener("click", function () {
      // Remove active class from all buttons and list items
      tabButtons.forEach((btn) => btn.classList.remove("active"))
      items.forEach((item) => item.classList.remove("active-li"))

      // Add active class to clicked button and its parent li
      this.classList.add("active")
      this.closest(".category-item").classList.add("active-li")
    })
  })

  // Update arrow visibility on window resize
  window.addEventListener("resize", updateArrowVisibility)
})


