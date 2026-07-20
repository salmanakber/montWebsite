jQuery(document).ready(function($) {
    $('.CheckDefualt input[type="checkbox"]').on('change', function() {
        if ($(this).prop('checked')) {
            // Uncheck all other checkboxes within the same parent div
            $('.CheckDefualt').find('input[type="checkbox"]').not(this).prop('checked', false);
        }
    });
	
});
