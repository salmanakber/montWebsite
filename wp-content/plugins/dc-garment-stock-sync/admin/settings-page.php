<?php
if (!defined('ABSPATH')) {
    exit;
}

class DC_Garment_Settings_Page {
    public function __construct() {
        add_action('admin_menu', [$this, 'register_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
    }

    public function register_admin_menu() {
        add_options_page('DC Garment Stock Sync', 'Stock Sync', 'manage_options', 'dc-garment-stock-sync', [$this, 'settings_page']);
    }

    public function register_settings() {
        register_setting('dc_garment_settings', 'dc_garment_api_url');
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>DC Garment Stock Sync Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('dc_garment_settings');
                do_settings_sections('dc_garment_settings');
                ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="dc_garment_api_url">API URL:</label></th>
                        <td><input type="text" name="dc_garment_api_url" id="dc_garment_api_url" value="<?php echo esc_attr(get_option('dc_garment_api_url')); ?>" class="regular-text" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
}

new DC_Garment_Settings_Page();
