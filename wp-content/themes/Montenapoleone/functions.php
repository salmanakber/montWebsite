<?php
// SVG Icons class.
require get_template_directory() . '/classes/class-twenty-twenty-one-svg-icons.php';
require get_template_directory() . '/classes/class-product-helper.php';
require get_template_directory() . '/classes/class-custom-variation-setting.php';
require get_template_directory() . '/classes/body-type-taxonomy-image.php';
require get_template_directory() . '/classes/collar-type-taxonomy-image.php';
require get_template_directory() . '/classes/cuff-type-taxonomy-image.php';
require get_template_directory() . '/classes/women-product-vc.php';

$Body_type_Taxonomy_Images = new Body_type_Taxonomy_Images();

$Body_type_Taxonomy_Images->init();



$Collar_type_Taxonomy_Images = new Collar_type_Taxonomy_Images();

$Collar_type_Taxonomy_Images->init();



$Cuff_type_Taxonomy_Images = new Cuff_type_Taxonomy_Images();

$Cuff_type_Taxonomy_Images->init();

// Custom color classes.
require get_template_directory() . '/classes/class-twenty-twenty-one-custom-colors.php';
require get_template_directory() . '/classes/optimization.php';

new Twenty_Twenty_One_Custom_Colors();

// Enhance the theme by hooking into WordPress.
require get_template_directory() . '/inc/template-functions.php';

// Menu functions and filters.
require get_template_directory() . '/inc/menu-functions.php';

// Custom template tags for the theme.
require get_template_directory() . '/inc/template-tags.php';

// Customizer additions.
require get_template_directory() . '/classes/class-twenty-twenty-one-customize.php';
new Twenty_Twenty_One_Customize();

// Block Patterns.
require get_template_directory() . '/inc/block-patterns.php';

// Block Styles.
require get_template_directory() . '/inc/block-styles.php';

// Dark Mode.
require_once get_template_directory() . '/classes/class-twenty-twenty-one-dark-mode.php';
new Twenty_Twenty_One_Dark_Mode();

/**
 * Enqueue scripts for the customizer preview.
 *
 * @since Twenty Twenty-One 1.0
 *
 * @return void
 */
function twentytwentyone_customize_preview_init() {
	wp_enqueue_script(
		'twentytwentyone-customize-helpers',
		get_theme_file_uri( '/assets/js/customize-helpers.js' ),
		array(),
		wp_get_theme()->get( 'Version' ),
		true
	);

	wp_enqueue_script(
		'twentytwentyone-customize-preview',
		get_theme_file_uri( '/assets/js/customize-preview.js' ),
		array( 'customize-preview', 'customize-selective-refresh', 'jquery', 'twentytwentyone-customize-helpers' ),
		wp_get_theme()->get( 'Version' ),
		true
	);
}
add_action( 'customize_preview_init', 'twentytwentyone_customize_preview_init' );

/**
 * Enqueue scripts for the customizer.
 *
 * @since Twenty Twenty-One 1.0
 *
 * @return void
 */
function twentytwentyone_customize_controls_enqueue_scripts() {

	wp_enqueue_script(
		'twentytwentyone-customize-helpers',
		get_theme_file_uri( '/assets/js/customize-helpers.js' ),
		array(),
		wp_get_theme()->get( 'Version' ),
		true
	);
}
add_action( 'customize_controls_enqueue_scripts', 'twentytwentyone_customize_controls_enqueue_scripts' );

/**
 * Calculate classes for the main <html> element.
 *
 * @since Twenty Twenty-One 1.0
 *
 * @return void
 */
function twentytwentyone_the_html_classes() {
	/**
	 * Filters the classes for the main <html> element.
	 *
	 * @since Twenty Twenty-One 1.0
	 *
	 * @param string The list of classes. Default empty string.
	 */
	$classes = apply_filters( 'twentytwentyone_html_classes', '' );
	if ( ! $classes ) {
		return;
	}
	echo 'class="' . esc_attr( $classes ) . '"';
}

/**
 * Add "is-IE" class to body if the user is on Internet Explorer.
 *
 * @since Twenty Twenty-One 1.0
 *
 * @return void
 */
function twentytwentyone_add_ie_class() {
	?>
	<script>
	if ( -1 !== navigator.userAgent.indexOf( 'MSIE' ) || -1 !== navigator.appVersion.indexOf( 'Trident/' ) ) {
		document.body.classList.add( 'is-IE' );
	}
	</script>
	<?php
}
add_action( 'wp_footer', 'twentytwentyone_add_ie_class' );

if ( ! function_exists( 'wp_get_list_item_separator' ) ) :
	/**
	 * Retrieves the list item separator based on the locale.
	 *
	 * Added for backward compatibility to support pre-6.0.0 WordPress versions.
	 *
	 * @since 6.0.0
	 */
	function wp_get_list_item_separator() {
		/* translators: Used between list items, there is a space after the comma. */
		return __( ', ', 'twentytwentyone' );
	}
endif;



function my_assets()

{

  //wp_deregister_script('jquery');





  wp_enqueue_style('bootstrap', get_stylesheet_directory_uri() . '/css/bootstrap.min.css');

  wp_enqueue_style('all', get_stylesheet_directory_uri() . '/css/all.min.css');

  //wp_enqueue_style('owl', get_stylesheet_directory_uri() . '/css/owl.carousel.min.css');

  wp_enqueue_style('magnific', get_stylesheet_directory_uri() . '/css/magnific-popup.css');

  wp_enqueue_style('magnifiercss', get_stylesheet_directory_uri() . '/css/image-magnifier.css');

  wp_enqueue_style('dashicons', get_stylesheet_directory_uri() . '/css/dashicons.min.css');

  wp_enqueue_style('slickcss', get_stylesheet_directory_uri() . '/css/slick-theme.css');

  wp_enqueue_style('style', get_stylesheet_uri());

  wp_enqueue_style('responsive', get_stylesheet_directory_uri() . '/css/responsive.css');
  wp_enqueue_style('custom_css', get_stylesheet_directory_uri() . '/css/custom_css.css');
  wp_enqueue_style('style_css', get_stylesheet_directory_uri() . '/css/style_css.css');



  wp_enqueue_script('jquery', get_template_directory_uri() . '/js/jquery-3.5.1.min.js', array(), '2.2.3', true);

  wp_enqueue_script('all-js', get_template_directory_uri() . '/js/all.js', array(), '2.2.3', true);

  wp_enqueue_script('magnific-js', get_template_directory_uri() . '/js/jquery.magnific-popup.min.js', array(), '2.2.3', true);

  wp_enqueue_script('magnifier-js', get_template_directory_uri() . '/js/image-magnifier.js', array(), '2.2.3', true);

  wp_enqueue_script('zoom-js', get_template_directory_uri() . '/js/jquery.zoom.js', array(), '2.2.3', true);

  wp_enqueue_script('typeout-js', get_template_directory_uri() . '/js/jquery.typeout.js', array(), '2.2.3', true);

  wp_enqueue_script('headroom-js', get_template_directory_uri() . '/js/headroom.min.js', array(), '2.2.3', true);

  //wp_enqueue_script('owl-js', get_template_directory_uri() . '/js/owl.carousel.min.js', array(), '2.2.3', true);

  wp_enqueue_script('slick-js', get_template_directory_uri() . '/js/slick.min.js', array(), '2.2.3', true);

  wp_enqueue_script('bootstrapjs', get_template_directory_uri() . '/js/bootstrap.min.js', array(), '2.2.3', true);

  wp_enqueue_script('script', get_template_directory_uri() . '/js/script.js', array(), '2.2.5', true);



  wp_enqueue_script('script', 'https://cdnjs.cloudflare.com/ajax/libs/jquery-cookie/1.4.1/jquery.cookie.js', array(), '2.2.5', true);

  wp_enqueue_script('script', 'https://cdnjs.cloudflare.com/ajax/libs/modernizr/2.8.3/modernizr.min.js', array(), '2.2.5', true);



//   wp_enqueue_script('script', get_template_directory_uri() . '/js/popup-cookie.js', array(), '2.2.5', true);

}

function adminscript()
{
	 wp_enqueue_script('customAdmin', get_template_directory_uri() . '/js/custom.js', array(), '2.2.5', true);
}
add_action('admin_enqueue_scripts', 'adminscript');
add_action('wp_enqueue_scripts', 'my_assets');

add_filter('woocommerce_default_address_fields', 'override_default_address_checkout_fields', 20, 1);

function override_default_address_checkout_fields($address_fields)
{
   if(isset($_GET['lang']) && $_GET['lang'] =='en'){
    $companyName  = 'Company Name';
    $firstName  = 'First Name';
    $lastName  = 'Last Name';
    $state = 'State';
    $postnummer = 'Postcode';
    $email_id  = 'Email';
    $address  = 'Address';
    $address  = 'Address';
    $city = 'City';
   }else{ 
    $companyName  = 'selskapsnavn';
    $firstName  = 'Fornavn';
    $lastName  = 'Etternavn';
    $state = 'Stater';
    $email_id  = 'E-post';
    $postnummer = 'Postnummer';
    $address = 'Besøksadresse';
    $city = 'By';
    
  } 

  $address_fields['email']['placeholder'] = $email_id;

  $address_fields['first_name']['placeholder'] = $firstName;

  $address_fields['last_name']['placeholder'] = $lastName;

  $address_fields['address_1']['placeholder'] = $address;

  $address_fields['state']['placeholder'] = $state;

  $address_fields['postcode']['placeholder'] = $postnummer;

  $address_fields['city']['placeholder'] = $city;



  return $address_fields;

}



add_filter('woocommerce_checkout_fields', 'override_billing_checkout_fields', 20, 1);

function override_billing_checkout_fields($fields){

   if(isset($_GET['lang']) && $_GET['lang'] =='en'){
    $companyName  = 'Company Name';
    $firstName  = '*First Name';
    $lastName  = 'Last Name';
    $state = 'State';
    $postnummer = 'Postcode';
    $email_id  = 'Email';
    $address  = 'Address';
    $address  = 'Address';
    $city = 'City';
    $phone_code = 'Code';
    $phone_call = 'Phone';
   }else{ 
    $companyName  = 'selskapsnavn';
    $firstName  = '*Fornavn';
    $lastName  = 'Etternavn';
    $state = 'Stater';
    $email_id  = 'E-post';
    $postnummer = 'Postnummer';
    $address = 'Besøksadresse';
    $city = 'By';
    $phone_code = 'Kode';
    $phone_call = 'Telefon ';
  } 

  $fields['billing']['billing_phone']['placeholder'] = 'Phone';

  $fields['billing']['billing_email']['placeholder'] = $email_id;
  $fields['billing']['billing_first_name']['placeholder'] = $firstName;
  $fields['billing']['billing_phone_code']['placeholder'] = $phone_code;
  $fields['billing']['billing_phone']['placeholder'] = $phone_call;
  /*$fields['billing']['billing_city']['placeholder'] = $firstName;
  $fields['billing']['billing_city']['placeholder'] = $firstName;
  $fields['billing']['shipping_city']['placeholder'] = $firstName;
  $fields['billing']['shipping_postcode']['placeholder'] = $firstName;
  $fields['billing']['billing_postcode']['placeholder'] = $firstName;*/




  return $fields;

}



add_filter('woocommerce_save_account_details_required_fields', 'sm_save_account_details_required_fields');

function sm_save_account_details_required_fields($required_fields)

{

  unset($required_fields['account_display_name']);

  return $required_fields;

}





if (!function_exists('woocommerce_get_product_thumbnail')) {

  /**

   * Get the product thumbnail, or the placeholder if not set.

   *

   * @subpackage Loop

   * @param string $size (default: 'shop_catalog')

   * @param int $deprecated1 Deprecated since WooCommerce 2.0 (default: 0)

   * @param int $deprecated2 Deprecated since WooCommerce 2.0 (default: 0)

   * @return string

   */

  function woocommerce_get_product_thumbnail($size = 'shop_catalog', $deprecated1 = 0, $deprecated2 = 0)

  {

    global $post;

    if (has_post_thumbnail()) {

      return '<a href="' . get_permalink($post->ID) . '">' . get_the_post_thumbnail($post->ID, $size) . '</a>';

    } elseif (wc_placeholder_img_src()) {

      return wc_placeholder_img($size);

    }

  }

}






/***add_action( 'woocommerce_product_thumbnails', 'woocommerce_cart_btn', 20 );



function woocommerce_cart_btn(){

   ?>

<div class="add-cart-btn">

    <a href="#">Add to cart</a>

</div>



<?php

// }*/



function my_custom_post_fabric()

{



  //labels array added inside the function and precedes args array



  $labels = array(

    'name' => _x('Retailer', 'post type general name'),

    'singular_name' => _x('Retailer', 'post type singular name'),

    'add_new' => _x('Add New', 'Retailer'),

    'add_new_item' => __('Add New Retailer'),

    'edit_item' => __('Edit Retailer'),

    'new_item' => __('New Retailer'),

    'all_items' => __('All Retailer'),

    'view_item' => __('View Retailer'),

    'search_items' => __('Search Retailer'),

    'not_found' => __('No Retailer found'),

    'not_found_in_trash' => __('No Retailer found in the Trash'),

    'parent_item_colon' => '',

    'menu_name' => 'Retailer'

  );



  // args array



  $args = array(

    'labels' => $labels,

    'description' => 'Displays Retailer and their ratings',

    'public' => true,

    'menu_position' => 4,

    'supports' => array('title', 'editor', 'custom-fields', 'thumbnail', 'excerpt', 'comments'),

    'has_archive' => true,

  );



  register_post_type('retailer', $args);

}

