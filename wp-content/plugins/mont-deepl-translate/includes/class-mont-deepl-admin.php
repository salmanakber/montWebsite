<?php
/**
 * Admin settings for DeepL.
 *
 * @package Mont_DeepL
 */

if (!defined('ABSPATH')) {
    exit;
}

class Mont_DeepL_Admin {

    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'menu'));
        add_action('admin_init', array(__CLASS__, 'register'));
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue'));
        add_action('admin_post_mont_deepl_clear_cache', array(__CLASS__, 'clear_cache'));
    }

    public static function enqueue($hook) {
        if ($hook !== 'settings_page_mont-deepl-translate') {
            return;
        }

        $ver = file_exists(MONT_DEEPL_DIR . 'assets/js/admin.js')
            ? (string) filemtime(MONT_DEEPL_DIR . 'assets/js/admin.js')
            : MONT_DEEPL_VERSION;

        wp_enqueue_script(
            'mont-deepl-admin',
            MONT_DEEPL_URL . 'assets/js/admin.js',
            array(),
            $ver,
            true
        );

        wp_localize_script('mont-deepl-admin', 'montDeepLAdmin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('mont_deepl_test_api'),
            'i18n'    => array(
                'testing'  => __('Testing DeepL API…', 'mont-deepl'),
                'testBtn'  => __('Test DeepL API', 'mont-deepl'),
                'success'  => __('Connection successful', 'mont-deepl'),
                'failed'   => __('Connection failed', 'mont-deepl'),
            ),
        ));
    }

    public static function menu() {
        add_options_page(
            __('Mont DeepL Translate', 'mont-deepl'),
            __('DeepL Translate', 'mont-deepl'),
            'manage_options',
            'mont-deepl-translate',
            array(__CLASS__, 'render')
        );
    }

    public static function register() {
        register_setting('mont_deepl_settings_group', Mont_DeepL_Plugin::OPTION_KEY, array(
            'type'              => 'array',
            'sanitize_callback' => array(__CLASS__, 'sanitize'),
            'default'           => Mont_DeepL_Plugin::defaults(),
        ));
    }

    public static function sanitize($input) {
        $existing = Mont_DeepL_Plugin::settings();
        $defaults = Mont_DeepL_Plugin::defaults();
        $out      = $defaults;

        if (!is_array($input)) {
            return $existing;
        }

        $out['enabled']        = !empty($input['enabled']) ? 1 : 0;
        $out['api_plan']       = (isset($input['api_plan']) && $input['api_plan'] === 'pro') ? 'pro' : 'free';
        $out['source_lang']    = Mont_DeepL_API::normalize_lang(isset($input['source_lang']) ? $input['source_lang'] : 'NB');
        if ($out['source_lang'] === '') {
            $out['source_lang'] = 'NB';
        }
        $out['monthly_limit']  = max(1000, (int) (isset($input['monthly_limit']) ? $input['monthly_limit'] : 500000));
        $out['disable_google'] = !empty($input['disable_google']) ? 1 : 0;
        $out['normalize_mixed_to_source'] = !empty($input['normalize_mixed_to_source']) ? 1 : 0;
        $out['polylang_style'] = !empty($input['polylang_style']) ? 1 : 0;
        $out['include_selectors'] = isset($input['include_selectors'])
            ? (string) wp_unslash($input['include_selectors'])
            : $existing['include_selectors'];

        $new_key = isset($input['api_key']) ? trim(sanitize_text_field($input['api_key'])) : '';
        if ($new_key !== '') {
            $out['api_key'] = $new_key;
        } else {
            $out['api_key'] = $existing['api_key'];
        }

        if (!empty($out['api_key'])) {
            $out['api_plan'] = Mont_DeepL_API::detect_plan_from_key($out['api_key']);
        }

        return $out;
    }

    public static function clear_cache() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Forbidden', 'mont-deepl'));
        }
        check_admin_referer('mont_deepl_clear_cache');
        Mont_DeepL_Cache::clear_all();
        wp_safe_redirect(add_query_arg(array(
            'page'    => 'mont-deepl-translate',
            'cleared' => '1',
        ), admin_url('options-general.php')));
        exit;
    }

    public static function render() {
        if (!current_user_can('manage_options')) {
            return;
        }

        $settings = Mont_DeepL_Plugin::settings();
        $usage    = Mont_DeepL_Cache::get_usage();
        $cached   = Mont_DeepL_Cache::count_entries();
        $limit    = (int) $settings['monthly_limit'];
        $used     = (int) $usage['characters'];
        $pct      = $limit > 0 ? min(100, round(($used / $limit) * 100, 1)) : 0;
        $diag     = Mont_DeepL_Plugin::diagnostics();
        $masked   = self::mask_key($settings['api_key']);
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Mont DeepL Translate', 'mont-deepl'); ?></h1>

            <?php if (!empty($_GET['cleared'])) : ?>
                <div class="notice notice-success is-dismissible"><p><?php esc_html_e('Translation cache cleared.', 'mont-deepl'); ?></p></div>
            <?php endif; ?>

            <?php if (!empty($_GET['settings-updated'])) : ?>
                <div class="notice notice-success is-dismissible"><p><?php esc_html_e('Settings saved.', 'mont-deepl'); ?></p></div>
            <?php endif; ?>

            <p><?php esc_html_e('Translates the storefront with DeepL when the visitor changes region. Every unique string is stored in a local cache so DeepL is only called once per string/language.', 'mont-deepl'); ?></p>

            <div style="max-width:720px;margin:16px 0 24px;padding:16px 18px;background:#fff;border:1px solid #dcdcde;border-radius:6px;">
                <h2 style="margin-top:0;"><?php esc_html_e('Local usage this month', 'mont-deepl'); ?></h2>
                <p style="margin:0 0 8px;">
                    <strong><?php echo esc_html(number_format_i18n($used)); ?></strong>
                    /
                    <?php echo esc_html(number_format_i18n($limit)); ?>
                    <?php esc_html_e('characters tracked locally', 'mont-deepl'); ?>
                    (<?php echo esc_html((string) $pct); ?>%)
                </p>
                <div style="height:10px;background:#f0f0f1;border-radius:999px;overflow:hidden;">
                    <div style="height:100%;width:<?php echo esc_attr((string) $pct); ?>%;background:<?php echo $pct > 90 ? '#d63638' : '#1d2327'; ?>;"></div>
                </div>
                <p style="margin:12px 0 0;color:#646970;">
                    <?php
                    printf(
                        esc_html__('Cached strings: %s', 'mont-deepl'),
                        '<strong>' . esc_html(number_format_i18n($cached)) . '</strong>'
                    );
                    ?>
                </p>
            </div>

            <div style="max-width:720px;margin:0 0 24px;padding:16px 18px;background:#fff;border:1px solid #dcdcde;border-radius:6px;">
                <h2 style="margin-top:0;"><?php esc_html_e('Status', 'mont-deepl'); ?></h2>
                <ul style="margin:0;padding-left:18px;">
                    <li><?php echo $diag['plugin_enabled'] ? '✅' : '⚠️'; ?> <?php esc_html_e('Plugin enabled', 'mont-deepl'); ?>: <strong><?php echo $diag['plugin_enabled'] ? esc_html__('Yes', 'mont-deepl') : esc_html__('No — check “Enable DeepL” below', 'mont-deepl'); ?></strong></li>
                    <li><?php echo $diag['has_api_key'] ? '✅' : '❌'; ?> <?php esc_html_e('API key saved', 'mont-deepl'); ?>: <strong><?php echo $diag['has_api_key'] ? esc_html($masked) : esc_html__('Missing', 'mont-deepl'); ?></strong></li>
                    <li><?php echo $diag['cache_table'] ? '✅' : '❌'; ?> <?php esc_html_e('Cache database table', 'mont-deepl'); ?></li>
                    <li><?php esc_html_e('Detected API plan', 'mont-deepl'); ?>: <strong><?php echo esc_html(strtoupper($diag['api_plan'])); ?></strong> <?php esc_html_e('(Free keys end with :fx)', 'mont-deepl'); ?></li>
                    <li><?php esc_html_e('Source language', 'mont-deepl'); ?>: <strong><?php echo esc_html($diag['source_lang']); ?></strong></li>
                    <li><?php esc_html_e('Current region target', 'mont-deepl'); ?>: <strong><?php echo esc_html($diag['target_lang'] ?: '—'); ?></strong> <?php echo $diag['current_region'] ? '(' . esc_html($diag['current_region']) . ')' : ''; ?></li>
                    <li><?php echo $diag['should_translate'] ? '✅' : 'ℹ️'; ?> <?php esc_html_e('Frontend will translate on this visit', 'mont-deepl'); ?>: <strong><?php echo $diag['should_translate'] ? esc_html__('Yes', 'mont-deepl') : esc_html__('No — switch region (e.g. Italy / International) to test', 'mont-deepl'); ?></strong></li>
                    <li><?php echo !empty($settings['normalize_mixed_to_source']) ? '✅' : 'ℹ️'; ?> <?php esc_html_e('Normalize English leftovers into source language', 'mont-deepl'); ?>: <strong><?php echo !empty($settings['normalize_mixed_to_source']) ? esc_html__('Enabled', 'mont-deepl') : esc_html__('Disabled', 'mont-deepl'); ?></strong></li>
                </ul>
            </div>

            <form method="post" action="options.php">
                <?php settings_fields('mont_deepl_settings_group'); ?>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><?php esc_html_e('Enable DeepL', 'mont-deepl'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="<?php echo esc_attr(Mont_DeepL_Plugin::OPTION_KEY); ?>[enabled]" value="1" <?php checked(!empty($settings['enabled'])); ?> />
                                <?php esc_html_e('Translate frontend when region language differs from source', 'mont-deepl'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="mont_deepl_api_key"><?php esc_html_e('DeepL API key', 'mont-deepl'); ?></label></th>
                        <td>
                            <input type="password" class="regular-text" id="mont_deepl_api_key" name="<?php echo esc_attr(Mont_DeepL_Plugin::OPTION_KEY); ?>[api_key]" value="" placeholder="<?php echo esc_attr($masked ?: __('Paste API key', 'mont-deepl')); ?>" autocomplete="off" />
                            <?php if ($masked) : ?>
                                <p class="description"><?php printf(esc_html__('Saved key: %s — leave blank to keep current key.', 'mont-deepl'), esc_html($masked)); ?></p>
                            <?php else : ?>
                                <p class="description"><?php esc_html_e('Get a free key at deepl.com/pro-api (Free plan ≈ 500,000 characters / month).', 'mont-deepl'); ?></p>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('API plan', 'mont-deepl'); ?></th>
                        <td>
                            <select name="<?php echo esc_attr(Mont_DeepL_Plugin::OPTION_KEY); ?>[api_plan]">
                                <option value="free" <?php selected($settings['api_plan'], 'free'); ?>><?php esc_html_e('Free (api-free.deepl.com) — auto-detected from :fx key', 'mont-deepl'); ?></option>
                                <option value="pro" <?php selected($settings['api_plan'], 'pro'); ?>><?php esc_html_e('Pro (api.deepl.com)', 'mont-deepl'); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="mont_deepl_source"><?php esc_html_e('Website source language', 'mont-deepl'); ?></label></th>
                        <td>
                            <select id="mont_deepl_source" name="<?php echo esc_attr(Mont_DeepL_Plugin::OPTION_KEY); ?>[source_lang]">
                                <?php foreach (Mont_DeepL_API::supported_targets() as $code => $label) : ?>
                                    <option value="<?php echo esc_attr($code); ?>" <?php selected($settings['source_lang'], $code); ?>>
                                        <?php echo esc_html($label . ' (' . $code . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="mont_deepl_limit"><?php esc_html_e('Monthly character soft limit', 'mont-deepl'); ?></label></th>
                        <td>
                            <input type="number" min="1000" step="1000" id="mont_deepl_limit" name="<?php echo esc_attr(Mont_DeepL_Plugin::OPTION_KEY); ?>[monthly_limit]" value="<?php echo esc_attr((string) $settings['monthly_limit']); ?>" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Disable Google Translate', 'mont-deepl'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="<?php echo esc_attr(Mont_DeepL_Plugin::OPTION_KEY); ?>[disable_google]" value="1" <?php checked(!empty($settings['disable_google'])); ?> />
                                <?php esc_html_e('Turn off Google/GTranslate hooks from the region switcher', 'mont-deepl'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Normalize mixed-language source', 'mont-deepl'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="<?php echo esc_attr(Mont_DeepL_Plugin::OPTION_KEY); ?>[normalize_mixed_to_source]" value="1" <?php checked(!empty($settings['normalize_mixed_to_source'])); ?> />
                                <?php esc_html_e('When target equals source (ex: Norway/NB), still translate likely English leftovers to source language', 'mont-deepl'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Polylang-style string mode', 'mont-deepl'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="<?php echo esc_attr(Mont_DeepL_Plugin::OPTION_KEY); ?>[polylang_style]" value="1" <?php checked(!empty($settings['polylang_style'])); ?> />
                                <?php esc_html_e('Force string translation on included selectors (skip English heuristics). Also exposes language codes (en, it, nb, vi) on the region switcher URL via ?lang=.', 'mont-deepl'); ?>
                            </label>
                            <p class="description">
                                <?php esc_html_e('Use with CRM curated product descriptions (notranslate). Pair with “Disable Google Translate” for DeepL-only behaviour.', 'mont-deepl'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="mont_deepl_include_selectors"><?php esc_html_e('Extra CSS selectors', 'mont-deepl'); ?></label></th>
                        <td>
                            <textarea id="mont_deepl_include_selectors" class="large-text code" rows="8" name="<?php echo esc_attr(Mont_DeepL_Plugin::OPTION_KEY); ?>[include_selectors]" placeholder=".my-block a&#10;.custom-widget .title"><?php echo esc_textarea($settings['include_selectors']); ?></textarea>
                            <p class="description">
                                <?php esc_html_e('One CSS selector per line. These are merged with built-in selectors for header, menu, WooCommerce, Elementor, and B2B blocks.', 'mont-deepl'); ?>
                            </p>
                            <details style="margin-top:8px;">
                                <summary><?php esc_html_e('Show built-in selectors', 'mont-deepl'); ?></summary>
                                <pre style="white-space:pre-wrap;background:#f6f7f7;padding:10px;border:1px solid #dcdcde;margin-top:8px;"><?php echo esc_html(implode("\n", Mont_DeepL_Plugin::default_include_selectors())); ?></pre>
                            </details>
                            <p class="description">
                                <?php
                                printf(
                                    esc_html__('Active selectors on frontend: %d', 'mont-deepl'),
                                    count(Mont_DeepL_Plugin::get_include_selectors())
                                );
                                ?>
                            </p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>

            <div style="max-width:720px;margin:24px 0;padding:16px 18px;background:#fff;border:1px solid #dcdcde;border-radius:6px;">
                <h2 style="margin-top:0;"><?php esc_html_e('Test DeepL connection', 'mont-deepl'); ?></h2>
                <p><?php esc_html_e('Sends one short Norwegian sentence to DeepL and shows the result. Use this to confirm your API key works before testing on the storefront.', 'mont-deepl'); ?></p>
                <p>
                    <label for="mont_deepl_test_target"><?php esc_html_e('Test target language', 'mont-deepl'); ?></label>
                    <select id="mont_deepl_test_target">
                        <option value="EN-US">English (US)</option>
                        <option value="IT">Italian</option>
                        <option value="DE">German</option>
                        <option value="FR">French</option>
                    </select>
                </p>
                <p>
                    <button type="button" class="button button-primary" id="mont_deepl_test_btn"><?php esc_html_e('Test DeepL API', 'mont-deepl'); ?></button>
                </p>
                <div id="mont_deepl_test_result" style="display:none;margin-top:12px;padding:12px 14px;border-radius:4px;"></div>
            </div>

            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" onsubmit="return confirm('Clear all cached translations? Next page loads may use API characters again.');">
                <input type="hidden" name="action" value="mont_deepl_clear_cache" />
                <?php wp_nonce_field('mont_deepl_clear_cache'); ?>
                <?php submit_button(__('Clear translation cache', 'mont-deepl'), 'delete'); ?>
            </form>
        </div>
        <?php
    }

    private static function mask_key($key) {
        $key = trim((string) $key);
        if ($key === '') {
            return '';
        }
        if (strlen($key) <= 8) {
            return str_repeat('•', strlen($key));
        }
        return substr($key, 0, 4) . str_repeat('•', max(4, strlen($key) - 8)) . substr($key, -4);
    }
}
