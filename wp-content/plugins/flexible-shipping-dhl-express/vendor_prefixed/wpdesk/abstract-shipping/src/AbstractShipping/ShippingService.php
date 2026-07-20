<?php

/**
 * Shipping service abstract: ShippingService class.
 *
 * @package WPDesk\AbstractShipping
 */
namespace DhlVendor\WPDesk\AbstractShipping;

use DhlVendor\Psr\Log\LoggerAwareInterface;
use DhlVendor\WPDesk\AbstractShipping\Settings\SettingsDefinition;
/**
 * Basic abstract class for shipping classes.
 *
 * @package WPDesk\AbstractShipping
 */
abstract class ShippingService implements LoggerAwareInterface
{
    /**
     * Get unique ID.
     *
     * @return string
     */
    abstract public function get_unique_id();
    /**
     * Get name.
     *
     * @return string
     */
    abstract public function get_name();
    /**
     * Get description.
     *
     * @return string
     */
    abstract public function get_description();
    /**
     * Get settings definitions.
     *
     * @return SettingsDefinition
     */
    abstract public function get_settings_definition();
}
