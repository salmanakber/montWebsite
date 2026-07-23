<?php
/**
 * The Template for displaying single products.
 *
 * @package WooCommerce\Templates
 * @version 1.6.4
 */

defined('ABSPATH') || exit;

get_header('shop');

/**
 * Hook: woocommerce_before_main_content.
 */
do_action('woocommerce_before_main_content');

while (have_posts()) {
    the_post();
    wc_get_template_part('content', 'single-product');
}

/**
 * Hook: woocommerce_after_main_content.
 */
do_action('woocommerce_after_main_content');

get_footer('shop');
