<?php
class b2b extends getApi {
    public $path;
    public $url;
    public $api;

    function __construct($urlPath, $dirPath)
    {
        $this->path = $dirPath;
        $this->url = $urlPath;
        $this->api = 'sixerweb1234';

        // Call the activation hook
        register_activation_hook(__FILE__, array($this, 'plugin_activation'));

        // Call the deactivation hook
        register_deactivation_hook(__FILE__, array($this, 'plugin_deactivation'));

        // Register shortcode
        add_shortcode('monte_b2b_shortcode', array($this, 'monte_b2b_shortcode'));


        // Call the enqueue scripts and styles hook
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts_and_styles'));

        add_action('wp_footer', array($this, 'bubble_b2b_cart_button'));
    }
    
    public function plugin_activation() {
        // Create or update page with shortcode content
        $page_title = 'Monte Connected B2B';

        $shortcode_content = '[monte_b2b_shortcode]';


        $page_id = wp_insert_post(array(
            'post_title' => $page_title,
            'post_content' => $shortcode_content,
            'post_type' => 'page',
            'post_status' => 'publish',
        ));



        // Assign template to the newly created page
        if ($page_id) {
            $template_file = 'templates.php';
            update_post_meta($page_id, '_wp_page_template', $this->path . 'include/templates/' . $template_file);
        }

    }

    public function plugin_deactivation() {
        // Deactivation tasks, if any
    }

    function replace_variables_in_html_file($file_url, $variables) {
        $html_content = file_get_contents($file_url);
        if ($html_content === false) {
            return false;
        }
        foreach ($variables as $key => $value) {
            $placeholder = '{{' . $key . '}}';
            $html_content = str_replace($placeholder, $value, $html_content);
        }
        return $html_content;
    }

