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
    const QUERY_VAR   = 'dc_region';

    /** @var bool Guard against recursive resolution. */
    private static $resolving = false;

    /** @var string|null Cached region slug for this request. */
    private static $cached_slug = null;

    /**
     * Region definitions (no translations here — keeps locale loading safe).
     */
    public static function get_regions() {
        return array(
            'intl' => array(
                'label'     => 'International',
                'currency'  => 'USD',
                'symbol'    => '$',
                'display'   => '$ USD',
                'lang'      => 'en',
                'flag'      => 'globe',
                'countries' => array(),
            ),
            'it' => array(
                'label'     => 'Italy',
                'currency'  => 'EUR',
                'symbol'    => '€',
                'display'   => '€ EUR',
                'lang'      => 'it',
                'flag'      => 'it',
                'countries' => array('IT'),
            ),
            'no' => array(
                'label'     => 'Norway',
                'currency'  => 'NOK',
                'symbol'    => 'kr',
                'display'   => 'kr NOK',
                'lang'      => 'nb',
                'flag'      => 'no',
                'countries' => array('NO'),
            ),
            'vn' => array(
                'label'     => 'Việt Nam',
                'currency'  => 'VND',
                'symbol'    => '₫',
                'display'   => '₫ VND',
                'lang'      => 'vi',
                'flag'      => 'vn',
                'countries' => array('VN'),
            ),
        );
    }

    public static function get_region($slug) {
        $regions = self::get_regions();
        return isset($regions[$slug]) ? $regions[$slug] : null;
    }

    public static function is_valid_region($slug) {
        return self::get_region($slug) !== null;
    }

    /**
     * Resolve current region from: query arg → cookie → IP (once) → intl.
     * Never triggers translations or WC price APIs.
     */
    public static function get_current_region_slug() {
        if (self::$cached_slug !== null) {
            return self::$cached_slug;
        }

        if (self::$resolving) {
            return 'intl';
        }

        self::$resolving = true;
        $slug = 'intl';

        // 1) Explicit query (?dc_region=it) — URL-friendly switch.
        if (isset($_GET[self::QUERY_VAR])) {
            $from_query = sanitize_key(wp_unslash($_GET[self::QUERY_VAR]));
            if (self::is_valid_region($from_query)) {
                $slug = $from_query;
            }
        } elseif (isset($_COOKIE[self::COOKIE_NAME])) {
            // 2) Cookie.
            $from_cookie = sanitize_key($_COOKIE[self::COOKIE_NAME]);
            if (self::is_valid_region($from_cookie)) {
                $slug = $from_cookie;
            }
        }

        self::$cached_slug = $slug;
        self::$resolving = false;

        return $slug;
    }

    public static function get_current_region() {
        $region = self::get_region(self::get_current_region_slug());
        return $region ? $region : self::get_region('intl');
    }

    public static function get_current_currency() {
        if (self::$resolving) {
            return 'USD';
        }
        $region = self::get_current_region();
        return !empty($region['currency']) ? $region['currency'] : 'USD';
    }

    public static function get_current_lang() {
        $region = self::get_current_region();
        return !empty($region['lang']) ? $region['lang'] : 'en';
    }

    public static function country_to_region($country_code) {
        $country_code = strtoupper(sanitize_text_field($country_code));
        foreach (self::get_regions() as $slug => $region) {
            if (!empty($region['countries']) && in_array($country_code, $region['countries'], true)) {
                return $slug;
            }
        }
        return 'intl';
    }

    public static function get_client_ip() {
        $headers = array('HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR');
        foreach ($headers as $header) {
            if (empty($_SERVER[$header])) {
                continue;
            }
            $ip = sanitize_text_field(wp_unslash($_SERVER[$header]));
            if (strpos($ip, ',') !== false) {
                $ip = trim(explode(',', $ip)[0]);
            }
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            }
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
        return '';
    }

    /**
     * Lightweight IP detect — never blocks request hard; defaults to intl.
     */
    public static function detect_region_from_ip() {
        $ip = self::get_client_ip();
        if (
            !$ip
            || $ip === '127.0.0.1'
            || $ip === '::1'
            || strpos($ip, '192.168.') === 0
            || strpos($ip, '10.') === 0
        ) {
            return 'intl';
        }

        $cache_key = 'dc_geo_' . md5($ip);
        $cached = get_transient($cache_key);
        if ($cached !== false && self::is_valid_region($cached)) {
            return $cached;
        }

        $region = 'intl';

        // Skip remote call in admin / cron / AJAX to avoid slow/broken loads.
        if (!is_admin() && !wp_doing_ajax() && !wp_doing_cron()) {
            $response = wp_remote_get(
                'https://ip-api.com/json/' . rawurlencode($ip) . '?fields=status,countryCode',
                array(
                    'timeout'     => 1,
                    'redirection' => 0,
                    'sslverify'   => false,
                )
            );

            if (!is_wp_error($response) && (int) wp_remote_retrieve_response_code($response) === 200) {
                $body = json_decode(wp_remote_retrieve_body($response), true);
                if (!empty($body['status']) && $body['status'] === 'success' && !empty($body['countryCode'])) {
                    $region = self::country_to_region($body['countryCode']);
                }
            }
        }

        set_transient($cache_key, $region, DAY_IN_SECONDS);
        return $region;
    }

    public static function set_region_cookie($region_slug) {
        if (!self::is_valid_region($region_slug)) {
            return false;
        }

        $path   = defined('COOKIEPATH') && COOKIEPATH ? COOKIEPATH : '/';
        $domain = defined('COOKIE_DOMAIN') ? COOKIE_DOMAIN : '';

        if (!headers_sent()) {
            setcookie(
                self::COOKIE_NAME,
                $region_slug,
                time() + (DAY_IN_SECONDS * self::COOKIE_DAYS),
                $path,
                $domain,
                is_ssl(),
                false
            );
        }

        $_COOKIE[self::COOKIE_NAME] = $region_slug;
        self::$cached_slug = $region_slug;
        return true;
    }

    /**
     * Build a safe redirect URL after region change.
     * - With WPML: language-aware permalink
     * - Without WPML: same page (cookie already set — no fake /en/ paths)
     */
    public static function get_url_for_region($region_slug, $url = null) {
        if (!self::is_valid_region($region_slug)) {
            return $url ? $url : home_url('/');
        }

        $url = $url ? $url : self::get_current_page_url();
        $region = self::get_region($region_slug);
        $lang = $region['lang'];

        // WPML active: use its permalink API only.
        if (defined('ICL_SITEPRESS_VERSION') && has_filter('wpml_permalink')) {
            $wpml_url = apply_filters('wpml_permalink', $url, $lang, true);
            if (is_string($wpml_url) && $wpml_url !== '') {
                return remove_query_arg(self::QUERY_VAR, $wpml_url);
            }
        }

        // No WPML: stay on the same path. Cookie carries the region.
        return remove_query_arg(self::QUERY_VAR, $url);
    }

    public static function get_current_page_url() {
        $scheme = is_ssl() ? 'https' : 'http';
        $host = isset($_SERVER['HTTP_HOST']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_HOST'])) : '';
        $uri = isset($_SERVER['REQUEST_URI']) ? wp_unslash($_SERVER['REQUEST_URI']) : '/';
        // Strip dangerous characters but keep path/query.
        $uri = esc_url_raw($scheme . '://' . $host . $uri);
        return $uri ? $uri : home_url('/');
    }

    public function init() {
        add_action('init', array($this, 'maybe_handle_region_query'), 5);
        add_action('init', array($this, 'maybe_auto_set_region'), 20);
        add_action('init', array($this, 'register_shortcode'), 20);
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('wp_ajax_dc_switch_region', array($this, 'ajax_switch_region'));
        add_action('wp_ajax_nopriv_dc_switch_region', array($this, 'ajax_switch_region'));
        add_filter('woocommerce_currency', array($this, 'filter_woocommerce_currency'), 5);
    }

    /**
     * If ?dc_region=xx is present, persist cookie and optionally clean redirect.
     */
    public function maybe_handle_region_query() {
        if (is_admin() || wp_doing_ajax() || wp_doing_cron()) {
            return;
        }

        if (!isset($_GET[self::QUERY_VAR])) {
            return;
        }

        $slug = sanitize_key(wp_unslash($_GET[self::QUERY_VAR]));
        if (!self::is_valid_region($slug)) {
            return;
        }

        self::set_region_cookie($slug);

        // Clean URL: redirect once to same page without the query arg (cookie keeps region).
        // Skip if WPML will own the language URL.
        if (!defined('ICL_SITEPRESS_VERSION') && !headers_sent()) {
            $clean = remove_query_arg(self::QUERY_VAR, self::get_current_page_url());
            wp_safe_redirect($clean, 302);
            exit;
        }
    }

    /**
     * First visit: set cookie from IP (no redirect, no URL change).
     */
    public function maybe_auto_set_region() {
        if (isset($_COOKIE[self::COOKIE_NAME]) || isset($_GET[self::QUERY_VAR])) {
            return;
        }
        if (is_admin() || wp_doing_ajax() || wp_doing_cron() || headers_sent()) {
            return;
        }

        $detected = self::detect_region_from_ip();
        if (!self::is_valid_region($detected)) {
            $detected = 'intl';
        }
        self::set_region_cookie($detected);
    }

    public function filter_woocommerce_currency($currency) {
        if (self::$resolving) {
            return $currency;
        }
        self::$resolving = true;
        $region = self::get_region(self::get_current_region_slug());
        self::$resolving = false;
        return $region ? $region['currency'] : $currency;
    }

    public function register_shortcode() {
        add_shortcode('dc_region_switcher', array($this, 'render_switcher'));
    }

    public function enqueue_assets() {
        $css_ver = file_exists(DC_PM_PLUGIN_DIR . 'assets/css/region-switcher.css')
            ? (string) filemtime(DC_PM_PLUGIN_DIR . 'assets/css/region-switcher.css')
            : DC_PM_VERSION;
        $js_ver = file_exists(DC_PM_PLUGIN_DIR . 'assets/js/region-switcher.js')
            ? (string) filemtime(DC_PM_PLUGIN_DIR . 'assets/js/region-switcher.js')
            : DC_PM_VERSION;

        wp_enqueue_style(
            'dc-region-switcher',
            DC_PM_PLUGIN_URL . 'assets/css/region-switcher.css',
            array(),
            $css_ver
        );
        wp_enqueue_script(
            'dc-region-switcher',
            DC_PM_PLUGIN_URL . 'assets/js/region-switcher.js',
            array('jquery'),
            $js_ver,
            true
        );

        $regions = array();
        foreach (self::get_regions() as $slug => $region) {
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
            'currentRegion' => self::get_current_region_slug(),
            'regions'       => $regions,
            'queryVar'      => self::QUERY_VAR,
        ));
    }

    public function ajax_switch_region() {
        check_ajax_referer('dc_region_nonce', 'nonce');

        $region_slug = isset($_POST['region']) ? sanitize_key($_POST['region']) : '';
        if (!self::is_valid_region($region_slug)) {
            wp_send_json_error(array('message' => 'Invalid region'));
        }

        self::set_region_cookie($region_slug);

        $redirect = isset($_POST['redirect_url']) ? esc_url_raw(wp_unslash($_POST['redirect_url'])) : '';
        if (!$redirect) {
            $redirect = home_url('/');
        }

        // Only allow same-host redirects.
        $home_host = wp_parse_url(home_url(), PHP_URL_HOST);
        $redir_host = wp_parse_url($redirect, PHP_URL_HOST);
        if ($redir_host && $home_host && strcasecmp($redir_host, $home_host) !== 0) {
            $redirect = home_url('/');
        }

        $redirect = self::get_url_for_region($region_slug, $redirect);

        wp_send_json_success(array(
            'region'   => $region_slug,
            'currency' => self::get_region($region_slug)['currency'],
            'redirect' => $redirect,
        ));
    }

    public function render_switcher($atts = array()) {
        static $instance = 0;
        $instance++;

        $atts = shortcode_atts(array(
            'context' => 'default',
        ), $atts, 'dc_region_switcher');

        $current_slug = self::get_current_region_slug();
        $current = self::get_region($current_slug);
        $regions = self::get_regions();
        $panel_id = 'dc-region-panel-' . $instance;

        ob_start();
        include DC_PM_PLUGIN_DIR . 'public/partials/region-switcher.php';
        return ob_get_clean();
    }
}
