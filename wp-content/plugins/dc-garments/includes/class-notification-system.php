<?php
namespace DC_Product_Manager;

class Notification_System {
    private $stock_threshold = 10; // Default stock threshold

    public function init() {
        // Add settings
        add_action('admin_init', array($this, 'register_settings'));
        
        // Add stock check cron job
        add_action('init', array($this, 'schedule_stock_check'));
        add_action('dc_check_stock_levels', array($this, 'check_stock_levels'));
        
        // Add notification menu
        add_action('admin_menu', array($this, 'add_notification_menu'));
        
        // Handle stock updates
        add_action('woocommerce_update_product', array($this, 'check_product_stock'), 10, 1);
        add_action('woocommerce_update_product_variation', array($this, 'check_variation_stock'), 10, 1);
    }

    public function register_settings() {
        // register_setting('dc_product_manager_options', 'dc_stock_threshold');
        
        // add_settings_section(
        //     'dc_notification_settings',
        //     __('Notification Settings', 'dc-product-manager'),
        //     array($this, 'notification_settings_section'),
        //     'dc_product_manager_options'
        // );
        
        // add_settings_field(
        //     'dc_stock_threshold',
        //     __('Stock Alert Threshold', 'dc-product-manager'),
        //     array($this, 'stock_threshold_field'),
        //     'dc_product_manager_options',
        //     'dc_notification_settings'
        // );
    }

    public function notification_settings_section() {
        echo '<p>' . __('Configure notification settings for stock alerts.', 'dc-product-manager') . '</p>';
    }

    public function stock_threshold_field() {
        $threshold = get_option('dc_stock_threshold', $this->stock_threshold);
        echo '<input type="number" name="dc_stock_threshold" value="' . esc_attr($threshold) . '" min="1" />';
        echo '<p class="description">' . __('Minimum stock level before alert is triggered.', 'dc-product-manager') . '</p>';
    }

    public function schedule_stock_check() {
        if (!wp_next_scheduled('dc_check_stock_levels')) {
            wp_schedule_event(time(), 'daily', 'dc_check_stock_levels');
        }
    }

    public function check_stock_levels() {
        $threshold = get_option('dc_stock_threshold', $this->stock_threshold);
        $low_stock_products = array();

        // Get all products
        $products = wc_get_products(array(
            'limit' => -1,
            'status' => 'publish',
        ));

        foreach ($products as $product) {
            if ($product->is_type('variable')) {
                $variations = $product->get_children();
                foreach ($variations as $variation_id) {
                    $variation = wc_get_product($variation_id);
                    $stock = $variation->get_stock_quantity();
                    
                    if ($stock !== null && $stock <= $threshold) {
                        $low_stock_products[] = array(
                            'id' => $variation_id,
                            'name' => $variation->get_formatted_name(),
                            'stock' => $stock,
                            'threshold' => $threshold
                        );
                    }
                }
            } else {
                $stock = $product->get_stock_quantity();
                if ($stock !== null && $stock <= $threshold) {
                    $low_stock_products[] = array(
                        'id' => $product->get_id(),
                        'name' => $product->get_name(),
                        'stock' => $stock,
                        'threshold' => $threshold
                    );
                }
            }
        }

        if (!empty($low_stock_products)) {
            $this->send_stock_alert($low_stock_products);
        }
    }

    public function check_product_stock($product_id) {
        $product = wc_get_product($product_id);
        if (!$product) {
            return;
        }

        $threshold = get_option('dc_stock_threshold', $this->stock_threshold);
        
        if ($product->is_type('variable')) {
            $variations = $product->get_children();
            foreach ($variations as $variation_id) {
                $variation = wc_get_product($variation_id);
                $stock = $variation->get_stock_quantity();
                
                if ($stock !== null && $stock <= $threshold) {
                    $this->send_stock_alert(array(
                        array(
                            'id' => $variation_id,
                            'name' => $variation->get_formatted_name(),
                            'stock' => $stock,
                            'threshold' => $threshold
                        )
                    ));
                }
            }
        } else {
            $stock = $product->get_stock_quantity();
            if ($stock !== null && $stock <= $threshold) {
                $this->send_stock_alert(array(
                    array(
                        'id' => $product_id,
                        'name' => $product->get_name(),
                        'stock' => $stock,
                        'threshold' => $threshold
                    )
                ));
            }
        }
    }

