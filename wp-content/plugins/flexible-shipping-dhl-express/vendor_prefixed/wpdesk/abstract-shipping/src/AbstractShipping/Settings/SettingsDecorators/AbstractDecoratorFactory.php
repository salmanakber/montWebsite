<?php

/**
 * Class AbstractDecoratorFactory
 * @package WPDesk\AbstractShipping\Settings\SettingsDecorators
 */
namespace DhlVendor\WPDesk\AbstractShipping\Settings\SettingsDecorators;

use DhlVendor\WPDesk\AbstractShipping\Settings\DefinitionModifier\SettingsDefinitionModifierAfter;
use DhlVendor\WPDesk\AbstractShipping\Settings\DefinitionModifier\SettingsDefinitionModifierBefore;
use DhlVendor\WPDesk\AbstractShipping\Settings\SettingsDefinition;
/**
 * Abstract factory.
 */
abstract class AbstractDecoratorFactory
{
    /**
     * @param SettingsDefinition $settings_definition .
     * @param string             $related_field_id .
     * @param bool               $before .
     * @param string             $field_id .
     *
     * @return SettingsDefinition
     */
    public function create_decorator(SettingsDefinition $settings_definition, $related_field_id, $before = \true, $field_id = null)
    {
        $decorator_class = $this->get_settings_definition_modifier_class($before);
        return new $decorator_class($settings_definition, $related_field_id, empty($field_id) ? $this->get_field_id() : $field_id, $this->get_field_settings());
    }
    /**
     * @return string
     */
    abstract protected function get_field_settings();
    /**
     * @return array
     */
    abstract public function get_field_id();
    /**
     * @param bool $before .
     *
     * @return string
     */
    protected function get_settings_definition_modifier_class($before = \true)
    {
        return $before ? SettingsDefinitionModifierBefore::class : SettingsDefinitionModifierAfter::class;
    }
}
