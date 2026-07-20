<?php
/**
 * Plugin Name: Bulk Attribute & Variation Updater
 * Description: Allows bulk updating of WooCommerce product attributes and creation of variations by category.
 * Version: 1.0
 * Author: Salman Akber
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action('admin_menu', 'bulk_variation_admin_menu');

function bulk_variation_admin_menu() {
    if ( ! class_exists('WooCommerce') ) {
        // WooCommerce is not active or not loaded yet
        add_action('admin_notices', function(){
            echo '<div class="error"><p><strong>Bulk Attribute & Variation Updater</strong> requires WooCommerce plugin to be active.</p></div>';
        });
        return;
    }

    add_menu_page(
        'Bulk Attribute & Variation Updater',
        'Bulk Attribute Updater',
        'manage_options',
        'bulk-attribute-variation-updater',
        'render_bulk_variation_page',
        'dashicons-admin-tools',
        56
    );
}


function bulk_attribute_variation_updater_admin_menu() {
    add_menu_page(
        'Bulk Variation Updater',
        'Variation Updater',
        'manage_woocommerce',
        'bulk-variation-updater',
        'render_bulk_variation_page'
    );
}

// Render the admin page form and handle submissions
function render_bulk_variation_page() {
    if ( isset($_POST['bulk_variation_action']) ) {
        $category_slug = sanitize_text_field($_POST['category_slug']);
        $attributes_data = [];

        if (!empty($_POST['attributes']) && is_array($_POST['attributes'])) {
            foreach ($_POST['attributes'] as $attr_name => $term_ids) {
                $attr_name = sanitize_text_field($attr_name);
                $term_ids = array_map('absint', $term_ids);
                if ($attr_name && !empty($term_ids)) {
                    $attributes_data[$attr_name] = $term_ids;
                }
            }
        }

        if ( $category_slug && is_array($attributes_data) ) {
            $product_ids = wc_get_products([
                'status' => 'publish',
                'limit' => -1,
                'category' => [$category_slug],
                'return' => 'ids',
                'type' => 'variable'
            ]);

            bulk_create_attributes_terms_and_variations($product_ids, $attributes_data);

            echo '<div class="notice notice-success"><p>Variations created successfully!</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>Invalid input. Please try again.</p></div>';
        }
    }

    // Fetch product categories
    $categories = get_terms([
        'taxonomy' => 'product_cat',
        'hide_empty' => false,
    ]);

    // Fetch all global product attributes
    $attribute_taxonomies = wc_get_attribute_taxonomies();

    echo '<div class="wrap">
    <h1>Bulk Attribute & Variation Updater</h1>
    <form method="post">
    <table class="form-table">
    <tr>
    <th scope="row"><label for="category_slug">Product Category</label></th>
    <td>
    <select name="category_slug" id="category_slug" required>';
    foreach ($categories as $cat) {
        echo '<option value="'.esc_attr($cat->slug).'">'.esc_html($cat->name).'</option>';
    }
    echo '          </select>
    </td>
    </tr>
    </table>

    <h2>Select Attributes and Terms</h2>';

    foreach ($attribute_taxonomies as $attr) {
        $taxonomy = wc_attribute_taxonomy_name($attr->attribute_name);
        $terms = get_terms(['taxonomy' => $taxonomy, 'hide_empty' => false]);

        echo '<h3>'.esc_html($attr->attribute_label).'</h3>';
        echo '<select multiple name="attributes['.esc_attr($attr->attribute_name).'][]" style="min-width:300px; height: 120px;">';
$existing_term_ids = [];
foreach ($product_ids as $pid) {
    $prod = wc_get_product($pid);
    if (!$prod || !$prod->is_type('variable')) continue;
    $attr_objs = $prod->get_attributes();
    if (isset($attr_objs[$taxonomy])) {
        $existing_term_ids = array_merge($existing_term_ids, $attr_objs[$taxonomy]->get_options());
    }
}
$existing_term_ids = array_unique($existing_term_ids);

foreach ($terms as $term) {
    $selected = in_array($term->term_id, $existing_term_ids) ? 'selected' : '';
    echo '<option value="'.esc_attr($term->term_id).'" '.$selected.'>'.esc_html($term->name).'</option>';
}
    
        echo '</select>';
        echo '<p>Hold CTRL or CMD to select multiple terms.</p>';
    }

    echo '<p><em>Note: Only existing attributes and terms are selectable for now.</em></p>';
    

	echo '
	<p class="submit">
    <input type="submit" name="bulk_variation_action" class="button-primary" value="Create Variations">
    <input type="submit" name="bulk_variation_delete" class="button button-secondary" value="Delete Variations">
</p>


    </form>
    </div>';
}

function bulk_create_attributes_terms_and_variations($product_ids, $attributes_data) {
    foreach ($product_ids as $product_id) {
        $product = wc_get_product($product_id);
        if (!$product || !$product->is_type('variable')) continue;

        $current_attributes = $product->get_attributes();

        // Prepare new attributes based on $attributes_data
        $new_product_attributes = [];

 foreach ($attributes_data as $attr_name => $new_term_ids) {
            $attribute_obj = my_wc_get_attribute_taxonomy_by_name($attr_name);
            $taxonomy = wc_attribute_taxonomy_name($attr_name);

            // Get existing term IDs for this attribute (if any)
            $existing_attr = $current_attributes[$taxonomy] ?? null;
            $existing_term_ids = [];

            if ($existing_attr instanceof WC_Product_Attribute) {
                $existing_term_ids = $existing_attr->get_options();
            }

        // Merge existing attributes with new ones (new overwrite old if conflict)
            $merged_term_ids = array_unique(array_merge($existing_term_ids, $new_term_ids));

       $pa = new WC_Product_Attribute();
            $pa->set_id($attribute_obj ? $attribute_obj->attribute_id : 0);
            $pa->set_name($taxonomy);
            $pa->set_options($merged_term_ids);
            $pa->set_position(0);
            $pa->set_visible(true);
            $pa->set_variation(true);

            $current_attributes[$taxonomy] = $pa;
        }

        // ✅ FIX: Update parent product with merged attributes
$product->set_attributes($current_attributes);
        $product->save(); // 💾 Save attributes BEFORE creating variations

        // Get all possible new combinations
        $terms_for_variations = array_values($attributes_data);
        $combinations = cartesian_product($terms_for_variations);

        // Get existing variations and their attributes
        $existing_variations = $product->get_children();
        $existing_combinations = [];

foreach ($existing_variations as $variation_id) {
            $variation = wc_get_product($variation_id);
            if (!$variation) continue;

            $attrs = $variation->get_attributes();

            // Condition 1: Remove invalid variations (empty attr name or value)
    $is_invalid = false;
    foreach ($attrs as $attr_name => $attr_value) {

        if (empty($attr_name) || empty($attr_value) || strtolower($attr_value) === 'n/a') {
            $is_invalid = true;
            break;
        }
    }

    if ($is_invalid) {
        wp_delete_post($variation_id, true); // ✅ Force delete invalid variation
        continue;
    }

            // Normalize attribute set to compare
            $existing_combinations[$variation_id] = $attrs;
        }

        // Process each new combination from request
        foreach ($combinations as $combination) {
            $variation_attributes = [];
            $index = 0;

            foreach (array_keys($attributes_data) as $attr_name) {
                $taxonomy = wc_attribute_taxonomy_name($attr_name);
                $term_id = $combination[$index++];
                $term = get_term($term_id, $taxonomy);
                if ($term) {
                    $variation_attributes[$taxonomy] = $term->slug;
                }
            }

            // Check if a variation with the same attributes exists
            $found_variation_id = null;
            foreach ($existing_combinations as $variation_id => $existing_attr) {
                if ($variation_attributes == $existing_attr) {
                    $found_variation_id = $variation_id;
                    break;
                }
            }

            if ($found_variation_id) {
                // Condition 2: Variation exists - update if needed
                unset($existing_combinations[$found_variation_id]);
            } else {
                // Variation does not exist - create new
                create_variation($product_id, $variation_attributes);
            }
        }

        // Remaining $existing_combinations are untouched variations (we don’t remove them)
    }
}





function cartesian_product($arrays) {
    $result = [[]];
    foreach ($arrays as $property_values) {
        $tmp = [];
        foreach ($result as $result_item) {
            foreach ($property_values as $property_value) {
                $tmp[] = array_merge($result_item, [$property_value]);
            }
        }
        $result = $tmp;
    }
    return $result;
}

function my_wc_get_attribute_taxonomy_by_name( $name ) {
    $attributes = wc_get_attribute_taxonomies();
    foreach ( $attributes as $attribute ) {
        if ( $attribute->attribute_name === $name ) {
            return $attribute;
        }
    }
    return false;
}


function create_variation($product_id, $variation_attributes) {
    // Check if any attribute name or value is empty or 'n/a' => if yes, skip or delete matching variation
    foreach ($variation_attributes as $attr_name => $attr_value) {
        if (empty($attr_name) || empty($attr_value) || strtolower($attr_value) === 'n/a') {
            // Find and delete existing variation with these attributes if any
            delete_variation_with_attributes($product_id, $variation_attributes);
            return; // Skip creating/updating this variation
        }
    }

    // Try to find existing variation matching these attributes
    $existing_variation_id = find_variation_by_attributes($product_id, $variation_attributes);

    if ($existing_variation_id) {
        // Update existing variation
        $variation = new WC_Product_Variation($existing_variation_id);
        $variation->set_attributes($variation_attributes);
        $variation->save();
    } else {
        // Create new variation
        $variation = new WC_Product_Variation();
        $variation->set_parent_id($product_id);
        $variation->set_attributes($variation_attributes);
        $variation->set_regular_price(''); // or set a price if you want
        $variation->save();
    }
}

function find_variation_by_attributes($product_id, $attributes) {
    $product = wc_get_product($product_id);
    if (!$product || !$product->is_type('variable')) return false;

    foreach ($product->get_children() as $variation_id) {
        $variation = wc_get_product($variation_id);
        if (!$variation) continue;
        $variation_attrs = $variation->get_attributes();

        // Compare attribute arrays — all keys and values must match
        if ($variation_attrs == $attributes) {
            return $variation_id;
        }
    }
    return false;
}

function delete_variation_with_attributes($product_id, $attributes) {
    $variation_id = find_variation_by_attributes($product_id, $attributes);
    if ($variation_id) {
        wp_delete_post($variation_id, true);
    }
}


add_action('admin_init', 'handle_bulk_variation_delete');

function handle_bulk_variation_delete() {
    if ( isset($_POST['bulk_variation_delete']) ) {
        $category_slug = sanitize_text_field($_POST['category_slug']);
        $attributes_data = [];

        if (!empty($_POST['attributes']) && is_array($_POST['attributes'])) {
            foreach ($_POST['attributes'] as $attr_name => $term_ids) {
                $attr_name = sanitize_text_field($attr_name);
                $term_ids = array_map('absint', $term_ids);
                if ($attr_name && !empty($term_ids)) {
                    $attributes_data[$attr_name] = $term_ids;
                }
            }
        }

        if ( $category_slug && is_array($attributes_data) ) {
            // Ensure function exists before calling
            if ( function_exists('wc_get_products') ) {
                $product_ids = wc_get_products([
                    'status' => 'publish',
                    'limit' => -1,
                    'category' => [$category_slug],
                    'return' => 'ids',
                    'type' => 'variable'
                ]);

                bulk_delete_variations($product_ids, $attributes_data);

                add_action('admin_notices', function() {
                    echo '<div class="notice notice-success"><p>Selected variations deleted successfully!</p></div>';
                });
            } else {
                add_action('admin_notices', function() {
                    echo '<div class="notice notice-error"><p><strong>Error:</strong> WooCommerce is not loaded.</p></div>';
                });
            }
        } else {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p>Invalid input. Please try again.</p></div>';
            });
        }
    }
}



function bulk_delete_variations($product_ids, $attributes_data) {
    foreach ($product_ids as $product_id) {
        $product = wc_get_product($product_id);
        if (!$product || !$product->is_type('variable')) continue;

        $children = $product->get_children();

        foreach ($children as $child_id) {
            $variation = wc_get_product($child_id);
            if (!$variation || !$variation->is_type('variation')) continue;

            $variation_attributes = $variation->get_attributes();
            $matches = true;

            foreach ($attributes_data as $attr_name => $term_ids) {
                $taxonomy = wc_attribute_taxonomy_name($attr_name);
                $term_slug_matches = array_map(function($term_id) use ($taxonomy) {
                    $term = get_term($term_id, $taxonomy);
                    return $term ? $term->slug : '';
                }, $term_ids);

                if (isset($variation_attributes[$taxonomy])) {
                    if (!in_array($variation_attributes[$taxonomy], $term_slug_matches)) {
                        $matches = false;
                        break;
                    }
                } else {
                    $matches = false;
                    break;
                }
            }

            if ($matches) {
                wp_delete_post($child_id, true);
            }
        }
    }
}

