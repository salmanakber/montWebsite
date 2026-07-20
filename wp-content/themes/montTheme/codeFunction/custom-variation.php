<?php 
class CustomVariation
{
	 protected static $slider_displayed = false;

	function __construct()
	{
		        global $wpdb;
        $this->table_name = $wpdb->prefix . 'variation_settings';
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_save_variation', array($this, 'save_variation'));
        add_action('wp_ajax_delete_variation', array($this, 'delete_variation'));
        add_action('wp_ajax_get_all_variation', array($this, 'getAllvariation'));
		add_action('wp_ajax_nopriv_get_all_variation', array($this, 'getAllvariation'));
        add_action('plugins_loaded', array($this, 'create_table'));
		// add_action('admin_menu', array($this,'creatingmenus'));

	}
	public function customVariation($product)
	{
		$output = [];

		if ($product && $product->is_type('variable')) {
            $variations = $product->get_children(); // Get all variation IDs
            $attributes_list = [];

            foreach ($variations as $variation_id) {
            	$variation = wc_get_product($variation_id);
            	$attributes = $variation->get_attributes();

            	foreach ($attributes as $key => $value) {
                    $taxonomy =  $key; // WooCommerce attribute taxonomy
                    if (taxonomy_exists($taxonomy)) {
                        // Try fetching term by name first
                    	$value_term = get_term_by('name', $value, $taxonomy);


                        // If not found by name, try fetching by slug
                    	if (!$value_term) {
                    		$value_term = get_term_by('slug', sanitize_title($value), $taxonomy);
                    	}

                        // If a term is found, use its slug and name
                    	if ($value_term) {
                    		$value_slug = $value_term->slug;
                    		$value_name = $value_term->name;
                    	} else {
                            // If term is missing, use the original value
                    		$value_slug = sanitize_title($value);
                    		$value_name = $value;
                    	}
                    } else {
                        // Custom attributes (not taxonomy-based)
                    	$value_slug = sanitize_title($value);
                    	$value_name = $value;
                    }

                    // Ensure the attribute list is properly structured
                    if (!isset($attributes_list[$key])) {
                    	$attributes_list[$key] = [
                    		'attribute_slug' => $taxonomy,
                    		'attribute_name' => ucfirst(str_replace('pa_', '', $key)),
                    		'values' => []
                    	];
                    }

                    // Add unique values
                    $value_entry = ['slug' => $value_slug, 'name' => $value_name];

                    if (!in_array($value_entry, $attributes_list[$key]['values'])) {
                    	$attributes_list[$key]['values'][] = $value_entry;
                    }
                }
            }

            // Structure the final output
            foreach ($attributes_list as $attribute_data) {
            	$output[] = [
            		'attribute_slug'   => $attribute_data['attribute_slug'],
            		'attribute_name'   => ucfirst(str_replace('pa_', '', $attribute_data['attribute_slug'])),
            		'attribute_values' => $attribute_data['values']
            	];
            }
        }

        return $output;
    }

    



public function create_table() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $this->table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            attribute_key varchar(255) NOT NULL,
            shirt_length float NOT NULL,
            sleeve_length float NOT NULL,
            shoulder float NOT NULL,
            half_chest float NOT NULL,
            half_waist float NOT NULL,
            half_bottom float NOT NULL,
            armhole float NOT NULL,
            neck_collar float NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY attribute_key (attribute_key)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function add_admin_menu() {
        add_menu_page(
            'Variation Settings',
            'Variation Settings',
            'manage_options',
            'variation-settings',
            array($this, 'render_admin_page'),
            'dashicons-admin-generic',
            30
        );
    }

