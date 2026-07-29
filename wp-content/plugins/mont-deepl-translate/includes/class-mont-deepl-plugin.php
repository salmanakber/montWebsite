<?php
/**
 * Bootstrap Mont DeepL Translate.
 *
 * @package Mont_DeepL
 */

if (!defined('ABSPATH')) {
    exit;
}

class Mont_DeepL_Plugin {

    const OPTION_KEY = 'mont_deepl_settings';

    /** @var self|null */
    private static $instance = null;

    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'maybe_install_table'), 5);

        if (is_admin()) {
            Mont_DeepL_Admin::init();
        }

        add_action('wp_enqueue_scripts', array($this, 'enqueue_front'), 40);
        add_action('wp_ajax_mont_deepl_translate', array($this, 'ajax_translate'));
        add_action('wp_ajax_nopriv_mont_deepl_translate', array($this, 'ajax_translate'));
        add_action('wp_ajax_mont_deepl_test_api', array($this, 'ajax_test_api'));
    }

    public function maybe_install_table() {
        if (!Mont_DeepL_Cache::table_exists()) {
            Mont_DeepL_Cache::install();
        }
    }

    public static function defaults() {
        return array(
            'enabled'        => 0,
            'api_key'        => '',
            'api_plan'       => 'free',
            'source_lang'    => 'NB',
            'monthly_limit'  => 500000,
            'disable_google' => 1,
            'normalize_mixed_to_source' => 1,
            'include_selectors' => '',
        );
    }

    /**
     * Built-in selectors for Monte storefront (always merged with custom list).
     *
     * @return string[]
     */
    public static function default_include_selectors() {
        return array(
            '.mont_header_menu a',
            '.mont_header_menu_mobile a',
            '.mont_header_mobile_footer__link',
            '.mont_header_nav-right a',
            '.category-slider a',
            '.mont-cat-tabs a',
            '.mont-cat-tabs button',
            '#b2bmenu button',
            '.product-name-b2b',
            '.woocommerce ul.products li.product',
            '.woocommerce div.product',
            '.mont_custom_options',
            '.mont_variation-selector',
            '.mont_variation-header',
            '.mont_variation-header h3',
            '.mont_variation-header .dpName',
            '.mont_option-list',
            '.mont_option-item',
            '.mont_option-item .tobeSelected',
            '.collar-options',
            '.collar-option',
            '.collar-name',
            '.smallSubname',
            '.mont_sizes-measurement-list',
            '.mont_sizes-measurement-name',
            '.mont_sizes-measurement-price',
            '.mont_sizes-change-btn',
            '.mont_sizes-close-btn',
            '.mont_sizes-control-label',
            '.mont_straight_line',
            '.mont_add_to_cart_button_and_alert',
            '.woocommerce-tabs',
            '.woocommerce-breadcrumb',
            '.woocommerce-message',
            '.woocommerce-info',
            '.woocommerce-error',
            '.elementor-widget-heading',
            '.elementor-widget-text-editor',
            '.elementor-button',
            'footer a',
            'footer p',
            'footer span',
            'footer li',
        );
    }

    /**
     * @return string[]
     */
    public static function parse_selector_lines($raw) {
        if (!is_string($raw) || trim($raw) === '') {
            return array();
        }

        $selectors = array();
        $lines     = preg_split('/\r\n|\r|\n/', $raw);

        foreach ($lines as $line) {
            $line = trim(wp_strip_all_tags($line));
            if ($line === '') {
                continue;
            }
            // Allow common CSS selector characters only.
            if (!preg_match('/^[#.\w\[\]:\s>+~*="\',()-]+$/u', $line)) {
                continue;
            }
            $selectors[] = $line;
        }

        return array_values(array_unique($selectors));
    }

    /**
     * @return string[]
     */
    public static function get_include_selectors() {
        $settings = self::settings();
        $custom   = self::parse_selector_lines(isset($settings['include_selectors']) ? $settings['include_selectors'] : '');
        return array_values(array_unique(array_merge(self::default_include_selectors(), $custom)));
    }

    public static function settings() {
        $saved = get_option(self::OPTION_KEY, array());
        if (!is_array($saved)) {
            $saved = array();
        }
        $settings = array_merge(self::defaults(), $saved);
        if (!empty($settings['api_key'])) {
            $settings['api_plan'] = Mont_DeepL_API::detect_plan_from_key($settings['api_key']);
        }
        return $settings;
    }

    public static function current_target_lang() {
        $lang = '';

        if (class_exists('DC_Product_Manager\\DC_Region_Currency')) {
            $slug   = \DC_Product_Manager\DC_Region_Currency::get_current_region_slug();
            $region = \DC_Product_Manager\DC_Region_Currency::get_region($slug);
            if (!empty($region['lang'])) {
                $lang = $region['lang'];
            }
        }

        if ($lang === '' && !empty($_COOKIE['dc_region'])) {
            $slug = sanitize_key(wp_unslash($_COOKIE['dc_region']));
            $map  = array(
                'intl' => 'en',
                'it'   => 'it',
                'no'   => 'nb',
                'vn'   => 'vi',
            );
            if (isset($map[$slug])) {
                $lang = $map[$slug];
            }
        }

        return Mont_DeepL_API::normalize_lang($lang);
    }

    public static function diagnostics() {
        $settings = self::settings();
        $source   = Mont_DeepL_API::normalize_lang($settings['source_lang']);
        $target   = self::current_target_lang();
        $region   = '';

        if (class_exists('DC_Product_Manager\\DC_Region_Currency')) {
            $region = \DC_Product_Manager\DC_Region_Currency::get_current_region_slug();
        }

        return array(
            'plugin_enabled'   => !empty($settings['enabled']),
            'has_api_key'      => trim((string) $settings['api_key']) !== '',
            'api_plan'         => !empty($settings['api_plan']) ? $settings['api_plan'] : 'free',
            'cache_table'      => Mont_DeepL_Cache::table_exists(),
            'cache_entries'    => Mont_DeepL_Cache::count_entries(),
            'source_lang'      => $source,
            'target_lang'      => $target,
            'current_region'   => $region,
            'normalize_mixed_to_source' => !empty($settings['normalize_mixed_to_source']),
            'should_translate' => (
                $target &&
                $source &&
                (
                    $target !== $source ||
                    (!empty($settings['normalize_mixed_to_source']) && $target === $source)
                )
            ),
        );
    }

    public function enqueue_front() {
        if (is_admin()) {
            return;
        }

        $settings = self::settings();
        $diag     = self::diagnostics();

        if (empty($settings['api_key'])) {
            return;
        }

        if (empty($settings['enabled'])) {
            if (!empty($settings['disable_google'])) {
                wp_register_script('mont-deepl-bridge', false, array(), MONT_DEEPL_VERSION, true);
                wp_enqueue_script('mont-deepl-bridge');
                wp_add_inline_script('mont-deepl-bridge', 'window.montDeepL = window.montDeepL || { disableGoogle: true, enabled: false };', 'before');
            }
            return;
        }

        $ver = file_exists(MONT_DEEPL_DIR . 'assets/js/frontend.js')
            ? (string) filemtime(MONT_DEEPL_DIR . 'assets/js/frontend.js')
            : MONT_DEEPL_VERSION;

        wp_enqueue_script(
            'mont-deepl-front',
            MONT_DEEPL_URL . 'assets/js/frontend.js',
            array(),
            $ver,
            true
        );

        wp_localize_script('mont-deepl-front', 'montDeepL', array(
            'enabled'         => true,
            'disableGoogle'   => !empty($settings['disable_google']),
            'ajaxUrl'         => admin_url('admin-ajax.php'),
            'nonce'           => wp_create_nonce('mont_deepl_translate'),
            'sourceLang'      => $diag['source_lang'],
            'targetLang'      => $diag['target_lang'],
            'shouldTranslate' => (bool) $diag['should_translate'],
            'normalizeMixedToSource' => (bool) $diag['normalize_mixed_to_source'],
            'includeSelectors' => self::get_include_selectors(),
            'batchSize'       => 60,
        ));
    }

    public function ajax_translate() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'mont_deepl_translate')) {
            wp_send_json_error(array('message' => __('Invalid security token. Reload the page.', 'mont-deepl')), 403);
        }

        $settings = self::settings();
        if (empty($settings['enabled'])) {
            wp_send_json_error(array('message' => __('DeepL translation is disabled in settings.', 'mont-deepl')), 403);
        }

        if (empty($settings['api_key'])) {
            wp_send_json_error(array('message' => __('DeepL API key is missing.', 'mont-deepl')), 403);
        }

        $target = isset($_POST['target_lang']) ? sanitize_text_field(wp_unslash($_POST['target_lang'])) : '';
        $source = isset($_POST['source_lang']) ? sanitize_text_field(wp_unslash($_POST['source_lang'])) : $settings['source_lang'];

        if ($target === '') {
            $target = self::current_target_lang();
        }

        $texts = array();
        if (isset($_POST['texts'])) {
            $decoded = json_decode(wp_unslash($_POST['texts']), true);
            if (is_array($decoded)) {
                foreach ($decoded as $item) {
                    $item = wp_check_invalid_utf8((string) $item);
                    $item = trim($item);
                    if ($item !== '' && strlen($item) <= 5000) {
                        $texts[] = $item;
                    }
                }
            }
        }

        $texts = array_values(array_unique($texts));
        if (count($texts) > 250) {
            $texts = array_slice($texts, 0, 250);
        }

        if (!$texts) {
            wp_send_json_success(array('translations' => array(), 'usage' => Mont_DeepL_Cache::get_usage()));
        }

        $result = Mont_DeepL_API::translate_batch($texts, $target, $source);
        if (is_wp_error($result)) {
            wp_send_json_error(array(
                'message' => $result->get_error_message(),
                'code'    => $result->get_error_code(),
            ), 400);
        }

        wp_send_json_success(array(
            'translations' => $result,
            'usage'        => Mont_DeepL_Cache::get_usage(),
        ));
    }

    public function ajax_test_api() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'mont_deepl_test_api')) {
            wp_send_json_error(array('message' => __('Invalid security token. Reload the page.', 'mont-deepl')), 403);
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Forbidden', 'mont-deepl')), 403);
        }

        $settings = self::settings();
        $api_key  = isset($_POST['api_key']) ? sanitize_text_field(wp_unslash($_POST['api_key'])) : '';
        if ($api_key === '') {
            $api_key = trim((string) $settings['api_key']);
        }

        $source = isset($_POST['source_lang']) ? sanitize_text_field(wp_unslash($_POST['source_lang'])) : $settings['source_lang'];
        $target = isset($_POST['target_lang']) ? sanitize_text_field(wp_unslash($_POST['target_lang'])) : 'EN-US';

        $result = Mont_DeepL_API::test_connection($api_key, $source, $target);
        if (is_wp_error($result)) {
            $error_data = $result->get_error_data();
            wp_send_json_error(array(
                'message' => $result->get_error_message(),
                'code'    => $result->get_error_code(),
                'details' => is_array($error_data) ? $error_data : null,
            ), 400);
        }

        wp_send_json_success($result);
    }
}
