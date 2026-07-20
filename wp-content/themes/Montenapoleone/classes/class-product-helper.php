<?php
/**
 * Product_Helper
 *
 * The Product_Helper Class.
 *
 * @class    Product_Helper
 * @category Class
 * @author   Codingkart
 */
class Product_Helper
{
  public function __construct()
  {
    $this->init();
    add_action('wp_enqueue_scripts', array($this, 'load_tailor_scripts'));
    add_action('wp_ajax_shirt_item_ajax', array($this, 'shirt_item_ajax_callback'));
    add_action('wp_ajax_nopriv_shirt_item_ajax', array($this, 'shirt_item_ajax_callback'));
    add_action('woocommerce_before_calculate_totals', array($this, 'add_product_price_in_cart'), 20, 1);
    add_filter('woocommerce_get_item_data', array($this, 'show_item_data_on_cart'), 10, 2);
    add_filter('woocommerce_get_item_data', array($this, 'shirt_show_item_data_on_cart'), 10, 2);
    add_action('woocommerce_checkout_create_order_line_item', array($this, 'add_tailor_in_order_items'), 10, 4);
    // Display a linked button + the link of the logo file in backend
    add_action('woocommerce_after_order_itemmeta', array($this, 'backend_logo_link_after_order_itemmeta'), 20, 3);
    add_action('woocommerce_order_item_meta_end', array($this, 'display_custom_data_in_order'), 10, 4);
    // hook into the fragments in AJAX and add our new table to the group
    add_filter('woocommerce_update_order_review_fragments', array($this, 'tailor_order_fragments_split_shipping'), 10, 1);

    add_action('sm_checkout_payment', array($this, 'my_custom_display_payments'), 20);

    add_filter('woocommerce_update_order_review_fragments', array($this, 'my_custom_payment_fragment'));
    // add_filter( 'woocommerce_cart_item_name', array($this,'sm_product_image_on_checkout'), 10, 3 );
    add_action('wp_ajax_ajaxlogin', array($this, 'ajax_login'));
    add_action('wp_ajax_nopriv_ajaxlogin', array($this, 'ajax_login'));
    add_action('init', array($this, 'registerPost_size_chart'));

    add_action('init', array($this, 'registerPost_story'));
    add_action('init', array($this, 'retailers_partner_post'));
    add_action('init', array($this, 'monte_offices_post'));
    add_action('init', array($this, 'faq_post_function'));
    add_action('init', array($this, 'timeline_post_function'));
    add_action('init', array($this, 'registerPost_news'));
    add_action('init', array($this, 'stockManagerProduct'));

    add_action('wp_ajax_size_guide_img_preview_ajax', array($this, 'size_guide_img_preview_ajax_callback'));
    add_action('wp_ajax_nopriv_size_guide_img_preview_ajax', array($this, 'size_guide_img_preview_ajax_callback'));

    add_filter('woocommerce_cart_item_price', array($this, 'sm_woocommerce_cart_item_price_filter'), 10, 3);

    add_action('wp_ajax_newsletter_chek_ajax', array($this, 'newsletter_chek_ajax_callback'));
    add_action('wp_ajax_nopriv_newsletter_chek_ajax', array($this, 'newsletter_chek_ajax_callback'));

    add_action('wp_ajax_get_variation_custom_size', array($this, 'get_variation_custom_size'));
    add_action('wp_ajax_nopriv_get_variation_custom_size', array($this, 'get_variation_custom_size'));
  }


  public function init()
  {
    if (!is_admin() || (defined('DOING_AJAX') && DOING_AJAX)) {

      $this->frontend_design();
    }
  }
  public function stockManagerProduct(){
      global $post;
    

      /*$product_id = $id;
      
      $stock_manage = get_post_meta($product_id , 'over_all_stock', true);
      $per_piece = get_post_meta($product_id , 'perpiece', true);
 
      $pendingStock = $stock_manage / $per_piece;
      $stock_manage = roundDown($pendingStock,0);
      $allProId[] = apply_filters( 'wpml_object_id', $product_id, 'product', FALSE, 'no' );
      $allProId[] = apply_filters( 'wpml_object_id', $product_id, 'product', FALSE, 'en' );
     
      foreach($allProId as $proId){
        $product = new WC_Product($proId);
        update_post_meta($proId , 'perpiece',  $per_piece);
        update_post_meta($proId , 'over_all_stock',  $stock_manage);
        update_post_meta($proId , '_stock',  $stock_manage);
        if($stock_manage > 0){
          update_post_meta($proId, '_stock_status',  'instock');
        }else{
          update_post_meta($proId, '_stock_status',  'outofstock');
        } 

           $products = wc_get_product( $product_id );
          if($products->is_type( 'variable' ) ) {
            $variations = $products->get_available_variations();
            if($variations){
              $variations_id = wp_list_pluck( $variations, 'variation_id' ); 
              foreach($variations_id as $variant_id){
                update_post_meta($variant_id, '_stock',  $stock_manage);
                if($stock_manage > 0){
                 
                  update_post_meta($variant_id, '_stock_status',  'instock');
                }else{
                  update_post_meta($variant_id, '_stock_status',  'outofstock');
                }
              }
            }
          }else{
            update_post_meta($proId, '_stock',  $stock_manage);
            update_post_meta($proId, '_stock_status',  'instock');
          }
      }*/
   
  }

  function get_variation_custom_size(){
    // $variation = get_post_meta($variation_id, 'custom_field');
    // echo $variation[0];
    global $post;
    $id = $_POST['post_id'];
    $variation_id = $_POST['variation_id'];
    $terms = get_the_terms( $id, 'product_cat' );
    $all_cat_slug = [];
    foreach ($terms as $term) {
        $all_cat_slug[] = $term->slug;
    }
    $variation = wc_get_product($variation_id);
    $variation_attributes = $variation->get_variation_attributes();  
    $sq = '';
    if(in_array('suits', $all_cat_slug))
    {
      $form_type = 'form_submit2';
    }
    else
    {
      $form_type = 'form_submit1';
    }
    global $wpdb;
    $tablename = 'addcustomproductvariations';
    if (!empty($variation_attributes['attribute_pa_size'])) {
      if($variation_attributes['attribute_pa_body-fit']=='slim-2')
    {
      $variation_attributes['attribute_pa_body-fit'] = 'slim';    
    }
      if($variation_attributes['attribute_pa_body-fit']=='vanlig-passform')
    { 
      $variation_attributes['attribute_pa_body-fit'] = 'modern'; 
    }
      if($variation_attributes['attribute_pa_body-fit']=='moderne')
    { 
      $variation_attributes['attribute_pa_body-fit'] = 'contemporary';
    }
      $sql = "SELECT * FROM $tablename WHERE form_type='$form_type' AND body_type='".$variation_attributes['attribute_pa_body-fit']."' AND size='".$variation_attributes['attribute_pa_size']."'";
     $result = $wpdb->get_results($sql , ARRAY_A);
    }
    else{
   
      $result = $wpdb->get_results("SELECT suits_length, shirt_length, sleeve_length, half_chest, half_wrist, shoulder, half_bottom, arm_hole, neck_collar  FROM $tablename WHERE form_type='$form_type' AND body_type='".$variation_attributes['attribute_pa_body-fit']."' AND size='".$variation_attributes['attribute_pa_size-suits']."'" , ARRAY_A);

    }

    $count = count($result);
    if($count > 0){
      $array = array('message' => 'found', 'data' => $result[0] );
    }
    else
    {
      $array = array('message' => $result );
//    echo "RESULT ELSE ", $form_type;
    }
    echo json_encode($array);

    die();
    /*print_r($array);
    die();*/
  }

  public function frontend_design()
  {

    remove_action('woocommerce_before_checkout_form', 'woocommerce_checkout_login_form', 10);
    add_action('login_before_checkout_form', 'woocommerce_checkout_login_form');

    remove_action('woocommerce_before_checkout_form', 'woocommerce_checkout_coupon_form', 10);
    add_action('coupon_before_checkout_form', 'woocommerce_checkout_coupon_form', 10);
  }

  public function load_tailor_scripts()
  {
    wp_enqueue_script('tailor_ajax-js', get_stylesheet_directory_uri() . '/js/tailor_ajax.js', array(), '1.0.0', true);
    wp_enqueue_script('tailor_ajax-js');
    $params = array(
      'ajaxurl' => admin_url('admin-ajax.php'),
    );
    wp_localize_script('tailor_ajax-js', 'ajax_object', $params);
    //wp_register_style('tailor_css', get_stylesheet_directory_uri() . '/css/tailor.css');
    wp_enqueue_style('tailor_css');

    wp_register_script('sweetalert-js', 'https://unpkg.com/sweetalert/dist/sweetalert.min.js');
    wp_enqueue_script('sweetalert-js');
  }


  public function shirt_item_ajax_callback()
  {
    global $woocommerce;
    //  parse_str($_POST['form'], $post_data);
    // print_r($_POST);print_r($post_data);die;
    // print_r($_POST['form']);die;
    if (isset($_POST['form'])) {
      WC()->cart->empty_cart();
      parse_str($_POST['form'], $post_data);
      // print_r($post_data);die;

      $textile_arry = unserialize(base64_decode($post_data['cloth_color']));
      $collar_arry = unserialize(base64_decode($post_data['t_collar']));
      $cuff_arry = unserialize(base64_decode($post_data['t_cuff']));
      // print_r($textile_arry);die;

      $response = $cart_item_data = array();

      $quantity = 1;
      $final_price = 0;
      // $cloth_color = $post_data['cloth_color'];
      $product_id = 2442;//2234
      // $t_collar = $post_data['t_collar'];
      //  $t_cuff = $post_data['t_cuff'];
      $fit = $_POST['fit'];
      $s_my_weight = $post_data['s_my_weight'];
      $s_my_hight = $post_data['s_my_hight'];
      $m_my_weight = $post_data['m_my_weight'];
      $m_my_hight = $post_data['m_my_hight'];
      $l_my_weight = $post_data['l_my_weight'];
      $l_my_hight = $post_data['l_my_hight'];
      $comment = $post_data['comment'];
      $pocket = $post_data['pocket'];
      $embroid_logo = empty($post_data['embroid_logo']) ? 50 : $post_data['embroid_logo'] ;
      $logo_text = $post_data['logo_text'];
      $logo_text_style = $post_data['logo_text_style'];
      //$measure = $post_data['measure_product'];
      //  $measure_option = $post_data['unit_option'];
      //$measure_option_fit = $post_data['measure_option_fit'];

      $shirt_measure = $post_data['shirt_measure'];
      $body_measure =  $post_data['body_measure'];
      $measureby =  $_POST['measureby'];
     
      $collar_design =  $_POST['collar_design'];
      $cuff_design =  $_POST['cuff_design'];

      //  $customize_type = $_POST['customize_type'];
      $delivery_days =  get_post_meta($product_id, 'delivery_days', true);
      if ($delivery_days) {
        $cart_item_data['delivery_days'] =  $delivery_days;
      }
      // if ($measure_option_fit=='on') {
      //    $unit = 'kg/cm';

      // }else{
      //    $unit = 'ibs/in';
      // }
      // print_r($body_measure[0]['unit']);die;
      $unit = $body_measure[0]['unit'];
      // if ($customize_type) {
      //   $cart_item_data['customize_type'] = $customize_type;
      // }

      $cart_item_data['customize_product'] = 'yes';
      if ($fit == "slim_fit") {
        $cart_item_data['fit_weight'] = $s_my_weight;
        $cart_item_data['fit_hight'] =  $s_my_hight;
        $cart_item_data['fit'] = 'slim fit';
      } else if ($fit == "modern_fit") {
        $cart_item_data['fit_weight'] = $m_my_weight;
        $cart_item_data['fit_hight'] =  $m_my_hight;
        $cart_item_data['fit'] = 'modern fit';
      } else if ($fit == "loose_fit") {
        $cart_item_data['fit_weight'] = $l_my_weight;
        $cart_item_data['fit_hight'] =  $l_my_hight;
        $cart_item_data['fit'] = 'loose fit';
      }
      if ($unit) {
        $cart_item_data['unit'] = $unit;
      }

      if (is_array($textile_arry) && !empty($textile_arry)) {
        $color_name = $textile_arry['textile_name'];
        $cart_item_data['textile_name'] =  $color_name;
        $color_price =  $textile_arry['textile_price'];
        $cart_item_data['color_price'] =  $color_price;
        $cart_item_data['main_textile_imag'] =  $textile_arry['textile_image'];
        $final_price += $color_price;
      }
      // print_r($cart_item_data);die;
      if (is_array($collar_arry) && !empty($collar_arry)) {

        $collar_name = $collar_arry['collar_name'];
        $cart_item_data['collar_name'] =  $collar_name;
        $collar_price =  $collar_arry['collar_price'];
        $cart_item_data['collar_price'] =  $collar_price;
        $final_price += $collar_price;
      }
      if (is_array($cuff_arry) && !empty($cuff_arry)) {
        // print_r($cuff_arry);die;
        $cuff_name = $cuff_arry['cuffs_name'];
        $cart_item_data['cuff_name'] =  $cuff_name;
        $cuff_price =  $cuff_arry['cuffs_price'];
        $cart_item_data['cuff_price'] =  $cuff_price;
        $final_price += $cuff_price;
      }

      if ($embroid_logo) {
        $cart_item_data['logo_price'] =  $embroid_logo;
        $final_price += $embroid_logo;
        if ($logo_text) {
          $cart_item_data['logo_text'] =  $logo_text;
        }
        if ($logo_text_style) {
          $cart_item_data['logo_text_style'] =  $logo_text_style;
        }
      }
      if ($comment) {
        $cart_item_data['comment'] =  $comment;
      }
      if ($pocket) {
        $cart_item_data['pocket_price'] =  $pocket;
        $cart_item_data['pocket'] =  'yes';
        $final_price += $pocket;
      }

      if ($collar_design) {
        $cart_item_data['collar_design'] = $collar_design;
      }
      if ($cuff_design) {
        $cart_item_data['cuff_design'] = $cuff_design;
      }

      $product_measure = array();
      if (!empty($body_measure) && $measureby == 'body') {
        $product_measure['body'] = $body_measure;
      }
      if (!empty($shirt_measure) && $measureby == 'shirt') {
        $product_measure['shirt'] = $shirt_measure;
      }
      $cart_item_data['product_measure'] = $product_measure;

      /*if ($measure_option) {
           $munit = 'kg/cm';
        }else{
           $munit = 'ibs/in';
        }*/

      if ($final_price) {
        $cart_item_data['final_price'] =  $final_price;
      }
      if (isset($_FILES['file']['name']) && !empty($_FILES['file']['name'])) {
        $upload = wp_upload_bits($_FILES['file']['name'], null, file_get_contents($_FILES['file']['tmp_name']));
        $filetype = wp_check_filetype(basename($upload['file']), null);
        $upload_dir = wp_upload_dir();
        $upl_base_url = is_ssl() ? str_replace('http://', 'https://', $upload_dir['baseurl']) : $upload_dir['baseurl'];
        $base_name = basename($upload['file']);
        $cart_item_data['logo_file'] = array(
          'url'      => $upl_base_url . '/' . _wp_relative_upload_path($upload['file']),
          'file_type' => $filetype['type'],
          'file_name' => $base_name,
          'title'     => preg_replace('/\.[^.]+$/', '', $base_name),
        );
      }

      $added = WC()->cart->add_to_cart($product_id, $quantity, '', '', $cart_item_data);
      if ($added) {
        $response['status'] = "success";
      } else {
        $response['status'] = "error";
      }
    } else {
      $response['status'] = "else error .. ";
    }

    $response['url'] = wc_get_cart_url();
    
    // return $response;
    echo json_encode($response);
    die;
  }

