<?php
/**
 * 
 */
class productHelper
{

    function __construct()

    {
        add_action('wp_ajax_handle_wishlist_ajax', array($this, 'handle_wishlist_ajax'));
        add_action('wp_ajax_nopriv_handle_wishlist_ajax', array($this, 'handle_wishlist_ajax'));
        add_shortcode('custom_product_grid', array($this, 'custom_product_grid_shortcode'));
		add_shortcode('custom_wishlist',  array($this,  'display_custom_wishlist'));

    }
	
	
	public function display_custom_wishlist() {
    if (!isset($_SESSION['custom_wishlist']) || empty($_SESSION['custom_wishlist'])) {
        echo '<p>Your wishlist is empty.</p>';
        return;
    }

    echo '<div class="wishlist-container">';
    foreach ($_SESSION['custom_wishlist'] as $product_id) {
        $product = wc_get_product($product_id);
        if ($product) {
            echo '<div class="wishlist-item" id="wishlist-item-' . esc_attr($product_id) . '">';
            echo '<img src="' . esc_url(get_the_post_thumbnail_url($product_id, 'thumbnail')) . '" alt="' . esc_attr($product->get_name()) . '">';
            echo '<div class="wishlist-details">';
            echo '<h3>' . esc_html($product->get_name()) . '</h3>';
            echo '<p class="wishlist-price">' . wc_price($product->get_price()) . '</p>';
            echo '</div>';
            echo '<button class="remove-wishlist-item" data-product-id="' . esc_attr($product_id) . '">Remove</button>';
            echo '</div>';
        }
    }
    echo '</div>';
}


    public function init_wishlist_session() {
        if (!session_id()) {
            session_start();
        }
        if (!isset($_SESSION['custom_wishlist'])) {
            $_SESSION['custom_wishlist'] = array();
        }
    }

    public function custom_product_grid_shortcode($atts) {
        $this->init_wishlist_session();

    // Parse attributes
        $atts = shortcode_atts(array(
            'limit' => 12,
            'category' => '',
            'related' => 'no'
        ), $atts);

    // Query arguments
        $args = array(
            'post_type' => 'product',
            'posts_per_page' => ($atts['limit'] === 'all') ? -1 : intval($atts['limit']),
            'post_status' => 'publish'
        );

    // Handle category filter
        if (!empty($atts['category'])) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'product_cat',
                    'field' => 'slug',
                    'terms' => explode(',', $atts['category'])
                )
            );
        }

    // Handle related products
        if ($atts['related'] === 'yes' && is_product()) {
            global $post;
            $current_product_id = $post->ID;
            $product_cats = wp_get_post_terms($current_product_id, 'product_cat', array('fields' => 'ids'));

            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'product_cat',
                    'field' => 'term_id',
                    'terms' => $product_cats
                )
            );
            $args['post__not_in'] = array($current_product_id);
        }

    // Get products
        $products = new WP_Query($args);

        ob_start();

        if ($products->have_posts()) : ?>
            <div class="custom-product-grid">
                <?php while ($products->have_posts()) : $products->the_post();
                    global $product;

                // Get gallery images
                    $gallery_images = $product->get_gallery_image_ids();
                    $hover_image = !empty($gallery_images) ? wp_get_attachment_image_url($gallery_images[0], 'full') : '';

                // Check if product is in wishlist
                    $in_wishlist = isset($_SESSION['custom_wishlist']) && 
                    in_array($product->get_id(), $_SESSION['custom_wishlist']);
                    ?>
				
                    <div class="product-item" 
                    data-product-id="<?php echo esc_attr($product->get_id()); ?>" 
                    onclick="window.location.href='<?php echo esc_url(get_permalink()); ?>'" style="cursor: pointer;">
                    <div class="wishlist-toggle <?php echo $in_wishlist ? 'in-wishlist' : ''; ?>"
                     data-product-id="<?php echo esc_attr($product->get_id()); ?>">
                     <i class="heart-icon"></i>
                 </div>

                 <div class="product-image-wrapper p-relative">
					 <div class="stock-left <?php echo ($product->managing_stock() && $product->get_stock_quantity() == 0) ? 'make-it-green' : ''; ?>">
						 <?php if ($product->managing_stock()) : ?>
						 <?php
								if($product->get_stock_quantity() > 0 )
								{
									?>
							<?php echo esc_html($product->get_stock_quantity()); ?>
						 <?php
								}
								if($product->get_stock_quantity() == 0 )
								{
									echo 'Pre-order';
								}
							?>
						
						<?php endif; ?>
						 
					 </div>
                    <?php 
                    $main_image = get_the_post_thumbnail_url($product->get_id(), 'full');
                    if (!$main_image) {
                        $main_image = wc_placeholder_img_src();
                    }
                    ?>
                    <img src="<?php echo esc_url($main_image); ?>" 
                    alt="<?php echo esc_attr($product->get_name()); ?>" 
                    class="main-image">

                    <?php if ($hover_image) : ?>
                        <img src="<?php echo esc_url($hover_image); ?>" 
                        alt="<?php echo esc_attr($product->get_name()); ?>" 
                        class="hover-image">
                    <?php endif; ?>
                </div>

                <h2 class="product-title">
                    <a href="<?php echo esc_url(get_permalink()); ?>">
                        <?php echo esc_html($product->get_name()); ?>
                    </a>
                </h2>

                <div class="product-price">
                    <?php echo $product->get_price_html(); ?>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
<?php endif;

wp_reset_postdata();

return ob_get_clean();
}

public function handle_wishlist_ajax() {
    $this->init_wishlist_session();
    
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    
    if ($product_id) {
        if (in_array($product_id, $_SESSION['custom_wishlist'])) {
            // Remove from wishlist
            $_SESSION['custom_wishlist'] = array_diff($_SESSION['custom_wishlist'], array($product_id));
            $status = 'removed';
        } else {
            // Add to wishlist
            $_SESSION['custom_wishlist'][] = $product_id;
            $status = 'added';
        }
        
        wp_send_json_success(array(
            'status' => $status,
            'product_id' => $product_id
        ));
    }
    
    wp_send_json_error('Invalid request');
}
}
new productHelper();
?>