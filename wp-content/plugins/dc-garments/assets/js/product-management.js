jQuery(document).ready(function($) {

    // Check if dc_product_manager is available
    if (typeof dc_product_manager === 'undefined') {
        console.error('dc_product_manager is not defined');
        return;
    }


    // Check if i18n is available
    if (typeof dc_product_manager.i18n === 'undefined') {
        console.error('dc_product_manager.i18n is not defined');
        return;
    }

    // Add loading overlay to body
    $('body').append('<div class="dc-loading-overlay"><div class="dc-loading-spinner"></div></div>');
    $('body').append('<div class="dc-notifications-container"></div>');

    // Variables
    var productList = [];
    var currentCategory = 'all';
    var lowStockThreshold = 10; // Threshold for low stock notifications
    var selectedProducts = []; // Array to store selected product IDs
    
    // DOM Elements
    var $productListBody = $('#dc-product-list-body');
    var $productSearch = $('#dc-product-search');
    var $notifications = $('.dc-notifications-container');
    var $loadingOverlay = $('.dc-loading-overlay');
    var $productEditForm = $('#dc-product-edit-form');
    var $saveProduct = $('#dc-save-product');
    var $productTitle = $('#dc-product-title');
    var $productFabricColor = $('#dc-product-fabric-color');
	var $productFabricColorEnglish = $('#dc-product-fabric-color-english');
    var $productCategory = $('#dc-product-category');
    var $productFabricNo = $('#dc-product-fabric-no');
    var $productCustomTitle = $('#dc-product-custom-title');
    var $productCustomTitleInput = $('#dc-product-custom-title-input');
    var $productTitlePreview = $('#dc-product-title-preview');
    var $productPrice = $('#dc-product-price');
    var $productStock = $('#dc-product-stock');
    var $productMOQ = $('#dc-product-moq');
    var $productB2BStatus = $('#dc-product-b2b-status');
    var $productSupplier = $('#dc-product-supplier');
    var $productSupplierSku = $('#dc-product-supplier-sku');
    var $productQuality = $('#dc-product-quality');
    var $productFabricWidth = $('#dc-product-fabric-width');
    var $productWeight = $('#dc-product-weight');
    var $productSupplierPrice = $('#dc-product-supplier-price');
	   var $productImage = $('#dc-product-image');
		  var $productImageId = $('#dc-product-image-id');
    var $productId = $('#dc-product-id');
    
    // Bulk Edit Elements
    var $bulkEditToggle = $('#dc-bulk-edit-toggle');
    var $bulkEditPanel = $('#dc-bulk-edit-panel');
    var $bulkEditCancel = $('#dc-bulk-edit-cancel');
    var $bulkEditApply = $('#dc-bulk-edit-apply');
    
    // Check if we're on the product list page or edit page
    var isProductListPage = $('#dc-product-list-body').length > 0;
    var isProductEditPage = $('.dc-product-form').length > 0;
    
    // Initialize category selection if on edit page
    if (isProductEditPage) {
        initCategorySelection();
    }
    

    // Initialize when document is ready
    $(document).ready(function() {
        // Load products first
    loadProducts();

//         alert('sdfsaaaad vs 111 1234');
        // Initialize notification system
//         initializeNotificationSystem();

    // Event Listeners
        $productSearch.on('input', function() {
            filterProducts();
        });
        
						function applyCategory(category) {
        // Run your category filtering logic
        filterByCategory(category);

        // Update active class
        $('.dc-category-item').removeClass('active');
        $('.dc-category-item[data-category="' + category + '"]').addClass('active');
    }

    // On click: only update hash (let the hashchange event do the rest)
    $('.dc-category-item').on('click', function (e) {
        e.preventDefault();
        const category = $(this).data('category');
        if (category) {
            // This triggers the hashchange event
            window.location.hash = category;
        }
    });

    // Handle hashchange and initial page load
    function handleHashChange() {
        const hash = window.location.hash.replace('#', '');
        const $item = $('.dc-category-item[data-category="' + hash + '"]');

        if ($item.length) {
            applyCategory(hash);
        } else {
            // Optional: apply first category if no valid hash
            const firstCategory = $('.dc-category-item').first().data('category');
            if (firstCategory) {
                applyCategory(firstCategory);
            }
        }
    }

    // Trigger on load and on hash change
    $(window).on('hashchange', handleHashChange);
    handleHashChange(); // Run on page load				
     
    });
    
    // Notification System
    let notifications = [];
    let unreadCount = 0;

    function initializeNotificationSystem() {
        // Add notification toggle button
        $('body').append(`
            <div class="dc-notification-toggle">
                <span class="dashicons dashicons-bell"></span>
                <span class="dc-notification-count">0</span>
            </div>
            <div class="dc-notification-menu" style="display: none;">
                <div class="dc-notification-header">
                    <h3>Stock Notifications</h3>
                    <div class="dc-notification-actions">
                        <button class="dc-notification-refresh">
                            <span class="dashicons dashicons-update"></span>
                        </button>
                        <span class="dc-notification-count">0</span>
                    </div>
                </div>
                <div class="dc-notification-list"></div>
            </div>
        `);

        // Toggle notification menu
        $('.dc-notification-toggle').on('click', function() {
            $('.dc-notification-menu').slideToggle(200);
        });

        // Refresh notifications
        $('.dc-notification-refresh').on('click', function(e) {
            e.stopPropagation();
            refreshNotifications();
        });

        // Close notification menu when clicking outside
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.dc-notification-menu, .dc-notification-toggle').length) {
                $('.dc-notification-menu').slideUp(200);
            }
        });

        // Check for low stock products periodically
        checkLowStockProducts();
        setInterval(checkLowStockProducts, 300000); // Check every 5 minutes
    }

    function checkLowStockProducts() {
        // Get existing notifications from localStorage
        const existingNotifications = JSON.parse(localStorage.getItem('dc_notifications') || '[]');
        const existingIds = new Set(existingNotifications.map(n => n.id));

        // Find products with low stock
        const lowStockProducts = productList.filter(product => {
            const stock = parseInt(product.stock_quantity) || 0;
            return stock <= lowStockThreshold;
        });

        // Add new notifications
        const newNotifications = lowStockProducts
            .filter(product => !existingIds.has(product.id))
            .map(product => {
                return {
                    id: product.id,
                    title: product.name || `Product #${product.id}`,
                    message: `Current stock: ${product.stock_quantity || 0}`,
                    type: product.stock_quantity <= 0 ? 'out' : 'low',
                    time: new Date(),
                    read: false
                };
            });

        notifications = [...existingNotifications, ...newNotifications];
        
        // Save to localStorage
        localStorage.setItem('dc_notifications', JSON.stringify(notifications));
        
        // Update unread count
        unreadCount = notifications.filter(n => !n.read).length;
        
        updateNotificationUI();
        
        // Show initial notification if there are new unread items
        if (newNotifications.length > 0) {
            showNotification(
                'warning',
                'Low Stock Alert',
                `${newNotifications.length} new products have low or no stock. Click the bell icon to view details.`
            );
        }
    }

    function updateNotificationUI() {
        const $notificationList = $('.dc-notification-list');
        const $notificationCount = $('.dc-notification-count');
        const $notificationToggle = $('.dc-notification-toggle');

        // Update notification count
        $notificationCount.text(unreadCount);
        $notificationToggle.toggleClass('has-notifications', unreadCount > 0);

        // Update notification list
        if (notifications.length === 0) {
            $notificationList.html(`
                <div class="dc-notification-empty">
                    No stock notifications at this time.
                </div>
            `);
            return;
        }

        // Sort notifications by time (newest first)
        const sortedNotifications = [...notifications].sort((a, b) => 
            new Date(b.time) - new Date(a.time)
        );

        const notificationsHtml = sortedNotifications.map(notification => {
            // Get product details from productList if available
            const productDetails = productList.find(p => p.id === notification.id);
            const title = productDetails ? productDetails.title : notification.title || `Product #${notification.id}`;
            const stock = productDetails ? productDetails.stock : (notification.message.match(/\d+/) || [0])[0];
            const type = productDetails && productDetails.stock <= 0 ? 'out' : notification.type;
            
            return `
                <a class="dc-notification-item ${notification.read ? 'read' : 'unread'}" data-id="${notification.id}"  href="${dc_product_manager.siteUrl +'/crm/product/' +productDetails.id + '/edit'}">
                    <div class="dc-notification-icon ${type}">
                        <span class="dashicons dashicons-${type === 'out' ? 'warning' : 'info'}"></span>
                    </div>
                    <div class="dc-notification-content">
                        <div class="dc-notification-title">${title}</div>
                        <div class="dc-notification-message">Current stock: ${stock}</div>
                        <div class="dc-notification-time">${formatTime(new Date(notification.time))}</div>
                    </div>
                    <button class="dc-notification-mark-read">
                        <span class="dashicons dashicons-yes"></span>
                    </button>
                </a>
            `;
        }).join('');

        $notificationList.html(notificationsHtml);

        // Add click handler for mark as read button
        $('.dc-notification-mark-read').on('click', function(e) {
            e.stopPropagation();
            const $item = $(this).closest('.dc-notification-item');
            const id = $item.data('id');
            markAsRead(id);
        });

        // Add click handler for notification items
        $('.dc-notification-item').on('click', function() {
            const id = $(this).data('id');
            markAsRead(id);
        });
    }

    function markAsRead(id) {
        const notification = notifications.find(n => n.id === id);
        if (notification && !notification.read) {
            notification.read = true;
            unreadCount--;
            
            // Update localStorage
            localStorage.setItem('dc_notifications', JSON.stringify(notifications));
            
            // Update UI
            updateNotificationUI();
        }
    }

    function markAllAsRead() {
        notifications.forEach(notification => {
            notification.read = true;
        });
        unreadCount = 0;
        
        // Update localStorage
        localStorage.setItem('dc_notifications', JSON.stringify(notifications));
        
        // Update UI
        updateNotificationUI();
    }

    function formatTime(date) {
        const now = new Date();
        const diff = now - date;
        const minutes = Math.floor(diff / 60000);
        const hours = Math.floor(minutes / 60);
        const days = Math.floor(hours / 24);

        if (days > 0) return `${days}d ago`;
        if (hours > 0) return `${hours}h ago`;
        if (minutes > 0) return `${minutes}m ago`;
        return 'Just now';
    }
    
    // Functions
    function loadProducts() {
        showLoading();
        
        $.ajax({
            url: dc_product_manager.ajaxUrl,
            type: 'POST',
            data: {
                action: 'dc_get_products',
                nonce: dc_product_manager.nonce
            },
            success: function(response) {
        
                hideLoading();
                if (response.success) {
                    productList = response.data;
                    
                    // Apply current category filter if one is selected
                    if (currentCategory && currentCategory !== 'all') {
                        const filteredProducts = productList.filter(product => {
                            return product.categories && product.categories.includes(parseInt(currentCategory));
                        });
                        renderProducts(filteredProducts);
                    } else {
                        renderProducts(productList);
                    }
                    
                    // Clear old notifications
                    clearOldNotifications();
                    
                    // Check for low stock products after loading
                    checkLowStockProducts();
                } else {
                    showNotification('error', dc_product_manager.i18n.error, response.data);
                }
            },
            error: function() {
                hideLoading();
                showNotification('error', dc_product_manager.i18n.error, dc_product_manager.i18n.error);
            }
        });
    }
    
    function renderProducts(products) {
        const $grid = $('.dc-product-grid');
        $grid.empty();

        if (!products || products.length === 0) {
            $grid.html(`
                <div class="dc-no-products">
                    <p>No products found. Try adjusting your search or filters.</p>
                </div>
            `);
            return;
        }
        
        products.forEach(product => {
            const stockStatus = getStockStatus(product.stock);
            const imageUrl = product.image ;
            const imageHtml = imageUrl 
                ? `<img src="${imageUrl}" alt="${product.title}" loading="lazy">`
                : `<div class="dc-product-card-no-image">No image available</div>`;

            const selectedClass = selectedProducts.includes(product.id) ? 'selected' : '';
            const card = `
                <div class="ass dc-product-card ${selectedClass}" data-id="${product.id}">
                    <div class="dc-product-checkbox-wrapper">
                        <input type="checkbox" class="dc-product-checkbox" ${selectedProducts.includes(product.id) ? 'checked' : ''}>
                    </div>
                    <div class="dc-product-card-image">
                        ${imageHtml}
                    </div>
                    <div class="dc-product-card-content">
                        <h3 class="dc-product-card-title">${product.title}</h3>
                        <div class="dc-product-card-details">
                            <div class="dc-product-card-detail">
                                <span class="dc-product-card-detail-label">SKU</span>
                                <span class="dc-product-card-detail-value">${product.supplier_sku || 'N/A'}</span>
                            </div>
                            <div class="dc-product-card-detail">
                                <span class="dc-product-card-detail-label">Price</span>
                                <span class="dc-product-card-detail-value">${formatPrice(product.price)}</span>
                            </div>
                            <div class="dc-product-card-detail">
                                <span class="dc-product-card-detail-label">Stock</span>
                                <span class="dc-product-card-detail-value">${product.stock || 0}</span>
                            </div>
                        </div>
                        <div class="dc-product-card-stock ${stockStatus.class}">
                            ${stockStatus.text}
                        </div>
                        <div class="dc-product-card-actions">
                            <button class="button dc-edit-product" data-id="${product.id}">
                                Edit Product
                            </button>
                        </div>
                    </div>
                </div>
            `;
            $grid.append(card);
        });

        // Add event listeners to product cards
        $('.dc-product-card').on('click', function(e) {
            // Don't toggle if clicking on the checkbox or edit button
            if ($(e.target).is('.dc-product-checkbox') || $(e.target).is('.dc-edit-product')) {
                return;
            }
            
            const $card = $(this);
            const $checkbox = $card.find('.dc-product-checkbox');
            const productId = parseInt($card.data('id'));
            
            if (selectedProducts.includes(productId)) {
                selectedProducts = selectedProducts.filter(id => id !== productId);
                $checkbox.prop('checked', false);
                $card.removeClass('selected');
            } else {
                selectedProducts.push(productId);
                $checkbox.prop('checked', true);
                $card.addClass('selected');
            }
            
            // Update bulk actions visibility
            updateBulkActionsVisibility();
        });
        
        // Add event listeners to checkboxes
        $('.dc-product-checkbox').on('change', function(e) {
            e.stopPropagation();
            const $card = $(this).closest('.dc-product-card');
            const productId = parseInt($card.data('id'));
            
            if ($(this).is(':checked')) {
                $card.addClass('selected');
                if (!selectedProducts.includes(productId)) {
                    selectedProducts.push(productId);
                }
            } else {
                $card.removeClass('selected');
                selectedProducts = selectedProducts.filter(id => id !== productId);
            }
            
            // Update bulk actions visibility
            updateBulkActionsVisibility();
        });
    }
    
    function getStockStatus(quantity) {
        if (quantity === null || quantity === undefined) {
            return { class: 'out', text: 'Out of Stock' };
        }
        if (quantity <= 0) {
            return { class: 'out', text: 'Out of Stock' };
        }
        if (quantity <= 10) {
            return { class: 'low', text: 'Low Stock' };
        }
        return { class: 'good', text: 'In Stock' };
    }

