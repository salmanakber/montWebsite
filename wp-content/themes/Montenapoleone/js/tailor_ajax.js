function cm_to_in(cm_value) {
  var v_cm = cm_value;
  var v_in = v_cm * 0.39370079;
  return v_in;
}

function in_to_cm(v_in) {
  var v_cm = v_in * 2.54;
  return v_cm;
}
jQuery(function ($) {
  var baseUrl = document.location.origin;
  jQuery(document).ready(function ($) {
    //  $('section.main_product_section input[type="radio"]').change(function() {
    //           // console.log($(this).parents(".main_product_inner").find(".main_product_measure"));
    //            $('.main_product_measure').val('');

    // });
    $("#measure_option_fit").change(function (e) {
      // checked will equal true if checked or false otherwise
      const checked = $(this).is(":checked");
      if (checked == true) {
        $("#s_my_weight").attr("placeholder", "kg");
        $("#m_my_weight").attr("placeholder", "kg");
        $("#s_my_hight").attr("placeholder", "cm");
        $("#m_my_hight").attr("placeholder", "cm");
      } else {
        $("#s_my_weight").attr("placeholder", "ibs");
        $("#m_my_weight").attr("placeholder", "ibs");
        $("#s_my_hight").attr("placeholder", "in");
        $("#m_my_hight").attr("placeholder", "in");
      }
      //alert(checked);
    });

    $(".option_box").click(function () {
      var val = $(this).find("input:radio").prop("checked") ? false : true;
      $(this).find("input:radio").prop("checked", val);
    });

    $(".measure_unit").change(function (e) {
      var $parent = jQuery(this);
      if (!$parent.is(":checked")) {
        $parent.parent().find(".measure_unit_inpt").val("cm");

        $("#s_my_weight").attr("placeholder", "KG");
        $("#m_my_weight").attr("placeholder", "KG");
        $("#l_my_weight").attr("placeholder", "KG");

        $("#s_my_hight").attr("placeholder", "CM");
        $("#m_my_hight").attr("placeholder", "CM");
        $("#l_my_hight").attr("placeholder", "CM");
      } else {
        $parent.parent().find(".measure_unit_inpt").val("in");
        $("#s_my_weight").attr("placeholder", "LBS");
        $("#m_my_weight").attr("placeholder", "LBS");
        $("#l_my_weight").attr("placeholder", "LBS");

        $("#s_my_hight").attr("placeholder", "IN");
        $("#m_my_hight").attr("placeholder", "IN");
        $("#l_my_hight").attr("placeholder", "IN");
      }
    });

    $("#shirt_add_cart_form").submit(function (e) {
      e.preventDefault();
      var main_product_id = $(
        'section.main_product_section input[type="radio"]:checked'
      ).val();
      if (!jQuery('section.cloth-color input[type="radio"]').is(":checked")) {
        swal("Please check at least one textile.", {
          icon: "warning",
        });
        $("html, body").animate(
          {
            scrollTop: $("section.cloth-color").parent().offset().top,
          },
          "slow"
        );
        return false;
      }

      // if(jQuery('#measure-body input[type="radio"]').is(':checked')){
      //    var measure_product = $('#measure_product1').val();
      //    var customize_type = $('#customize_type1').val();
      //     var product_unit = $('#one').val();
      //    // alert("1");

      // }else if(jQuery('#measure-shirt input[type="radio"]').is(':checked')){
      //   var measure_product = $('#measure_product').val();
      //   var customize_type = $('#customize_type2').val();
      //   var product_unit = $('#two').val();
      //   //alert("2");

      // }

      // if(!jQuery('section.main_product_section input[type="radio"]').is(':checked')){
      //    $('html, body').animate({
      //           scrollTop: $('section.main_product_section').offset().top
      //       }, 'slow');
      //   alert("Please check at least one Product.");
      //   return false;

      // }

      var is_measure_product = 0;
      var measure_title = "";
      var dataType = "";
      jQuery(".main_product_measure").removeClass("emty_error");
      var activeDiv = jQuery(".main_product_section .filter-btn")
        .find(".active")
        .attr("step-id");
      jQuery("#" + activeDiv + " .main_product_measure").each(function (index) {
        if (jQuery(this).val() == "" || jQuery(this).val() == "0") {
          if (measure_title != "") {
            measure_title += ", " + jQuery(this).attr("data-title");
          } else {
            measure_title += jQuery(this).attr("data-title");
          }
          dataType = jQuery(this).attr("data-type");
          jQuery(this).addClass("emty_error");
          is_measure_product = 1;
        }
      });
      if (is_measure_product == 1) {
        measure_title =
          "Please add your measurement for " + measure_title + ".";

        swal({
          title: "Measure by " + dataType + "!",
          text: measure_title,
          icon: "warning",
        });

        $("html, body").animate(
          {
            scrollTop: $("section.main_product_section").offset().top,
          },
          "slow"
        );

        return false;
      }

      /*$('#measure_product').removeClass('emty_error');
     var measure_product = $('#measure_product').val();
     if(!measure_product){
       $('html, body').animate({
              scrollTop: $('section.main_product_section').offset().top
          }, 'slow');
        alert("Please enter measurement.");
        $('#measure_product').addClass('emty_error');
      return false;

    }*/

      if (!jQuery('section.collar input[type="radio"]').is(":checked")) {
        swal("Please check at least one collar.", {
          icon: "warning",
        });
        $("html, body").animate(
          {
            scrollTop: $("section.collar").offset().top,
          },
          "slow"
        );
        return false;
      }
      if (!jQuery('section.cuffs input[type="radio"]').is(":checked")) {
        swal("Please check at least one cuffs.", {
          icon: "warning",
        });
        $("html, body").animate(
          {
            scrollTop: $("section.cuffs").offset().top,
          },
          "slow"
        );
        return false;
      }

      if (!jQuery('section input[type="radio"].fit-select').is(":checked")) {
        swal("Please check at least one fit.", {
          icon: "warning",
        });
        $("html, body").animate(
          {
            scrollTop: $("section.last-section").offset().top,
          },
          "slow"
        );
        return false;
      }

      var formdata = new FormData();
      var file = jQuery("#logo_file")[0].files[0];

      var sformdata = jQuery(this).serialize();
      var slim_fit = $("#slim_fit").prop("checked");
      var embroid_logo = $("#embroid_logo").prop("checked");
      var modern_fit = $("#normal_fit").prop("checked");
      var loose_fit = $("#loose_fit").prop("checked");
      var s_my_weight = $("#s_my_weight").val();
      var s_my_hight = $("#s_my_hight").val();
      var m_my_weight = $("#m_my_weight").val();
      var m_my_hight = $("#m_my_hight").val();
      var l_my_weight = $("#l_my_weight").val();
      var l_my_hight = $("#l_my_hight").val();

      $("#s_my_weight").removeClass("emty_error");
      $("#s_my_hight").removeClass("emty_error");
      $("#m_my_weight").removeClass("emty_error");
      $("#m_my_hight").removeClass("emty_error");
      $("#l_my_weight").removeClass("emty_error");
      $("#l_my_hight").removeClass("emty_error");
      if (slim_fit == true) {
        var fit = "slim_fit";
        if (!s_my_weight || !s_my_hight) {
          $("#s_my_weight").addClass("emty_error");
          $("#s_my_hight").addClass("emty_error");
          $("html, body").animate(
            {
              scrollTop: $("section.last-section").offset().top,
            },
            "slow"
          );

          return false;
        }
      } else if (modern_fit == true) {
        var fit = "modern_fit";
        if (!m_my_weight || !m_my_hight) {
          $("#m_my_weight").addClass("emty_error");
          $("#m_my_hight").addClass("emty_error");
          $("html, body").animate(
            {
              scrollTop: $("section.last-section").offset().top,
            },
            "slow"
          );

          return false;
        }
      } else if (loose_fit == true) {
        var fit = "loose_fit";
        if (!l_my_weight || !l_my_hight) {
          $("#l_my_weight").addClass("emty_error");
          $("#l_my_hight").addClass("emty_error");
          $("html, body").animate(
            {
              scrollTop: $("section.last-section").offset().top,
            },
            "slow"
          );

          return false;
        }
      }
      $("#logo_text").removeClass("emty_error");
      if (embroid_logo == true) {
        var logo_text = $("#logo_text").val();
        if (!logo_text) {
          $("#logo_text").addClass("emty_error");
          $("html, body").animate(
            {
              scrollTop: $("section.last-section").offset().top,
            },
            "slow"
          );

          return false;
        }
      }
      if (activeDiv == "measure-body") {
        formdata.append("measureby", "body");
      } else {
        formdata.append("measureby", "shirt");
      }

      formdata.append("file", file);
      formdata.append("action", "shirt_item_ajax");
      formdata.append("form", sformdata);
      formdata.append("fit", fit);

      //print_r(formdata);
      // formdata.append('measure_product', measure_product);
      // formdata.append('customize_type', customize_type);
      //  formdata.append('product_unit', product_unit);

      jQuery.ajax({
        type: "post",
        dataType: "json",
        url: ajax_object.ajaxurl,
        data: formdata,
        contentType: false,
        processData: false,

        beforeSend: function () {
          $("#shirt_add_cart_form").addClass("loader_show");
        },

        success: function (response) {
           console.log(response);
          //return false;
          if (response.status == "success") {
            // jQuery('#update_msg').addClass('msg-success');
            //jQuery("#update_msg").text("your request will be processed");
            swal("Successfully added to cart", {
              icon: "success",
            });
            //window.location = response.url;
            location.reload();
          } 
          
          $("#shirt_add_cart_form").removeClass("loader_show");
        },
      });
      return false;
    });

    // chekout code

    $("#checkout-customer-continue").click(function () {
      $(".login_box").slideToggle("slow");
      $(".billing_box").slideToggle("slow");
      $(".first_next").slideToggle("slow");
    });

    if ($(".steps-fields").length > 0) {
      $(".steps-fields .scroll-to").click(function () {
        var shilprice = $("#shipping_method li input[type='radio']:checked")
          .parent()
          .text();
        var ship_fname = jQuery("#shipping_first_name").val();
        var ship_lname = jQuery("#shipping_last_name").val();
        var shipping_company = jQuery("#shipping_company").val();
        var shipping_country = jQuery("#shipping_country").val();
        var shipping_address = jQuery("#shipping_address_1").val();
        var shipping_address2 = jQuery("#shipping_address_2").val();
        var shipping_city = jQuery("#shipping_city").val();
        var shipping_state = jQuery("#shipping_state").val();
        var shipping_postcode = jQuery("#shipping_postcode").val();
        var shipping_phone = jQuery("#shipping_phone").val();

        var shipping_country_name = jQuery(
          "#shipping_country option[value='" + shipping_country + "']"
        ).text();
        var bil_fname = jQuery("#billing_first_name").val();
        var bil_lname = jQuery("#billing_last_name").val();
        // var billing_company = jQuery('#billing_company').val();
        var bil_email = jQuery("#billing_email").val();
        var billing_phone_code = jQuery("#billing_phone_code").val();
        var bil_phone = jQuery("#billing_phone").val();
        var billing_country = jQuery("#billing_country").val();
        var billing_address = jQuery("#billing_address_1").val();
        var billing_address2 = jQuery("#billing_address_2").val();
        var billing_city = jQuery("#billing_city").val();
        var billing_state = jQuery("#billing_state").val();
        var billing_postcode = jQuery("#billing_postcode").val();
        var billing_country_name = jQuery(
          "#billing_country option[value='" + billing_country + "']"
        ).text();
        var shipping_html = "";
        shipping_html += "<div class='fill_detail_box'>";
        shipping_html +=
          "<p><span><b>Shipping- " + shilprice + "</b></span></p>";
        // shipping_html += "<span>"+ship_fname+' '+ship_lname+"</span>";
        // shipping_html += "<span>"+shipping_phone+"</span>";
        // shipping_html += "<span>"+shipping_company+"</span>";
        shipping_html +=
          "<p class='s_address'><b>Delivery address</b><span>" +
          billing_address +
          "</span>";
        shipping_html +=
          "<span>" + billing_city + " " + billing_postcode + "</span></p>";
        shipping_html += "</div>";

        var billing_html = "";
        billing_html += "<div class='fill_detail_box'>";
        billing_html +=
          "<p class='b_name'><b>Name</b><span>" +
          bil_fname +
          " " +
          bil_lname +
          "</span></p>";

        billing_html += "<p class='b_address'><b>Address</b>";
        billing_html += "<span>" + billing_address + "</span>";
        billing_html += "<span>" + billing_address2 + "</span>";
        billing_html +=
          "<span>" + billing_city + " " + billing_postcode + "</span>";
        billing_html += "<span>" + bil_email + "</span></p>";
        billing_html +=
          "<p class='b_mobile'><b>Mobile</b><span>" +
          billing_phone_code +
          bil_phone +
          "</span></p>";
        billing_html += "</div>";

        prevWrapper = $(this).parent().parent().parent();
        console.log(prevWrapper);
        prevWrapper.find(".chekout_error_info").text("");
        $(prevWrapper).addClass("loader_show");
        var empty = prevWrapper
          .find(".woocommerce-billing-fields p.validate-required input")
          .filter(function () {
            return this.value === "";
          });
        // var empty2 = prevWrapper.find('p.validate-required select').filter(function() {

        //   return this.value === "";

        // });

        if (empty.length == 0) {
          var firsttab = $(this).attr("id");
          // if (firsttab=='checkout-customer-continue') {
          //         $('.customer_btn').removeClass('inactive_tab');
          //   }
          prevWrapper.prev().prev(".btn-step").removeClass("inactive_tab");
          var step_id = $(prevWrapper).attr("id");

          if (step_id == "edit-step1") {
            $("#fill_box_billing_html").html(billing_html);
          }
          if (step_id == "edit-step2") {
            $("#fill_box_shipping_html").html(shipping_html);
          }
        } else {
          prevWrapper
            .find(".chekout_error_info")
            .text("Please fill all required fields");
          prevWrapper
            .find(".woocommerce-billing-fields p.validate-required input")
            .filter(function () {
              $(prevWrapper).removeClass("loader_show");
              console.log($(this).val().length);
              if ($(this).val().length === 0) {
                $(this).addClass("emty_error");
              } else {
                $(this).removeClass("emty_error");
              }
            });
          //  prevWrapper.find('p.validate-required select').filter(function() {

          //  if( $(this).val().length === 0 ) {
          //    $(this).addClass('emty_error');

          //  }else{
          //    $(this).removeClass('emty_error');
          //  }

          // });
          $("html, body").animate(
            {
              scrollTop: $(".steps-fields").parent().offset().top,
            },
            200
          );

          return false;
        }

        $("html, body").animate(
          {
            scrollTop: $(".steps-fields").parent().offset().top,
          },
          200
        );
      });
    }

    $("#steps .btn-step").click(function () {
      $(".step_box").removeClass("loader_show");
    });

    // Perform AJAX login on form submit
    $("#slogin_submit").on("click", function (e) {
      e.preventDefault();

      $.ajax({
        type: "POST",
        dataType: "json",
        url: ajax_object.ajaxurl,
        data: {
          action: "ajaxlogin", //calls wp_ajax_nopriv_ajaxlogin
          username: $(".login_box #username").val(),
          password: $(".login_box #password").val(),
        },

        beforeSend: function () {
          $(".email_chek_box").addClass("loader_show");
        },
        success: function (data) {
          $("#edit-step1 .login_error_info").text("");

          if (data.status == "success") {
            location.reload();
            // $( document.body ).trigger( 'wc_fragment_refresh' );
          } else {
            $(".login_box #username").addClass("emty_error");
            $(".login_box #password").addClass("emty_error");
            // $('#edit-step1 .login_error_info').html(data.msg);
            $("#edit-step1 .login_error_info").text(
              "please check your username and password"
            );
          }
          $(".email_chek_box").removeClass("loader_show");
        },
      });
    });

    $("body").on("change", "#cm_change", function (e) {
      // checked will equal true if checked or false otherwise
      const checked = $(this).is(":checked");
      var main_shirt_length = $("#main_shirt_length").val();
      console.log("LLL", main_shirt_length);
      var main_left_sleeve = $("#main_left_sleeve").val();
      var main_right_sleeve = $("#main_right_sleeve").val();
      if (checked == true) {
        var textview = "cm";

        $(".shirt_length_display").text(main_shirt_length);
        $(".left_sleeve_display").text(main_left_sleeve);
        $(".right_sleeve_display").text(main_right_sleeve);

        $(".custom_var.size-guide-tabs ul li").each(function () {
          var value = $(this).attr("data-value");

          $(this).find("a").text(value);
        });
      } else {
        var textview = "inch";
        $(".shirt_length_display").text(cm_to_in(main_shirt_length).toFixed(2));
        $(".left_sleeve_display").text(cm_to_in(main_left_sleeve).toFixed(2));
        $(".right_sleeve_display").text(cm_to_in(main_right_sleeve).toFixed(2));
        $(".custom_var.size-guide-tabs ul li").each(function () {
          var cm = $(this).find("a").text();
          var changev = cm_to_in(cm);
          // var frac = getfract(changev);
          $(this).find("a").text(changev.toFixed(2));
        });
      }
      $("#front_unit").val(textview);
//       $(".unit_display").text(textview);
    });
  });

  $("body").on("click", ".size-guide-img-preview", function (e) {
    e.preventDefault();
    $(".fancybox-container").remove();
  //  $(".view_guide_image").empty();
    var $toggle = $(this),
      postID = $toggle.data("post_id");
    var variation_id = $(".single_variation_wrap .variation_id").val();

    if (variation_id == 0) {
      alert("please select variation first");
      return false;
    } else if (variation_id) {
      postID = variation_id;
    }


    const checked = $('#cm_change').is(":checked");

    if( checked == true )
    {
      var measure = 'cm';
    }
    else{
      var measure = 'inch';
    }

    console.log(postID);

    // $.ajax({
    //   type: "POST",
    //   url: ajax_object.ajaxurl,
    //   data: {
    //     post_id: postID,
    //     action: "size_guide_img_preview_ajax",
    //     // nonce: ajax.nonce,
    //   },
    //   beforeSend: function () {
    //     $(".email_chek_box").addClass("loader_show");
    //     $(".view_guide_image").addClass("loader_show");
    //   },
    //   success: function (data) {
    //     setTimeout(function () {
    //       $(".view_guide_image").removeClass("loader_show");
    //       $(".view_guide_image").html(data);
    //     }, 1000);

    //     $(".email_chek_box").removeClass("loader_show");
    //   },
    // });
    $.fancybox.open({
    // width  : 800,
    // height: 500,
    
     arrows: true,
   autoSize : false,
     width    : "50%",
     height   : "50%",
    src: ajax_object.ajaxurl,
    type: 'ajax',
    opts: {
      ajax: {
        settings: {
          dataType: 'html',
          type: "POST",
          data: {
            post_id: postID,
            product_id: $toggle.data("post_id"),
            measure: measure,
            action: 'size_guide_img_preview_ajax',
           // nonce: ajax.nonce,
            fancybox: true,
          },
        }
      },
      afterLoad: function(instance, slide){

        
      },
    }
  });
  });
  $("body").on("click", ".s_reset_variations", function (e) {
    $(".view_guide_image").empty();
  });
  // $("body").on("change", "form.variations_form .variations select", function (
  //   e
  // ) {
  
    
  //   //$(".view_guide_image").empty();
  //   e.preventDefault();
  //    $(".fancybox-container").remove();
  //   var $toggle = $(this),
  //     postID = $toggle.data("post_id");
  //   var variation_id = $(".single_variation_wrap .variation_id").val();

  //   if (variation_id == 0) {
  //     /*alert('please select variation first');*/
  //     return false;
  //   } else if (variation_id) {
  //     postID = variation_id;
  //   }
  //  // $(".view_guide_image").empty();
  //   console.log(postID);

  //   $.fancybox.open({
  //   // width  : 800,
  //   // height: 500,
    
  //    arrows: true,
  //  autoSize : false,
  //    width    : "50%",
  //    height   : "50%",
  //   src: ajax_object.ajaxurl,
  //   type: 'ajax',
  //   opts: {
  //     ajax: {
  //       settings: {
  //         dataType: 'html',
  //         type: "POST",
  //         data: {
  //           post_id: postID,
  //           action: 'size_guide_img_preview_ajax',
  //          // nonce: ajax.nonce,
  //           fancybox: true,
  //         },
  //       }
  //     },
  //     afterLoad: function(instance, slide){

        
  //     },
  //   }
  // });
  //   // $.ajax({
  //   //   type: "POST",
  //   //   url: ajax_object.ajaxurl,
  //   //   data: {
  //   //     post_id: postID,
  //   //     action: "size_guide_img_preview_ajax",
  //   //     // nonce: ajax.nonce,
  //   //   },
  //   //   beforeSend: function () {
  //   //     $(".email_chek_box").addClass("loader_show");

  //   //     $(".view_guide_image").addClass("loader_show");
  //   //   },
  //   //   success: function (data) {
  //   //     setTimeout(function () {
  //   //       $(".view_guide_image").removeClass("loader_show");
  //   //       $(".view_guide_image").html(data);
  //   //     }, 1000);

  //   //     $(".email_chek_box").removeClass("loader_show");
  //   //   },
  //   // });
  //   return false;
  // });

  $("body").on("change", "#snewsletter", function (e) {
    // checked will equal true if checked or false otherwise
    const checked = $(this).is(":checked");
    var user_id = $(this).attr("data-user_id");
    //console.log(checked);
    if (checked == true) {
      var ns = "yes";
    } else {
      var ns = "no";
    }
    $.ajax({
      type: "POST",
      dataType: "json",
      url: ajax_object.ajaxurl,
      data: {
        user_id: user_id,
        snewsletter: ns,
        action: "newsletter_chek_ajax",
      },
      beforeSend: function () {
        $(".my_account_newlettr").addClass("loader_show");
      },
      success: function (data) {
        if (data.status == "success") {
          location.reload();
          // $( document.body ).trigger( 'wc_fragment_refresh' );
        }
        $(".my_account_newlettr").removeClass("loader_show");
      },
    });
  });
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



$('body').on('click', '.custom_var ul li', function(e) {
  e.preventDefault();

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
          // console.log(val);
      }

  });
  if ($('.custommade_option_for_shirts').hasClass('active')) {

      var variation_id = $('input.variation_id').val();
      
      var post_id = $('input.post_id').val();
      if (variation_id != '') {

          jQuery.ajax({
              type: "POST",
              url: ajax_object.ajaxurl,
              data: {
                  action: "get_variation_custom_size",
                  variation_id: variation_id,
                  post_id: post_id
              },
              success: function(data) {
                  var obj = jQuery.parseJSON(data);
				  console.log(variation_id);
				  	console.log(obj)
                  if (obj.message === 'found') {
//                      if (checked !== true) {
						 console.log('Okay');
                          $("#main_shirt_length").val(obj.data.shirt_length);
                          $("#main_left_sleeve").val(obj.data.sleeve_length);
                          $("#main_right_sleeve").val(obj.data.sleeve_length);
					  	$(".unit_display").text('');

                          $("#main_half_wrist").val(obj.data.half_wrist);
                          $("#main_half_chest").val(obj.data.half_chest);
                          $("#main_half_hip").val(obj.data.half_hip);
                          $("#main_shoulder").val(obj.data.shoulder);
                          $("#main_arm_hole").val(obj.data.arm_hole);

                          $('input#default_shirt_length').val(obj.data.shirt_length);
                          $('span.shirt_length_display').text(obj.data.shirt_length);

                          $('input#suits_length').val(obj.data.suits_length);
                          $('span.suits_length').text(obj.data.suits_length);

                          $('.left_sleeve_display').text(obj.data.sleeve_length);
                          $('.right_sleeve_display').text(obj.data.sleeve_length);
                          $('.half_waist_display').text(obj.data.half_wrist);

                          $('input#default_left_sleeve').val(obj.data.sleeve_length);
                          $('input#default_right_sleeve').val(obj.data.sleeve_length);

                          $('span.suits_length_display').text(obj.data.half_wrist);
                          $('span.shirt_chest_display').text(obj.data.half_chest);
                          $('span.shirt_hip_display').text(obj.data.half_hip);
                          $('span.shirt_shoulder_display').text(obj.data.shoulder);
                          $('span.shirt_arm_hole_display').text(obj.data.arm_hole);

                          $('input#default_half-wrist').val(obj.data.half_wrist);
                          $('input#default_half-chest').val(obj.data.half_chest);
                          $('input#default_half-hip').val(obj.data.half_hip);
                          $('input#default_half-shoulder').val(obj.data.shoulder);
                          $('input#default_arm-hole').val(obj.data.arm_hole);


//                       } else {
//                           $("#main_shirt_length").val(cm_to_in(obj.data.shirt_length)
//                               .toFixed(2));

//                           $("#main_left_sleeve").val(cm_to_in(obj.data.sleeve_length)
//                               .toFixed(2));
//                           $("#main_right_sleeve").val(cm_to_in(obj.data.sleeve_length)
//                               .toFixed(2));

//                           $('input#suits_length').val(cm_to_in(obj.data.suits_length)
//                               .toFixed(2));
//                           $('span.suits_length').text(cm_to_in(obj.data.suits_length)
//                               .toFixed(2));

//                           $('input#default_shirt_length').val(cm_to_in(obj.data
//                               .shirt_length).toFixed(2));
//                           $('span.shirt_length_display').text(cm_to_in(obj.data
//                               .shirt_length).toFixed(2));

//                           $('.left_sleeve_display').text(cm_to_in(obj.data
//                               .sleeve_length).toFixed(2));
//                           $('.right_sleeve_display').text(cm_to_in(obj.data
//                               .sleeve_length).toFixed(2));

//                           $('input#default_left_sleeve').val(cm_to_in(obj.data
//                               .sleeve_length).toFixed(2));
//                           $('input#default_right_sleeve').val(cm_to_in(obj.data
//                               .sleeve_length).toFixed(2));

//                           $('span.suits_length_display').text(cm_to_in(obj.data
//                               .half_wrist).toFixed(2));
//                           $('span.shirt_chest_display').text(cm_to_in(obj.data
//                               .half_chest).toFixed(2));
//                           $('span.shirt_shoulder_display').text(cm_to_in(obj.data
//                               .shoulder).toFixed(2));

//                           $('input#default_half-wrist').val(cm_to_in(obj.data
//                               .half_wrist).toFixed(2));
//                           $('input#default_half-chest').val(cm_to_in(obj.data
//                               .half_chest).toFixed(2));
//                               $('input#default_half-hip').val(cm_to_in(obj.data
//                                   .half_hip).toFixed(2));
//                                   $('input#default_arm-hole').val(cm_to_in(obj.data
//                                       .arm_hole).toFixed(2));
//                           $('input#default_half-shoulder').val(cm_to_in(obj.data
//                               .shoulder).toFixed(2));
                              

//                           $("#main_half_wrist").val(cm_to_in(obj.data.half_wrist)
//                               .toFixed(2));
//                           $("#main_half_chest").val(cm_to_in(obj.data.half_chest)
//                               .toFixed(2));
//                           $("#main_half_hip").val(cm_to_in(obj.data.half_hip)
//                           .toFixed(2));
//                           $("#main_arm_hole").val(cm_to_in(obj.data.arm_hole)
//                               .toFixed(2));
//                           $("#main_shoulder").val(cm_to_in(obj.data.shoulder).toFixed(
//                               2));
//                       }
                  } else {
                      $("#main_shirt_length").val('0');
                      $("#suits_length").val('0');
                      $("#main_left_sleeve").val('0');
                      $("#main_right_sleeve").val('0');

                      $("#main_half_wrist").val('0');
                      $("#main_half_chest").val('0');
                      $("#main_half_hip").val('0');
                      $("#main_arm_hole").val('0');
                      $("#main_shoulder").val('0');

                      $('input#default_shirt_length').val('0');
                      $('span.shirt_length_display').text('0');

                      $('input#suits_length').val('0');
                      $('span.suits_length').text('0');

                      $('.left_sleeve_display').text('0');
                      $('.right_sleeve_display').text('0');

                      $('input#default_left_sleeve').val('0');
                      $('input#default_right_sleeve').val('0');

                      $('span.suits_length_display').text('0');
                      $('span.shirt_chest_display').text('0');
                      $('span.shirt_hip_display').text('0');
                      $('span.shirt_arm_hole_display').text('0');
                      $('span.shirt_shoulder_display').text('0');

                      $('input#default_half-wrist').val('0');
                      $('input#default_half-chest').val('0');
                      $('input#default_half-hip').val('0');
                      $('input#default_arm-hole').val('0');
                      $('input#default_half-shoulder').val('0');
                      
                  }
                  $('.length-box').removeClass('fields_disabale');
                  $(".custom-loader").remove();
              }
          });
      }
      }

});



});