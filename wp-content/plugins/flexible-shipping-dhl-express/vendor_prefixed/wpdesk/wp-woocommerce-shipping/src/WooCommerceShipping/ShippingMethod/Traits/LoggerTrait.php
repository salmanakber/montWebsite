<?php

namespace DhlVendor\WPDesk\WooCommerceShipping\ShippingMethod\Traits;

use DhlVendor\Psr\Log\LoggerInterface;
use DhlVendor\WPDesk\WooCommerceShipping\ShippingMethod;
/**
 * Implements basic logs methods.
 *
 * @package WPDesk\WooCommerceShipping\ShippingMethod\Traits
 */
trait LoggerTrait
{
    /**
     * @param ShippingMethod $shipping_method
     *
     * @return LoggerInterface
     */
    private function get_logger(ShippingMethod $shipping_method)
    {
        return $shipping_method->get_plugin_shipping_decisions()->get_logger();
    }
    /**
     * User can see debug notices?
     *
     * @return bool
     */
    private function can_see_debug_notices(): bool
    {
        return 'yes' === $this->get_option('debug_mode', 'no') && current_user_can('manage_woocommerce');
    }
    /**
     * User can see error notices?
     *
     * @return bool
     */
    private function can_see_error_notices(): bool
    {
        return current_user_can('manage_woocommerce');
    }
}
