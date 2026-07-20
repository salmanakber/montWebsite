/**
 * Supplier Management JavaScript
 */
(function($) {
    'use strict';

    // Debug function
    function debug(message, data) {
        console.log('[Supplier Manager] ' + message, data || '');
    }

    // Initialize when document is ready
    $(document).ready(function() {
        // Create a fallback object if dc_supplier_manager is not available
        if (typeof window.dc_supplier_manager === 'undefined') {
            console.log('Creating fallback dc_supplier_manager object');
            window.dc_supplier_manager = {
                ajax_url: ajax_object.ajax_url,
                nonce: '',
                i18n: {
                    error: 'Error',
                    success: 'Success',
                    confirm_delete: 'Are you sure you want to delete this supplier?',
                    supplier_deleted: 'Supplier deleted successfully',
                    supplier_saved: 'Supplier saved successfully',
                    server_error: 'Error connecting to the server'
                }
            };
        }
        
        // Store dc_supplier_manager reference locally
        var supplierManager = window.dc_supplier_manager;
        
        // Add New Supplier button
        $('#add-new-supplier').on('click', function() {
            // Clear form
            $('#supplier-form')[0].reset();
            $('#supplier-id').val('');
            
            // Show form
            $('.dc-supplier-form-container').show();
            
            // Scroll to form
            $('html, body').animate({
                scrollTop: $('.dc-supplier-form-container').offset().top - 100
            }, 500);
        });
        
        // Edit Supplier button
        $(document).on('click', '.edit-supplier', function() {
            var supplierId = $(this).data('supplier-id');
            
            // Get supplier data
            $.ajax({
                url: ajax_object.ajax_url,
                type: 'POST',
                data: {
                    action: 'dc_get_supplier',
                    supplier_id: supplierId,
                    nonce: supplierManager.nonce
                },
                success: function(response) {
                    if (response.success) {
                        var supplier = response.data;
                        
                        // Fill form
                        $('#supplier-id').val(supplier.id);
                        $('#supplier-name').val(supplier.name);
                        $('#supplier-contact-name').val(supplier.contact_name);
                        $('#supplier-email').val(supplier.email);
                        $('#supplier-phone').val(supplier.phone);
                        $('#supplier-address').val(supplier.address);
                        
                        // Show form
                        $('.dc-supplier-form-container').show();
                        
                        // Scroll to form
                        $('html, body').animate({
                            scrollTop: $('.dc-supplier-form-container').offset().top - 100
                        }, 500);
                    } else {
                        alert(supplierManager.i18n.error + ': ' + response.data);
                    }
                },
                error: function() {
                    alert(supplierManager.i18n.error + ': ' + supplierManager.i18n.server_error);
                }
            });
        });
        
        // Delete Supplier button
        $(document).on('click', '.delete-supplier', function() {
            if (!confirm(supplierManager.i18n.confirm_delete)) {
                return;
            }
            
            var supplierId = $(this).data('supplier-id');
            var row = $(this).closest('tr');
            
            // Delete supplier
            $.ajax({
                url: ajax_object.ajax_url,
                type: 'POST',
                data: {
                    action: 'dc_delete_supplier',
                    supplier_id: supplierId,
                    nonce: supplierManager.nonce
                },
                success: function(response) {
                    if (response.success) {
                        row.fadeOut(400, function() {
                            $(this).remove();
                            
                            // Check if there are any suppliers left
                            if ($('#supplier-list tr').length === 0) {
                                $('#supplier-list').html('<tr><td colspan="5" class="no-items">No suppliers found. Click "Add New Supplier" to create one.</td></tr>');
                            }
                        });
                    } else {
                        alert(supplierManager.i18n.error + ': ' + response.data);
                    }
                },
                error: function() {
                    alert(supplierManager.i18n.error + ': ' + supplierManager.i18n.server_error);
                }
            });
        });
        
        // Cancel Edit button
        $('.cancel-edit').on('click', function() {
            $('.dc-supplier-form-container').hide();
        });
        
        // Submit form
        $('#supplier-form').on('submit', function(e) {
            e.preventDefault();
            
            var formData = $(this).serialize();
            formData += '&action=dc_save_supplier&nonce=' + supplierManager.nonce;
            
            // Save supplier
            $.ajax({
                url: ajax_object.ajax_url,
                type: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        // Reload page to show updated data
                        location.reload();
                    } else {
                        alert(supplierManager.i18n.error + ': ' + response.data);
                    }
                },
                error: function() {
                    alert(supplierManager.i18n.error + ': ' + supplierManager.i18n.server_error);
                }
            });
        });
        
        // Search suppliers
        $('#supplier-search').on('keyup', function() {
            var searchText = $(this).val().toLowerCase();
            
            $('#supplier-list tr').each(function() {
                var row = $(this);
                var text = row.text().toLowerCase();
                
                if (text.indexOf(searchText) === -1) {
                    row.hide();
                } else {
                    row.show();
                }
            });
        });
    });
})(jQuery); 