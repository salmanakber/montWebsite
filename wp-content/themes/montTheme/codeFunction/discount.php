<?php
class Custom_WooCommerce_Coupon {
    private $coupon_prefix = 'SUB-';
    public $discount_amount = 10; // 10% Discount
    private $discount_type = 'percent';
    private $usage_limit = 1;
    private $cookie_name = 'subscribed_popup';
    
    // Mailchimp API Credentials
    private $mailchimp_api_key = 'a4b76160d44eb9a1d980e67bd72c1ff1-us21';
    private $mailchimp_list_id = 'cca728f848';

    public function __construct() {
        add_action('wp_ajax_generate_coupon', [$this, 'handle_coupon_request']);
        add_action('wp_ajax_nopriv_generate_coupon', [$this, 'handle_coupon_request']);
        add_action('wp_footer', [$this, 'inject_announcement_bar']);
    }

    public function generate_coupon($email) {
        $coupon_code = $this->coupon_prefix . wp_generate_password(8, false);
        
        $coupon = array(
            'post_title'   => $coupon_code,
            'post_content' => 'One-time use subscription coupon',
            'post_status'  => 'publish',
            'post_author'  => 1,
            'post_type'    => 'shop_coupon'
        );

        $new_coupon_id = wp_insert_post($coupon);
        
        update_post_meta($new_coupon_id, 'discount_type', $this->discount_type);
        update_post_meta($new_coupon_id, 'coupon_amount', $this->discount_amount);
        update_post_meta($new_coupon_id, 'individual_use', 'yes');
        update_post_meta($new_coupon_id, 'usage_limit', $this->usage_limit);
        update_post_meta($new_coupon_id, 'email_restrictions', [$email]);
        
        return $coupon_code;
    }
    
    public function handle_coupon_request() {
        if (!isset($_POST['email']) || !is_email($_POST['email'])) {
            wp_send_json_error(['message' => 'Invalid email address']);
        }

        $email = sanitize_email($_POST['email']);

        if ($this->is_email_subscribed($email)) {
            wp_send_json_error(['message' => 'This email is already subscribed.']);
        }

        // Generate coupon and send to Mailchimp
        $coupon_code = $this->generate_coupon($email);
        $this->send_coupon_to_mailchimp($email, $coupon_code);

        // Ensure WooCommerce session is active before applying coupon
        if (WC()->cart) {
            WC()->session->set_customer_session_cookie(true);
            WC()->cart->add_discount($coupon_code); // Alternative to apply_coupon()
            WC()->cart->calculate_totals();
            WC()->cart->maybe_set_cart_cookies();
        } else {
            error_log('WooCommerce cart not initialized.');
        }

        // Set cookie to prevent popup for this user
        setcookie($this->cookie_name, '1', time() + (86400 * 30), '/');
        
        wp_send_json_success(['coupon' => $coupon_code]);
    }

    public function is_email_subscribed($email) {
        $member_id = md5(strtolower($email));
        $url = "https://us21.api.mailchimp.com/3.0/lists/{$this->mailchimp_list_id}/members/{$member_id}";

        $response = wp_remote_get($url, [
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode('user:' . $this->mailchimp_api_key),
                'Content-Type'  => 'application/json'
            ]
        ]);

        if (is_wp_error($response)) {
            return false;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        return isset($body['status']) && ($body['status'] === 'subscribed');
    }

    public function send_coupon_to_mailchimp($email, $coupon_code) {
        $url = "https://us21.api.mailchimp.com/3.0/lists/{$this->mailchimp_list_id}/members";

        $data = [
            'email_address' => $email,
            'status'        => 'subscribed',
            'merge_fields'  => ['COUPON' => $coupon_code] // Mailchimp must have a COUPON merge field
        ];

        $response = wp_remote_post($url, [
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode('user:' . $this->mailchimp_api_key),
                'Content-Type'  => 'application/json'
            ],
            'body' => json_encode($data)
        ]);

        return !is_wp_error($response);
    }

    public function inject_announcement_bar() {
        if (WC()->cart->has_discount()) {
            echo '<div id="announcement-bar">Coupon applied: ' . implode(', ', WC()->cart->get_applied_coupons()) . '</div>';
        }
    }
}

new Custom_WooCommerce_Coupon();
?>