add_action('init', 'my_custom_post_fabric');



//registration of taxonomies



function my_taxonomies_fabric()

{



  $labels = array(

    'name' => _x('Retailer Categories', 'taxonomy general name'),

    'singular_name' => _x('Retailer Category', 'taxonomy singular name'),

    'search_items' => __('Search Retailer Categories'),

    'all_items' => __('All Retailer Categories'),

    'parent_item' => __('Parent Retailer Category'),

    'parent_item_colon' => __('Parent Retailer Category:'),

    'edit_item' => __('Edit Retailer Category'),

    'update_item' => __('Update Retailer Category'),

    'add_new_item' => __('Add New Retailer Category'),

    'new_item_name' => __('New Retailer Category'),

    'menu_name' => __(' Retailer Categories'),

  );



  $args = array('labels' => $labels, 'hierarchical' => true);

  register_taxonomy('retailer_category', 'retailer', $args);



  $labels1 = array(

    'name' => _x('Body Type', 'taxonomy general name'),

    'singular_name' => _x('Body Type', 'taxonomy singular name'),

    'search_items' => __('Search Body Type'),

    'all_items' => __('All Body Type'),

    'parent_item' => __('Parent Body Type'),

    'parent_item_colon' => __('Parent Body Type:'),

    'edit_item' => __('Edit Body Type'),

    'update_item' => __('Update Body Type'),

    'add_new_item' => __('Add New Body Type'),

    'new_item_name' => __('New Body Type'),

    'menu_name' => __(' Body Type'),

  );



  $args1 = array('labels' => $labels1, 'hierarchical' => true);

  register_taxonomy('body_type', 'retailer', $args1);



  $labels2 = array(

    'name' => _x('Cuff Type', 'taxonomy general name'),

    'singular_name' => _x('Cuff Type', 'taxonomy singular name'),

    'search_items' => __('Search Cuff Type'),

    'all_items' => __('All Cuff Type'),

    'parent_item' => __('Parent Cuff Type'),

    'parent_item_colon' => __('Parent Cuff Type:'),

    'edit_item' => __('Edit Cuff Type'),

    'update_item' => __('Update Cuff Type'),

    'add_new_item' => __('Add New Cuff Type'),

    'new_item_name' => __('New Cuff Type'),

    'menu_name' => __(' Cuff Type'),

  );



  $args2 = array('labels' => $labels2, 'hierarchical' => true);

  register_taxonomy('cuff_type', 'retailer', $args2);



  $labels3 = array(

    'name' => _x('Collar Type', 'taxonomy general name'),

    'singular_name' => _x('Collar Type', 'taxonomy singular name'),

    'search_items' => __('Search Collar Type'),

    'all_items' => __('All Collar Type'),

    'parent_item' => __('Parent Collar Type'),

    'parent_item_colon' => __('Parent Collar Type:'),

    'edit_item' => __('Edit Collar Type'),

    'update_item' => __('Update Collar Type'),

    'add_new_item' => __('Add New Collar Type'),

    'new_item_name' => __('New Collar Type'),

    'menu_name' => __(' Collar Type'),

  );



  $args3 = array('labels' => $labels3, 'hierarchical' => true);

  register_taxonomy('collar_type', 'retailer', $args3);

}



add_action('init', 'my_taxonomies_fabric', 0);





add_action('wp_ajax_nopriv_send_email', 'send_retailer_email');

add_action('wp_ajax_send_email', 'send_retailer_email');



function send_retailer_email()

{

  $params = array();

  parse_str($_POST['form_data'], $params);

  // print_r($params);

  // die;

  $to = strip_tags($params['email']);



  $subject = 'Retailer Order Details';



  $headers = "From: " . "sachincodingkart@gmail.com" . "rn";

  $headers .= "Reply-To: " . "sachincodingkart@gmail.com" . "rn";

  $headers .= "CC: susan@example.comrn";

  $headers .= "MIME-Version: 1.0rn";

  $headers .= "Content-Type: text/html; charset=ISO-8859-1rn";



  $message = '<html><body>';

  $message .= '<h1>Retailer Order Details</h1>';

  $message .= '</body></html>';



  $message = '<html><body>';

  $message .= '<table rules="all" style="border-color: #666;" cellpadding="10">';

  $message .= "<tr style='background: #eee;'><td><strong>Fabric Type:</strong> </td><td>" . strip_tags($params['fabric_type']) . "</td></tr>";

  $message .= "<tr><td><strong>body_type:</strong> </td><td>" . strip_tags($params['body_type']) . "</td></tr>";

  $message .= "<tr><td><strong>collar_type:</strong> </td><td>" . strip_tags($params['collar_type']) . "</td></tr>";

  for ($i = 0; $i < count($params['breakDown_size']); $i++) {

    $message .= "<tr><td><strong>BeakDown Size:</strong> </td><td>(" . strip_tags($params['breakDown_size'][$i]) . ") (" . strip_tags($params['breakDown_quantity'][$i]) . ")</td></tr>";

  }

  $message .= "<tr><td><strong>Company:</strong> </td><td>" . strip_tags($params['company_name']) . "</td></tr>";

  $message .= "<tr><td><strong>Address:</strong> </td><td>" . strip_tags($params['address']) . "</td></tr>";

  $message .= "<tr><td><strong>Country:</strong> </td><td>" . strip_tags($params['country']) . "</td></tr>";

  $message .= "<tr><td><strong>Email:</strong> </td><td>" . strip_tags($params['email']) . "</td></tr>";

  $message .= "<tr><td><strong>Contact:</strong> </td><td>" . strip_tags($params['contact']) . "</td></tr>";

  $message .= "<tr><td><strong>Note:</strong> </td><td>" . strip_tags($params['note']) . "</td></tr>";



  $message .= "</table>";

  $message .= "</body></html>";



  if (mail($to, $subject, $message, $headers)) {

    global $wpdb;

    $form_data = $_POST['form_data'];

    $post_id = $params['post_id'];

    $wpdb->query("INSERT INTO wp_retailer (post_id, form_data) VALUES ('$post_id', '$form_data')");

    $response_array['status'] = 1;

    echo json_encode($response_array);

  }

  die;

}



function retailer_page_admin_menu()

{

  add_menu_page(__('Retailer Request', 'retailer_page'), __('Retailer Request', 'retailer_page'), 'edit_posts', 'add_data', 'retailer_form_page_handler', '', 6);

}

add_action('admin_menu', 'retailer_page_admin_menu');



function retailer_form_page_handler()

{

  global $wpdb;

  $pagenum = isset($_GET['pagenum']) ? absint($_GET['pagenum']) : 1;

  $limit = 10;

  $offset = ($pagenum - 1) * $limit;

  $total = $wpdb->get_var("SELECT COUNT(*) FROM  wp_retailer");

  // print_r($total);

  $num_of_pages = ceil($total / $limit);



  $qry = "select * from  wp_retailer LIMIT $offset, $limit";

  $result = $wpdb->get_results($qry, object);

  //echo "<pre>";

   //print_r($result);

  $params = array();

  if ($result) :

    echo '<table id="myTable"><thead><tr><th>Fabric Type </th><th>Body Type</th><th>Collar Type</th><th>Cuff Type</th><th>BreakDown Size</th><th>Company</th><th>Address</th><th>Country</th><th>Email</th></th><th>Contact</th><th>Note</th></tr></thead><tbody>';

    foreach ($result as $row) {

      // parse_str($row->form_data, $params);

      // echo "<pre>";

     // print_r($params);

      $params = unserialize($row->form_data);



      $term1 = get_term($params['body_type'], 'body_type');

      $term2 = get_term($params['collar_type'], 'collar_type');

      $term3 = get_term($params['cuff_type'], 'cuff_type');

      $jk=$params['fabric_type']; 



    echo '<td><p>' .get_the_title( $jk[0] ).'</p></td>'; 





      $body_type_image_id = get_term_meta($params['body_type'], 'body-type-taxonomy-image-id', true);

      $body_type_img = wp_get_attachment_image_src($body_type_image_id, 'thumbnail');

      echo '<td><p>' . $term1->name . '</p></td>';



      $collar_type_image_id = get_term_meta($params['collar_type'], 'collar-type-taxonomy-image-id', true);

      $collar_type_img = wp_get_attachment_image_src($collar_type_image_id, 'thumbnail');

      echo '<td><img src="' . $collar_type_img[0] . '" width="56" height="59"><p style="margin: 0;">' . $term2->name . '</p></td>';



      $cuff_type_image_id = get_term_meta($params['cuff_type'], 'cuff-type-taxonomy-image-id', true);

      $cuff_type_img = wp_get_attachment_image_src($cuff_type_image_id, 'thumbnail');

      echo '<td><img src="' . $cuff_type_img[0] . '" width="56" height="59"><p style="margin: 0;">' . $term3->name . '</p></td>';



      echo '<td>';

      for ($i = 0; $i < count($params['breakDown_size']); $i++) {

        if ($params['breakDown_quantity'][$i] > 0) {

          echo 'Size: ' . $params['breakDown_size'][$i] . ' Quantity: ' . $params['breakDown_quantity'][$i] . '<br>';

        }

      }

      echo '</td>';



      echo '<td>' . $params['company_name'] . '</td><td>' . $params['address'] . ',' . $params['address1'] . ',' . $params['address2'] . '</td><td>' . $params['country'] . '</td><td>' . $params['email'] . '</td></td><td>' . $params['contact_code'] . $params['contact'] . '</td><td>' . $params['comment'] . '</td></tr>';

    }

    echo "</tbody></table>";

    //Link for Pagination



    $page_links = paginate_links(array(

      'base'               => add_query_arg('pagenum', '%#%'),

      'format'             => '',

      'prev_text'          => __('«', 'aag'),

      'next_text'          => __('»', 'aag'),

      'total'              => $num_of_pages,

      'current'            => $pagenum,

      'base'               => add_query_arg('pagenum', '%#%'),

      'format'             => '',

      'prev_next'          => true,

      'prev_text'          => __('←', 'aag'),

      'next_text'          => __('→', 'aag'),

      'before_page_number' => '<li><span class="page-numbers btn btn-pagination btn-tb-primary">',

      'after_page_number'  => '</span></li>'

    ));

    if ($page_links) {

?>

      <br class="clear">

      <nav id="archive-navigation" class="paging-navigation tbWow fadeInUp" role="navigation" style="visibility: visible; animation-name: fadeInUp;">

        <ul class="page-numbers" style="display:inline;">

          <li style="display:inline;">

            <?php echo $page_links; ?>

          </li>

        </ul>

      </nav>

  <?php   }

  endif;

  ?>

  <link rel="stylesheet" type="text/css" href="//cdn.datatables.net/1.10.21/css/jquery.dataTables.min.css">

  <script src="//cdn.datatables.net/1.10.21/js/jquery.dataTables.min.js"></script>

  <script type="text/javascript">

    jQuery(document).ready(function() {

      jQuery('#myTable').DataTable();

    });

  </script>

<?php

}



add_shortcode('inquiryformm', 'inquiryform', 1);

function inquiryform($atts, $content = null)

