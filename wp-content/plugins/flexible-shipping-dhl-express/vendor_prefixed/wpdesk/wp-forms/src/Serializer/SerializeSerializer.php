<?php

// phpcs:disable WordPress.PHP.DiscouragedPHPFunctions
namespace DhlVendor\WPDesk\Forms\Serializer;

use DhlVendor\WPDesk\Forms\Serializer;
class SerializeSerializer implements Serializer
{
    public function serialize($value): string
    {
        return serialize($value);
    }
    public function unserialize(string $value)
    {
        return unserialize($value);
    }
}
