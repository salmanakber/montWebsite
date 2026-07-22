<?php
/**
 * Multi-currency product pricing for WooCommerce.
 *
 * @package DC_Product_Manager
 */

namespace DC_Product_Manager;

if (!defined('ABSPATH')) {
    exit;
}

class DC_Multi_Currency {

    const META_KEY = '_dc_multicurrency_prices';

    public static function get_currencies() {
        $currencies = array();
        foreach (DC_Region_Currency::get_regions() as $region) {
            $currencies[$region['currency']] = $region;
        }
        return $currencies;
    }

    /**
     * Get stored multi-currency prices for a product/variation.
     */
    public static function get_prices($product_id) {
        $prices = get_post_meta($product_id, self::META_KEY, true);
        return is_array($prices) ? $prices : array();
    }

    /**
     * Save multi-currency prices.
     */
    public static function save_prices($product_id, $prices) {
        $clean = array();
        foreach (self::get_currencies() as $code => $region) {
            if (isset($prices[$code]) && $prices[$code] !== '' && $prices[$code] !== null) {
                $clean[$code] = wc_format_decimal($prices[$code]);
            }
        }
        update_post_meta($product_id, self::META_KEY, $clean);
        return $clean;
    }

    /**
     * Resolve price for the active currency with fallback to WC default price.
     */
    public static function resolve_price($product, $currency = null) {
        if (!$product) {
            return '';
        }

        $currency = $currency ?: DC_Region_Currency::get_current_currency();
        $product_id = $product->get_id();
        $prices = self::get_prices($product_id);

        if (!empty($prices[$currency])) {
            return $prices[$currency];
        }

        $default_price = $product->get_meta('_regular_price');
        if ($default_price === '' || $default_price === null) {
            $default_price = $product->get_meta('_price');
        }

        return $default_price;
    }

    public function init() {
        add_filter('woocommerce_product_get_price', array($this, 'filter_price'), 99, 2);
        add_filter('woocommerce_product_get_regular_price', array($this, 'filter_price'), 99, 2);
        add_filter('woocommerce_product_variation_get_price', array($this, 'filter_price'), 99, 2);
        add_filter('woocommerce_product_variation_get_regular_price', array($this, 'filter_price'), 99, 2);
        add_filter('woocommerce_variation_prices_price', array($this, 'filter_variation_price'), 99, 3);
        add_filter('woocommerce_variation_prices_regular_price', array($this, 'filter_variation_price'), 99, 3);
        add_filter('woocommerce_get_variation_prices_hash', array($this, 'variation_prices_hash'), 10, 3);
        add_filter('woocommerce_currency_symbol', array($this, 'filter_currency_symbol'), 10, 2);
        add_filter('woocommerce_price_format', array($this, 'filter_price_format'), 10, 2);
        add_filter('woocommerce_get_price_decimals', array($this, 'filter_price_decimals'));
    }

    public function filter_price($price, $product) {
        $resolved = self::resolve_price($product);
        return $resolved !== '' ? $resolved : $price;
    }

    public function filter_variation_price($price, $variation, $product) {
        $resolved = self::resolve_price($variation);
        return $resolved !== '' ? $resolved : $price;
    }

    /**
     * Bust variation price cache when currency changes.
     */
    public function variation_prices_hash($hash, $product, $display) {
        $hash[] = DC_Region_Currency::get_current_currency();
        $hash[] = DC_Region_Currency::get_current_region_slug();
        return $hash;
    }

    public function filter_currency_symbol($symbol, $currency) {
        foreach (DC_Region_Currency::get_regions() as $region) {
            if ($region['currency'] === $currency) {
                return $region['symbol'];
            }
        }
        return $symbol;
    }

    public function filter_price_format($format, $currency_pos) {
        $currency = DC_Region_Currency::get_current_currency();
        if ($currency === 'NOK') {
            return '%2$s %1$s';
        }
        if ($currency === 'VND') {
            return '%1$s%2$s';
        }
        return $format;
    }

    public function filter_price_decimals($decimals) {
        static $resolving = false;
        if ($resolving) {
            return $decimals;
        }
        $resolving = true;
        $currency = DC_Region_Currency::get_current_currency();
        $resolving = false;
        if ($currency === 'VND') {
            return 0;
        }
        return $decimals;
    }

    /**
     * Apply multi-currency prices to all variations of a variable product.
     */
    public static function save_prices_for_product($product_id, $prices, $wc_product = null) {
        if (!$wc_product) {
            $wc_product = wc_get_product($product_id);
        }
        if (!$wc_product) {
            return;
        }

        self::save_prices($product_id, $prices);

        if ($wc_product->is_type('variable')) {
            foreach ($wc_product->get_children() as $variation_id) {
                self::save_prices($variation_id, $prices);
            }
        }
    }

    /**
     * Get prices for CRM edit form (from parent or first variation).
     */
    public static function get_product_edit_prices($product_id) {
        $wc_product = wc_get_product($product_id);
        if (!$wc_product) {
            return array();
        }

        $prices = self::get_prices($product_id);
        if (!empty($prices)) {
            return $prices;
        }

        if ($wc_product->is_type('variable')) {
            $children = $wc_product->get_children();
            if (!empty($children)) {
                return self::get_prices($children[0]);
            }
        }

        $default = $wc_product->get_regular_price();
        return $default ? array('NOK' => $default) : array();
    }
}