    public function getCategory()
    {
        $categories = json_decode($this->getApifromDC('cat', '', $this->api), true);

        if (!empty($categories)) {

// Your existing PHP code with some modifications for the slider
            echo '<div class="category-slider-container mont-cat-tabs">';
            echo '<button class="slider-arrow prev-arrow" aria-label="Previous categories" style="display:none;">&lt;</button>';
            echo '<div class="category-slider-wrapper mont-cat-tabs__scroller">';
            echo '<ul class="category-slider mont-cat-tabs__list" id="b2bmenu" role="tablist">';

// Variable to track if it's the first category
            $first_category = true;

            foreach ($categories as $category) {
                $category_name = $category['category_name'];
                $tab_id = 'tab-' . strtolower(str_replace(' ', '-', $category_name));
                $content_id = 'content-' . strtolower(str_replace(' ', '-', $category_name));

    // Add the "active" class to the first category
                $class = $first_category ? 'active' : '';
                $class2 = $first_category ? 'active-li is-active' : '';

                echo '<li class="category-item mont-cat-tabs__item mont-cat-item ' . $class2 . '">';
                echo '<button class="nav-link-monte-b2b mont-cat-tabs__link ' . $class . '" id="' . $tab_id . '" data-bs-toggle="tab" data-bs-target="#' . $content_id . '" type="button" role="tab" aria-controls="' . $content_id . '" aria-selected="' . ($first_category ? 'true' : 'false') . '">' . esc_html( $category_name ) . '</button>';
                echo '</li>';

    // Set $first_category to false after the first category tab is created
                $first_category = false;
            }

            echo '</ul>';
            echo '</div>';
            echo '<button class="slider-arrow next-arrow" aria-label="Next categories" style="display:none;">&gt;</button>';
            echo '</div>';



        // Tab content
            echo '<div class="tab-content tabb2b" id="myTabContent">';

        // Reset $first_category for tab-pane
            $first_category = true;

            foreach ($categories as $category) {
                $category_name = $category['category_name'];
                $content_id = 'content-' . strtolower(str_replace(' ', '-', $category_name));

            // Add the "active" class to the first tab-pane
                $class = $first_category ? ' show active' : '';


                echo '<div class="tab-pane-monte-b2b ' . $class . '" id="' . $content_id . '" role="tabpanel" aria-labelledby="' . $tab_id . '">';
                echo '<div class="container-fluid ">';
                echo '<div class="row">';
            // Check if products exist for this category
                if (!empty($category['products'])) {
                    foreach ($category['products'] as $product) {
                     $in_wishlist = isset($_SESSION['custom_wishlist']) && 
                     in_array($product['id'], $_SESSION['custom_wishlist']);
                    // Display product data here
                     echo '<div class="col-sm-3"><a href="'.esc_url( add_query_arg( 'productb2b', $product['id'] ) ).'">';
                     echo '<div class="product-img-b2b" style="position:relative">'; ?>
                     <div class="wishlist-toggle <?php echo $in_wishlist ? 'in-wishlist' : ''; ?>"
                       data-product-id="<?php echo esc_attr($product['id']); ?>">
                       <i class="heart-icon"></i>
                   </div>
                   <?php
                   echo '<img src="https://dc-garment.com/staff/'.json_decode($product['paths'])[0] .'" />';
                   echo '</div>';
                   echo '<div class="product-name-b2b">';
                   echo '<p>' . json_decode($product['data'], true)['pname'] . '</p>';
                   echo '</div>';
                   echo '</a></div>';
               }
           } else {
            echo '<p>No products available for this category.</p>';
        }
        echo '</div>';
        echo '</div>';
        echo '</div>';

            // Set $first_category to false after the first tab-pane is created
        $first_category = false;
    }


    echo '</div>';
} else {
    echo '<p>No categories available.</p>';
}
}

public function getProductDetails($pid, $remoteURL)
{
    $rightColumn = '';
    $leftColumn = '';
    $products = json_decode($this->getApifromDC('productDetails', $pid, $this->api), true);
    
    // Start left column (60%)
    $rightColumn .= '<div class="mont_gallery_wrapper">';
    
    // Back button
 
    // Desktop Gallery Grid
    $rightColumn .= '<div class="mont_gallery_image-grid">';
    foreach (json_decode($products['paths'], true) as $index => $path) {
        $rightColumn .= '<div class="mont_gallery_image-container">';
        $rightColumn .= '<img src="'.$remoteURL.$path.'" 
        class="mont_gallery_main-image" 
        alt="Product Image '.($index + 1).'" 
        data-index="'.$index.'" 
        data-gallerysrc="'.$remoteURL.$path.'">';
        $rightColumn .= '</div>';
    }
    $rightColumn .= '</div>';
    
    // Navigation dots
    $rightColumn .= '<div class="mont_gallery_navigation-dots">';
    foreach (json_decode($products['paths'], true) as $index => $path) {
        $rightColumn .= '<div class="mont_gallery_dot '.($index === 0 ? 'active' : '').'"></div>';
    }
    $rightColumn .= '</div>';
    
    // Mobile carousel (keep existing)
    $rightColumn .= '<div class="mobile-view-b2b loop owl-carousel owl-theme">';
    foreach (json_decode($products['paths'], true) as $path) {
        $rightColumn .= '<div class="item"><img src="'.$remoteURL.$path.'" class="b2b-img"></div>';
    }
    $rightColumn .= '</div>';
    
    $rightColumn .= '</div>'; // Close gallery wrapper

    // Add lightbox structure
    $rightColumn .= '<div class="mont_gallery_lightbox">';
    $rightColumn .= '<div class="mont_gallery_close-btn">×</div>';
    $rightColumn .= '<div class="mont_gallery_zoom-controls">';
    $rightColumn .= '<div class="mont_gallery_zoom-btn mont_gallery_zoom-in">+</div>';
    $rightColumn .= '<div class="mont_gallery_zoom-btn mont_gallery_zoom-out">−</div>';
    $rightColumn .= '<div class="mont_gallery_zoom-btn mont_gallery_restore">↺</div>';
    $rightColumn .= '</div>';
    $rightColumn .= '<div class="mont_gallery_lightbox-content">';
    $rightColumn .= '<img src="/placeholder.svg" class="mont_gallery_lightbox-image" alt="Lightbox Image">';
    $rightColumn .= '<div class="mont_gallery_thumbnails">';
    
    foreach (json_decode($products['paths'], true) as $index => $path) {
        $rightColumn .= '<img src="'.$remoteURL.$path.'" 
        class="mont_gallery_thumbnail '.($index === 0 ? 'active' : '').'" 
        alt="Thumbnail '.($index + 1).'" 
        data-index="'.$index.'">';
    }
    
    $rightColumn .= '</div>'; // Close thumbnails
    $rightColumn .= '</div>'; // Close lightbox-content
    $rightColumn .= '</div>'; // Close lightbox

    // Your existing hidden inputs
    $leftColumn .= '<input type="hidden" id="moq" name="moq" value="'.json_decode($products['data'], true)['moq'].'" >';
    $leftColumn .= '<input type="hidden" id="pname" name="pname" value="'.json_decode($products['data'], true)['pname'].'" >';
    $leftColumn .= '<input type="hidden" id="pcolor" name="pcolor" value="'.json_decode($products['data'], true)['color'].'" >';
    $leftColumn .= '<input type="hidden" id="pweight" name="pweight" value="'.json_decode($products['data'], true)['weight'].'" >';
    $leftColumn .= '<input type="hidden" id="pquality" name="pquality" value="'.json_decode($products['data'], true)['quality'].'" >';
    $leftColumn .= '<input type="hidden" id="pstock" name="pstock" value="'.$products['stock'].'" >';

    return array('right' => $rightColumn, 'left' => $leftColumn, 'moq' => json_decode($products['data'], true)['moq'] , 'data' => json_decode($products['data'], true));
}



