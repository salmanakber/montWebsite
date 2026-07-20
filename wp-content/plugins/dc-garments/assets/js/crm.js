jQuery(document).ready(function($) {
    // Check if dc_crm object exists

    if (typeof dc_crm === 'undefined' || !dc_crm.i18n) {
        console.error('dc_crm object is not properly initialized');
        return;
    }

    // Initialize variables
    const $crmWrap = $('.dc-crm-wrap');
    const $tabs = $('.dc-crm-tabs');
    const $content = $('.dc-crm-content');
    const $searchInput = $('.dc-crm-search input');
    const $form = $('.dc-crm-form');
    const $loading = $('.dc-crm-loading');
    const $notifications = $('.dc-crm-notifications');

    // Initialize tooltips
    if (typeof $.fn.tooltip === 'function') {
        $('[data-toggle="tooltip"]').tooltip();
    }

    // Initialize datepickers
    if (typeof $.fn.datepicker === 'function') {
        $('.dc-crm-datepicker').datepicker({
            format: 'yyyy-mm-dd',
            autoclose: true
        });
    }

    // Initialize select2 if available
    if (typeof $.fn.select2 === 'function') {
        $('.dc-crm-select2').select2({
            width: '100%'
        });
    }

    // Tab Navigation
    $tabs.on('click', 'a', function(e) {
        e.preventDefault();
        const tab = $(this).data('tab');
        loadTab(tab);
    });

    // Search functionality
    let searchTimeout;
    $searchInput.on('input', function() {
        clearTimeout(searchTimeout);
        const query = $(this).val();
        searchTimeout = setTimeout(() => {
            searchItems(query);
        }, 500);
    });

    // Form submission
    $form.on('submit', function(e) {
        e.preventDefault();
        saveItem($(this));
    });

    // Delete confirmation
    $content.on('click', '.dc-crm-delete', function(e) {
        e.preventDefault();
        const id = $(this).data('id');
        if (confirm(dc_crm.i18n.confirmDelete)) {
            deleteItem(id);
        }
    });

    // Load initial tab
    const initialTab = $tabs.find('.active a').data('tab') || 'dashboard';
    loadTab(initialTab);

    // AJAX Functions
    function loadTab(tab) {
        $loading.show();
        $content.addClass('dc-crm-loading');

        $.ajax({
            url: dc_crm.ajaxUrl,
            type: 'POST',
            data: {
                action: 'dc_crm_load_tab',
                tab: tab,
                nonce: dc_crm.nonce
            },
            success: function(response) {
                if (response.success) {
                    $content.html(response.data.content);
                    updateActiveTab(tab);
                    initializeComponents();
                } else {
                    showNotification(response.data.message, 'error');
                }
            },
            error: function() {
                showNotification(dc_crm.i18n.errorLoading, 'error');
            },
            complete: function() {
                $loading.hide();
                $content.removeClass('dc-crm-loading');
            }
        });
    }

    function searchItems(query) {
        $loading.show();
        $content.addClass('dc-crm-loading');

        $.ajax({
            url: dc_crm.ajaxUrl,
            type: 'POST',
            data: {
                action: 'dc_crm_search',
                query: query,
                nonce: dc_crm.nonce
            },
            success: function(response) {
                if (response.success) {
                    $content.find('.dc-crm-table tbody').html(response.data.content);
                } else {
                    showNotification(response.data.message, 'error');
                }
            },
            error: function() {
                showNotification(dc_crm.i18n.errorSearching, 'error');
            },
            complete: function() {
                $loading.hide();
                $content.removeClass('dc-crm-loading');
            }
        });
    }

    function saveItem($form) {
        const formData = new FormData($form[0]);
        formData.append('action', 'dc_crm_save');
        formData.append('nonce', dc_crm.nonce);

        $loading.show();
        $form.addClass('dc-crm-loading');

        $.ajax({
            url: dc_crm.ajaxUrl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    showNotification(response.data.message, 'success');
                    if (response.data.redirect) {
                        window.location.href = response.data.redirect;
                    } else {
                        loadTab($form.data('tab'));
                    }
                } else {
                    showNotification(response.data.message, 'error');
                }
            },
            error: function() {
                showNotification(dc_crm.i18n.errorSaving, 'error');
            },
            complete: function() {
                $loading.hide();
                $form.removeClass('dc-crm-loading');
            }
        });
    }

    function deleteItem(id) {
        $loading.show();
        $content.addClass('dc-crm-loading');

        $.ajax({
            url: dc_crm.ajaxUrl,
            type: 'POST',
            data: {
                action: 'dc_crm_delete',
                id: id,
                nonce: dc_crm.nonce
            },
            success: function(response) {
                if (response.success) {
                    showNotification(response.data.message, 'success');
                    loadTab($content.data('current-tab'));
                } else {
                    showNotification(response.data.message, 'error');
                }
            },
            error: function() {
                showNotification(dc_crm.i18n.errorDeleting, 'error');
            },
            complete: function() {
                $loading.hide();
                $content.removeClass('dc-crm-loading');
            }
        });
    }

    // UI Helpers
    function updateActiveTab(tab) {
        $tabs.find('a').removeClass('active');
        $tabs.find(`a[data-tab="${tab}"]`).addClass('active');
        $content.data('current-tab', tab);
    }

    function showNotification(message, type = 'success') {
        const $notice = $(`
            <div class="dc-crm-notice ${type}">
                ${message}
            </div>
        `);

        $notifications.html($notice);
        setTimeout(() => {
            $notice.fadeOut(() => $notice.remove());
        }, 5000);
    }

    function initializeComponents() {
        // Reinitialize tooltips
        if (typeof $.fn.tooltip === 'function') {
            $('[data-toggle="tooltip"]').tooltip();
        }

        // Reinitialize datepickers
        if (typeof $.fn.datepicker === 'function') {
            $('.dc-crm-datepicker').datepicker({
                format: 'yyyy-mm-dd',
                autoclose: true
            });
        }

        // Reinitialize select2
        if (typeof $.fn.select2 === 'function') {
            $('.dc-crm-select2').select2({
                width: '100%'
            });
        }
    }
}); 