{

  $post_arr = shortcode_atts(array('post_id' => '',), $atts);

  $post_id = $post_arr['post_id'];

  ob_start();

?>

  <div class="inquiry-formm">

    <form id="retailer_form_<?php echo $post_id; ?>" method="post" enctype="multipart/form-data">

      <div class="row">

        <div class="col-md-12">

          <div class="collection-form">

            <div class="body-fit-type">

              <h4>Body Fit</h4>

              <?php $terms = get_the_terms($post_id, 'body_type');

              $count = 1;

              foreach ((array) $terms as $term) {

                $image_id = get_term_meta($term->term_id, 'body-type-taxonomy-image-id', true);

                $post_thumbnail_img = wp_get_attachment_image_src($image_id, 'thumbnail'); ?>

                <li class="<?php if (count($terms) - $count == 0) {

                              echo "active";

                            } ?> change_body_type" body_type="<?php echo $term->term_id; ?>"><a href="javscript:void(0)">

                    <input type="radio" name="body_type" class="radio_<?php echo $term->term_id; ?>" value="<?php echo $term->term_id; ?>" checked>

                    <img src="<?php echo $post_thumbnail_img[0]; ?>" alt="">

                    <div class="fit_text"><?php echo

                                            $term->name; ?></div>

                  </a></li>

              <?php $count++;

              } ?>



            </div>



            <div class="clearfix"></div>



            <div class="measure-boxes collar-type">

              <h4>Collar Type</h4>

              <?php $terms = get_the_terms($post_id, 'collar_type');

              foreach ((array) $terms as $term) {

                $image_id = get_term_meta($term->term_id, 'collar-type-taxonomy-image-id', true);

                $post_thumbnail_img = wp_get_attachment_image_src($image_id, 'thumbnail'); ?>

                <label class="">

                  <input type="radio" name="collar_type" value="<?php echo $term->term_id; ?>" checked="">

                  <img src="<?php echo $post_thumbnail_img[0]; ?>">

                  <span><?php echo $term->name; ?></span>

                </label>

              <?php } ?>

            </div>



            <div class="measure-boxes cuff-type">

              <h4>Cuff Type</h4>

              <?php $terms = get_the_terms($post_id, 'cuff_type');

              foreach ((array) $terms as $term) {

                $image_id = get_term_meta($term->term_id, 'cuff-type-taxonomy-image-id', true);

                $post_thumbnail_img = wp_get_attachment_image_src($image_id, 'thumbnail'); ?>

                <label class="">

                  <input type="radio" name="cuff_type" value="<?php echo $term->term_id; ?>" checked="">

                  <img src="<?php echo $post_thumbnail_img[0]; ?>">

                  <span><?php echo $term->name; ?></span>

                </label>

              <?php } ?>

            </div>



            <div class="measure-boxes size-type">

              <h4>Size BreakDown</h4>

              <div class="sizes-boxx">

                <ul>

                  <?php $size_breakdown =  get_field("size_breakdown", $post_id);

                  $count = $size_breakdown[0]['max_size'] - $size_breakdown[0]['min_size'];

                  $size = $size_breakdown[0]['min_size'];

                  for ($i = 0; $i <= $count; $i++) { ?>

                    <li>

                      <span class="sizeee"><?php echo $size; ?></span>

                      <div class="input_field input-fld">

                        <input type="hidden" name="breakDown_size[]" readonly="" value="<?php echo $size; ?>" />

                        <input type="text" class="input_val" name="breakDown_quantity[]" readonly="" value="0">

                        <span class="minus"> - </span> <span class="plus"> + </span>

                      </div>

                    </li>

                  <?php $size++;

                  } ?>

                </ul>

              </div>

            </div>



            <div class="clearfix"></div>

            <div class="next-btnn"><a href="#next-btn-form">Next</a></div>



          </div>

        </div>

        <div class="clearfix"></div>

        <div class="col-md-12 submit-formm" id="next-btn-form">

                          <?php if(isset($_GET['lang']) && $_GET['lang'] =='en'){
                                $companyName  = 'Company Name';
                              }else{ 
                                $companyName  = 'selskapsnavn';                                
                              } ?>
          <div class="fieldss"> <textarea placeholder="Note for manufacture" name="note"></textarea></div>

          <div class="fieldss"> <input type="text" name="company_name" placeholder="<?php echo $companyName ?>"></div>

          <div class="fieldss"> <input type="text" name="address" placeholder="Address"></div>

          <div class="fieldss"> <input type="text" name="country" placeholder="Country"></div>

          <div class="fieldss"> <input type="email" name="email" placeholder="Email"></div>

          <div class="fieldss"> <input type="number" name="contact" placeholder="Contact"></div>

          <div class="fieldss fieldss-btn"> <button type="submit" class="retailer_form_submit" post_id="<?php echo $post_id; ?>">Send Inquiry</button></div>

        </div>

      </div>

    </form>

  </div>

  <?php return ob_get_clean();

}



add_action('wp_ajax_nopriv_call_short_code', 'call_short_code_content');

add_action('wp_ajax_call_short_code', 'call_short_code_content');



function call_short_code_content()

{

  $post_id = $_POST['post_id'];

  $response_array['shortcode_content'] = do_shortcode("[inquiryformm post_id=" . $post_id . "]");

  $response_array['status'] = 1;

  // $response_array['post_id'] = $post_id;

  echo json_encode($response_array);

  die;

}







//add_action('woocommerce_after_add_to_cart_form', 'woocommerce_after_add_to_cart_form_callback');



function woocommerce_after_add_to_cart_form_callback()

{

  $product_tabs = apply_filters('woocommerce_product_tabs', array());



  if (!empty($product_tabs)) : ?>
<div class="tabs-cont">
	<div class="">
		<?php foreach ($product_tabs as $key => $product_tab) : ?>
		<?php
		if(esc_attr($key) != 'reviews' AND esc_attr($key) != 'additional_information' AND esc_attr($key) != 'details' AND  esc_attr($key) != 'description')
		{
			?>
		<div class="col-6 p-0 tabsData tabsName_<?php echo $key;?>">
			<p>
		<?php call_user_func($product_tab['callback'], $key, $product_tab); ?>
			</p>
		</div>
		<?php
		}
	?>
		 <?php endforeach; ?>
			<div class="tabsData dynamictext">
		   <?php 
      if(isset($_GET['lang']) && $_GET['lang'] =='en'){
      if( has_term( 'suits', 'product_cat' ) ) { ?>
       <div class="custommade_cancellation_policy alert alert-info commentBox scaleImage aaa">
		   <div class="closep"><i class="fa fa-times" aria-hidden="true"></i></div>
          <h6>CANCELLATION & RETURNS FOR TAILOR MADE SHIRTS.</h6>
          <p> All tailor-made shirts is 100% individually customize according to customers preferences. Hence, we DO NOT accept returns for any reason except production error.</p>
          <h6>DELIVERYTIME FOR TAILOR MADE SHIRTS.</h6>
          <p>All tailor-made shirts require more work and changing new body parts therefore we have to add up-to seven (7) days extra additional to normal delivery time. </p>
       </div>
     <?php } else{ ?>
       <div class="custommade_cancellation_policy alert alert-info commentBox scaleImage vbs">
		   <div class="closep">X</div>
          <h6>CANCELLATION & RETURNS FOR TAILOR MADE SHIRTS.</h6>
          <p> All tailor-made shirts are 100% individually customize according to customers preferences. Hence, we DO NOT accept returns for any reason except production error.</p>
          <h6>DELIVERYTIME FOR TAILOR MADE SHIRTS.</h6>
          <p>All tailor-made shirts require more work and changing new body parts therefore we have to add up-to seven (7) days extra additional to normal delivery time. </p>
       </div>
      <?php } ?>

    <?php  
    }else{
       if( has_term( 'suits', 'product_cat' ) ) { ?>
         <div class="custommade_cancellation_policy alert alert-info commentBox scaleImage as">
			 <div class="closep">X</div>
            <h6>KANSELLERING OG RETUR FOR SKREDDERSYKTE SKJORTER.</h6>
            <p> Alle skreddersydde skjorter er 100% individuelt tilpasset etter kundens preferanser. Derfor aksepterer vi IKKE returer av noen grunn bortsett fra produksjonsfeil.</p>
            <h6>LEVERINGSTID FOR SKREDDERSYKTE SKJORTER.</h6>
            <p>Alle skreddersydde skjorter krever mer arbeid og skifter av nye deler, derfor må vi legge til opptil syv (7) dager ekstra i tillegg til normal leveringstid.</p>
         </div>
       <?php } else{ ?>
         <div class="custommade_cancellation_policy alert alert-info commentBox scaleImage vd">
			 <div class="closep">X</div>
            <h6>KANSELLERING OG RETUR FOR SKREDDERSYKTE SKJORTER.</h6>
            <p> Alle skreddersydde skjorter er 100% individuelt tilpasset etter kundens preferanser. Derfor aksepterer vi IKKE returer av noen grunn bortsett fra produksjonsfeil.</p>
            <h6>LEVERINGSTID FOR SKREDDERSYKTE SKJORTER.</h6>
            <p>Alle skreddersydde skjorter krever mer arbeid og skifter av nye deler, derfor må vi legge til opptil syv (7) dager ekstra i tillegg til normal leveringstid.</p>
         </div>
        <?php } ?>
         <?php }
?>		
		</div>
	</div>
</div>

<!--     <div class="woocommerce-tabs wc-tabs-wrapper" style>

<!--       <ul class="tabs wc-tabs" role="talist"> -->

        <?php //foreach ($product_tabs as $key => $product_tab) : ?>
<!--           <li class="<?php //echo esc_attr($key); ?>_tab" id="tab-title-<?php //echo esc_attr($key); ?>" role="tab" aria-controls="tab-<?php //echo esc_attr($key); ?>"> -->

<!--             <a href="#tab-<?php //echo esc_attr($key); ?>"> -->

              <?php //echo wp_kses_post(apply_filters('woocommerce_product_' . $key . '_tab_title', $product_tab['title'], $key)); ?>

<!--             </a>

          </li> -->

        <?php //endforeach; ?>

<!--       </ul> -->

      <?php //foreach ($product_tabs as $key => $product_tab) : ?>

<!--         <div class="woocommerce-Tabs-panel woocommerce-Tabs-panel--<?php echo esc_attr($key); ?> panel entry-content wc-tab" id="tab-<?php //echo esc_attr($key); ?>" role="tabpanel" aria-labelledby="tab-title-<?php echo esc_attr($key); ?>"> -->

          <?php

//           if (isset($product_tab['callback'])) {

//             call_user_func($product_tab['callback'], $key, $product_tab);

//           }

          ?>

<!--         </div> -->

      <?php //endforeach; ?>



    <?php //do_action('woocommerce_product_after_tabs'); ?>

<!--     </div> -->



  <?php endif;

}





add_action('wp_ajax_nopriv_new_send_email', 'new_send_retailer_email');

add_action('wp_ajax_new_send_email', 'new_send_retailer_email');



function new_send_retailer_email()

{

  $params = array();

  // parse_str($_POST['form_data'][0]['post_data'], $params);

  // print_r($_POST);

  // die;





  $subject = 'Retailer Order Details';

  //$headers = "From: " . "sachincodingkart@gmail.com" . "rn";

  //$headers .= "Reply-To: " . "sachincodingkart@gmail.com" . "rn";

  // $headers .= "CC: susan@example.comrn";

  $headers .= "MIME-Version: 1.0rn";

  $headers .= "Content-Type: text/html; charset=ISO-8859-1rn";



  $message = '<html><body>';

  $message .= '<h1>Retailer Order Details</h1>';

  $message .= '</body></html>';



  $message = '<html><body>';

  $message .= '<table rules="all" style="border-color: #666;" cellpadding="10">';



  foreach ($_POST['form_data'] as $key => $value) {

    $collection_name = '';

    $collection_name = get_the_title($value['post_id']);

    parse_str($value['post_data'], $params);



    if ($value['post_id']) {

      $term1 = get_term($params['body_type'], 'body_type');

      $term2 = get_term($params['collar_type'], 'collar_type');

      $term3 = get_term($params['cuff_type'], 'cuff_type');

      //print_r($params);die;

      $message .= "<tr style='background: #eee;'><td><strong>Fabric Type:</strong> </td><td>" . strip_tags($collection_name) . "</td></tr>";

      //  $message .= "<tr><td><strong>Fabric Type:</strong> </td><td>" . strip_tags($params['fabric_type']) . "</td></tr>";

      $message .= "<tr><td><strong>body_type:</strong> </td><td>" . strip_tags($term1->name) . "</td></tr>";

      $message .= "<tr><td><strong>collar_type:</strong> </td><td>" . strip_tags($term2->name) . "</td></tr>";

      $message .= "<tr><td><strong>cuff_type:</strong> </td><td>" . strip_tags($term3->name) . "</td></tr>";

      for ($i = 0; $i < count($params['breakDown_size']); $i++) {

        if ($params['breakDown_quantity'][$i] > 0) {

          $message .= "<tr><td><strong>BeakDown Size:</strong> </td><td>(" . strip_tags($params['breakDown_size'][$i]) . ") (" . strip_tags($params['breakDown_quantity'][$i]) . ")</td></tr>";

        }

      }

    }



    $address_data = $params;

  }

  // print_r($address_data);die;

  $message .= "<tr style='background: #eee;'><td></td><td></td></tr>";

  $message .= "<tr><td><strong>Company:</strong> </td><td>" . strip_tags($address_data['company_name']) . "</td></tr>";

  $message .= "<tr><td><strong>Address:</strong> </td><td>" . strip_tags($address_data['address']) . ', ' . strip_tags($address_data['address1']) . ', ' . strip_tags($address_data['address2']) . "</td></tr>";

  $message .= "<tr><td><strong>Country:</strong> </td><td>" . strip_tags($address_data['country']) . "</td></tr>";

  $message .= "<tr><td><strong>Email:</strong> </td><td>" . strip_tags($address_data['email']) . "</td></tr>";

  $message .= "<tr><td><strong>Contact:</strong> </td><td>" . strip_tags($address_data['contact_code']) . strip_tags($address_data['contact']) . "</td></tr>";

  $message .= "<tr><td><strong>Comment:</strong> </td><td>" . strip_tags($address_data['comment']) . "</td></tr>";



  $message .= "</table>";

  $message .= "</body></html>";



  //$to = strip_tags($address_data['email']);

  // $to = get_option( 'admin_email' );

  $to = 'monte@montenapoleonetailor.com,thanhdieusakura@gmail.com,info@codingkart.com,annbetty844@gmail.com';



  if (mail($to, $subject, $message, $headers)) {



    global $wpdb;

    // $form_data = $_POST['form_data'];

    // $post_id = $params['post_id'];

    // $wpdb->query("INSERT INTO wp_retailer (post_id, form_data) VALUES ('$post_id', '$form_data')"  );



    foreach ($_POST['form_data'] as $key => $value) {

      $post_id = $value['post_id'];

      if ($post_id) {

        parse_str($value['post_data'], $params2);

        $params2['company_name'] = $address_data['company_name'];

        $params2['address'] = $address_data['address'];

        $params2['address1'] = $address_data['address1'];

        $params2['address2'] = $address_data['address2'];

        $params2['country'] = $address_data['country'];

        $params2['email'] = $address_data['email'];

        $params2['contact_code'] = $address_data['contact_code'];

        $params2['contact'] = $address_data['contact'];

        $params2['comment'] = $address_data['comment'];
       // $params2['contactperson'] = $address_data['contactperson'];
        //$params2['postbox'] = $address_data['postbox'];

        // print_r($address_data);die;

        $form_data = maybe_serialize($params2);

        $wpdb->query("INSERT INTO wp_retailer (post_id, form_data) VALUES ('$post_id', '$form_data')");

      }

    }

    $thank_to = strip_tags($address_data['email']);

    $thank_subject = 'Thank you for submitting your request';

    //$thank_message = 'Thank you for submitting your request we will get in touch with you shortly.';

    ob_start();

    include(get_stylesheet_directory() . '/thankyou.html');

    $thank_message = ob_get_contents();

    ob_end_clean();

    mail($thank_to, $thank_subject, $thank_message, $headers);

    $response_array['status'] = 1;

    echo json_encode($response_array);

  }

  die;

}

