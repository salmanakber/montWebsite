        <?php

        ob_start();

            /*

            Template Name: Template-New-Retailor

            */

            ?>
            <style type="text/css">
               
</style>

<?php get_header(); ?>
<?php
$phoneCodes = [
    '44' => 'UK (+44)',
    '1' => 'USA (+1)',
    '213' => 'Algeria (+213)',
    '376' => 'Andorra (+376)',
    '244' => 'Angola (+244)',
    '1264' => 'Anguilla (+1264)',
    '1268' => 'Antigua & Barbuda (+1268)',
    '54' => 'Argentina (+54)',
    '374' => 'Armenia (+374)',
    '297' => 'Aruba (+297)',
    '61' => 'Australia (+61)',
    '43' => 'Austria (+43)',
    '994' => 'Azerbaijan (+994)',
    '1242' => 'Bahamas (+1242)',
    '973' => 'Bahrain (+973)',
    '880' => 'Bangladesh (+880)',
    '1246' => 'Barbados (+1246)',
    '375' => 'Belarus (+375)',
    '32' => 'Belgium (+32)',
    '501' => 'Belize (+501)',
    '229' => 'Benin (+229)',
    '1441' => 'Bermuda (+1441)',
    '975' => 'Bhutan (+975)',
    '591' => 'Bolivia (+591)',
    '387' => 'Bosnia Herzegovina (+387)',
    '267' => 'Botswana (+267)',
    '55' => 'Brazil (+55)',
    '673' => 'Brunei (+673)',
    '359' => 'Bulgaria (+359)',
    '226' => 'Burkina Faso (+226)',
    '257' => 'Burundi (+257)',
    '855' => 'Cambodia (+855)',
    '237' => 'Cameroon (+237)',
    '1' => 'Canada (+1)',
    '238' => 'Cape Verde Islands (+238)',
    '1345' => 'Cayman Islands (+1345)',
    '236' => 'Central African Republic (+236)',
    '56' => 'Chile (+56)',
    '86' => 'China (+86)',
    '57' => 'Colombia (+57)',
    '269' => 'Comoros (+269)',
    '242' => 'Congo (+242)',
    '682' => 'Cook Islands (+682)',
    '506' => 'Costa Rica (+506)',
    '385' => 'Croatia (+385)',
    '53' => 'Cuba (+53)',
    '90392' => 'Cyprus North (+90392)',
    '357' => 'Cyprus South (+357)',
    '42' => 'Czech Republic (+42)',
    '45' => 'Denmark (+45)',
    '253' => 'Djibouti (+253)',
    '1809' => 'Dominica (+1809)',
    '1809' => 'Dominican Republic (+1809)',
    '593' => 'Ecuador (+593)',
    '20' => 'Egypt (+20)',
    '503' => 'El Salvador (+503)',
    '240' => 'Equatorial Guinea (+240)',
    '291' => 'Eritrea (+291)',
    '372' => 'Estonia (+372)',
    '251' => 'Ethiopia (+251)',
    '500' => 'Falkland Islands (+500)',
    '298' => 'Faroe Islands (+298)',
    '679' => 'Fiji (+679)',
    '358' => 'Finland (+358)',
    '33' => 'France (+33)',
    '594' => 'French Guiana (+594)',
    '689' => 'French Polynesia (+689)',
    '241' => 'Gabon (+241)',
    '220' => 'Gambia (+220)',
    '7880' => 'Georgia (+7880)',
    '49' => 'Germany (+49)',
    '233' => 'Ghana (+233)',
    '350' => 'Gibraltar (+350)',
    '30' => 'Greece (+30)',
    '299' => 'Greenland (+299)',
    '1473' => 'Grenada (+1473)',
    '590' => 'Guadeloupe (+590)',
    '671' => 'Guam (+671)',
    '502' => 'Guatemala (+502)',
    '224' => 'Guinea (+224)',
    '245' => 'Guinea - Bissau (+245)',
    '592' => 'Guyana (+592)',
    '509' => 'Haiti (+509)',
    '504' => 'Honduras (+504)',
    '852' => 'Hong Kong (+852)',
    '36' => 'Hungary (+36)',
    '354' => 'Iceland (+354)',
    '91' => 'India (+91)',
    '62' => 'Indonesia (+62)',
    '98' => 'Iran (+98)',
    '964' => 'Iraq (+964)',
    '353' => 'Ireland (+353)',
    '972' => 'Israel (+972)',
    '39' => 'Italy (+39)',
    '1876' => 'Jamaica (+1876)',
    '81' => 'Japan (+81)',
    '962' => 'Jordan (+962)',
    '7' => 'Kazakhstan (+7)',
    '254' => 'Kenya (+254)',
    '686' => 'Kiribati (+686)',
    '850' => 'Korea North (+850)',
    '82' => 'Korea South (+82)',
    '965' => 'Kuwait (+965)',
    '996' => 'Kyrgyzstan (+996)',
    '856' => 'Laos (+856)',
    '371' => 'Latvia (+371)',
    '961' => 'Lebanon (+961)',
    '266' => 'Lesotho (+266)',
    '231' => 'Liberia (+231)',
    '218' => 'Libya (+218)',
    '417' => 'Liechtenstein (+417)',
    '370' => 'Lithuania (+370)',
    '352' => 'Luxembourg (+352)',
    '853' => 'Macao (+853)',
    '389' => 'Macedonia (+389)',
    '261' => 'Madagascar (+261)',
    '265' => 'Malawi (+265)',
    '60' => 'Malaysia (+60)',
    '960' => 'Maldives (+960)',
    '223' => 'Mali (+223)',
    '356' => 'Malta (+356)',
    '692' => 'Marshall Islands (+692)',
    '596' => 'Martinique (+596)',
    '222' => 'Mauritania (+222)',
    '269' => 'Mayotte (+269)',
    '52' => 'Mexico (+52)',
    '691' => 'Micronesia (+691)',
    '373' => 'Moldova (+373)',
    '377' => 'Monaco (+377)',
    '976' => 'Mongolia (+976)',
    '1664' => 'Montserrat (+1664)',
    '212' => 'Morocco (+212)',
    '258' => 'Mozambique (+258)',
    '95' => 'Myanmar (+95)',
    '264' => 'Namibia (+264)',
    '674' => 'Nauru (+674)',
    '977' => 'Nepal (+977)',
    '31' => 'Netherlands (+31)',
    '687' => 'New Caledonia (+687)',
    '64' => 'New Zealand (+64)',
    '505' => 'Nicaragua (+505)',
    '227' => 'Niger (+227)',
    '234' => 'Nigeria (+234)',
    '683' => 'Niue (+683)',
    '672' => 'Norfolk Islands (+672)',
    '670' => 'Northern Marianas (+670)',
    '47' => 'Norway (+47)',
    '968' => 'Oman (+968)',
    '680' => 'Palau (+680)',
    '507' => 'Panama (+507)',
    '675' => 'Papua New Guinea (+675)',
    '595' => 'Paraguay (+595)',
    '51' => 'Peru (+51)',
    '63' => 'Philippines (+63)',
    '48' => 'Poland (+48)',
    '351' => 'Portugal (+351)',
    '1787' => 'Puerto Rico (+1787)',
    '974' => 'Qatar (+974)',
    '262' => 'Reunion (+262)',
    '40' => 'Romania (+40)',
    '7' => 'Russia (+7)',
    '250' => 'Rwanda (+250)',
    '378' => 'San Marino (+378)',
    '239' => 'Sao Tome & Principe (+239)',
    '966' => 'Saudi Arabia (+966)',
    '221' => 'Senegal (+221)',
    '381' => 'Serbia (+381)',
    '248' => 'Seychelles (+248)',
    '232' => 'Sierra Leone (+232)',
    '65' => 'Singapore (+65)',
    '421' => 'Slovak Republic (+421)',
    '386' => 'Slovenia (+386)',
    '677' => 'Solomon Islands (+677)',
    '252' => 'Somalia (+252)',
    '27' => 'South Africa (+27)',
    '34' => 'Spain (+34)',
    '94' => 'Sri Lanka (+94)',
    '290' => 'St. Helena (+290)',
    '1869' => 'St. Kitts (+1869)',
    '1758' => 'St. Lucia (+1758)',
    '249' => 'Sudan (+249)',
    '597' => 'Suriname (+597)',
    '268' => 'Swaziland (+268)',
    '46' => 'Sweden (+46)',
    '41' => 'Switzerland (+41)',
    '963' => 'Syria (+963)',
    '886' => 'Taiwan (+886)',
    '7' => 'Tajikstan (+7)',
    '66' => 'Thailand (+66)',
    '228' => 'Togo (+228)',
    '676' => 'Tonga (+676)',
    '1868' => 'Trinidad & Tobago (+1868)',
    '216' => 'Tunisia (+216)',
    '90' => 'Turkey (+90)',
    '7' => 'Turkmenistan (+7)',
    '993' => 'Turkmenistan (+993)',
    '1649' => 'Turks & Caicos Islands (+1649)',
    '688' => 'Tuvalu (+688)',
    '256' => 'Uganda (+256)',
    '380' => 'Ukraine (+380)',
    '971' => 'United Arab Emirates (+971)',
    '598' => 'Uruguay (+598)',
    '7' => 'Uzbekistan (+7)',
    '678' => 'Vanuatu (+678)',
    '379' => 'Vatican City (+379)',
    '58' => 'Venezuela (+58)',
    '84' => 'Vietnam (+84)',
    '84' => 'Virgin Islands - British (+1284)',
    '84' => 'Virgin Islands - US (+1340)',
    '681' => 'Wallis & Futuna (+681)',
    '969' => 'Yemen (North)(+969)',
    '967' => 'Yemen (South)(+967)',
    '260' => 'Zambia (+260)',
    '263' => 'Zimbabwe (+263)',
];

