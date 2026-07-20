<?php

namespace DhlVendor\WPDesk\WooCommerceShipping\ShippingMethod\Traits;

use DhlVendor\WPDesk\AbstractShipping\Settings\SettingsDefinition;
use DhlVendor\WPDesk\AbstractShipping\Settings\SettingsValuesAsArray;
use DhlVendor\WPDesk\AbstractShipping\ShippingService;
use DhlVendor\WPDesk\AbstractShipping\ShippingServiceCapability\CanTestSettings;
use DhlVendor\WPDesk\WooCommerceShipping\ApiStatus\ApiStatusSettingsDefinitionDecorator;
use DhlVendor\WPDesk\WooCommerceShipping\CustomFields\CouldNotFindService;
use DhlVendor\WPDesk\WooCommerceShipping\CustomFields\Services\FieldServices;
use DhlVendor\WPDesk\WooCommerceShipping\CustomFields\FieldHandlingFees;
use DhlVendor\WPDesk\WooCommerceShipping\CustomFields\FieldsFactory;
use DhlVendor\WPDesk\WooCommerceShipping\CustomOrigin\CustomOriginFields;
use DhlVendor\WPDesk\WooCommerceShipping\CustomOrigin\InstanceCustomOriginFields;
use DhlVendor\WPDesk\WooCommerceShipping\FreeShipping\FreeShippingFields;
use DhlVendor\WPDesk\WooCommerceShipping\HandlingFees\PriceAdjustmentNone;
use DhlVendor\WPDesk\WooCommerceShipping\PluginShippingDecisions;
use DhlVendor\WPDesk\WooCommerceShipping\ShippingMethod\HasCustomOrigin;
use DhlVendor\WPDesk\WooCommerceShipping\ShippingMethod\HasInstanceCustomOrigin;
use DhlVendor\WPDesk\WooCommerceShipping\ShippingMethod\HasFreeShipping;
use DhlVendor\WPDesk\WooCommerceShipping\ShippingMethod\HasHandlingFees;
use DhlVendor\WPDesk\WooCommerceShipping\ShippingMethod\MethodFieldsFactory;
use DhlVendor\WPDesk\WooCommerceShipping\ShippingMethod\RateMethod\Fallback\FallbackRateMethod;
use DhlVendor\WPDesk\WooCommerceShipping\ShippingMethod\RulesTableAdv;
/**
 * Job of this trait is to render/save/load settings fields using WC_Shipping_Method methods or FieldsFactory.
 *
 * @package WPDesk\WooCommerceShipping\ShippingMethod\Traits
 */
