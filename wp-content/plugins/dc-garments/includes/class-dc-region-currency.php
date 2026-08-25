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
        } else {
            // 3) First visit: geolocate from IP / CDN header, default intl (English).
            $slug = self::detect_region_from_ip();
            if (!self::is_valid_region($slug)) {
                $slug = 'intl';
            }
            if (!headers_sent()) {
                self::set_region_cookie($slug);
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

    /**
     * Language prefix for bare-URL redirect: cookie → geo IP → en (intl).
     * Sets region cookie on first visit when geo-detecting.
     *
     * @return string Language code (en, it, nb, vi).
     */
    public static function resolve_redirect_lang() {
        $slug = 'intl';

        if (isset($_COOKIE[self::COOKIE_NAME])) {
            $from_cookie = sanitize_key(wp_unslash($_COOKIE[self::COOKIE_NAME]));
            if (self::is_valid_region($from_cookie)) {
                $slug = $from_cookie;
            }
        } else {
            $slug = self::detect_region_from_ip();
            if (!self::is_valid_region($slug)) {
                $slug = 'intl';
            }
            if (!headers_sent()) {
                self::set_region_cookie($slug);
            }
            self::$cached_slug = $slug;
        }

        $region = self::get_region($slug);
        $lang   = ($region && !empty($region['lang'])) ? $region['lang'] : 'en';

        $valid_langs = array('en', 'it', 'nb', 'vi');
        if (!in_array($lang, $valid_langs, true)) {
            $lang = 'en';
        }

        return $lang;
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
        // Cloudflare country header (fast, no external API).
        if (!empty($_SERVER['HTTP_CF_IPCOUNTRY'])) {
            $cc = strtoupper(sanitize_text_field(wp_unslash($_SERVER['HTTP_CF_IPCOUNTRY'])));
            if ($cc && $cc !== 'XX' && $cc !== 'T1') {
                $mapped = self::country_to_region($cc);
                if (self::is_valid_region($mapped)) {
                    return $mapped;
                }
            }
        }

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
                    'timeout'     => 2,
                    'redirection' => 0,
                    'sslverify'   => true,
                )
            );

            if (!is_wp_error($response) && (int) wp_remote_retrieve_response_code($response) === 200) {
                $body = json_decode(wp_remote_retrieve_body($response), true);
                if (!empty($body['status']) && $body['status'] === 'success' && !empty($body['countryCode'])) {
                    $region = self::country_to_region($body['countryCode']);
                }
            }

            // Fallback geo provider if primary fails.
            if ($region === 'intl') {
                $fallback = wp_remote_get(
                    'https://ipapi.co/' . rawurlencode($ip) . '/country_code/',
                    array(
                        'timeout'     => 2,
                        'redirection' => 0,
                        'sslverify'   => true,
                        'headers'     => array('User-Agent' => 'Montenapoleone/1.0'),
                    )
                );
                if (!is_wp_error($fallback) && (int) wp_remote_retrieve_response_code($fallback) === 200) {
                    $cc = strtoupper(trim((string) wp_remote_retrieve_body($fallback)));
                    if ($cc && strlen($cc) === 2) {
                        $region = self::country_to_region($cc);
                    }
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
     * - Real Polylang: translated page / language home
     * - WPML: language-aware permalink
     * - DeepL polylang-style mode: /{lang}/… path prefixes
     * - Default: same page (cookie carries the region)
     */
    public static function get_url_for_region($region_slug, $url = null) {
        if (!self::is_valid_region($region_slug)) {
            return $url ? $url : home_url('/');
        }

        $url    = $url ? $url : self::get_current_page_url();
        $region = self::get_region($region_slug);
        $lang   = !empty($region['lang']) ? $region['lang'] : 'en';

        // Real Polylang: jump to the translated page (or that language's home).
        if (class_exists(__NAMESPACE__ . '\\DC_Language_Urls') && DC_Language_Urls::polylang_plugin_active()) {
            $pll_url = self::get_polylang_url_for_lang($lang, $url);
            if ($pll_url) {
                return remove_query_arg(array(self::QUERY_VAR, 'lang'), $pll_url);
            }
        }

        // WPML active: use its permalink API only.
        if (defined('ICL_SITEPRESS_VERSION') && has_filter('wpml_permalink')) {
            $wpml_url = apply_filters('wpml_permalink', $url, $lang, true);
            if (is_string($wpml_url) && $wpml_url !== '') {
                return remove_query_arg(array(self::QUERY_VAR, 'lang'), $wpml_url);
            }
        }

        $url = remove_query_arg(self::QUERY_VAR, $url);

        // DeepL “Polylang-style” mode: /en/product/… path prefixes.
        if (self::polylang_style_enabled() && class_exists(__NAMESPACE__ . '\\DC_Language_Urls')) {
            $url = remove_query_arg('lang', $url);
            return \DC_Product_Manager\DC_Language_Urls::convert_url_lang($url, $lang);
        }

        return remove_query_arg('lang', $url);
    }

    /**
     * Resolve a Polylang URL for a region language (en|it|nb|vi).
     *
     * @param string $lang Our language code.
     * @param string $url  Current page URL.
     * @return string Empty if unavailable.
     */
    public static function get_polylang_url_for_lang($lang, $url = '') {
        $candidates = self::polylang_lang_candidates($lang);
        if (!$candidates) {
            return '';
        }

        $path = (string) wp_parse_url($url, PHP_URL_PATH);
        // Front page / ugly /en/66-2/ → always use language home.
        $is_homeish = (function_exists('is_front_page') && is_front_page())
            || (function_exists('is_home') && is_home())
            || (bool) preg_match('#/(en|it|nb|no|vi)/(66-2|home)/?$#i', $path)
            || (bool) preg_match('#/(en|it|nb|no|vi)/?$#i', $path);

        if ($is_homeish && function_exists('pll_home_url')) {
            foreach ($candidates as $try) {
                $home = pll_home_url($try);
                if (is_string($home) && $home !== '') {
                    return $home;
                }
            }
        }

        // Prefer Polylang's own "this page in language X" URL.
        if (function_exists('PLL')) {
            $pll = PLL();
            if (is_object($pll) && isset($pll->links) && method_exists($pll->links, 'get_translation_url')) {
                foreach ($candidates as $try) {
                    $lang_obj = (isset($pll->model) && method_exists($pll->model, 'get_language'))
                        ? $pll->model->get_language($try)
                        : null;
                    if (!$lang_obj) {
                        continue;
                    }
                    $translated = $pll->links->get_translation_url($lang_obj);
                    if (is_string($translated) && $translated !== '') {
                        return $translated;
                    }
                }
            }
        }

        $post_id = 0;
        if (function_exists('url_to_postid') && $url) {
            $post_id = (int) url_to_postid($url);
        }
        if (!$post_id && function_exists('get_queried_object_id')) {
            $post_id = (int) get_queried_object_id();
        }

        if ($post_id && function_exists('pll_get_post')) {
            foreach ($candidates as $try) {
                $tr = pll_get_post($post_id, $try);
                if ($tr) {
                    $permalink = get_permalink((int) $tr);
                    if ($permalink) {
                        return $permalink;
                    }
                }
            }
        }

        if (function_exists('pll_home_url')) {
            foreach ($candidates as $try) {
                $home = pll_home_url($try);
                if (is_string($home) && $home !== '') {
                    return $home;
                }
            }
        }

        return '';
    }

    /**
     * Polylang may register Norwegian as nb or no.
     *
     * @param string $lang Our lang code.
     * @return string[]
     */
    private static function polylang_lang_candidates($lang) {
        $lang = strtolower(sanitize_key($lang));
        if ($lang === 'nb' || $lang === 'no') {
            return array('nb', 'no');
        }
        return $lang ? array($lang) : array();
    }

    /**
     * Whether Mont DeepL polylang_style mode is on.
     * Reads the option directly so it works before the DeepL plugin class is loaded.
     */
    public static function polylang_style_enabled() {
        // Never run our fake /en/ URL layer alongside real Polylang.
        if (class_exists(__NAMESPACE__ . '\\DC_Language_Urls') && DC_Language_Urls::polylang_plugin_active()) {
            return false;
        }
        if (class_exists('\\Mont_DeepL_Plugin')) {
            $settings = \Mont_DeepL_Plugin::settings();
            return !empty($settings['polylang_style']);
        }
        $settings = get_option('mont_deepl_settings', array());
        return is_array($settings) && !empty($settings['polylang_style']);
    }

    /**
     * Map language code (en|it|nb|vi) → region slug.
     */
    public static function lang_to_region($lang) {
        $lang = strtolower(sanitize_key($lang));
        foreach (self::get_regions() as $slug => $region) {
            if (!empty($region['lang']) && $region['lang'] === $lang) {
                return $slug;
            }
        }
        return '';
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
        add_action('init', array($this, 'maybe_handle_lang_query'), 6);
        add_action('init', array($this, 'maybe_sync_region_from_polylang'), 8);
        add_action('init', array($this, 'maybe_auto_set_region'), 20);
        add_action('init', array($this, 'register_shortcode'), 20);
        // Run on front + admin so deploy fixes /en/66-2/ without waiting for wp-admin.
        add_action('init', array($this, 'maybe_fix_polylang_home_slugs'), 30);
        add_action('template_redirect', array($this, 'maybe_redirect_ugly_home_slug'), 1);
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('wp_ajax_dc_switch_region', array($this, 'ajax_switch_region'));
        add_action('wp_ajax_nopriv_dc_switch_region', array($this, 'ajax_switch_region'));
        add_filter('woocommerce_currency', array($this, 'filter_woocommerce_currency'), 5);
        add_filter('pll_preferred_language', array($this, 'filter_pll_preferred_language'), 5);
    }

    /**
     * Prefer our region cookie over Polylang browser auto-detect (avoids /en/66-2/ surprises).
     *
     * @param string|bool $slug Polylang language slug.
     * @return string|bool
     */
    public function filter_pll_preferred_language($slug) {
        if (empty($_COOKIE[self::COOKIE_NAME])) {
            return $slug;
        }
        $region_slug = sanitize_key(wp_unslash($_COOKIE[self::COOKIE_NAME]));
        if (!self::is_valid_region($region_slug)) {
            return $slug;
        }
        $region = self::get_region($region_slug);
        if (empty($region['lang'])) {
            return $slug;
        }
        $candidates = self::polylang_lang_candidates($region['lang']);
        foreach ($candidates as $try) {
            if (function_exists('pll_languages_list')) {
                $list = pll_languages_list(array('fields' => 'slug'));
                if (is_array($list) && in_array($try, $list, true)) {
                    return $try;
                }
            } else {
                return $try;
            }
        }
        return $slug;
    }

    /**
     * One-time: rename ugly Polylang home slugs like "66-2" → "home".
     * Fixes https://montenapoleone1974.com/en/66-2/
     * (About Us did not cause this — WP/Polylang assigned a conflict slug to the EN home page.)
     */
    public function maybe_fix_polylang_home_slugs() {
        if (get_option('mont_fixed_pll_home_slugs_v1')) {
            return;
        }
        if (!function_exists('pll_get_post_translations') && !function_exists('get_posts')) {
            return;
        }

        $fixed = false;
        $front = (int) get_option('page_on_front');

        if ($front > 0 && function_exists('pll_get_post_translations')) {
            $translations = pll_get_post_translations($front);
            if (!is_array($translations) || !$translations) {
                $translations = array($front);
            }
            foreach ($translations as $pid) {
                if ($this->rename_ugly_page_slug((int) $pid)) {
                    $fixed = true;
                }
            }
        }

        // Always scrub known bad slug, even if not linked as front page.
        $pages = get_posts(array(
            'post_type'              => 'page',
            'post_status'            => 'any',
            'name'                   => '66-2',
            'posts_per_page'         => 10,
            'fields'                 => 'ids',
            'no_found_rows'          => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
        ));
        foreach ($pages as $pid) {
            if ($this->rename_ugly_page_slug((int) $pid)) {
                $fixed = true;
            }
        }

        // Only mark done when we fixed something, or confirmed the bad slug is gone.
        $still = get_posts(array(
            'post_type'              => 'page',
            'post_status'            => 'any',
            'name'                   => '66-2',
            'posts_per_page'         => 1,
            'fields'                 => 'ids',
            'no_found_rows'          => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
        ));
        if ($fixed || !$still) {
            update_option('mont_fixed_pll_home_slugs_v1', 1, false);
            if ($fixed) {
                flush_rewrite_rules(false);
            }
        }
    }

    /**
     * @param int $pid Page ID.
     * @return bool True if renamed.
     */
    private function rename_ugly_page_slug($pid) {
        $pid  = (int) $pid;
        $post = $pid ? get_post($pid) : null;
        if (!$post || $post->post_type !== 'page') {
            return false;
        }
        $slug = (string) $post->post_name;
        if ($slug !== '66-2' && !preg_match('/^\d+-2$/', $slug)) {
            return false;
        }
        $result = wp_update_post(array(
            'ID'        => $pid,
            'post_name' => 'home',
        ), true);
        return !is_wp_error($result) && $result;
    }

    /**
     * Soft-redirect legacy /en/66-2/ to Polylang language home when possible.
     */
    public function maybe_redirect_ugly_home_slug() {
        if (is_admin() || wp_doing_ajax() || wp_doing_cron() || headers_sent()) {
            return;
        }
        if (!function_exists('pll_home_url')) {
            return;
        }

        $path = (string) wp_parse_url(self::get_current_page_url(), PHP_URL_PATH);
        if (!preg_match('#/(en|it|nb|no|vi)/(66-2)/?$#i', $path, $m)) {
            return;
        }

        $lang = strtolower($m[1]);
        if ($lang === 'no') {
            $lang = 'nb';
        }

        $target = '';
        foreach (self::polylang_lang_candidates($lang) as $try) {
            $home = pll_home_url($try);
            if (is_string($home) && $home !== '') {
                $target = $home;
                break;
            }
        }
        if (!$target) {
            return;
        }

        $target_path = (string) wp_parse_url($target, PHP_URL_PATH);
        if (untrailingslashit($target_path) === untrailingslashit($path)) {
            return;
        }

        wp_safe_redirect($target, 301);
        exit;
    }

    /**
     * When real Polylang is active, mirror its language into dc_region cookie.
     */
    public function maybe_sync_region_from_polylang() {
        if (is_admin() || wp_doing_ajax() || wp_doing_cron()) {
            return;
        }
        if (!class_exists(__NAMESPACE__ . '\\DC_Language_Urls') || !DC_Language_Urls::polylang_plugin_active()) {
            return;
        }

        $lang = '';
        if (function_exists('pll_current_language')) {
            $lang = (string) pll_current_language('slug');
        }
        if (!$lang && !empty($_COOKIE['pll_language'])) {
            $lang = sanitize_key(wp_unslash($_COOKIE['pll_language']));
        }
        // Map Polylang "no" → our "nb".
        if ($lang === 'no') {
            $lang = 'nb';
        }

        $slug = self::lang_to_region($lang);
        if (!$slug) {
            return;
        }

        $current = isset($_COOKIE[self::COOKIE_NAME]) ? sanitize_key($_COOKIE[self::COOKIE_NAME]) : '';
        if ($current !== $slug) {
            self::set_region_cookie($slug);
        }
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
        self::set_polylang_language_cookie($slug);

        // Clean URL: redirect once without the query arg.
        // With real Polylang, jump to the translated page for that region.
        if (!defined('ICL_SITEPRESS_VERSION') && !headers_sent()) {
            $clean = remove_query_arg(array(self::QUERY_VAR, 'lang'), self::get_current_page_url());
            if (class_exists(__NAMESPACE__ . '\\DC_Language_Urls') && DC_Language_Urls::polylang_plugin_active()) {
                $clean = self::get_url_for_region($slug, $clean);
            } elseif (self::polylang_style_enabled() && class_exists(__NAMESPACE__ . '\\DC_Language_Urls')) {
                $region = self::get_region($slug);
                $clean  = DC_Language_Urls::convert_url_lang($clean, $region['lang']);
            }
            wp_safe_redirect($clean, 302);
            exit;
        }
    }

    /**
     * Legacy ?lang=en|it|nb|vi → pretty /en/… when Polylang-style is on.
     */
    public function maybe_handle_lang_query() {
        if (is_admin() || wp_doing_ajax() || wp_doing_cron()) {
            return;
        }
        if (!self::polylang_style_enabled()) {
            return;
        }
        if (!isset($_GET['lang'])) {
            return;
        }

        $lang = sanitize_key(wp_unslash($_GET['lang']));
        $slug = self::lang_to_region($lang);
        if (!$slug) {
            return;
        }

        self::set_region_cookie($slug);

        if (!headers_sent() && class_exists(__NAMESPACE__ . '\\DC_Language_Urls')) {
            $clean = remove_query_arg('lang', self::get_current_page_url());
            $target = DC_Language_Urls::convert_url_lang($clean, $lang);
            wp_safe_redirect($target, 302);
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
            'polylangStyle' => self::polylang_style_enabled(),
            'currentLang'   => self::get_current_lang(),
            'langCodes'     => class_exists(__NAMESPACE__ . '\\DC_Language_Urls')
                ? DC_Language_Urls::get_lang_codes()
                : array('en', 'it', 'nb', 'vi'),
            'returnForm'    => function_exists('mont_return_form_js_config') ? mont_return_form_js_config() : null,
        ));
    }

    public function ajax_switch_region() {
        check_ajax_referer('dc_region_nonce', 'nonce');

        $region_slug = isset($_POST['region']) ? sanitize_key($_POST['region']) : '';
        if (!self::is_valid_region($region_slug)) {
            wp_send_json_error(array('message' => 'Invalid region'));
        }

        self::set_region_cookie($region_slug);
        self::set_polylang_language_cookie($region_slug);

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

    /**
     * Keep Polylang's pll_language cookie aligned with our region switcher.
     *
     * @param string $region_slug Region slug.
     */
    public static function set_polylang_language_cookie($region_slug) {
        if (!self::is_valid_region($region_slug)) {
            return;
        }
        if (headers_sent()) {
            return;
        }
        $region = self::get_region($region_slug);
        if (empty($region['lang'])) {
            return;
        }

        $pll_slug = $region['lang'];
        // Prefer Polylang's registered slug (nb vs no).
        if (function_exists('pll_languages_list')) {
            $list = pll_languages_list(array('fields' => 'slug'));
            if (is_array($list)) {
                foreach (self::polylang_lang_candidates($region['lang']) as $try) {
                    if (in_array($try, $list, true)) {
                        $pll_slug = $try;
                        break;
                    }
                }
            }
        }

        $expire = time() + YEAR_IN_SECONDS;
        $secure = is_ssl();
        // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.cookies_setcookie
        setcookie('pll_language', $pll_slug, $expire, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, $secure, true);
        $_COOKIE['pll_language'] = $pll_slug;
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
        $context = $atts['context'];

        ob_start();
        include DC_PM_PLUGIN_DIR . 'public/partials/region-switcher.php';
        return ob_get_clean();
    }
}
