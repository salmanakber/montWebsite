<?php
/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://example.com
 * @since      1.0.0
 *
 * @package    DC_Product_Manager
 * @subpackage DC_Product_Manager/admin/partials
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get suppliers
$suppliers = array();
if (method_exists($this->supplier_manager, 'get_all_suppliers')) {
    $suppliers = $this->supplier_manager->get_all_suppliers();
}
?>

<div class="dc-supplier-management">
    <div class="dc-page-header">
        <h1><?php _e('Supplier Management', 'dc-product-manager'); ?></h1>
        <button type="button" class="button button-primary" id="add-new-supplier">
            <?php _e('Add New Supplier', 'dc-product-manager'); ?>
        </button>
    </div>

    <div class="dc-content-container">
        <div class="dc-supplier-filters">
            <input type="text" id="supplier-search" class="dc-search-input" placeholder="<?php esc_attr_e('Search suppliers...', 'dc-product-manager'); ?>">
        </div>

        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php esc_html_e('Name', 'dc-product-manager'); ?></th>
                    <th><?php esc_html_e('Contact Name', 'dc-product-manager'); ?></th>
                    <th><?php esc_html_e('Email', 'dc-product-manager'); ?></th>
                    <th><?php esc_html_e('Phone', 'dc-product-manager'); ?></th>
                    <th><?php esc_html_e('Actions', 'dc-product-manager'); ?></th>
                </tr>
            </thead>
            <tbody id="supplier-list">
                <?php if (!empty($suppliers)) : ?>
                    <?php foreach ($suppliers as $supplier) : ?>
                    <tr data-supplier-id="<?php echo esc_attr($supplier['id']); ?>">
                        <td><?php echo esc_html($supplier['name']); ?></td>
                        <td><?php echo esc_html($supplier['contact_name']); ?></td>
                        <td><?php echo esc_html($supplier['email']); ?></td>
                        <td><?php echo esc_html($supplier['phone']); ?></td>
                        <td>
                            <button class="button edit-supplier" data-supplier-id="<?php echo esc_attr($supplier['id']); ?>">
                                <?php esc_html_e('Edit', 'dc-product-manager'); ?>
                            </button>
                            <button class="button button-link-delete delete-supplier" data-supplier-id="<?php echo esc_attr($supplier['id']); ?>">
                                <?php esc_html_e('Delete', 'dc-product-manager'); ?>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="5" class="no-items"><?php esc_html_e('No suppliers found. Click "Add New Supplier" to create one.', 'dc-product-manager'); ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="dc-content-container dc-supplier-form-container" style="display: none;">
        <h2><?php esc_html_e('Edit Supplier', 'dc-product-manager'); ?></h2>
        <form id="supplier-form">
			        <?php wp_nonce_field('dc_product_manager_settings', 'dc_product_manager_settings_nonce'); ?>
            <input type="hidden" id="supplier-id" name="supplier_id" value="">
            
            <div class="dc-form-section">
                <label for="supplier-name"><?php esc_html_e('Name', 'dc-product-manager'); ?></label>
                <input type="text" id="supplier-name" name="name" required>
            </div>

            <div class="dc-form-section">
                <label for="supplier-contact-name"><?php esc_html_e('Contact Name', 'dc-product-manager'); ?></label>
                <input type="text" id="supplier-contact-name" name="contact_name" >
            </div>

            <div class="dc-form-section">
                <label for="supplier-email"><?php esc_html_e('Email', 'dc-product-manager'); ?></label>
                <input type="email" id="supplier-email" name="email" >
            </div>

            <div class="dc-form-section">
                <label for="supplier-phone"><?php esc_html_e('Phone', 'dc-product-manager'); ?></label>
                <input type="tel" id="supplier-phone" name="phone" value="">
            </div>

            <div class="dc-form-section">
                <label for="supplier-address"><?php esc_html_e('Address', 'dc-product-manager'); ?></label>
                <textarea id="supplier-address" name="address" rows="3" value="n/a"></textarea>
            </div>

            <div class="dc-form-actions">
                <button type="submit" class="button button-primary"><?php esc_html_e('Save Changes', 'dc-product-manager'); ?></button>
                <button type="button" class="button cancel-edit"><?php esc_html_e('Cancel', 'dc-product-manager'); ?></button>
            </div>
        </form>
    </div>
</div> 