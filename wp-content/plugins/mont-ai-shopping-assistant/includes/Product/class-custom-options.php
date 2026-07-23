<?php
/**
 * Detect Mont custom product options (collar, cuff, fit, size, measurements).
 *
 * @package Mont_AI_Assistant
 */

namespace Mont_AI_Assistant\Product;

defined( 'ABSPATH' ) || exit;

/**
 * Class Custom_Options
 *
 * Maps theme-specific options so the AI can validate and collect them
 * before calling add_to_cart.
 */
class Custom_Options {

	/**
	 * Option schema for a product (required / optional / choices).
	 *
	 * @param \WC_Product $product Product.
	 * @return array
	 */
	public function for_product( $product ) {
		$cat_ids = $product->get_category_ids();
		$hide_collar = false;
		$hide_tailoring = false;

		foreach ( $cat_ids as $cid ) {
			if ( function_exists( 'get_field' ) ) {
				if ( get_field( 'cup_and_collar', 'product_cat_' . $cid ) ) {
					$hide_collar = true;
				}
				if ( get_field( 'customer_tailoring_', 'product_cat_' . $cid ) ) {
					$hide_tailoring = true;
				}
			}
		}

		$options = array();

		// Body fit (Woo attribute).
		$fit = $this->attribute_options( $product, 'pa_body-fit' );
		if ( $fit ) {
			$options[] = array(
				'key'         => 'body_fit',
				'label'       => 'Passform / Body Fit',
				'cart_label'  => 'Passform',
				'type'        => 'choice',
				'required'    => true,
				'choices'     => $fit,
				'depends_on'  => null,
			);
		}

		// Size.
		$size = $this->attribute_options( $product, 'pa_size' );
		if ( $size ) {
			$options[] = array(
				'key'         => 'size',
				'label'       => 'Størrelse / Size',
				'cart_label'  => 'Størrelse',
				'type'        => 'choice',
				'required'    => true,
				'choices'     => $size,
				'depends_on'  => 'body_fit',
				'note'        => 'Available sizes may depend on selected body fit.',
			);
		}

		if ( ! $hide_collar ) {
			$collars = $this->acf_option_choices( 'choose_collar_update' );
			if ( $collars ) {
				$options[] = array(
					'key'        => 'collar_type',
					'label'      => 'Snipp / Collar',
					'cart_label' => 'Snipp (Collar)',
					'type'       => 'choice',
					'required'   => true,
					'choices'    => $collars,
				);
			}

			$cuffs = $this->acf_option_choices( 'choose_cuff_update' );
			if ( $cuffs ) {
				$options[] = array(
					'key'        => 'cuff_type',
					'label'      => 'Mansjetter / Cuff',
					'cart_label' => 'Mansjetter (Cuff)',
					'type'       => 'choice',
					'required'   => true,
					'choices'    => $cuffs,
				);
			}
		}

		if ( ! $hide_tailoring ) {
			$options[] = array(
				'key'        => 'custom_measurements',
				'label'      => 'Custom measurements (cm)',
				'type'       => 'group',
				'required'   => false,
				'note'       => 'Optional. Changing paid measurements may add a surcharge (10 per changed field). Shirt length and sleeves are typically free to adjust.',
				'fields'     => array(
					array( 'key' => 'shirt_length', 'label' => 'Skjortelengde', 'unit' => 'cm' ),
					array( 'key' => 'sleeve_length_left', 'label' => 'Ermelengde (Venstre)', 'unit' => 'cm' ),
					array( 'key' => 'sleeve_length_right', 'label' => 'Ermelengde (Høyre)', 'unit' => 'cm' ),
					array( 'key' => 'chest', 'label' => 'Bryst', 'unit' => 'cm' ),
					array( 'key' => 'waist', 'label' => 'Liv', 'unit' => 'cm' ),
					array( 'key' => 'half_bottom', 'label' => 'Halv bunnmål', 'unit' => 'cm' ),
					array( 'key' => 'shoulder', 'label' => 'Skulder', 'unit' => 'cm' ),
				),
			);
		}

		$options[] = array(
			'key'      => 'quantity',
			'label'    => 'Quantity',
			'type'     => 'number',
			'required' => true,
			'default'  => 1,
			'min'      => 1,
		);

		/**
		 * Filter custom option schema for a product.
		 *
		 * @param array       $options Options.
		 * @param \WC_Product $product Product.
		 */
		return apply_filters( 'mont_ai_custom_options', $options, $product );
	}

