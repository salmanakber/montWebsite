<?php
require_once get_template_directory() . '/codeFunction/size-diagram-helper.php';

class CustomVariation {
	protected static $slider_displayed = false;
	public $table_name;

	const MEASUREMENT_FIELDS = array(
		'shirt_length'  => 'Shirt Length',
		'sleeve_length' => 'Sleeve Length',
		'shoulder'      => 'Shoulder',
		'half_chest'    => 'Half Chest',
		'half_waist'    => 'Half Waist',
		'half_bottom'   => 'Half Bottom',
		'armhole'       => 'Armhole',
		'neck_collar'   => 'Neck/Collar',
	);

	function __construct() {
		global $wpdb;
		$this->table_name = $wpdb->prefix . 'variation_settings';

		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'wp_ajax_save_variation', array( $this, 'save_variation' ) );
		add_action( 'wp_ajax_save_variation_bulk', array( $this, 'save_variation_bulk' ) );
		add_action( 'wp_ajax_delete_variation', array( $this, 'delete_variation' ) );
		add_action( 'wp_ajax_get_all_variation', array( $this, 'getAllvariation' ) );
		add_action( 'wp_ajax_nopriv_get_all_variation', array( $this, 'getAllvariation' ) );
		add_action( 'wp_ajax_mont_scan_size_images', array( $this, 'ajax_scan_size_images' ) );
		// Schema only once (dbDelta on every request made the whole site slow).
		add_action( 'admin_init', array( $this, 'maybe_install_schema' ) );
	}

	/**
	 * Create/migrate variation table at most once per schema version.
	 */
	public function maybe_install_schema() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$version = '1.2.0';
		if ( get_option( 'mont_variation_schema_version' ) === $version ) {
			return;
		}
		$this->create_table();
		$this->maybe_migrate_schema();
		update_option( 'mont_variation_schema_version', $version, true );
	}

	public function customVariation( $product ) {
		$output = array();

		if ( $product && $product->is_type( 'variable' ) ) {
			$variations      = $product->get_children();
			$attributes_list = array();

			foreach ( $variations as $variation_id ) {
				$variation  = wc_get_product( $variation_id );
				$attributes = $variation->get_attributes();

				foreach ( $attributes as $key => $value ) {
					$taxonomy = $key;
					if ( taxonomy_exists( $taxonomy ) ) {
						$value_term = get_term_by( 'name', $value, $taxonomy );
						if ( ! $value_term ) {
							$value_term = get_term_by( 'slug', sanitize_title( $value ), $taxonomy );
						}
						if ( $value_term ) {
							$value_slug = $value_term->slug;
							$value_name = $value_term->name;
						} else {
							$value_slug = sanitize_title( $value );
							$value_name = $value;
						}
					} else {
						$value_slug = sanitize_title( $value );
						$value_name = $value;
					}

					if ( ! isset( $attributes_list[ $key ] ) ) {
						$attributes_list[ $key ] = array(
							'attribute_slug' => $taxonomy,
							'attribute_name' => ucfirst( str_replace( 'pa_', '', $key ) ),
							'values'         => array(),
						);
					}

					$value_entry = array(
						'slug' => $value_slug,
						'name' => $value_name,
					);

					if ( ! in_array( $value_entry, $attributes_list[ $key ]['values'], true ) ) {
						$attributes_list[ $key ]['values'][] = $value_entry;
					}
				}
			}

			foreach ( $attributes_list as $attribute_data ) {
				$output[] = array(
					'attribute_slug'   => $attribute_data['attribute_slug'],
					'attribute_name'   => ucfirst( str_replace( 'pa_', '', $attribute_data['attribute_slug'] ) ),
					'attribute_values' => $attribute_data['values'],
				);
			}
		}

		return $output;
	}

	public function create_table() {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();
		$sql             = "CREATE TABLE IF NOT EXISTS {$this->table_name} (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            attributes varchar(255) NOT NULL,
            body_fit varchar(120) DEFAULT '',
            size_slug varchar(120) DEFAULT '',
            shirt_length float NOT NULL DEFAULT 0,
            sleeve_length float NOT NULL DEFAULT 0,
            shoulder float NOT NULL DEFAULT 0,
            half_chest float NOT NULL DEFAULT 0,
            half_waist float NOT NULL DEFAULT 0,
            half_bottom float NOT NULL DEFAULT 0,
            armhole float NOT NULL DEFAULT 0,
            neck_collar float NOT NULL DEFAULT 0,
            diagram_images longtext NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY attributes (attributes)
        ) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	public function maybe_migrate_schema() {
		global $wpdb;
		$cols = $wpdb->get_col( "DESCRIBE {$this->table_name}", 0 );
		if ( ! $cols ) {
			return;
		}
		if ( ! in_array( 'attributes', $cols, true ) && in_array( 'attribute_key', $cols, true ) ) {
			$wpdb->query( "ALTER TABLE {$this->table_name} CHANGE attribute_key attributes varchar(255) NOT NULL" );
			$cols = $wpdb->get_col( "DESCRIBE {$this->table_name}", 0 );
		}
		if ( ! in_array( 'body_fit', $cols, true ) ) {
			$wpdb->query( "ALTER TABLE {$this->table_name} ADD body_fit varchar(120) DEFAULT '' AFTER attributes" );
		}
		if ( ! in_array( 'size_slug', $cols, true ) ) {
			$wpdb->query( "ALTER TABLE {$this->table_name} ADD size_slug varchar(120) DEFAULT '' AFTER body_fit" );
		}
		if ( ! in_array( 'diagram_images', $cols, true ) ) {
			$wpdb->query( "ALTER TABLE {$this->table_name} ADD diagram_images longtext NULL AFTER neck_collar" );
		}

		// One-time backfill only for rows still missing fit/size columns.
		$rows = $wpdb->get_results(
			"SELECT id, attributes, body_fit, size_slug FROM {$this->table_name}
			 WHERE (body_fit = '' OR body_fit IS NULL OR size_slug = '' OR size_slug IS NULL)
			 LIMIT 500"
		);
		foreach ( $rows as $row ) {
			$parts = explode( '___', (string) $row->attributes );
			if ( count( $parts ) >= 2 ) {
				$wpdb->update(
					$this->table_name,
					array(
						'body_fit'  => $parts[0],
						'size_slug' => $parts[1],
					),
					array( 'id' => (int) $row->id ),
					array( '%s', '%s' ),
					array( '%d' )
				);
			}
		}
	}

	public function add_admin_menu() {
		add_menu_page(
			'Variation Settings',
			'Variation Settings',
			'manage_options',
			'variation-settings',
			array( $this, 'render_admin_page' ),
			'dashicons-editor-table',
			30
		);
	}

	public function enqueue_scripts( $hook ) {
		if ( 'toplevel_page_variation-settings' !== $hook ) {
			return;
		}
		$theme_dir = get_template_directory();
		$theme_uri = get_template_directory_uri();

		wp_enqueue_media();
		wp_enqueue_style( 'thickbox' );
		wp_enqueue_script( 'media-upload' );
		wp_enqueue_script( 'thickbox' );

		wp_enqueue_style(
			'mont-variation-admin',
			$theme_uri . '/assets/admin-variation-settings.css',
			array(),
			filemtime( $theme_dir . '/assets/admin-variation-settings.css' )
		);
		wp_enqueue_script(
			'variation-settings-script',
			$theme_uri . '/assets/only-variations.js',
			array( 'jquery', 'jquery-ui-core', 'media-upload', 'media-views', 'wp-util' ),
			filemtime( $theme_dir . '/assets/only-variations.js' ),
			true
		);
		wp_localize_script(
			'variation-settings-script',
			'variationSettings',
			array(
				'ajaxurl'  => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'variation-settings-nonce' ),
				'fields'   => array_keys( self::MEASUREMENT_FIELDS ),
				'diagrams' => Mont_Size_Diagram_Helper::MEASUREMENT_FILES,
				'labels'   => self::MEASUREMENT_FIELDS,
				'i18n'     => array(
					'saved'   => 'All changes saved.',
					'error'   => 'Could not save. Please try again.',
					'confirm' => 'Delete this size chart row?',
					'upload'  => 'Upload / Replace',
					'clear'   => 'Use auto',
					'custom'  => 'Custom',
					'auto'    => 'Auto',
					'missing' => 'Missing',
					'noMedia' => 'Media library could not open. Hard-refresh this page and try again.',
				),
			)
		);
	}

	private function get_body_fit_terms() {
		$terms = get_terms(
			array(
				'taxonomy'   => 'pa_body-fit',
				'hide_empty' => false,
			)
		);
		return ( ! empty( $terms ) && ! is_wp_error( $terms ) ) ? $terms : array();
	}

	private function get_size_terms() {
		$terms = get_terms(
			array(
				'taxonomy'   => 'pa_size',
				'hide_empty' => false,
			)
		);
		if ( empty( $terms ) || is_wp_error( $terms ) ) {
			return array();
		}
		usort(
			$terms,
			function ( $a, $b ) {
				$na = (int) preg_replace( '/\D+/', '', $a->slug );
				$nb = (int) preg_replace( '/\D+/', '', $b->slug );
				if ( $na && $nb && $na !== $nb ) {
					return $na - $nb;
				}
				return strcasecmp( $a->name, $b->name );
			}
		);
		return $terms;
	}

	private function get_rows_indexed() {
		global $wpdb;
		$rows = $wpdb->get_results( "SELECT * FROM {$this->table_name}" );
		$out  = array();
		foreach ( $rows as $row ) {
			$key         = $row->attributes;
			$out[ $key ] = $row;
			if ( ! empty( $row->body_fit ) && ! empty( $row->size_slug ) ) {
				$out[ $row->body_fit . '___' . $row->size_slug ] = $row;
			}
		}
		return $out;
	}

	public function render_admin_page() {
		$fits     = $this->get_body_fit_terms();
		$sizes    = $this->get_size_terms();
		$rows     = $this->get_rows_indexed();
		$library  = Mont_Size_Diagram_Helper::scan_library();
		$fields   = self::MEASUREMENT_FIELDS;
		$active   = ! empty( $fits ) ? $fits[0]->slug : '';
		?>
		<div class="wrap mont-vs-wrap">
			<div class="mont-vs-hero">
				<div>
					<h1>Size Chart Studio</h1>
					<p>Manage body-fit × size measurements in bulk. Diagrams auto-load from <code>Size/</code> — you can upload or replace any diagram per size.</p>
				</div>
				<div class="mont-vs-hero__actions">
					<button type="button" class="button button-primary mont-vs-save-bulk" id="mont-vs-save-bulk">Save all changes</button>
					<button type="button" class="button mont-vs-scan-images" id="mont-vs-scan-images">Refresh image library</button>
				</div>
			</div>

			<div class="mont-vs-toast" id="mont-vs-toast" hidden></div>

			<?php if ( empty( $fits ) || empty( $sizes ) ) : ?>
				<div class="notice notice-warning"><p>Add WooCommerce attributes <strong>Body Fit</strong> (<code>pa_body-fit</code>) and <strong>Size</strong> (<code>pa_size</code>) with terms to use this matrix.</p></div>
			<?php endif; ?>

			<div class="mont-vs-layout">
				<aside class="mont-vs-sidebar">
					<h3>Body fits</h3>
					<ul class="mont-vs-fit-tabs" role="tablist">
						<?php foreach ( $fits as $i => $fit ) : ?>
							<li>
								<button type="button"
									class="mont-vs-fit-tab <?php echo 0 === $i ? 'is-active' : ''; ?>"
									data-fit="<?php echo esc_attr( $fit->slug ); ?>">
									<?php echo esc_html( $fit->name ); ?>
									<span class="mont-vs-fit-tab__slug"><?php echo esc_html( $fit->slug ); ?></span>
								</button>
							</li>
						<?php endforeach; ?>
					</ul>

					<div class="mont-vs-library">
						<h3>Image library</h3>
						<p class="description">Auto-scanned from theme <code>/Size</code>. Used on the product page when fit + size are selected.</p>
						<div id="mont-vs-library-list">
							<?php foreach ( $library as $fit_folder => $items ) : ?>
								<details class="mont-vs-lib-group" <?php echo $fit_folder === 'CONTEMPORARY' ? 'open' : ''; ?>>
									<summary><?php echo esc_html( $fit_folder ); ?> <em>(<?php echo count( $items ); ?> sizes)</em></summary>
									<ul>
										<?php foreach ( $items as $item ) : ?>
											<li>
												<strong><?php echo esc_html( $item['code'] ); ?></strong>
												<span><?php echo esc_html( $item['folder'] ); ?></span>
												<span class="mont-vs-badge"><?php echo (int) $item['images']; ?> imgs</span>
											</li>
										<?php endforeach; ?>
									</ul>
								</details>
							<?php endforeach; ?>
							<?php if ( empty( $library ) ) : ?>
								<p>No folders found in <code>Size/</code>.</p>
							<?php endif; ?>
						</div>
					</div>
				</aside>

				<main class="mont-vs-main">
					<?php foreach ( $fits as $i => $fit ) : ?>
						<section class="mont-vs-panel <?php echo 0 === $i ? 'is-active' : ''; ?>" data-fit-panel="<?php echo esc_attr( $fit->slug ); ?>">
							<header class="mont-vs-panel__head">
								<h2><?php echo esc_html( $fit->name ); ?></h2>
								<p>Edit every size for this fit, then click <strong>Save all changes</strong>. Key format: <code><?php echo esc_html( $fit->slug ); ?>___size</code></p>
							</header>
							<div class="mont-vs-table-wrap">
								<table class="mont-vs-matrix">
									<thead>
										<tr>
											<th class="sticky-col">Size</th>
											<?php foreach ( $fields as $field_label ) : ?>
												<th><?php echo esc_html( $field_label ); ?></th>
											<?php endforeach; ?>
											<th>Diagrams</th>
											<th></th>
										</tr>
									</thead>
									<tbody>
										<?php foreach ( $sizes as $size ) :
											$key = $fit->slug . '___' . $size->slug;
											$row = isset( $rows[ $key ] ) ? $rows[ $key ] : null;
											$overrides = ( $row && ! empty( $row->diagram_images ) )
												? Mont_Size_Diagram_Helper::parse_overrides( $row->diagram_images )
												: array();
											$auto_imgs = Mont_Size_Diagram_Helper::get_images_for( $fit->slug, $size->slug );
											$imgs      = Mont_Size_Diagram_Helper::get_merged_images( $fit->slug, $size->slug, $overrides );
											$custom_n  = count( array_filter( $overrides ) );
											?>
											<tr class="mont-vs-row"
												data-id="<?php echo $row ? (int) $row->id : 0; ?>"
												data-fit="<?php echo esc_attr( $fit->slug ); ?>"
												data-size="<?php echo esc_attr( $size->slug ); ?>"
												data-key="<?php echo esc_attr( $key ); ?>"
												data-overrides="<?php echo esc_attr( wp_json_encode( $overrides ) ); ?>"
												data-auto="<?php echo esc_attr( wp_json_encode( $auto_imgs ) ); ?>">
												<td class="sticky-col">
													<strong><?php echo esc_html( $size->name ); ?></strong>
													<code><?php echo esc_html( $size->slug ); ?></code>
												</td>
												<?php foreach ( $fields as $field => $label ) :
													$val = $row && isset( $row->$field ) ? $row->$field : '';
													?>
													<td>
														<input type="number"
															step="0.1"
															class="mont-vs-input"
															data-field="<?php echo esc_attr( $field ); ?>"
															value="<?php echo esc_attr( $val ); ?>"
															placeholder="—">
													</td>
												<?php endforeach; ?>
												<td class="mont-vs-thumbs">
													<?php if ( $imgs ) : ?>
														<?php foreach ( array_slice( $imgs, 0, 4 ) as $img_url ) :
															$thumb_url = Mont_Size_Diagram_Helper::ensure_thumb_url( $img_url );
															?>
															<img src="<?php echo esc_url( $thumb_url ); ?>" alt="" loading="lazy">
														<?php endforeach; ?>
														<span class="mont-vs-badge ok"><?php echo count( $imgs ); ?> linked</span>
														<?php if ( $custom_n ) : ?>
															<span class="mont-vs-badge custom"><?php echo (int) $custom_n; ?> custom</span>
														<?php endif; ?>
													<?php else : ?>
														<span class="mont-vs-badge warn">No Size/ match</span>
													<?php endif; ?>
													<button type="button" class="button button-small mont-vs-edit-diagrams">
														<?php echo $imgs ? 'Replace / Upload' : 'Upload diagrams'; ?>
													</button>
												</td>
												<td>
													<?php if ( $row ) : ?>
														<button type="button" class="button-link-delete mont-vs-delete" data-id="<?php echo (int) $row->id; ?>">Delete</button>
													<?php endif; ?>
												</td>
											</tr>
										<?php endforeach; ?>
									</tbody>
								</table>
							</div>
						</section>
					<?php endforeach; ?>
				</main>
			</div>

			<!-- Diagram manager modal -->
			<div class="mont-vs-modal" id="mont-vs-diagram-modal" hidden>
				<div class="mont-vs-modal__backdrop" data-close-diagrams></div>
				<div class="mont-vs-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="mont-vs-diagram-title">
					<header class="mont-vs-modal__head">
						<div>
							<h2 id="mont-vs-diagram-title">Diagrams</h2>
							<p class="mont-vs-modal__sub" id="mont-vs-diagram-sub"></p>
						</div>
						<button type="button" class="mont-vs-modal__close" data-close-diagrams aria-label="Close">&times;</button>
					</header>
					<div class="mont-vs-modal__body" id="mont-vs-diagram-slots"></div>
					<footer class="mont-vs-modal__foot">
						<button type="button" class="button" data-close-diagrams>Cancel</button>
						<button type="button" class="button button-primary" id="mont-vs-diagram-apply">Apply diagrams</button>
					</footer>
				</div>
			</div>
		</div>
		<?php
	}

	public function save_variation() {
		check_ajax_referer( 'variation-settings-nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized' ), 403 );
		}

		$fit  = isset( $_POST['body_fit'] ) ? sanitize_title( wp_unslash( $_POST['body_fit'] ) ) : '';
		$size = isset( $_POST['size_slug'] ) ? sanitize_title( wp_unslash( $_POST['size_slug'] ) ) : '';
		$key  = isset( $_POST['attribute_key'] ) ? sanitize_text_field( wp_unslash( $_POST['attribute_key'] ) ) : '';

		if ( $fit && $size ) {
			$key = $fit . '___' . $size;
		} elseif ( $key && false !== strpos( $key, '___' ) ) {
			$parts = explode( '___', $key );
			$fit   = $parts[0];
			$size  = $parts[1];
		}

		if ( ! $key ) {
			wp_send_json_error( array( 'message' => 'Missing key' ) );
		}

		$data = $this->build_row_data( $key, $fit, $size, $_POST );
		$id   = isset( $_POST['id'] ) ? intval( $_POST['id'] ) : 0;
		$this->upsert_row( $data, $id );
		wp_send_json_success( array( 'key' => $key ) );
	}

	public function save_variation_bulk() {
		check_ajax_referer( 'variation-settings-nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized' ), 403 );
		}

		$raw = isset( $_POST['rows'] ) ? wp_unslash( $_POST['rows'] ) : '[]';
		if ( is_string( $raw ) ) {
			$rows = json_decode( $raw, true );
		} else {
			$rows = $raw;
		}
		if ( ! is_array( $rows ) ) {
			wp_send_json_error( array( 'message' => 'Invalid payload' ) );
		}

		$saved = 0;
		foreach ( $rows as $row ) {
			$fit  = isset( $row['body_fit'] ) ? sanitize_title( $row['body_fit'] ) : '';
			$size = isset( $row['size_slug'] ) ? sanitize_title( $row['size_slug'] ) : '';
			if ( ! $fit || ! $size ) {
				continue;
			}
			$key  = $fit . '___' . $size;
			$data = $this->build_row_data( $key, $fit, $size, $row );
			$id   = isset( $row['id'] ) ? intval( $row['id'] ) : 0;
			$this->upsert_row( $data, $id );
			$saved++;
		}

		wp_send_json_success( array( 'saved' => $saved ) );
	}

	private function build_row_data( $key, $fit, $size, $source ) {
		$data = array(
			'attributes' => $key,
			'body_fit'   => $fit,
			'size_slug'  => $size,
		);
		foreach ( array_keys( self::MEASUREMENT_FIELDS ) as $field ) {
			$data[ $field ] = isset( $source[ $field ] ) && $source[ $field ] !== ''
				? floatval( $source[ $field ] )
				: 0;
		}

		if ( array_key_exists( 'diagram_images', $source ) ) {
			$raw = $source['diagram_images'];
			if ( is_array( $raw ) ) {
				$clean = array();
				foreach ( $raw as $k => $url ) {
					$url = esc_url_raw( (string) $url );
					if ( $url ) {
						$clean[ sanitize_key( $k ) ] = $url;
					}
				}
				$data['diagram_images'] = wp_json_encode( $clean );
			} elseif ( is_string( $raw ) ) {
				$parsed = json_decode( $raw, true );
				$data['diagram_images'] = is_array( $parsed ) ? wp_json_encode( $parsed ) : '{}';
			} else {
				$data['diagram_images'] = '{}';
			}
		}

		return $data;
	}

	private function bust_chart_cache( $key, $fit = '', $size = '' ) {
		if ( $key ) {
			delete_transient( 'mont_chart_' . md5( $key ) );
			delete_transient( 'mont_chart_v2_' . md5( $key ) );
			delete_transient( 'mont_chart_v3_' . md5( $key ) );
		}
		if ( $fit && $size ) {
			$fit_l  = strtolower( $fit );
			$size_l = strtolower( $size );
			delete_transient( 'mont_szimg_' . md5( $fit_l . '|' . $size_l ) );
			delete_transient( 'mont_chart_' . md5( $fit . '___' . $size ) );
			delete_transient( 'mont_chart_v2_' . md5( $fit . '___' . $size ) );
			delete_transient( 'mont_chart_v3_' . md5( $fit . '___' . $size ) );
			// Frontend thumb pairs use a content-hash key; bump generation so new uploads show immediately.
			set_transient( 'mont_szfront_gen', (string) time(), WEEK_IN_SECONDS );
		}
	}

	private function upsert_row( $data, $id = 0 ) {
		global $wpdb;
		$this->bust_chart_cache(
			isset( $data['attributes'] ) ? $data['attributes'] : '',
			isset( $data['body_fit'] ) ? $data['body_fit'] : '',
			isset( $data['size_slug'] ) ? $data['size_slug'] : ''
		);

		$has_diagrams = array_key_exists( 'diagram_images', $data );
		$format       = array( '%s', '%s', '%s', '%f', '%f', '%f', '%f', '%f', '%f', '%f', '%f' );
		if ( $has_diagrams ) {
			$format[] = '%s';
		}

		if ( $id > 0 ) {
			// When updating without diagram payload, don't wipe existing images.
			if ( ! $has_diagrams ) {
				unset( $data['diagram_images'] );
				$format = array( '%s', '%s', '%s', '%f', '%f', '%f', '%f', '%f', '%f', '%f', '%f' );
			}
			$wpdb->update( $this->table_name, $data, array( 'id' => $id ), $format, array( '%d' ) );
			return $id;
		}

		$existing = $wpdb->get_var(
			$wpdb->prepare( "SELECT id FROM {$this->table_name} WHERE attributes = %s", $data['attributes'] )
		);
		if ( $existing ) {
			if ( ! $has_diagrams ) {
				unset( $data['diagram_images'] );
				$format = array( '%s', '%s', '%s', '%f', '%f', '%f', '%f', '%f', '%f', '%f', '%f' );
			}
			$wpdb->update( $this->table_name, $data, array( 'id' => (int) $existing ), $format, array( '%d' ) );
			return (int) $existing;
		}

		if ( ! $has_diagrams ) {
			$data['diagram_images'] = '{}';
			$format[] = '%s';
		}
		$wpdb->insert( $this->table_name, $data, $format );
		return (int) $wpdb->insert_id;
	}

	public function delete_variation() {
		check_ajax_referer( 'variation-settings-nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized' ), 403 );
		}
		global $wpdb;
		$id = intval( $_POST['id'] );
		$wpdb->delete( $this->table_name, array( 'id' => $id ), array( '%d' ) );
		wp_send_json_success();
	}

	public function getAllvariation() {
		global $wpdb;
		$key = isset( $_POST['key'] ) ? sanitize_text_field( wp_unslash( $_POST['key'] ) ) : '';
		if ( ! $key ) {
			wp_send_json( array() );
		}

		$transient_key = 'mont_chart_v3_' . md5( $key );
		$cached        = get_transient( $transient_key );
		if ( is_array( $cached ) ) {
			wp_send_json( $cached );
		}

		$select = 'attributes, body_fit, size_slug, shirt_length, sleeve_length, shoulder, half_chest, half_waist, half_bottom, neck_collar, diagram_images';
		$variations = $wpdb->get_results(
			$wpdb->prepare( "SELECT {$select} FROM {$this->table_name} WHERE attributes = %s LIMIT 1", $key )
		);

		// Fallback: body_fit + size_slug columns.
		if ( empty( $variations ) && false !== strpos( $key, '___' ) ) {
			$parts = explode( '___', $key, 2 );
			$variations = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT {$select} FROM {$this->table_name} WHERE body_fit = %s AND size_slug = %s LIMIT 1",
					$parts[0],
					$parts[1]
				)
			);
		}

		$out = array();
		$warm_urls = array();
		foreach ( $variations as $row ) {
			$fit  = ! empty( $row->body_fit ) ? $row->body_fit : ( explode( '___', $row->attributes )[0] ?? '' );
			$size = ! empty( $row->size_slug ) ? $row->size_slug : ( explode( '___', $row->attributes )[1] ?? '' );
			$overrides = ! empty( $row->diagram_images ) ? $row->diagram_images : '{}';
			// Never resize images during this AJAX — that was causing 10–20s waits.
			$images = Mont_Size_Diagram_Helper::get_frontend_images( $fit, $size, $overrides, false );
			foreach ( $images as $img ) {
				if ( ! empty( $img['full'] ) && ( empty( $img['thumb'] ) || $img['thumb'] === $img['full'] ) ) {
					$warm_urls[] = $img['full'];
				}
			}
			$out[] = array(
				'attributes'   => $row->attributes,
				'body_fit'     => $fit,
				'size_slug'    => $size,
				'shirt_length' => $row->shirt_length,
				'sleeve_length'=> $row->sleeve_length,
				'shoulder'     => $row->shoulder,
				'half_chest'   => $row->half_chest,
				'half_waist'   => $row->half_waist,
				'half_bottom'  => $row->half_bottom,
				'neck_collar'  => $row->neck_collar,
				'images'       => $images,
			);
		}

		// Even without chart numbers, return diagram URLs so the PDP can update icons.
		if ( empty( $out ) && false !== strpos( $key, '___' ) ) {
			$parts = explode( '___', $key, 2 );
			$images = Mont_Size_Diagram_Helper::get_frontend_images( $parts[0], $parts[1], array(), false );
			foreach ( $images as $img ) {
				if ( ! empty( $img['full'] ) && ( empty( $img['thumb'] ) || $img['thumb'] === $img['full'] ) ) {
					$warm_urls[] = $img['full'];
				}
			}
			$out[] = array(
				'attributes' => $key,
				'body_fit'   => $parts[0],
				'size_slug'  => $parts[1],
				'images'     => $images,
			);
		}

		set_transient( $transient_key, $out, 6 * HOUR_IN_SECONDS );

		// Flush JSON to the browser first, then warm thumbs in the background.
		if ( ! headers_sent() ) {
			nocache_headers();
			header( 'Content-Type: application/json; charset=' . get_option( 'blog_charset' ) );
			header( 'X-Content-Type-Options: nosniff' );
		}
		echo wp_json_encode( $out );
		if ( function_exists( 'fastcgi_finish_request' ) ) {
			@fastcgi_finish_request();
		} else {
			if ( function_exists( 'session_write_close' ) ) {
				@session_write_close();
			}
			while ( ob_get_level() > 0 ) {
				@ob_end_flush();
			}
			@flush();
		}

		if ( $warm_urls ) {
			foreach ( array_values( array_unique( $warm_urls ) ) as $url ) {
				$path = Mont_Size_Diagram_Helper::url_to_local_path( $url );
				if ( $path ) {
					Mont_Size_Diagram_Helper::make_cached_thumb( $path );
				}
			}
			delete_transient( 'mont_chart_v3_' . md5( $key ) );
			set_transient( 'mont_szfront_gen', (string) time(), WEEK_IN_SECONDS );
		}
		exit;
	}

	public function ajax_scan_size_images() {
		check_ajax_referer( 'variation-settings-nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized' ), 403 );
		}
		wp_send_json_success( array( 'library' => Mont_Size_Diagram_Helper::scan_library() ) );
	}

	public static function display_slider_on_product_page() {
		if ( self::$slider_displayed || ! is_product() ) {
			return;
		}

		global $post;
		if ( ! $post || ! isset( $post->ID ) ) {
			return;
		}

		$product_cats = wp_get_post_terms( $post->ID, 'product_cat', array( 'orderby' => 'term_id' ) );
		if ( empty( $product_cats ) || is_wp_error( $product_cats ) ) {
			return;
		}

		$main_cat = $product_cats[0];
		$term_id  = $main_cat->term_id;

		$categories_to_show = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'hide_empty' => false,
				'parent'     => 44,
				'orderby'    => 'menu_order',
				'order'      => 'ASC',
			)
		);

		if ( ! empty( $categories_to_show ) && ! is_wp_error( $categories_to_show ) ) {
			$slider_id = 'product_category_slider_' . wp_rand( 1000, 9999 );
			?>
			<div class="category-slider-container ssds">
				<button type="button" class="slider-arrow prev-arrow" aria-label="Previous categories">
					<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M15 5L8 12L15 19" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
				</button>
				<div class="category-slider" id="<?php echo esc_attr( $slider_id ); ?>">
					<?php foreach ( $categories_to_show as $category ) : ?>
						<a href="<?php echo esc_url( get_term_link( $category ) ); ?>"
						   class="category-item <?php echo ( (int) $category->term_id === (int) $term_id ) ? 'category-active' : ''; ?>">
							<?php echo esc_html( $category->name ); ?>
						</a>
					<?php endforeach; ?>
				</div>
				<button type="button" class="slider-arrow next-arrow" aria-label="Next categories">
					<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M9 5L16 12L9 19" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
				</button>
			</div>
			<?php
			self::$slider_displayed = true;
		}
	}
}

$custom_variation = new CustomVariation();
