<?php  

function get_variation_custom_size(){
    // $variation = get_post_meta($variation_id, 'custom_field');
    // echo $variation[0];
    global $post,$wpdb;
    $id = $_POST['post_id'];
    $variation_id = $_POST['variation_id'];
    $terms = get_the_terms( $id, 'product_cat' );
    $all_cat_slug = [];
    foreach ($terms as $term) {
        $all_cat_slug[] = $term->slug;
    }
    $variation = wc_get_product($variation_id);
    $variation_attributes = $variation->get_variation_attributes();      

    if(in_array('suits', $all_cat_slug))
    {
    $form_type = 'form_submit2';
    }
    else
    {
      $form_type = 'form_submit1';
    }
   
    $tablename = 'addcustomproductvariations';
    if ($variation_attributes['attribute_pa_size']==true) {
     $result = $wpdb->get_results( "SELECT suits_length,shirt_length, sleeve_length, half_chest, half_wrist, half_hip, shoulder, arm_hole FROM $tablename WHERE form_type='$form_type' AND body_type='".$variation_attributes['attribute_pa_body-fit']."' AND size='".$variation_attributes['attribute_pa_size']."'" , ARRAY_A);
    }
    else{
      $result = $wpdb->get_results( "SELECT suits_length,shirt_length, sleeve_length, half_chest, half_wrist, half_hip, shoulder, arm_hole  FROM $tablename WHERE form_type='$form_type' AND body_type='".$variation_attributes['attribute_pa_body-fit']."' AND size='".$variation_attributes['attribute_pa_size-suits']."'" , ARRAY_A);

    }
    $count = count($result);
    if($count != 0){
      echo json_encode(array('message' => 'found', 'data' => $result[0] ));
      wp_die();
    }
    else
    {
      echo json_encode(array('message' => 'not_foundsdfs' ));
      wp_die();
    }
    
  }