  public function add_product_price_in_cart($cart_obj)
  {
    // print_r($cart_obj);
    // This is necessary for WC 3.0+
    if (is_admin() && !defined('DOING_AJAX'))
      return;

    // Avoiding hook repetition (when using price calculations for example)
    if (did_action('woocommerce_before_calculate_totals') >= 2)
      return;
    // Loop through cart items
    foreach ($cart_obj->get_cart() as $cart_item) {
      $cart_product_id = $cart_item['data']->get_id();
      // echo "<pre>";
      // print_r($cart_item);
      // echo "</pre>";
      // die();

      $price = get_post_meta($cart_item['product_id'] , '_price', true);

      if(isset($cart_item['half_wrist'])){
        // echo $half_wrist_price = '10';

        //die();
      }
      else
      {
        $half_wrist_price = '0';
      }

      if(isset($cart_item['half_chest'])){
        $half_chest_price = '10';
      }
      else
      {
        $half_chest_price = '0';
      }

      if(isset($cart_item['half_hip'])){
        $half_hip_price = '10';
      }
      else
      {
        $half_hip_price = '0';
      }

      
      if(isset($cart_item['arm_hole'])){
        $arm_hole_price = '10';
      }
      else
      {
        $arm_hole_price = '0';
      }

      if(isset($cart_item['shoulder'])){
       echo  $shoulder_price = '10';
      }
      else
      {
        $shoulder_price = '0';
      }

      if($half_wrist_price!='0' || $half_chest_price !='0' || $half_hip_price !='0' || $shoulder_price !='0'|| $arm_hole_price !='0'){
      $final_price = $price + $half_wrist_price + $half_chest_price + $shoulder_price + $half_hip_price + $arm_hole_price;

      $cart_item['data']->set_price($final_price);        
      }


      if (isset($cart_item['final_price'])) {
       echo  $cart_item['data']->set_price($cart_item['final_price']);
      }
    }
  }

  public function sm_woocommerce_cart_item_price_filter($price, $cart_item, $cart_item_key)
  {
    //print_r($cart_item);
    if (isset($cart_item['final_price'])) {
      $price = wc_price($cart_item['final_price']);
    }

    return $price;
  }

  public function show_item_data_on_cart($item_data, $cart_item)
  {
//    Testing //print_r($item_data);
    $wait_unit = 'LBS';
	  if(isset($cart_item['unit'])){
    if ($cart_item['unit'] == 'cm') {
      $wait_unit = 'KG';
    }
	  }
    $html = '';
    $html2 = '';
    if (isset($cart_item['delivery_days'])) {
      $html2 .= '<p>Delivery in 7 days</p>';
    }


    if (isset($cart_item['textile_name'])) {
      $html .= '<table class="sssa">';
      if (isset($cart_item['fit'])) {
        $html .= '<tr><td><b>Fit:</b></td><td>' . $cart_item['fit'] . '</td></tr>';
      }
      if (isset($cart_item['textile_name'])) {
        $html .= '<tr><td><b>Textile:</b></td><td>' . $cart_item['textile_name'] . '</td><td>' . wc_price($cart_item['color_price']) . '</td></tr>';
      }
      if (isset($cart_item['collar_name'])) {
        $html .= '<tr><td><b>Collar:</b></td><td>' . $cart_item['collar_name'] . '</td><td>' . wc_price($cart_item['collar_price']) . '</td></tr>';
      }
      if (isset($cart_item['cuff_name'])) {
        $html .= '<tr><td><b>Cuff:</b></td><td>' . $cart_item['cuff_name'] . '</td><td>' . wc_price($cart_item['cuff_price']) . '</td></tr>';
      }

      if (isset($cart_item['pocket'])) {
        $html .= '<tr><td><b>Pocket:</b></td><td>' . $cart_item['pocket'] . '</td><td>' . wc_price($cart_item['pocket_price']) . '</td></tr>';
      }
      if (isset($cart_item['product_measure'])) {

        //$html .= '<tr><td><b>product '.$cart_item['customize_type'].' measure:</b></td><td>'.$cart_item['product_measure'].'</td></tr>';
        $measure_html = '';
        foreach ($cart_item['product_measure'] as $key => $data) {
          $measure_html .= '</br><b>' . ucfirst($key) . ' Measure </b><br></br>';
          // print_r($data);
          foreach ($data as $name => $data) {
            //print_r($name);
            if ($name == '0') {
            } else {
              if (!empty($data['measure'])) {
                $measure_html .= '<p>' . ucfirst($name) . ': ' . $data['measure'] . ' ' . $cart_item['unit'] . '</p>';
              }
            }
          }
        }
        $html .= '<tr><td><b>Product Measure:</b></td><td>
            ' . $measure_html . '</td></tr>';
      }
      if (isset($cart_item['fit_weight'])) {
        $html .= '<tr><td><b>Weight:</b></td><td>' . $cart_item['fit_weight'] . ' ' . $wait_unit . '</td></tr>';
      }
      if (isset($cart_item['fit_hight'])) {
        $html .= '<tr><td><b>Hight:</b></td><td>' . $cart_item['fit_hight'] . ' ' . $cart_item['unit'] . '</td></tr>';
      }

      if (isset($cart_item['logo_text'])) {
        $html .= '<tr><td><b>Logo text:</b></td><td>' . $cart_item['logo_text'] . '</td><td>' . wc_price($cart_item['logo_price']) . '</td></tr>';
      }
      if (isset($cart_item['logo_text_style'])) {
        $html .= '<tr><td><b>Logo text style:</b></td><td>' . $cart_item['logo_text_style'] . '</td></tr>';
      }

      if(isset($cart_item['product_color']))
      {
        $html .= '<tr><td><b>Color:</b></td><td>' . json_encode($cart_item['product_color'], ture)['name'] . '</td></tr>';
      }
      if (isset($cart_item['logo_file'])) {
        $html .= '<tr><td><b>Logo image:</b></td><td><a href="' . $cart_item['logo_file']['url'] . '"><img src="' . $cart_item['logo_file']['url'] . '"></a></td></tr>';
      }

      $html .= '</table>';

      $item_data[] = array(
        'key'     => __('Custom Made', 'tailor_made_shirt'),
        'value'   => $html,
        'display' => '',
      );
    }

    return $item_data;
  }

  public function shirt_show_item_data_on_cart($item_data, $cart_item)
  {
    $product_id = $cart_item['product_id'];

    $term_list =  get_the_terms( $product_id, 'product_cat' );

    $label = '';

   foreach ($term_list as $key => $value) {
    if( 't-shirts' ==  $value->slug )
    {
      $label = 'T-shirt length:';
    }
    else{
      $label = 'Shirt length:';
    }
   }


    $html = '';

    if (isset($cart_item['main_front_unit'])) {
      $main_front_unit = $cart_item['main_front_unit'];
    }
    if (isset($cart_item['shirt_length'])) {
      $html .= '<table class="TTP">';
      if (isset($cart_item['shirt_length'])) {
        $html .= '<tr><td><b>'.$label.'</b></td><td>' . $cart_item['shirt_length'] . ' ' . $main_front_unit . '</td></tr>';
      }
   
   if (isset($cart_item['suit_length'])) {
        $html .= '<tr><td><b>length:</b></td><td>' . $cart_item['suits_length'] . ' ' . $main_front_unit . '</td></tr>';
      }

      if (isset($cart_item['left_sleeve_length'])) {
        $html .= '<tr><td><b>Left sleeve length:</b></td><td>' . $cart_item['left_sleeve_length'] . ' ' . $main_front_unit . '</td></tr>';
      }
      if (isset($cart_item['right_sleeve_length'])) {
        $html .= '<tr><td><b>Right sleeve length:</b></td><td>' . $cart_item['right_sleeve_length'] . ' ' . $main_front_unit . '</td></tr>';
      }
      if (isset($cart_item['half_wrist'])) {
        $html .= '<tr><td><b>Half Wrist:</b></td><td>' . $cart_item['half_wrist'] . ' ' . $main_front_unit . '</td></tr>';
      }
      if (isset($cart_item['half_chest'])) {
        $html .= '<tr><td><b>Half Chest:</b></td><td>' . $cart_item['half_chest'] . ' ' . $main_front_unit . '</td></tr>';
      }
      if (isset($cart_item['half_hip'])) {
        $html .= '<tr><td><b>Half Hip:</b></td><td>' . $cart_item['half_hip'] . ' ' . $main_front_unit . '</td></tr>';
      }
      if (isset($cart_item['arm_hole'])) {
        $html .= '<tr><td><b>Arm Hole:</b></td><td>' . $cart_item['arm_hole'] . ' ' . $main_front_unit . '</td></tr>';
      }
      if (isset($cart_item['shoulder'])) {
        $html .= '<tr><td><b>Shoulder:</b></td><td>' . $cart_item['shoulder'] . ' ' . $main_front_unit . '</td></tr>';
      }

      if (isset($cart_item['collar_design'])) {
         $html .= '<tr><td><b>Collar Design </b></td><td>' . $cart_item['collar_design'] . ' ' . $main_front_unit . '</td></tr>';
      }
      if (isset($cart_item['cuff_design'])) {
          $html .= '<tr><td><b>Cuff Design:</b></td><td>' . $cart_item['cuff_design'] . ' ' . $main_front_unit . '</td></tr>';
      }
      $html .= '</table>';
      $item_data[] = array(
        'key'     => __('', 'custume_shirt'),
        'value'   => $html,
        'display' => '',
      );
    }

    return $item_data;
  }

  public function add_tailor_in_order_items($item, $cart_item_key, $values, $order)
  {
    global $woocommerce;

    if (isset($values['delivery_days'])) {
      $item->add_meta_data('delivery_days', $values['delivery_days']);
    }
    if (isset($values['fit'])) {
      $item->add_meta_data('fit', $values['fit']);
    }
    if (isset($values['textile_name'])) {
      $item->add_meta_data('textile_name', $values['textile_name']);
    }
    if (isset($values['collar_name'])) {
      $item->add_meta_data('collar_name', $values['collar_name']);
    }
    if (isset($values['cuff_name'])) {
      $item->add_meta_data('cuff_name', $values['cuff_name']);
    }
    if (isset($values['fit_weight'])) {
      $item->add_meta_data('fit_weight', $values['fit_weight']);
    }
    if (isset($values['fit_hight'])) {
      $item->add_meta_data('fit_hight', $values['fit_hight']);
    }

    if (isset($values['main_textile_imag'])) {
      $item->add_meta_data('_main_textile_imag', $values['main_textile_imag']);
    }
    if (isset($values['product_measure'])) {

      //$item->add_meta_data('_customize_type',$values['customize_type']); 
      //$skey = 'product_'.$values['customize_type'].'_measure';
      $measure_html = '';
      foreach ($values['product_measure'] as $key => $data) {
        $measure_html .= '</br><b>' . ucfirst($key) . ' Measure </b><br></br>';
        foreach ($data as $name => $data) {
          if ($name == '0') {
          } else {
            if (!empty($data['measure'])) {
              $measure_html .= '<p>' . ucfirst($name) . ': ' . $data['measure'] . ' ' . $values['unit'] . '</p>';
            }
          }
        }
      }
      $html .= '<table>';
      $html .= '<tr><td><b>Product Measure:</b></td><td>
            ' . $measure_html . '</td></tr>';
      $html .= '</table>';

      $item->add_meta_data('product_measure', $values['product_measure']);

      $item->add_meta_data('product_measure_data', $html);
    }
    if (isset($values['pocket_price'])) {
      $item->add_meta_data('pocket', $values['pocket']);
      $item->add_meta_data('_pocket_price', $values['pocket_price']);
    }
    if (isset($values['logo_text'])) {
      $item->add_meta_data('logo_text', $values['logo_text']);
    }
    if (isset($values['logo_text_style'])) {
      $item->add_meta_data('logo_text_style', $values['logo_text_style']);
    }
    if (isset($values['logo_file'])) {
      $item->add_meta_data('_logo_file', $values['logo_file']);
    }
    if (isset($values['comment'])) {
      $item->add_meta_data('_comment', $values['comment']);
    }
    if (isset($values['shirt_length'])) {
      $item->add_meta_data('shirt_length', $values['shirt_length']);
    }
    if (isset($values['left_sleeve_length'])) {
      $item->add_meta_data('left_sleeve_length', $values['left_sleeve_length']);
    }
    if (isset($values['right_sleeve_length'])) {
      $item->add_meta_data('right_sleeve_length', $values['right_sleeve_length']);
    }
    if (isset($values['collar_design'])) {
      $item->add_meta_data('collar_design', $values['collar_design']);
    }
    if (isset($values['cuff_design'])) {
      $item->add_meta_data('cuff_design', $values['cuff_design']);
    }
  }
  public function backend_logo_link_after_order_itemmeta($item_id, $item, $product)
  {
    // Only in backend for order line items (avoiding errors)
    if (is_admin() && $item->is_type('line_item') && $item->get_meta('_logo_file')) {
      $file_data = $item->get_meta('_logo_file');
      if ($file_data['url']) {
        echo '<p><a href="' . $file_data['url'] . '" target="_blank" class="button">' . __("Logo File") . '</a></p>';
      }
    }
  }
  public function display_custom_data_in_order($item_id, $item, $order, $bool)
  {
//     $file_data = $item->get_meta('_logo_file');
//     if ($file_data['url']) {
//       echo '<p><a href="' . $file_data['url'] . '" target="_blank" class="button">' . __("Logo File") . '</a></p>';
//     }
//     $comment = $item->get_meta('_comment');
//     if ($comment) {
//       echo '<p>' . $comment . '</p>';
//     }
  }
  // We'll get the template that just has the shipping options that we need for the new table
  public function tailor_woocommerce_order_review_shipping_split($deprecated = false)
  {
?>
<table class="shop_table">
    <?php do_action('woocommerce_review_order_before_shipping'); ?>

    <?php wc_cart_totals_shipping_html(); ?>

    <?php do_action('woocommerce_review_order_after_shipping'); ?>
</table>
<?php
  }
  public function tailor_order_fragments_split_shipping($order_fragments)
  {

    ob_start();
    $this->tailor_woocommerce_order_review_shipping_split();
    $tailor_woocommerce_order_review_shipping_split = ob_get_clean();

    $order_fragments['.websites-depot-checkout-review-shipping-table'] = $tailor_woocommerce_order_review_shipping_split;

    return $order_fragments;
  }


