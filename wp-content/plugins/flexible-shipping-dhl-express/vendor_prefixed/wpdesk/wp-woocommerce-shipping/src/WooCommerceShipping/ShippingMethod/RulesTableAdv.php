<?php

namespace DhlVendor\WPDesk\WooCommerceShipping\ShippingMethod;

class RulesTableAdv
{
    public const FS_METHOD_RULES_TITLE = 'fs_method_rules_title';
    public const FS_METHOD_RULES = 'fs_method_rules';
    public const FS_CALCULATION_ENABLED = 'fs_calculation_enabled';
    public function add_fields(array $fields): array
    {
        if (empty($fields[self::FS_METHOD_RULES_TITLE])) {
            $fields[self::FS_METHOD_RULES_TITLE] = ['type' => 'title', 'title' => __('Additional costs by Flexible Shipping Table Rate', 'flexible-shipping-dhl-express'), 'default' => ''];
            $fields[self::FS_CALCULATION_ENABLED] = ['title' => __('Additional costs', 'flexible-shipping-dhl-express'), 'type' => 'checkbox', 'label' => __('Enable Flexible Shipping Rules Table', 'flexible-shipping-dhl-express'), 'desc_tip' => __('Enabling this option will allow you to adjust the calculated shipping cost, with additional charges applied according to the shipping cost calculation rules.', 'flexible-shipping-dhl-express'), 'default' => 'no', 'class' => 'fs-costs-calculation-enabled-adv'];
            $fields[self::FS_METHOD_RULES] = ['title' => __('Shipping Cost Calculation Rules', 'flexible-shipping-dhl-express'), 'type' => 'text', 'class' => 'hidden fs-method-rules', 'description' => '', 'default' => ''];
        }
        return $fields;
    }
    public function append_flexible_shipping_rules_table_description(array $fields, $method_id): array
    {
        if (isset($fields[self::FS_METHOD_RULES]['type']) && 'text' === $fields[self::FS_METHOD_RULES]['type']) {
            $fields[self::FS_METHOD_RULES]['description'] = $this->get_flexible_shipping_plugin_action();
        }
        return $fields;
    }
    private function get_flexible_shipping_plugin_action(): string
    {
        $slug = 'flexible-shipping';
        $plugin_file = 'flexible-shipping/flexible-shipping.php';
        $plugin_status = $this->get_flexible_shipping_plugin_status($plugin_file);
        $plugin_url = 'https://octol.io/fs-tr-adv-live-rates-plugin';
        switch ($plugin_status) {
            case 'active':
                return sprintf(__('You can update %1$sFlexible Shipping%2$s plugin to define additional costs for this shipping method. %3$s', 'flexible-shipping-dhl-express'), '<a href="' . esc_url($plugin_url) . '" target="_blank">', '</a>', $this->create_update_link($slug, 'plugin-update', __('Update Now', 'flexible-shipping-dhl-express')));
            case 'inactive':
                return sprintf(__('You can activate %1$sFlexible Shipping%2$s plugin to define additional costs for this shipping method. %3$s', 'flexible-shipping-dhl-express'), '<a href="' . esc_url($plugin_url) . '" target="_blank">', '</a>', $this->create_plugins_page_link($plugin_file, __('Activate Now', 'flexible-shipping-dhl-express')));
            case 'not installed':
                return sprintf(__('You can install %1$sFlexible Shipping%2$s plugin to define additional costs for this shipping method. %3$s', 'flexible-shipping-dhl-express'), '<a href="' . esc_url($plugin_url) . '" target="_blank">', '</a>', $this->create_update_link($slug, 'plugin-install', __('Install Now', 'flexible-shipping-dhl-express')));
            default:
                return '';
        }
    }
    private function get_flexible_shipping_plugin_status(string $plugin_file): string
    {
        if (defined('DhlVendor\FLEXIBLE_SHIPPING_VERSION')) {
            $status = 'active';
        } else if (is_plugin_inactive($plugin_file)) {
            $status = 'inactive';
        } else {
            $status = 'not installed';
        }
        return $status;
    }
    private function create_update_link(string $slug, string $action, string $link_text): string
    {
        $action_link = wp_nonce_url(add_query_arg(['action' => $action, 'plugin' => $slug], admin_url('update.php')), $action . '_' . $slug);
        return sprintf('%1$s%2$s%3$s', '<a class="oct-btn-more" href="' . esc_url($action_link) . '" target="_blank">', $link_text, '</a>');
    }
    private function create_plugins_page_link(string $plugin_file, string $link_text): string
    {
        $action_link = add_query_arg(['_wpnonce' => wp_create_nonce('activate-plugin_' . $plugin_file), 'action' => 'activate', 'plugin' => $plugin_file], admin_url('plugins.php'));
        return sprintf('%1$s%2$s%3$s', '<a class="oct-btn-more" href="' . esc_url($action_link) . '" target="_blank">', $link_text, '</a>');
    }
}
