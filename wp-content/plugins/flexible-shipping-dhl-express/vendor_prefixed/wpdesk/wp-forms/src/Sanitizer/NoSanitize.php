<?php

namespace DhlVendor\WPDesk\Forms\Sanitizer;

use DhlVendor\WPDesk\Forms\Sanitizer;
class NoSanitize implements Sanitizer
{
    public function sanitize($value)
    {
        return $value;
    }
}
