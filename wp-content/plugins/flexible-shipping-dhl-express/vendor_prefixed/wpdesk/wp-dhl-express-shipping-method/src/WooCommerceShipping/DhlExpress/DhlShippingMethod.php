<?php

/**
 * DHL Shipping Method.
 *
 * @package WPDesk\FlexibleShippingDhl
 */
namespace DhlVendor\WPDesk\WooCommerceShipping\DhlExpress;

use DhlVendor\WPDesk\DhlExpressShippingService\DhlSettingsDefinition;
use DhlVendor\WPDesk\DhlExpressShippingService\DhlShippingService;
use DhlVendor\WPDesk\WooCommerceShipping\CustomFields\ApiStatus\FieldApiStatusAjax;
use DhlVendor\WPDesk\WooCommerceShipping\ShippingMethod;
use DhlVendor\WPDesk\WooCommerceShippingPro\Packer\PackerFactory;
use DhlVendor\WPDesk\WooCommerceShippingPro\Packer\PackerSettings;
use DhlVendor\WPDesk\WooCommerceShippingPro\ShippingBuilder\WooCommerceShippingBuilder;
/**
 * DHL Shipping Method.
 */
class DhlShippingMethod extends ShippingMethod implements ShippingMethod\HasRateCache
{
    const UNIQUE_ID = DhlShippingService::UNIQUE_ID;
    /**
     * .
     *
     * @var FieldApiStatusAjax
     */
    protected static $api_status_ajax_handler;
    /**
     * .
     *
     * @param int $instance_id Instance ID.
     */
    public function __construct($instance_id = 0)
    {
        parent::__construct($instance_id);
        $this->title = $this->get_option('title', $this->title);
        /* @phpstan-ignore-line */
    }
    /**
     * Init form fields.
     */
    public function build_form_fields()
    {
        $settings_definition = new DhlSettingsDefinitionWooCommerce($this->form_fields);
        $this->form_fields = $settings_definition->get_form_fields();
        $this->instance_form_fields = $settings_definition->get_instance_form_fields();
    }
    /**
     * Is unit metric?
     *
     * @return bool
     */
    private function is_unit_metric()
    {
        return isset($this->settings[DhlSettingsDefinition::FIELD_UNITS]) ? DhlSettingsDefinition::UNITS_METRIC === $this->settings[DhlSettingsDefinition::FIELD_UNITS] : \true;
    }
    /**
     * Init.
     *
     * @return void
     */
    protected function init()
    {
        parent::init();
        $packer_settings = new PackerSettings('');
        $packaging_method = $packer_settings->get_packaging_method($this);
        $packer_factory = new PackerFactory($packaging_method);
        $packer = $packer_factory->create_packer(array());
        $this->shipping_builder = new WooCommerceShippingBuilder($packer, $packaging_method, $this->is_unit_metric());
    }
    /**
     * @return bool
     */
    protected function should_calculate_shipping()
    {
        return \true;
    }
    /**
     * Render shipping method settings.
     *
     * @throws \Exception .
     *
     * @return void
     */
    public function admin_options()
    {
        parent::admin_options();
        include __DIR__ . '/views/html-payment-account-number.php';
        if (0 === $this->instance_id) {
            $this->output_settings_script();
        }
    }
    private function output_settings_script()
    {
        include __DIR__ . '/views/settings-scrips.php';
    }
}