function formatPrice(price, currency) {
    if (!price) return 'N/A';
    currency = currency || (dc_product_manager.defaultCurrency || 'NOK');
    var localeMap = { USD: 'en-US', EUR: 'de-DE', NOK: 'nb-NO', VND: 'vi-VN' };
    var locale = localeMap[currency] || 'en-US';
    var decimals = currency === 'VND' ? 0 : 2;
    return new Intl.NumberFormat(locale, {
        style: 'currency',
        currency: currency,
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals
    }).format(price);
}

    
    function filterProducts() {
        var searchTerm = $productSearch.val().toLowerCase();
        var filteredProducts = productList.filter(function(product) {
            return product.title.toLowerCase().includes(searchTerm) ||
                   product.sku.toLowerCase().includes(searchTerm);
        });
        
        if (currentCategory !== 'all') {
            filteredProducts = filteredProducts.filter(function(product) {
                return product.category === currentCategory;
            });
        }
        
        renderProducts(filteredProducts);
    }
    
    function filterByCategory(category) {
        currentCategory = category;
        
        // If category is 'all', show all products
        if (category === 'all') {
            renderProducts(productList);
            return;
        }
        
        // Filter products by category
        const filteredProducts = productList.filter(product => {
            // Check if product has categories array and if it includes the selected category
            return product.categories && product.categories.includes(parseInt(category));
        });
        
        renderProducts(filteredProducts);
    }
    
    function showLoading() {
        $loadingOverlay.css('display', 'flex');
    }

    function hideLoading() {
        $loadingOverlay.css('display', 'none');
    }

    function showNotification(type, title, message) {
        var notification = $('<div class="dc-notification ' + type + '">');
        notification.append($('<h4> ').text(title));
        notification.append($('<p> ').text(message));
        
        $notifications.append(notification);
        
        // Auto remove after 5 seconds
        setTimeout(function() {
            notification.fadeOut(function() {
                $(this).remove();
            });
        }, 5000);
    }
    
    function initProductEditPage() {
        // Event Listeners
        $productCustomTitle.on('change', function() {
            if ($(this).is(':checked')) {
                $productCustomTitleInput.show();
                $productTitlePreview.hide();
                } else {
                $productCustomTitleInput.hide();
                $productTitlePreview.show();
                updateTitlePreview();
            }
        });
        
        $productFabricColor.on('input', updateTitlePreview);
		$productFabricColorEnglish.on('input', updateTitlePreview);
        $productCategory.on('change', updateTitlePreview);
        $productFabricNo.on('input', updateTitlePreview);
        
        // Add stock change indicator
        $productStock.on('change', function() {
            var stockValue = parseInt($(this).val()) || 0;
            var $stockStatus = $(this).siblings('.stock-status');
            
            // Remove existing classes
            $stockStatus.removeClass('good low out');
            
            // Add appropriate class based on stock value
            if (stockValue <= 0) {
                $stockStatus.addClass('out');
            } else if (stockValue <= 10) {
                $stockStatus.addClass('low');
            } else {
                $stockStatus.addClass('good');
            }
            
            // Update title attribute
            $stockStatus.attr('title', 'Current stock: ' + stockValue);
            
            // Show a brief notification
            showNotification('info', 'Stock Updated', 'Stock value has been changed to ' + stockValue);
        });
        
        $saveProduct.on('click', function(e) {
            e.preventDefault();
            
            // Validate required fields
            if (!$productFabricColor.val() || !$productCategory.val() || !$productFabricNo.val() || !$productFabricColorEnglish.val()) {
                showNotification('error', dc_product_manager.i18n.error, dc_product_manager.i18n.requiredFields);
                return;
            }
            
            // Prepare form data
            var multicurrencyPrices = {};
            $('.dc-multicurrency-price').each(function() {
                var currency = $(this).data('currency');
                var val = $(this).val();
                if (currency && val !== '') {
                    multicurrencyPrices[currency] = val;
                }
            });
            if ($('#dc-price-nok').length && $('#dc-price-nok').val()) {
                $productPrice.val($('#dc-price-nok').val());
            } else if (Object.keys(multicurrencyPrices).length) {
                $productPrice.val(Object.values(multicurrencyPrices)[0]);
            }

            var formData = {
            action: 'dc_update_product',
                nonce: dc_product_manager.nonce,
                product_id: $productId.val(),
			       fabric_color_english: $productFabricColorEnglish.val(),
            fabric_color: $productFabricColor.val(),
            category_id: $productCategory.val(),
            fabric_no: $productFabricNo.val(),
                title: $productCustomTitle.is(':checked') ? $productCustomTitleInput.val() : $productTitlePreview.text(),
            price: $productPrice.val(),
            multicurrency_prices: multicurrencyPrices,
            multicurrency_prices_json: JSON.stringify(multicurrencyPrices),
            stock: $productStock.val(),
                moq: $productMOQ.val(),
                b2b_product: $productB2BStatus.val(),
            supplier_id: $productSupplier.val(),
            supplier_sku: $productSupplierSku.val(),
            quality: $productQuality.val(),
            fabric_width: $productFabricWidth.val(),
            weight: $productWeight.val(),
            supplier_price: $productSupplierPrice.val(),
			product_image: $productImage.val(),
			product_imageid: $productImageId.val()
        };
        
            // Show loading state
            showLoading();
            $saveProduct.addClass('dc-save-button-loading').prop('disabled', true);
            
            // Send AJAX request
        $.ajax({
                url: dc_product_manager.ajaxUrl,
            type: 'POST',
                data: formData,
            success: function(response) {

                    hideLoading();
                    $saveProduct.removeClass('dc-save-button-loading').prop('disabled', false);
                    
                if (response.success) {
                        showNotification('success ', dc_product_manager.i18n.success, dc_product_manager.i18n.productUpdated);
                        // setTimeout(function() {
                        //     window.location.href = dc_product_manager.siteUrl + 'crm/';
                        // }, 1500);
                } else {
                        showNotification('error ', dc_product_manager.i18n.error, response.data || dc_product_manager.i18n.error);
                    }
                },
                error: function(xhr, status, error) {
                    hideLoading();
                    $saveProduct.removeClass('dc-save-button-loading').prop('disabled', false);
                    
                    var errorMessage = 'An error occurred while updating the product';
                    try {
                        if (xhr.responseJSON && xhr.responseJSON.data) {
                            errorMessage = xhr.responseJSON.data;
                        } else if (xhr.responseText) {
                            try {
                                var response = JSON.parse(xhr.responseText);
                                if (response.data) {
                                    errorMessage = response.data;
                                }
                            } catch (e) {
                                console.error('Error parsing response text:', e);
                            }
                        }
                    } catch (e) {
                        console.error('Error handling response:', e);
                    }
                    
                    // Log the full error details
                    console.error('AJAX Error Details:', {
                        status: status,
                        error: error,
                        response: xhr.responseText,
                        statusText: xhr.statusText,
                        statusCode: xhr.status
                    });
                    
                    showNotification('error', 'Update Failed', errorMessage);
                }
            });
        });
        
        function updateTitlePreview() {
            var fabricColor = $productFabricColor.val();
            var category = $productCategory.find('option:selected').text();
            var fabricNo = $productFabricNo.val();
            
            if (fabricColor && category && fabricNo) {
                var title = fabricColor + ' ' + category + ' ' + fabricNo;
                $productTitlePreview.text(title);
            } else {
                $productTitlePreview.text(dc_product_manager.i18n.titlePreview);
            }
        }
        
        // Initialize title preview
        updateTitlePreview();
    }
    
    // Function to initialize category selection
    function initCategorySelection() {
        // Store parent-child relationships
        var categoryParents = {};
        var categoryChildren = {};
        
        // Build parent-child relationships
        $('#dc-product-category option').each(function() {
            var $option = $(this);
            var categoryId = $option.val();
            var parentId = $option.data('parent');
            
            if (parentId) {
                categoryParents[categoryId] = parentId;
                
                if (!categoryChildren[parentId]) {
                    categoryChildren[parentId] = [];
                }
                categoryChildren[parentId].push(categoryId);
            }
        });
        
        // Handle category selection
        $('#dc-product-category').on('change', function() {
            var selectedId = $(this).val();
            
            // If a child category is selected, also select its parent
            if (categoryParents[selectedId]) {
                var parentId = categoryParents[selectedId];
                $(this).find('option[value="' + parentId + '"]').prop('selected', true);
                
                // Show notification
                showNotification('info', 'Category Updated', 'Parent category has been automatically selected');
            }
            
            // Update title preview
            updateTitlePreview();
        });
    }
    
    if (isProductEditPage) {
        // Initialize product edit page
        initProductEditPage();
    }

    // Add this function to clear old notifications
    function clearOldNotifications() {
        const thirtyDaysAgo = new Date();
        thirtyDaysAgo.setDate(thirtyDaysAgo.getDate() - 30);
        
        notifications = notifications.filter(notification => {
            const notificationDate = new Date(notification.time);
            return notificationDate > thirtyDaysAgo;
        });
        
        // Update localStorage
        localStorage.setItem('dc_notifications', JSON.stringify(notifications));
        
        // Update unread count
        unreadCount = notifications.filter(n => !n.read).length;
        
        // Update UI
        updateNotificationUI();
    }

    // Add a function to refresh notifications
    function refreshNotifications() {
        if (productList.length > 0) {
            checkLowStockProducts();
        } else {
            loadProducts();
        }
    }

    // Function to render product cards
    function renderProductCards(products) {
        if (!products || products.length === 0) {
            $productListBody.html('<div class="dc-no-products">' + dc_product_manager.i18n.noProducts + '</div>');
            return;
        }
        
        var html = '';
        
        products.forEach(function(product) {
            var stockClass = 'good';
            if (product.stock <= 0) {
                stockClass = 'out';
            } else if (product.stock <= lowStockThreshold) {
                stockClass = 'low';
            }
            
            var selectedClass = selectedProducts.includes(product.id) ? 'selected' : '';
            
            html += '<div class="dc-product-card ' + selectedClass + '" data-id="' + product.id + '">';
            html += '<div class="dc-product-checkbox-wrapper">';
            html += '<input type="checkbox" class="dc-product-checkbox" ' + (selectedProducts.includes(product.id) ? 'checked' : '') + '>';
            html += '</div>';
            html += '<div class="dc-product-image">';
            
            if (product.image) {
                html += '<img src="' + product.image + '" alt="' + product.title + '">';
            } else {
                html += '<div class="dc-no-image">' + dc_product_manager.i18n.noImage + '</div>';
            }
            
            html += '</div>';
            html += '<div class="dc-product-content">';
            html += '<h3 class="dc-product-title">' + product.title + '</h3>';
            html += '<div class="dc-product-details">';
            html += '<div class="dc-product-detail"><span class="dc-detail-label">' + dc_product_manager.i18n.sku + ':</span> <span class="dc-detail-value">' + product.sku + '</span></div>';
            html += '<div class="dc-product-detail"><span class="dc-detail-label">' + dc_product_manager.i18n.price + ':</span> <span class="dc-detail-value">' + product.price + '</span></div>';
            html += '<div class="dc-product-detail"><span class="dc-detail-label">' + dc_product_manager.i18n.stock + ':</span> <span class="dc-detail-value stock-' + stockClass + '">' + product.stock + '</span></div>';
            html += '</div>';
            html += '<div class="dc-product-actions">';
            html += '<button class="button button-small dc-edit-product">' + dc_product_manager.i18n.edit + '</button>';
            html += '</div>';
            html += '</div>';
            html += '</div>';
        });
        
        $productListBody.html(html);
        
        // Add event listeners to product cards
        $('.dc-product-card').on('click', function(e) {
            // Don't toggle if clicking on the checkbox or edit button
            if ($(e.target).is('.dc-product-checkbox') || $(e.target).is('.dc-edit-product')) {
                return;
            }
            
            const $card = $(this);
            const $checkbox = $card.find('.dc-product-checkbox');
            const productId = parseInt($card.data('id'));
            
            if (selectedProducts.includes(productId)) {
                selectedProducts = selectedProducts.filter(id => id !== productId);
                $checkbox.prop('checked', false);
                $card.removeClass('selected');
            } else {
                selectedProducts.push(productId);
                $checkbox.prop('checked', true);
                $card.addClass('selected');
            }
            
            // Update bulk actions visibility
            updateBulkActionsVisibility();
        });
        
        // Add event listeners to checkboxes
        $('.dc-product-checkbox').on('change', function(e) {
            e.stopPropagation();
            const $card = $(this).closest('.dc-product-card');
            const productId = parseInt($card.data('id'));
            
            if ($(this).is(':checked')) {
                $card.addClass('selected');
                if (!selectedProducts.includes(productId)) {
                    selectedProducts.push(productId);
                }
            } else {
                $card.removeClass('selected');
                selectedProducts = selectedProducts.filter(id => id !== productId);
            }
            
            // Update bulk actions visibility
            updateBulkActionsVisibility();
        });
    }
    
    // Bulk Edit Toggle
    $bulkEditToggle.on('click', function() {
        $bulkEditPanel.slideToggle();
    });
    
    // Bulk Edit Cancel
    $bulkEditCancel.on('click', function() {
        $bulkEditPanel.slideUp();
    });
	