/*-----------------------------thumbnail size----------------------------*/

// add_filter('woocommerce_get_image_size_thumbnail', function ($size) {

//   return array(

//     'width'  => 1020,

//     'height' => 1020,

//     'crop'   => 1,

//   );

// });





// Add the code below to your theme's functions.php file to add a confirm password field on the register form under My Accounts.

add_filter('woocommerce_registration_errors', 'sm_registration_errors_validation', 10, 3);

function sm_registration_errors_validation($reg_errors, $sanitized_user_login, $user_email)

{

  global $woocommerce;

  extract($_POST);



  if (strcmp($password, $password2) !== 0) {

    return new WP_Error('registration-error', __('Passwords do not match.', 'woocommerce'));

  }



  return $reg_errors;

}

add_filter('woocommerce_registration_errors', 'prefix_validate_name_fields', 10, 3);



function prefix_validate_name_fields($errors, $username, $email)

{

  if (isset($_POST['firstname']) && empty($_POST['firstname'])) {

    $errors->add('billing_first_name_error', __('<strong>Error</strong>: First name is required!', 'woocommerce'));

  }

  if (isset($_POST['lastname']) && empty($_POST['lastname'])) {

    $errors->add('billing_last_name_error', __('<strong>Error</strong>: Last name is required!.', 'woocommerce'));

  }

  return $errors;

}



// add_filter('woocommerce_product_tabs', 'sm_custom_product_tabs');

// function sm_custom_product_tabs($tabs)

// {





//   // 2 Adding new tabs and set the right order



//   //Attribute Description tab

//   $tabs['attrib_details_tab'] = array(

//     'title'     => __('Details', 'woocommerce'),

//     'priority'  => 100,

//     'callback'  => 'sm_attrib_details_tab_content'

//   );





//   return $tabs;

// }



// function sm_attrib_details_tab_content()

// {

//   echo 'Details content';

// }

add_action('woocommerce_product_thumbnails', 'woocommerce_show_product_thumbnails', 20);

function woocommerce_show_product_thumbnails()

{

  echo "<div class='custom_made_warning alert alert-info fade in alert-dismissible'>

  <p>CANCELLATION & RETURNS

  Every tailor made and custom made products is 100% individually customize according to customers preferences. Hence, we DO NOT accept returns for any reason except production error.</p></div>";

}









//Registering Navigation Menu

function register_menu()

{

  register_nav_menu('primary', __('Primary Menu'));

  register_nav_menu('secondary', __('Secondary Menu'));

}

add_action('init', 'register_menu');


register_sidebar(array(
    'name' => 'Footer-About',

    'id' => 'footer-about',

    'description' => '',

    'before_widget' => '',

    'after_widget' => '',

    'before_title' => '',

    'after_title' => '',

));

register_sidebar(array(
    'name' => 'Footer-About-Norwegian',

    'id' => 'footer-about-norwegian',

    'description' => '',

    'before_widget' => '',

    'after_widget' => '',

    'before_title' => '',

    'after_title' => '',

));





register_sidebar(array(

  'name' => 'Footer-Contact',

  'id' => 'footer-contact',

  'description' => '',

  'before_widget' => '',

  'after_widget' => '',

  'before_title' => '',

  'after_title' => '',

));


register_sidebar(array(

  'name' => 'Footer-Contact-Norwegian',

  'id' => 'footer-contact-norwegian',

  'description' => '',

  'before_widget' => '',

  'after_widget' => '',

  'before_title' => '',

  'after_title' => '',

));



register_sidebar(array(

  'name' => 'Footer-Address',

  'id' => 'footer-address',

  'description' => '',

  'before_widget' => '',

  'after_widget' => '',

  'before_title' => '',

  'after_title' => '',

));

register_sidebar(array(

    'name' => 'Footer-Address-Norwegian',

    'id' => 'footer-address-norwegian',

    'description' => '',

    'before_widget' => '',

    'after_widget' => '',

    'before_title' => '',

    'after_title' => '',

  ));



register_sidebar(array(

  'name' => 'Footer-Followus',

  'id' => 'footer-followus',

  'description' => '',

  'before_widget' => '',

  'after_widget' => '',

  'before_title' => '',

  'after_title' => '',

));

register_sidebar(array(

  'name' => 'Footer-Followus-Norwegian',

  'id' => 'footer-followus-norwegian',

  'description' => '',

  'before_widget' => '',

  'after_widget' => '',

  'before_title' => '',

  'after_title' => '',

));



register_sidebar(array(

  'name' => 'FOR YOUR GUIDANCE',

  'id' => 'for-your-guidance',

  'description' => '',

  'before_widget' => '',

  'after_widget' => '',

  'before_title' => '',

  'after_title' => '',

));



add_action('wp_footer', 'change_currency');

function change_currency()

{

  $vis_ip = getVisIPAddr();

  $ipdat = @json_decode(file_get_contents(

    "http://www.geoplugin.net/json.gp?ip=" . $vis_ip

  ));

  $country = $ipdat->geoplugin_countryCode;

  ?>

  <script type="text/javascript">

    jQuery(document).ready(function($) {

      var lang = document.getElementsByTagName('html')[0].getAttribute('lang');

      var currency;

      var country = '<?php echo $country; ?>';



      if ((lang == 'en') && (country == 'UK')) {

        currency = "GBP";

      } else if ((lang == 'it') || (lang == 'de')) {

        currency = "EUR";

      } else if ((lang == 'no') || (country == "SE") || (country == "FI") || (country == "DK")) {

        currency = "NOK";

      } else {

        currency = "NOK";

      }

      // jQuery('.woocommerce-currency-selector').val(currency).trigger('change'); 

      setCookie('woocommerce_multicurrency_forced_currency', currency, 365);

    })



    function setCookie(cname, cvalue, exdays) {

      var d = new Date();

      d.setTime(d.getTime() + (exdays * 24 * 60 * 60 * 1000));

      var expires = "expires=" + d.toUTCString();

      document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";

    }
	/* jQuery(window).load(function(){
		doGTranslate('en|no');
		var nturl = jQuery(".option .nturl").eq(-2).click();
		var img = jQuery(".option .nturl").eq(-2).find('img').attr('data-gt-lazy-src');
		jQuery('div.switcher div.selected a img').attr('src',img);
		
	}); */
  </script>

<?php

}







function getVisIpAddr()

{



  if (!empty($_SERVER['HTTP_CLIENT_IP'])) {

    return $_SERVER['HTTP_CLIENT_IP'];

  } else if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {

    return $_SERVER['HTTP_X_FORWARDED_FOR'];

  } else {

    return $_SERVER['REMOTE_ADDR'];

  }

}









/*add_action( 'woocommerce_before_shop_loop_item_title', 'add_on_hover_shop_loop_image' ) ; 



function add_on_hover_shop_loop_image() {



    $image_id = wc_get_product()->get_gallery_image_ids()[1] ; 



    if ( $image_id ) {



        echo wp_get_attachment_image( $image_id ) ;



    } else {  //assuming not all products have galleries set



        echo wp_get_attachment_image( wc_get_product()->get_image_id() ) ; 



    }



}*/



/*add_filter( 'woocommerce_get_availability', 'wcs_custom_get_availability', 1, 2);

function wcs_custom_get_availability( $availability, $_product ) {

     $free=get_field('fabricstock');

    // Change In Stock Text

    if ( $_product->is_in_stock() ) {

        $availability['availability'] = __('In stock ', 'woocommerce');

    }

    // Change Out of Stock Text

    if ( !$_product->is_in_stock() ) {



                   if( $product->backorders_allowed()  ){

        $availability['availability'] = __('Fabric available but it will take 7 days to deliever ', 'woocommerce');

      }

      else{

                $availability['availability'] = __('Out of stock ', 'woocommerce');



      }

    }

    return $availability;

}

*/

//If a product allows backorder, modify add to cart button

// Function that will check the stock status and display the corresponding additional text





/* backorder text on single product page */

 
// function so_42345940_backorder_message( $text, $product ){
//   $free=get_field('fabricstock');
//  if ( $product->managing_stock() && $product->is_on_backorder( 1 )) {
//      $text = get_field('fabric-text');
//  }
//  return $text;
// }
// add_filter( 'woocommerce_get_availability_text', 'so_42345940_backorder_message', 10, 2 );

 /**
  * Change Sold Out Text
  */
  function change_text_soldout ( $text, $product) { 
    if ( !$product->is_in_stock() ) {
      $check = get_field('fabric-text');
      if(!empty($check)){
        $text = '<p class="fabric">' . get_field('fabric-text'). '</p>';
      }
      else{
        $text = get_field('out_of_stock_text');
      }
         
    } 
    return $text; 
}
add_filter('woocommerce_get_availability_text', 'change_text_soldout', 10, 2 );


add_action( 'woocommerce_init', 'force_non_logged_user_wc_session' );

function force_non_logged_user_wc_session(){ 

    if( is_user_logged_in() || is_admin() )

       return;



    if ( isset(WC()->session) && ! WC()->session->has_session() ) 

       WC()->session->set_customer_session_cookie( true ); 

}

add_action('wp_ajax_wdm_add_user_custom_data_options', 'wdm_add_user_custom_data_options_callback');
add_action('wp_ajax_nopriv_wdm_add_user_custom_data_options', 'wdm_add_user_custom_data_options_callback');

function wdm_add_user_custom_data_options_callback()
{
      //Custom data - Sent Via AJAX post method
      $product_id = $_POST['id']; //This is product ID
      $user_custom_data_values =  $_POST['user_data']; //This is User custom value sent via AJAX
      session_start();
      $_SESSION['wdm_user_custom_data'] = $user_custom_data_values;
      die();
}

add_filter('woocommerce_add_cart_item_data','wdm_add_item_data',1,2);
 
if(!function_exists('wdm_add_item_data'))
{
    function wdm_add_item_data($cart_item_data,$product_id)
    {
        /*Here, We are adding item in WooCommerce session with, wdm_user_custom_data_value name*/
        global $woocommerce;
        session_start();    
        if (isset($_SESSION['wdm_user_custom_data'])) {
            $option = $_SESSION['wdm_user_custom_data'];       
            $new_value = array('wdm_user_custom_data_value' => $option);
        }
        if(empty($option))
            return $cart_item_data;
        else
        {    
            if(empty($cart_item_data))
                return $new_value;
            else
                return array_merge($cart_item_data,$new_value);
        }
        unset($_SESSION['wdm_user_custom_data']); 
        //Unset our custom session variable, as it is no longer needed.
    }
}
add_filter('woocommerce_get_cart_item_from_session', 'wdm_get_cart_items_from_session', 1, 3 );
if(!function_exists('wdm_get_cart_items_from_session'))
{
    function wdm_get_cart_items_from_session($item,$values,$key)
    {
        if (array_key_exists( 'wdm_user_custom_data_value', $values ) )
        {
        $item['wdm_user_custom_data_value'] = $values['wdm_user_custom_data_value'];
        }       
        return $item;
    }
}
add_action('wp_head','woocommerce_js');

function woocommerce_js()
{ // break out of php ?>
<script>
jQuery(document).ready(function($) {

 
 var checkFabric = '<?php echo get_field('fabric-text'); ?>';
  jQuery("#custume_pa_size").click(function(){
    if(checkFabric !== null){
$(".single_add_to_cart_button").removeClass("wc-variation-is-unavailable").addClass('postWatchVideo');


}
    });
 jQuery('.closep').click(function(){
	$(this).parents('.alert').fadeOut();
 })
});
</script>
<?php } // break back into php


add_shortcode('popuploginform', 'popuploginform');

function popuploginform(){


if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

do_action( 'woocommerce_before_customer_login_form' ); ?>



<div class="u-columns col2-set pd120 customer-popup" id="customer_login">
         <div class="col-md-12">
             <!-----------------------login----------------------->
             <div class="login-form" style="display: block;">

		<form class="woocommerce-form woocommerce-form-login login" method="post">

			<?php do_action( 'woocommerce_login_form_start' ); ?>

			<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
			
				<input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="username" id="username" autocomplete="username" placeholder="Username or email address"><?php // @codingStandardsIgnoreLine ?>
			</p>
			<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
			
				<input class="woocommerce-Input woocommerce-Input--text input-text" type="password" name="password" id="password" autocomplete="current-password" placeholder="Password" />
			</p>

			<?php do_action( 'woocommerce_login_form' ); ?>
              <div class="clearfix"></div>
<div class="register-btn pop-login-btn">
<p class="lost_password">
				<a href="<?php echo esc_url( wp_lostpassword_url() ); ?>"><?php esc_html_e( 'Lost your password?', 'woocommerce' ); ?></a>
			</p>
     <p class="flo-right">
				<button type="submit" class="woocommerce-button button woocommerce-form-login__submit" name="login" value="Log in"><font style="vertical-align: inherit;"><font style="vertical-align: inherit;">Logg Inn</font></font></button>
    </p>
			
            </div>
			<?php do_action( 'woocommerce_login_form_end' ); ?>

		</form>
	</div>
        
        </div>
        </div>


<?php do_action( 'woocommerce_after_customer_login_form' ); 


}
add_shortcode('popupregisterform', 'popupregisterform');