	/**
	 * Validate a selection bag against schema.
	 *
	 * @param \WC_Product $product    Product.
	 * @param array       $selection  User selections keyed by option key.
	 * @return array{valid:bool,missing:array,errors:array}
	 */
	public function validate( $product, array $selection ) {
		$schema  = $this->for_product( $product );
		$missing = array();
		$errors  = array();

		foreach ( $schema as $opt ) {
			$key = $opt['key'];
			if ( empty( $opt['required'] ) ) {
				continue;
			}
			if ( 'custom_measurements' === $key ) {
				continue; // optional group
			}
			if ( 'quantity' === $key ) {
				$qty = isset( $selection['quantity'] ) ? (int) $selection['quantity'] : 0;
				if ( $qty < 1 ) {
					$missing[] = $key;
				}
				continue;
			}
			if ( empty( $selection[ $key ] ) ) {
				$missing[] = $key;
				continue;
			}
			if ( ! empty( $opt['choices'] ) ) {
				$labels = array_map(
					function ( $c ) {
						return is_array( $c ) ? $c['label'] : (string) $c;
					},
					$opt['choices']
				);
				$val = (string) $selection[ $key ];
				$ok  = in_array( $val, $labels, true );
				if ( ! $ok ) {
					// Allow slug match.
					foreach ( $opt['choices'] as $c ) {
						if ( is_array( $c ) && ( $c['value'] === $val || $c['label'] === $val ) ) {
							$ok = true;
							break;
						}
					}
				}
				if ( ! $ok ) {
					$errors[] = sprintf( 'Invalid value for %s: %s', $opt['label'], $val );
				}
			}
		}

		return array(
			'valid'   => empty( $missing ) && empty( $errors ),
			'missing' => $missing,
			'errors'  => $errors,
			'schema'  => $schema,
		);
	}

	/**
	 * Attribute term labels for a product.
	 *
	 * @param \WC_Product $product Product.
	 * @param string      $taxonomy Taxonomy.
	 * @return array
	 */
	private function attribute_options( $product, $taxonomy ) {
		$attrs = $product->get_attributes();
		if ( empty( $attrs[ $taxonomy ] ) ) {
			return array();
		}
		$attr = $attrs[ $taxonomy ];
		$ids  = $attr->get_options();
		$out  = array();
		foreach ( $ids as $term_id ) {
			$term = get_term( $term_id );
			if ( $term && ! is_wp_error( $term ) ) {
				$out[] = array(
					'value' => $term->slug,
					'label' => $term->name,
				);
			}
		}
		return $out;
	}

	/**
	 * ACF options-page repeater choices.
	 *
	 * @param string $field_name Field.
	 * @return array
	 */
	private function acf_option_choices( $field_name ) {
		if ( ! function_exists( 'get_field' ) ) {
			return array();
		}
		$rows = get_field( $field_name, 'option' );
		if ( ! is_array( $rows ) ) {
			return array();
		}
		$out = array();
		foreach ( $rows as $row ) {
			$name = isset( $row['name'] ) ? $row['name'] : '';
			if ( ! $name ) {
				continue;
			}
			$out[] = array(
				'value'    => $name,
				'label'    => $name,
				'sub_name' => isset( $row['sub_name'] ) ? $row['sub_name'] : '',
				'image'    => isset( $row['image']['url'] ) ? $row['image']['url'] : '',
			);
		}
		return $out;
	}
}
