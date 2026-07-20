jQuery(document).ready(function($) {
    // Show popup
    $('#add-new-variation').on('click', function() {
        $('#variation-popup').show();
        $('#variation-form')[0].reset();
    });

    // Close popup
    $('.close-popup, .cancel-popup').on('click', function() {
        $('#variation-popup').hide();
    });

    // Handle plus/minus buttons
    $('.number-input .minus').on('click', function() {
        var input = $(this).siblings('input');
        var value = parseFloat(input.val()) || 0;
        input.val((value - 0.1).toFixed(1));
    });

    $('.number-input .plus').on('click', function() {
        var input = $(this).siblings('input');
        var value = parseFloat(input.val()) || 0;
        input.val((value + 0.1).toFixed(1));
    });

    // Handle form submission
    $('#variation-form').on('submit', function(e) {
        e.preventDefault();

        var formData = new FormData(this);
        formData.append('action', 'save_variation');
        formData.append('nonce', variationSettings.nonce);

        // Combine selected attributes into a unique key
        var attributeKey = [];
        $(this).find('select[name^="attributes"]').each(function() {
            if ($(this).val()) {
                attributeKey.push($(this).val());
            }
        });
        formData.append('attribute_key', attributeKey.join('___'));  //3 time underscore

        $.ajax({
            url: variationSettings.ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                console.log(response);
                if (response.success) {
                    // location.reload();
                }
            }
        });
    });

    // Handle delete
    $('.delete-variation').on('click', function() {
        if (!confirm('Are you sure you want to delete this variation?')) {
            return;
        }

        var id = $(this).data('id');

        $.ajax({
            url: variationSettings.ajaxurl,
            type: 'POST',
            data: {
                action: 'delete_variation',
                id: id,
                nonce: variationSettings.nonce
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                }
            }
        });
    });

    // Handle edit
    $('.edit-variation').on('click', function() {
        var row = $(this).closest('tr');
        var id = $(this).data('id');

        // Populate form with existing values
        $('#variation-form').find('input[type="number"]').each(function() {
            var fieldName = $(this).attr('name');
            var value = row.find('td').eq(getColumnIndex(fieldName)).text();
            $(this).val(value);
        });

        // Add ID to form for update
        $('#variation-form').append('<input type="hidden" name="id" value="' + id + '">');
        
        $('#variation-popup').show();
    });

    function getColumnIndex(fieldName) {
        var columns = {
            'shirt_length': 1,
            'sleeve_length': 2,
            'shoulder': 3,
            'half_chest': 4,
            'half_waist': 5,
            'half_bottom': 6,
            'armhole': 7,
            'neck_collar': 8
        };
        return columns[fieldName] || 0;
    }

});