<script>

  jQuery(document).ready(function($) {
  // Toggle search popup when search icon is clicked
    $('#mont-search-toggle').on('click', function(e) {
      e.preventDefault();
      $('.mont-search-popup').addClass('active');
      $('body').addClass('mont-search-active');

    // Focus on search input after animation completes
      setTimeout(function() {
        $('.mont-search-popup-content input[type="text"]').focus();
      }, 500);
    });

  // Close search popup when close button is clicked
    $('.mont-search-close').on('click', function() {
      closeSearchPopup();
    });

  // Close search popup when clicking outside the search container
    $('.mont-search-popup').on('click', function(e) {
      if ($(e.target).closest('.mont-search-popup-container').length === 0) {
        closeSearchPopup();
      }
    });

  // Close search popup when ESC key is pressed
    $(document).keyup(function(e) {
      if (e.key === "Escape" && $('.mont-search-popup').hasClass('active')) {
        closeSearchPopup();
      }
    });

  // Function to close the search popup
    function closeSearchPopup() {
      $('.mont-search-popup').removeClass('active');
      $('body').removeClass('mont-search-active');
    }
  });
</script>

<style>
  /* Alternative positioning - under header instead of fullscreen */
  .mont-search-popup {
    position: absolute;
    top: 100%; /* Position right under the header */
    left: 0;
    width: 100%;
    height: auto;
    max-height: 0;
/*    overflow: hidden;*/
    transition: max-height 0.4s cubic-bezier(0.19, 1, 0.22, 1), opacity 0.4s ease;
    opacity: 0;
/*    z-index: 999;*/
    display: none;
  }

  .mont-search-popup.active {
    max-height: 300px; /* Adjust based on your needs */
    opacity: 1;
    display: block;
  }

  .mont-search-popup-container {
    transform: translateY(-20px);
    transition: transform 0.3sease;
    border-radius: 0 0 8px 8px;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    background: white;
    border-top: 0.5px solid #949598;
    padding: 25px 0 25px 0;
  }

  .mont-search-popup.active .mont-search-popup-container {
    transform: translateY(0);
  }
.mont-search-popup-content {
    max-width: 94%;
    margin: auto 15px;
}
input.mont-search-input, .mont-search-button {
    border-radius: 0;
    font-size: 14px;
    font-weight: 200;
    font-family: 'Figtree';
}

button.mont-search-button {
    background: #000000;
    color: white;
    border: 1px solid black;
}
.mont-search-popup-header {
    position: absolute;
    right: 13px;
    margin-top: 10px;
}
button.mont-search-close {
    background: transparent;
    border: 0;
}
</style>
<div class="mont-search-popup">
  <div class="mont-search-popup-container">
       <div class="mont-search-popup-header">
      <button class="mont-search-close">
        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round">
          <line x1="18" y1="6" x2="6" y2="18"></line>
          <line x1="6" y1="6" x2="18" y2="18"></line>
        </svg>
      </button>
    </div>
    <div class="mont-search-popup-content">
      <!-- WordPress shortcode will be rendered here -->
      <?php echo do_shortcode('[mont_search placeholder="Find products..." button_text="Søk" show_button="no"]'); ?>
    </div>
 

  </div>
</div>



