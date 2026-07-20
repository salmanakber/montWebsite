<?php

/**
 * Settings definitions.
 *
 * @package WPDesk\WooCommerceShipping\DhlExpress
 */
namespace DhlVendor\WPDesk\WooCommerceShipping\DhlExpress;

use DhlVendor\WPDesk\DhlExpressShippingService\DhlSettingsDefinition;
use DhlVendor\WPDesk\DhlExpressShippingService\DhlShippingService;
use DhlVendor\WPDesk\WooCommerceShipping\ApiStatus\ApiStatusSettingsDefinitionDecorator;
use DhlVendor\WPDesk\WooCommerceShipping\CustomFields\ApiStatus\FieldApiStatus;
use DhlVendor\WPDesk\WooCommerceShipping\ShopSettings;
/**
 * Can handle global and instance settings for WooCommerce shipping method.
 */
class DhlSettingsDefinitionWooCommerce extends DhlSettingsDefinition
{
    protected $global_method_fields = [DhlSettingsDefinition::DHL_HEADER, DhlSettingsDefinition::FIELD_API_TYPE, DhlSettingsDefinition::CREDENTIALS_HEADER, DhlSettingsDefinition::FIELD_SITE_ID, DhlSettingsDefinition::FIELD_API_PASSWORD, DhlSettingsDefinition::FIELD_API_KEY, DhlSettingsDefinition::FIELD_API_SECRET, DhlSettingsDefinition::FIELD_ACCOUNT_NUMBER, DhlSettingsDefinition::FIELD_TESTING, DhlSettingsDefinition::SHIPPING_METHOD_HEADER, DhlSettingsDefinition::ENABLE_SHIPPING_METHOD, DhlSettingsDefinition::ADVANCED_OPTIONS_HEADER, DhlSettingsDefinition::DEBUG_MODE, DhlSettingsDefinition::FIELD_UNITS, ApiStatusSettingsDefinitionDecorator::API_STATUS];
    private $instance_and_method_fields = [DhlSettingsDefinition::METHOD_TITLE];
    /**
     * Form fields.
     *
     * @var array
     */
    private $form_fields;
    /**
     * UpsSettingsDefinitionWooCommerce constructor.
     *
     * @param array $form_fields Form fields.
     */
    public function __construct(array $form_fields)
    {
        $this->form_fields = $form_fields;
    }
    /**
     * Get instance form fields.
     *
     * @return array
     */
    public function get_instance_form_fields()
    {
        return $this->filter_instance_fields($this->form_fields, \true);
    }
    /**
     * Get global method fields.
     *
     * @return array
     */
    protected function get_global_method_fields()
    {
        return $this->global_method_fields;
    }
    /**
     * Filter instance form fields.
     *
     * @param array $all_fields .
     * @param bool  $instance_fields .
     *
     * @return array
     */
    private function filter_instance_fields(array $all_fields, $instance_fields)
    {
        $fields = array();
        foreach ($all_fields as $key => $field) {
            $is_instance_field = !in_array($key, $this->get_global_method_fields(), \true) || in_array($key, $this->instance_and_method_fields, \true);
            if ($instance_fields === $is_instance_field) {
                $fields[$key] = $field;
            }
        }
        return $fields;
    }
    public function get_form_fields()
    {
        return $this->form_fields;
    }
}
