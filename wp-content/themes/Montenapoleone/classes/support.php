<?php
ini_set('display_errors', 1); 
error_reporting(E_ALL);
// require_once('class-product-helper.php');

// $variation = get_post_meta($variation_id, 'custom_field');
    // echo $variation[0];
    
// add_action( 'woocommerce_init', 'get_variation_custom_size' );
function get_variation_custom_size(){
    global $post;
    $id = $_POST['post_id'];
    $variation_id = $_POST['variation_id'];
    // $terms = get_the_terms( $id, 'product_cat' );
    echo "<pre> POST ID:";
    print_r($id);
    echo "</pre>";
    echo "<pre> VARIATION ID:";
    print_r($variation_id);
    echo "</pre>";
    // $all_cat_slug = [];
    // foreach ($terms as $term) {
        // $all_cat_slug[] = $term->slug;
    // }
    // $product = new WC_Product( get_the_ID() );
    // echo "<pre> Product ID:";
    // print_r($product);
    // echo "</pre>";
    // // die();
    // $product = WC_Product($variation_id);
    // $product->wc_get_product();
    $variation = wc_get_product($variation_id);
    $variation_attributes = $variation->get_variation_attributes();      

    // if(in_array('suits', $all_cat_slug))
    // {
    // $form_type = 'form_submit2';
    // }
    // else
    // {
      $form_type = 'form_submit1';
    // }
    global $wpdb;
    $tablename = 'addcustomproductvariations';
    if ($variation_attributes['attribute_pa_size']==true) {
     $result = $wpdb->get_results( "SELECT suits_length,shirt_length, sleeve_length, half_chest, half_wrist,  half_hip, shoulder, arm_hole  FROM $tablename WHERE form_type='$form_type' AND body_type='".$variation_attributes['attribute_pa_body-fit']."' AND size='".$variation_attributes['attribute_pa_size']."'" , ARRAY_A);
    }
    else{
           $result = $wpdb->get_results( "SELECT suits_length,shirt_length, sleeve_length, half_chest, half_wrist,  half_hip, shoulder, arm_hole  FROM $tablename WHERE form_type='$form_type' AND body_type='".$variation_attributes['attribute_pa_body-fit']."' AND size='".$variation_attributes['attribute_pa_size-suits']."'" , ARRAY_A);

    }
    $count = count($result);
    if($count > 0){
      $array = array('message' => 'found', 'data' => $result[0] );
    }
    else
    {
      $array = array('message' => 'not_found' );
    }
    echo json_encode($array);
    die();
}
     ?><?php
ini_set('display_errors', 1); 
error_reporting(E_ALL);
// require_once('class-product-helper.php');

// $variation = get_post_meta($variation_id, 'custom_field');
    // echo $variation[0];
    
// add_action( 'woocommerce_init', 'get_variation_custom_size' );
function get_variation_custom_size(){
    global $post;
    $id = $_POST['post_id'];
    $variation_id = $_POST['variation_id'];
    // $terms = get_the_terms( $id, 'product_cat' );
    echo "<pre> POST ID:";
    print_r($id);
    echo "</pre>";
    echo "<pre> VARIATION ID:";
    print_r($variation_id);
    echo "</pre>";
    // $all_cat_slug = [];
    // foreach ($terms as $term) {
        // $all_cat_slug[] = $term->slug;
    // }
    // $product = new WC_Product( get_the_ID() );
    // echo "<pre> Product ID:";
    // print_r($product);
    // echo "</pre>";
    // // die();
    // $product = WC_Product($variation_id);
    // $product->wc_get_product();
    $variation = wc_get_product($variation_id);
    $variation_attributes = $variation->get_variation_attributes();      

    // if(in_array('suits', $all_cat_slug))
    // {
    // $form_type = 'form_submit2';
    // }
    // else
    // {
      $form_type = 'form_submit1';
    // }
    global $wpdb;
    $tablename = 'addcustomproductvariations';
    if ($variation_attributes['attribute_pa_size']==true) {
     $result = $wpdb->get_results( "SELECT suits_length,shirt_length, sleeve_length, half_chest, half_wrist, shoulder FROM $tablename WHERE form_type='$form_type' AND body_type='".$variation_attributes['attribute_pa_body-fit']."' AND size='".$variation_attributes['attribute_pa_size']."'" , ARRAY_A);
    }
    else{
           $result = $wpdb->get_results( "SELECT suits_length,shirt_length, sleeve_length, half_chest, half_wrist, shoulder FROM $tablename WHERE form_type='$form_type' AND body_type='".$variation_attributes['attribute_pa_body-fit']."' AND size='".$variation_attributes['attribute_pa_size-suits']."'" , ARRAY_A);

    }
    $count = count($result);
    if($count > 0){
      $array = array('message' => 'found', 'data' => $result[0] );
    }
    else
    {
      $array = array('message' => 'not_found' );
    }
    echo json_encode($array);
    die();
}
     ?>