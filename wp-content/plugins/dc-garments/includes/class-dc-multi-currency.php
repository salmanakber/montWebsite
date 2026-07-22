<?php
/**
 * Multi-currency product pricing for WooCommerce (variable-product aware).
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
     * Get multicurrency map for a product/variation.
     * Variations fall back to parent product prices when their own meta is empty.
     */
    public static function get_prices_for_product($product) {
        if (!$product) {
            return array();
        }

        $product_id = is_numeric($product) ? (int) $product : (int) $product->get_id();
        $prices = self::get_prices($product_id);

        if (!empty($prices)) {
            return $prices;
        }

        // Variation → inherit from parent.
        $parent_id = 0;
        if (!is_numeric($product) && is_callable(array($product, 'get_parent_id'))) {
            $parent_id = (int) $product->get_parent_id();
        } elseif (is_numeric($product)) {
            $post_parent = wp_get_post_parent_id($product_id);
            $parent_id = $post_parent ? (int) $post_parent : 0;
        }

        if ($parent_id > 0) {
            return self::get_prices($parent_id);
        }

        return array();
    }

    /**
     * Resolve price for active currency using raw meta only (no WC getters).
     */
    public static function resolve_price($product, $currency = null) {
        if (!$product) {
            return '';
        }

        $currency = $currency ? $currency : DC_Region_Currency::get_current_currency();
        $prices = self::get_prices_for_product($product);

        if (isset($prices[$currency]) && $prices[$currency] !== '' && $prices[$currency] !== null) {
            return (string) $prices[$currency];
        }

        $product_id = (int) $product->get_id();

        // Fallback: WooCommerce default price (raw meta only — never call get_price()).
        $default_price = get_post_meta($product_id, '_regular_price', true);
        if ($default_price === '' || $default_price === null) {
            $default_price = get_post_meta($product_id, '_price', true);
        }

        // Variable parent with no own price: use first child multicurrency / meta.
        if (($default_price === '' || $default_price === null) && is_callable(array($product, 'is_type')) && $product->is_type('variable')) {
            $children = $product->get_children();
            if (!empty($children)) {
                $child_prices = self::get_prices_for_product($children[0]);
                if (isset($child_prices[$currency]) && $child_prices[$currency] !== '') {
                    return (string) $child_prices[$currency];
                }
                $child_default = get_post_meta($children[0], '_regular_price', true);
                if ($child_default === '') {
                    $child_default = get_post_meta($children[0], '_price', true);
                }
                return $child_default;
            }
        }

        return $default_price;
    }

    public function init() {
        // Individual product/variation getters (view context).
        add_filter('woocommerce_product_get_price', array($this, 'filter_price'), 99, 2);
        add_filter('woocommerce_product_get_regular_price', array($this, 'filter_price'), 99, 2);
        add_filter('woocommerce_product_variation_get_price', array($this, 'filter_price'), 99, 2);
        add_filter('woocommerce_product_variation_get_regular_price', array($this, 'filter_price'), 99, 2);

        // Variable product price tables (WC builds these with 'edit' context — getters are skipped).
        add_filter('woocommerce_variation_prices_price', array($this, 'filter_variation_price'), 99, 3);
        add_filter('woocommerce_variation_prices_regular_price', array($this, 'filter_variation_price'), 99, 3);
        add_filter('woocommerce_variation_prices_sale_price', array($this, 'filter_variation_price'), 99, 3);
        add_filter('woocommerce_get_variation_prices_hash', array($this, 'variation_prices_hash'), 10, 3);

        // Frontend variation JSON (add-to-cart form / AJAX).
        add_filter('woocommerce_available_variation', array($this, 'filter_available_variation'), 99, 3);

        // Cart / totals.
        add_action('woocommerce_before_calculate_totals', array($this, 'apply_cart_item_prices'), 99);

        add_filter('woocommerce_currency_symbol', array($this, 'filter_currency_symbol'), 10, 2);
        add_filter('woocommerce_price_format', array($this, 'filter_price_format'), 10, 2);
        add_filter('woocommerce_price_num_decimals', array($this, 'filter_price_decimals'), 10, 1);
    }

    public function filter_price($price, $product) {
        if (self::$filtering || !$product) {
            return $price;
        }

        self::$filtering = true;
        $resolved = self::resolve_price($product);
        self::$filtering = false;

        return ($resolved !== '' && $resolved !== null) ? $resolved : $price;
    }

    /**
     * @param string|float       $price
     * @param \WC_Product        $variation
     * @param \WC_Product_Variable $product
     */
    public function filter_variation_price($price, $variation, $product) {
        if (self::$filtering || !$variation) {
            return $price;
        }

        self::$filtering = true;
        $resolved = self::resolve_price($variation);
        self::$filtering = false;

        return ($resolved !== '' && $resolved !== null) ? $resolved : $price;
    }

    public function variation_prices_hash($hash, $product, $display) {
        $hash[] = 'dc_mc_' . DC_Region_Currency::get_current_currency();
        $hash[] = 'dc_rg_' . DC_Region_Currency::get_current_region_slug();
        return $hash;
    }

    /**
     * Ensure variation data used by JS has the correct region price.
     */
    public function filter_available_variation($data, $product, $variation) {
        if (!$variation) {
            return $data;
        }

        $resolved = self::resolve_price($variation);
        if ($resolved === '' || $resolved === null) {
            return $data;
        }

        $price = (float) $resolved;
        $data['display_price'] = (float) wc_get_price_to_display($variation, array('price' => $price));
        $data['display_regular_price'] = (float) wc_get_price_to_display($variation, array('price' => $price));
        $data['price_html'] = wc_price($data['display_price']);

        return $data;
    }

    /**
     * Force cart line items to use the active region price.
     */
    public function apply_cart_item_prices($cart) {
        if (is_admin() && !defined('DOING_AJAX')) {
            return;
        }
        if (!$cart || self::$filtering) {
            return;
        }

        self::$filtering = true;
        foreach ($cart->get_cart() as $cart_item) {
            if (empty($cart_item['data']) || !is_object($cart_item['data'])) {
                continue;
            }
            $resolved = self::resolve_price($cart_item['data']);
            if ($resolved !== '' && $resolved !== null) {
                $cart_item['data']->set_price($resolved);
            }
        }
        self::$filtering = false;
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
        return $format;
    }

    public function filter_price_decimals($decimals) {
        $currency = DC_Region_Currency::get_current_currency();
        if ($currency === 'VND') {
            return 0;
        }
        return $decimals;
    }

    /**
     * Save multicurrency prices on parent AND every variation, then bust WC caches.
     */
    public static function save_prices_for_product($product_id, $prices, $wc_product = null) {
        if (!$wc_product) {
            $wc_product = wc_get_product($product_id);
        }
        if (!$wc_product) {
            return;
        }

        $clean = self::save_prices($product_id, $prices);

        if ($wc_product->is_type('variable')) {
            $children = $wc_product->get_children();
            foreach ($children as $variation_id) {
                self::save_prices($variation_id, $clean);
                // Also keep WC base price in sync with NOK (or first available) as store default.
                $base = null;
                if (isset($clean['NOK']) && $clean['NOK'] !== '') {
                    $base = $clean['NOK'];
                } elseif (!empty($clean)) {
                    $base = reset($clean);
                }
                if ($base !== null) {
                    update_post_meta($variation_id, '_regular_price', $base);
                    update_post_meta($variation_id, '_price', $base);
                    delete_post_meta($variation_id, '_sale_price');
                }
                wc_delete_product_transients($variation_id);
            }

            if (!empty($clean)) {
                $base = isset($clean['NOK']) ? $clean['NOK'] : reset($clean);
                update_post_meta($product_id, '_min_variation_price', $base);
                update_post_meta($product_id, '_max_variation_price', $base);
                update_post_meta($product_id, '_min_variation_regular_price', $base);
                update_post_meta($product_id, '_max_variation_regular_price', $base);
                update_post_meta($product_id, '_price', $base);
            }

            // Bust variable price cache so region switch recalculates.
            delete_transient('wc_var_prices_' . $product_id);
            wc_delete_product_transients($product_id);

            if (class_exists('WC_Product_Variable') && method_exists('WC_Product_Variable', 'sync')) {
                \WC_Product_Variable::sync($product_id);
                // Re-apply multicurrency meta after sync (sync can refresh price metas).
                self::save_prices($product_id, $clean);
                foreach ($children as $variation_id) {
                    self::save_prices($variation_id, $clean);
                }
                delete_transient('wc_var_prices_' . $product_id);
            }
        } else {
            $base = null;
            if (isset($clean['NOK']) && $clean['NOK'] !== '') {
                $base = $clean['NOK'];
            } elseif (!empty($clean)) {
                $base = reset($clean);
            }
            if ($base !== null) {
                update_post_meta($product_id, '_regular_price', $base);
                update_post_meta($product_id, '_price', $base);
            }
            wc_delete_product_transients($product_id);
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

        $default = get_post_meta($product_id, '_regular_price', true);
        if ($default === '') {
            $default = get_post_meta($product_id, '_price', true);
        }

        return $default !== '' ? array('NOK' => $default) : array();
    }

    /**
     * Parse multicurrency payload from AJAX (array or JSON string).
     */
    public static function parse_prices_from_request($post) {
        $prices = array();

        if (!empty($post['multicurrency_prices_json'])) {
            $decoded = json_decode(wp_unslash($post['multicurrency_prices_json']), true);
            if (is_array($decoded)) {
                $prices = $decoded;
            }
        } elseif (!empty($post['multicurrency_prices']) && is_array($post['multicurrency_prices'])) {
            $prices = $post['multicurrency_prices'];
        }

        $clean = array();
        foreach ($prices as $code => $value) {
            $code = strtoupper(sanitize_text_field($code));
            if ($value === '' || $value === null) {
                continue;
            }
            $clean[$code] = $value;
        }

        return $clean;
    }
}
