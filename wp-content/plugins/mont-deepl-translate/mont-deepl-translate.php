<?php
/**
 * Plugin Name: Mont DeepL Translate
 * Description: Region-aware DeepL website translation with persistent string caching to stay within API character limits.
 * Version: 1.0.0
 * Author: Monte Napoleone
 * Text Domain: mont-deepl
 *
 * @package Mont_DeepL
 */

if (!defined('ABSPATH')) {
    exit;
}

define('MONT_DEEPL_VERSION', '1.0.1');
define('MONT_DEEPL_FILE', __FILE__);
define('MONT_DEEPL_DIR', plugin_dir_path(__FILE__));
define('MONT_DEEPL_URL', plugin_dir_url(__FILE__));
define('MONT_DEEPL_TABLE', 'mont_deepl_cache');

require_once MONT_DEEPL_DIR . 'includes/class-mont-deepl-cache.php';
require_once MONT_DEEPL_DIR . 'includes/class-mont-deepl-api.php';
require_once MONT_DEEPL_DIR . 'includes/class-mont-deepl-admin.php';
require_once MONT_DEEPL_DIR . 'includes/class-mont-deepl-plugin.php';

register_activation_hook(__FILE__, array('Mont_DeepL_Cache', 'install'));

add_action('plugins_loaded', static function () {
    Mont_DeepL_Plugin::instance();
});