?>

<section class="second-section cloth-color">
  
<!--   <div class="loader"></div> -->

<div class="container">

    <?php if (isset($_GET['thankyou']) && $_GET['thankyou'] == 1) { ?>

        <div class="retailer_thankyou">

            <div class="th_high">Thanks,</div>



            Thank you for submitting your request we will get in touch with you shortly.

        </div>

    <?php } else { ?>

        <div class="row justify-content-center">

            <form id="retailer_form" class="hide_class" method="post" enctype="multipart/form-data">

                <div class="tabs">

                    <ul class="tabs-list">

                        <li class="active new"><a href="#tab1">Formal</a></li>
 						<li class="new"><a href="#tab5">Flanell</a></li>
                        <li class="new"><a href="#tab2">Lin</a></li>
 						<li class="new"><a href="#tab4">Stretch Bomull</a></li>
						 <li class="new"><a href="#tab6">Knit</a></li>
                        <li class="new"><a href="#tab7">Cordy</a></li>
                        <li class="new"><a href="#tab3">Oxford</a></li>
                       </ul>





                    <div class="tab" id="tab1">

                        <div class="col-12 fixed-heightt chkbx-design  " id="fabric-select " style="display:block;">

                            <div class="row">

                                <?php

                                $args = array(

                                    'post_type' => 'retailer',

                                    'post_status' => 'publish',

                                    'posts_per_page' => 6,

                                    'tax_query' => array(

                                        array(

                                            'taxonomy' => 'retailer_category',

                                            'field'    => 'slug',

                                            'terms'    => array( 'formal' ),

                                            'operator' => 'IN'

                                        ),

                                    ),

                                );



                                $arr_posts = new WP_Query( $args );

                                $loop = new WP_Query($args);

                                while ($loop->have_posts()) : $loop->the_post(); ?>

                                    <div class="col-md-4  col-6">

                                        <div class="featured-bg chek_item chek_img_<?php echo $post->ID; ?>" id="<?php echo $post->ID; ?>">

                                            <div class="shirt-itm">

                                                <figure class="zoom" onmousemove="zoom(event);" style="background-image: url(<?php the_post_thumbnail_url('full'); ?>); background-position: 92% 66.9039%;">

                                                    <img src="<?php echo wp_get_attachment_image_src( get_post_thumbnail_id(), 'full' )[0]; ?>">


                                                </figure>

                                            </div>

                                        </div>

                                        <div class="amazingClass chek_item chek_item_<?php echo $post->ID; ?>" id="<?php echo $post->ID; ?>" style="cursor: pointer;">

                                            <input type="radio" name="fabric_type[]" id="fabric_type<?php echo $post->ID; ?>" class="checkboxType" value="<?php echo $post->ID; ?>">

                                            <label for="fabric_type<?php echo $post->ID; ?>" class="control-label"><?php the_title(); ?></label>

                                        </div>

                                        <!-- <p> -->
                                            <?php //the_content(); ?>
                                            <!-- </p> -->

                                        </div>

                                    <?php endwhile;

                                    wp_reset_postdata(); ?>

                                </div>

                            </div>

                        </div>

                        <div class="tab" id="tab2">

                            <div class="col-12 fixed-heightt chkbx-design mble-hide " id="fabric-select " style="display:block;">

                                <div class="row">

                                    <?php

                                    $args = array(

                                        'post_type' => 'retailer',

                                        'post_status' => 'publish',

                                        'posts_per_page' => 6,

                                        'tax_query' => array(

                                            array(

                                                'taxonomy' => 'retailer_category',

                                                'field'    => 'slug',

                                                'terms'    => array( 'linen' ),

                                                'operator' => 'IN'

                                            ),

                                        ),

                                    );



                                    $arr_posts = new WP_Query( $args );

                                    $loop = new WP_Query($args);

                                    while ($loop->have_posts()) : $loop->the_post(); ?>

                                        <div class="col-md-4  col-6">

                                            <div class="featured-bg chek_item chek_img_<?php echo $post->ID; ?>" id="<?php echo $post->ID; ?>">

                                                <div class="shirt-itm">

                                                    <figure class="zoom" onmousemove="zoom(event);" style="background-image: url(<?php the_post_thumbnail_url('full'); ?>); background-position: 92% 66.9039%;">

                                                        <img src="<?php echo wp_get_attachment_image_src( get_post_thumbnail_id(), 'full' )[0]; ?>">


                                                    </figure>

                                                </div>

                                            </div>

                                            <div class="amazingClass chek_item chek_item_<?php echo $post->ID; ?>" id="<?php echo $post->ID; ?>" style="cursor: pointer;">

                                                <input type="radio" name="fabric_type[]" id="fabric_type<?php echo $post->ID; ?>" class="checkboxType" value="<?php echo $post->ID; ?>">

                                                <label for="fabric_type<?php echo $post->ID; ?>" class="control-label"><?php the_title(); ?></label>

                                            </div>

                                            <p>
                                                <?php// the_content(); ?>
                                            </p>

                                        </div>

                                    <?php endwhile;

                                    wp_reset_postdata(); ?>

                                </div>

                            </div>

                        </div>

                        <div class="tab" id="tab3">

                            <div class="col-12 fixed-heightt chkbx-design mble-hide " id="fabric-select " style="display:block;">

                                <div class="row">

                                    <?php

                                    $args = array(

                                        'post_type' => 'retailer',

                                        'post_status' => 'publish',

                                        'posts_per_page' => 6,

                                        'tax_query' => array(

                                            array(

                                                'taxonomy' => 'retailer_category',

                                                'field'    => 'slug',

                                                'terms'    => array( 'oxford-2' ),

                                                'operator' => 'IN'

                                            ),

                                        ),

                                    );

                                    $loop = new WP_Query($args);

                                    while ($loop->have_posts()) : $loop->the_post(); ?>

                                        <div class="col-md-4 col-6">

                                            <div class="featured-bg chek_item chek_img_<?php echo $post->ID; ?>" id="<?php echo $post->ID; ?>">

                                                <div class="shirt-itm">

                                                    <figure class="zoom" onmousemove="zoom(event);" style="background-image: url(<?php the_post_thumbnail_url('full'); ?>); background-position: 92% 66.9039%;">

                                                        <img src="<?php echo wp_get_attachment_image_src( get_post_thumbnail_id(), 'full' )[0]; ?>">


                                                    </figure>

                                                </div>

                                            </div>

                                            <div class="amazingClass chek_item chek_item_<?php echo $post->ID; ?>" id="<?php echo $post->ID; ?>" style="cursor: pointer;">

                                                <input type="radio" name="fabric_type[]" id="fabric_type<?php echo $post->ID; ?>" class="checkboxType" value="<?php echo $post->ID; ?>">

                                                <label for="fabric_type<?php echo $post->ID; ?>" class="control-label"><?php the_title(); ?></label>

                                            </div>

                                            <p>
                                                <?php //the_content(); ?>
                                            </p>

                                        </div>

                                    <?php endwhile;

                                    wp_reset_postdata(); ?>

                                </div>

                            </div>

                        </div>

                        <div class="tab" id="tab4">

                            <div class="col-12 fixed-heightt chkbx-design mble-hide " id="fabric-select " style="display:block;">

                                <div class="row">

                                    <?php

                                    $args = array(

                                        'post_type' => 'retailer',

                                        'post_status' => 'publish',

                                        'posts_per_page' => 6,

                                        'tax_query' => array(

                                            array(

                                                'taxonomy' => 'retailer_category',

                                                'field'    => 'slug',

                                                'terms'    => array( 'denim' ),

                                                'operator' => 'IN'

                                            ),

                                        ),

                                    );

                                    $loop = new WP_Query($args);

                                    while ($loop->have_posts()) : $loop->the_post(); ?>

                                        <div class="col-md-4  col-6">

                                            <div class="featured-bg chek_item chek_img_<?php echo $post->ID; ?>" id="<?php echo $post->ID; ?>">

                                                <div class="shirt-itm">

                                                    <figure class="zoom" onmousemove="zoom(event);" style="background-image: url(<?php the_post_thumbnail_url('full'); ?>); background-position: 92% 66.9039%;">

                                                        <img src="<?php echo wp_get_attachment_image_src( get_post_thumbnail_id(), 'full' )[0]; ?>">


                                                    </figure>

                                                </div>

                                            </div>

                                            <div class="amazingClass chek_item chek_item_<?php echo $post->ID; ?>" id="<?php echo $post->ID; ?>" style="cursor: pointer;">

                                                <input type="radio" name="fabric_type[]" id="fabric_type<?php echo $post->ID; ?>" class="checkboxType" value="<?php echo $post->ID; ?>">

                                                <label for="fabric_type<?php echo $post->ID; ?>" class="control-label"><?php the_title(); ?></label>

                                            </div>

                                            <p>
                                                <?php //the_content(); ?>
                                            </p>

                                        </div>

                                    <?php endwhile;

                                    wp_reset_postdata(); ?>

                                </div>

                            </div>

                        </div>

                        <div class="tab" id="tab5">

                            <div class="col-12 fixed-heightt chkbx-design mble-hide " id="fabric-select " style="display:block;">

                                <div class="row">

                                    <?php

                                    $args = array(

                                        'post_type' => 'retailer',

                                        'post_status' => 'publish',

                                        'posts_per_page' => 6,

                                        'tax_query' => array(

                                            array(

                                                'taxonomy' => 'retailer_category',

                                                'field'    => 'slug',

                                                'terms'    => array( 'flannels' ),

                                                'operator' => 'IN'

                                            ),

                                        ),

                                    );

                                    $loop = new WP_Query($args);

                                    while ($loop->have_posts()) : $loop->the_post(); ?>

                                        <div class="col-md-4 col-6">

                                            <div class="featured-bg chek_item chek_img_<?php echo $post->ID; ?>" id="<?php echo $post->ID; ?>">

                                                <div class="shirt-itm">

                                                    <figure class="zoom" onmousemove="zoom(event);" style="background-image: url(<?php the_post_thumbnail_url('full'); ?>); background-position: 92% 66.9039%;">

                                                        <img src="<?php echo wp_get_attachment_image_src( get_post_thumbnail_id(), 'full' )[0]; ?>">


                                                    </figure>

                                                </div>

                                            </div>

                                            <div class="amazingClass chek_item chek_item_<?php echo $post->ID; ?>" id="<?php echo $post->ID; ?>" style="cursor: pointer;">

                                                <input type="radio" name="fabric_type[]" id="fabric_type<?php echo $post->ID; ?>" class="checkboxType" value="<?php echo $post->ID; ?>">

                                                <label for="fabric_type<?php echo $post->ID; ?>" class="control-label"><?php the_title(); ?></label>

                                            </div>

                                            <p>
                                                <?php //the_content(); ?>
                                            </p>

                                        </div>

                                    <?php endwhile;

                                    wp_reset_postdata(); ?>

                                </div>

                            </div>

                        </div>
                        <div class="tab" id="tab6">

                            <div class="col-12 fixed-heightt chkbx-design mble-hide " id="fabric-select " style="display:block;">

                                <div class="row">

                                    <?php

                                    $args = array(

                                        'post_type' => 'retailer',

                                        'post_status' => 'publish',

                                        'posts_per_page' => 6,

                                        'tax_query' => array(

                                            array(

                                                'taxonomy' => 'retailer_category',

                                                'field'    => 'slug',

                                                'terms'    => array( 'knit' ),

                                                'operator' => 'IN'

                                            ),

                                        ),

                                    );

                                    $loop = new WP_Query($args);

                                    while ($loop->have_posts()) : $loop->the_post(); ?>

                                        <div class="col-md-4 col-6">

                                            <div class="featured-bg chek_item chek_img_<?php echo $post->ID; ?>" id="<?php echo $post->ID; ?>">

                                                <div class="shirt-itm">

                                                    <figure class="zoom" onmousemove="zoom(event);" style="background-image: url(<?php the_post_thumbnail_url('full'); ?>); background-position: 92% 66.9039%;">

                                                        <img src="<?php echo wp_get_attachment_image_src( get_post_thumbnail_id(), 'full' )[0]; ?>">


                                                    </figure>

                                                </div>

                                            </div>

                                            <div class="amazingClass chek_item chek_item_<?php echo $post->ID; ?>" id="<?php echo $post->ID; ?>" style="cursor: pointer;">

                                                <input type="radio" name="fabric_type[]" id="fabric_type<?php echo $post->ID; ?>" class="checkboxType" value="<?php echo $post->ID; ?>">

                                                <label for="fabric_type<?php echo $post->ID; ?>" class="control-label"><?php the_title(); ?></label>

                                            </div>

                                            <p>
                                                <?php //the_content(); ?>
                                            </p>

                                        </div>

                                    <?php endwhile;

                                    wp_reset_postdata(); ?>

                                </div>

                            </div>

                        </div>

                        <div class="tab" id="tab7">

                            <div class="col-12 fixed-heightt chkbx-design mble-hide " id="fabric-select " style="display:block;">

                                <div class="row">

                                    <?php

                                    $args = array(

                                        'post_type' => 'retailer',

                                        'post_status' => 'publish',

                                        'posts_per_page' => 6,

                                        'tax_query' => array(

                                            array(

                                                'taxonomy' => 'retailer_category',

                                                'field'    => 'slug',

                                                'terms'    => array( 'print-2' ),

                                                'operator' => 'IN'

                                            ),

                                        ),

                                    );

                                    $loop = new WP_Query($args);

                                    while ($loop->have_posts()) : $loop->the_post(); ?>

                                        <div class="col-md-4 col-6">

                                            <div class="featured-bg chek_item chek_img_<?php echo $post->ID; ?>" id="<?php echo $post->ID; ?>">

                                                <div class="shirt-itm">

                                                    <figure class="zoom" onmousemove="zoom(event);" style="background-image: url(<?php the_post_thumbnail_url('full'); ?>); background-position: 92% 66.9039%;">

                                                        <img src="<?php echo wp_get_attachment_image_src( get_post_thumbnail_id(), 'full' )[0]; ?>">


                                                    </figure>

                                                </div>

                                            </div>

                                            <div class="amazingClass chek_item chek_item_<?php echo $post->ID; ?>" id="<?php echo $post->ID; ?>" style="cursor: pointer;">

                                                <input type="radio" name="fabric_type[]" id="fabric_type<?php echo $post->ID; ?>" class="checkboxType" value="<?php echo $post->ID; ?>">

                                                <label for="fabric_type<?php echo $post->ID; ?>" class="control-label"><?php the_title(); ?></label>

                                            </div>

                                            <p>
                                                <?php //the_content(); ?>
                                            </p>

                                        </div>

                                    <?php endwhile;

                                    wp_reset_postdata(); ?>

                                </div>

                            </div>

                        </div>



                    </div>

                    <div class="col-4 wd100-sml mble-hide" id="filter-select">

                        <div class="response_msg"><span class="success_msg"></span></div>

                        <div class="inquiryy-form-shortcode">

                            <div class="inquiry-formm">

                                <?php

                        //  $args = array( 'post_type' => 'retailer', 'posts_per_page' => -1,);

                        //  $loop = new WP_Query( $args );

                        //  while ( $loop->have_posts() ) : $loop->the_post(); $post_id = $post->ID;  ob_start(); 

                                ?>



                                <!--   <input type="hidden" name="post_id" value=""> -->

                                <div class="row">

                                    <div class="col-md-12">

                                        <div class="collection-form collection-form_">
                                            <div class="body-fit-type">
                                                <!--<h4> Fit</h4>-->



                                                            <?php  //$terms = get_the_terms( $post_id , 'body_type');

                                                            $terms = get_terms(array(

                                                                'taxonomy' => 'body_type',

                                                                'hide_empty' => false,

                                                                'order' => 'DESC',
                                                            ));

                                                            $count = 1;

                                                            foreach ((array) $terms as $term) {

                                                                $image_id = get_term_meta($term->term_id, 'body-type-taxonomy-image-id', true);

                                                                $post_thumbnail_img = wp_get_attachment_image_src($image_id, 'thumbnail'); ?>

                                                                <li class="change_body_type <?php if ($count == 1) {

                                                                    echo " active ";

                                                                } ?> body_type_active" body_type="<?php echo $term->term_id; ?>">

                                                                <input type="radio" name="body_type" class="radio_<?php echo $term->term_id . $post_id; ?>" value="<?php echo $term->term_id; ?>" <?php if ($count==1 ) { echo "checked"; } ?>>

                                                                <img src="<?php echo $post_thumbnail_img[0]; ?>" alt="">

                                                                <div class="fit_text">
                                                                    <?php echo

                                                                    $term->name; ?>
                                                                </div>

                                                            </li>

                                                            <?php $count++;

                                                        } ?>



                                                    </div>



                                                    <div class="clearfix"></div>

                                                    <!-- Trigger the modal with a button -->

                                                    <!-- <a href="javascript:void(0)" class="ret_size_gide" data-toggle="modal" data-target="#szModal"><u>Size guide</u></a> -->



                                                    <!-- Modal -->

                                                    <div id="szModal" class="modal fade" role="dialog">
                                                        <div class="modal-dialog">



                                                            <!-- Modal content-->

                                                            <div class="modal-content">

                                                                <div class="modal-header">

                                                                    <button type="button" class="close" data-dismiss="modal">&times;</button>

                                                                </div>

                                                                <div class="modal-body">

                                                                    <div class="guide-table-data">

                                                                        <table>

                                                                            <thead>

                                                                                <tr>

                                                                                    <th colspan="10" class="main-heading"> Regular </th>

                                                                                </tr>

                                                                                <tr>

                                                                                    <th>No.</th>

                                                                                    <th>Size</th>

                                                                                    <th>M/39 </th>

                                                                                    <th>M/40 </th>

                                                                                    <th>L/41 </th>

                                                                                    <th>L/42 </th>

                                                                                    <th>XL/43 </th>

                                                                                    <th>XL/44 </th>

                                                                                    <th>2XL/45 </th>

                                                                                    <th>2XL/46 </th>

                                                                                </tr>

                                                                                <tr>

                                                                                    <td>1</td>

                                                                                    <td>NECK/COLLAR</td>

                                                                                    <td>39</td>

                                                                                    <td>40</td>

                                                                                    <td>41</td>

                                                                                    <td>42</td>

                                                                                    <td>43</td>

                                                                                    <td>44</td>

                                                                                    <td>45</td>

                                                                                    <td>46</td>

                                                                                </tr>

                                                                                <tr>

                                                                                    <td>2</td>

                                                                                    <td>HALF CHEST</td>

                                                                                    <td>110</td>

                                                                                    <td>114</td>

                                                                                    <td>116</td>

                                                                                    <td>120</td>

                                                                                    <td>124</td>

                                                                                    <td>127</td>

                                                                                    <td>132</td>

                                                                                    <td>135</td>

                                                                                </tr>

                                                                                <tr>

                                                                                    <td>3</td>

                                                                                    <td>HALF WAIST</td>

                                                                                    <td>100</td>

                                                                                    <td>103</td>

                                                                                    <td>106</td>

                                                                                    <td>109</td>

                                                                                    <td>112</td>

                                                                                    <td>115</td>

                                                                                    <td>118</td>

                                                                                    <td>123</td>

                                                                                </tr>

                                                                                <tr>

                                                                                    <td>4</td>

                                                                                    <td>HALF BOTTOM</td>

                                                                                    <td>109</td>

                                                                                    <td>112</td>

                                                                                    <td>115</td>

                                                                                    <td>118</td>

                                                                                    <td>125</td>

                                                                                    <td>130</td>

                                                                                    <td>133</td>

                                                                                    <td>136</td>

                                                                                </tr>

                                                                                <tr>

                                                                                    <td>5</td>

                                                                                    <td>HALF SHOULDER</td>

                                                                                    <td>46.5</td>

                                                                                    <td>48</td>

                                                                                    <td>49.5</td>

                                                                                    <td>51</td>

                                                                                    <td>53.5</td>

                                                                                    <td>55</td>

                                                                                    <td>56.5</td>

                                                                                    <td>58</td>

                                                                                </tr>

                                                                                <tr>

                                                                                    <td>6</td>

                                                                                    <td>SLEEVE LENGTH</td>

                                                                                    <td>66</td>

                                                                                    <td>66.5</td>

                                                                                    <td>67</td>

                                                                                    <td>67.5</td>

                                                                                    <td>68</td>

                                                                                    <td>69</td>

                                                                                    <td>69.5</td>

                                                                                    <td>70</td>

                                                                                </tr>

                                                                                <tr>

                                                                                    <td>7</td>

                                                                                    <td>BACK LENGTH</td>

                                                                                    <td>79</td>

                                                                                    <td>80</td>

                                                                                    <td>81</td>

                                                                                    <td>82</td>

                                                                                    <td>83</td>

                                                                                    <td>84</td>

                                                                                    <td>85</td>

                                                                                    <td>86</td>

                                                                                </tr>

                                                                            </thead>

                                                                        </table>

                                                                    </div>

                                                                    <div class="guide-table-data">



                                                                        <table>

                                                                            <thead>

                                                                                <tr>

                                                                                    <th colspan="10" class="main-heading"> slim Fit </th>

                                                                                </tr>

                                                                                <tr>

                                                                                    <th>No.</th>

                                                                                    <th>Size</th>

                                                                                    <th>S/37 </th>

                                                                                    <th>S/38 </th>

                                                                                    <th>M/39 </th>

                                                                                    <th>M/40 </th>

                                                                                    <th>L/41 </th>

                                                                                    <th>L/42 </th>

                                                                                    <th>XL/43 </th>

                                                                                    <th>XL/44 </th>

                                                                                </tr>

                                                                                <tr>

                                                                                    <td>1</td>

                                                                                    <td>NECK/COLLAR</td>

                                                                                    <td>37</td>

                                                                                    <td>38</td>

                                                                                    <td>39</td>

                                                                                    <td>40</td>

                                                                                    <td>41</td>

                                                                                    <td>42</td>

                                                                                    <td>43</td>

                                                                                    <td>44</td>



                                                                                </tr>

                                                                                <tr>

                                                                                    <td>2</td>

                                                                                    <td>HALF CHEST</td>

                                                                                    <td>96</td>

                                                                                    <td>102</td>

                                                                                    <td>105</td>

                                                                                    <td>109</td>

                                                                                    <td>113</td>

                                                                                    <td>118</td>

                                                                                    <td>123</td>

                                                                                    <td>126</td>

                                                                                </tr>

                                                                                <tr>

                                                                                    <td>3</td>

                                                                                    <td>HALF WAIST</td>

                                                                                    <td>86</td>

                                                                                    <td>91</td>

                                                                                    <td>94</td>

                                                                                    <td>99</td>

                                                                                    <td>103</td>

                                                                                    <td>106</td>

                                                                                    <td>110</td>

                                                                                    <td>113</td>

                                                                                </tr>

                                                                                <tr>

                                                                                    <td>4</td>

                                                                                    <td>HALF BOTTOM</td>

                                                                                    <td>96</td>

                                                                                    <td>100</td>

                                                                                    <td>104</td>

                                                                                    <td>108</td>

                                                                                    <td>112</td>

                                                                                    <td>117</td>

                                                                                    <td>122</td>

                                                                                    <td>125</td>

                                                                                </tr>

                                                                                <tr>

                                                                                    <td>5</td>

                                                                                    <td>HALF SHOULDER</td>

                                                                                    <td>42.5</td>

                                                                                    <td>44</td>

                                                                                    <td>45.5</td>

                                                                                    <td>47</td>

                                                                                    <td>48</td>

                                                                                    <td>49</td>

                                                                                    <td>51</td>

                                                                                    <td>52</td>

                                                                                </tr>

                                                                                <tr>

                                                                                    <td>6</td>

                                                                                    <td>SLEEVE LENGTH</td>

                                                                                    <td>64.5</td>

                                                                                    <td>65</td>

                                                                                    <td>65.5</td>

                                                                                    <td>66</td>

                                                                                    <td>67</td>

                                                                                    <td>68</td>

                                                                                    <td>69</td>

                                                                                    <td>69</td>

                                                                                </tr>

                                                                                <tr>

                                                                                    <td>7</td>

                                                                                    <td>BACK LENGTH AT CB</td>

                                                                                    <td>77</td>

                                                                                    <td>78</td>

                                                                                    <td>79</td>

                                                                                    <td>80</td>

                                                                                    <td>81</td>

                                                                                    <td>82</td>

                                                                                    <td>83</td>

                                                                                    <td>84</td>



                                                                                </tr>

                                                                            </thead>

                                                                        </table>



                                                                    </div>

                                                                    <div class="guide-table-data">

                                                                        <table>

                                                                            <thead>

                                                                                <tr>

                                                                                    <th colspan="10" class="main-heading"> Loose fit </th>

                                                                                </tr>

                                                                                <tr>

                                                                                    <th>No.</th>

                                                                                    <th>Size</th>

                                                                                    <th>M/39-40 </th>

                                                                                    <th>L/41-42 </th>

                                                                                    <th>XL/43-44 </th>

                                                                                    <th>2XL/45-46 </th>

                                                                                    <th>3XL/47-48 </th>

                                                                                    <th>4XL/49-50 </th>



                                                                                </tr>

                                                                                <tr>

                                                                                    <td>1</td>

                                                                                    <td>NECK/COLLAR</td>



                                                                                    <td>40</td>

                                                                                    <td>42</td>

                                                                                    <td>44</td>

                                                                                    <td>46</td>

                                                                                    <td>48</td>

                                                                                    <td>50</td>

                                                                                </tr>

                                                                                <tr>

                                                                                    <td>2</td>

                                                                                    <td>HALF CHEST</td>

                                                                                    <td>118</td>

                                                                                    <td>126</td>

                                                                                    <td>133</td>

                                                                                    <td>140</td>

                                                                                    <td>147</td>

                                                                                    <td>154</td>



                                                                                </tr>

                                                                                <tr>

                                                                                    <td>3</td>

                                                                                    <td>HALF WAIST</td>

                                                                                    <td>113</td>

                                                                                    <td>121</td>

                                                                                    <td>130</td>

                                                                                    <td>138</td>

                                                                                    <td>146</td>

                                                                                    <td>153</td>



                                                                                </tr>

                                                                                <tr>

                                                                                    <td>4</td>

                                                                                    <td>HALF BOTTOM</td>

                                                                                    <td>117</td>

                                                                                    <td>125</td>

                                                                                    <td>132</td>

                                                                                    <td>140</td>

                                                                                    <td>147</td>

                                                                                    <td>154</td>



                                                                                </tr>

                                                                                <tr>

                                                                                    <td>5</td>

                                                                                    <td>HALF SHOULDER</td>

                                                                                    <td>52</td>

                                                                                    <td>55</td>

                                                                                    <td>58</td>

                                                                                    <td>61</td>

                                                                                    <td>64</td>

                                                                                    <td>67</td>



                                                                                </tr>

                                                                                <tr>

                                                                                    <td>6</td>

                                                                                    <td>SLEEVE LENGTH</td>

                                                                                    <td>89</td>

                                                                                    <td>92</td>

                                                                                    <td>94</td>

                                                                                    <td>95</td>

                                                                                    <td>98</td>

                                                                                    <td>99</td>



                                                                                </tr>

                                                                                <tr>

                                                                                    <td>7</td>

                                                                                    <td>BACK LENGTH AT CB</td>

                                                                                    <td>81</td>

                                                                                    <td>83</td>

                                                                                    <td>84</td>

                                                                                    <td>85</td>

                                                                                    <td>87</td>

                                                                                    <td>89</td>

                                                                                </tr>

                                                                            </thead>

                                                                        </table>

                                                                    </div>

                                                                    <div class="guide-table-data">





                                                                        <table>

                                                                            <thead>

                                                                                <tr>

                                                                                    <th colspan="10" class="main-heading"> CONTEMPORARY(casual) </th>

                                                                                </tr>

                                                                                <tr>

                                                                                    <th>No.</th>

                                                                                    <th>Size</th>

                                                                                    <th>S/38 </th>

                                                                                    <th>M/40 </th>

                                                                                    <th>L/42 </th>

                                                                                    <th>XL/44 </th>

                                                                                    <th>2XL/46 </th>

                                                                                    <th>3XL/48 </th>



                                                                                </tr>

                                                                                <tr>

                                                                                    <td>1</td>

                                                                                    <td>NECK/COLLAR</td>

                                                                                    <td>39</td>

                                                                                    <td>41</td>

                                                                                    <td>43</td>

                                                                                    <td>45</td>

                                                                                    <td>47</td>

                                                                                    <td>49</td>



                                                                                </tr>

                                                                                <tr>

                                                                                    <td>2</td>

                                                                                    <td>HALF CHEST</td>

                                                                                    <td>110</td>

                                                                                    <td>108</td>

                                                                                    <td>118</td>

                                                                                    <td>126</td>

                                                                                    <td>134</td>

                                                                                    <td>142</td>



                                                                                </tr>

                                                                                <tr>

                                                                                    <td>3</td>

                                                                                    <td>HALF WAIST</td>

                                                                                    <td>94</td>

                                                                                    <td>102</td>

                                                                                    <td>110</td>

                                                                                    <td>118</td>

                                                                                    <td>124</td>

                                                                                    <td>132</td>



                                                                                </tr>

                                                                                <tr>

                                                                                    <td>4</td>

                                                                                    <td>HALF BOTTOM</td>

                                                                                    <td>100</td>

                                                                                    <td>108</td>

                                                                                    <td>116</td>

                                                                                    <td>124</td>

                                                                                    <td>132</td>

                                                                                    <td>140</td>



                                                                                </tr>

                                                                                <tr>

                                                                                    <td>5</td>

                                                                                    <td>HALF SHOULDER</td>

                                                                                    <td>44</td>

                                                                                    <td>46</td>

                                                                                    <td>48.5</td>

                                                                                    <td>51.5</td>

                                                                                    <td>54.5</td>

                                                                                    <td>57.5</td>



                                                                                </tr>

                                                                                <tr>

                                                                                    <td>6</td>

                                                                                    <td>SLEEVE LENGTH</td>

                                                                                    <td>64.5</td>

                                                                                    <td>66.5</td>

                                                                                    <td>68.5</td>

                                                                                    <td>70.5</td>

                                                                                    <td>72.5</td>

                                                                                    <td>74.5</td>



                                                                                </tr>

                                                                                <tr>

                                                                                    <td>7</td>

                                                                                    <td>BACK LENGTH AT CB</td>

                                                                                    <td>74.5</td>

                                                                                    <td>75.5</td>

                                                                                    <td>77.5</td>

                                                                                    <td>79.5</td>

                                                                                    <td>81</td>

                                                                                    <td>83</td>

                                                                                </tr>

                                                                            </thead>

                                                                        </table>

                                                                    </div>

                                                                </div>



                                                            </div>



                                                        </div>

                                                    </div>


                                                    <div class="row">
                                                        <div class="col-md-6 aas">

                                                            <div class="measure-boxes size-type">
                                                                <div style="color: red;">
                                                                    <span class="brkdwn_err_msg brkdwn_err_msg_"></span>
                                                                </div>
                                                                <h4>Size BreakDowns</h4>
                                                                <div class="sizes-boxx">
                                                                    <ul>
                                                                        <?php $size_breakdown =  get_field("size_breakdown", $post_id);
                                                                        $count = 42 - 34;
                                                                        $size = 37;
                                                                        $size_notation = "s";
                                                                        for ($i = 37; $i <= 48; $i++) {
                                                                            if ($size == 37 || $size == 38) {
                                                                                $size_notation = "S";
                                                                            } elseif ($size == 39 || $size == 40) {
                                                                                $size_notation = "M";
                                                                            } elseif ($size == 41 || $size == 42) {
                                                                                $size_notation = "L";
                                                                            } elseif ($size == 43 || $size == 44) {
                                                                                $size_notation = "XL";
                                                                            } elseif ($size == 45 || $size == 46) {
                                                                                $size_notation = "2XL";
                                                                            } elseif ($size == 47 || $size == 48) {
                                                                                $size_notation = "3XL";
                                                                            }
                                                                            ?>
                                                                            <li>
                                                                                <span class="sizeee"><?php echo $size . '(' . $size_notation . ')'; ?></span>
                                                                                <div class="input_field input-fld">
                                                                                    <input type="hidden" name="breakDown_size[]" readonly="" value="<?php echo $size . '(' . $size_notation . ')'; ?>" />
                                                                                    <input type="number" class="breakdown_input_val_" name="breakDown_quantity[]" min="1" max="1000" placeholder="0">
                                                                                    <span class="minus"> - </span> <span class="plus"> + </span>
                                                                                </div>
                                                                            </li>
                                                                            <?php $size++;
                                                                        } ?>
                                                                    </ul>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="col-md-6 aas">
                                                            <h3>BODY FIT</h3>
                                                            <ul class="chs">
                                                                <li>
                                                                    <div class="t-left">SLIM FIT</div>
                                                                    <div class="t-right d-flex">
                                                                        <p class="flex-fill">
                                                                            <a href="javascript:void(0)" class="ret_sie_gide" data-toggle="modal" data-target="#szModal"><u>Size guide</u></a>
                                                                        </p>
                                                                        <input type="checkbox" name="slim_fit" class="form-check-input flex-fill">
                                                                    </div>
                                                                </li>
                                                                <li>
                                                                    <div class="t-left">REGULAR FIT</div>
                                                                    <div class="t-right d-flex">
                                                                        <p class="flex-fill">
                                                                            <a href="javascript:void(0)" class="ret_sie_gide" data-toggle="modal" data-target="#szModal"><u>Size guide</u></a>
                                                                        </p>
                                                                        <input type="checkbox" name="regular_fit" class="form-check-input flex-fill">
                                                                    </div>
                                                                </li>
                                                                <li>
                                                                    <div class="t-left">CONTEMPORARY FIT</div>
                                                                    <div class="t-right d-flex">
                                                                        <p class="flex-fill">
                                                                            <a href="javascript:void(0)" class="ret_sie_gide" data-toggle="modal" data-target="#szModal"><u>Size guide</u></a>
                                                                        </p>
                                                                        <input type="checkbox" name="contemporary" class="form-check-input flex-fill">
                                                                    </div>
                                                                </li>

                                                            </ul>

                                                        </div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-md-12">
                                                            <div class="price-block">
                                                                <input type="number" class="pricep" name="totalprice" placeholder="Total pieces">
                                                                <span class="custom_text"></span>
                                                            </div>

                                                        </div>

                                                        <div class="col-md-8">
                                                            <p class="text-block">Minimum order 60 shirts (min. 10 shirts/ color).</p>
                                                        </div>
                                                       <!--  <div class="col-md-4">
                                                            <input type="text" name="smeters" placeholder="Stock in meters" class="metrss">
                                                        </div> -->
                                                    </div>
                                                    <div class="measure-boxes commentt-type">

                                                        <textarea placeholder="Comments" id="s_comment" name="comment"></textarea>

                                                    </div>
                                                    <hr>

                                                    <div class="measure-boxes collar-type">

                                                        <h4>Collar Type</h4>

                                                            <?php  //$terms = get_the_terms( $post_id , 'collar_type');

                                                            $terms = get_terms(array(

                                                                'taxonomy' => 'collar_type',

                                                                'hide_empty' => false,

                                                            ));

                                                            $count1 = 1;

                                                            foreach ((array) $terms as $term) {

                                                                $image_id = get_term_meta($term->term_id, 'collar-type-taxonomy-image-id', true);

                                                                $post_thumbnail_img = wp_get_attachment_image_src($image_id, 'thumbnail'); ?>

                                                                <label class="">

                                                                    <input type="radio" name="collar_type" value="<?php echo $term->term_id; ?>" <?php if ($count1 == 1) {echo "checked";} ?>>
<input type="hidden" name="data_collar_type_transmit_<?php echo $term->term_id; ?>" value="<?php echo $post_thumbnail_img[0].'__sep__'.$term->name; ?>"/>                                 

                                                                    <img src="<?php echo $post_thumbnail_img[0]; ?>" height="69">

                                                                    <span><?php echo $term->name; ?></span>

                                                                </label>

                                                                <?php $count1++;

                                                            } ?>

                                                        </div>



                                                        <div class="measure-boxes cuff-type">

                                                            <h4>Cuff Type</h4>

                                                            <?php //$terms = get_the_terms( $post_id , 'cuff_type');

                                                            $terms = get_terms(array(

                                                                'taxonomy' => 'cuff_type',

                                                                'hide_empty' => false,

                                                            ));

                                                            $count2 = 1;

                                                            foreach ((array) $terms as $term) {

                                                                $image_id = get_term_meta($term->term_id, 'cuff-type-taxonomy-image-id', true);

                                                                $post_thumbnail_img = wp_get_attachment_image_src($image_id, 'thumbnail'); ?>

                                                                <label class="">

                                                                    <input type="radio" name="cuff_type" value="<?php echo $term->term_id; ?>" <?php if ($count2 == 1) {

                                                                        echo "checked";

                                                                    } ?>>
<input type="hidden" name="data_cuff_type_transmit_<?php echo $term->term_id; ?>" value="<?php echo $post_thumbnail_img[0].'__sep__'.$term->name; ?>"/>

                                                                    <img src="<?php echo $post_thumbnail_img[0]; ?>" height="69">
                                                                    <span><?php echo $term->name; ?></span>

                                                                </label>

                                                                <?php $count2++;

                                                            } ?>

                                                        </div>


                                                        <div class="clearfix"></div>

                                                        <div class="next-btnn fieldss-btn fieldss">

                                                            <button type="submit" class="save_and_continue">SAVE & ADD NEW COLOUR</button>

                                                            <a href="javascript:void(0)">I´M DONE CHOOSING</a>

                                                        </div>



                                                    </div>

                                                </div>

                                                <div class="clearfix"></div>

                                                <div class="col-md-12 submit-formm submit-formm_" id="next-btn-form">
                                                    <!-- <div class="fieldss">

                                                        <span class="add_err_msg_" style="color: red;"></span>

                                                        <input type="text" class="address_" name="address" placeholder="Address 1" value="">

                                                    </div>

                                                    <div class="fieldss">

                                                        <span class="add_err_msg1" style="color: red;"></span>

                                                        <input type="text" class="address1" name="address1" placeholder="Address 2" value="">

                                                    </div> -->

                                                    <div class="fieldss">

                                                        <span class="compny_err_msg_" style="color: red;"></span>

                                                        <input type="text" class="company_name_" name="company_name" placeholder="Company Name" value="">

                                                    </div>

                                                    <div class="fieldss">

                                                        <input type="text" class="address2" name="address2" placeholder="Delivery address" value="">

                                                    </div>

                                                    <div class="fieldss">

                                                        <span class="cntry_err_msg_" style="color: red;"></span>

                                                        <input type="text" class="country_" name="country" placeholder="Country" value="">

                                                    </div>
                                                    <div class="fieldss">

                                                        <span class="postbox__err_msg" style="color: red;"></span>

                                                        <input type="text" class="postbox" name="postbox" placeholder="Post Box" value="">

                                                    </div>

                                                    <div class="fieldss">

                                                        <span class="email_err_msg_" style="color: red;"></span>

                                                        <input type="email" class="email_" name="email" placeholder="Email" value="">

                                                    </div>
                                                     <div class="fieldss">

                                                        <span class="contactperson_err_msg1" style="color: red;"></span>

                                                        <span class="contactperson_err_msg_" style="color: red;"></span>



                                                        <input type="tel" class="contactperson" name="contactperson" placeholder="Contact Person" value="">





                                                    </div>


                                                    <div class="fieldss">

                                                        <span class="contct_err_msg1" style="color: red;"></span>

                                                        <span class="contct_err_msg_" style="color: red;"></span>


                                                         <select name="phone_number_code" class="form-control" style="height: 48px;color: gray;font-size: 13;">
                                                                             <?php

                                                                         foreach($phoneCodes as $key => $phoneno){

                                                                          if($setting_phone==$key) { $selected = 'selected'; }else{ $selected=""; }

                                                                          

                                                                          echo'<option value= "'.$key.'" '.$selected.'>'.$phoneno.'</option>  ';

                                                                         }

                                                                         ?>

                                                                          
                                                                         </select>
                                                        <input type="tel" class="contact_" name="contact" placeholder="Mobile Number" value="" style="position: relative;top: -47px;left: 0;width: auto;border-top: none !important;border-bottom: none !important;border-right: 0 !important;">





                                                    </div>

                                                    <div class="fieldss fieldss-btn">

                                                        <button class="btn retailer_form_submit" type="submit">
                                                            Send Inquiry
                                                        </button>

                                                    </div>

                                                </div>

                                            </div>

                                        </form>

                        <?php  // endwhile; wp_reset_postdata(); 

                        ?>

                    </div>

                </div>

            </div>



        </div>

    <?php } ?>

