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
        if (is_admin()) {
            Mont_DeepL_Admin::init();
        }

        add_action('wp_enqueue_scripts', array($this, 'enqueue_front'), 40);
        add_action('wp_ajax_mont_deepl_translate', array($this, 'ajax_translate'));
        add_action('wp_ajax_nopriv_mont_deepl_translate', array($this, 'ajax_translate'));
    }

    public static function defaults() {
        return array(
            'enabled'        => 0,
            'api_key'        => '',
            'api_plan'       => 'free',
            'source_lang'    => 'NB',
            'monthly_limit'  => 500000,
            'disable_google' => 1,
        );
    }

    public static function settings() {
        $saved = get_option(self::OPTION_KEY, array());
        if (!is_array($saved)) {
            $saved = array();
        }
        return array_merge(self::defaults(), $saved);
    }

    /**
     * Resolve target DeepL language from current region.
     */
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
            // Fallback if class not loaded yet — cookie is region slug.
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

    public function enqueue_front() {
        if (is_admin()) {
            return;
        }

        $settings = self::settings();
        if (empty($settings['enabled']) || empty($settings['api_key'])) {
            // Still expose flag so region-switcher can skip Google when requested.
            if (!empty($settings['disable_google'])) {
                wp_register_script('mont-deepl-bridge', false, array(), MONT_DEEPL_VERSION, true);
                wp_enqueue_script('mont-deepl-bridge');
                wp_add_inline_script('mont-deepl-bridge', 'window.montDeepL = window.montDeepL || { disableGoogle: true, enabled: false };', 'before');
            }
            return;
        }

        $source = Mont_DeepL_API::normalize_lang($settings['source_lang']);
        $target = self::current_target_lang();
        $should = ($target && $source && $target !== $source);

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
            'enabled'       => (bool) $settings['enabled'],
            'disableGoogle' => !empty($settings['disable_google']),
            'ajaxUrl'       => admin_url('admin-ajax.php'),
            'nonce'         => wp_create_nonce('mont_deepl_translate'),
            'sourceLang'    => $source,
            'targetLang'    => $target,
            'shouldTranslate' => (bool) $should,
            'batchSize'     => 60,
        ));
    }

    public function ajax_translate() {
        check_ajax_referer('mont_deepl_translate', 'nonce');

        $settings = self::settings();
        if (empty($settings['enabled'])) {
            wp_send_json_error(array('message' => 'Disabled'), 403);
        }

        $target = isset($_POST['target_lang']) ? sanitize_text_field(wp_unslash($_POST['target_lang'])) : '';
        $source = isset($_POST['source_lang']) ? sanitize_text_field(wp_unslash($_POST['source_lang'])) : $settings['source_lang'];

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
        if (count($texts) > 80) {
            $texts = array_slice($texts, 0, 80);
        }

        $result = Mont_DeepL_API::translate_batch($texts, $target, $source);
        if (is_wp_error($result)) {
            wp_send_json_error(array(
                'message' => $result->get_error_message(),
                'code'    => $result->get_error_code(),
            ), 400);
        }

        $usage = Mont_DeepL_Cache::get_usage();

        wp_send_json_success(array(
            'translations' => $result,
            'usage'        => $usage,
        ));
    }
}