function popupregisterform(){


if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

do_action( 'woocommerce_before_customer_login_form' ); ?>



<div class="u-columns col2-set pd120 customer-popup" id="customer_login">
         <div class="col-md-12 ">
             <div class="create-forms">
          		<form method="post" class="woocommerce-form woocommerce-form-register register" <?php do_action( 'woocommerce_register_form_tag' ); ?> >

          			<?php do_action( 'woocommerce_register_form_start' ); ?>
                        
                        <?php if(isset($_GET['lang']) && $_GET['lang'] =='en'){
                                $companyName  = 'Company Name';
                                $firstName  = 'First Name';
                                $lastName  = 'Last Name';
                                $email_id  = 'Email';
                                $password  = 'Password';
                                $repeatPassword  = 'Repeat Password';
                                $Newsletter_tilte = 'Subscribe to newsletter';
                                $Register = 'Register';
                              }else{ 
                                $companyName  = 'selskapsnavn';
                                $firstName  = 'Fornavn';
                                $lastName  = 'Etternavn';
                                $email_id  = 'E-post';
                                $password  = 'Passord';
                                $repeatPassword  = 'Gjenta passord';
                                $Newsletter_tilte = 'Abonner på nyhetsbrev';
                                $Register = 'Registrere';
                              } ?>

                      <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
          		
          					<input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="companyname" id="" autocomplete="companyname" placeholder="<?php echo $companyName ?>"><?php // @codingStandardsIgnoreLine ?>
          				</p> 
                  <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
          		
          					<input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="firstname" id="" autocomplete="firstname" placeholder="*<?php echo $firstName ?>"><?php // @codingStandardsIgnoreLine ?>
          				</p>
                      
                      
                       <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
          		
          					<input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="lastname" id="" autocomplete="lastname" placeholder="*<?php echo $lastName ?>"><?php // @codingStandardsIgnoreLine ?>
          				</p>
                      
                        <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
          		
          					<input type="email" class="woocommerce-Input woocommerce-Input--text input-text" name="email" id="" autocomplete="email" placeholder="*<?php echo $email_id ?>"><?php // @codingStandardsIgnoreLine ?>
          				</p>
                      
                      <div class="clearfix"></div>
                      
                        <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
          		<input type="password" class="woocommerce-Input woocommerce-Input--text input-text" name="password" id="" autocomplete="password" placeholder="*<?php echo $password ?>"><?php // @codingStandardsIgnoreLine ?>
          				</p>
                      
                        <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
          		<input type="password" class="woocommerce-Input woocommerce-Input--text input-text" name="password2" id="" autocomplete="rpassword" placeholder="*<?php echo $repeatPassword ?>"><?php // @codingStandardsIgnoreLine ?>
          				</p>
                      <div class="clearfix"></div>
                      <div class="register-btn">
                      <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide subscribe">
          		        
          					<input type="checkbox" class="" name="password" id="newsletter" ><?php // @codingStandardsIgnoreLine ?>
                             <label for="newsletter"><?php echo $Newsletter_tilte;?> </label>
          				</p>
                      
                      
                      

          			<?php do_action( 'woocommerce_register_form' ); ?>

          			<p class="woocommerce-form-row form-row flo-right">
          				<?php wp_nonce_field( 'woocommerce-register', 'woocommerce-register-nonce' ); ?>
          				<button type="submit" class="woocommerce-Button woocommerce-button button woocommerce-form-register__submit" name="register" value="Create account">Register</button>
          			</p>
                      </div>
          			<?php do_action( 'woocommerce_register_form_end' ); ?>

          		</form>
          				
             </div>
             
             
        </div>
        </div>


<?php do_action( 'woocommerce_after_customer_login_form' ); 


}

/**
 * @snippet       WooCommerce User Login Shortcode
 * @how-to        Get CustomizeWoo.com FREE
 * @author        Rodolfo Melogli
 * @compatible    WooCommerce 4.0
 * @donate $9     https://businessbloomer.com/bloomer-armada/
 */
  
add_shortcode( 'wc_login_form_bbloomer', 'bbloomer_separate_login_form' );
  
function bbloomer_separate_login_form() {
   if ( is_admin() ) return;
   if ( is_user_logged_in() ) return; 
   ob_start();
   woocommerce_login_form( array( 'redirect' => 'https://custom.url' ) );
   return ob_get_clean();
}
/**
 * @snippet       WooCommerce User Registration Shortcode
 * @how-to        Get CustomizeWoo.com FREE
 * @author        Rodolfo Melogli
 * @compatible    WooCommerce 4.0
 * @donate $9     https://businessbloomer.com/bloomer-armada/
 */
   
add_shortcode( 'wc_reg_form_bbloomer', 'bbloomer_separate_registration_form' );
    
function bbloomer_separate_registration_form() {
   if ( is_admin() ) return;
   if ( is_user_logged_in() ) return;
   ob_start();
 
  ?>
<?php
 
   do_action( 'woocommerce_before_customer_login_form' );
 
   ?>
      <form method="post" class="woocommerce-form woocommerce-form-register register" <?php do_action( 'woocommerce_register_form_tag' ); ?> >
 
         <?php do_action( 'woocommerce_register_form_start' ); ?>
 
         <?php if ( 'no' === get_option( 'woocommerce_registration_generate_username' ) ) : ?>
 
            <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
               <label for="reg_username"><?php esc_html_e( 'Username', 'woocommerce' ); ?> <span class="required">*</span></label>
               <input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="username" id="reg_username" autocomplete="username" value="<?php echo ( ! empty( $_POST['username'] ) ) ? esc_attr( wp_unslash( $_POST['username'] ) ) : ''; ?>" /><?php // @codingStandardsIgnoreLine ?>
            </p>
 
         <?php endif; ?>
 
         <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
            <label for="reg_email"><?php esc_html_e( 'Email address', 'woocommerce' ); ?> <span class="required">*</span></label>
            <input type="email" class="woocommerce-Input woocommerce-Input--text input-text" name="email" id="reg_email" autocomplete="email" value="<?php echo ( ! empty( $_POST['email'] ) ) ? esc_attr( wp_unslash( $_POST['email'] ) ) : ''; ?>" /><?php // @codingStandardsIgnoreLine ?>
         </p>
 
         <?php if ( 'no' === get_option( 'woocommerce_registration_generate_password' ) ) : ?>
 
            <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
               <label for="reg_password"><?php esc_html_e( 'Password', 'woocommerce' ); ?> <span class="required">*</span></label>
               <input type="password" class="woocommerce-Input woocommerce-Input--text input-text" name="password" id="reg_password" autocomplete="new-password" />
            </p>
 
         <?php else : ?>
 
            <p><?php esc_html_e( 'A password will be sent to your email address.', 'woocommerce' ); ?></p>
 
         <?php endif; ?>
 
         <?php do_action( 'woocommerce_register_form' ); ?>
 
         <p class="woocommerce-FormRow form-row">
            <?php wp_nonce_field( 'woocommerce-register', 'woocommerce-register-nonce' ); ?>
            <button type="submit" class="woocommerce-Button woocommerce-button button woocommerce-form-register__submit" name="register" value="<?php esc_attr_e( 'Register', 'woocommerce' ); ?>"><?php esc_html_e( 'Register', 'woocommerce' ); ?></button>
         </p>
 
         <?php do_action( 'woocommerce_register_form_end' ); ?>
 
      </form>
 
   <?php
     
   return ob_get_clean();
}



/**
* Redirect users to custom URL based on their role after login
*
* @param string $redirect
* @param object $user
* @return string
*/
function wc_custom_user_redirect( $redirect, $user ) {
  // Get the first of all the roles assigned to the user
  $role = $user->roles[0];
  $dashboard = admin_url();
  $myaccount = 'https://wordpress-843741-3123615.cloudwaysapps.com/retailer-order/';
  if( $role == 'administrator' ) {
    //Redirect administrators to the dashboard
    $redirect = $dashboard;
  } elseif ( $role == 'shop-manager' ) {
    //Redirect shop managers to the dashboard
    $redirect = $myaccount;
  } elseif ( $role == 'editor' ) {
    //Redirect editors to the dashboard
    $redirect = $myaccount;
  } elseif ( $role == 'author' ) {
    //Redirect authors to the dashboard
    $redirect = $myaccount;
  } elseif ( $role == 'customer' || $role == 'subscriber' ) {
    //Redirect customers and subscribers to the "My Account" page
    $redirect = $myaccount;
  } else {
    //Redirect any other role to the previous visited page or, if not available, to the home
    $redirect = wp_get_referer() ? wp_get_referer() : home_url();
  }
  return $redirect;
}
add_filter( 'woocommerce_login_redirect', 'wc_custom_user_redirect', 10, 2 );

/*if(!function_exists(pr)){
  function pr($par1, $par2=false){
    echo "<pre>";
    print_r($par1);
    echo "</pre>";
    if($par2 !=false)
      die;
  }
}*/

/**add custom meta fields for product collar and Cuff**/
add_action( 'woocommerce_before_add_to_cart_button', 'add_fields_before_add_to_cart' );
function add_fields_before_add_to_cart( ) { ?>
  <input type = "hidden" name = "collar_name" id="collor_name" placeholder = "" value="">
  <input type = "hidden" name = "cuff_name" id="cuff_name" placeholder = "" value="">
  <input type = "hidden" name="extra_days" id ="extra_days" placeholder = "" value="" />
  <script>
    jQuery("#add,#sub").each(function(){
      jQuery(this).click(function(){
        jQuery('.woocommerce-variation-add-to-cart input').each(function(){
          var custom_shirt =  jQuery(this).val();
          if(custom_shirt != 0 || custom_shirt != 'NaN'){
            jQuery("#extra_days").val('3');
          }
        });
      });
    });
  </script>
  <?php
}

/**
 * Add data to cart item
 */
add_filter( 'woocommerce_add_cart_item_data', 'add_cart_item_data', 25, 2 );
function add_cart_item_data( $cart_item_meta, $product_id ) {
   $custom_data  = array() ;
  if ( isset( $_POST ['collar_name'] ) ) {
    $custom_data [ 'collar_name' ]    = isset( $_POST ['collar_name'] ) ?  sanitize_text_field ( $_POST ['collar_name'] ) : "" ;
    $cart_item_meta ['custom_data']     = $custom_data ;
  }
  if ( isset( $_POST ['cuff_name'] ) ) {
   
    $custom_data [ 'cuff_name' ] = isset( $_POST ['cuff_name'] ) ? sanitize_text_field ( $_POST ['cuff_name'] ): "" ;
    $cart_item_meta ['custom_data']     = $custom_data ;
  }
  if ( isset( $_POST ['extra_days'] ) ) {
    $custom_data [ 'extra_days' ]    = isset( $_POST ['extra_days'] ) ?  sanitize_text_field ( $_POST ['extra_days'] ) : "" ;
    /*$cart_item_meta ['custom_data']     = $custom_data ;*/
  }
  return $cart_item_meta;
}



/**
 * Display custom data on cart and checkout page.
 */
add_filter( 'woocommerce_get_item_data', 'get_item_data' , 25, 2 );
function get_item_data( $other_data, $cart_item ) {
  if ( isset( $cart_item [ 'custom_data' ] ) ) {
    $item_data = $cart_item['data'];
    $attributes = $item_data->get_attributes();
    $pa_size =  $attributes['pa_size'];
    $custom_data  = $cart_item[ 'custom_data' ];
  
    $other_data[1] = array( 'key' => 'Size Men','value'  => $pa_size);
    if(!empty($custom_data['collar_name']))
    $other_data[] = array( 'name' => 'Collar Name','display' => $custom_data['collar_name'] );
    if(!empty($custom_data['cuff_name']))
    $other_data[] = array( 'name' => 'Cuff Name','display' => $custom_data['cuff_name'] );
    /*if(!empty($custom_data['extra_days']))
      $other_data[] = array( 'name' => 'Extra Days','display' => $custom_data['extra_days'] );*/
    }
    return $other_data;
}


// add the filter 
add_filter( 'woocommerce_add_cart_item_data', 'filter_woocommerce_add_cart_item_data', 10, 3 ); 
function filter_woocommerce_add_cart_item_data( $cart_item_data, $product_id, $variation_id ) { 
    // make filter magic happen here... 
    $allProId = [];
    if( isset( $_POST ['extra_days'] )  && !empty($_POST ['extra_days'])){
      $allProId[] = apply_filters( 'wpml_object_id', $product_id, 'product', FALSE, 'no' );
      $allProId[] = apply_filters( 'wpml_object_id', $product_id, 'product', FALSE, 'en' );
      foreach($allProId as $productID){
        $extraDays = get_field('extra_working_days', $productID);
        if($extraDays > 0 ){
          $cart_item_data['delivery_days'] = get_field('extra_working_days', $productID);
          break;
        }
      }
    }
    return $cart_item_data; 
}; 
         
/**
 * Add order item meta.
 */

add_action( 'woocommerce_add_order_item_meta', 'add_order_item_meta' , 10, 2);
function add_order_item_meta ( $item_id, $values ) {
  if ( isset( $values [ 'custom_data' ] ) ) {
    $custom_data  = $values [ 'custom_data' ];
    wc_add_order_item_meta( $item_id, 'Collar Name', $custom_data['collar_name'] );
    wc_add_order_item_meta( $item_id, 'Cuff Name', $custom_data['cuff_name'] );
    //wc_add_order_item_meta( $item_id, 'Extra Days', $custom_data['extra_days'] );
  }
}