$('#dc-bulk-delete-button').on('click', function (e) {
    e.preventDefault();

    if (!confirm('Are you sure you want to delete selected products?')) return;

    const $button = $(this);

    const deleteObj = {
        action: 'dc_bulk_delete_products', // Make sure this matches your PHP handler
        product_ids: selectedProducts,
        nonce: dc_product_manager.nonce
    };

    $button.prop('disabled', true).text('Deleting...');
    $loadingOverlay.show(); // optional, only if you use an overlay

    $.ajax({
        url: dc_product_manager.ajaxUrl,
        type: 'POST',
        data: deleteObj,
        success: function (response) {
            $loadingOverlay.hide();
            if (response.success) {
                showNotification('success', 'Successfully deleted!');
                loadProducts();
            } else {
                showNotification('error', response.data.message || 'Error deleting products');
            }
            $button.prop('disabled', false).text('Bulk Delete Selected');
        },
        error: function () {
            $loadingOverlay.hide();
            showNotification('error', 'Error connecting to server');
            $button.prop('disabled', false).text('Bulk Delete Selected');
        }
    });
});

    
    // Bulk Edit Apply
    $bulkEditApply.on('click', function() {
        if (selectedProducts.length === 0) {
            showNotification('error', dc_product_manager.i18n.requiredFields);
            return;
        }
        
        // Get values from bulk edit fields
        var updateData = {
            product_ids: selectedProducts,
            nonce: dc_product_manager.nonce
        };
        
        // Only include fields that have values
        var $bulkStock = $('#dc-bulk-stock');
        var $bulkMoq = $('#dc-bulk-moq');
        var $bulkB2b = $('#dc-bulk-b2b');
        var $bulkSupplierPrice = $('#dc-bulk-supplier-price');
        var $bulkQuality = $('#dc-bulk-quality');
        var $bulkFabricWidth = $('#dc-bulk-fabric-width');
        var $bulkWeight = $('#dc-bulk-weight');

        var multicurrencyPrices = {};
        $('.dc-bulk-multicurrency-price').each(function() {
            var code = $(this).data('currency');
            var val = $(this).val();
            if (code && val !== '') {
                multicurrencyPrices[code] = val;
            }
        });
        if (Object.keys(multicurrencyPrices).length) {
            updateData.multicurrency_prices = multicurrencyPrices;
            updateData.multicurrency_prices_json = JSON.stringify(multicurrencyPrices);
        }
        
        if ($bulkStock.val() !== '') {
            updateData.stock = $bulkStock.val();
        }

        if ($bulkMoq.val() !== '') {
            updateData.moq = $bulkMoq.val();
        }

        if ($bulkB2b.val() !== '') {
            updateData.b2b_product = $bulkB2b.val();
        }
        
        if ($bulkSupplierPrice.val() !== '') {
            updateData.supplier_price = $bulkSupplierPrice.val();
        }
        
        if ($bulkQuality.val() !== '') {
            updateData.quality = $bulkQuality.val();
        }
        
        if ($bulkFabricWidth.val() !== '') {
            updateData.fabric_width = $bulkFabricWidth.val();
        }
        
        if ($bulkWeight.val() !== '') {
            updateData.weight = $bulkWeight.val();
        }
        
        // Check if any fields have values
        if (Object.keys(updateData).length <= 2) { // Only has product_ids and nonce
            showNotification('error', dc_product_manager.i18n.requiredFields);
            return;
        }
        

        // Show loading overlay
        $loadingOverlay.show();
		      $(this).prop('disabled', true).text(dc_product_manager.i18n.saving);
             
        // Send AJAX request
        $.ajax({
            url: dc_product_manager.ajaxUrl,
            type: 'POST',
            data: {
                action: 'dc_bulk_update_products',
                ...updateData
            },
            success: function(response) {
                $loadingOverlay.hide();
                
                if (response.success) {
                    var message = response.data.updated + ' ' + (response.data.updated === 1 ? 'product' : 'products') + ' updated successfully.';
                    showNotification('success', message);
                    
                    // Clear form
                    $bulkStock.val('');
                    $bulkMoq.val('');
                    $bulkB2b.val('');
                    $('.dc-bulk-multicurrency-price').val('');
                    $bulkSupplierPrice.val('');
                    $bulkQuality.val('');
                    $bulkFabricWidth.val('');
                    $bulkWeight.val('');
                    
                    // Hide panel
                    $bulkEditPanel.slideUp();
                    
                    // Clear selected products
                    selectedProducts = [];
                         closeBulkEditModal();
                    // Reload products
                    loadProducts();
					$('#dc-bulk-edit-apply').prop('disabled', false).text('Apply to Selected Products');
                } else {
                    showNotification('error', response.data.message || 'Error updating products');
					$('#dc-bulk-edit-apply').prop('disabled', false).text('Apply to Selected Products');
                }
            },
            error: function() {
                $loadingOverlay.hide();
                showNotification('error', 'Error connecting to server');
				$('#dc-bulk-edit-apply').prop('disabled', false).text('Apply to Selected Products');
            }
        });
    });

    // Add this function to update bulk actions visibility
    function updateBulkActionsVisibility() {
        const $bulkActions = $('.dc-bulk-actions');
        if (selectedProducts.length > 0) {
            $bulkActions.show();
        } else {
            $bulkActions.hide();
        }
    }

    // Update the bulk selection functions to properly maintain selectedProducts array
    function selectAllProducts() {
        selectedProducts = [];
        $('.dc-product-card').each(function() {
            const productId = parseInt($(this).data('id'));
            selectedProducts.push(productId);
            $(this).addClass('selected');
            $(this).find('.dc-product-checkbox').prop('checked', true);
        });
        updateBulkActionsVisibility();
    }

    function selectOutOfStockProducts() {
        selectedProducts = [];
        $('.dc-product-card').each(function() {
            const stockStatus = $(this).find('.dc-product-card-stock').text().trim().toLowerCase();
            if (stockStatus === 'out of stock') {
                const productId = parseInt($(this).data('id'));
                selectedProducts.push(productId);
                $(this).addClass('selected');
                $(this).find('.dc-product-checkbox').prop('checked', true);
            } else {
                $(this).removeClass('selected');
                $(this).find('.dc-product-checkbox').prop('checked', false);
            }
        });
        // updateBulkActionsVisibility();
    }

    function selectLowStockProducts() {
        selectedProducts = [];
        $('.dc-product-card').each(function() {
            const stockStatus = $(this).find('.dc-product-card-stock').text().trim().toLowerCase();
            if (stockStatus === 'low stock') {
                const productId = parseInt($(this).data('id'));
                selectedProducts.push(productId);
                $(this).addClass('selected');
                $(this).find('.dc-product-checkbox').prop('checked', true);
            } else {
                $(this).removeClass('selected');
                $(this).find('.dc-product-checkbox').prop('checked', false);
            }
        });
        updateBulkActionsVisibility();
    }

    function selectInStockProducts() {
        selectedProducts = [];
        $('.dc-product-card').each(function() {
            const stockStatus = $(this).find('.dc-product-card-stock').text().trim().toLowerCase();
            if (stockStatus === 'in stock') {
                const productId = parseInt($(this).data('id'));
                selectedProducts.push(productId);
                $(this).addClass('selected');
                $(this).find('.dc-product-checkbox').prop('checked', true);
            } else {
                $(this).removeClass('selected');
                $(this).find('.dc-product-checkbox').prop('checked', false);
            }
        });
        updateBulkActionsVisibility();
    }

    // Initialize bulk selection handlers
    $('.dc-select-all').on('click', selectAllProducts);
    $('.dc-select-out-of-stock').on('click', selectOutOfStockProducts);
    $('.dc-select-low-stock').on('click', selectLowStockProducts);
    $('.dc-select-in-stock').on('click', selectInStockProducts);

    // Handle individual product selection
    $(document).on('change', '.dc-product-checkbox', function(e) {
        e.stopPropagation();
        const $card = $(this).closest('.dc-product-card');
        const productId = parseInt($card.data('id'));
        
        if ($(this).is(':checked')) {
            $card.addClass('selected');
            if (!selectedProducts.includes(productId)) {
                selectedProducts.push(productId);
            }
        } else {
            $card.removeClass('selected');
            selectedProducts = selectedProducts.filter(id => id !== productId);
        }
        
        // Update bulk actions visibility
        updateBulkActionsVisibility();
    });

    // Update product card click handler
    $(document).on('click', '.dc-product-card', function(e) {
        // Don't toggle if clicking on the checkbox or edit button
        if ($(e.target).is('.dc-product-checkbox') || $(e.target).is('.dc-edit-product')) {
            return;
        }
        
        const $card = $(this);
        const $checkbox = $card.find('.dc-product-checkbox');
        const productId = parseInt($card.data('id'));
        
        if (selectedProducts.includes(productId)) {
            selectedProducts = selectedProducts.filter(id => id !== productId);
            $checkbox.prop('checked', false);
            $card.removeClass('selected');
        } else {
            selectedProducts.push(productId);
            $checkbox.prop('checked', true);
            $card.addClass('selected');
        }
        
        // Update bulk actions visibility
        updateBulkActionsVisibility();
    });

    // Edit button click handler
    $(document).on('click', '.dc-edit-product', function(e) {
        e.preventDefault();
        e.stopPropagation();
        id = $(this).data('id');
        const productId = parseInt($(this).closest('.dc-product-card').data('id'));
        // openEditPopup(productId);
      window.location.href = `${dc_product_manager.siteUrl}/crm/product/${id}/edit`;
    });

    // Edit Form Popup Functions
    function openEditPopup(productId) {
        // Show loading state
        showLoading();
        
        // Fetch product data
        $.ajax({
            url: dc_product_manager.ajaxUrl,
            type: 'POST',
            data: {
                action: 'dc_get_product',
                nonce: dc_product_manager.nonce,
                product_id: productId
            },
            success: function(response) {
                hideLoading();
                if (response.success) {
                    const product = response.data;
                    
                    // Create popup HTML
                    const popupHtml = `
                        <div class="dc-edit-popup">
                            <div class="dc-edit-popup-content">
                                <div class="dc-edit-popup-header">
                                    <h2>${dc_product_manager.i18n.editProduct}</h2>
                                    <button class="dc-edit-popup-close">&times;</button>
                                </div>
                                <div class="dc-edit-popup-body">
                                    <form id="dc-product-edit-form">
                                        <input type="hidden" id="dc-product-id" value="${product.id}">
                                        <div class="dc-form-row">
                                            <div class="dc-form-group">
                                                <label for="dc-product-title">${dc_product_manager.i18n.title}</label>
                                                <input type="text" id="dc-product-title" value="${product.title}" required>
                                            </div>
                                        </div>
                                        <div class="dc-form-row">
                                            <div class="dc-form-group">
                                                <label for="dc-product-price">${dc_product_manager.i18n.price}</label>
                                                <input type="number" id="dc-product-price" value="${product.price}" step="0.01" required>
                                            </div>
                                            <div class="dc-form-group">
                                                <label for="dc-product-stock">${dc_product_manager.i18n.stock}</label>
                                                <input type="number" id="dc-product-stock" value="${product.stock}" required>
                                            </div>
                                        </div>
                                        <div class="dc-form-row">
                                            <div class="dc-form-group">
                                                <label for="dc-product-supplier">${dc_product_manager.i18n.supplier}</label>
                                                <select id="dc-product-supplier" required>
                                                    ${product.suppliers.map(supplier => 
                                                        `<option value="${supplier.id}" ${supplier.id === product.supplier_id ? 'selected' : ''}>
                                                            ${supplier.name}
                                                        </option>`
                                                    ).join('')}
                                                </select>
                                            </div>
                                            <div class="dc-form-group">
                                                <label for="dc-product-sku">${dc_product_manager.i18n.sku}</label>
                                                <input type="text" id="dc-product-sku" value="${product.sku}" required>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                                <div class="dc-edit-popup-footer">
                                    <button class="button dc-edit-popup-cancel">${dc_product_manager.i18n.cancel}</button>
                                    <button class="button button-primary" id="dc-save-product">${dc_product_manager.i18n.save}</button>
                                </div>
                            </div>
                        </div>
                    `;
                    
                    // Add popup to body
                    $('body').append(popupHtml);
                    
                    // Show popup
                    $('.dc-edit-popup').fadeIn(200);
                    
                    // Initialize form handlers
                    initializeEditForm();
                } else {
                    showNotification('error', dc_product_manager.i18n.error, response.data);
                }
            },
            error: function() {
                hideLoading();
                showNotification('error', dc_product_manager.i18n.error, dc_product_manager.i18n.error);
            }
        });
    }

    function closeEditPopup() {
        $('.dc-edit-popup').fadeOut(200, function() {
            $(this).remove();
        });
    }

    // Close popup handlers
    $(document).on('click', '.dc-edit-popup-close, .dc-edit-popup-cancel', function() {
        closeEditPopup();
    });

    $(document).on('click', '.dc-edit-popup', function(e) {
        if ($(e.target).hasClass('dc-edit-popup')) {
            closeEditPopup();
        }
    });

    // Bulk Edit Modal Functions
    function openBulkEditModal() {
        $('#dc-bulk-selected-count').text('(' + selectedProducts.length + ' selected)');
        $('#dc-bulk-edit-modal').fadeIn(200);
    }

    function closeBulkEditModal() {
        $('#dc-bulk-edit-modal').fadeOut(200);
        // Clear form fields
        $('#dc-bulk-stock, #dc-bulk-moq, #dc-bulk-b2b, .dc-bulk-multicurrency-price, #dc-bulk-supplier-price, #dc-bulk-quality, #dc-bulk-fabric-width, #dc-bulk-weight').val('');
        $('#dc-bulk-b2b').val('');
    }

    // Update bulk actions visibility
    function updateBulkActionsVisibility() {
        const $bulkActions = $('.dc-bulk-actions');
        if (selectedProducts.length > 0) {
            $bulkActions.show();
        } else {
            $bulkActions.hide();
            closeBulkEditModal();
        }
    }
	


    // Event Handlers for Bulk Edit
    $('#dc-bulk-edit-button').on('click', function(e) {
        e.preventDefault();
        if (selectedProducts.length > 0) {
            openBulkEditModal();
        }
    });

    $('.dc-modal-close, #dc-bulk-edit-cancel').on('click', function(e) {
        e.preventDefault();
        closeBulkEditModal();
    });

    // Close modal when clicking outside
    $(window).on('click', function(e) {
        if ($(e.target).is('#dc-bulk-edit-modal')) {
            closeBulkEditModal();
        }
    });

    // Handle bulk edit form submission
