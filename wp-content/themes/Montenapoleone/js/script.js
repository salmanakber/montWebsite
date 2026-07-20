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


jQuery(function ($) {
  $(window).scroll(function () {
  var scroll = $(window).scrollTop();

  //>=, not <=
  if (scroll >= 100) {
    //clearHeader, not clearheader - caps H
    $("header.header-all").addClass("header-bg");
    $(".logo-img").attr("src", "/wp-content/uploads/2020/10/Montenapoleone_logo.png");
    $(".mbl-logo").attr("src", "/wp-content/uploads/2020/10/Montenapoleone_logo.png");
      
      $(".header-elements .nav-menus ul li.green-logoo").hover(
    function () {
      $("header.header-all").removeClass("sub-menu-color");
         $(
    ".home .logo-img, .page-id-372 .logo-img, .page-id-368 .logo-img, .page-id-430 .logo-img, .page-id-375 .logo-img, .single-story .logo-img, .page-template-blog-template .logo-img"
  ).attr("src", "/wp-content/uploads/2020/10/Montenapoleone_logo.png");
    }
  );
      
        $(".header-elements .nav-menus ul li.add-submenu-class").mouseleave(function() {
           $(
    ".home .logo-img, .page-id-372 .logo-img, .page-id-368 .logo-img, .page-id-430 .logo-img, .page-id-375 .logo-img, .single-story .logo-img, .page-template-blog-template .logo-img"
  ).attr("src", "/wp-content/uploads/2020/10/Montenapoleone_logo.png");
      });
      
      
  } else {
    $("header.header-all").removeClass("header-bg");
    $(
      ".home .logo-img, .page-id-372 .logo-img, .page-id-368 .logo-img, .page-id-430 .logo-img, .page-id-375 .logo-img, .single-story .logo-img, .page-template-blog-template .logo-img"
    ).attr("src", "/wp-content/uploads/2020/10/Montenapoleone_logo.png");
    $(
      ".home .mbl-logo, .page-id-372 .mbl-logo, .page-id-368 .mbl-logo, .page-id-430 .mbl-logo, .page-id-375 .mbl-logo, .single-story .mbl-logo, .page-template-blog-template .mbl-logo"
    ).attr("src", "/wp-content/uploads/2020/10/Montenapoleone_logo.png");
      
       $(".header-elements .nav-menus ul li.green-logoo").hover(
    function () {
      $("header.header-all").removeClass("sub-menu-color");
         $(
    ".home .logo-img, .page-id-372 .logo-img, .page-id-368 .logo-img, .page-id-430 .logo-img, .page-id-375 .logo-img, .single-story .logo-img, .page-template-blog-template .logo-img"
  ).attr("src", "/wp-content/uploads/2020/10/Montenapoleone_logo.png");
    }
  );
      
      
    
  }

  if (scroll >= 600) {
    //clearHeader, not clearheader - caps H
    $(".up-arrow").fadeIn();
  } else {
    $(".up-arrow").fadeOut();
  }
});

$(document).ready(function () {
  $(
    ".home .logo-img, .page-id-372 .logo-img, .page-id-368 .logo-img, .page-id-430 .logo-img, .page-id-375 .logo-img, .single-story .logo-img, .page-template-blog-template .logo-img"
  ).attr("src", "/wp-content/uploads/2020/10/Montenapoleone_logo.png");
  $(
    ".home .mbl-logo, .page-id-372 .mbl-logo, .page-id-368 .mbl-logo, .page-id-430 .mbl-logo, .page-id-375 .mbl-logo, .single-story .mbl-logo, .page-template-blog-template .mbl-logo"
  ).attr("src", "/wp-content/uploads/2020/10/Montenapoleone_logo.png");

  $(".up-arrow").click(function () {
    $("html, body").animate(
      {
        scrollTop: 0,
      },
      800
    );
    return false;
  });

  $(".popup-youtube").magnificPopup({
    disableOn: 700,
    type: "iframe",
    mainClass: "mfp-fade",
    preloader: false,

    fixedContentPos: false,
  });

  $(".main-banner-slider").owlCarousel({
    loop: true,
    margin:200,
    nav: true,
    autoplay:false,
    autoplaySpeed: 15000, 
    autoplayTimeout:40000, 
    scrollPerPage: true,
    items: 1,
    mouseDrag: false,
    navText: [
      "<img src='/wp-content/uploads/2020/06/left-arrow.png'>",
      "<img src='/wp-content/uploads/2020/06/left-arrow.png'>",
    ],
    lazyLoad: true,
    smartSpeed: 300000,
      animateOut: 'fadeOut',
      dots: 'true'
  });

  $(".header-elements .nav-menus ul li.add-submenu-class").hover(
    function () {
      $("header.header-all").addClass("sub-menu-color");
         $(
    ".home .logo-img, .page-id-372 .logo-img, .page-id-368 .logo-img, .page-id-430 .logo-img, .page-id-375 .logo-img, .single-story .logo-img, .page-template-blog-template .logo-img"
  ).attr("src", "/wp-content/uploads/2020/10/Montenapoleone_logo.png");
    },
    function () {
      $("header.header-all").removeClass("sub-menu-color");
         $(
    ".home .logo-img, .page-id-372 .logo-img, .page-id-368 .logo-img, .page-id-430 .logo-img, .page-id-375 .logo-img, .single-story .logo-img, .page-template-blog-template .logo-img"
  ).attr("src", "/wp-content/uploads/2020/10/Montenapoleone_logo.png");
    }
  );
    
    
    

 $(".sub-menu").hover(
    function () {
      $(
        ".home header.header-all, .page-id-368 header.header-all, .page-id-372 header.header-all, .page-id-430 header.header-all, .page-id-375 header.header-all, .single-story header.header-all, .page-template-blog-template header.header-all"
      ).addClass("black-nav");
    },
    function () {
      $(
        ".home header.header-all, .page-id-368 header.header-all,  .page-id-372 header.header-all, .page-id-430 header.header-all, .page-id-375 header.header-all, .single-story header.header-all, .page-template-blog-template header.header-all"
      ).removeClass("black-nav");
    }
  );

  $(".header-elements ul li").hover(
    function () {
      if ($(this).find(".sub-menu").length > 0) {
        $(this).find(".sub-menu").fadeIn();
        $(this).addClass("arrow-down");
      }
    },
    function () {
      if ($(this).find(".sub-menu").length > 0) {
        $(this).find(".sub-menu").fadeOut();
        $(this).removeClass("arrow-down");
      }
    }
  );

  $(".search a").click(function () {
    $(".search-slide").slideDown();
  });

  $(".search-slide .search-close").click(function () {
    $(".search-slide").slideUp();
  });

  $(".help-popup a").click(function () {
    $(".help-box").slideDown();
  });

  $(".help-box .search-close").click(function () {
    $(".help-box").slideUp();
  });

  $(".account-popup a").click(function () {
    $(".acc-popup").slideDown();
  });

  $(".acc-popup .search-close").click(function () {
    $(".acc-popup").slideUp();
  });

  $(".cart-bag").click(function () {
    $(".cart-details").slideDown();
  });

  $(".cart-details .search-close").click(function () {
    $(".cart-details").slideUp();
  });

  /*******mobile jquery******/

  $(".mobile-bag").click(function () {
    $(".mobile-cart-box").slideDown();
  });

  $(".mobile-cart-box .search-close").click(function () {
    $(".mobile-cart-box").slideUp();
  });

  /****************/

  $("#nav-icon").click(function () {
    $(".mobile-search-menu").slideDown();
  });

  $(".close-menu").click(function () {
    $(".mobile-search-menu").slideUp();
  });

  jQuery(".mobile-search-menu li a").on("click", function () {
    var target = $(this).attr("title");
    target = "#" + target;

    $(target).css("transform", "translate3d(0px,0,0)");
    // jQuery(".menu-tabb").css("transform", "translate3d(0px,0,0)");
  });

  jQuery(".back-menus").on("click", function () {
    jQuery(".menu-tabb").css("transform", "translate3d(1050px,0,0)");
  });

  $(".popup-gallery").magnificPopup({
    delegate: "a",
    type: "image",
    tLoading: "Loading image #%curr%...",
    mainClass: "mfp-img-mobile",
    gallery: {
      enabled: true,
      navigateByImgClick: true,
      preload: [0, 1], // Will preload 0 - before current, and 1 after the current image
    },
    image: {
      tError: '<a href="%url%">The image #%curr%</a> could not be loaded.',
      titleSrc: function (item) {
        return item.el.attr("title") + "<small>by Marsel Van Oosten</small>";
      },
    },
  });

  $(".popup-with-zoom-anim").magnificPopup({
    type: "inline",

    fixedContentPos: false,
    fixedBgPos: true,

    overflowY: "auto",

    closeBtnInside: true,
    preloader: false,

    midClick: true,
    removalDelay: 300,
    mainClass: "my-mfp-zoom-in",
  });

  var maxHeight = 0;

  jQuery(".our-roots").each(function () {
    if ($(this).height() > maxHeight) {
      maxHeight = $(this).height();
    }
  });

  jQuery(".our-roots").height(maxHeight);

  $(".typing-abt").typeOut({
    marker: "|",

    delay: 90,
  });

  $(".typingg").typeOut({
    marker: "|",

    delay: 90,
  });

  setTimeout(function () {
    $(".typingg2").typeOut({
      marker: "|",

      delay: 90,
    });
  }, 5000);

  setTimeout(function () {
    $(".typingg3").typeOut({
      marker: "|",

      delay: 90,
    });
  }, 10000);

  var Header = document.querySelector("header");
  var headroom = new Headroom(Header, {
    offset: 105,
    tolerance: 0,
  });
  headroom.init();
});

$(document).ready(function () {
  var scrollLink = $(".faq-left li a");

  // Smooth scrolling
  scrollLink.click(function (e) {
    e.preventDefault();
    $("body,html").animate(
      {
        scrollTop: $(this.hash).offset().top - 50,
      },
      1000
    );
  });

  $(function () {
    var Accordion = function (el, multiple) {
      this.el = el || {};
      this.multiple = multiple || false;

      var links = this.el.find(".article-title");
      links.on(
        "click",
        {
          el: this.el,
          multiple: this.multiple,
        },
        this.dropdown
      );
    };

    Accordion.prototype.dropdown = function (e) {
      var $el = e.data.el;
      ($this = $(this)), ($next = $this.next());

      $next.slideToggle();
      $this.parent().toggleClass("open");

      if (!e.data.multiple) {
        $el
          .find(".accordion-content")
          .not($next)
          .slideUp()
          .parent()
          .removeClass("open");
      }
    };
    var accordion = new Accordion($(".accordion-container"), false);
  });

  $(document).click(function () {
    $(".search-box").slideUp();
  });
  $(".search-box").click(function (e) {
    e.stopPropagation();
  });
  $(".search a, .help-popup a, .account-popup a, .cart-bag").click(function (
    e
  ) {
    e.stopPropagation();
  });

  $(".login-text").click(function () {
    $(".create-forms").fadeOut();
    $(".login-form").fadeIn();
  });

  $(".login-text").click(function () {
    $(".act-text").fadeIn();
    $(".login-text").hide();
    $(".create-ac").hide();
    $(".login-ac").fadeIn();
  });

  $(".act-text").click(function () {
    $(".act-text").hide();
    $(".login-text").fadeIn();
    $(".create-ac").fadeIn();
    $(".login-ac").hide();
  });

  $(".act-text").click(function () {
    $(".create-forms").fadeIn();
    $(".login-form").fadeOut();
  });

  /*$(document).on("click", ".click-measure-shirt", function () {
    window.location.href = "/custom-made/#measuree-shirt";
    $('#measuree-shirt').trigger('click');
      $("html, body").animate({ scrollTop: 0 }, "slow");
     
});*/

  $(document).on("click", ".click-measure-body", function () {
    window.location.href = "/tailor-made/";
    $("html, body").animate({ scrollTop: 0 }, "slow");
  });

  $(".filter-btn a").on("click", function () {
    var target = $(this).attr("step-id");
    target = "#" + target;
    $(".measure-contnt").hide();
    $(target).show();
    $(".filter-btn a").removeClass("active");
    $(this).addClass("active");
  });

  $(".shop-tabss a").on("click", function (e) {
    e.preventDefault();
    var target = $(this).attr("tab-attr");
    $(".reset_variations").trigger("click");
    $(".custom_var ul li").removeClass("disable_class");
    $(".shirt-lengthh").addClass("fields_disabale");
    $("#cm_change").prop("checked", true);
    $("#cm_change").trigger("change");
    if (target == "custommade") {
      $(".custom_made_note").show("slow");
      $(".custom_made_warning").show("slow");
      var size_box_standard = $(".size_box_standard").html();
      $(".size_box_standard").html("");
      $(".size_box_custommade").append(size_box_standard);

      $("#made_info").val("custommade");
    } else if (target == "standard") {
      $("#made_info").val("");
      $(".custom_made_note").hide("slow");
      $(".custom_made_warning").hide("slow");
      var size_box_custommade = $(".size_box_custommade").html();
      $(".size_box_custommade").html("");
      $(".size_box_standard").append(size_box_custommade);
      $("#custommade #sleeve-value").hide();
      $("#custommade #shirt-value").hide();
      $("#custommade #one-c").text("Change");
      $("#custommade #two-c").text("Change");
    }
    target = "#" + target;
    console.log(target);

    $(".tabss-option").hide();
    $(target).show();
    $(".shop-tabss a").removeClass("active");
    $(this).addClass("active");
  });

  $(".shirt-length-box").click(function () {
    $("#shirt-value").fadeToggle();
    $("#sleeve-value").hide();
  });

  $(".sleeve-length-box").click(function () {
    $("#sleeve-value").fadeToggle();
    $("#shirt-value").hide();
  });

  $(".suits-half-wrist").click(function () {
    $("#half-wrist").fadeToggle();
    $("#shirt-value").hide();
  });

 $(".suits-half-waist").click(function () {
  $("#half-waist").fadeToggle();
      $("#shirt-value").hide();
});

  $(".suits-half-chest").click(function () {
    $("#half-chest").fadeToggle();
    $("#shirt-value").hide();
  });

  $(".suits-half-hip").click(function () {
    $("#half-hip").fadeToggle();
    $("#shirt-value").hide();
  });

  $(".suits-half-shoulder").click(function () {
    $("#half-shoulder").fadeToggle();
    $("#shirt-value").hide();
  });

  $(".suits-arm-hole").click(function () {
    $("#arm-hole").fadeToggle();
    $("#shirt-value").hide();
  });

  $(".shirt-length-box").click(function () {
    $("#one-c").fadeOut(function () {
      $("#one-c")
        .text($("#one-c").text() == "Change" ? "Close" : "Change")
        .show();
      $("#two-c").text("Change");
    });
  });

  $(".sleeve-length-box").click(function () {
    $("#two-c").fadeOut(function () {
      $("#two-c")
        .text($("#two-c").text() == "Change" ? "Close" : "Change")
        .show();

      $("#one-c").text("Change");
    });
  });

  function change_text_qty() {
    
    console.log("IFWORKS");
    var shirt_length = $("#input_shirt_length").val();
    var default_shirt_length = $("#default_shirt_length").val();
    var left_sleeve_length = $("#input_left_sleeve").val();
    var default_left_sleeve = $("#default_left_sleeve").val();

    var right_sleeve_length = $("#input_right_sleeve").val();
    var default_right_sleeve = $("#default_right_sleeve").val();

    var suit_half_wrist_length = $("#input_half-wrist").val();
    var default_half_wrist = $("#default_half-wrist").val();
  
  // fixed these vare for half waist.
  var suit_half_waist_length = $("#input_half-waist").val();
    var default_half_waist = $("#default_half-waist").val();

    var suit_half_chest_length = $("#input_half-chest").val();
    var default_half_chest = $("#default_half-chest").val();

    
    var suit_half_hip_length = $("#input_half-hip").val();
    var default_half_hip = $("#default_half-hip").val();

    var suit_half_shoulder_length = $("#input_half-shoulder").val();
    var default_half_shoulder = $("#default_half-shoulder").val();

    
    var suit_arm_hole_length = $("#input_arm-hole").val();
    var default_arm_hole = $("#default_arm-hole").val();

    const checked = $("#cm_change").is(":checked");

    if (checked == true) {
      console.log("CHEJFK");
      $(".shirt_length_display").text(
        parseInt(default_shirt_length, 10) + parseInt(shirt_length, 10)
      );
      $(".left_sleeve_display").text(
        parseInt(default_left_sleeve, 10) + parseInt(left_sleeve_length, 10)
      );
      $(".right_sleeve_display").text(
        parseInt(default_right_sleeve, 10) + parseInt(right_sleeve_length, 10)
      );
      $(".suits_length_display").text(
        parseInt(default_half_wrist, 10) + parseInt(suit_half_wrist_length, 10)
      );
  // for half waist
  $(".suits_half_waist_display").text(
        parseInt(default_half_waist, 10) + parseInt(suit_half_waist_length, 10)
      );
      $(".shirt_chest_display").text(
        parseInt(default_half_chest, 10) + parseInt(suit_half_chest_length, 10)
      );
      $(".shirt_hip_display").text(
        parseInt(default_half_hip, 10) + parseInt(suit_half_hip_length, 10)
      );
      $(".shirt_arm_hole_display").text(
        parseInt(default_arm_hole, 10) + parseInt(suit_arm_hole_length, 10)
      );
      $(".shirt_shoulder_display").text(
        parseInt(default_half_shoulder, 10) + parseInt(suit_half_shoulder_length, 10)
      );
    } else {
      $(".shirt_length_display").text(
        cm_to_in(
          parseInt(default_shirt_length, 10) + parseInt(shirt_length, 10)
        ).toFixed(2)
      );
      $(".left_sleeve_display").text(
        cm_to_in(
          parseInt(default_left_sleeve, 10) + parseInt(left_sleeve_length, 10)
        ).toFixed(2)
      );
      $(".right_sleeve_display").text(
        cm_to_in(
          parseInt(default_right_sleeve, 10) +
            parseInt(right_sleeve_length, 10)
        ).toFixed(2)
      );
      $(".suits_length_display").text(
        cm_to_in(
          parseInt(default_half_wrist, 10) + parseInt(suit_half_wrist_length, 10)
        ).toFixed(2)
      );
  // half waist display
  $(".suits_half_waist_display").text(
        cm_to_in(
          parseInt(default_half_waistist, 10) + parseInt(suit_half_waist_length, 10)
        ).toFixed(2)
      );
      $(".shirt_chest_display").text(
        cm_to_in(
          parseInt(default_half_chest, 10) + parseInt(suit_half_chest_length, 10)
        ).toFixed(2)
      );
      $(".shirt_hip_display").text(
        cm_to_in(
          parseInt(default_half_hip, 10) + parseInt(suit_half_hip_length, 10)
        ).toFixed(2)
      );
      $(".shirt_arm_hole_display").text(
        cm_to_in(
          parseInt(default_arm_hole, 10) + parseInt(suit_arm_hole_length, 10)
        ).toFixed(2)
      );
      $(".shirt_shoulder_display").text(
        cm_to_in(
          parseInt(default_half_shoulder, 10) + parseInt(suit_half_shoulder_length, 10)
        ).toFixed(2)
      );
    }

    $("#main_shirt_length").val(
      parseInt(default_shirt_length, 10) + parseInt(shirt_length, 10)
    );
    $("#main_left_sleeve").val(
      parseInt(default_left_sleeve, 10) + parseInt(left_sleeve_length, 10)
    );
    $("#main_right_sleeve").val(
      parseInt(default_right_sleeve, 10) + parseInt(right_sleeve_length, 10)
    );

    $("#main_half_wrist").val(
      parseInt(default_half_wrist, 10) + parseInt(suit_half_wrist_length, 10)
    );
    $("#main_half_chest").val(
      parseInt(default_half_chest, 10) + parseInt(suit_half_chest_length, 10)
    );
    $("#main_half_hip").val(
      parseInt(default_half_hip, 10) + parseInt(suit_half_hip_length, 10)
    );
    $("#main_arm_hole").val(
      parseInt(default_arm_hole, 10) + parseInt(suit_arm_hole_length, 10)
    );
    $("#main_shoulder").val(
      parseInt(default_half_shoulder, 10) + parseInt(suit_half_shoulder_length, 10)
    );
  }

  
  $(".add").click(function () {
    //if ($(this).prev().val() < 3) {
    $(this)
      .prev()
      .val(+$(this).prev().val() + 1);
    $(this).parent().find(".qty_text").text($(this).prev().val());
    //}
    console.log("WHATTTT");
    change_text_qty();
  });
  $(".sub").click(function () {
    // if ($(this).next().val() > 1) {
    $(this)
      .next()
      .val(+$(this).next().val() - 1);
    $(this).parent().find(".qty_text").text($(this).next().val());
    // }
    change_text_qty();
  });

  //$( '.custom_var ul li' ).on( 'click', function() {
  $(document).on("click", ".custom_var ul li", function () {
    $(this).parent().find("li.active").removeClass("active");
    $(this).addClass("active");
  });

  // $( '#fabric-size' ).on( 'click', function() {

  //      $(".collection-form").fadeIn();
  //      $(".submit-formm ").hide();
  //  });

  // $( '.next-btnn a' ).on( 'click', function() {
  //     $(".collection-form").hide();
  //      $(".submit-formm ").fadeIn();

  //  });
});

function increaseValue() {
  var value = parseInt(document.getElementById("number").value, 10);
  value = isNaN(value) ? 0 : value;
  value++;
  document.getElementById("number").value = value;
  document.getElementById("number2").value = value;
}

function decreaseValue() {
  var value = parseInt(document.getElementById("number").value, 10);
  value = isNaN(value) ? 0 : value;

  value--;
  document.getElementById("number").value = value;
  document.getElementById("number2").value = value;
}

/********************/

// jQuery(document).ready(function(){
// 	  jQuery(document).on('click','.chek_item',function(){
// 	  	var post_id = jQuery(this).attr('id');
// 	  	jQuery('.checkboxType').prop('checked', false);
// 	  	jQuery('.fabric_type'+post_id).prop('checked', true);
//      if(jQuery('.fabric_type'+post_id).is(":checked"))
//        {
//         jQuery('.custom_form').addClass('form_div');
//         jQuery('.custom_field_'+post_id).removeClass('form_div');
//        }
//       });

//   jQuery(document).on('click','.plus',function(){
//       var val = jQuery(this).prev().prev().val();
//        if(val < 10){ val ++; }
//        jQuery(this).prev().prev().val(val);
//       });
//   jQuery(document).on('click','.minus',function(){
//   var val = jQuery(this).prev().val();
//        if(val > 0){ val --; }
//        jQuery(this).prev().val(val);
//   });

//   jQuery(document).on('click','.retailer_form_submit',function(e){
//   	 e.preventDefault();
//   	 var post_id = jQuery(this).attr('post_id');
//   	 var form_data = jQuery('#retailer_form_'+post_id).serialize();
//   	 var ajax_url = "<?php echo admin_url( 'admin-ajax.php' ); ?>";
//        jQuery.ajax({
// 	    type: "post",
// 	    dataType: "json",
// 	    url: ajax_url,
// 	    data: {action:"send_email",form_data:form_data},
// 	    success: function(response){
// 	        console.log(response);
// 	    }
// 	});
//   });

// });

$(window).on("resize", function () {
  var maxHeight = 0;

  $(".our-roots").each(function () {
    if ($(this).height() > maxHeight) {
      maxHeight = $(this).height();
    }
  });

  $(".our-roots").height(maxHeight);
});

/*$(document).on("click", ".click-measure-shirt", function () {
    window.location.href = "/custom-made/#measuree-shirt";
  $("html, body").animate({ scrollTop: 0 }, "slow");
});
$(window).bind("load", function() {
      $('#measuree-shirt').trigger('click');
  })

$(document).on("click", ".click-measure-body", function () {
    window.location.href = "/custom-made/";
});*/

$(document).on("click", ".click-measure-shirt", function () {
  window.location.href = "/tailor-made/?measuree=shirt";
  $("html, body").animate({ scrollTop: 0 }, "slow");
});

$(document).ready(function () {
  $(".play-pause-btn").on("click", function () {
    if ($(this).attr("data-click") == 1) {
      $(this).attr("data-click", 0);
      $(this).html('<i class="fa fa-pause" aria-hidden="true"></i>');
      $(".play-btnn")[0].play();
    } else {
      $(this).attr("data-click", 1);
      $(this).html('<i class="fa fa-play" aria-hidden="true"></i>');
      $(".play-btnn")[0].pause();
    }
  });

  $(".play-pause-btn2").on("click", function () {
    if ($(this).attr("data-click2") == 1) {
      $(this).attr("data-click2", 0);
      $(this).html('<i class="fa fa-pause" aria-hidden="true"></i>');
      $(".play-btnn2")[0].play();
    } else {
      $(this).attr("data-click2", 1);
      $(this).html('<i class="fa fa-play" aria-hidden="true"></i>');
      $(".play-btnn2")[0].pause();
    }
  });
});

$(document).ready(function () {
    
      $(".play-main-video").on("click", function () {
    if ($(this).attr("data-click") == 0) {
      $(this).attr("data-click", 1);
      $("#myVideo")[0].play();
    }
  });
    
    
    

// Attach event handler
$('#myVideo').on('click', function(event) {
  event.preventDefault();
  $('#myVideo')[0].play();
});
// Trigger click event
$('#myVideo').trigger('click');

    
    
      $('#myVideo_branding_left').on('click', function(event) {
  event.preventDefault();
  $('#myVideo_branding_left')[0].play();
});
// Trigger click event
$('#myVideo_branding_left').trigger('click');

    
    

 // var figure = $(".youtube-section");
 //      var vid = $("video");

 //      [].forEach.call(figure, function (item) {
 //              item.addEventListener('mouseover', hoverVideo, false);
 //              item.addEventListener('mouseout', hideVideo, false);
 //      });
      
 //      function hoverVideo(e) {  
 //             // $(this).find('.play-btnn')[0].play();
 //              $(this).find('.play-btnn2')[0].play();
 //      }

 //      function hideVideo(e) {
 //             // $(this).find('.play-btnn')[0].pause(); 
 //              $(this).find('.play-btnn2')[0].pause(); 

 //      }


  $(".play-btnn").on("ended", function () {
    $(".play-btnn2")[0].play();
  });

  function playFile() {
    $(".inlineVideo")
      .not(this)
      .each(function () {
        $(this).get(0).pause();
        $(this).next().html('<i class="fa fa-play" aria-hidden="true"></i>');
      });
  }

  $(".inlineVideo").on("click play", function () {
    this.onplaying = function () {
      playFile.call(this);
    };
  });

  /****************slider***************/
    
    
     $(".regularr").on("click", function () {
         $(".font-style").css('font-style', 'inherit')
          $(".font-style").css('font-weight', '400')
     });
     $(".italicc").on("click", function () {
         $(".font-style").css('font-style', 'italic')
           $(".font-style").css('font-weight', '400')
     });
     $(".boldd").on("click", function () {
         $(".font-style").css('font-weight', '500')
           $(".font-style").css('font-style', 'inherit')
     });
    
    
    $("#measuree-shirt").on("click", function () {
         $(".main_product_section").css('background', 'rgb(221 221 221)')
     });
    
     $("#measuree-body").on("click", function () {
         $(".main_product_section").css('background', 'rgb(239 239 239)')
     });
    
    

  

  $(".twist-pgs").slick({
    accessibility: false, //prevent scroll to top
    lazyLoad: "progressive",
    slidesToShow: 1,
    slidesToScroll: 1,
    arrows: true,
    fade: false,
    swipe:false,
    prevArrow: '<i class="btn-prev dashicons dashicons-arrow-left-alt2"></i>',
    nextArrow:
      '<i class="btn-next dashicons dashicons-arrow-right-alt2"></i>',
    rtl: false,
    infinite: false,
    autoplay: false,
    pauseOnDotsHover: true,
    autoplaySpeed: "0",
    asNavFor: "#slide-nav-vertical",
    dots: false,
    draggable: false,
      verticalSwiping: true,
    responsive: [
      {
        breakpoint: 767,
        settings: {
          swipe: false,
          draggable: false,
          autoplay: false, //no autoplay in mobile
          isMobile: true, // let custom knows on mobile
          arrows: true, //hide arrow on mobile
        },
      },
    ],
  });

//   $("#slide-nav-vertical").slick({
//     accessibility: false, //prevent scroll to top
   
//     arrows: true,
//     slidesToShow: 5,
//     slidesToScroll: 1,
//     infinite: false,
//     asNavFor: ".twist-pgs",
//     prevArrow: '<i class="btn-prev dashicons dashicons-arrow-left-alt2"></i>',
//     nextArrow:
//       '<i class="btn-next dashicons dashicons-arrow-right-alt2"></i>',

//     dots: false,
//     centerMode: false,

//     rtl: false,
//     vertical: true,

//     draggable: true,
//     focusOnSelect: true,

//     responsive: [
//       {
//         breakpoint: 767,
//         settings: {
//             slidesToShow: 5,
//           draggable: true,
//           autoplay: false, //no autoplay in mobile
         
//           arrows: true, //hide arrow on mobile
//         },
//       },
//     ],
//   });
$(".slider-nav").slick({
    accessibility: false, //prevent scroll to top
    arrows: true,
    slidesToShow: 5,
    slidesToScroll: 1,
    infinite: false,
    asNavFor: ".twist-pgs",
    prevArrow: '<i class="btn-prev dashicons dashicons-arrow-left-alt2"></i>',
    nextArrow: '<i class="btn-next dashicons dashicons-arrow-right-alt2"></i>',

    dots: false,
    centerMode: false,

    rtl: false,
    vertical: true,

    draggable: true,
    focusOnSelect: true,

    responsive: [
      {
        breakpoint: 767,
        settings: {
          slidesToShow: 5,
          draggable: true,
          autoplay: false, //no autoplay in mobile
          arrows: true, //hide arrow on mobile
        },
      },
    ],
  });
 $(".single_product_image").zoom({
    
  }); 
    
    $(".retailer-filter-btn a").on("click", function () {
    var target = $(this).attr("fabric-id");
    target = "#" + target;
    $(".mble-hide").hide();
    $(target).show();
    $(".retailer-filter-btn a").removeClass("active");
    $(this).addClass("active");
  }); 
    
}); 
  
   $(window).load(function() {
         $(".play-main-video").trigger("click"); 
    }); 
  
  var testVar = "<?=get_field('field_name'); ?>";
  if(testVar !== null && data !== '') {
    var element = document.getElementByClass("stock");
    element.classList.add("otherclass");
  }
});