    public function check_variation_stock($variation_id) {
        $variation = wc_get_product($variation_id);
        if (!$variation) {
            return;
        }

        $threshold = get_option('dc_stock_threshold', $this->stock_threshold);
        $stock = $variation->get_stock_quantity();
        
        if ($stock !== null && $stock <= $threshold) {
            $this->send_stock_alert(array(
                array(
                    'id' => $variation_id,
                    'name' => $variation->get_formatted_name(),
                    'stock' => $stock,
                    'threshold' => $threshold
                )
            ));
        }
    }

    private function send_stock_alert($products) {
        // Get staff users
        $staff_users = get_users(array(
            'role' => 'dc_staff'
        ));

        if (empty($staff_users)) {
            return;
        }

        // Prepare email content
        $subject = sprintf(__('[%s] Low Stock Alert', 'dc-product-manager'), get_bloginfo('name'));
        
        $message = __('The following products are running low on stock:', 'dc-product-manager') . "\n\n";
        
        foreach ($products as $product) {
            $message .= sprintf(
                __('%s - Current Stock: %d (Threshold: %d)', 'dc-product-manager'),
                $product['name'],
                $product['stock'],
                $product['threshold']
            ) . "\n";
            
            $message .= admin_url('post.php?post=' . $product['id'] . '&action=edit') . "\n\n";
        }

        // Send email to each staff member
        foreach ($staff_users as $user) {
//             wp_mail($user->user_email, $subject, $message);
        }

        // Store notification in database
        $this->store_notification($products);
    }

    private function store_notification($products) {
        $notifications = get_option('dc_stock_notifications', array());
        
        $notification = array(
            'timestamp' => current_time('mysql'),
            'products' => $products
        );
        
        array_unshift($notifications, $notification);
        
        // Keep only last 50 notifications
        $notifications = array_slice($notifications, 0, 50);
        
        update_option('dc_stock_notifications', $notifications);
    }

    public function add_notification_menu() {
        add_submenu_page(
            'edit.php?post_type=product',
            __('Stock Alerts', 'dc-product-manager'),
            __('Stock Alerts', 'dc-product-manager'),
            'edit_products',
            'dc-stock-alerts',
            array($this, 'render_notifications_page')
        );
    }

    public function render_notifications_page() {
        $notifications = get_option('dc_stock_notifications', array());
        ?>
        <div class="wrap">
            <h1><?php _e('Stock Alerts', 'dc-product-manager'); ?></h1>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Date', 'dc-product-manager'); ?></th>
                        <th><?php _e('Product', 'dc-product-manager'); ?></th>
                        <th><?php _e('Current Stock', 'dc-product-manager'); ?></th>
                        <th><?php _e('Threshold', 'dc-product-manager'); ?></th>
                        <th><?php _e('Actions', 'dc-product-manager'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if (empty($notifications)) {
                        echo '<tr><td colspan="5">' . __('No stock alerts found.', 'dc-product-manager') . '</td></tr>';
                    } else {
                        foreach ($notifications as $notification) {
                            foreach ($notification['products'] as $product) {
                                ?>
                                <tr>
                                    <td><?php echo esc_html($notification['timestamp']); ?></td>
                                    <td><?php echo esc_html($product['name']); ?></td>
                                    <td><?php echo esc_html($product['stock']); ?></td>
                                    <td><?php echo esc_html($product['threshold']); ?></td>
                                    <td>
                                        <a href="<?php echo esc_url(admin_url('post.php?post=' . $product['id'] . '&action=edit')); ?>" class="button button-small">
                                            <?php _e('Edit Product', 'dc-product-manager'); ?>
                                        </a>
                                    </td>
                                </tr>
                                <?php
                            }
                        }
                    }
                    ?>
                </tbody>
            </table>
        </div>
        <?php
    }
} 