add_filter( 'default_checkout_billing_country', 'change_default_checkout_country', 10, 1 );

function change_default_checkout_country( $country ) {
    // If the user already exists, don't override country
    if ( WC()->customer->get_is_paying_customer() ) {
        return $country;
    }
    return 'NO';
}

function roundDown($decimal, $precision)
{
    $sign = $decimal > 0 ? 1 : -1;
    $base = pow(10, $precision);
    return floor(abs($decimal) * $base) / $base * $sign;
}
/** Manage Stock **/
add_action( 'add_meta_boxes', 'himweb_register_meta_boxes' );
function himweb_register_meta_boxes() {
  add_meta_box( 'himweb-1', __( 'Manage Stock', 'himweb' ), 'himweb_display_callback', 'product' );
}
function himweb_display_callback( $post ){
  wp_nonce_field(basename(__FILE__), "managestock-nonce");
  $product_id = $post->ID;  
  $overAllStock = 0;
  $perPiece = 0;

  $tempAllStock_main = get_post_meta($product_id , 'over_all_stock', true);

  if($tempAllStock_main!='')
  {
    $tempAllStock = get_post_meta($product_id , 'over_all_stock', true);;
  }
  else
  {
    $tempAllStock='0';
  }

   $tempPiece_main = get_post_meta($product_id , 'perpiece', true);
 if($tempPiece_main!='')
  {
   $tempPiece = get_post_meta($product_id , 'perpiece', true);;
  }
  else
  {
    $tempPiece='0';
  }


  if($tempPiece){
    $perPiece = roundDown($tempPiece,2);
  }
  if($tempAllStock){
    $overAllStock = roundDown($tempAllStock,2);
  }
  if($perPiece!="" && $overAllStock!="")
  {
  
}
else
{
   $pendingStockFebric = 0;
    $pendingStock = 0;
}
 
  
  // Examples
  echo "<table class='form-table'><tbody>
      <tr>
        <th scope='row'><label>Manage Overall Stock In Meter</label></th>
        <td>";
        
              echo "<input type='text' name='stock_manage' value='".$pendingStockFebric."'> Meters";
            
     echo "</td>
      </tr>
      <tr>
        <th scope='row'><label>Febric Per Piece In Meter</label></th>
        <td>";
         
              echo "<input type='text' name='per_piece' value='".$perPiece."'> Meters";
           
     echo "</td>
      </tr>";
     
        echo"<tr>
            <th scope='row'><label>Available Febric Stock</label></th>
            <td><span>".$pendingStockFebric." Meters</span> </td>
          </tr>";
      
   
      echo"<tr>
          <th scope='row'><label>Available Shirts Stock</label></th>
          <td><span>".roundDown($pendingStock,0)."</span> </td>
        </tr>";
    
  echo"</tbody></table>";
}

add_action("save_post", "saveCustomMeta", 10, 3);
function saveCustomMeta($post_id, $post, $update){
    if (!isset($_POST["managestock-nonce"]) || !wp_verify_nonce($_POST["managestock-nonce"], basename(__FILE__)))
      return $post_id;
    if(!current_user_can("edit_post", $post_id))
        return $post_id;
    if(defined("DOING_AUTOSAVE") && DOING_AUTOSAVE)
        return $post_id;
    $stock_manage = "";
    $allProId = [];
    if(isset($_POST["stock_manage"]) && !empty($_POST["stock_manage"])){
      $stock_manage = $_POST["stock_manage"];
      $per_piece = $_POST["per_piece"];
      $pendingStock = $stock_manage / $per_piece;
      $stock_manage = roundDown($pendingStock,0);
      $product_id = $post_id;
      $allProId[] = apply_filters( 'wpml_object_id', $product_id, 'product', FALSE, 'no' );
      $allProId[] = apply_filters( 'wpml_object_id', $product_id, 'product', FALSE, 'en' );

      foreach($allProId as $proId){
        $product = new WC_Product($proId);
        update_post_meta($proId , 'perpiece',  $per_piece);
        update_post_meta($proId , 'over_all_stock',  $stock_manage);
        update_post_meta($proId , '_stock',  $stock_manage);
        update_post_meta($proId, '_stock_status',  'instock');
          $products = wc_get_product( $proId );
         
          if($products->is_type( 'variable' ) ) {
            $variations = $products->get_available_variations();
            if($variations){
              $variations_id = wp_list_pluck( $variations, 'variation_id' ); 
              foreach($variations_id as $variant_id){
                update_post_meta($variant_id, '_stock',  $stock_manage);
                update_post_meta($variant_id, '_stock_status',  'instock');
                
              }
            }
          }else{
            update_post_meta($proId, '_stock',  $stock_manage);
            update_post_meta($proId, '_stock_status',  'instock');
          }
      }
    }
}

/** check product id**/
add_action('woocommerce_thankyou', 'enroll_student', 10, 1);
function enroll_student( $order_id ) {
    if ( ! $order_id )
        return;

    // Allow code execution only once 
    if( ! get_post_meta( $order_id, '_thankyou_action_done', true ) ) {

        // Get an instance of the WC_Order object
        $order = wc_get_order( $order_id );

        // Get the order key
        $order_key = $order->get_order_key();

        // Get the order number
        $order_key = $order->get_order_number();

        if($order->is_paid())
            $paid = __('yes');
        else
            $paid = __('no');
        // Loop through order items
        $allProId = [];

         if ( get_option( 'order_id_'.$order_id ) != $order_id ) {
              add_option('order_id'.$order_id , $order_id);

            foreach ( $order->get_items() as $item_id => $item ) {
              // Get the product object
              $product = $item->get_product();
              // Get the product Id
              $product_id = $product->get_id();
              $allProId[] = apply_filters( 'wpml_object_id', $product_id, 'product', FALSE, 'no' );
              $allProId[] = apply_filters( 'wpml_object_id', $product_id, 'product', FALSE, 'en' );
              foreach($allProId as $proId){
                $item_quantity  = $item->get_quantity();
                $product = wc_get_product($proId);
                $productParentID = $product->get_parent_id();
               
                if($productParentID != 0){

                  $products = wc_get_product( $productParentID );
                  
                  if ( $products->is_type( 'variable' ) ) {
                    //$product = wc_get_product($productParentID);
                    $variations = $products->get_available_variations();
                    $variations_id = wp_list_pluck( $variations, 'variation_id' ); 

                    $currentStock = '';
                    $per_piece = '';
                    $tempStock  = get_post_meta($productParentID ,'over_all_stock',true);
                    if($tempStock){
                      $currentStock = $tempStock;
                    }

                    $tempPiece = get_post_meta($productParentID , 'perpiece', true);
                    if($tempPiece){
                       $per_piece =  $tempPiece;
                    }

                     $pendingStock = $currentStock - $item_quantity;
                     $currentStock = $pendingStock / $per_piece;
                      update_post_meta($variant_id, '_stock',  $pendingStock);
                      update_post_meta($productParentID , 'over_all_stock',  $pendingStock);
                      if($pendingStock > $per_piece){
                          update_post_meta($productParentID, '_stock_status',  'instock');
                      }else{
                          update_post_meta($productParentID, '_stock_status',  'outofstock');
                      }
                      foreach($variations_id as $variant_id){
                        update_post_meta($variant_id, '_stock',  $pendingStock);
                        if($pendingStock > $per_piece){
                          update_post_meta($variant_id, '_stock_status',  'instock');
                        }else{
                          update_post_meta($variant_id, '_stock_status',  'outofstock');
                        }
                      
                    }
                  }
                }else{
                    $products = wc_get_product( $productParentID );
                    if (! $products->is_type( 'variable' ) ) {
                      $product = wc_get_product($proId);
                      $variations = $products->get_available_variations();
                      $variations_id = wp_list_pluck( $variations, 'variation_id' ); 
                      $currentStock  = get_post_meta($proId ,'over_all_stock',true);
                      if($currentStock){
                        $pendingStock = $currentStock - $item_quantity;
                        $currentStock = $pendingStock / $per_piece;
                        update_post_meta($variant_id, '_stock',  $pendingStock);
                        update_post_meta($proId , 'over_all_stock',  $pendingStock);
                        if($pendingStock > $per_piece){
                           update_post_meta($proId, '_stock_status',  'instock');
                        }else{
                           update_post_meta($proId, '_stock_status',  'outofstock');
                        }
                        foreach($variations_id as $variant_id){
                          update_post_meta($variant_id, '_stock',  $pendingStock);
                          if($pendingStock > $per_piece){
                            update_post_meta($variant_id, '_stock_status',  'instock');
                          }else{
                            update_post_meta($variant_id, '_stock_status',  'outofstock');
                          }
                        }
                      }
                    }
                  }
              } 
            }
        }

        // Output some data
        //echo '<p>Order ID: '. $order_id . ' — Order Status: ' . $order->get_status() . ' — Order is paid: ' . $paid . '</p>';

        // Flag the action as done (to avoid repetitions on reload for example)
    /*    $order->update_meta_data( '_thankyou_action_done', true );
        $order->save();*/
    }
}


add_filter( 'woocommerce_currencies', 'add_my_currency' );

function add_my_currency( $currencies ) {
     $currencies['NOK'] = __( 'Currency name', 'woocommerce' );
     return $currencies;
}

add_filter('woocommerce_currency_symbol', 'add_my_currency_symbol', 10, 2);

function add_my_currency_symbol( $currency_symbol, $currency ) {
     switch( $currency ) {
          case 'NOK': $currency_symbol = 'NOK '; break;
     }
     return $currency_symbol;
}
add_filter( 'auto_update_plugin', '__return_false' );
add_filter( 'auto_update_theme', '__return_false' );



function custom_badges() {
	if(!empty(get_field('product_type',false)))
	{
	 global $product;
$product_id = $product->get_id();	
		$str = get_field('product_type',false);
// Get the product object
$product = wc_get_product($product_id);
$total_stock = "";
// Check if the product object exists and is a product type
if ($str=="TILGJENGELIG") {
    // Get the stock quantity
    $stock_quantity = $product->get_stock_quantity();

    // Check if the product is in stock
    if ($stock_quantity > 0) {
        $total_stock = $stock_quantity;
    } else {
        $total_stock = 0;
    }
}

  echo '<span class="badges">'.ucfirst(mb_strtolower(strip_tags($str))).' '.$total_stock.'</span>';
 

	}
   
    }
add_action( 'woocommerce_before_shop_loop_item_title', 'custom_badges', 5 );

@ini_set( 'upload_max_size' , '764M' );
@ini_set( 'post_max_size', '764M');
@ini_set( 'max_execution_time', '300' );


function remove_query_string( $src ) {
    if ( strpos( $src, '?ver=' ) ) {
        $src = remove_query_arg( 'ver', $src );
    }
    return $src.'?ver=7886711231';
}
add_filter( 'script_loader_src', 'remove_query_string', 10, 1 );
// add_filter( 'style_loader_src', 'remove_query_string', 10, 1 );




function decrement_product_quantity_in_crm($order_id) {

    // Get the order object
    $order = wc_get_order( $order_id );
    
    // Loop through the order items
    foreach ( $order->get_items() as $item_id => $item ) {
        
        // Get the product SKU
        $product_sku = $item->get_product()->get_sku();
        
        // Get the quantity sold
        $quantity_sold = $item->get_quantity();
        
        // Decrement the quantity in your CRM based on the product SKU
        $crm_endpoint = 'http://admin.montenapoleone.no/curl.php';
        $crm_data = array(
            'uid' => $product_sku,
            'decrement_by' => $quantity_sold,
        );
        $crm_secret_key = 'SALMAN12345678900987654321'; // Replace with your webhook secret key
        $curl = curl_init();
        curl_setopt_array( $curl, array(
            CURLOPT_URL => $crm_endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query( $crm_data ),
            CURLOPT_HTTPHEADER => array(
                "X-Webhook-Secret-Key: $crm_secret_key" // Set the webhook secret key header
            )
        ) );
        $response = curl_exec( $curl );
        curl_close( $curl );
    }
}

add_action( 'woocommerce_thankyou', 'decrement_product_quantity_in_crm', 10, 1 );
add_filter( 'the_author', '__return_empty_string' );

add_filter( 'woocommerce_product_single_add_to_cart_text', 'change_checkout_text' );
function change_checkout_text( $text ) {
    $new_text = 'Go to Payment';
    return $new_text;
}

