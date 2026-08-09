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

    // Handle related products — same category, exclude current, prefer in-stock.
        if ($atts['related'] === 'yes' && is_product()) {
            global $post;
            $current_product_id = $post->ID;
            $product_cats = wp_get_post_terms($current_product_id, 'product_cat', array('fields' => 'ids'));
            $limit = ($atts['limit'] === 'all') ? 8 : max(1, intval($atts['limit']));

            $args['posts_per_page'] = $limit;
            $args['post__not_in']   = array($current_product_id);
            $args['orderby']        = 'rand';
            $args['tax_query']      = array(
                array(
                    'taxonomy' => 'product_cat',
                    'field'    => 'term_id',
                    'terms'    => ! empty( $product_cats ) ? $product_cats : array( 0 ),
                ),
            );
            // Prefer sellable shirts first.
            $args['meta_query'] = array(
                array(
                    'key'     => '_stock_status',
                    'value'   => array( 'instock', 'onbackorder' ),
                    'compare' => 'IN',
                ),
            );

            $products = new WP_Query($args);

            // Fallback: same category without stock filter if too few.
            if ( $products->post_count < $limit && ! empty( $product_cats ) ) {
                $found_ids = wp_list_pluck( $products->posts, 'ID' );
                $need = $limit - count( $found_ids );
                $fill_args = array(
                    'post_type'      => 'product',
                    'post_status'    => 'publish',
                    'posts_per_page' => $need,
                    'orderby'        => 'rand',
                    'post__not_in'   => array_merge( array( $current_product_id ), $found_ids ),
                    'tax_query'      => array(
                        array(
                            'taxonomy' => 'product_cat',
                            'field'    => 'term_id',
                            'terms'    => $product_cats,
                        ),
                    ),
                );
                $fill = new WP_Query( $fill_args );
                if ( $fill->have_posts() ) {
                    $products->posts = array_merge( $products->posts, $fill->posts );
                    $products->post_count = count( $products->posts );
                }
                wp_reset_postdata();
            }
        } else {
            // Get products
            $products = new WP_Query($args);
        }

        if ( ! isset( $products ) ) {
            $products = new WP_Query($args);
        }

        ob_start();

        if ($products->have_posts()) : ?>
            <div class="custom-product-grid">
                <?php while ($products->have_posts()) : $products->the_post();
                    global $product;

                // Get gallery images
                    $gallery_images = $product->get_gallery_image_ids();

                // Check if product is in wishlist
                    $in_wishlist = isset($_SESSION['custom_wishlist']) && 
                    in_array($product->get_id(), $_SESSION['custom_wishlist']);
                    ?>
				
                    <div class="product-item"
                    data-product-id="<?php echo esc_attr($product->get_id()); ?>"
                    data-href="<?php echo esc_url(get_permalink()); ?>"
                    style="cursor: pointer;">
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
                    $slide_urls = array();
                    $main_image = get_the_post_thumbnail_url($product->get_id(), 'large');
                    if ($main_image) {
                        $slide_urls[] = $main_image;
                    }
                    foreach ( (array) $gallery_images as $gid ) {
                        $u = wp_get_attachment_image_url( (int) $gid, 'large' );
                        if ( $u && ! in_array( $u, $slide_urls, true ) ) {
                            $slide_urls[] = $u;
                        }
                    }
                    if ( empty( $slide_urls ) ) {
                        $slide_urls[] = wc_placeholder_img_src();
                    }
                    ?>
                    <div class="mont-card-slider" data-mont-card-slider>
                        <div class="mont-card-slider__track">
                            <?php foreach ( $slide_urls as $i => $url ) : ?>
                                <div class="mont-card-slider__slide">
                                    <img src="<?php echo esc_url( $url ); ?>"
                                         alt="<?php echo esc_attr( $product->get_name() ); ?>"
                                         <?php echo $i === 0 ? 'loading="eager"' : 'loading="lazy"'; ?>
                                         decoding="async"
                                         draggable="false"
                                         class="<?php echo $i === 0 ? 'main-image' : ''; ?>">
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <?php if ( count( $slide_urls ) > 1 ) : ?>
                            <div class="mont-card-slider__dots" aria-hidden="true">
                                <?php foreach ( $slide_urls as $i => $_u ) : ?>
                                    <button type="button"
                                            class="mont-card-slider__dot<?php echo $i === 0 ? ' is-active' : ''; ?>"
                                            data-index="<?php echo (int) $i; ?>"
                                            aria-label="<?php echo esc_attr( sprintf( 'Image %d', $i + 1 ) ); ?>"></button>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
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