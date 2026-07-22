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

    /** @var bool Prevent recursive price filter loops. */
    private static $filtering = false;

    public static function get_currencies() {
        $currencies = array();
        foreach (DC_Region_Currency::get_regions() as $region) {
            $currencies[$region['currency']] = $region;
        }
        return $currencies;
    }

    public static function get_prices($product_id) {
        $prices = get_post_meta($product_id, self::META_KEY, true);
        return is_array($prices) ? $prices : array();
    }

    public static function save_prices($product_id, $prices) {
        $clean = array();
        foreach (array_keys(self::get_currencies()) as $code) {
            if (isset($prices[$code]) && $prices[$code] !== '' && $prices[$code] !== null) {
                $clean[$code] = wc_format_decimal($prices[$code]);
            }
        }
        update_post_meta($product_id, self::META_KEY, $clean);
        return $clean;
    }

    /**
     * Resolve price for active currency using raw meta only (no WC getters).
     */
    public static function resolve_price($product, $currency = null) {
        if (!$product || self::$filtering) {
            return '';
        }

        $currency = $currency ? $currency : DC_Region_Currency::get_current_currency();
        $product_id = $product->get_id();
        $prices = self::get_prices($product_id);

        if (isset($prices[$currency]) && $prices[$currency] !== '') {
            return $prices[$currency];
        }

        // Raw meta only — never call get_price()/get_regular_price() (would recurse).
        $default_price = get_post_meta($product_id, '_regular_price', true);
        if ($default_price === '' || $default_price === null) {
            $default_price = get_post_meta($product_id, '_price', true);
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
        add_filter('woocommerce_price_num_decimals', array($this, 'filter_price_decimals'), 10, 1);
    }

    public function filter_price($price, $product) {
        if (self::$filtering) {
            return $price;
        }

        self::$filtering = true;
        $resolved = self::resolve_price($product);
        self::$filtering = false;

        return ($resolved !== '' && $resolved !== null) ? $resolved : $price;
    }

    public function filter_variation_price($price, $variation, $product) {
        if (self::$filtering) {
            return $price;
        }

        self::$filtering = true;
        $resolved = self::resolve_price($variation);
        self::$filtering = false;

        return ($resolved !== '' && $resolved !== null) ? $resolved : $price;
    }

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
        // Read cookie/slug directly — do not call get_woocommerce_currency() (recursion risk).
        $currency = DC_Region_Currency::get_current_currency();
        if ($currency === 'NOK') {
            return '%2$s %1$s';
        }
        return $format;
    }

    public function filter_price_decimals($decimals) {
        $currency = DC_Region_Currency::get_current_currency();
        if ($currency === 'VND') {
            return 0;
        }
        return $decimals;
    }

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

    public static function get_product_edit_prices($product_id) {
        $prices = self::get_prices($product_id);
        if (!empty($prices)) {
            return $prices;
        }

        $wc_product = wc_get_product($product_id);
        if (!$wc_product) {
            return array();
        }

        if ($wc_product->is_type('variable')) {
            $children = $wc_product->get_children();
            if (!empty($children)) {
                $var_prices = self::get_prices($children[0]);
                if (!empty($var_prices)) {
                    return $var_prices;
                }
            }
        }

        // Raw meta — avoid get_regular_price() recursion.
        $default = get_post_meta($product_id, '_regular_price', true);
        if ($default === '') {
            $default = get_post_meta($product_id, '_price', true);
        }

        return $default !== '' ? array('NOK' => $default) : array();
    }
}