</div>

</section>



<section class="retailer-filters">

    <div class="retailer-filter-btn">

        <a class="cls-fabric active" href="javascript:void(0)" fabric-id="fabric-select">Fabrics</a>

        <a class="cls-shirts" href="javascript:void(0)" fabric-id="filter-select">Shirt Style</a>

    </div>

</section>





<script src="https://code.jquery.com/jquery-3.5.1.min.js" type="text/javascript"></script>

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
      jQuery(".measure-boxes").find('input[type="radio"]').change(function(){
        if(jQuery(this).is(':checked')){
      // add class to current label
          jQuery(this).closest('label').addClass('cehksComplete');
      // remove class from other labels
          jQuery(this).closest('.measure-boxes').find('label').not(jQuery(this).closest('label')).removeClass('cehksComplete');
      } else {
          jQuery(this).closest('label').removeClass('cehksComplete');
      }
  });
      jQuery(".measure-boxes").find('input[type="radio"]').each(function(){
          if(jQuery(this).is(':checked'))
          {
            jQuery(this).closest('label').addClass('cehksComplete');
        }
    })
  });

</script>

<script type="text/javascript">
    function isValidEmailAddress(emailAddress) {

        var pattern = /^([a-z\d!#$%&'*+\-\/=?^_`{|}~\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]+(\.[a-z\d!#$%&'*+\-\/=?^_`{|}~\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]+)*|"((([ \t]*\r\n)?[ \t]+)?([\x01-\x08\x0b\x0c\x0e-\x1f\x7f\x21\x23-\x5b\x5d-\x7e\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]|\\[\x01-\x09\x0b\x0c\x0d-\x7f\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]))*(([ \t]*\r\n)?[ \t]+)?")@(([a-z\d\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]|[a-z\d\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF][a-z\d\-._~\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]*[a-z\d\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])\.)+([a-z\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]|[a-z\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF][a-z\d\-._~\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]*[a-z\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])\.?$/i;

        return pattern.test(emailAddress);

    }



    function zoom(e) {

        var zoomer = e.currentTarget;

        e.offsetX ? offsetX = e.offsetX : offsetX = e.touches[0].pageX

        e.offsetY ? offsetY = e.offsetY : offsetX = e.touches[0].pageX

        x = offsetX / zoomer.offsetWidth * 100

        y = offsetY / zoomer.offsetHeight * 100

        zoomer.style.backgroundPosition = x + '% ' + y + '%';

    }




    jQuery(document).ready(function($) {
  // Select all the input fields with class "breakdown_input_val_" and bind a keyup event to them
      $('.breakdown_input_val_').on('keyup', function() {
        var total = 0;
    // Loop through all the input fields and get their values if they're not empty
        $('.breakdown_input_val_').each(function() {
          if ($(this).val() != '') {
            total += parseInt($(this).val());
        }
    });
    // Set the total value to the input field with class "metrss"
        $('.metrss').val((total / 1.7).toFixed(1));
        
                $('.pricep').val(total);
                $('.custom_text').html("Shirts");
    });
      $('.pricep').on('keyup', function() {
       $('.metrss').val(($(this).val() / 1.7).toFixed(1));
   })
  });



    var Postobject = [];

    var baseUrl = document.location.origin;

    jQuery(document).ready(function() {

        var prev_remo = '';

        jQuery(document).on('click', '.chek_item', function(e) {

            e.preventDefault();

            jQuery('.success_msg').text("");

            jQuery('.success_msg').removeClass("success_style");

            jQuery(".brkdwn_err_msg").text("");

            jQuery('.loader').show();

            setTimeout(function() {

                jQuery('.loader').hide();

            }, 1000);

                        //  jQuery('.hide_class').addClass("hide_form");

            var post_id = jQuery(this).attr('id');

            console.log(post_id);

            prev_remo = post_id;

            if (prev_remo == post_id) {

                            //jQuery(".collection-form_").show();

                            //jQuery(".submit-formm_").fadeOut();

            }

            if (jQuery('#fabric_type' + post_id).prop('checked') == false) {

                jQuery('#fabric_type' + post_id).prop("checked", true);

                jQuery('.chek_img_' + post_id).addClass("check_box_img");

                jQuery('.chek_item_' + post_id).addClass("check_box_active");

                            // jQuery('#retailer_form_').removeClass("hide_form");

            } else {

                jQuery('#fabric_type' + post_id).prop("checked", false);

                jQuery('.chek_img_' + post_id).removeClass("check_box_img");

                jQuery('.chek_item_' + post_id).removeClass("check_box_active");

            }



        });



        jQuery(document).on('click', '.chek_item_sec', function(e) {

            e.preventDefault();

            jQuery('.success_msg').text("");

            jQuery('.success_msg').removeClass("success_style");

            jQuery(".brkdwn_err_msg").text("");

            jQuery('.loader').show();

            setTimeout(function() {

                jQuery('.loader').hide();

            }, 1000);

                        //  jQuery('.hide_class').addClass("hide_form");

            var post_id = jQuery(this).attr('id');

            console.log(post_id);

            prev_remo = post_id;

            if (prev_remo == post_id) {

                            //jQuery(".collection-form_").show();

                            //jQuery(".submit-formm_").fadeOut();

            }

            if (jQuery('#fabric_type_sec' + post_id).prop('checked') == false) {

                jQuery('#fabric_type_sec' + post_id).prop("checked", true);

                jQuery('.chek_img_sec' + post_id).addClass("check_box_img");

                jQuery('.chek_item_sec' + post_id).addClass("check_box_active");

                            // jQuery('#retailer_form_').removeClass("hide_form");

            } else {

                jQuery('#fabric_type' + post_id).prop("checked", false);

                jQuery('.chek_img_sec' + post_id).removeClass("check_box_img");

                jQuery('.chek_item_sec' + post_id).removeClass("check_box_active");

            }



        });



        jQuery(document).on('click', '.change_body_type', function() {

            var body_type_id = jQuery(this).attr("body_type");

            var post_id = jQuery(this).attr("post_id");

            jQuery('.body_type_active').removeClass('active');

            jQuery(".radio_" + body_type_id).prop('checked', true);;

            jQuery(this).addClass('active');

        });



        jQuery(document).on('click', '.plus', function() {

            var val = jQuery(this).prev().prev().val();

            if (val < 10) {

                val++;

            }

            jQuery(this).prev().prev().val(val);

        });

        jQuery(document).on('click', '.minus', function() {

            var val = jQuery(this).prev().val();

            if (val > 0) {

                val--;

            }

            jQuery(this).prev().val(val);

        });



        jQuery(document).on('click', '.next-btnn a', function(e) {

            e.preventDefault();

            jQuery(".brkdwn_err_msg").text("");

            jQuery('.success_msg').text("");

            jQuery('.success_msg').removeClass("success_style");

            jQuery('.loader').show();

            setTimeout(function() {

                jQuery('.loader').hide();

            }, 1000);

            console.log(Postobject.length);

            if (Postobject.length == 0) {

                var input_val = 0;

                var post_id = jQuery(this).attr("post_id");

                jQuery(".breakdown_input_val_").each(function() {

                    var brkdwn_inpt_val = jQuery(this).val();

                    if (brkdwn_inpt_val > 0) {

                        input_val++;

                    }

                });

                console.log(input_val);

                if (!jQuery('input[type="radio"].checkboxType').is(':checked')) {

                    jQuery('html, body').animate({

                        scrollTop: jQuery('section.second-section.cloth-color').offset().top

                    }, 1500);

                    alert("Please select one collection.");

                    return false;



                }

                if (input_val == 0) {

                    jQuery(".brkdwn_err_msg_").text("please select breakdown size.");

                    jQuery('html, body').animate({

                        scrollTop: jQuery('.cuff-type').offset().top

                    }, 1500);

                    return false;

                }



            }

            jQuery(".section-head h2").text('FILL OUT YOUR CONTACT INFORMATION');

            jQuery(".chkbx-design").hide();



            jQuery(".tabs").hide();

            jQuery(".collection-form_").hide();

            jQuery(".submit-formm_").fadeIn();



            jQuery('html, body').animate({

                scrollTop: jQuery('section.second-section.cloth-color').offset().top

            }, 1500);

        });



        jQuery(document).on('click', '.save_and_continue', function(e) {

            e.preventDefault();

            var count = active_form = input_val = 0;

            jQuery(".brkdwn_err_msg").text("");

            jQuery('.success_msg').text("");

            jQuery('.success_msg').removeClass("success_style");

            jQuery('.loader').show();

            setTimeout(function() {

                jQuery('.loader').hide();

            }, 1000);



            var collection_id = jQuery(".chek_item .checkboxType:checked").val();

                        //alert(collection_id);



            jQuery(".breakdown_input_val_").each(function() {

                var brkdwn_inpt_val = jQuery(this).val();

                if (brkdwn_inpt_val > 0) {

                    input_val++;

                }

            });



            if (!jQuery('input[type="radio"].checkboxType').is(':checked')) {

                jQuery('html, body').animate({

                    scrollTop: jQuery('section.second-section.cloth-color').offset().top

                }, 1500);

                alert("Please select one collection.");

                return false;



            }



            if (input_val == 0) {

                jQuery('html, body').animate({

                    scrollTop: jQuery('.cuff-type').offset().top

                }, 1500);

                jQuery(".brkdwn_err_msg_").text("please select breakdown size.");

                return false;

            }

            form_data = jQuery('#retailer_form').serialize();

                        //var data = JSON.stringify( form_data );

            obj = {};

            obj['post_id'] = collection_id;

            obj['post_data'] = form_data;

            Postobject.push(obj);

            console.log(Postobject);

            jQuery(".breakdown_input_val_").each(function() {

                jQuery(this).val('0');

            });

            jQuery(".checkboxType").prop('checked', false);;

            jQuery('#s_comment').val('');

            jQuery('.success_msg').text("YOU HAVE CHOOSEN 1 COLOUR. PLEASE CONTINUE.");

            jQuery('.success_msg').addClass("success_style");

            jQuery('html, body').animate({

                scrollTop: jQuery('section.second-section.cloth-color').offset().top

            }, 1500);

                        //jQuery(".checkboxType:checked").each(function() {

                        // var post_id = jQuery(this).val();

                        // jQuery(".submit-formm_").fadeOut();



                        // if( post_id == save_n_con ){

                        //    //jQuery('#retailer_form_').addClass("hide_form");

                        // }

                        // else{

                        //    //if( active_form < 1){

                        //        jQuery('#retailer_form_').removeClass("hide_form");

                        //        jQuery(".collection-form_").show();

                        //     // } active_form++;

                        //    }

                        //count ++;

                        //});

                        // if(count == 1){

                        //   jQuery('#retailer_form_'+save_n_con).removeClass("hide_form");

                        //   jQuery(".collection-form_").show();

                        // }

        });



        jQuery(document).on('click', '.retailer_form_submit', function(e) {

            e.preventDefault();
            var thisElement = jQuery(this);

            var note = company = address = conutry = email = contact = form_data = '';

            var post_arr = [];



            jQuery(".checkboxType:checked").each(function() {

                var post_id = jQuery(this).val();

                post_arr.push(post_id);

            });

            console.log(post_arr);

                        //  post_id = jQuery(this).val();

            note = jQuery('.note_').val();

            company = jQuery('.company_name_').val();

            address = jQuery('.address_').val();

            address1 = jQuery('.address1').val();

            address2 = jQuery('.address2').val();

            conutry = jQuery('.country_').val();

            email = jQuery('.email_').val();

            contact_code = jQuery('.contact_code').val();

            contact = jQuery('.contact_').val();
            postbox = jQuery('.postbox').val();
            contactperson = jQuery('contactperson').val();



                        // if (note == '') {

                        //   jQuery('.note_err_msg_').text("please fill this field.");

                        // } else {

                        //   jQuery('.note_err_msg_').text("");

                        // }



            if (company == '') {

                jQuery('.compny_err_msg_').text("please fill this field.");

            } else {

                jQuery('.compny_err_msg_').text("");

            }



            if (address == '') {

                jQuery('.add_err_msg_').text("please fill this field.");

            } else {

                jQuery('.add_err_msg_').text("");

            }

            if (address1 == '') {

                jQuery('.add_err_msg1').text("please fill this field.");

            } else {

                jQuery('.add_err_msg1').text("");

            }



            if (conutry == '') {

                jQuery('.cntry_err_msg_').text("please fill this field.");

            } else {

                jQuery('.cntry_err_msg_').text("");

            }

             if (contactperson == '') {

                jQuery('.contactperson_err_msg').text("please fill this field.");

            } 

            if (postbox == '') {

                jQuery('.postbox__err_msg').text("please fill this field.");

            } 

            if (email == '') {

                jQuery('.email_err_msg_').text("please fill this field.");

            } else {

                jQuery('.email_err_msg_').text("");

                if (!isValidEmailAddress(email)) {

                    jQuery('.email_err_msg_').text("Please enter valid email id.");

                } else {

                    jQuery('.email_err_msg_').text("");

                }

            }







            if (contact == '') {

                jQuery('.contct_err_msg_').text("please fill this field.");

            } else {

                jQuery('.contct_err_msg_').text("");

            }



            form_data = jQuery('#retailer_form').serialize();





            if (company == '' || address == '' || conutry == '' || email == '' || contact == '' || postbox == '' || form_data == '') {

                            //jQuery(".hide_class").addClass("hide_form");

                jQuery(".submit-formm").fadeOut();

                            //  jQuery('#retailer_form_').removeClass("hide_form");

                            //  jQuery(".collection-form_").hide();

                jQuery(".submit-formm_").fadeIn();

                setTimeout(function() {

                    jQuery('.loader').hide();

                }, 1000);

                return false;

            }

            var collection_id = jQuery(".chek_item .checkboxType:checked").val();



            obj = {};

            obj['post_id'] = collection_id;

            obj['post_data'] = form_data;
            jQuery('chs').find('input[type=checkbox]:checked').each(function(){
                var fieldData = {};
                fieldData[$(this).attr('name')] = $(this).val();
                Postobject.push(fieldData);
            });
            Postobject.push(obj);

            console.log(Postobject);
            thisElement.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span><span class="sr-only">Loading...</span>');
            thisElement.prop('disabled',true);
            var ajax_url = "<?php echo admin_url('admin-ajax.php'); ?>";

            jQuery.ajax({

                type: "post",

                dataType: "json",

                url: ajax_url,



                            //data: {action:"new_send_email",form_data:form_data,post_id:post_arr},

                data: {

                    action: "new_send_email",

                    form_data: Postobject

                },

                beforeSend: function() {

                    jQuery('.loader').show();

                    jQuery('.success_msg').text('');

                    jQuery('.success_msg').removeClass("success_style");

                },

                            // async: false,

                success: function(response) {

                    if (response.status == 1) {

                        jQuery('.loader').show();
                        thisElement.html('Send Inquiry');
                        jQuery('#fabric_type').prop("checked", false);
                        thisElement.prop('disabled',true);

                        jQuery('.chek_img_').removeClass("check_box_img");

                        jQuery('.chek_item_').removeClass("check_box_active");

                        //jQuery('.success_msg').text("Your Order Details are Submitted succesfully.");

                        setTimeout(function() {

                                        // jQuery('.loader').hide();

                                        //location.reload();

                            window.location = baseUrl + '/Montenapoleone/retailer-order?thankyou=1';

                        }, 500);

                                    // jQuery('.hide_class').addClass("hide_form"); 

                    }

                },

            });

                        //    }); 

        });

    });







        jQuery('.retailer-filter-btn a').click(function() {

            jQuery("html, body").animate({

                scrollTop: 0,

            },

            800

            );

            return false;

        });
    </script>



    <?php get_footer(); ?>