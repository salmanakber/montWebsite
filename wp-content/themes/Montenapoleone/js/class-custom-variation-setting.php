<?php

/**
 * Product_Custom_Setting_Helper
 *
 * The Product_Custom_Setting_Helper Class.
 *
 * @class    Product_Custom_Setting_Helper
 * @category Class
 * @author   Codingkart
 */
class Product_Custom_Setting_Helper
{
	public function __construct() {
		add_action('admin_menu', array($this, 'custom_add_options') );
		add_action('wp_ajax_form_submit1', array($this, 'Custom_Variation_callback1'));
		add_action('wp_ajax_form_submit2', array($this, 'Custom_Variation_callback2'));
		add_action('wp_ajax_check_variation', array($this, 'Custom_check_variation'));
	}

	public function Custom_check_variation($value='')
	{
		# code...
		global $wpdb;
		$tablename = 'addcustomproductvariations';
		$result = $wpdb->get_results( "SELECT * FROM $tablename WHERE form_type='".$_POST['form_type']."' AND body_type='".$_POST['body_type']."' AND size='".$_POST['size']."'" , ARRAY_A);
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

	public function Custom_Variation_callback1()
	{
		# code...
		// echo "<pre>";
		// print_r($_POST);
		// print_r($_FILES);
		// echo "</pre>";
		// die();
		global $wpdb;
		$tablename = 'addcustomproductvariations';

		// if(isset($_FILES)){
		// 	$uploaddir = wp_upload_dir();
		// 	$files_name = [];
		// 	foreach ($_FILES as $key => $value) {
		// 		# code...
		// 		$files_name[$key] = basename($_FILES[$key]['name']);
		// 		$uploadfile = $uploaddir['basedir'] . '/customsizeguid/' . basename($_FILES[$key]['name']);
		// 		move_uploaded_file($_FILES[$key]["tmp_name"], $uploadfile);
		// 	}
		// }

		$size_guide_cm = $_POST['hidden_shirt_size_guide_cm'];
		$size_guide_in = $_POST['hidden_shirts_size_guide_in'];
		$files_name['shirts_size_guide_cm'] = $_POST['shirts_size_guide_cm'];
		$files_name['shirts_size_guide_in'] = $_POST['shirts_size_guide_in'];
		
		if($files_name['shirts_size_guide_cm'] !='' ){
			$size_guide_cm = $files_name['shirts_size_guide_cm'];			
		}
		if($files_name['shirts_size_guide_in'] !='')
		{
			$size_guide_in = $files_name['shirts_size_guide_in'];
		}
		

		if(isset($_POST)){

			$result = $wpdb->get_results( "SELECT * FROM $tablename WHERE form_type='".$_POST['action']."' AND body_type='".$_POST['shirts_body_type']."' AND size='".$_POST['shirts_size']."'" , ARRAY_A);
			$count = count($result);
			if($count > 0){

				$record_id = $result[0]['ID'];
				$wpdb->update($tablename, array( 
				        'form_type'     => $_POST['action'],
				        'body_type'    => $_POST['shirts_body_type'],
				        'size' => $_POST['shirts_size'],
				        'size_guide_cm'   => $size_guide_cm,
				        'size_guide_in'      => $size_guide_in,
				        'shirt_length' => $_POST['shirts_input_shirt_length'],
				        'sleeve_length' => $_POST['shirts_input_sleeve_length']
				    ), array('ID'=>$record_id));
			}
			else
			{				
				$wpdb->insert( 
				    $tablename, 
				    array( 
				        'form_type'     => $_POST['action'],
				        'body_type'    => $_POST['shirts_body_type'],
				        'size' => $_POST['shirts_size'],
				        'size_guide_cm'   => $files_name['shirts_size_guide_cm'],
				        'size_guide_in'      => $files_name['shirts_size_guide_in'],
				        'shirt_length' => $_POST['shirts_input_shirt_length'],
				        'sleeve_length' => $_POST['shirts_input_sleeve_length']
				    )
				);
				$record_id = $wpdb->insert_id;
				
			}
			echo  $wpdb->last_query;
				die;
			//echo $record_id;
		}
		die();
	}

	public function Custom_Variation_callback2()
	{
		# code...
		// echo "<pre>";
		// print_r($_POST);
		// print_r($_FILES);
		// echo "</pre>";
		// die();
		global $wpdb;
		$tablename = 'addcustomproductvariations';

		// if(isset($_FILES)){
		// 	$uploaddir = wp_upload_dir();
		// 	$files_name = [];
		// 	foreach ($_FILES as $key => $value) {
		// 		# code...
		// 		$files_name[$key] = basename($_FILES[$key]['name']);
		// 		$uploadfile = $uploaddir['basedir'] . '/customsizeguid/' . basename($_FILES[$key]['name']);
		// 		move_uploaded_file($_FILES[$key]["tmp_name"], $uploadfile);
		// 	}
		// }
		$files_name['suits_size_guide_cm'] = $_POST['suits_size_guide_cm'];
		$files_name['suits_size_guide_in'] = $_POST['suits_size_guide_in'];
		$size_guide_cm = $_POST['hidden_suits_size_guide_cm'];
		$size_guide_in = $_POST['hidden_suits_size_guide_in'];
		
		if($files_name['suits_size_guide_cm'] !='' ){
			$size_guide_cm = $files_name['suits_size_guide_cm'];			
		}
		if($files_name['suits_size_guide_in'] !='')
		{
			$size_guide_in = $files_name['suits_size_guide_in'];
		}
		

		if(isset($_POST)){

			$result = $wpdb->get_results( "SELECT * FROM $tablename WHERE form_type='".$_POST['action']."' AND body_type='".$_POST['suits_body_type']."' AND size='".$_POST['suits_size']."'" , ARRAY_A);
			$count = count($result);

			if($count > 0){

				$record_id = $result[0]['ID'];
				$wpdb->update($tablename, array( 
				        'form_type'     => $_POST['action'],
				        'body_type'    => $_POST['suits_body_type'],
				        'size' => $_POST['suits_size'],
				        'size_guide_cm'   => $size_guide_cm,
				        'size_guide_in'      => $size_guide_in,
				        'sleeve_length' => $_POST['suits_input_sleeve_length'],
				        'half_chest' => $_POST['suits_input_half_chest'],
				        'half_wrist' => $_POST['suits_input_half_wrist'],
				        'shoulder' => $_POST['suits_input_shoulder']
				    ), array('ID'=>$record_id));
			}
			else
			{				
				$wpdb->insert( 
				    $tablename, 
				    array( 
				        'form_type'     => $_POST['action'],
				        'body_type'    => $_POST['suits_body_type'],
				        'size' => $_POST['suits_size'],
				        'size_guide_cm'   => $size_guide_cm,
				        'size_guide_in'      => $size_guide_in,
				        'sleeve_length' => $_POST['suits_input_sleeve_length'],
				        'half_chest' => $_POST['suits_input_half_chest'],
				        'half_wrist' => $_POST['suits_input_half_wrist'],
				        'shoulder' => $_POST['suits_input_shoulder']
				    )
				);
				$record_id = $wpdb->insert_id;
			}
			echo $record_id;
		}
		die();
	}


	function custom_add_options() {
	  add_options_page('Custom Add Variations', 'Custom Add Variations', 'manage_options', 'addcustom', array(
                $this,'addcustom_options_page') );
	}

	function addcustom_options_page()
	{
		# code...

		?>
		<?php screen_icon(); ?>
  		<h2>Add Custom Variations</h2>
  		<style type="text/css">
  			#tabs {
			   width: 100%;
			   height:30px; 
			   border-bottom: solid 1px #CCC;
			   padding-right: 2px;
			   margin-top: 30px;
			}
			a {cursor:pointer;}
			#tabs li {
			    float:left; 
			    list-style:none; 
			    border-top:1px solid #ccc; 
			    border-left:1px solid #ccc; 
			    border-right:1px solid #ccc; 
			    margin-right:5px; 
			    border-top-left-radius:3px;  
			    border-top-right-radius:3px;
			    outline:none;
			}
			#tabs li a {
				font-family:Arial, Helvetica, sans-serif; 
				font-size: small;
				font-weight: bold; 
				color: #5685bc;;
				padding-top: 5px;
				padding-left: 7px;
				padding-right: 7px;
				padding-bottom: 8px; 
				display:block; 
				background: #FFF;
				border-top-left-radius:3px; 
				border-top-right-radius:3px; 
				text-decoration:none;
				outline:none;			  
			}
			#tabs li a.inactive{
				padding-top:5px;
				padding-bottom:8px;
				padding-left: 8px;
				padding-right: 8px;
				color:#666666;
				background: #EEE;
				outline:none;
				border-bottom: solid 1px #CCC;
			}
			#tabs li a:hover, #tabs li a.inactive:hover {
				color: #5685bc;
				outline:none;
			}
			.container {
				clear:both;           
				width:100%; 
				border-left: solid 1px #CCC;
				border-right: solid 1px #CCC;
				border-bottom: solid 1px #CCC;
				text-align:left;
				padding-top: 20px;
			}
			.container h2 { margin-left: 15px;  margin-right: 15px;  margin-bottom: 10px; color: #5685bc; }
			.container p { margin-left: 15px; margin-right: 15px;  margin-top: 10px; margin-bottom: 10px; line-height: 1.3; font-size: small; }
			.container ul { margin-left: 25px; font-size: small; line-height: 1.4; list-style-type: disc; }
			.container li { padding-bottom: 5px; margin-left: 5px;}

			/* Create three equal columns that floats next to each other */
			.column {
			  float: left;
			  width: 30.33%;
			  padding: 10px;
			  height: auto; /* Should be removed. Only for demonstration */
			}

			/* Clear floats after the columns */
			.row:after {
			  content: "";
			  display: table;
			  clear: both;
			}

			label {
				font-weight: bold;
			}

			.form-control{
				width: 100%;
			}

			/* Chrome, Safari, Edge, Opera */
			input::-webkit-outer-spin-button,
			input::-webkit-inner-spin-button {
			  -webkit-appearance: none;
			  margin: 0;
			}

			/* Firefox */
			input[type=number] {
			  -moz-appearance: textfield;
			}
  		</style>
		<script>
		jQuery("document").ready(function() {
			jQuery('#tabs li a:not(:first)').addClass('inactive');
			jQuery('.container').hide();
			jQuery('.container:first').show();
			    
			jQuery('#tabs li a').click(function(){
			    var t = jQuery(this).attr('id');
				  if(jQuery(this).hasClass('inactive')){ //this is the start of our condition 
				    jQuery('#tabs li a').addClass('inactive');           
				    jQuery(this).removeClass('inactive');
				    
				    jQuery('.container').hide();
				    jQuery('#'+ t + 'C').fadeIn('slow');
				 }
			});

			jQuery(".add").click(function () {
		      //if ($(this).prev().val() < 3) {
		      jQuery(this)
		        .prev()
		        .val(+jQuery(this).prev().val() + 1);
		      jQuery(this).parent().find(".qty_text").val(jQuery(this).prev().val());
		      //}
		      
		    });


			jQuery(".sub").click(function () {
		      // if ($(this).next().val() > 1) {
		      jQuery(this)
		        .next()
		        .val(+jQuery(this).next().val() - 1);
		      jQuery(this).parent().find(".qty_text").val(jQuery(this).next().val());
		      // }
		    });

		});

		jQuery(document).on('change', '#shirts_size', function(e){
			e.preventDefault();
			var ajax_url= jQuery('#shirts_submit').attr('data-url');
			var size = jQuery(this).val();
			var body_type = jQuery('#shirts_body_type').val();
			var form_type = jQuery('#shirts_submit').attr('data-action');
			jQuery.ajax({
	            url : ajax_url,
	            type : 'post',
	            data : {
	                action : 'check_variation',
	                size : size,
	                body_type : body_type,
	                form_type : form_type
	            },
	            success : function( response ) {
	            	var obj = jQuery.parseJSON( response );
	                console.log(obj.message);
	                if(obj.message == 'found'){
	                	jQuery('#hidden_shirt_size_guide_cm').val(obj.data.size_guide_cm);
	                	var image_cm = '<img src='+obj.data.size_guide_cm+' alt="cm" width="100" height="100">';
	                	jQuery('#show_cm').html(image_cm);
						if (jQuery('.shirts_cm_image').is(':empty')){
							jQuery('#show_cm').show();
						}
						if (jQuery('.shirts_in_image').is(':empty')){
							jQuery('#show_in').show();
						}
	                	jQuery('#hidden_shirts_size_guide_in').val(obj.data.size_guide_in);
	                	var image_in = '<img src='+obj.data.size_guide_in+' alt="in" width="100" height="100">';
	                	jQuery('#show_in').html(image_in);

	                	jQuery('#shirts_input_shirt_length').val(obj.data.shirt_length);
	                	jQuery('#shirts_input_sleeve_length').val(obj.data.sleeve_length);
	                }
	            }
	        });
		});

		jQuery(document).on('change', '#suits_size', function(e){
			e.preventDefault();
			var ajax_url= jQuery('#suits_submit').attr('data-url');
			var size = jQuery(this).val();
			var body_type = jQuery('#suits_body_type').val();
			var form_type = jQuery('#suits_submit').attr('data-action');
			jQuery.ajax({
	            url : ajax_url,
	            type : 'post',
	            data : {
	                action : 'check_variation',
	                size : size,
	                body_type : body_type,
	                form_type : form_type
	            },
	            success : function( response ) {
	            	var obj = jQuery.parseJSON( response );
	                console.log(obj.message);
	                if(obj.message == 'found'){
	                	jQuery('#hidden_suits_size_guide_cm').val(obj.data.size_guide_cm);
	                	var image_cm = '<img src='+obj.data.size_guide_cm+' alt="cm" width="100" height="100">';
	                	jQuery('#suits_show_cm').html(image_cm);

	                	jQuery('#hidden_suits_size_guide_in').val(obj.data.size_guide_in);
	                	var image_in = '<img src='+obj.data.size_guide_in+' alt="in" width="100" height="100">';
	                	jQuery('#suits_show_in').html(image_in);

	                	jQuery('#suits_input_shirt_length').val(obj.data.shirt_length);
	                	jQuery('#suits_input_sleeve_length').val(obj.data.sleeve_length);

	                	jQuery('#suits_input_half_chest').val(obj.data.half_chest);
	                	jQuery('#suits_input_half_wrist').val(obj.data.half_wrist);
	                	jQuery('#suits_input_shoulder').val(obj.data.shoulder);


						if (jQuery('.suits_cm_image').is(':empty')){
							jQuery('#suits_show_cm').show();
						}
						if (jQuery('#suits_in_images').is(':empty')){
							jQuery('#suits_show_in').show();
						}

	                }
	            }
	        });
		});

		jQuery(document).ready(function ($) {
		    // when user submits the form
		    jQuery(document).on('submit', 'form#shirts_submit', function (event) {

		        var error_elm = jQuery('.ajax-error');
		        var response_elm = jQuery('.ajax-response');
		        error_elm.html('');
		        response_elm.html('');

		        event.preventDefault();

		        var form_elm = jQuery(this);
		        var url = form_elm.data('url');
		        var action = form_elm.data('action');
		        var shirts_body_type = form_elm[0][0].value;
		        var shirts_size = form_elm[0][1].value;

		        var hidden_shirt_size_guide_cm = jQuery('#hidden_shirt_size_guide_cm').val();
		        var hidden_shirts_size_guide_in = jQuery('#hidden_shirts_size_guide_in').val();
		     
		        // var shirts_size_guide_cm = form_elm[0][2].files[0];
		        // var shirts_size_guide_in = form_elm[0][4].files[0];

				var shirts_size_guide_cm = jQuery('#shirts_size_guide_cm').val();
		        var shirts_size_guide_in = jQuery('#shirts_size_guide_in').val();

		        var shirts_input_shirt_length = form_elm[0][9].value;
		        var shirts_input_sleeve_length = form_elm[0][12].value;
		        var form_data = new FormData();
		        form_data.append('action', action);
		        form_data.append('shirts_body_type', shirts_body_type);
		        form_data.append('shirts_size', shirts_size);
		        form_data.append('hidden_shirt_size_guide_cm', hidden_shirt_size_guide_cm);
		        form_data.append('hidden_shirts_size_guide_in', hidden_shirts_size_guide_in);
		        form_data.append('shirts_input_shirt_length', shirts_input_shirt_length);
		        form_data.append('shirts_input_sleeve_length', shirts_input_sleeve_length);
		        if(shirts_size_guide_cm != 'undefined'){
		        	form_data.append('shirts_size_guide_cm', shirts_size_guide_cm);
		        }
		        if(shirts_size_guide_in != 'undefined'){
		        	form_data.append('shirts_size_guide_in', shirts_size_guide_in);
		        }




		        // response_elm.html('Loading...');

		        jQuery.ajax({
		            type: 'POST',
		            url: url,
		            data: form_data,
		            processData: false,
		            contentType: false,
		            cache: false
		        }).success(function (response) {
		        	console.log('submit1');
		        	console.log(response);
		            location.reload();

		        }).error(function (response) {
		            error_elm.html('');
		            response_elm.html('');

		            error_elm.html(response.statusText);
		        });
		    });

		});

		jQuery(document).ready(function ($) {
		    // when user submits the form
		    jQuery(document).on('submit', 'form#suits_submit', function (event) {

		        var error_elm = jQuery('.ajax-error');
		        var response_elm = jQuery('.ajax-response');
		        error_elm.html('');
		        response_elm.html('');

		        event.preventDefault();

		        var form_elm = jQuery(this);
		        var url = form_elm.data('url');
		        var action = form_elm.data('action');
		        var suits_body_type = form_elm[0][0].value;
		        var suits_size = form_elm[0][1].value;

		        var hidden_suits_size_guide_cm = jQuery('#hidden_suits_size_guide_cm').val();
		        var hidden_suits_size_guide_in = jQuery('#hidden_suits_size_guide_in').val();

		        // var suits_size_guide_cm = form_elm[0][2].files[0];
		        // var suits_size_guide_in = form_elm[0][4].files[0];
                var suits_size_guide_cm = jQuery('#suits_size_guide_cm').val();;
		         var suits_size_guide_in = jQuery('#suits_size_guide_in').val();
		        var suits_input_sleeve_length = form_elm[0][9].value;
		        var suits_input_half_chest = form_elm[0][12].value;
		        var suits_input_half_wrist = form_elm[0][15].value;
		        var suits_input_shoulder = form_elm[0][18].value;
		        var form_data = new FormData();
		        form_data.append('action', action);
		        form_data.append('suits_body_type', suits_body_type);
		        form_data.append('suits_size', suits_size);
		        form_data.append('hidden_suits_size_guide_cm', hidden_suits_size_guide_cm);
		        form_data.append('hidden_suits_size_guide_in', hidden_suits_size_guide_in);
		        form_data.append('suits_input_sleeve_length', suits_input_sleeve_length);
		        form_data.append('suits_input_half_chest', suits_input_half_chest);
		        form_data.append('suits_input_half_wrist', suits_input_half_wrist);
		        form_data.append('suits_input_shoulder', suits_input_shoulder);
		        if(suits_size_guide_cm != 'undefined'){
		        	form_data.append('suits_size_guide_cm', suits_size_guide_cm);
		        }
		        if(suits_size_guide_in != 'undefined'){
		        	form_data.append('suits_size_guide_in', suits_size_guide_in);
		        }

		        // response_elm.html('Loading...');

		        jQuery.ajax({
		            type: 'POST',
		            url: url,
		            data: form_data,
		            processData: false,
		            contentType: false,
		            cache: false
		        }).success(function (response) {

		        	console.log(response);
		        	location.reload();
		            

		        }).error(function (response) {
		            error_elm.html('');
		            response_elm.html('');

		            error_elm.html(response.statusText);
		        });
			    
		    });

		});
		</script>

		<ul id="tabs">
		    <li><a id="shirts">SHIRTS</a></li>
		    <li><a id="suits">SUITS</a></li>
		</ul>
		<div class="container" id="shirtsC">
			<form id="shirts_submit" method="post" enctype="multipart/form-data" data-url="<?php echo esc_url(admin_url('admin-ajax.php')) ?>" data-action="form_submit1">
				<div class="row">
					<div class="column">
						<label>Select Body Type</label><br>
						<select name="shirts_body_type" id="shirts_body_type" class="form-control" required>
						<option value="">--Select Body Type--</option>
						<?php 
						$bodyfit = $this->get_product_attributes('pa_body-fit');
						foreach ($bodyfit as $key => $value) {
						?>
						<option value="<?php echo $value->slug; ?>"><?php echo $value->name; ?></option>
						<?php }	?>
						</select>
					</div>

					<div class="column">
						<label>Select Size</label><br>
						<select name="shirts_size" id="shirts_size" class="form-control" required>
						<option value="">--Select Size--</option>
						<?php 
						$size = $this->get_product_attributes('pa_size');
						foreach ($size as $key => $value) {
						?>
						<option value="<?php echo $value->slug; ?>"><?php echo $value->name; ?></option>
						<?php }	?>
						</select>
					</div>
				</div>

				<div class="row">
					<div class="column">
						<label>Size Guide (CM)</label><br>
						<!-- <input type="file" id="shirts_size_guide_cm" name="shirts_size_guide_cm"> -->
						<input type="hidden" id="hidden_shirt_size_guide_cm"/>
						<input type="hidden"  id="shirts_size_guide_cm" name="shirts_size_guide_cm"/>
                         <input id="wk-button_shirts_cm" type="button" class="button" value="Upload Image" />
						 <span class="shirts_cm_image"></span>

						<span id="show_cm"></span>
					</div>

					<div class="column">
						<label>Size Guide (IN)</label><br>
						<input type="hidden" id="shirts_size_guide_in" name="shirts_size_guide_in"/>
						<input type="hidden" id="hidden_shirts_size_guide_in"/>
						<input id="wk-button_shirts_in" type="button" class="button" value="Upload Image" />
						<span class="shirts_in_image"></span>

						<span id="show_in"></span>
					</div>
				</div>

				<div class="row">
					<div class="column">
						<label>Shirt Length</label><br>
						<div class="qty_box">
			                <button type="button" id="sub" class="sub value-button">-</button>
			                <input type="number"  id="shirts_input_shirt_length" class="qty_text" name="shirts_input_shirt_length" value="0" step="0.01" pattern="^\d*(\.\d{0,2})?$">
			                <button type="button" id="add" class="add value-button">+</button>
			            </div>
					</div>

					<div class="column">
						<label>Sleeve Length</label><br>
						<div class="qty_box">
			                <button type="button" id="sub" class="sub value-button">-</button>
			                <input type="number" id="shirts_input_sleeve_length" class="qty_text" name="shirts_input_sleeve_length" value="0" step="0.01" pattern="^\d*(\.\d{0,2})?$">
			                <button type="button" id="add" class="add value-button">+</button>
			            </div>
					</div>
				</div>

				<div class="row">
					<div class="column">
					</div>
					<div class="column">
						<button id="shirt_save">Save</button>
					</div>
					<div class="column">
					</div>
				</div>
			</form>

		</div>

		<div class="container" id="suitsC">
			<form id="suits_submit"  method="post" enctype="multipart/form-data" data-url="<?php echo esc_url(admin_url('admin-ajax.php')) ?>" data-action="form_submit2">
			<div class="row">
				<div class="column">
					<label>Select Body Type</label><br>
					<select name="suits_body_type" id="suits_body_type" class="form-control" required>
					<option value="">--Select Body Type--</option>
					<?php 
					$bodyfit = $this->get_product_attributes('pa_body-fit');
					foreach ($bodyfit as $key => $value) {
					?>
					<option value="<?php echo $value->slug; ?>"><?php echo $value->name; ?></option>
					<?php }	?>
					</select>
				</div>

				<div class="column">
					<label>Select Size</label><br>
					<select name="suits_size" id="suits_size" class="form-control" required>
					<option value="">--Select Size--</option>
					<?php 
					$size = $this->get_product_attributes('pa_size');
					foreach ($size as $key => $value) {
					?>
					<option value="<?php echo $value->slug; ?>"><?php echo $value->name; ?></option>
					<?php }	?>
					</select>
				</div>
			</div>

			<div class="row">
				<div class="column">
					<label>Size Guide (CM)</label><br>
					<!-- <input type="file" id="suits_size_guide_cm" name="suits_size_guide_cm"> -->
					<input id="wk-button_size_cm" type="button" class="button"  value="Upload Image"/>
					<input type="hidden" id="suits_size_guide_cm" name="suits_size_guide_cm"/>

					<input type="hidden" id="hidden_suits_size_guide_cm"/>
					<span class="suits_cm_image"></span>

					<span id="suits_show_cm"></span>
				</div>

				<div class="column">
					<label>Size Guide (IN)</label><br>
					<input id="wk-button_size_innnn" type="button" class="button"  value="Upload Image"/>
					<input type="hidden" id="suits_size_guide_in" name="suits_size_guide_in"/>
					<input type="hidden" id="hidden_suits_size_guide_in"/>
					<span id="suits_in_images"></span>

					<span id="suits_show_in"></span>
				</div>

				<div class="column">
					<label>Sleeve Length</label><br>
					<div class="qty_box">
		                <button type="button" id="sub" class="sub value-button">-</button>
		                <input type="number" id="suits_input_sleeve_length" class="qty_text" name="suits_input_sleeve_length" value="0" step="0.01" pattern="^\d*(\.\d{0,2})?$">
		                <button type="button" id="add" class="add value-button">+</button>
		            </div>
				</div>
			</div>

			<div class="row">
				

				<div class="column">
					<label>Half Chest</label><br>
					<div class="qty_box">
		                <button type="button" id="sub" class="sub value-button">-</button>
		                <input type="number" id="suits_input_half_chest" class="qty_text" name="suits_input_half_chest" value="0" value="0" step="0.01" pattern="^\d*(\.\d{0,2})?$">
		                <button type="button" id="add" class="add value-button">+</button>
		            </div>
				</div>

				<div class="column">
					<label>Half Wrist</label><br>
					<div class="qty_box">
		                <button type="button" id="sub" class="sub value-button">-</button>
		                <input type="number" id="suits_input_half_wrist" class="qty_text" name="suits_input_half_wrist" value="0" value="0" step="0.01" pattern="^\d*(\.\d{0,2})?$">
		                <button type="button" id="add" class="add value-button">+</button>
		            </div>
				</div>

				<div class="column">
					<label>Shoulder</label><br>
					<div class="qty_box">
		                <button type="button" id="sub" class="sub value-button">-</button>
		                <input type="number" id="suits_input_shoulder" class="qty_text" name="suits_input_shoulder" value="0" value="0" step="0.01" pattern="^\d*(\.\d{0,2})?$">
		                <button type="button" id="add" class="add value-button">+</button>
		            </div>
				</div>
			</div>

			<div class="row">
				<div class="column">
				</div>
				<div class="column">
					<button id="suits_save">Save</button>
				</div>
				<div class="column">
				</div>
			</div>
			</form>
		</div>       
		<script>
jQuery(document).ready(function($){


$('#wk-button_shirts_cm').click(function(e) {
	var wkMedia;

  e.preventDefault();
  // If the upload object has already been created, reopen the dialog
	if (wkMedia) {
	wkMedia.open();
	return;
  }
  // Extend the wp.media object
  var button = $(this),

  wkMedia = wp.media.frames.file_frame = wp.media({
	title: 'Select media',
	
	button: {
	text: 'Select media'
  }, multiple: false });

  // When a file is selected, grab the URL and set it as the text field's value
  wkMedia.on('select', function() {
	var attachment = wkMedia.state().get('selection').first().toJSON();
	$('.shirts_cm_image').html('<img src="' + attachment.url + '" width="100" height="100" />').next().val(attachment.id).next().show();

	$('#shirts_size_guide_cm').val(attachment.url);
	$('#show_cm').hide();
	
  });
  // Open the upload dialog
  wkMedia.open();
});

$('#wk-button_shirts_in').click(function(e) {
	var wkMedia;

  e.preventDefault();
  // If the upload object has already been created, reopen the dialog
	if (wkMedia) {
	wkMedia.open();
	return;
  }
  // Extend the wp.media object
  wkMedia = wp.media.frames.file_frame = wp.media({
	title: 'Select media',
	button: {
	text: 'Select media'
  }, multiple: false });

  // When a file is selected, grab the URL and set it as the text field's value
  wkMedia.on('select', function() {
	var attachment = wkMedia.state().get('selection').first().toJSON();
	$('#shirts_size_guide_in').val(attachment.url);
	$('.shirts_in_image').html('<img src="' + attachment.url + '" width="100" height="100"/>').next().val(attachment.id).next().show();

	$('#show_in').hide();


  });
  // Open the upload dialog
  wkMedia.open();
});

$('#wk-button_size_cm').click(function(e) {
	var wkMedia;

  e.preventDefault();
  
  // If the upload object has already been created, reopen the dialog
	if (wkMedia) {
	wkMedia.open();
	return;
  }
  // Extend the wp.media object
  wkMedia = wp.media.frames.file_frame = wp.media({
	title: 'Select media',
	button: {
	text: 'Select media'
  }, multiple: false });

  // When a file is selected, grab the URL and set it as the text field's value
  wkMedia.on('select', function() {
	var attachment = wkMedia.state().get('selection').first().toJSON();
	$('#suits_size_guide_cm').val(attachment.url);
	$('.suits_cm_image').html('<img src="' + attachment.url + '" width="100" height="100"/>').next().val(attachment.id).next().show();
	$('#suits_show_cm').hide();


  });
  // Open the upload dialog
  wkMedia.open();
});
//$('#wk-button_size_innnn').click(function(e) {
	$('body').on( 'click', '#wk-button_size_innnn', function(e){
		var wkMedia;

  e.preventDefault();
  // If the upload object has already been created, reopen the dialog
// 	if (wkMedia) {
// 	wkMedia.open();
// 	return;
//   }
  // Extend the wp.media object
  wkMedia = wp.media.frames.file_frame = wp.media({
	title: 'Select media',
	button: {
	text: 'Select media'
  }, multiple: false });

  // When a file is selected, grab the URL and set it as the text field's value
  wkMedia.on('select', function() {
	var attachment = wkMedia.state().get('selection').first().toJSON();
	$('#suits_size_guide_in').val(attachment.url);
	$('#suits_in_images').html('<img src="' + attachment.url + '" width="100" height="100"/>').next().val(attachment.id).next().show();
	$('#suits_show_in').hide();


  });
  // Open the upload dialog
  wkMedia.open();
});

});
</script>
		<?php
	}

	function get_product_attributes($taxonomy)
	{
		$arr = array(
        	'hide_empty' => false,
        	'taxonomy' => $taxonomy
        );
    	$result = get_terms( $arr );
    	return $result;		
	}


}

new Product_Custom_Setting_Helper();