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
        add_action('admin_post_mont_deepl_clear_cache', array(__CLASS__, 'clear_cache'));
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
        $defaults = Mont_DeepL_Plugin::defaults();
        $out      = $defaults;

        if (!is_array($input)) {
            return $out;
        }

        $out['enabled']       = !empty($input['enabled']) ? 1 : 0;
        $out['api_key']       = isset($input['api_key']) ? sanitize_text_field($input['api_key']) : '';
        $out['api_plan']      = (isset($input['api_plan']) && $input['api_plan'] === 'pro') ? 'pro' : 'free';
        $out['source_lang']   = Mont_DeepL_API::normalize_lang(isset($input['source_lang']) ? $input['source_lang'] : 'NB');
        if ($out['source_lang'] === '') {
            $out['source_lang'] = 'NB';
        }
        $out['monthly_limit'] = max(1000, (int) (isset($input['monthly_limit']) ? $input['monthly_limit'] : 500000));
        $out['disable_google']= !empty($input['disable_google']) ? 1 : 0;

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
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Mont DeepL Translate', 'mont-deepl'); ?></h1>

            <?php if (!empty($_GET['cleared'])) : ?>
                <div class="notice notice-success is-dismissible"><p><?php esc_html_e('Translation cache cleared.', 'mont-deepl'); ?></p></div>
            <?php endif; ?>

            <p><?php esc_html_e('Translates the storefront with DeepL when the visitor changes region. Every unique string is stored forever in a local cache so DeepL is only called once per string/language.', 'mont-deepl'); ?></p>

            <div style="max-width:720px;margin:16px 0 24px;padding:16px 18px;background:#fff;border:1px solid #dcdcde;border-radius:6px;">
                <h2 style="margin-top:0;"><?php esc_html_e('Usage this month', 'mont-deepl'); ?></h2>
                <p style="margin:0 0 8px;">
                    <strong><?php echo esc_html(number_format_i18n($used)); ?></strong>
                    /
                    <?php echo esc_html(number_format_i18n($limit)); ?>
                    <?php esc_html_e('characters', 'mont-deepl'); ?>
                    (<?php echo esc_html((string) $pct); ?>%)
                </p>
                <div style="height:10px;background:#f0f0f1;border-radius:999px;overflow:hidden;">
                    <div style="height:100%;width:<?php echo esc_attr((string) $pct); ?>%;background:<?php echo $pct > 90 ? '#d63638' : '#1d2327'; ?>;"></div>
                </div>
                <p style="margin:12px 0 0;color:#646970;">
                    <?php
                    printf(
                        /* translators: %s: cache entry count */
                        esc_html__('Cached strings: %s (these never cost API characters again)', 'mont-deepl'),
                        '<strong>' . esc_html(number_format_i18n($cached)) . '</strong>'
                    );
                    ?>
                </p>
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
                            <input type="password" class="regular-text" id="mont_deepl_api_key" name="<?php echo esc_attr(Mont_DeepL_Plugin::OPTION_KEY); ?>[api_key]" value="<?php echo esc_attr($settings['api_key']); ?>" autocomplete="off" />
                            <p class="description"><?php esc_html_e('Get a free key at deepl.com/pro-api (Free plan ≈ 500,000 characters / month).', 'mont-deepl'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('API plan', 'mont-deepl'); ?></th>
                        <td>
                            <select name="<?php echo esc_attr(Mont_DeepL_Plugin::OPTION_KEY); ?>[api_plan]">
                                <option value="free" <?php selected($settings['api_plan'], 'free'); ?>><?php esc_html_e('Free (api-free.deepl.com)', 'mont-deepl'); ?></option>
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
                            <p class="description"><?php esc_html_e('Language your content is written in (this store looks Norwegian — NB is recommended).', 'mont-deepl'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="mont_deepl_limit"><?php esc_html_e('Monthly character soft limit', 'mont-deepl'); ?></label></th>
                        <td>
                            <input type="number" min="1000" step="1000" id="mont_deepl_limit" name="<?php echo esc_attr(Mont_DeepL_Plugin::OPTION_KEY); ?>[monthly_limit]" value="<?php echo esc_attr((string) $settings['monthly_limit']); ?>" />
                            <p class="description"><?php esc_html_e('Stops new DeepL calls when reached. Cached translations still apply.', 'mont-deepl'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Disable Google Translate', 'mont-deepl'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="<?php echo esc_attr(Mont_DeepL_Plugin::OPTION_KEY); ?>[disable_google]" value="1" <?php checked(!empty($settings['disable_google'])); ?> />
                                <?php esc_html_e('Turn off Google/GTranslate hooks from the region switcher (recommended)', 'mont-deepl'); ?>
                            </label>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>

            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" onsubmit="return confirm('Clear all cached translations? Next page loads may use API characters again.');">
                <input type="hidden" name="action" value="mont_deepl_clear_cache" />
                <?php wp_nonce_field('mont_deepl_clear_cache'); ?>
                <?php submit_button(__('Clear translation cache', 'mont-deepl'), 'delete'); ?>
            </form>

            <hr />
            <h2><?php esc_html_e('Region → language mapping', 'mont-deepl'); ?></h2>
            <ul>
                <li><?php esc_html_e('International → English (EN)', 'mont-deepl'); ?></li>
                <li><?php esc_html_e('Italy → Italian (IT)', 'mont-deepl'); ?></li>
                <li><?php esc_html_e('Norway → Norwegian (NB) — usually no API call if source is NB', 'mont-deepl'); ?></li>
                <li><?php esc_html_e('Việt Nam → not supported by DeepL (page stays in source language)', 'mont-deepl'); ?></li>
            </ul>
        </div>
        <?php
    }
}