//     $('/#dc-bulk-edit-apply').on('click', function(e) {
//         e.preventDefault();
        
//         const updateData = {
//             action: 'dc_bulk_update_products',
//             nonce: dc_product_manager.nonce,
//             product_ids: selectedProducts,
//             price: $('#dc-bulk-price').val(),
//             stock: $('#dc-bulk-stock').val(),
//             supplier_price: $('#dc-bulk-supplier-price').val(),
//             quality: $('#dc-bulk-quality').val(),
//             fabric_width: $('#dc-bulk-fabric-width').val(),
//             weight: $('#dc-bulk-weight').val()
//         };
//         console.log(updateData);

        // Show loading state
//         $(this).prop('disabled', true).text(dc_product_manager.i18n.saving);

//         $.post(dc_product_manager.ajaxUrl, updateData, function(response) {
//             if (response.success) {
//                 // Show success message
//                 console.log(response);
//                 const message = `${response.data.updated} products updated successfully`;
//                 showNotification(message, 'success');
                
//                 // Clear selection and close modal
//                 selectedProducts = [];
//                 // closeBulkEditModal();
//                 // updateBulkActionsVisibility();
                
//                 // Refresh product list
//                 // loadProducts();
//             } else {
//                 // Show error message
//                 showNotification(response.data.message || 'Error updating products', 'error');
//             }
//         }).fail(function() {
//             showNotification('Error updating products', 'error');
//         }).always(function() {
//             // Reset button state
//             $('#dc-bulk-edit-apply').prop('disabled', false).text('Apply to Selected Products');
//         });
//     });

    // Sync friendly B2B toggle with hidden select used by save payload.
    (function bindB2bToggle() {
        var $toggle = $('#dc-product-b2b-toggle');
        var $select = $('#dc-product-b2b-status');
        var $section = $('.dc-b2b-channel-section');
        var $badge = $section.find('.dc-b2b-badge');
        if (!$toggle.length || !$select.length) {
            return;
        }

        function syncUi(isOn) {
            $select.val(isOn ? 'yes' : 'no');
            $section.toggleClass('is-b2b-active', isOn);
            $badge
                .toggleClass('dc-b2b-badge--on', isOn)
                .toggleClass('dc-b2b-badge--off', !isOn)
                .text(isOn ? 'B2B' : 'B2C only');
        }

        $toggle.on('change', function() {
            syncUi($(this).is(':checked'));
        });

        $select.on('change', function() {
            var isOn = $(this).val() === 'yes';
            $toggle.prop('checked', isOn);
            syncUi(isOn);
        });
    })();
});