trait SettingsTrait
{
    /**
     * For internal caching purpose.
     *
     * @var FieldsFactory
     */
    protected $fields_factory;
    /**
     * Returns decorated settings definitions from service.
     *
     * @param PluginShippingDecisions $plugin_shipping_decisions .
     *
     * @return SettingsDefinition .
     */
    protected function get_settings_definition_from_service(PluginShippingDecisions $plugin_shipping_decisions)
    {
        $shipping_service = $plugin_shipping_decisions->get_shipping_service();
        $settings_definitions = $shipping_service->get_settings_definition();
        if ($shipping_service instanceof CanTestSettings) {
            $settings_definitions = new ApiStatusSettingsDefinitionDecorator($settings_definitions, $shipping_service->get_field_before_api_status_field(), $plugin_shipping_decisions->get_field_api_status_ajax(), $shipping_service->get_unique_id());
        }
        return $settings_definitions;
    }
    /**
     * Returns decorated form fields if needed.
     *
     * @param PluginShippingDecisions $plugin_shipping_decisions .
     *
     * @return array
     */
    private function get_form_fields_from_shipping_service(PluginShippingDecisions $plugin_shipping_decisions)
    {
        return $this->get_settings_definition_from_service($plugin_shipping_decisions)->get_form_fields();
    }
    /**
     * Get the form fields after they are initialized.
     *
     * @return array of options
     */
    public function get_form_fields()
    {
        $allowed_shipping_methods_global_settings = apply_filters('flexible-shipping/integration/allowed-shipping-methods-global-settings', []);
        if (in_array($this->id, $allowed_shipping_methods_global_settings, \true)) {
            return $this->prepare_custom_field_types($this->append_advert_fields(parent::get_form_fields()));
        }
        return $this->prepare_custom_field_types(parent::get_form_fields());
    }
    /**
     * Get settings fields for instances of this shipping method (within zones).
     */
    public function get_instance_form_fields()
    {
        return $this->prepare_custom_field_types($this->append_advert_fields(parent::get_instance_form_fields()));
    }
    /**
     * Append advert fields.
     *
     * @param array $fields Fields.
     *
     * @return array
     */
    private function append_advert_fields(array $fields): array
    {
        return $this->rules_table_adv->add_fields($fields);
    }
    private function append_flexible_shipping_rules_table_description(array $fields): array
    {
        return $this->rules_table_adv->append_flexible_shipping_rules_table_description($fields, $this->id);
    }
    /**
     * Generate Settings HTML.
     *
     * @param array $form_fields Form fields.
     * @param bool $echo Show or return.
     *
     * @return string Generated settings
     * @throws \Exception View doesn't exists.
     *
     */
    public function generate_settings_html($form_fields = array(), $echo = \true)
    {
        if (empty($form_fields)) {
            $form_fields = $this->get_form_fields();
        }
        $settings = $this->create_settings_values_as_array();
        $html = '';
        foreach ($form_fields as $field_id => $values) {
            $type = $this->get_field_type($values);
            $options_generator = $values['options_generator'] ?? '';
            if ($options_generator === CustomOriginFields::OPTIONS_GENERATOR_COUNTRY_STATE) {
                $values['options'] = $this->prepare_country_state_options();
            }
            if ($options_generator === InstanceCustomOriginFields::OPTIONS_GENERATOR_COUNTRY_STATE_FOR_ORIGIN) {
                $values['options'] = $this->prepare_country_state_options($this->get_origin_country_code());
                $values['default'] = $this->get_origin_country_state();
            }
            if ($settings->has_value($field_id)) {
                $values['value'] = $settings->get_value($field_id);
            }
            if (method_exists($this, 'generate_' . $type . '_html')) {
                $html .= $this->{'generate_' . $type . '_html'}($field_id, $values);
            } elseif ($type === 'number') {
                $html .= $this->generate_text_html($field_id, $values);
            } elseif (has_filter('woocommerce_generate_' . $type . '_html')) {
                $html .= apply_filters('woocommerce_generate_' . $type . '_html', '', $field_id, $values, $this);
            } else {
                try {
                    $custom_field = $this->create_fields_factory()->create_field($type, $values);
                } catch (CouldNotFindService $e) {
                    $custom_field = null;
                }
                if (null !== $custom_field) {
                    $html .= $custom_field->render($this->get_field_params($field_id, $values), $this);
                }
            }
        }
        if ($echo) {
            echo $html;
            // WPCS: XSS ok.
        } else {
            return $html;
        }
        return $html;
    }
    /**
     * Prepare country state options.
     *
     * @param string|null $origin_country_code
     *
     * @return array
     */
    protected function prepare_country_state_options($origin_country_code = null)
    {
        $country_state_options = $this->get_countries();
        foreach ($country_state_options as $country_code => $country) {
            if ($origin_country_code !== null && $origin_country_code !== $country_code) {
                unset($country_state_options[$country_code]);
            } else {
                $states = WC()->countries->get_states($country_code);
                if ($states) {
                    unset($country_state_options[$country_code]);
                    foreach ($states as $state_code => $state_name) {
                        $country_state_options[$country_code . ':' . $state_code] = $country . ' &mdash; ' . $state_name;
                    }
                }
                unset($states);
            }
        }
        return $country_state_options;
    }
    private function get_countries()
    {
        if (WC()->countries) {
            $countries = WC()->countries->get_countries();
            if (isset($countries)) {
                return $countries;
            }
        }
        return [];
    }
    /**
     * Get a field's posted and validated value.
     *
     * @param string $key Field key.
     * @param array $field Field array.
     * @param array $post_data Posted data.
     *
     * @return string
     */
    public function get_field_value($key, $field, $post_data = array())
    {
        $type = $this->get_field_type($field);
        $field_key = $this->get_field_key($key);
        $post_data = empty($post_data) ? $_POST : $post_data;
        // WPCS: CSRF ok, input var ok.
        $value = isset($post_data[$field_key]) ? $post_data[$field_key] : null;
        if ($this->create_fields_factory()->is_field_supported($type)) {
            return $this->create_fields_factory()->create_field($type, $post_data)->sanitize($value);
        }
        return parent::get_field_value($key, $field, $post_data);
    }
    /**
     * Prepare custom field types.
     *
     * @param $fields
     *
     * @return array
     *
     * @TODO: Breaks OCP. Move to Placeholder factory.
     */
    private function prepare_custom_field_types($fields)
    {
        $fields = $this->replace_fallback_field_if_exists($fields);
        if ($this instanceof HasHandlingFees) {
            $fields = $this->replace_handling_fees_field_if_exists($fields);
        }
        if ($this instanceof HasCustomOrigin) {
            $custom_origin_fields = new CustomOriginFields($this instanceof HasInstanceCustomOrigin);
            $fields = $custom_origin_fields->replace_fallback_field_if_exists($fields, $this);
        }
        if ($this instanceof HasInstanceCustomOrigin) {
            $instance_custom_origin_fields = new InstanceCustomOriginFields(\true);
            $fields = $instance_custom_origin_fields->replace_fallback_field_if_exists($fields, $this);
        }
        if ($this instanceof HasFreeShipping) {
            $free_shipping_fields = new FreeShippingFields();
            $fields = $free_shipping_fields->replace_fields($fields);
        }
        $fields = $this->setup_sanitize_callback_on_services_field($fields);
        return $fields;
    }
    /**
     * Always creates fields factory. Can be overwritten to change factory.
     *
     * @return FieldsFactory
     */
    protected function create_fields_factory()
    {
        if ($this->fields_factory === null) {
            $this->fields_factory = new MethodFieldsFactory();
        }
        return $this->fields_factory;
    }
    /**
     * Replace fallback fake field with checkbox and input field in settings.
     *
     * @param $settings
     *
     * @return array
     */
    private function replace_fallback_field_if_exists($settings)
    {
        $new_settings = [];
        foreach ($settings as $key => $field) {
            if ($field['type'] === FallbackRateMethod::FIELD_TYPE_FALLBACK) {
                $new_settings[FallbackRateMethod::FIELD_ENABLE_FALLBACK] = ['title' => __('Fallback', 'flexible-shipping-dhl-express'), 'type' => 'checkbox', 'label' => __('Enable fallback', 'flexible-shipping-dhl-express'), 'description' => __('Enable to offer flat rate cost for shipping so that the user can still checkout, if API for some reason returns no matching rates.', 'flexible-shipping-dhl-express'), 'desc_tip' => \true, 'default' => 'no'];
                $new_settings[FallbackRateMethod::FIELD_FALLBACK_COST] = ['title' => __('Fallback Cost', 'flexible-shipping-dhl-express'), 'type' => 'price', 'required' => \true, 'description' => __('Enter only a numeric value without the currency symbol.', 'flexible-shipping-dhl-express'), 'desc_tip' => \true, 'default' => ''];
            } else {
                $new_settings[$key] = $field;
            }
        }
        return $new_settings;
    }
    /**
     * Replace handling fees fake field with checkbox and input field in settings.
     *
     * @param array $settings Settings fields.
     *
     * @return array
     */
    private function replace_handling_fees_field_if_exists($settings)
    {
        $new_settings = [];
        foreach ($settings as $key => $field) {
            if ($field['type'] === FieldHandlingFees::FIELD_TYPE) {
                $field_handling_fees = new FieldHandlingFees();
                $new_settings = $field_handling_fees->add_to_settings($new_settings, $field);
            } else {
                $new_settings[$key] = $field;
            }
        }
        return $new_settings;
    }
    /**
     * Setup sanitize callback on services field.
     *
     * @param $settings
     *
     * @return mixed
     *
     * @TODO: move to custom field.
     *
     */
    private function setup_sanitize_callback_on_services_field($settings)
    {
        foreach ($settings as $key => $field) {
            if (isset($field['type']) && FieldServices::FIELD_TYPE === $field['type']) {
                $settings[$key]['sanitize_callback'] = [FieldServices::class, 'sanitize'];
            }
        }
        return $settings;
    }
    /**
     * Get field params
     *
     * @param string $key Field key.
     * @param array $data Data.
     *
     * @return array
     *
     * @TODO: is this really necessary?
     */
    private function get_field_params($key, $data)
    {
        $field_key = $this->get_field_key($key);
        $defaults = ['field_key' => $field_key, 'title' => '', 'disabled' => \false, 'class' => '', 'css' => '', 'placeholder' => '', 'type' => 'text', 'desc_tip' => \false, 'description' => '', 'custom_attributes' => [], 'value' => ''];
        $data = wp_parse_args($data, $defaults);
        return $data;
    }
    /**
     * Render shipping method settings.
     *
     * @throws \Exception .
     */
    public function admin_options()
    {
        if ($this->instance_id) {
            $settings_html = $this->generate_settings_html($this->append_flexible_shipping_rules_table_description($this->get_instance_form_fields()), \false);
        } else {
            $settings_html = $this->generate_settings_html($this->append_flexible_shipping_rules_table_description($this->get_form_fields()), \false);
        }
        $service_id = $this->id;
        include __DIR__ . '/view/shipping-method-settings-html.php';
        echo $this->create_fields_factory()->render_used_fields_footers();
        /** @TODO: move to custom field & field footer. */
        $settings_prefix = 'woocommerce_' . $this->id;
        include __DIR__ . '/view/shipping-method-java-script-fallback.php';
        include __DIR__ . '/view/shipping-method-java-script-custom-services.php';
        include __DIR__ . '/view/shipping-method-java-script-custom-origin.php';
        include __DIR__ . '/view/shipping-method-java-script-fs-table-rate-adv.php';
        if ($this instanceof HasFreeShipping) {
            include __DIR__ . '/view/shipping-method-java-script-free-shipping.php';
        }
        /** @TODO: move to custom field & field footer. */
        if ($this instanceof HasHandlingFees) {
            $price_adjustment_type_field = $settings_prefix . '_' . FieldHandlingFees::OPTION_PRICE_ADJUSTMENT_TYPE;
            $price_adjustment_value_field = $settings_prefix . '_' . FieldHandlingFees::OPTION_PRICE_ADJUSTMENT_VALUE;
            $price_adjustment_type_none = PriceAdjustmentNone::ADJUSTMENT_TYPE;
            include __DIR__ . '/view/shipping-method-java-script-handling-fees.php';
        }
        include __DIR__ . '/view/shipping-method-java-script-dynamic-fields.php';
    }
    /**
     * @return SettingsValuesAsArray
     */
    public function create_settings_values_as_array()
    {
        return new SettingsValuesAsArray(array_merge($this->settings, $this->instance_id ? $this->instance_settings : []));
    }
}