function add_this_script_footer() { 
?>


<!--*----------------Update cart on qty change on cart page-------------------*/-->

<?php

if (is_cart()) {

?>

   <script type="text/javascript">

      jQuery('div.woocommerce').on('change', 'input.qty', function() {

         jQuery("[name='update_cart']").trigger("click");

      });

   </script>

<?php

}

?>

<?php 

if(is_user_logged_in()) {

  $user = wp_get_current_user();

  $user_id = $user->ID;

 ?>



  <script type="text/javascript">

    var popup_user_id = '<?php echo $user_id ?>';

    var popup_user_cookie = 'wc_face_mask_user_'+popup_user_id;

    console.log(popup_user_cookie);

        jQuery(document).ready(function() {

          jQuery(".popupp-close").on("click", function () {

             jQuery(".main-popup").fadeOut();

             setCookie(popup_user_cookie,popup_user_id,20*365);

          });



          jQuery(".main-popup .popp-btn").on("click", function () {

              setCookie(popup_user_cookie,popup_user_id,20*365);

          });

        

          jQuery(document).click(function () {

            jQuery(".main-popup").fadeOut();



            var get_face_mask = getCookie(popup_user_cookie);

            if(typeof get_face_mask === 'undefined' || get_face_mask === '' || get_face_mask === null)

            {

              console.log('set user cookie');  

               setCookie(popup_user_cookie,popup_user_id,20*365);

            }

          });

      });



    jQuery(window).load(function() { 

      var get_face_mask = getCookie(popup_user_cookie);

      console.log(get_face_mask);  

      if(typeof get_face_mask === 'undefined' || get_face_mask === '' || get_face_mask === null)

      {

        jQuery(".main-popup").fadeIn();

      }

    });

  </script>



<?php }else{ ?>



  <script type="text/javascript">

      jQuery(document).ready(function() {

         jQuery(".popupp-close").on("click", function () {

             jQuery(".main-popup").fadeOut();

             setCookie('wc_face_mask_popup','100',20*365);

          });



          jQuery(".main-popup .popp-btn").on("click", function () {

              setCookie('wc_face_mask_popup','100',20*365);

          });

        

          jQuery(document).click(function () {

            jQuery(".main-popup").fadeOut();



            var get_face_mask = getCookie('wc_face_mask_popup');

            if(typeof get_face_mask === 'undefined' || get_face_mask === '' || get_face_mask === null)

            {

               setCookie('wc_face_mask_popup','100',20*365);

            }

          });

      });



    jQuery(window).load(function() { 

      var get_face_mask = getCookie('wc_face_mask_popup');

      console.log(get_face_mask);  

      if(typeof get_face_mask === 'undefined' || get_face_mask === '' || get_face_mask === null)

      {

        jQuery(".main-popup").fadeIn();

      }

    });

  </script>



<?php } ?>



<script type="text/javascript">

   jQuery(document).ready(function() {
    jQuery(".aws-search-form").append('<div class="aws-search-btn aws-form-btn"><span class="aws-search-btn_icon"><svg focusable="false" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24px"><path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"></path></svg></span></div>');
      jQuery(".commentBox").hide();
         jQuery('.tabss-option button').each(function(){
            jQuery(this).click(function(){
              jQuery(".commentBox").show();
            });
         });
      /*jQuery('.woocommerce-variation-add-to-cart input:hidden').each(function(){
         if(jQuery(this).val() != 0 || jQuery(this).val() != 'NaN' || jQuery(this).val() != 'cm'){
           if(jQuery(this).find('#main_arm_hole').val() != 0 || jQuery(this).find('#main_arm_hole').val() != 0){
            jQuery(".commentBox").show();
           }
         }else{
            jQuery(".commentBox").hide();
         }
      });*/
      jQuery("a.custommade_option_for_shirts").on("click", function() {
         jQuery(".commentBox").show();
      });
      jQuery("a.standard_option_for_shirts").on("click", function() {
         jQuery(".commentBox").hide();
      }); 

   });

 

    function setCookie(c_name,value,exdays) {

        var exdate=new Date();

        exdate.setDate(exdate.getDate() + exdays);

        var c_value=escape(value) + ((exdays==null) ? "" : ""); 

        document.cookie=c_name + "=" + c_value;

    }



    function getCookie(c_name) {

        var i,x,y,ARRcookies=document.cookie.split(";");

        for (i=0;i<ARRcookies.length;i++) {

            x=ARRcookies[i].substr(0,ARRcookies[i].indexOf("="));

            y=ARRcookies[i].substr(ARRcookies[i].indexOf("=")+1);

            x=x.replace(/^s+|s+$/g,"");

            if (x==c_name) {

                return unescape(y);

            }

        }

    }

    jQuery(document).click(function () {
    jQuery(".search-box").slideUp();
  });
  jQuery(".search-box").click(function (e) {
    e.stopPropagation();
  });
  jQuery(".search a, .help-popup a, .account-popup a, .cart-bag").click(function (
    e
  ) {
    e.stopPropagation();
  });

</script>
<script type="text/javascript">
      jQuery(function(){
         var get_UrlParams = window.location.href;
         if (window.location.href.indexOf("lang=") == -1) {
            jQuery('.newsletter-field label').text('Nyhetsbrev');
            jQuery('.newsletter-field .submit_cha').text('Sende');
            jQuery('.otgs-development-site-front-end').html('Dette nettstedet er registrert på <a href="http://wpml.org/">wpml.org</a> som et utviklingssted');
            if(get_UrlParams == "<?php echo site_url()?>/cart/"){
               var label = jQuery('#shipping_method li label').html();
               console.log(label);
               if(label == 'Free shipping'){
                   jQuery('#shipping_method li label').text('gratis frakt');
               }
            }
         }
      
         jQuery('.menu-item-has-children').each(function(){
            jQuery(this).click(function(){
               jQuery(this).toggleClass('active');
            });
         });
      });

     
   /*setTimeout(function(){
      if(localStorage.getItem("ChangeLanguageFirstTime") === null) {
         doGTranslate('en|no');
         localStorage.setItem("ChangeLanguageFirstTime", true);
      }
   }, 3000);*/


   jQuery(function ($) {
       $.get("https://ipinfo.io", function (response) {
//            console.log("IP", response.ip);
//            console.log("Location: " + response.city + ", " + response.country);
        if(response.country == 'NO'){}
          jQuery("#details").html(JSON.stringify(response, null, 4));
       }, "jsonp");
    });



  jQuery(document).ready(function(){
    jQuery('.popup-section ul li input').change(function () {
         jQuery('.popup-section ul li input').prop('checked', false);
         jQuery(".hullcheck input").prop('checked', false);
         var txt = jQuery(this).val(); 
         jQuery(".popup-section ul li input[value='" + txt + "']").prop('checked', true);
         if ($(this).prop('checked')) {
            jQuery(".hullcheck input[value='" + txt + "']").prop('checked', true);
         }else{
          var txt = jQuery(this).val();
            jQuery(".hullcheck input[value='" + txt + "']").prop('checked', false);
         }
      });
      
      jQuery("#cuff-design input").on('click', function() {
        var box = jQuery(this);
        if (box.is(":checked")) {
          jQuery("#cuff-design input").prop("checked", false);
          box.prop("checked", true);
            var box_class= box.attr('data_value');
            var box_val= box.val();
            jQuery('.'+box_class).val(box_val);
            jQuery('#cuff_name').val(box_val);
            jQuery(".commentBox").show();
        } else {
          box.prop("checked", false);

        }
      });
      jQuery("#collor-design input").on('click', function() {
        var box = jQuery(this);
        if (box.is(":checked")) {
          jQuery("#collor-design input").prop("checked", false);
          box.prop("checked", true);
            var box_class= box.attr('data_value');
            var box_val= box.val();
            console.log(box_val);
            jQuery('.'+box_class).val(box_val);
            jQuery('#collor_name').val(box_val);
            jQuery(".commentBox").show();
        } else {
          box.prop("checked", false);

        }
      });

   });
  

</script>
  <script>  
  jQuery(document).ready(function(){   
jQuery(".shop-right-contenntt #standard #custume_pa_size li a").each(function(){
    var getXtitle = jQuery(this).attr('title');
     jQuery(this).html(getXtitle);
        });
  }); 
jQuery(document).ready(function(){   
  jQuery('body').on('click', '.standard_option_for_shirts', function(e) {
    jQuery(".shop-right-contenntt #standard #custume_pa_size li a").each(function(){
    var getXtitle = jQuery(this).attr('title');
     jQuery(this).html(getXtitle);
        });
  }); 
  }); 
jQuery(document).ready(function(){   
  jQuery('body').on('click', '.shop-right-contenntt .size_box_standard .contemporary', function(e) {
    jQuery(".shop-right-contenntt #standard #custume_pa_size li a").each(function(){
    var getXtitle = jQuery(this).attr('title');
     jQuery(this).html(getXtitle);
        });
  }); 
  }); 
jQuery(document).ready(function(){
 jQuery(".shop-right-contenntt #custommade #custume_pa_size li a").each(function(){
    var getXtitle = jQuery(this).attr('title');
     jQuery(this).html(getXtitle);
        });
  });


jQuery(document).ready(function(){
 jQuery('body').on('click', '.custommade_option_for_shirts', function(e) {
    jQuery(".shop-right-contenntt #custommade #custume_pa_size li a").each(function(){
    var getXtitle = jQuery(this).attr('title');
     jQuery(this).html(getXtitle);
        });
  });
 });

jQuery(document).ready(function(){
  jQuery('body').on('click', '.shop-right-contenntt .size_box_custommade .contemporary', function(e) {
    jQuery(".shop-right-contenntt #custommade #custume_pa_size li a").each(function(){
    var getXtitle = jQuery(this).attr('title');
     jQuery(this).html(getXtitle);
        });
  }); 

 

/*jQuery('body').on('click', '.shop-right-contenntt .size_box_custommade .slim', function(e) {
    jQuery(".shop-right-contenntt #custommade #custume_pa_size li a").each(function(){
    var getXtitle = jQuery(this).attr('title');
     jQuery(this).html(getXtitle);
        });
  }); 


  jQuery('body').on('click', '.shop-right-contenntt .size_box_standard .slim', function(e) {
    jQuery(".shop-right-contenntt #standard #custume_pa_size li a").each(function(){
    var getXtitle = jQuery(this).attr('title');
     jQuery(this).html(getXtitle);
        });
  });*/ 

  jQuery(".cls-shirts").click(function(){
     // removing active class from tab
  jQuery(".tabs").addClass("cls-intro");   // hiding open tab
    //  adding active class to clicked tab

  });
   jQuery(".cls-fabric").click(function(){
     // removing active class from tab
  jQuery(".tabs").removeClass("cls-intro");   // hiding open tab
    //  adding active class to clicked tab

  });

});   
</script>



<script type="text/javascript">
jQuery(document).ready(function(){
  jQuery(".tabs>div:not(:first)").hide();

  jQuery(".tabs-list li a").click(function(e){
     e.preventDefault();
  });

  jQuery(".tabs-list li").click(function(){
     var tabid = jQuery(this).find("a").attr("href");

       

     jQuery(".tabs-list li,.tabs div.tab").removeClass("active");   // removing active class from tab
  jQuery(".tab").hide();   // hiding open tab
     jQuery(tabid).show();    // show tab
     jQuery(this).addClass("active"); //  adding active class to clicked tab

  });
});





</script>


<script>
 
jQuery(document).ready(function(){
 
  jQuery(".hamburger #nav-icon").click(function(){
 
    jQuery("body").addClass("addclsfix");
 
  });
  
  jQuery(".mobile-search-menu .close-menu").click(function(){
 
    jQuery("body").removeClass("addclsfix");
 
  });
  
  jQuery(".contents").find('li').click(function(){
    jQuery(this).parents('.contents').hide()
  })
    jQuery(".contents").find('input').change(function(){
    if(jQuery(this).is(':checked'))
      {
    jQuery(this).parents('.contents').hide()
      }
  })

});
 
jQuery(document).ready(function() {
  jQuery('.down_arrow_open').click(function(event){
    event.preventDefault();
    if (jQuery(this).text() === 'Vis flere alternativer') {
      jQuery(this).text('Skjul');
  jQuery('.product_cutomization').addClass('active');
    } else {
      jQuery(this).text('Vis flere alternativer');
    jQuery('.product_cutomization').removeClass('active');
    }
  });
jQuery('a').on('click', function(event) {
  if (this.href.startsWith('#')) {
    event.preventDefault();
  }
});
});


	
	document.addEventListener('DOMContentLoaded', function() {
    // Define the links and their target URLs
    var links = [
        { href: 'https://www.montenapoleone.no/product-category/linskjorter/', text: 'Lin' },
        { href: 'https://www.montenapoleone.no/product-category/flanell-skjorter/', text: 'Flanell' },
        { href: 'https://www.montenapoleone.no/product-category/herre-formelle-skjorter/', text: 'Business' },
        { href: 'https://www.montenapoleone.no/product-category/oxford-skjorter/', text: 'Oxford' },
        { href: 'https://www.montenapoleone.no/product-category/stretch-skjorter/', text: 'Stretch' },
        { href: 'https://www.montenapoleone.no/product-category/kordfloyel-skjorter/', text: 'Kordfløyel' },
        { href: 'https://www.montenapoleone.no/product-category/bedriftsavtale-skjorter/', text: 'Bedriftsavtale' },
    ];

    // Check if the current URL matches any of the target links
    if (links.some(link => window.location.href === link.href)) {
        // Select the container with the class 'sub-cats'
        var elements = document.querySelectorAll('.sub-cats');

        // Loop through each container, remove existing links, and add the new links
        elements.forEach(function(element) {
            // Remove existing child elements
            element.innerHTML = '';

            links.forEach(function(link) {
                // Create a new anchor element
                var anchor = document.createElement('a');
                anchor.href = link.href; // Set the href attribute
                anchor.textContent = link.text; // Set the link text
                anchor.className = 'subcategory-link'; // Optional: Add a class for styling

                // Check if the link's href matches the current URL and handle special cases
                if (window.location.href === 'https://www.montenapoleone.no/product-category/herre-skjorter/' && link.href === 'https://www.montenapoleone.no/product-category/herre-formelle-skjorter/') {
                    anchor.classList.add('active'); // Add the 'active' class only to 'Herre Formelle Skjorter'
                } else if (window.location.href === link.href) {
                    anchor.classList.add('active'); // Add the 'active' class for other matching links
                }

                // Append the anchor to the container
                element.appendChild(anchor);
            });
        });
    }
		
		// Check if the current URL matches the specified URL
if (window.location.href === "https://www.montenapoleone.no/product/firm-testing-product/") {
  // Hide the last three .content_parents elements
  document.querySelectorAll('.content_parents').forEach((div, index, arr) => {
    if (index >= arr.length - 3) div.style.display = 'none';
  });

  // Add comment section
  const commentSection = document.createElement('div');
  commentSection.className = 'comment-section new-21';
  commentSection.innerHTML = `
    <textarea id="comment-input" placeholder="Kommentarer"></textarea>
    <div id="comments-list"></div>
  `;
  document.querySelector('.accordion').insertAdjacentElement('afterend', commentSection);

  // Add BDRIFTS CODE section
  const bdriftsCode = document.createElement('div');
  bdriftsCode.className = 'bdrifts-code new-21';
  bdriftsCode.innerHTML = '<input type="text" placeholder="BEDRIFTSAVTALE KODE:">';
  commentSection.insertAdjacentElement('afterend', bdriftsCode);
}

});


window.addEventListener('load', function() {
	 document.querySelector('.cccvvddd').style.cssText = `
            padding-bottom: 34px !important;
        `;
	document.querySelector('.single_add_to_cart_button').style.cssText = `
                margin-top: 25px;
        `;
    if (
        (window.location.href === "https://www.montenapoleone.no/product/begunder-bla-rutet-flanell-skjorte-f15/" || 
        window.location.href === "https://www.montenapoleone.no/product/lys-gronn-flanell-skjorter-f30-2/" || 
        window.location.href === "https://www.montenapoleone.no/product/brun-flanell-skjorte-f12/") && 
        window.innerWidth >= 992
    ) {
        document.querySelector('.cccvvddd').style.cssText = `
            padding-bottom: 34px !important;
        `;
    }
	
	// Get the current URL
const currentURL = window.location.href;

// Check if the URL contains the word "product"
if (currentURL.includes('product')) {
  // Get the containers
  const cartContainer = document.querySelector('.variations_form.cart');
  const summaryContainer = document.querySelector('.summary.entry-summary');

  // Check if both containers exist before appending
  if (cartContainer && summaryContainer) {
    // Append the cartContainer to the summaryContainer
    summaryContainer.appendChild(cartContainer);
    console.log('Cart container appended to summary container.');
  } else {
    console.error('Either cartContainer or summaryContainer is missing.');
  }
} else {
  console.log('URL does not contain "product". Operation skipped.');
}
	
	/// Select the element with the class 'cartButton'
const cartButton = document.querySelector('.cartButton');

// Check if the element exists
if (cartButton) {
  // Add CSS styles with !important using setProperty
  cartButton.style.setProperty('position', 'relative', 'important');
  cartButton.style.setProperty('width', '100%', 'important');
  cartButton.style.setProperty('top', 'unset', 'important');
  cartButton.style.setProperty('bottom', '0px', 'important');
  cartButton.style.setProperty('float', 'revert', 'important');
  cartButton.style.setProperty('right', 'unset', 'important');
} else {
  console.error('Element with class "cartButton" not found.');
}


});







</script>
<?php } 
add_action('wp_footer', 'add_this_script_footer');