    // Shortcode callback function
public function monte_b2b_shortcode($atts) {
    // Your shortcode content goes here
   session_start();
   $collar_type = '';
   $cuff_type = '';
   $output = '<div class="b2b-contents">';
    // Include your template HTML here
   foreach (get_field('choose_collar_update', 'option') as $key => $value){

    $collar_type .= ' <label class="b2b-check-to-go-collar">
                      <input type="radio" name="collar_type" value="'.ucfirst($value['name']).'" '.(($value['selected']) === 'Yes' ? 'checked' : '').'>
                     <input type="hidden" name="data_collar_type_transmit_129" value="'.$value['image'].'">                                 
                      <img src="'.$value['image'].'" height="69">
                      <div class="text-and-check">
                      <div class="blank-check '.(($value['selected']) === 'Yes' ? 'checkbtn' : '').'"></div>
                      <span>'.ucfirst($value['name']).'</span>
                     </div>
                 </label>';
}

   foreach (get_field('choose_cuff_update', 'option') as $key => $value){

    $cuff_type .= ' <label class="b2b-check-to-go-cuff">
                     <input type="radio" name="cuff_type" value="'.ucfirst($value['name']).'" '.(($value['selected']) === 'Yes' ? 'checked' : '').'>
                     <input type="hidden" name="data_cuff_type_transmit_111" value="'.$value['image'].'">                                 
                     <img src="'.$value['image'].'" height="69">
    <div class="text-and-check">
    <div class="blank-check '.(($value['selected']) === 'Yes' ? 'checkbtn' : '').'"></div>
    <span>'.ucfirst($value['name']).'</span>
    </div>
    </label>';
}

        
$pdetails = get_query_var('productb2b');
if(isset($_GET['productb2b']) AND !empty($_GET['productb2b'])){
    $product_details = $this->getProductDetails($_GET['productb2b'], 'https://dc-garment.com/staff/');
     // $fabricDetail = '<input type="hidden" name="fabricColor" value="'.$product_details['data']['color'].'"> '
    // echo '<br><br><br><br>';
    // print_r($product_details['data']);
    $template_content_b2b_details = $this->replace_variables_in_html_file
    (
        $this->path . 'include/templates/details.php' ,
        array(
            'images' => $product_details['right'],
            'details' => $product_details['left'],
            'moq' => 'This color requires a minimum order of '.$product_details['moq'].' shirts total',
            'done' => (isset($_SESSION['products']) ? 'add-to-cart-button-bubble' : 'e'),
            'collar' => $collar_type,
            'cuff' =>  $cuff_type 
        ));
    $output .= $template_content_b2b_details;
}
else
{
    $template_content = $this->replace_variables_in_html_file($this->path . 'include/templates/templates.php' , array('data' => $this->getCategory()));
    $output .= $template_content;
}
    // You can include any additional HTML or PHP code here
$output .= '</div>';
return $output;
}
public function bubble_b2b_cart_button(){
    require_once $this->path . 'include/templates/model.php';
}

public function enqueue_scripts_and_styles() {
        // Enqueue scripts and styles if needed
    wp_enqueue_script('b2b-notify-script', $this->url . 'assets/js/b2b-notify.js', array('jquery'), '1.0', true);
    wp_enqueue_script('b2b-custom-script', $this->url . 'assets/js/custom.js', array('jquery'), '1.1', true);
    wp_enqueue_script('b2b-owl-script', $this->url . 'assets/js/owl.carousel.js', array('jquery'), '1.0', true);
    wp_enqueue_style('b2b-style', $this->url . 'assets/css/style.css', array(), '1.3');
    wp_enqueue_style('b2b-pdp', $this->url . 'assets/css/b2b-pdp.css', array('b2b-style'), '1.1');
    wp_enqueue_style('b2b-notify', $this->url . 'assets/css/notify.css', array(), '1.0');
    wp_enqueue_style('b2b-owl-css-min', $this->url . 'assets/css/owl.carousel.min.css', array(), '1.0');
    wp_enqueue_style('b2b-owl-default-css', $this->url . 'assets/css/owl.theme.default.min.css', array(), '1.0');
    wp_enqueue_style('b2b-fontaweseom', 'https://maxcdn.bootstrapcdn.com/font-awesome/4.6.3/css/font-awesome.min.css');

    // Shared category tab design (theme file when available).
    $theme_tabs = get_template_directory() . '/assets/category-tabs.css';
    if ( file_exists( $theme_tabs ) ) {
        wp_enqueue_style(
            'mont-category-tabs',
            get_template_directory_uri() . '/assets/category-tabs.css',
            array( 'b2b-style' ),
            (string) filemtime( $theme_tabs )
        );
    }

    wp_localize_script('b2b-custom-script', 'ajax_object', array('ajax_url' => admin_url('admin-ajax.php')));

}
}

?>