  /**
   * Displaying the Payment Gateways 
   */
  public function my_custom_display_payments()
  {
  ?>
<?php
    if (WC()->cart->needs_payment()) {
      $available_gateways = WC()->payment_gateways()->get_available_payment_gateways();
      WC()->payment_gateways()->set_current_gateway($available_gateways);
    } else {
      $available_gateways = array();
    }
    ?>
<div id="checkout_payments">

    <?php if (WC()->cart->needs_payment()) : ?>
    <ul class="wc_payment_methods payment_methods methods">
        <?php
          if (!empty($available_gateways)) {
            foreach ($available_gateways as $gateway) {
              wc_get_template('checkout/payment-method.php', array('gateway' => $gateway));
            }
          } else {
            echo '<li class="woocommerce-notice woocommerce-notice--info woocommerce-info">' . apply_filters('woocommerce_no_available_payment_methods_message', WC()->customer->get_billing_country() ? esc_html__('Sorry, it seems that there are no available payment methods for your state. Please contact us if you require assistance or wish to make alternate arrangements.', 'woocommerce') : esc_html__('Please fill in your details above to see available payment methods.', 'woocommerce')) . '</li>'; // @codingStandardsIgnoreLine
          }
          ?>
    </ul>
    <?php endif; ?>
</div>
<?php
  }

  /**
   * Adding our payment gateways to the fragment #checkout_payments so that this HTML is replaced with the updated one.
   */
  public function my_custom_payment_fragment($fragments)
  {
    ob_start();

    $this->my_custom_display_payments();

    $html = ob_get_clean();

    $fragments['#checkout_payments'] = $html;

    return $fragments;
  }

