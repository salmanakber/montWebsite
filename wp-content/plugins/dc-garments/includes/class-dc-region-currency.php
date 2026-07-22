<?php
/**
 * Region & currency switcher with IP geolocation and WPML URL support.
 *
 * @package DC_Product_Manager
 */

namespace DC_Product_Manager;

if (!defined('ABSPATH')) {
    exit;
}

class DC_Region_Currency {

    const COOKIE_NAME = 'dc_region';
    const COOKIE_DAYS = 30;

    /**
     * Prevent recursive resolution during locale/currency filters.
     */
    private static $resolving = false;

    /**
     * Cached region slug for the current request.
     */
    private static $cached_slug = null;

    /**
     * Region definitions: slug => config.
     */
    public static function get_regions() {
        $regions = array(
            'intl' => array(
                'label'       => 'International',
                'currency'    => 'USD',
                'symbol'      => '$',
                'display'     => '$ USD',
                'lang'        => 'en',
                'flag'        => 'globe',
                'countries'   => array(),
            ),
            'it' => array(
                'label'       => 'Italy',
                'currency'    => 'EUR',
                'symbol'      => '€',
                'display'     => '€ EUR',
                'lang'        => 'it',
                'flag'        => 'it',
                'countries'   => array('IT'),
            ),
            'no' => array(
                'label'       => 'Norway',
                'currency'    => 'NOK',
                'symbol'      => 'kr',
                'display'     => 'kr NOK',
                'lang'        => 'nb',
                'flag'        => 'no',
                'countries'   => array('NO'),
            ),
            'vn' => array(
                'label'       => 'Việt Nam',
                'currency'    => 'VND',
                'symbol'      => '₫',
                'display'     => '₫ VND',
                'lang'        => 'vi',
                'flag'        => 'vn',
                'countries'   => array('VN'),
            ),
        );

        return apply_filters('dc_regions', $regions);
    }

    /**
     * Translate region labels for display (call only after init, outside locale filters).
     */
    public static function get_regions_translated() {
        $regions = self::get_regions();
        if (!did_action('init')) {
            return $regions;
        }
        foreach ($regions as $slug => $region) {
            $regions[$slug]['label'] = __($region['label'], 'dc-product-manager');
        }
        return $regions;
    }

    public static function get_region($slug = null) {
        $regions = self::get_regions();
        if ($slug && isset($regions[$slug])) {
            return $regions[$slug];
        }
        return null;
    }

    public static function get_current_region_slug() {
        if (self::$cached_slug !== null) {
            return self::$cached_slug;
        }

        if (self::$resolving) {
            return 'intl';
        }

        self::$resolving = true;

        $slug = 'intl';
        if (isset($_COOKIE[self::COOKIE_NAME])) {
            $cookie_slug = sanitize_key($_COOKIE[self::COOKIE_NAME]);
            if (self::get_region($cookie_slug)) {
                $slug = $cookie_slug;
            }
        } else {
            $slug = self::detect_region_from_ip();
            if (!self::get_region($slug)) {
                $slug = 'intl';
            }
        }

        self::$cached_slug = $slug;
        self::$resolving = false;

        return $slug;
    }

    public static function get_current_region() {
        $slug = self::get_current_region_slug();
        return self::get_region($slug) ?: self::get_region('intl');
    }

    public static function get_current_currency() {
        $region = self::get_current_region();
        return $region ? $region['currency'] : 'USD';
    }

    public static function get_current_lang() {
        $region = self::get_current_region();
        return $region ? $region['lang'] : 'en';
    }

    /**
     * Map country code from IP to region slug.
     */
    public static function country_to_region($country_code) {
        $country_code = strtoupper(sanitize_text_field($country_code));
        foreach (self::get_regions() as $slug => $region) {
            if (!empty($region['countries']) && in_array($country_code, $region['countries'], true)) {
                return $slug;
            }
        }
        return 'intl';
    }

    /**
     * Detect region from visitor IP (cached per IP for 24h).
     */
    public static function detect_region_from_ip() {
        $ip = self::get_client_ip();
        if (!$ip || $ip === '127.0.0.1' || strpos($ip, '192.168.') === 0) {
            return 'intl';
        }

        $cache_key = 'dc_geo_' . md5($ip);
        $cached = get_transient($cache_key);
        if ($cached !== false) {
            return $cached;
        }

        $region = 'intl';
        $response = wp_remote_get(
            'http://ip-api.com/json/' . rawurlencode($ip) . '?fields=status,countryCode',
            array('timeout' => 3)
        );

        if (!is_wp_error($response)) {
            $body = json_decode(wp_remote_retrieve_body($response), true);
            if (!empty($body['status']) && $body['status'] === 'success' && !empty($body['countryCode'])) {
                $region = self::country_to_region($body['countryCode']);
            }
        }

        set_transient($cache_key, $region, DAY_IN_SECONDS);
        return $region;
    }

    public static function get_client_ip() {
        $headers = array('HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR');
        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = sanitize_text_field(wp_unslash($_SERVER[$header]));
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        return '';
    }