/**
 * Override theme default specification for product # per row
 */
function loop_columns() {
return 4; // 5 products per row
}

add_filter('loop_shop_columns', 'loop_columns', 999);

add_shortcode( 'SearchForm', 'SearchFormShortCode' );

function SearchFormShortCode()

{
  ?>
   <div class="search-box search-slide">
                      <a href="<?php echo site_url();?>" class="search-logo"> 
                        <img src="<?php echo site_url(); ?>/wp-content/themes/Montenapoleone/images/logo.png"/>
                      </a>
                      <div class="shop_search_box"><?php if (function_exists('aws_get_search_form')) {
                                                      aws_get_search_form();
                        } ?></div>
                      <!--  <input type="search" placeholder="Start typing what you're looking for"> -->
                      <a href="#" class="search-close"><i class="fas fa-times"></i></a>

                   </div>
  <?php
}

function enqueue_slick_carousel() {
    // Add Slick Carousel CSS
    if (wp_is_mobile()) {
    wp_enqueue_style('slick-carousel-css', 'https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.8.1/slick.min.css');

    // Add Slick Carousel JS
    wp_enqueue_script('slick-carousel-js', 'https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.8.1/slick.min.js', array('jquery'), '', true);
	}
}

add_action('wp_enqueue_scripts', 'enqueue_slick_carousel');



function update_dc_garments_stock_on_order_processing($order_id) {
    // Get the order object
    $order = wc_get_order($order_id);

    // Iterate through order items and send product data
    foreach ($order->get_items() as $item_id => $item) {
        $product_id = $item->get_product_id();
        $quantity = $item->get_quantity();
		$price = $item->get_total();

        // Your API endpoint and data
        $api_url = 'https://dc-garment.com/staff/api/test.php';
        $api_data = array(
            'product_id' => $product_id,
            'quantity' => $quantity,
			'price' => $price,
        );

        // Initialize cURL session
        $ch = curl_init($api_url);

        // Set cURL options
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($api_data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Api-Key: sixerweb1234'  // Replace with your actual API key
        ));

        // Execute cURL session and get the response
        $response = curl_exec($ch);

        // Check for cURL errors
        if (curl_errno($ch)) {
            // Handle cURL errors
            error_log('cURL Error: ' . curl_error($ch));
        }

        // Close cURL session
        curl_close($ch);

        // Decode the JSON response
        $response_data = json_decode($response, true);
        // Check for errors in the response
        if (isset($response_data['error'])) {
            // Handle errors
            error_log('Error updating DC Garments stock: ' . $response_data['error']);
        }
    }
}

add_action('woocommerce_order_status_processing', 'update_dc_garments_stock_on_order_processing', 10, 1);


// Add an external link to the admin sidebar with custom icon and link
function custom_menu_item() {
    add_menu_page(
        'DC Database',               // Page title
        'DC Database',               // Menu title
        'manage_options',            // Capability required
        'dc-database-link',          // Menu slug (unique identifier)
        'dc_database_callback',      // Callback function (not used in this case)
         'dashicons-admin-generic',
        1                            // Position in the menu (top position)
    );
}

// Callback function (not used in this case)
function dc_database_callback() {
    // Redirect to the external URL
    wp_redirect('https://dc-garment.com/staff/');
    exit;
}

// Hook the custom_menu_item function to the admin_menu action
add_action('admin_menu', 'custom_menu_item');



function enqueue_custom_admin_styles() {
    add_action('admin_head', 'custom_admin_styles');
}

function jss()
{
	?>
<script>	
	
	const link = document.querySelector('a[href="https://www.montenapoleone.no/product-category/herre-fritids-skjorter/"]');

// Add an event listener to the link
if (link) {
    link.addEventListener('click', function(event) {
        // Prevent the default action (following the link)
        event.preventDefault();
		
        // Redirect to the new URL
        window.location.href = "https://www.montenapoleone.no/product-category/linskjorter/";
    });
}
	
	
// This code is for sticky navbar

document.addEventListener('DOMContentLoaded', function() {
    var lastScrollTop = 0; // Store the last scroll position
    var menu = document.querySelector('.she-k'); // Replace with your menu selector
    var threshold = 150; // Scroll threshold to toggle sticky behavior
    var isScrollingDown = false;
    var isAnimating = false;
    var urlContainsProduct = window.location.href.includes("product"); // Check if the URL contains "product"
	var urlContainsb2b = window.location.href.includes("b2b"); // Check if the URL contains "b2b"
	
	
if (urlContainsb2b) {

    const b2bMenu = document.getElementById("b2bmenu");
    if (b2bMenu) {
        // Hide the menu until fully loaded
        b2bMenu.style.display = "none";

        // Modify "Formal" text to "Business"
        const formalTab = document.querySelector('#tab-formal');
        if (formalTab) {
            formalTab.textContent = 'Business';
        } else {
            console.error('Formal tab not found');
        }

        // Rearrange "Lin" and "Business" tabs
        const menuItems = Array.from(b2bMenu.children);
        const linItem = menuItems.find(item => item.querySelector('button').textContent.trim() === 'Lin');
        const businessItem = menuItems.find(item => item.querySelector('button').textContent.trim() === 'Business');

        if (linItem && businessItem) {
            // Remove "Lin" and "Business" from their current positions
            b2bMenu.removeChild(linItem);
            b2bMenu.removeChild(businessItem);

            // Insert "Lin" as the first item
            b2bMenu.insertBefore(linItem, b2bMenu.firstChild);

            // Insert "Business" as the third item
            b2bMenu.insertBefore(businessItem, b2bMenu.children[2]);
        } else {
            console.error('Lin or Business tab not found');
        }

        const linTab = document.querySelector('#tab-lin');
        if (linTab) {
            // Manually add click event listener to the "Lin" tab
            linTab.addEventListener('click', () => {
                console.log('"Lin" tab clicked');
                const activeTab = document.querySelector('.nav-link-monte-b2b.active');
                if (activeTab) {
                    activeTab.classList.remove('active');
                    activeTab.setAttribute('aria-selected', 'false');
                }
                linTab.classList.add('active');
                linTab.setAttribute('aria-selected', 'true');

                // Deactivate any currently active content
                const activeContent = document.querySelector('.tab-pane.active');
                if (activeContent) {
                    activeContent.classList.remove('active');
                    activeContent.style.display = 'none';  // Hide the current active content
                }

                // Activate the "Lin" content
                const linContent = document.querySelector(linTab.getAttribute('data-bs-target'));
                if (linContent) {
                    linContent.classList.add('active');
                    linContent.style.display = 'block';  // Show the "Lin" content
                    linContent.classList.add('lin2');  // Add the "lin2" class
                } else {
                    console.error('Lin tab content not found');
                }
            });

            // Programmatically trigger the click event to activate "Lin" by default
            linTab.dispatchEvent(new Event('click'));
        } else {
            console.error('Lin tab not found');
        }

        // Hide the "Business" content (previously "Formal") by default
        const businessContent = document.querySelector('#content-formal'); // The Business content
        if (businessContent) {
            businessContent.style.display = 'none'; // Hide "Business"
        } else {
            console.error('Business content not found');
        }

        // Show the "Lin" content as default
        const linContent = document.querySelector('#content-lin'); // The Lin content
        if (linContent) {
            linContent.style.display = 'block'; // Ensure "Lin" content is displayed
        } else {
            console.error('Lin content not found');
        }

        // Remove "lin2" class when clicking other "nav-link-monte-b2b" tabs
        const navLinks = document.querySelectorAll('.nav-link-monte-b2b');
        navLinks.forEach(link => {
            link.addEventListener('click', () => {
                const linContent = document.querySelector('#content-lin');
                if (linContent) {
                    linContent.classList.remove('lin2');  // Remove "lin2" class when other tabs are clicked
                }
            });
        });

        // Show the menu after everything is loaded
        b2bMenu.style.display = "flex";
    } else {
        console.error('Menu element not found');
    }
	
	 const detailsColumn = document.getElementById('details-column');
    
    if (detailsColumn) {
        // Update image sources
        const images = detailsColumn.querySelectorAll('img');
        images.forEach((img) => {
            if (img.src.includes('150x150')) {
                img.src = img.src.replace('150x150', '300x300');
            }
        });

        // Update hidden input values
        const hiddenInputs = detailsColumn.querySelectorAll('input[type="hidden"]');
        hiddenInputs.forEach((input) => {
            if (input.value.includes('150x150')) {
                input.value = input.value.replace('150x150', '300x300');
            }
        });
    }
}

	
	
    function slideUp(element) {
        if (!isAnimating) {
            isAnimating = true;
            element.style.transition = 'transform 0.3s ease';
            element.style.transform = 'translateY(-100%)';
            setTimeout(function() {
                element.style.display = 'none';
                isAnimating = false;
            }, 300);
        }
    }

    function slideDown(element) {
        if (!isAnimating) {
            isAnimating = true;
            element.style.display = 'block';
            element.style.transform = 'translateY(-100%)';
            setTimeout(function() {
                element.style.transition = 'transform 0.3s ease';
                element.style.transform = 'translateY(0)';
                isAnimating = false;
            }, 10);
        }
    }
	
    window.addEventListener('scroll', function() {
        var scrollTop = window.pageYOffset || document.documentElement.scrollTop;

         if (urlContainsProduct) {
            // Apply CSS to .main-shop if URL contains "product"
            
            var mainShop = document.querySelector('.main-shop');
			 
            if (mainShop) {
                mainShop.style.setProperty('margin-top', '80px', 'important');
            }
        }
		
		if (urlContainsb2b) {
            // Apply CSS to #b2bmenu if URL contains "b2b"
            
			var b2b = document.querySelector('#b2bmenu');
            if (b2b) {
                b2b.style.setProperty('margin-top', '60px', 'important');
            }
        }

        if (scrollTop < threshold) {
            // Make menu sticky when scroll is less than 150px
            menu.style.position = 'fixed';
            menu.style.top = '0px';
            menu.style.width = '100%';
            menu.style.display = 'block';
            menu.style.left = '0';
            menu.style.right = '0';
            menu.style.setProperty('margin', 'auto', 'important'); // Apply margin with !important
            menu.style.zIndex = '999999';
        } else {
            // Determine scroll direction
            if (scrollTop > lastScrollTop && !isScrollingDown) {
                // Scrolling down, hide menu
                slideUp(menu);
                isScrollingDown = true;
            } else if (scrollTop < lastScrollTop && isScrollingDown) {
                // Scrolling up, show menu
                slideDown(menu);
                isScrollingDown = false;
            }
        }

        lastScrollTop = scrollTop;
    });
});




</script>
<?php
}

add_action('wp_footer', 'jss');