  public function sm_product_image_on_checkout($name, $cart_item, $cart_item_key)
  {

    /* Return if not checkout page */
    if (!is_checkout()) {
      return $name;
    }

    /* Get product object */
    $_product = apply_filters('woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key);

    /* Get product thumbnail */
    $thumbnail = $_product->get_image();

    /* Add wrapper to image and add some css */
    $image = '<div class="ts-product-image" style="width: 52px; height: 45px; display: inline-block; padding-right: 7px; vertical-align: middle;">'
      . $thumbnail .
      '</div>';

    /* Prepend image to name and return it */
    return $image . $name;
  }

  public function ajax_login()
  {


    // Nonce is checked, get the POST data and sign user on
    $info = array();
    $info['user_login'] = $_POST['username'];
    $info['user_password'] = $_POST['password'];
    $info['remember'] = true;

    $user_signon = wp_signon($info, false);

    if (is_wp_error($user_signon)) {
      $response['status'] = "error";
      //wc_add_notice( __( 'username and password is incorrect.', 'woocommerce' ), 'error' );
      $response['msg'] = $user_signon->get_error_message();
    } else {

      $response['status'] = "success";
    }

    echo json_encode($response);
    die;
  }

  public function registerPost_size_chart()
  {

    /**
     * Post Type: size_chart.
     */

    $box_labels = array(

      "name" => __("Size Chart"),

      "singular_name" => __("Size Chart"),

    );

    register_post_type(
      'size_chart',
      array(
        'labels' =>  $box_labels,
        'public' => true,
        'has_archive' => true,
        'rewrite' => array('slug' => 'size_charts'),
        'supports'           => array('title', 'editor', 'author', 'excerpt', 'custom-fields')
      )
    );
  }


  public function registerPost_story()

  {

    /**
     * Post Type: Story.
     */

    $box_labels = array(

      "name" => __("Story"),

      "singular_name" => __("Story"),

    );

    register_post_type(
      'story',
      array(
        'labels' =>  $box_labels,
        'public' => true,
        'has_archive' => true,
        'rewrite' => array('slug' => 'story'),
        'supports'           => array('title', 'editor', 'author', 'excerpt', 'custom-fields', 'thumbnail')
      )
    );
  }
  public function registerPost_news()

  {

    /**
     * Post Type: news.
     */

    $box_labels = array(

      "name" => __("News"),

      "singular_name" => __("News"),

    );

    register_post_type(
      'news',
      array(
        'labels' =>  $box_labels,
        'public' => true,
        'has_archive' => true,
        'rewrite' => array('slug' => 'all_news'),
        'supports'           => array('title', 'editor', 'author', 'excerpt', 'custom-fields', 'thumbnail')
      )
    );
  }

  public function retailers_partner_post()

  {

    /**
     * Post Type: retailer_partner.
     */

    $box_labels = array(

      "name" => __("Retailers Partner"),

      "singular_name" => __("Retailers Partner"),

    );

    register_post_type(
      'retailer_partner',
      array(
        'labels' =>  $box_labels,
        'public' => true,
        'has_archive' => true,
        'rewrite' => array('slug' => 'retailer_partner'),
        'supports'           => array('title', 'editor', 'author', 'excerpt', 'custom-fields', 'thumbnail')
      )
    );
  }

  public function monte_offices_post()

  {

    /**
     * Post Type: monte_office.
     */

    $box_labels = array(

      "name" => __("Monte offices"),

      "singular_name" => __("Monte office"),

    );

    register_post_type(
      'monte_office',
      array(
        'labels' =>  $box_labels,
        'public' => true,
        'has_archive' => true,
        'rewrite' => array('slug' => 'monte_office'),
        'supports'           => array('title', 'editor', 'author', 'excerpt', 'custom-fields', 'thumbnail')
      )
    );
  }
  public function faq_post_function()

  {

    /**
     * Post Type: custum_faq.
     */

    $labels = '';

    $box_labels = array(

      "name" => __("Faq"),

      "singular_name" => __("Faq"),

    );

    register_post_type(
      'custum_faq',
      array(
        'labels' =>  $box_labels,
        'public' => true,
        'has_archive' => true,
        'rewrite' => array('slug' => 'custum_faq'),
        'supports'           => array('title', 'editor', 'author', 'excerpt', 'custom-fields', 'thumbnail')
      )
    );


    register_taxonomy('faq_category', array('custum_faq'), array(
      'hierarchical' => true,
      'labels' => $labels,
      'show_ui' => true,
      'show_admin_column' => true,
      'query_var' => true,
      'rewrite' => array('slug' => 'faq_category'),
    ));
  }
  public function timeline_post_function()

  {

    /**
     * Post Type: about_timeline.
     */

    $box_labels = array(

      "name" => __("About Timelines"),

      "singular_name" => __("About Timeline"),

    );

    register_post_type(
      'about_timeline',
      array(
        'labels' =>  $box_labels,
        'public' => true,
        'has_archive' => true,
        'rewrite' => array('slug' => 'about_timeline'),
        'supports'           => array('title', 'editor', 'author', 'excerpt', 'custom-fields', 'thumbnail')
      )
    );
  }

  public function size_guide_img_preview_ajax_callback()
  {

    if (isset($_POST['post_id']) && isset($_POST['measure'])) {
      $post_id = $_POST['post_id'];

      global $post;
      $id = $_POST['product_id'];
      $terms = get_the_terms( $id, 'product_cat' );
      $all_cat_slug = [];
      foreach ($terms as $term) {
          $all_cat_slug[] = $term->slug;
      }
      $variation = wc_get_product($post_id);
      $variation_attributes = $variation->get_variation_attributes();      
      
      if(in_array('suits', $all_cat_slug))
      {
        $form_type = 'form_submit2';
      }
      else
      {
        $form_type = 'form_submit1';
      }

      global $wpdb;
      $tablename = 'addcustomproductvariations';
    $sqls = "SELECT size_guide_cm, size_guide_in FROM $tablename WHERE form_type='$form_type' AND body_type='".$variation_attributes['attribute_pa_body-fit']."' AND size='".$variation_attributes['attribute_pa_size']."'";
      $result = $wpdb->get_results( $sqls , ARRAY_A);
      $count = count($result);


      echo '<div class="size_chart_im_box">';

      if($count > 0)
      {
        // $size_guide_cm = site_url() .'/wp-content/uploads/customsizeguid/' .$result[0]['size_guide_cm'];
        // $size_guide_in = site_url() .'/wp-content/uploads/customsizeguid/' .$result[0]['size_guide_in'];
        $size_guide_cm = $result[0]['size_guide_cm'];

        $size_guide_in = $result[0]['size_guide_in'];

        if( 'cm' == $_POST['measure'] )
        {

          echo '<a href="' . $size_guide_cm . '" class="sz_img"><img src="' . site_url() . '/wp-content/uploads/2023/04/Screen-Shot-2023-04-14-at-7.42.57-AM.jpg" /></a>';
        }
        else
        {
          echo '<a href="' . $size_guide_in . '" class="sz_img"><img src="' . site_url() . '/wp-content/uploads/2023/04/Screen-Shot-2023-04-14-at-7.42.57-AM.jpg" /></a>';
        }

      } else {
//         echo 'No Image Found.';
       echo '<a href="' . $size_guide_in . '" class="sz_img"><img src="' . site_url() . '/wp-content/uploads/2023/04/Screen-Shot-2023-04-14-at-7.42.57-AM.jpg" /></a>';
      }

      echo '</div>';


      // die();

      // if( 'cm' == $_POST['measure'] )
      // {
      //   $image_id = get_post_meta($post_id, '_size_gide_cm_image', true);
      // }
      // else{
      //   $image_id = get_post_meta($post_id, '_size_gide_inch_image', true);
      // }


      // echo '<div class="size_chart_im_box">';


      // if ($image_id) {
      //   $image = wp_get_attachment_image_src($image_id, 'full');
      //   // print_r($image);
      //   echo '<a href="' . $image[0] . '" class="sz_img"><img src="' . $image[0] . '" /></a>';
      // } else {
      //   echo 'No Image Found.';
      // }
      // echo '</div>'; 
      exit;
    }
  }


  public function newsletter_chek_ajax_callback()
  {
    // print_r($_POST);die;
    if (isset($_POST['snewsletter'])) {
      $user_id = $_POST['user_id'];
      $newsletter = $_POST['snewsletter'];
      // print_r($newsletter);
      if ($newsletter == 'yes') {
        update_user_meta($user_id, 'mailchimp_woocommerce_is_subscribed', 1);
      } else {
        update_user_meta($user_id, 'mailchimp_woocommerce_is_subscribed', 0);
      }
      $response['status'] = "success";
    }
    echo json_encode($response);
    die;
  }
}

new Product_Helper();

//remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart' );
//remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30 );

function size_chart_content($cat_id)
{
  global $wpdb;
  $check_chart = $wpdb->get_results("SELECT * FROM $wpdb->postmeta
                      WHERE meta_key = 'for_categories' AND  meta_value = $cat_id LIMIT 1", ARRAY_A);
  // print_r($check_chart);
  ob_start();
  if (!empty($check_chart)) {
    $chart_id = $check_chart[0]['post_id'];
    $content = get_post_field('post_content', $chart_id);
    echo $content;
  }
  return ob_get_clean();
}

function cmp($a, $b)
{
  if ($a["name"] == $b["name"]) {
    return 0;
  }
  return ($a["name"] < $b["name"]) ? -1 : 1;
}
// add_filter( 'wc_add_to_cart_message', 'wc_custom_add_to_cart_message' );

// function wc_custom_add_to_cart_message() {
//    // echo '<style>.woocommerce-message {display: none !important;}</style>';
// }
add_action('woocommerce_single_product_summary', 'woocommerce_before_add_to_cart_form_back', 15);
function woocommerce_before_add_to_cart_form_back()
{

  global $product;
  $terms = get_the_terms( $product->get_id(), 'product_cat' );
  $all_cat_slug = [];
  foreach ($terms as $term) {
      $all_cat_slug[] = $term->slug;
  }

  if(in_array('custom-made-2', $all_cat_slug)){
    $custom_made_info = true;
  }
  else
  {
    $custom_made_info = false;
  }
  // $custom_made_info = get_post_meta($product->get_id(), 'custom_made_info', true);
  $product_cats_ids = wc_get_product_term_ids($product->get_id(), 'product_cat');
  $all_cat_slug=[];
  foreach ($product_cats_ids as $key => $value) {
    # code...
    $term = get_term_by( 'id', $value, 'product_cat' );
    $all_cat_slug[] =  $term->slug;
  }
  $cat_id = (int)$product_cats_ids[0];
  if( $term = get_term_by( 'id', $cat_id, 'product_cat' ) ){
    $cat_slug =  $term->slug;
  }
  // print_r($cat_id);
  ?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/fancybox/3.5.7/jquery.fancybox.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/fancybox/3.5.7/jquery.fancybox.min.js"></script>
<?php
  if ($product->get_type() == 'variable') {

    foreach ($product->get_variation_attributes() as $taxonomy => $terms_slug) {
      // To get the taxonomy object

      $taxonomy_obj = get_taxonomy($taxonomy);

      $taxonomy_name = $taxonomy_obj->name; // Name (we already got it)
      $taxonomy_label = $taxonomy_obj->label; // Label

      // Setting some data in an array
      $variations_attributes_and_values[$taxonomy] = array('label' => $taxonomy_obj->label);


      foreach ($terms_slug as $term) {

        // Getting the term object from the slug
        $term_obj  = get_term_by('slug', $term, $taxonomy);

        $term_id   = $term_obj->term_id; // The ID  <==  <==  <==  <==  <==  <==  HERE
        // print_r(get_term_meta( $term_id, 'product_attribute_color', true ));
        $term_name = $term_obj->name; // The Name
        $term_slug = $term_obj->slug; // The Slug
        // $term_description = $term_obj->description; // The Description

        // Setting the terms ID and values in the array
        $variations_attributes_and_values[$taxonomy]['terms'][$term_id] = array(
          'name'        => $term_name,
          'slug'        => $term_slug
        );
      }
    }

    $pa_mask_size_html = '';
    $is_mask_size_attr = 0;

    // echo "<pre>";
    // print_r($variations_attributes_and_values);
    // echo "</pre>";

    $variations = $product->get_available_variations();

   // pr($variations,true);

    foreach($variations as $variation){
         $variation_id = $variation['variation_id'];
         $variation_obj = new WC_Product_variation($variation_id);
         $stock = $variation_obj->get_stock_quantity();
       
    }

    foreach ($variations_attributes_and_values as $key => $attribute) {
      $product_attributes = array();
      $product_attributes = $attribute['terms'];
      $heading = '';
  
      if ($key == 'pa_color') {
        $class = 'custom_var colors-variation';
        $heading = '<h5>Colour</h5>';
      }

      if ($key == 'pa_size') {
        $class = 'custom_var size-guide-tabs';
        array_multisort(array_column($product_attributes, "slug"), SORT_ASC, $product_attributes);
      }

  if ($key == 'pa_size-t-shirt') {
    
        $class = 'custom_var size-guide-tabs';
      ksort(array_column($product_attributes, "slug"), SORT_DESC, SORT_REGULAR, $product_attributes);// added ksort to sorting t-shirts sizes
        // array_multisort(array_column($product_attributes, "slug"), SORT_DESC, SORT_STRING, $product_attributes, SORT_ASC, SORT_NUMERIC);
      }
       if ($key == 'pa_sweater') {
        $class = 'custom_var size-guide-tabs';
        array_multisort(array_column($product_attributes, "slug"),SORT_DESC,$product_attributes);
      }
    //This will work for women category details page size desine
    if ($key == 'pa_size-women') {
        $class = 'custom_var size-guide-tabs';
        array_multisort(array_column($product_attributes, "slug"), SORT_ASC, $product_attributes);
      }
//this class always designe the controll of  size Designe 
  if ($key == 'pa_size-suits') {
        $class = 'custom_var size-guide-tabs';
        array_multisort(array_column($product_attributes, "slug"), SORT_ASC, $product_attributes);
      }
      if ($key == 'pa_body-fit') {
         if(isset($_GET['lang']) && $_GET['lang'] =='en'){
              $heading_name =  'Body Fit';
            }else{
              $heading_name =  'Passform';
          }

        $class = 'custom_var bodyfit_select_container';
        $heading = '<div class="heading"><span>*</span>'.$heading_name.' (Obligatorisk) <div class="selected"></div></div>';
        $chek_body = true;
      }

      if ($key == 'pa_mask-size') {
        $class = 'custom_mask_size colors-variation';
        $heading = '<div class="heading">Mask Size</div>';
        $chek_body = true;
      }
        if ($key == 'pa_mask-size') {
          $attributes_dropdown = '<div class="' . $class . '">' . $heading . '<select data-attribute_name="'.$key.'" id="custume_' . $key . '">';
          $attributes_dropdown .= '<option value="">Choose an option</option>';
          foreach ($product_attributes as $keyy => $data) {
            $attributes_dropdown .= '<option value="'.$data['slug'].'">'.$data['name'].'</option>';
          }
          $attributes_dropdown .= '</select></div>';
        }else{
          $attributes_dropdown = $heading.'<div class="contents ' . $class . ' bmmk">' . '<ul id="custume_' . $key . '">';
      

        foreach ($product_attributes as $paID => $pa) {
//        print_r($product_attributes);
//        echo($paID).'<br>'.$pa['name'].'<br>';
          if((($paID != ''))){
        $SizeName = '';
              if ($key == 'pa_size') {
        $avaialble = "";
              if ($chek_body) {
                $size_class = 'disable_size';
              }
          
             $attributes_dropdown .= '<li data-default-size-length="ff" value="' . $pa['slug'] . '" data-value="' . $pa['slug'] . '" class="checknow ' . $size_class . '"><i>'.explode('/',$pa['name'])[0].'</i><a href="#" class="' . $pa['slug'] . '" title="' . $pa['name'] . '">' . $pa['slug'] .'</a> <div class="checks"></div><span></span></li>';
              //print_r($pa['name']);
            } 
//            if ($key == 'pa_color') {
//              $color = get_term_meta($paID, 'product_attribute_color', true);
          
//              $attributes_dropdown .= '<li value="' . $pa['slug'] . '" data-value="' . $pa['slug'] . '"><i>'.$pa['name'].'</i><a href="#" class="' . $pa['slug'] . '" title="' . $pa['name'] . '" style="background-color: ' . $color . ' !important;"></a><span></span></li>';
//            } 
//              if ($key == 'pa_size') {
//        $avaialble = "";
//              if ($chek_body) {
//                $size_class = 'disable_size';
//              }
          
//             $attributes_dropdown .= '<li data-default-size-length="ff" value="' . $pa['slug'] . '" data-value="' . $pa['slug'] . '" class="checknow ' . $size_class . '"><i>'.explode('/',$pa['name'])[0].'</i><a href="#" class="' . $pa['slug'] . '" title="' . $pa['name'] . '">' . $pa['slug'] .'</a> <div class="checks"></div><span></span></li>';
//               //print_r($pa['name']);
//            } 
            else if ($key == 'pa_size-suits') {
              if ($chek_body) {
                $size_class = 'disable_size';
              }

              $attributes_dropdown .= '<li data-default-size-length="ff" value="' . $pa['slug'] . '" data-value="' . $pa['slug'] . '" class="checknow ' . $size_class . '"> <i>'.$pa['name'].'</i><a href="#" class="' . $pa['slug'] . '" title="' . $pa['name'] . '">' . $pa['slug'] . ' </a> <div class="checks"></div><span></span></li>';
            }
            else if ($key == 'pa_size-t-shirt') {
              if ($chek_body) {
                $size_class = 'disable_size';
              }

              $attributes_dropdown .= '<li data-default-size-length="ff" value="' . $pa['slug'] . '" data-value="' . $pa['slug'] . '" class=" checknow' . $size_class . '"><i>'.$pa['name'].'</i><a href="#" class="' . $pa['slug'] . '" title="' . $pa['name'] . '">' . $pa['slug'] . ' </a> <div class="checks"></div><span></span></li>';
            }
              else if ($key == 'pa_sweater') {
              if ($chek_body) {
                $size_class = 'disable_size';
              }

              $attributes_dropdown .= '<li data-default-size-length="ff" value="' . $pa['slug'] . '" data-value="' . $pa['slug'] . '" class=" checknow' . $size_class . '"><i>'.$pa['name'].'</i><a href="#" class="' . $pa['slug'] . '" title="' . $pa['name'] . '">' . $pa['name'] . ' </a> <div class="checks"></div><span></span></li>';
            }
            //This code will for women size
             else if ($key == 'pa_size-women') {
              if ($chek_body) {
                $size_class = 'disable_size';
              }

              $attributes_dropdown .= '<li data-default-size-length="ff" value="' . $pa['slug'] . '" data-value="' . $pa['slug'] . '" class="checknow ' . $size_class . '"><i>'.$pa['name'].'</i><a href="#" class="' . $pa['slug'] . '" title="' . $pa['name'] . '">' . $pa['name'] . ' </a> <div class="checks"></div><span></span></li>';
            }
            else if ($key == 'pa_body-fit') {
              $attachment_id = get_term_meta($paID, 'product_attribute_image', true);
              $image = wp_get_attachment_image($attachment_id);
              //print_r($image);

              $attributes_dropdown .= 
            '
          <li  value="' . $pa['slug'] . '" data-value="' . $pa['slug'] . '" data-default-size-length="ff"  data-value="' . $pa['slug'] . '" class="lines custommade_option_for_shirts">
          <label for="for-'.$pa['name'].'" class="containers">
            <a href="#" class="' . $pa['slug'] . '" title="' . $pa['name'] . '">' . $SizeName.$pa['name'] . '</a>
          <input type="checkbox" name="radio" id="for-'.$pa['name'].'" class="chec' . $size_class . '"/>
          <i class="checkmark"></i>
          </label>
          </li>
          ';
            //'<li value="' . $pa['slug'] . '" data-value="' . $pa['slug'] . '" class="custommade_option_for_shirts"><a href="#" class="' . $pa['slug'] . '" title="' . $pa['name'] . '">' . $image . ' <div class="fit_text">' . $pa['name'] . '</div></a></li>';
            }
          }
        }
                    if(isset($_GET['lang']) && $_GET['lang'] =='en'){
                          $size_guide=  'Body Fit';
                        }else{
                          $size_guide =  'Størrelsesguide';
                      }
 if ($key == 'pa_size' OR $key == 'pa_size-t-shirt' OR $key == 'pa_sweater' OR $key == 'pa_size-women' OR $key == 'pa_size-suits') {
$attributes_dropdown .= "<a href='javascript:void(0);' class='size-guide-img-preview' data-post_id='".$product->get_id()."'>".$size_guide."</a> <div class='view_guide_image'></div>";
 }
        $attributes_dropdown .= '</ul></div>';
    }

      // echo $attributes_dropdown;
      if ($key == 'pa_color') {
        $pa_color_html = $attributes_dropdown;
      }

      if ($key == 'pa_size') {
        $pa_size_html = $attributes_dropdown;
      }

         if ($key == 'pa_size-t-shirt') {
        $pa_size_html = $attributes_dropdown;
      }
        if ($key == 'pa_sweater') {
        $pa_size_html = $attributes_dropdown;
      }

        if ($key == 'pa_size-women') {
        $pa_size_html = $attributes_dropdown;
      }
         if ($key == 'pa_size-suits') {
        $pa_size_html = $attributes_dropdown;
      }


      if ($key == 'pa_body-fit') {
        $pa_body_fit_html = $attributes_dropdown;
      }
      if ($key == 'pa_mask-size') {
         $is_mask_size_attr = 1;
        $pa_mask_size_html = $attributes_dropdown;
      }
    }
  ?>
<style type="text/css">
.woocommerce.single div.product div.summary .quantity {
    display: none !important;
}

.woocommerce.single div.product div.summary table.variations {
    display: none;
}
</style>
<script type="text/javascript">
jQuery(document).ready(function($) {

    setTimeout(function() {
        // var pa_color = $('#pa_color').val();
        // var pa_body_fit = $('#pa_body-fit').val();
        // var pa_size = $('#pa_size').val();
        // //console.log(pa_body_fit);
        // if (pa_color) {
        //   jQuery('#custume_pa_color li a.' + pa_color).click();
        // }
        // if (pa_body_fit) {
        //   jQuery('#custume_pa_body-fit li a.' + pa_body_fit).click();

        // }
        // if (pa_size) {
        //   jQuery('#custume_pa_size li a.' + pa_size).click();
        // }
        $(".reset_variations").trigger("click");
    }, 100);
});
jQuery(function($) {
    $('body').on('click', '.custom_var ul li', function(e) {
        e.preventDefault();
    var elements = $(this);
        $('.s_reset_variations').show();
        var novoVal = $(this).data('value');
        $('.variations select:has([value=' + novoVal + '])').val(novoVal);
        $('.variations select').trigger('change');
        var select = $('.variations select');
        var stuff = [];
        select.find('option').each(function() {
            //var titulo = $(this).text();
            var data_value = $(this).val();
            stuff.push(data_value);

        });
    
        $('.custom_var li:not(.active)').each(function() {
            var li_item = $(this);
            li_item.removeClass('disable_class');
            var val = li_item.attr('data-value')
            if (jQuery.inArray(val, stuff) == -1) {
                li_item.addClass('disable_class');
        li_item.addClass('new_disable_method');
        li_item.removeClass('disable_size');
        li_item.find('span').empty().append('<span class="badge label-available">ikke tilgjengeligff</span>');
                // console.log(val);
            }
      else
        {
        li_item.removeClass('new_disable_method');
        li_item.removeClass('disable_size');
        li_item.find('span').empty().append('<span class="badge label-available">tilgjengelig</span>'); 
        }

        });
        if ($('.custommade_option_for_shirts').hasClass('active')) {
            // setTimeout(function(){ 
            // alert();
            var variation_id = $('input.variation_id').val();
            if (variation_id != '') {
                $('.length-box').addClass('fields_disabale').append(
                );
                //jQuery('.down_arrow_open').addClass('fields_disabale');
                ajaxurl = '<?php echo site_url().'/wp-admin/admin-ajax.php';?>';
                post_id = '<?php echo get_the_ID(); ?>';
                // alert(ajaxurl);

                jQuery.ajax({
                    type: "POST",
                    url: ajaxurl,
                    data: {
                        action: "get_variation_custom_size",
                        variation_id: variation_id,
                        post_id: post_id
                    },
                    success: function(data) {
                        var obj = jQuery.parseJSON(data);
                        if (obj.message == 'found') {
                            const checked = $('#cm_change').is(":checked");
                            if (checked == true) {
                                $("#main_shirt_length").val(obj.data.shirt_length);
                                $("#main_left_sleeve").val(obj.data.sleeve_length);
                                $("#main_right_sleeve").val(obj.data.sleeve_length);

                                $("#main_half_wrist").val(obj.data.half_wrist);
                                $("#main_half_chest").val(obj.data.half_chest);
                                $("#main_half_bottom").val(obj.data.half_bottom);
                                $("#main_shoulder").val(obj.data.shoulder);
                                $("#main_arm_hole").val(obj.data.arm_hole);
                                $("#main_neck_collar").val(obj.data.neck_collar);

                                $('input#default_shirt_length').val(obj.data.shirt_length);
//                                 $('span.shirt_length_display').text(obj.data.shirt_length);

                                $('input#suits_length').val(obj.data.suits_length);
                                $('span.suits_length').text(obj.data.suits_length);

                                $('.left_sleeve_display').text(obj.data.sleeve_length);
                                $('.right_sleeve_display').text(obj.data.sleeve_length);

                                $('input#default_left_sleeve').val(obj.data.sleeve_length);
                                $('input#default_right_sleeve').val(obj.data.sleeve_length);

                                $('span.suits_length_display').text(obj.data.half_wrist);
                                $('span.shirt_chest_display').text(obj.data.half_chest);
                                $('span.shirt_hip_display').text(obj.data.half_bottom);
                                $('span.shirt_shoulder_display').text(obj.data.shoulder);
                                $('span.shirt_arm_hole_display').text(obj.data.arm_hole);
                                $('span.shirt_neck_collar_display').text(obj.data.neck_collar);

                                $('input#default_half-wrist').val(obj.data.half_wrist);
                                $('input#default_half-chest').val(obj.data.half_chest);
                                $('input#default_half-hip').val(obj.data.half_hip);
                                $('input#default_half-shoulder').val(obj.data.shoulder);
                                $('input#default_arm-hole').val(obj.data.arm_hole);


                            } else {
//                console.log('Nothing ia here ');
                                $("#main_shirt_length").text(elements.attr('value').toFixed(2));

                                $("#main_left_sleeve").text(elements.attr('value').toFixed(2));
                                $("#main_right_sleeve").text(elements.attr('value').toFixed(2));

                                $('input#suits_length').text(elements.attr('value').toFixed(2));
                                $('span.suits_length').text(elements.attr('value').toFixed(2));

                                $('input#default_shirt_length').text(elements.attr('value').toFixed(2));
                                $('span.shirt_length_display').text(elements.attr('value').toFixed(2));

                                $('.left_sleeve_display').text(elements.attr('value').toFixed(2));
                                $('.right_sleeve_display').text(elements.attr('value').toFixed(2));

                                $('input#default_left_sleeve').text(elements.attr('value').toFixed(2));
                                $('input#default_right_sleeve').text(elements.attr('value').toFixed(2));

                                $('span.suits_length_display').text(elements.attr('value').toFixed(2));
                                $('span.shirt_chest_display').text(elements.attr('value').toFixed(2));
                                $('span.shirt_hip_display').text(elements.attr('value').toFixed(2));
                                $('span.shirt_shoulder_display').text(elements.attr('value').toFixed(2));
                                $('span.shirt_arm_hole_display').text(elements.attr('value').toFixed(2));

                                $('input#default_half-wrist').text(elements.attr('value').toFixed(2));
                                $('input#default_half-chest').text(elements.attr('value').toFixed(2));
                                $('input#default_half-hip').text(elements.attr('value').toFixed(2));
                                $('input#default_half-shoulder').text(elements.attr('value').toFixed(2));
                                $('input#default_arm-hole').text(elements.attr('value').toFixed(2));

                                $("#main_half_wrist").text(elements.attr('value').toFixed(2));
                                $("#main_half_chest").text(elements.attr('value').toFixed(2));
                                $("#main_half_hip").text(elements.attr('value').toFixed(2));
                                $("#main_shoulder").text(elements.attr('value').toFixed(2));
                                $("#main_arm_hole").text(elements.attr('value').toFixed(2));
                                    
                            }
                        } else {
                            $("#main_shirt_length").val(elements.attr('value').toFixed(2));
                            $("#suits_length").val(elements.attr('value').toFixed(2));
                            $("#main_left_sleeve").val(68);
                            $("#main_right_sleeve").val(elements.attr('value').toFixed(2));

                            $("#main_half_wrist").val(elements.attr('value').toFixed(2));
                            $("#main_half_chest").val(elements.attr('value').toFixed(2));
                            $("#main_half_hip").val(elements.attr('value').toFixed(2));
                            $("#main_shoulder").val(elements.attr('value').toFixed(2));
                            $("#main_arm_hole").val(elements.attr('value').toFixed(2));

                            $('input#default_shirt_length').val(elements.attr('value').toFixed(2));
                            $('span.shirt_length_display').text(elements.attr('value'));

                            $('input#suits_length').val(elements.attr('value').toFixed(2));
                            $('span.suits_length').text(elements.attr('value').toFixed(2));

                            $('.left_sleeve_display').text(elements.attr('value').toFixed(2));
                            $('.right_sleeve_display').text(elements.attr('value').toFixed(2));

                            $('input#default_left_sleeve').val(elements.attr('value').toFixed(2));
                            $('input#default_right_sleeve').val(elements.attr('value').toFixed(2));

                            $('span.suits_length_display').text(elements.attr('value').toFixed(2));
                            $('span.shirt_chest_display').text(elements.attr('value').toFixed(2));
                            $('span.shirt_hip_display').text(elements.attr('value').toFixed(2));
                            $('span.shirt_shoulder_display').text(elements.attr('value').toFixed(2));
                            $('span.shirt_arm_hole_display').text(elements.attr('value').toFixed(2));

                            $('input#default_half-wrist').val(elements.attr('value').toFixed(2));
                            $('input#default_half-chest').val(elements.attr('value').toFixed(2));
                            $('input#default_half-hip').val(elements.attr('value').toFixed(2));
                            $('input#default_half-shoulder').val(elements.attr('value').toFixed(2));
                            $('input#default_arm-hole').val(elements.attr('value').toFixed(2));
                        }
                        $('.length-box').removeClass('fields_disabale');
                       // $('.down_arrow_open').removeClass('fields_disabale');
                        $(".custom-loader").remove();
                    }
                });
            }
            // }, 3000);
        }
$('body').on('click', '.bmmk ul li', function(e) {
  var thisEl = $(this);
  $(".bmmk ul li").find('.checks').html('');
  thisEl.find('.checks').html('<i class="fa fa-check"></i>');
  
});
    });


    $('body').on('click', '#custume_pa_size li', function(e) {
        e.preventDefault();
        // console.log('xx');
        $('.shirt-lengthh').removeClass('fields_disabale');
    $('.abcd').text($(this).attr('data-value'));
    });

    $('body').on('click', '#custume_pa_size-suits li', function(e) {
        e.preventDefault();
        //console.log('xx');
        $('.shirt-lengthh').removeClass('fields_disabale');
    });
    $('body').on('click', '#custume_pa_size li', function(e) {
    $(this).find('input[type="checkbox"]').prop('checked',true);
        e.preventDefault();
        $(this).removeClass('disable_size');
    });
    $('body').on('click', '#custume_pa_body-fit li', function(e) {
        $(".chec").prop('checked', false);
    $(this).find('input[type="checkbox"]').prop('checked',true);
        e.preventDefault();
       $(this).parents('.content_parents').attr('data-checked','true')
      console.log('Checked')
        $('#custume_pa_size-t-shirt li').removeClass('disable_size');
    });
    $('body').on('click', '#custume_pa_body-fit li', function(e) {
        e.preventDefault();
        // console.log('xx');
        $('#custume_pa_sweater li').removeClass('disable_size');
    });
      $('body').on('click', '.contents .checknow', function(e) {
        e.preventDefault();
        // console.log('xx');
        $('#custume_pa_sweater li').removeClass('disable_size');
    });

    $('body').on('click', '#custume_pa_body-fit li', function(e) {
        e.preventDefault();
        // console.log('xx');
        $('#custume_pa_size-women li').removeClass('disable_size');
    });
    $('body').on('click', '#custume_pa_size li', function(e) {
        e.preventDefault();
        // console.log('xx');
        $('.length-box').removeClass(' fields_disabale');
    $(this).removeClass('disable_size');
        $(".custom-loader").remove();
    });
    $('body').on('click', '#custume_pa_size-women li', function(e) {
        e.preventDefault();
        // console.log('xx');
        $('.length-box').removeClass(' fields_disabale');
        $('.shirt-lengthh').removeClass(' fields_disabale');
        $(".custom-loader").remove();
    });
    $('body').on('click', '#custume_pa_body-fit li', function(e) {
        e.preventDefault();
        // console.log('xx');
        $('#custume_pa_size-suits li').removeClass('disable_size');
        
        $('#custume_pa_size li').removeClass('disable_class');
    });

    $('body').on('click', '.s_reset_variations', function(e) {
        e.preventDefault();
        $(".reset_variations").trigger("click");
    });
    $('body').on('click', '.reset_variations', function(e) {

//         $('.s_reset_variations').hide();
//         $(".custom_var ul li").removeClass('disable_class');
//         $(".custom_var ul li").removeClass('active');
//         $('#custume_pa_size li').addClass('disable_size');
    });
});


</script>
<?php if($is_mask_size_attr == 1){ ?>
<script type="text/javascript">
jQuery(document).on('change', '.custom_mask_size  #custume_pa_mask-size', function() {
    var val = jQuery(this).val();
    var attr = 'pa_mask-size';
    var $parent = jQuery('table.variations>tbody');
    jQuery('#' + attr).val(val).change();

});
</script>
<?php } ?>
<?php if ($custom_made_info != true) { ?>
<style type="text/css">
li.standard_tab {
    display: none;
}

.shop-tabss ul {
    border-bottom: 0;
}
</style>

<?php } ?>
<div class="shop-right-contenntt">


    <?php //echo $pa_color_html ?>
    <?php echo $pa_mask_size_html ?>
    <!-- <div class="shop-tabss">
        <ul>
            <li class="standard_tab"><a href="#" class="active standard_option_for_shirts" tab-attr="standard">READY TO
                    WEAR</a></li>
            <?php if ($custom_made_info == true) { ?>
              <li><a href="#" tab-attr="custommade" class="custommade_option_for_shirts"><span class="blink"> TAILOR MADE</span> </a></li>
            <?php } ?>
        </ul>
    </div> -->
<script>
      jQuery(document).ready(function(){
  jQuery(document).on('click', function (e) {
    if (jQuery(e.target).closest(".accordion").length === 0) {
        jQuery(".contents").hide();
    }
});
       jQuery(".accordion").on("click", ".heading", function() {
    var checkValid = jQuery(".first_check").attr('data-checked');
  var checkFirstOption = jQuery(this).parents('.content_parents').hasClass('first_check');
     if(checkFirstOption == false)
    {
    if(checkValid == 'true')
      {
     jQuery('.first_check').find('.heading').css('box-shadow','none');
       jQuery(this).toggleClass("active").next().slideToggle();
       jQuery(".contents").not(jQuery(this).next()).slideUp(300);
       jQuery(this).siblings().removeClass("active");
      jQuery(this).siblings().css('display', 'flow-root')
     
     }
    else
     {
    jQuery('.first_check').find('.heading').css('box-shadow','0px 0px 0px 1px red');      
    }
    }
       else
         {
    jQuery(this).toggleClass("active").next().slideToggle();
       jQuery(".contents").not(jQuery(this).next()).slideUp(300);
       jQuery(this).siblings().removeClass("active");
      jQuery(this).siblings().css('display', 'flow-root')
              
         }
       });
      });
  jQuery(document).ready(function () {

    jQuery(".content").hide();
    jQuery(".show_hide").on("click", function () {
        var txt = jQuery(".content").is(':visible') ? 'Read More' : 'Read Less';
        jQuery(".show_hide").text(txt);
        jQuery(this).next('.content').slideToggle(200);
    });
    jQuery('.seeMore').click(function(){
      var checker = jQuery(this).attr('data-at');
      if(checker == 'true')
      {
        jQuery(".short_des").hide();  
        jQuery(".full_desc").show();  
        jQuery(this).attr('data-at','false')
        jQuery(this).text('Skjul tekst');
        if(jQuery(".full_desc").text() == '')
          {
          jQuery(".full_desc").html('aa') 
          }
      }
      else
        {
        jQuery(".short_des").show();  
        jQuery(".full_desc").hide();  
        jQuery(this).attr('data-at','true')
        jQuery(this).text('Les mer...');
        }
    })
});
           
  </script>

      
            <?php 
             if(isset($_GET['lang']) && $_GET['lang'] =='en'){
                  $p_size = 'Size';
                }
          else{$p_size = "Størrelse"; }
              
  function custom_product_description($atts){
    global $product;

    try {
        if( is_a($product, 'WC_Product') ) {
            return wc_format_content( $product->get_description("shortcode") );
        }

        return "Product description shortcode run outside of product context";
    } catch (Exception $e) {
        return "Product description shortcode encountered an exception";
    }
}
add_shortcode( 'custom_product_description', 'custom_product_description' );
function limit_text($text, $limit) {
    if (str_word_count($text, 0) > $limit) {
        $words = str_word_count($text, 2);
        $pos   = array_keys($words);
        $text  = substr($text, 0, $pos[$limit]) . '...';
    }
    return $text;
}
    ?>
</div>
  <div class="desc_short">
    <span class="short_des aad" data-tick="false">
    <?php 
    $html = "abc<p></p><p>dd</p><b>non-empty</b>"; 
    $pattern = "/<p[^>]*><\\/p[^>]*>/"; 
    $nbsp = html_entity_decode("&nbsp;");
    $strings = do_shortcode('[custom_product_description]');
    $your_desired_width = 420;
   echo '<p>'.limit_text(do_shortcode('[custom_product_description]'),80).'</p>';
      ?>
    </span>
    <span style="display:none" class="full_desc aad" data-tick="false">
      <?php echo html_entity_decode(do_shortcode('[custom_product_description]'));?>
    </span>
    <a href="JavaScript:void(0)" class="seeMore" data-at="true">Les mer...</a>
  </div>

<div class="line-below"> Vennligst fyll ut</div>
<div class="monto-grid-container">
    <?php 
    $c_name = wc_get_product_category_list(get_the_ID());

    if (get_field('add_fabric_type') === true) {
        if ($fabric_types = get_field('fabric_type')) {
            $theme_directory_url = get_template_directory_uri();
            $placeholder_image_url = $theme_directory_url . '/images/placeholder.jpg';

            foreach ($fabric_types as $fabric) {
                $checkValue = (isset($fabric['make_it_defualt'][0]) && $fabric['make_it_defualt'][0] === "Default") ? $fabric['fabric_name'] . '___' . $fabric['fabric_image']['sizes']['large'] : 'Okay';
                $selectedFabric = [
                    'name' => $fabric['fabric_name'],
                    'image' => $fabric['fabric_image']['sizes']['large']
					];
                ?>
                <div class="monto-grid-item">
                    <a href="javascript:void(0)" data-src='<?php echo $selectedFabric['image']; ?>' <?php echo ($checkValue !== 'Okay') ? 'class="active-product"' : ''; ?> data-text='<?php echo json_encode($selectedFabric);?>'>
                        <img src="<?php echo $fabric['fabric_image']['sizes']['thumbnail']; ?>"/>	 
                    </a>
                </div>
                <div class="zoom-overlay"></div>
                <?php
            }
        }
    }
    ?>
</div>
<style>
.selected {
    position: absolute;
    top: 13px;
    right: 41px;
    font-weight: bolder;
}
</style>
<script>
	
	jQuery(document).ready(function($){
		$(document).on('click', '#custume_pa_size li', function(){
			$(this).parents('.content_parents').find('.selected').text($(this).find('a').attr('title'));
		})
				$(document).on('click', '#custume_pa_body-fit li', function(){
			$(this).parents('.content_parents').find('.selected').text($(this).find('a').attr('title'));
		})
	})
</script>
  <div class="line_below_desc">
  </div>
    <div class="accordion sdfsdfd">
      <div class="content_parents first_check" data-checked="<?php echo (get_field('add_fabric_type') === true) ? 'true' : 'false'; ?>">
       <?php echo $pa_body_fit_html; ?>
      </div>
      <div class="content_parents">
        <div class="heading"><span>*</span><?php echo $p_size; ?> (Obligatorisk) <div class="selected"></div> </div>
         <?php echo $pa_size_html ?>
      </div>
          <?php echo do_shortcode('[Product_customization_attributes]'); ?>
          <div class="content_parents">
        <?php if ($custom_made_info == true) { ?>
        <div class="heading"><?php echo 'Skreddersydd';//$p_size; ?> (Valgt Fritt)</div>
        <?php } ?>
        <?php if ($custom_made_info == true) { ?>
            <div class="contents cusotmContetn">
        <?php } ?>
              <?php //echo do_shortcode('[CustomMadeInfoShortCode]'); ?>

<!--             <div class="p-size"> -->
<!--                 <div class="form-group mb-2 form-inline">
                    <div class="form-group">
                        <span class="cm-input">in</span>
                        <label class="switchs" for="cm_change">
                            <input id="cm_change" name="cm_change" checked type="checkbox" class="slide-switch" />
                            <div class="slider round"></div>
                        </label>
                        <span class="in-input">cm</span>
                    </div>

                </div>  -->
                
                

<!--             </div> -->

            <?php echo $pa_size_html ?>

            <a class="s_reset_variations" href="#" style="display:none">Clear</a>
            <div class="modal fade" id="myModal1" role="dialog">
                <div class="modal-dialog">
                    <!-- Modal content-->
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal">&times;</button>
                        </div>
                        <div class="modal-body guide-poup">
                            <h4><?php echo $size_guide; ?></h4>
                        </div>
                    </div>

                </div>
            </div>
    <!-- </div> -->
<?php if ($custom_made_info == true) { ?>
<div class="tabss-option" id="custommade" style="display:block">
   <div class="size_box_custommade"></div>
   <div class="length-box">
      <?php if( !in_array('suits', $all_cat_slug) ) { ?>
      <div class="shirt-lengthh">
         <a href="javascript:void(0);" class="shirt-length-box ">
            <div class="div_1">
               <div class="option__header">
                  <img class="custom-option-thumbnail" src="<?php echo get_template_directory_uri(); ?>/images/length.jpg"/>
                  <defs>
                     <style>
                        .cls-1 {
                        fill: #231f20;
                        }
                     </style>
                  </defs>
                  <path class="cls-1"
                     d="M90.71,49.21C90,47,83.93,28,82.54,26.12,80.48,23.26,71,19.37,68,18.19l-.43-.82a.62.62,0,0,0-.16-.2,1.71,1.71,0,0,0-.14-.26c0-.09-.09-.17-.14-.26s-.18-.27-.28-.4l0-.07-.1-.14c-.15-.18-.3-.36-.49-.56A9.87,9.87,0,0,0,59.16,13c-.3,0-.62,0-.93,0l-.16,0a5.83,5.83,0,0,0-.73.1l-.17,0-.14,0-.55.13-.19.06-.18,0-.45.15-.19.08-.18.07-.4.17-.2.1-.14.08-.39.2-.2.12-.11.07-.36.23-.2.15-.09.07a4.19,4.19,0,0,0-.33.26l-.2.16-.14.13-.11.1a.72.72,0,0,0-.38.28c-.06.08-.18.25-.32.47l-.08.1,0,.06c-.08.11-.16.21-.23.32s-.11.16-.18.27a3.45,3.45,0,0,0-.2.32c0,.09-.09.17-.12.23-.53.84-1,1.64-1.4,2.37L37.62,26l-.12-.09-.15.2-.79.43L27.74,52.88l-2.15,25.9,10,2,3.85-23.89V81.32l.12.19c.32.49,8,12,16.7,12h6.86c9.74-.47,17-11.57,17.32-12l.13-.19-.29-27.62,1.47,4.81L92.31,79l8-4ZM27.19,77.57l.34-4.06h7.66L34.3,79Zm13.92-31.4a54.23,54.23,0,0,0,1.39-10,13.11,13.11,0,0,0-3.69-9.1l9.62-4.86a4.5,4.5,0,0,0-.33,1.55l1.55,7.38,6.18-2.39V92c-7.21-.33-14-9.77-14.94-11.16V47.58Zm18-31.71a8.35,8.35,0,0,1,6,2.08,5,5,0,0,1,1.29,2.19c-1.39-1.26-3.78-1.88-7.3-1.88-.48,0-1,0-1.52.08l-.32,0-.13,0c-.34,0-.67.09-1,.15l-.46.09c-.32.07-.62.15-.91.24l-.13,0-.23.07c-.32.12-.62.23-.89.36v0L52,18.72c0-.07,0-.13.07-.2.35-.56.69-1.06,1-1.46l.13-.13.27-.28.17-.14.28-.25.19-.14.31-.22.2-.14.33-.19.23-.13.35-.17.24-.11.39-.15.24-.09.4-.12.09,0,.17,0,.45-.1.24,0,.53-.07.18,0C58.66,14.48,58.91,14.46,59.16,14.46Zm-5.09,4.89A6.48,6.48,0,0,1,55,19l-.08-.24.17.21a8.85,8.85,0,0,1,1.13-.3l-.05-.24.13.22c.41-.08.85-.15,1.36-.2s1-.07,1.48-.07c3.2,0,5.35.54,6.4,1.6a2.71,2.71,0,0,1,.78,1.87,7.21,7.21,0,0,1-3.47,2.78,20.75,20.75,0,0,1-3.46,1.23.89.89,0,0,0-.33,0c-1.1-.34-5.6-1.9-7-4.4a2.46,2.46,0,0,1,.15-.51,2.42,2.42,0,0,1,.31-.48l.05-.07A4.08,4.08,0,0,1,53.1,20l.09-.06a4.44,4.44,0,0,1,.62-.41Zm14.06,8.41-5.72-1.32a16.27,16.27,0,0,0,2.7-1.36,8.71,8.71,0,0,0,1.69-1.36l.23-.28.17-.21.16-.2.06-.11a4.35,4.35,0,0,0,.59-1.3c0-.05,0-.11,0-.16l.68,1.3ZM51.74,23.42l.09.1.3.3c.1.1.21.19.33.29l.36.3.35.26.38.26c.12.09.25.16.37.24l.39.24.38.22.39.2.38.2.37.18.37.16.35.15.35.15,0,0-6.19,2.4-1.16-5.44a6.68,6.68,0,0,1,.87-2.32,3.1,3.1,0,0,0,.13.4l.12.22.08.15a3.72,3.72,0,0,0,.21.39l.16.23.09.12c.09.12.17.23.27.34Zm5.59,4.73,1.88-.73.08,0,.31-.07,9.82,2.27.87-7.12-1.17-2.23c1.95.81,5,2.15,7.66,3.55a.77.77,0,0,0-.06.45c.11.64,1.08,6.48,1.61,13.92.12,1.73.23,3.45.31,5.15l0-.07L79,80.86c-1,1.38-7.79,10.91-16.21,11.17H57.33ZM95.91,67.4l-6.09,3.52-6.74-13-2.73-9c0-2.33-.19-6.11-.52-10.89-.46-6.41-1.23-11.59-1.52-13.41a10.73,10.73,0,0,1,3,2.34c.94,1.31,5.38,14.62,8,22.71Zm-56.52-20L35.43,72H27.65L29.2,53.24,37.64,28A11.92,11.92,0,0,1,41,36.15a54.44,54.44,0,0,1-1.36,9.73L39.46,47h-.07ZM93,77l-2.45-4.74,5.93-3.44,2,5.43Z"
                     transform="translate(-15.86 -12.85)"></path>
                  <path class="cls-1" d="M59.71,69.84a1.44,1.44,0,1,0,1.43,1.43A1.44,1.44,0,0,0,59.71,69.84Z"
                     transform="translate(-15.86 -12.85)"></path>
                  <path class="cls-1" d="M59.71,39.84a1.44,1.44,0,1,0,1.43,1.43A1.44,1.44,0,0,0,59.71,39.84Z"
                     transform="translate(-15.86 -12.85)"></path>
                  <path class="cls-1" d="M59.71,59.84a1.44,1.44,0,1,0,1.43,1.43A1.44,1.44,0,0,0,59.71,59.84Z"
                     transform="translate(-15.86 -12.85)"></path>
                  <path class="cls-1" d="M59.71,79.84a1.44,1.44,0,1,0,1.43,1.43A1.44,1.44,0,0,0,59.71,79.84Z"
                     transform="translate(-15.86 -12.85)"></path>
                  <path class="cls-1" d="M59.71,29.79a1.44,1.44,0,1,0,0,2.87,1.44,1.44,0,0,0,0-2.87Z"
                     transform="translate(-15.86 -12.85)"></path>
                  <polygon class="cls-1"
                     points="5.92 78.53 5.92 2.98 9.25 6.53 10.35 5.5 5.17 0 0 5.5 1.09 6.53 4.42 2.99 4.42 78.53 1.09 74.99 0 76.02 5.17 81.52 10.35 76.02 9.25 74.99 5.92 78.53">
                  </polygon>
                  <path class="cls-1" d="M59.71,49.84a1.44,1.44,0,1,0,1.43,1.43A1.44,1.44,0,0,0,59.71,49.84Z"
                     transform="translate(-15.86 -12.85)"></path>
                  </svg>
               </div>
               <?php
                  if( $cat_slug == 't-shirts' ){
                    echo '<h4>T-shirts Length</h4>';
                  }
                  else{
                    echo '<h4>Shirt Length</h4>';
                  }
                  ?>
               <span><span class="shirt_length_display abcd">0</span> <span class="unit_display">cm</span></span>
            </div>
            <div class="div_2"><span class="free-off-charge"> Free of Charge </span></div>
            <div class="div_3">
               <h6 class="change-title change-toggle" id="one-c">Change</h6>
            </div>
         </a>
      </div>
      <div class="values" id="shirt-value">
         <input type="hidden" id="default_shirt_length" name="default_shirt_length" value="0" />
         <div class="qty_box">
            <button type="button" id="sub" class="sub value-button">-</button>
            <input type="hidden" id="input_shirt_length" name="input_shirt_length" value="0" />
            <button type="button" id="add" class="add value-button">+</button>
            <span><span class="qty_text abcd">0</span> <span class="unit_display">cm</span></span>
         </div>
      </div>
      <?php } ?>
      <!-- Sleeves Length & Input -->
      <div class="shirt-lengthh sleeve-length">
         <a href="javascript:void(0);" class="sleeve-length-box">
            <div class="div_1">
               <div class="option__header">
                  <?php
                     global $product;
                     $current_product_id = $product->get_id();
                     if ($current_product_id == 1849) : // (1849 = blazer id)
                     ?>
                  <img src="https://wordpress-843741-3123615.cloudwaysapps.com/wp-content/uploads/2021/03/Sleeve-Length.jpg"
                     class="waist_icon_class">
                  <?php else: ?>
                  <img class="custom-option-thumbnail" src="<?php echo get_template_directory_uri(); ?>/images/sleeve length 3.jpg"/>
                  <defs>
                     <style>
                        .cls-1 {
                        fill: #231f20;
                        }
                     </style>
                  </defs>
                  <title>sleeve-length</title>
                  <path class="cls-1"
                     d="M675.9,388.23a1.42,1.42,0,0,0,1.29.81,1.4,1.4,0,0,0,1.36-1h1.06v-.6h-1a1.4,1.4,0,0,0-.11-.39,1.42,1.42,0,0,0-2.69.39h-.85v.6h.89A1.41,1.41,0,0,0,675.9,388.23Zm.94-1.35a.82.82,0,1,1-.39,1.09A.82.82,0,0,1,676.84,386.88Z"
                     transform="translate(-668.14 -368.69)"></path>
                  <path class="cls-1"
                     d="M686.68,379.19H673.08L673,368.77h-.6l.07,10.42h-3.74V368.77h-.6v30.74h0a.3.3,0,0,0,.29.28H687a.3.3,0,0,0,.29-.28h0V368.77h-.6Zm0,.6v19.12h-5.27l-8.24-7.75-.08-11.37Zm-5.85,19.4h-4.24l2.13-2Zm-8.34-19.4.08,11.63,5.71,5.37L676,398.91h-7.27V379.79Z"
                     transform="translate(-668.14 -368.69)"></path>
                  <polygon class="cls-1"
                     points="30.49 26.75 27.78 29.66 27.78 1.2 30.49 4.11 30.93 3.7 27.48 0 24.04 3.7 24.48 4.11 27.18 1.2 27.18 29.66 24.48 26.75 24.04 27.16 27.48 30.87 30.93 27.16 30.49 26.75">
                  </polygon>
                  </svg>
                  <?php endif;?>
               </div>
               <h4>Sleeve Length:</h4>
               <span id="number">Left:<span>
               <span class="left_sleeve_display abcd">0</span>
               <span class="unit_display abcd">cm</span>
               </span>, Right: <span>
               <span class="right_sleeve_display">0</span>
               <span class="unit_display abcd">cm</span></span>
               </span>
            </div>
            <div class="div_2">
               <span class="sleeve-free-off-charge"> Free of Charge </span>
            </div>
            <div class="div_3">
               <h6 class="change-title change-toggle" id="two-c">Change</h6>
            </div>
         </a>
      </div>
      <div class="values" id="sleeve-value">
         <input type="hidden" id="default_left_sleeve" name="default_left_sleeve" value="0" />
         <input type="hidden" id="default_right_sleeve" name="default_right_sleeve" value="0" />
         <!-- Left Sleeve -->
         <div class="mr15">
            <h3>Left Sleeve</h3>
            <div class="qty_box">
               <button type="button" id="sub" class="sub value-button">-</button>
               <input type="hidden" id="input_left_sleeve" name="input_left_sleeve" value="0" />
               <button type="button" id="add" class="add value-button">+</button>
               <span><span class="qty_text ">0</span> <span class="unit_display">cm</span></span>
            </div>
         </div>
         <!-- Right Sleeve -->
         <div>
            <h3>Right Sleeve</h3>
            <div class="qty_box">
               <button type="button" id="sub" class="sub value-button">-</button>
               <input type="hidden" id="input_right_sleeve" name="input_right_sleeve" value="0" />
               <button type="button" id="add" class="add value-button">+</button>
               <span><span class="qty_text ">0</span> <span class="unit_display">cm</span></span>
            </div>
         </div>
      </div>
      <?php if( !in_array('suits', $all_cat_slug)){ ?>
      <div class="product_cutomization">
         <!-- Half Waist Length & Input -->
         <div class="shirt-lengthh half-wrist">
            <a href="javascript:void(0);" class="suits-half-waist ">
               <div class="div_1">
                  <div class="option__header">
                     <style>
                        .waist_icon_class {
                        height: 55px !important;
                        margin-top: -8px;
                        }
                     </style>
                     <img class="custom-option-thumbnail" src="<?php echo get_template_directory_uri(); ?>/images/half waist.jpg"/>
                     <!-- <img src="https://wordpress-843741-3123615.cloudwaysapps.com/wp-content/uploads/2021/03/Half-Waist-C.jpg"
                        class="waist_icon_class"> -->
                  </div>
                  <h4>Waist</h4>
                  <span><span class="half_waist_display abcd">0</span> <span class="unit_display">cm</span></span>
               </div>
               <div class="div_2"><span class="price-custom-options"> $10 </span></div>
               <div class="div_3">
                  <h6 class="change-title change-toggle" id="three-c">Change</h6>
               </div>
            </a>
         </div>
         <div class="values" id="half-waist">
            <input type="hidden" id="default_half-waist" name="default_half-waist" value="0" />
            <div class="qty_box">
               <button type="button" id="sub" class="sub value-button">-</button>
               <input type="hidden" id="input_half-waist" name="input_half-waist" value="0" />
               <button type="button" id="add" class="add value-button">+</button>
               <span><span class="qty_text">0</span> <span class="unit_display">cm</span> </span>
            </div>
         </div>
         <!-- Half Chest Length & Input -->
         <div class="shirt-lengthh half-chest">
            <a href="javascript:void(0);" class="suits-half-chest ">
               <div class="div_1">
                  <div class="option__header">
                     <img class="custom-option-thumbnail" src="<?php echo get_template_directory_uri(); ?>/images/half_chest.jpg"/>
                     <!-- <img src="https://wordpress-843741-3123615.cloudwaysapps.com/wp-content/uploads/2021/03/Half-Chest-C.jpg"
                        class="waist_icon_class"> -->
                  </div>
                  <h4>Chest</h4>
                  <span><span class="shirt_chest_display abcd">0</span> <span class="unit_display">cm</span></span>
               </div>
               <div class="div_2"><span class="price-custom-options"> $10 </span></div>
               <div class="div_3">
                  <h6 class="change-title change-toggle" id="one-c">Change</h6>
               </div>
            </a>
         </div>
         <div class="values" id="half-chest">
            <input type="hidden" id="default_half-chest" name="default_half-chest" value="0" />
            <div class="qty_box">
               <button type="button" id="sub" class="sub value-button">-</button>
               <input type="hidden" id="input_half-chest" name="input_half-chest" value="0" />
               <button type="button" id="add" class="add value-button">+</button>
               <span><span class="qty_text">0</span> <span class="unit_display">cm</span></span>
            </div>
         </div>
         <!-- Half Hip Length & Input -->
         <div class="shirt-lengthh half-hip">
            <a href="javascript:void(0);" class="suits-half-hip">
               <div class="div_1">
                  <div class="option__header">
                     <img class="custom-option-thumbnail" src="<?php echo get_template_directory_uri(); ?>/images/hip 1.jpg"/>
                     <!-- <img src="https://wordpress-843741-3123615.cloudwaysapps.com/wp-content/uploads/2021/03/Half-Chest-C.jpg"
                        class="waist_icon_class"> -->
                  </div>
                  <h4>Half Bottom</h4>
                  <span><span class="shirt_hip_display abcd">0</span> <span class="unit_display">cm</span></span>
               </div>
               <div class="div_2"><span class="price-custom-options"> $10 </span></div>
               <div class="div_3">
                  <h6 class="change-title change-toggle" id="one-c">Change</h6>
               </div>
            </a>
         </div>
         <div class="values" id="half-hip">
            <input type="hidden" id="default_half-hip" name="default_half-hip" value="0" />
            <div class="qty_box">
               <button type="button" id="sub" class="sub value-button">-</button>
               <input type="hidden" id="input_half-hip" name="input_half-hip" value="0" />
               <button type="button" id="add" class="add value-button">+</button>
               <span><span class="qty_text">0</span> <span class="unit_display">cm</span></span>
            </div>
         </div>
         <!-- Shoulders Length & Input -->
         <div class="shirt-lengthh shoulder-lengthh">
            <a href="javascript:void(0);" class="suits-half-shoulder ">
               <div class="div_1">
                  <div class="option__header">
                     <img class="custom-option-thumbnail" src="<?php echo get_template_directory_uri(); ?>/images/Shoulder.jpg"/>
                     <!-- <img src="https://wordpress-843741-3123615.cloudwaysapps.com/wp-content/uploads/2021/03/Shoulder.jpg"
                        class="waist_icon_class"> -->
                  </div>
                  <h4>Shoulder</h4>
                  <span><span class="shirt_shoulder_display abcd">0</span> <span class="unit_display">cm</span></span>
               </div>
               <div class="div_2"><span class="price-custom-options"> $10 </span></div>
               <div class="div_3">
                  <h6 class="change-title change-toggle" id="one-c">Change</h6>
               </div>
            </a>
         </div>
         <div class="values" id="half-shoulder">
            <input type="hidden" id="default_half-shoulder" name="default_half-shoulder" value="0" />
            <div class="qty_box">
               <button type="button" id="sub" class="sub value-button">-</button>
               <input type="hidden" id="input_half-shoulder" name="input_half-shoulder" value="0" />
               <button type="button" id="add" class="add value-button">+</button>
               <span><span class="qty_text">0</span> <span class="unit_display">cm</span></span>
            </div>
         </div>
         <!-- Arm Hole Length & Input -->
<!--          <div class="shirt-lengthh arm-hole">
            <a href="javascript:void(0);" class="suits-arm-hole">
               <div class="div_1">
                  <div class="option__header">
                     <img class="custom-option-thumbnail" src="<?php echo get_template_directory_uri(); ?>/images/arm hole 3.jpg"/>
                     <!-- <img src="https://wordpress-843741-3123615.cloudwaysapps.com/wp-content/uploads/2021/03/Half-Chest-C.jpg"
                        class="waist_icon_class"> -->
		  <!--
                  </div>
                  <h4>Arm Hole</h4>
                  <span><span class="shirt_arm_hole_display abcd">0</span> <span class="unit_display">cm</span></span>
               </div>
               <div class="div_2"><span class="price-custom-options"> $10 </span></div>
               <div class="div_3">
                  <h6 class="change-title change-toggle" id="one-c">Change</h6>
               </div>
            </a>
         </div>
         <div class="values" id="arm-hole">
            <input type="hidden" id="default_arm-hole" name="default_arm-hole" value="0" />
            <div class="qty_box">
               <button type="button" id="sub" class="sub value-button">-</button>
               <input type="hidden" id="input_arm-hole" name="input_arm-hole" value="0" />
               <button type="button" id="add" class="add value-button">+</button>
               <span><span class="qty_text">0</span> <span class="unit_display">cm</span> $ 25.00</span>
            </div>
         </div> -->
         <!-- Neck/Collar Size & Input -->
         <div class="shirt-lengthh neck-collar">
            <a href="javascript:void(0);" class="suits-arm-hole">
               <div class="div_1">
                  <div class="option__header">
                     <img class="custom-option-thumbnail" src="<?php echo get_template_directory_uri(); ?>/images/arm hole 3.jpg"/>
                     <!-- <img src="https://wordpress-843741-3123615.cloudwaysapps.com/wp-content/uploads/2021/03/Half-Chest-C.jpg"
                        class="waist_icon_class"> -->
                  </div>
                  <h4>Neck/Collar</h4>
                  <span><span class="shirt_neck_collar_display">0</span> <span class="unit_display">cm</span></span>
               </div>
               <div class="div_2"><span class="price-custom-options"> $10 </span></div>
               <div class="div_3">
                  <h6 class="change-title change-toggle" id="one-c">Change</h6>
               </div>
            </a>
         </div>
         <div class="values" id="arm-hole">
            <input type="hidden" id="default_arm-hole" name="default_arm-hole" value="0" />
            <div class="qty_box">
               <button type="button" id="sub" class="sub value-button">-</button>
               <input type="hidden" id="input_arm-hole" name="input_arm-hole" value="0" />
               <button type="button" id="add" class="add value-button">+</button>
               <span><span class="qty_text">0</span> <span class="unit_display">cm</span> $ 25.00</span>
            </div>
         </div>
      
       </div>
         <?php } ?>
        
      
   </div>
   <span class="down_arrow_open ">Vis flere alternativer</span>
   <script type="text/javascript">
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
   </script>
    </div>  
           </div>
</div>
</div>
<?php
    }  

?>

    </div>
 <?php if ($custom_made_info == true) { ?>
</div>
<?php } ?>
 <?php if ($custom_made_info != true) { ?>
<style>
.cartButton {
    width: 95% !important;
</style>
<?php } ?>
<?php
  } else {
    echo '<div class="size-guide-img single_p">';
    echo "<a href='javascript:void(0);' class='size-guide-img-preview' data-post_id='" . $product->get_id() . "'>Size guide</a>";
    echo '<div class="view_guide_image">dfsdfsd</div>';
    echo '</div>';
  }
}

add_action('woocommerce_product_query', 'ts_custom_pre_get_posts_query');

function ts_custom_pre_get_posts_query($q)
{

  $tax_query = (array) $q->get('tax_query');

  $tax_query[] = array(
    'taxonomy' => 'product_cat',
    'field' => 'slug',
    'terms' => array('tailor-made'), // Don't display products in the clothing category on the shop page.
    'operator' => 'NOT IN'
  );


  $q->set('tax_query', $tax_query);
}

remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_excerpt', 20);
function custom_override_checkout_fields($fields)
{
  //unset($fields['billing']['billing_country']);
  unset($fields['billing']['billing_company']);
  unset($fields['billing']['billing_state']);
  unset($fields['order']['order_comments']);
  return $fields;
}
add_filter('woocommerce_checkout_fields', 'custom_override_checkout_fields');
// ---------------------------------
// 1) Make original email field half width
// 2) Add new confirm email field

add_filter('woocommerce_checkout_fields', 'bbloomer_add_email_verification_field_checkout');

function bbloomer_add_email_verification_field_checkout($fields)
{
  $user_id = get_current_user_id();
  $billing_email = get_user_meta($user_id, 'billing_email', true);
  $billing_phone_code = get_user_meta($user_id, 'billing_phone_code', true);
  $fields['billing']['billing_email']['class'] = array('form-row-wide');
  $fields['billing']['billing_phone']['class'] = array('form-row-last');
  $fields['billing']['billing_first_name']['class'] = array('form-row-wide');
  $fields['billing']['billing_last_name']['class'] = array('form-row-wide');
  $fields['billing']['billing_postcode']['class'] = array('form-row-first');
  $fields['billing']['billing_city']['class'] = array('form-row-last');




  // $fields['billing']['billing_em_ver'] = array(
  //   'label' => 'Confirm mail Address',
  //   'required' => true,
  //   'class' => array('form-row-wide'),
  //   'clear' => true,
  //   'default' => $billing_email,
  //   // 'priority' => 9,
  // );
  $fields['billing']['billing_phone_code'] = array(
    'label' => '',
    'required' => false,
    'class' => array('form-row-first'),
    'clear' => true,
    'default' => $billing_phone_code,
    //  'priority' => 999,
  );

  $fields['billing']['billing_first_name']['placeholder'] = '*First Name';
  $fields['billing']['billing_last_name']['placeholder'] = '*Sur Name';
  $fields['billing']['billing_email']['placeholder'] = '*E-mail ';
  // $fields['billing']['billing_em_ver']['placeholder'] = '*Reapeat e-mail ';
  $fields['billing']['billing_phone']['placeholder'] = '*Phone ';
  $fields['billing']['billing_phone_code']['placeholder'] = 'Code ';
  $fields['billing']['billing_postcode']['placeholder'] = '*Post code ';
  $fields['billing']['billing_city']['placeholder'] = '*Place ';
  $fields['billing']['billing_address_1']['placeholder'] = '*Address ';
  $fields['billing']['billing_address_2']['placeholder'] = 'Address ';

  $fields['billing']['billing_country']['priority'] = 0;

  $fields['billing']['billing_email']['priority'] = 0;
  $fields['billing']['billing_em_ver']['priority'] = 0;

  $fields['billing']['billing_state']['required'] = false;
  $fields['shipping']['shipping_state']['required'] = false;
  return $fields;
}

// ---------------------------------
// 3) Generate error message if field values are different

add_action('woocommerce_checkout_process', 'bbloomer_matching_email_addresses');

function bbloomer_matching_email_addresses()
{
  $email1 = $_POST['billing_email'];
  $email2 = $_POST['billing_em_ver'];
  $_POST['billing_phone'] = $_POST['billing_phone_code'] . $_POST['billing_phone'];
  // if ($email2 !== $email1) {
  //   wc_add_notice('Your email addresses do not match', 'error');
  // }
  // if (!$_POST['billing_phone_code']) {
  //   wc_add_notice('phone code is required.', 'error');
  // }
}
add_action('woocommerce_checkout_update_user_meta', 'sm_custom_checkout_field_update_user_meta',10,2);

function sm_custom_checkout_field_update_user_meta($customer_id, $posted)
{
  if (isset($posted['billing_phone_code'])) {
    $billing_phone_code = sanitize_text_field($posted['billing_phone_code']);
    update_user_meta($customer_id, 'billing_phone_code', $billing_phone_code);
  }
}

add_filter("woocommerce_checkout_fields", "scustom_override_checkout_fields", 11, 1);
function scustom_override_checkout_fields($fields)
{



  // Overriding existing billing_phone field 'class' property 



  // Reordering billing fields
  $order = array(
    "billing_country",
    //  "billing_state",
    "billing_email",
    // "billing_em_ver",
    "billing_first_name",
    "billing_last_name",
    "billing_address_1",
    "billing_address_2",
    "billing_postcode",
    "billing_city",
    "billing_phone_code",
    "billing_phone",



  );

  foreach ($order as $field) {
    $ordered_fields[$field] = $fields["billing"][$field];
  }

  $fields["billing"] = $ordered_fields;

  return $fields;
}

add_filter('woocommerce_currency_symbol', 'change_existing_currency_symbol', 10, 2);

function change_existing_currency_symbol($currency_symbol, $currency)
{
  switch ($currency) {
    case 'USD':
      $currency_symbol = ' USD';
      break;
  }
  return $currency_symbol;
}

//add_action( 'woocommerce_review_order_before_submit', 'bbloomer_add_checkout_privacy_policy', 9 );

function bbloomer_add_checkout_privacy_policy()
{

  woocommerce_form_field('privacy_policy', array(
    'type'          => 'checkbox',
    'class'         => array('form-row privacy'),
    'label_class'   => array('woocommerce-form__label woocommerce-form__label-for-checkbox checkbox'),
    'input_class'   => array('woocommerce-form__input woocommerce-form__input-checkbox input-checkbox'),
    'required'      => true,
    'label'         => 'I agree to the terms of purchase.',
  ));
}

// Show notice if customer does not tick

//add_action('woocommerce_checkout_process', 'sm_not_approved_terms');


function sm_not_approved_terms()
{
  // print_r($_POST);die;

  if ($_POST['payment_method'] != 'kco') {
    if (!(int) isset($_POST['privacy_policy'])) {
      wc_add_notice(__('Please acknowledge the terms'), 'error');
    } else {
      $_POST['terms'] = 'on';
    }
  }
}

/**
 * Adds custom field for Product
 * @return [type] [description]
 */
function wdm_add_custom_fields()
{

  global $product;
  // $custom_made_info = get_post_meta($product->get_id(), 'custom_made_info', true);
  
  $product_id = $product->get_id();
  $custom_made_info = get_post_meta( $product_id, '_product_attributes' );
  ob_start();
//   if ($custom_made_info != true) {
  ?>
<input type="hidden" id="main_shirt_length" name="main_shirt_length" value="30" />
<input type="hidden" id="main_left_sleeve" name="main_left_sleeve" value="40" />
<input type="hidden" id="suits_length" name="suits_length" value="50" />
<input type="hidden" id="main_right_sleeve" name="main_right_sleeve" value="60" />
<input type="hidden" id="main_half_wrist" name="main_half_wrist" value="70" />
<input type="hidden" id="main_half_chest" name="main_half_chest" value="80" />
<input type="hidden" id="main_half_bottom" name="main_half_bottom" value="90" />
<input type="hidden" id="main_shoulder" name="main_shoulder" value="100" />
<input type="hidden" id="main_arm_hole" name="main_arm_hole" value="20" />
<input type="hidden" id="main_neck_collar" name="main_neck_collar" value="30" />
<input type="hidden" id="made_info" name="made_info" value="0" />
<?php
if (get_field('add_fabric_type') === true) {
    if ($fabric_types = get_field('fabric_type')) {
        // Find the first fabric with 'make_it_defualt' set to 'Default'
        foreach ($fabric_types as $fabric) {
            if (isset($fabric['make_it_defualt'][0]) && $fabric['make_it_defualt'][0] === 'Default') {
                $selectedFabric = [
                    'name' => $fabric['fabric_name'],
                    'image' => $fabric['fabric_image']['sizes']['large']
                ];
                break; // Stop the loop once a selected fabric is found
            }
        }
    }
}

  ?>
<input type="hidden" value='<?php echo (!empty($selectedFabric)) ? esc_attr(json_encode($selectedFabric)) : 0; ?>' name="custom_color" class="selected_vc"/>
<input type="hidden" id="front_unit" name="front_unit" value="cm" />
<?php
  $content = ob_get_contents();
  ob_end_flush();

  return $content;
}

function cm2inches($cm)
{
  $inches = $cm;// / 2.54;
  // $inches = $inches%12;
  return $inches;
}
function plugin_republic_add_cart_item_data($cart_item_data, $product_id, $variation_id)
{
  // echo "<pre>";
  // print_r($_POST);die;
  


  if (!$cart_item_data['customize_product']) {
    $delivery_days =  get_post_meta($product_id, 'delivery_days', true);
    if ($delivery_days) {
      $cart_item_data['delivery_days'] =  $delivery_days;
    }
  }
  if (isset($_POST['front_unit'])) {
    $cart_item_data['main_front_unit'] = sanitize_text_field($_POST['front_unit']);
  }
  if (isset($_POST['made_info']) && !empty($_POST['made_info'])) {
    if (isset($_POST['front_unit'])) {
      $main_front_unit = $_POST['front_unit'];
    }
    if (isset($_POST['main_shirt_length']) && $_POST['main_shirt_length'] !='0' && $_POST['main_shirt_length'] !='NaN' ) {
      if ($main_front_unit == 'inch') {
        $shirt_length =  round(cm2inches($_POST['main_shirt_length']), 2);
      } else {
        $shirt_length = $_POST['main_shirt_length'];
      }
      $cart_item_data['shirt_length'] = sanitize_text_field($shirt_length);
    }
    if (isset($_POST['suits_length']) && $_POST['suits_length'] !='0' && $_POST['suits_length'] !='NaN') {

      if ($main_front_unit == 'inch') {
        $suits_length  =  round(cm2inches($_POST['suits_length']), 2);
      } else {
        $suits_length = $_POST['suits_length'];
      }
      $cart_item_data['suits_length'] = sanitize_text_field($suits_length);
    }
    if (isset($_POST['main_left_sleeve']) && $_POST['main_left_sleeve'] !='0' && $_POST['main_left_sleeve'] !='NaN' ) {

      if ($main_front_unit == 'inch') {
        $left_sleeve_length =  round(cm2inches($_POST['main_left_sleeve']), 2);
      } else {
        $left_sleeve_length = $_POST['main_left_sleeve'];
      }
      $cart_item_data['left_sleeve_length'] = sanitize_text_field($left_sleeve_length);
    }
    if (isset($_POST['main_right_sleeve']) && $_POST['main_right_sleeve'] !='0' && $_POST['main_right_sleeve'] !='NaN'  ) {

      if ($main_front_unit == 'inch') {
        $main_right_sleeve =  round(cm2inches($_POST['main_right_sleeve']), 2);
      } else {
        $main_right_sleeve = $_POST['main_right_sleeve'];
      }
      $cart_item_data['right_sleeve_length'] = sanitize_text_field($main_right_sleeve);
    }

    if (isset($_POST['main_half_wrist']) && $_POST['main_half_wrist'] !='0' && $_POST['main_half_wrist'] !='NaN' ) {

      if ($main_front_unit == 'inch') {
        $main_right_sleeve =  round(cm2inches($_POST['main_half_wrist']), 2);
      } else {
        $main_right_sleeve = $_POST['main_half_wrist'];
      }
      $cart_item_data['half_wrist'] = sanitize_text_field($main_right_sleeve);
    }

    if (isset($_POST['main_half_chest']) && $_POST['main_half_chest'] !='0' && $_POST['main_half_chest'] !='NaN' ) {

      if ($main_front_unit == 'inch') {
        $main_right_sleeve =  round(cm2inches($_POST['main_half_chest']), 2);
      } else {
        $main_right_sleeve = $_POST['main_half_chest'];
      }
      $cart_item_data['half_chest'] = sanitize_text_field($main_right_sleeve);
    }
    
    if (isset($_POST['main_half_hip']) && $_POST['main_half_hip'] !='0' && $_POST['main_half_hip'] !='NaN' ) {

      if ($main_front_unit == 'inch') {
        $main_right_sleeve =  round(cm2inches($_POST['main_half_hip']), 2);
      } else {
        $main_right_sleeve = $_POST['main_half_hip'];
      }
      $cart_item_data['half_hip'] = sanitize_text_field($main_right_sleeve);
    }

    if (isset($_POST['main_shoulder']) && $_POST['main_shoulder'] !='0' && $_POST['main_shoulder'] !='NaN' ) {

      if ($main_front_unit == 'inch') {
        $main_right_sleeve =  round(cm2inches($_POST['main_shoulder']), 2);
      } else {
        $main_right_sleeve = $_POST['main_shoulder'];
      }
      $cart_item_data['shoulder'] = sanitize_text_field($main_right_sleeve);
    }

    
    if (isset($_POST['main_arm_hole']) && $_POST['main_arm_hole'] !='0' && $_POST['main_arm_hole'] !='NaN' ) {

      if ($main_front_unit == 'inch') {
        $main_right_sleeve =  round(cm2inches($_POST['main_arm_hole']), 2);
      } else {
        $main_right_sleeve = $_POST['main_arm_hole'];
      }
      $cart_item_data['arm_hole'] = sanitize_text_field($main_right_sleeve);
    }
  }

  return $cart_item_data;
}
add_action('woocommerce_before_add_to_cart_button', 'wdm_add_custom_fields', 10, 0);
add_filter('woocommerce_add_cart_item_data', 'plugin_republic_add_cart_item_data', 10, 3);

add_filter('woocommerce_add_cart_item', 'filter_add_cart_item', 10, 2);
function filter_add_cart_item($cart_item_data, $cart_item_key)
{


  if ($cart_item_data['main_front_unit'] == 'inch') {
    if (isset($cart_item_data['variation']['attribute_pa_size'])) {

      $attribute_pa_size = round(cm2inches($cart_item_data['variation']['attribute_pa_size']), 2);
      // Changing the term slug for product attribute "Size" from "cm" to "inch"
      $cart_item_data['variation']['attribute_pa_size'] = $attribute_pa_size . ' ' . $cart_item_data['main_front_unit'];
    }
  } else if ($cart_item_data['main_front_unit'] == 'cm') {

    if (isset($cart_item_data['variation']['attribute_pa_size'])) {
      $cart_item_data['variation']['attribute_pa_size'] = $cart_item_data['variation']['attribute_pa_size'] . ' ' . $cart_item_data['main_front_unit'];
    }
  }

  return $cart_item_data;
}
add_filter('woocommerce_product_variation_title_include_attributes', '__return_false');


//make shipping fields not required in checkout
add_filter('woocommerce_shipping_fields', 'sm_npr_filter_shipping_fields', 99, 1);
function sm_npr_filter_shipping_fields($address_fields)
{
  $address_fields['shipping_first_name']['required'] = false;
  $address_fields['shipping_last_name']['required'] = false;
  $address_fields['shipping_address_1']['required'] = false;
  $address_fields['shipping_address_2']['required'] = false;
  $address_fields['shipping_city']['required'] = false;
  $address_fields['shipping_country']['required'] = false;
  $address_fields['shipping_postcode']['required'] = false;
  $address_fields['shipping_state']['required'] = false;
  return $address_fields;
}


function my_custom_filename($dir, $name, $ext)
{
  $user = wp_get_current_user();
  $user_id = get_current_user_id();

  /* You wanted to add display_lastname, but its not required by WP so might not exist. 
       If it doesn't use their username instead: */
  $username = $user->display_lastname;
  if (!$username)  $username = $user->user_login;

  $newfilename =  $username . "_" . rand(1, 1000) . "_" . $name;  /* prepend username to filename */

  /* any other code you need to do, e.g. ensure the filename is unique, remove spaces from username etc */

  return $newfilename;
}

add_action('admin_footer', 'sm_size_image_upload');

function sm_size_image_upload()
{
  global $pagenow, $post_type;

?>
<style type="text/css">
a.misha-upl img {
    max-width: 100px;
}
</style>
<script type="text/javascript">
  jQuery('.logo_file').change(function(e) {
      var geekss = e.target.files[0].name;
      console.log(geekss);
  });
  
jQuery(function($) {
    // on upload button click
    $('body').on('click', '.misha-upl', function(e) {

        e.preventDefault();
        // $('.woocommerce_variation.wc-metabox').removeClass('variation-needs-update');
        var button = $(this),
            custom_uploader = wp.media({
                title: 'Insert image',
                library: {
                    // uploadedTo : wp.media.view.settings.post.id, // attach to the current post?
                    type: 'image'
                },
                button: {
                    text: 'Use this image' // button label text
                },
                multiple: false
            }).on('select', function() { // it also has "open" and "close" events
                var attachment = custom_uploader.state().get('selection').first().toJSON();
                console.log(attachment);
                button.html('<img src="' + attachment.url + '">').next().val(attachment.id).change()
                    .next().show();

            }).open();

    });

    // on remove button click
    $('body').on('click', '.misha-rmv', function(e) {

        e.preventDefault();
        // $('.woocommerce_variation.wc-metabox').removeClass('variation-needs-update');
        var button = $(this);
        button.prev().val('').change(); // emptying the hidden field
        button.hide().prev().prev().html('Upload Size Gide image');

    });
});
</script>
<?php

}

add_filter('woocommerce_add_to_cart_fragments', 'sm_cart_count_fragments', 10, 1);




function sm_cart_count_fragments($fragments)
{

  $fragments['span.header-cart-count'] = '<span class="header-cart-count">' . WC()->cart->get_cart_contents_count() . '</span>';

  return $fragments;
}


add_filter('woocommerce_cart_item_thumbnail', 'sm_product_image_on_checkout', 10, 3);

function sm_product_image_on_checkout($product_get_image, $cart_item, $cart_item_key)
{

  if (isset($cart_item['main_textile_imag'])) {
    /* Get product thumbnail */
    $thumbnail =   '<img src="' . $cart_item['main_textile_imag'] . '" />';

    /* Add wrapper to image and add some css */
    $product_get_image = '<div class="sm-product-image">'
      . $thumbnail .
      '</div>';
  }

  /* Prepend image to name and return it */
  return $product_get_image;
}

add_filter('woocommerce_order_item_name', 'sm_product_image_on_order_pay', 10, 3);
// add_filter( 'woocommerce_order_item_thumbnail', 'sm_product_image_on_order_pay', 10, 3 );
function sm_product_image_on_order_pay($name, $item, $extra)
{


  if ($item['main_textile_imag']) {
    /* Get product thumbnail */
    $thumbnail =   '<img src="' . $item['main_textile_imag'] . '" />';
  } else {
    $product_id = $item->get_product_id();
    /* Get product object */
    $_product = wc_get_product($product_id);
    /* Get product thumbnail */
    $thumbnail = $_product->get_image();
  }

  /* Add wrapper to image and add some css */
  $image = '<div class="sm-product-image" style="width: 52px; display: inline-block; padding-right: 7px; vertical-align: middle;">'
    . $thumbnail .
    '</div>';

  /* Prepend image to name and return it */
  return $image . $name;
}

// add the filter 
add_filter('woocommerce_admin_order_item_thumbnail', 'sm_woocommerce_admin_order_item_thumbnail', 10, 3);
function sm_woocommerce_admin_order_item_thumbnail($product_image, $item_id, $item)
{
  $imag_url = wc_get_order_item_meta($item_id, '_main_textile_imag');
  if ($imag_url) {
    $product_image = '<img src="' . $imag_url . '" />';
  }

  return $product_image;
}
function sm_woocommerce_hidden_order_itemmeta($arr)
{
  $arr[] = '_main_textile_imag';
  return $arr;
}

add_filter('woocommerce_hidden_order_itemmeta', 'sm_woocommerce_hidden_order_itemmeta', 10, 1);

add_action('woocommerce_product_thumbnails', 'sm_show_product_thumbnails_details', 20);
function sm_show_product_thumbnails_details()
{
  echo "<div class='custom_made_warning' style='display: none;'>
  <p>Every tailor made and made to measure products is 100% individually customize according to customers preferences. Hence, we DO NOT accept returns for any reason except production error.</p></div>";
  echo '<div class="custom_made_note" style="display: none;"><div class="custom_made_warning_inside"><h4>For custom-made shirt length:</h4>
<p> - It´ll take 5 (five) Working days extra, addition to normal delivery time.</p>
<p> - We do not charge extra to adjust your length.</p></div></div>';
}
add_action('woocommerce_no_products_found', function () {
  $term = get_queried_object();

  remove_action('woocommerce_no_products_found', 'wc_no_products_found', 10);

  // HERE change your message below
  $message = __('Products are coming soon.', 'woocommerce');

  echo '<p class="woocommerce-info">' . $message . '</p>';
}, 9);

add_filter('woocommerce_product_related_products_heading', function () {
if(isset($_GET['lang']) && $_GET['lang']=='en'):
  return 'SHIRTS - Our recommendations';
else:
  return 'Skjorter: Våre anbefalinger';
endif;
});



// Save registration checkbox field value
add_action('woocommerce_created_customer', 'save_account_registration_field');
function save_account_registration_field($customer_id)
{
  $value = isset($_POST['mailchimp_woocommerce_newsletter']) ? '1' : '0';
  update_user_meta($customer_id, 'mailchimp_woocommerce_is_subscribed', $value);
}

?>