    public function enqueue_scripts($hook) {
        if ('toplevel_page_variation-settings' !== $hook) {
            return;
        }

        wp_enqueue_script('variation-settings-script',get_template_directory_uri() . '/assets/only-variations.js',array('jquery'),'1.0',true);
        wp_localize_script('variation-settings-script', 'variationSettings', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('variation-settings-nonce')
        ));
    }

    public function get_product_attributes() {
        $attributes = wc_get_attribute_taxonomies();
        $formatted_attributes = array();
        
        foreach ($attributes as $attribute) {
            $terms = get_terms(array(
                'taxonomy' => 'pa_' . $attribute->attribute_name,
                'hide_empty' => false,
            ));
            
            if (!empty($terms) && !is_wp_error($terms)) {
                $formatted_attributes[$attribute->attribute_label] = array();
                foreach ($terms as $term) {
                    $formatted_attributes[$attribute->attribute_label][] = array(
                        'id' => $term->term_id,
                        'name' => $term->name,
                        'slug' => $term->slug
                    );
                }
            }
        }
        
        return $formatted_attributes;
    }

    public function render_admin_page() {
        global $wpdb;
        $variations = $wpdb->get_results("SELECT * FROM $this->table_name ORDER BY created_at DESC");
        $attributes = $this->get_product_attributes();
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">Variation Settings</h1>
            <button class="page-title-action" id="add-new-variation">Add New</button>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Attribute</th>
                        <th>Shirt Length</th>
                        <th>Sleeve Length</th>
                        <th>Shoulder</th>
                        <th>Half Chest</th>
                        <th>Half Waist</th>
                        <th>Half Bottom</th>
                        <th>Armhole</th>
                        <th>Neck/Collar</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($variations as $variation): ?>

                    	<?php 
                    	$trimData = explode('___', $variation->attributes);
                    	$bodyName = str_replace('_', '', $trimData[0]);
                    	$size = $trimData[1];
                    	?>
                    <tr>
                        <td><?php echo esc_html('Body Fit: '.  ucfirst($bodyName)). ' '.esc_html('Size: '. $size ); ?></td>
                        <td><?php echo esc_html($variation->shirt_length); ?></td>
                        <td><?php echo esc_html($variation->sleeve_length); ?></td>
                        <td><?php echo esc_html($variation->shoulder); ?></td>
                        <td><?php echo esc_html($variation->half_chest); ?></td>
                        <td><?php echo esc_html($variation->half_waist); ?></td>
                        <td><?php echo esc_html($variation->half_bottom); ?></td>
                        <td><?php echo esc_html($variation->armhole); ?></td>
                        <td><?php echo esc_html($variation->neck_collar); ?></td>
                        <td>
                            <button class="button edit-variation" data-id="<?php echo $variation->id; ?>">Edit</button>
                            <button class="button delete-variation" data-id="<?php echo $variation->id; ?>">Delete</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Popup Form -->
            <div id="variation-popup" class="popup-overlay">
                <div class="popup-content">
                    <span class="close-popup">&times;</span>
                    <h2>Add New Variation</h2>
                    <form id="variation-form">
                        <div class="attribute-selects">
                            <?php foreach ($attributes as $label => $terms): ?>
                            <div class="form-field">
                                <label><?php echo esc_html($label); ?></label>
                                <select name="attributes[<?php echo esc_attr($label); ?>]" required>
                                   <option value="">Select <?php echo isset($terms['slug']) ? esc_html($terms['slug']) : ''; ?></option>
                                    <?php foreach ($terms as $term): ?>
                                    <option value="<?php echo esc_attr($term['slug']); ?>">
                                        <?php echo esc_html($term['name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="measurements-fields">
                            <?php
                            $fields = array(
                                'shirt_length' => 'Shirt Length',
                                'sleeve_length' => 'Sleeve Length',
                                'shoulder' => 'Shoulder',
                                'half_chest' => 'Half Chest',
                                'half_waist' => 'Half Waist',
                                'half_bottom' => 'Half Bottom',
                                'armhole' => 'Armhole',
                                'neck_collar' => 'Neck/Collar'
                            );
                            
                            foreach ($fields as $field_name => $field_label):
                            ?>
                            <div class="form-field">
                                <label for="<?php echo esc_attr($field_name); ?>"><?php echo esc_html($field_label); ?></label>
                                <div class="number-input">
                                    <button type="button" class="minus">-</button>
                                    <input type="number" name="<?php echo esc_attr($field_name); ?>" 
                                           id="<?php echo esc_attr($field_name); ?>" 
                                           step="0.1" required>
                                    <button type="button" class="plus">+</button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="button button-primary">Save</button>
                            <button type="button" class="button cancel-popup">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php
    }

    public function save_variation() {
        check_ajax_referer('variation-settings-nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        global $wpdb;
        
        $data = array(
            'attributes' => sanitize_text_field($_POST['attribute_key']),
            'shirt_length' => floatval($_POST['shirt_length']),
            'sleeve_length' => floatval($_POST['sleeve_length']),
            'shoulder' => floatval($_POST['shoulder']),
            'half_chest' => floatval($_POST['half_chest']),
            'half_waist' => floatval($_POST['half_waist']),
            'half_bottom' => floatval($_POST['half_bottom']),
            'armhole' => floatval($_POST['armhole']),
            'neck_collar' => floatval($_POST['neck_collar'])
        );

        $format = array('%s', '%f', '%f', '%f', '%f', '%f', '%f', '%f', '%f');

        if (isset($_POST['id'])) {
            $wpdb->update(
                $this->table_name,
                $data,
                array('id' => intval($_POST['id'])),
                $format,
                array('%d')
            );
        } else {
            $wpdb->insert($this->table_name, $data, $format);
            wp_send_json($data);
        }

        
    }

    public function delete_variation() {
        check_ajax_referer('variation-settings-nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        global $wpdb;
        
        $id = intval($_POST['id']);
        $wpdb->delete($this->table_name, array('id' => $id), array('%d'));

        wp_send_json_success();
    }

	public function getAllvariation()
	{

	     global $wpdb;
	     if(isset($_POST['key']))
	     {
     	   $variations = $wpdb->get_results("SELECT * FROM $this->table_name WHERE attributes = '".$_POST['key']."'");
	     	wp_send_json($variations);
    	}

	}
	
public static function display_slider_on_product_page() {
        if (self::$slider_displayed || !is_product()) {
            return;
        }

        global $post;
        if (!$post || !isset($post->ID)) return;

        // Get product categories
        $product_cats = wp_get_post_terms($post->ID, 'product_cat', array('orderby' => 'term_id'));
        if (empty($product_cats) || is_wp_error($product_cats)) return;

        // Use first (primary) category
        $main_cat = $product_cats[0];
        $term_id = $main_cat->term_id;

            // If it's a parent, get children
            $categories_to_show = get_terms(array(
                'taxonomy' => 'product_cat',
                'hide_empty' => false,
                'parent' => 44,
            ));
        

        if (!empty($categories_to_show) && !is_wp_error($categories_to_show)) {
            $slider_id = 'product_category_slider_' . rand(1000, 9999);
            ?>
            <!-- START PRODUCT CATEGORY SLIDER -->
            <div class="category-slider-container ssds">
                <div class="category-slider" id="<?php echo esc_attr($slider_id); ?>">
                    <?php foreach ($categories_to_show as $category) : ?>
                        <a href="<?php echo esc_url(get_term_link($category)); ?>"
                           class="category-item <?php echo ($category->term_id == $term_id) ? 'category-active' : ''; ?>">
                            <?php echo esc_html($category->name); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <script>
            document.addEventListener('DOMContentLoaded', function() {
                const slider = document.getElementById('<?php echo esc_js($slider_id); ?>');
                if (!slider) return;

                const activeCategory = slider.querySelector('.category-active');
                if (activeCategory) {
                    activeCategory.scrollIntoView({ behavior: 'smooth', inline: 'center' });
                }

                let isDown = false, startX, scrollLeft;
                slider.addEventListener('mousedown', (e) => {
                    isDown = true;
                    startX = e.pageX - slider.offsetLeft;
                    scrollLeft = slider.scrollLeft;
                });
                slider.addEventListener('mouseleave', () => isDown = false);
                slider.addEventListener('mouseup', () => isDown = false);
                slider.addEventListener('mousemove', (e) => {
                    if (!isDown) return;
                    e.preventDefault();
                    const x = e.pageX - slider.offsetLeft;
                    const walk = (x - startX) * 2;
                    slider.scrollLeft = scrollLeft - walk;
                });
            });
            </script>
            <!-- END PRODUCT CATEGORY SLIDER -->
            <?php

            self::$slider_displayed = true;
        }
}
   
}
$custom_variation = new CustomVariation();


?>
