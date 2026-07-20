<?php
/**
 * Template for displaying the CRM interface
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get current user
$current_user = wp_get_current_user();
$current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'dashboard';

// Get the header
get_header();
?>
<style>
div#sticky-popup-btn {
    display: none !important;
}
</style>
<div class="dc-crm-wrap">
    <div class="dc-crm-header">
        <h1>Stock Management System</h1>
        <div class="dc-crm-user-info">
            <span class="dc-crm-welcome">Welcome, <?php echo esc_html($current_user->display_name); ?></span>
            <a href="<?php echo esc_url(wp_logout_url(home_url())); ?>" class="dc-crm-logout">Logout</a>
        </div>
    </div>

    <div class="dc-crm-content">
        <div class="dc-crm-sidebar">
            <nav class="dc-crm-nav">
                <ul>
                    <li class="<?php echo $current_tab === 'dashboard' ? 'active' : ''; ?>">
                        <a href="<?php echo esc_url(add_query_arg('tab', 'dashboard', home_url('/crm/'))); ?>">Dashboard</a>
                    </li>
                    <li class="<?php echo $current_tab === 'products' ? 'active' : ''; ?>">
                        <a href="<?php echo esc_url(add_query_arg('tab', 'products', home_url('/crm/'))); ?>">Products</a>
                    </li>
                    <li class="<?php echo $current_tab === 'suppliers' ? 'active' : ''; ?>">
                        <a href="<?php echo esc_url(add_query_arg('tab', 'suppliers', home_url('/crm/'))); ?>">Suppliers</a>
                    </li>
                    <li class="<?php echo $current_tab === 'settings' ? 'active' : ''; ?>">
                        <a href="<?php echo esc_url(add_query_arg('tab', 'settings', home_url('/crm/'))); ?>">Settings</a>
                    </li>
                </ul>
            </nav>
        </div>

        <div class="dc-crm-main">
            <?php
            switch ($current_tab) {
                case 'products':
                    require_once DC_PM_PLUGIN_DIR . 'admin/partials/product-management.php';
                    break;

                case 'suppliers':
                    require_once DC_PM_PLUGIN_DIR. 'admin/partials/supplier-management.php';
                    break;

                case 'settings':
                    require_once DC_PM_PLUGIN_DIR . 'admin/partials/settings.php';
                    break;

                default:
                    // Dashboard content
                    $total_products = wp_count_posts('product')->publish;
                    $total_suppliers = wp_count_posts('dc_supplier')->publish;
					
					$supplier = new WP_Query(array(
    'post_type' => 'dc_supplier',
    'orderby'   => 'date',
    'order'     => 'DESC'
));

                    // Get recent products
                    $recent_products = new WP_Query(array(
                        'post_type' => 'product',
                        'posts_per_page' => 5,
                        'orderby' => 'date',
                        'order' => 'DESC'
                    ));
                    ?>
                    <div class="dc-crm-dashboard">
                        <div class="dc-crm-stats">
                            <div class="dc-crm-stat-box">
                                <h3>Total Products</h3>
                                <div class="dc-crm-stat-number"><?php echo esc_html($total_products); ?></div>
                            </div>
                            <div class="dc-crm-stat-box">
                                <h3>Total Suppliers</h3>
                                <div class="dc-crm-stat-number"><?php echo esc_html($supplier->post_count); ?></div>
                            </div>
                        </div>

                        <div class="dc-crm-recent">
                            <h2>Recent Products</h2>
                            <?php if ($recent_products->have_posts()) : ?>
                                <table class="wp-list-table">
                                    <thead>
                                        <tr>
                                            <th>Title</th>
                                            <th>Category</th>
                                            <th>Price</th>
                                            <th>Stock</th>
                                            <th>Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($recent_products->have_posts()) : $recent_products->the_post(); ?>
                                            <tr>
                                                <td>
                                                    <a href="<?php echo esc_url(get_edit_post_link()); ?>">
                                                        <?php the_title(); ?>
                                                    </a>
                                                </td>
                                                <td><?php echo get_the_term_list(get_the_ID(), 'product_cat', '', ', '); ?></td>
                                                <td><?php echo get_post_meta(get_the_ID(), '_price', true); ?></td>
                                                <td><?php echo get_post_meta(get_the_ID(), '_stock', true); ?></td>
                                                <td><?php echo get_the_date(); ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            <?php else : ?>
                                <p>No products found.</p>
                            <?php endif; ?>
                            <?php wp_reset_postdata(); ?>
                        </div>
                    </div>
                    <?php
                    break;
            }
            ?>
        </div>
    </div>
</div>

<?php
// Get the footer
get_footer();
?> 