    /**
     * Build WPML-friendly URL for a region's language.
     */
    public static function get_url_for_region($region_slug, $url = null) {
        $region = self::get_region($region_slug);
        if (!$region) {
            return $url ?: home_url('/');
        }

        $url = $url ?: self::get_current_page_url();
        $lang = $region['lang'];

        if (has_filter('wpml_permalink')) {
            return apply_filters('wpml_permalink', $url, $lang);
        }

        if (function_exists('icl_get_languages')) {
            $languages = icl_get_languages('skip_missing=0');
            if (!empty($languages[$lang]['url'])) {
                $parsed_current = wp_parse_url($url);
                $parsed_lang = wp_parse_url($languages[$lang]['url']);
                $path = isset($parsed_current['path']) ? $parsed_current['path'] : '/';
                $base = trailingslashit($parsed_lang['scheme'] . '://' . $parsed_lang['host']);
                if (!empty($parsed_lang['path']) && $parsed_lang['path'] !== '/') {
                    return $base . trim($parsed_lang['path'], '/') . $path;
                }
                return $base . ltrim($path, '/');
            }
        }

        $home = trailingslashit(home_url());
        $parsed = wp_parse_url($url);
        $path = isset($parsed['path']) ? ltrim($parsed['path'], '/') : '';

        $existing_langs = array('en', 'it', 'nb', 'no', 'vi');
        $parts = explode('/', $path);
        if (!empty($parts[0]) && in_array($parts[0], $existing_langs, true)) {
            array_shift($parts);
            $path = implode('/', $parts);
        }

        $prefix = ($lang === 'en') ? 'en' : $lang;
        return $home . $prefix . '/' . $path;
    }

    public static function get_current_page_url() {
        $scheme = is_ssl() ? 'https' : 'http';
        $host = isset($_SERVER['HTTP_HOST']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_HOST'])) : '';
        $uri = isset($_SERVER['REQUEST_URI']) ? sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI'])) : '/';
        return $scheme . '://' . $host . $uri;
    }

    public static function set_region_cookie($region_slug) {
        if (!self::get_region($region_slug)) {
            return false;
        }
        setcookie(
            self::COOKIE_NAME,
            $region_slug,
            time() + (DAY_IN_SECONDS * self::COOKIE_DAYS),
            COOKIEPATH,
            COOKIE_DOMAIN,
            is_ssl(),
            false
        );
        $_COOKIE[self::COOKIE_NAME] = $region_slug;
        return true;
    }

    public function init() {
        add_action('init', array($this, 'maybe_auto_set_region'), 1);
        add_action('init', array($this, 'register_shortcode'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('wp_ajax_dc_switch_region', array($this, 'ajax_switch_region'));
        add_action('wp_ajax_nopriv_dc_switch_region', array($this, 'ajax_switch_region'));
        add_filter('woocommerce_currency', array($this, 'filter_woocommerce_currency'));
    }

    /**
     * Auto-set region cookie on first visit based on IP.
     */
    public function maybe_auto_set_region() {
        if (isset($_COOKIE[self::COOKIE_NAME])) {
            return;
        }
        if (is_admin() || wp_doing_ajax() || wp_doing_cron()) {
            return;
        }
        $detected = self::detect_region_from_ip();
        if (!self::get_region($detected)) {
            $detected = 'intl';
        }
        self::set_region_cookie($detected);
        self::$cached_slug = $detected;
    }

    public function filter_woocommerce_currency($currency) {
        if (self::$resolving) {
            return $currency;
        }
        $region = self::get_region(self::get_current_region_slug());
        return $region ? $region['currency'] : $currency;
    }

    public function register_shortcode() {
        add_shortcode('dc_region_switcher', array($this, 'render_switcher'));
    }

    public function enqueue_assets() {
        wp_enqueue_style(
            'dc-region-switcher',
            DC_PM_PLUGIN_URL . 'assets/css/region-switcher.css',
            array(),
            DC_PM_VERSION
        );
        wp_enqueue_script(
            'dc-region-switcher',
            DC_PM_PLUGIN_URL . 'assets/js/region-switcher.js',
            array('jquery'),
            DC_PM_VERSION,
            true
        );

        $current = self::get_current_region_slug();
        $regions = array();
        foreach (self::get_regions_translated() as $slug => $region) {
            $regions[$slug] = array(
                'label'    => $region['label'],
                'currency' => $region['currency'],
                'display'  => $region['display'],
                'lang'     => $region['lang'],
                'flag'     => $region['flag'],
            );
        }

        wp_localize_script('dc-region-switcher', 'dc_region', array(
            'ajaxUrl'       => admin_url('admin-ajax.php'),
            'nonce'         => wp_create_nonce('dc_region_nonce'),
            'currentRegion' => $current,
            'regions'       => $regions,
            'i18n'          => array(
                'label'   => __('Region / Currency', 'dc-product-manager'),
                'title'   => __('SELECT YOUR REGION / CURRENCY', 'dc-product-manager'),
                'close'   => __('Close', 'dc-product-manager'),
            ),
        ));
    }

    public function ajax_switch_region() {
        check_ajax_referer('dc_region_nonce', 'nonce');

        $region_slug = isset($_POST['region']) ? sanitize_key($_POST['region']) : '';
        if (!self::get_region($region_slug)) {
            wp_send_json_error(array('message' => __('Invalid region', 'dc-product-manager')));
        }

        self::set_region_cookie($region_slug);
        self::$cached_slug = $region_slug;

        $redirect = isset($_POST['redirect_url']) ? esc_url_raw(wp_unslash($_POST['redirect_url'])) : '';
        if (!$redirect) {
            $redirect = self::get_current_page_url();
        }

        $redirect = self::get_url_for_region($region_slug, $redirect);

        wp_send_json_success(array(
            'region'   => $region_slug,
            'currency' => self::get_region($region_slug)['currency'],
            'redirect' => $redirect,
        ));
    }

    public function render_switcher($atts = array()) {
        $current_slug = self::get_current_region_slug();
        $current = self::get_region($current_slug);
        $regions = self::get_regions_translated();

        ob_start();
        include DC_PM_PLUGIN_DIR . 'public/partials/region-switcher.php';
        return ob_get_clean();
    }

    /**
     * Output switcher directly (for header.php).
     */
    public static function output_switcher() {
        $instance = new self();
        echo $instance->render_switcher(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }
}
