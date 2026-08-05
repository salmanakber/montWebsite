<?php
/**
 * Import theme Size/{Fit}/{SizeFolder}/*.jpg into WP Media and seed variation_settings.
 * Measurements come from the public Size Guide tables (Contemporary / Modern / Slimfit).
 */
class Mont_Size_Media_Seed {

	const OPTION_DONE    = 'mont_size_media_seed_version';
	const OPTION_STATE   = 'mont_size_media_seed_state';
	const META_SOURCE    = '_mont_size_source';
	const SEED_VERSION   = '2.0.0';
	const BATCH_SIZE     = 4;

	/**
	 * Size Guide defaults (cm). Keys = size collar/code from folder names.
	 * Field names match variation_settings columns.
	 */
	const CHART = array(
		'CONTEMPORARY' => array(
			'38' => array( 'neck_collar' => 38, 'half_chest' => 106, 'half_waist' => 95, 'half_bottom' => 103, 'shoulder' => 44.5, 'sleeve_length' => 64.5, 'shirt_length' => 76.5 ),
			'40' => array( 'neck_collar' => 40, 'half_chest' => 113, 'half_waist' => 103, 'half_bottom' => 111, 'shoulder' => 46.5, 'sleeve_length' => 66.5, 'shirt_length' => 77.5 ),
			'42' => array( 'neck_collar' => 42, 'half_chest' => 122, 'half_waist' => 111, 'half_bottom' => 119, 'shoulder' => 49, 'sleeve_length' => 68.5, 'shirt_length' => 79 ),
			'44' => array( 'neck_collar' => 44, 'half_chest' => 130, 'half_waist' => 119, 'half_bottom' => 127, 'shoulder' => 52, 'sleeve_length' => 70.5, 'shirt_length' => 81 ),
			'46' => array( 'neck_collar' => 46, 'half_chest' => 134, 'half_waist' => 125, 'half_bottom' => 133, 'shoulder' => 55, 'sleeve_length' => 71.5, 'shirt_length' => 82.5 ),
			'48' => array( 'neck_collar' => 48, 'half_chest' => 146, 'half_waist' => 136, 'half_bottom' => 146, 'shoulder' => 58, 'sleeve_length' => 74.5, 'shirt_length' => 85 ),
		),
		'Modern' => array(
			'39' => array( 'neck_collar' => 39, 'half_chest' => 110, 'half_waist' => 100, 'half_bottom' => 109, 'shoulder' => 46.5, 'sleeve_length' => 66, 'shirt_length' => 79 ),
			'40' => array( 'neck_collar' => 40, 'half_chest' => 114, 'half_waist' => 103, 'half_bottom' => 112, 'shoulder' => 48, 'sleeve_length' => 66.5, 'shirt_length' => 80 ),
			'41' => array( 'neck_collar' => 41, 'half_chest' => 116, 'half_waist' => 106, 'half_bottom' => 115, 'shoulder' => 49.5, 'sleeve_length' => 67, 'shirt_length' => 81 ),
			'42' => array( 'neck_collar' => 42, 'half_chest' => 120, 'half_waist' => 109, 'half_bottom' => 120, 'shoulder' => 51, 'sleeve_length' => 67.5, 'shirt_length' => 82 ),
			'43' => array( 'neck_collar' => 43, 'half_chest' => 124, 'half_waist' => 114, 'half_bottom' => 125, 'shoulder' => 53.5, 'sleeve_length' => 68, 'shirt_length' => 83 ),
			'44' => array( 'neck_collar' => 44, 'half_chest' => 127, 'half_waist' => 118, 'half_bottom' => 130, 'shoulder' => 55, 'sleeve_length' => 69, 'shirt_length' => 84 ),
			'45' => array( 'neck_collar' => 45, 'half_chest' => 132, 'half_waist' => 121, 'half_bottom' => 133, 'shoulder' => 56.5, 'sleeve_length' => 69.5, 'shirt_length' => 85 ),
			'46' => array( 'neck_collar' => 46, 'half_chest' => 135, 'half_waist' => 124, 'half_bottom' => 136, 'shoulder' => 58, 'sleeve_length' => 70, 'shirt_length' => 86 ),
		),
		'Slim' => array(
			'36' => array( 'neck_collar' => 36, 'half_chest' => 93, 'half_waist' => 83, 'half_bottom' => 92, 'shoulder' => 41, 'sleeve_length' => 64, 'shirt_length' => 75 ),
			'37' => array( 'neck_collar' => 37, 'half_chest' => 96, 'half_waist' => 86, 'half_bottom' => 96, 'shoulder' => 42.5, 'sleeve_length' => 64.5, 'shirt_length' => 77 ),
			'38' => array( 'neck_collar' => 38, 'half_chest' => 102, 'half_waist' => 91, 'half_bottom' => 100, 'shoulder' => 44, 'sleeve_length' => 65, 'shirt_length' => 78 ),
			'39' => array( 'neck_collar' => 39, 'half_chest' => 105, 'half_waist' => 94, 'half_bottom' => 104, 'shoulder' => 45.5, 'sleeve_length' => 65.5, 'shirt_length' => 79 ),
			'40' => array( 'neck_collar' => 40, 'half_chest' => 109, 'half_waist' => 99, 'half_bottom' => 108, 'shoulder' => 47, 'sleeve_length' => 66, 'shirt_length' => 80 ),
			'41' => array( 'neck_collar' => 41, 'half_chest' => 113, 'half_waist' => 103, 'half_bottom' => 112, 'shoulder' => 48, 'sleeve_length' => 67, 'shirt_length' => 81 ),
			'42' => array( 'neck_collar' => 42, 'half_chest' => 118, 'half_waist' => 106, 'half_bottom' => 117, 'shoulder' => 49, 'sleeve_length' => 68, 'shirt_length' => 82 ),
			'43' => array( 'neck_collar' => 43, 'half_chest' => 123, 'half_waist' => 110, 'half_bottom' => 122, 'shoulder' => 51, 'sleeve_length' => 69, 'shirt_length' => 83 ),
			'44' => array( 'neck_collar' => 44, 'half_chest' => 126, 'half_waist' => 113, 'half_bottom' => 125, 'shoulder' => 52, 'sleeve_length' => 69.5, 'shirt_length' => 84 ),
		),
	);

	/** Fit folder → preferred WC body-fit slug fragments. */
	const FIT_SLUG_HINTS = array(
		'CONTEMPORARY' => array( 'contemporary' ),
		'Modern'       => array( 'modern', 'regular', 'vanlig' ),
		'Slim'         => array( 'slim', 'slimfit' ),
	);

	public static function is_complete() {
		return get_option( self::OPTION_DONE ) === self::SEED_VERSION;
	}

	/**
	 * Build flat job list: one job per size folder (upload all its images + upsert row).
	 *
	 * @return array
	 */
	public static function build_jobs() {
		$root = Mont_Size_Diagram_Helper::size_root();
		$jobs = array();
		if ( ! is_dir( $root ) ) {
			return $jobs;
		}

		foreach ( array( 'CONTEMPORARY', 'Modern', 'Slim' ) as $fit_folder ) {
			$base = $root . '/' . $fit_folder;
			if ( ! is_dir( $base ) ) {
				continue;
			}
			foreach ( scandir( $base ) as $size_dir ) {
				if ( '.' === $size_dir || '..' === $size_dir ) {
					continue;
				}
				$path = $base . '/' . $size_dir;
				if ( ! is_dir( $path ) ) {
					continue;
				}
				$code = Mont_Size_Diagram_Helper::extract_size_code( $size_dir );
				if ( ! $code ) {
					continue;
				}
				$jobs[] = array(
					'fit_folder' => $fit_folder,
					'size_dir'   => $size_dir,
					'size_code'  => $code,
					'path'       => $path,
				);
			}
		}
		return $jobs;
	}

	/**
	 * Reset import state so a full re-import can run (keeps already-uploaded media).
	 */
	public static function reset_state() {
		delete_option( self::OPTION_STATE );
		delete_option( self::OPTION_DONE );
	}

	/**
	 * Process next batch of size folders. Returns progress payload.
	 *
	 * @param int $batch
	 * @return array
	 */
	public static function process_batch( $batch = self::BATCH_SIZE ) {
		@set_time_limit( 120 );
		$batch = max( 1, min( 10, (int) $batch ) );

		$state = get_option( self::OPTION_STATE, array() );
		if ( empty( $state['jobs'] ) || empty( $state['version'] ) || $state['version'] !== self::SEED_VERSION ) {
			$state = array(
				'version'   => self::SEED_VERSION,
				'jobs'      => self::build_jobs(),
				'index'     => 0,
				'uploaded'  => 0,
				'updated'   => 0,
				'skipped'   => 0,
				'errors'    => array(),
			);
		}

		$jobs  = $state['jobs'];
		$total = count( $jobs );
		$i     = (int) $state['index'];
		$end   = min( $total, $i + $batch );

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$fit_map  = self::resolve_fit_term_map();
		$size_map = self::resolve_size_term_map();

		for ( ; $i < $end; $i++ ) {
			$job = $jobs[ $i ];
			try {
				$result = self::import_size_folder( $job, $fit_map, $size_map );
				$state['uploaded'] += (int) $result['uploaded'];
				$state['skipped']  += (int) $result['skipped'];
				if ( ! empty( $result['updated'] ) ) {
					$state['updated']++;
				}
			} catch ( Exception $e ) {
				$state['errors'][] = $job['fit_folder'] . '/' . $job['size_dir'] . ': ' . $e->getMessage();
			}
		}

		$state['index'] = $i;
		$done           = $i >= $total;
		update_option( self::OPTION_STATE, $state, false );

		if ( $done ) {
			update_option( self::OPTION_DONE, self::SEED_VERSION, true );
			// Bust chart caches so PDP picks up media thumbs immediately.
			delete_transient( 'mont_all_charts_meas_v1' );
			delete_transient( 'mont_all_charts_meas_v2' );
			set_transient( 'mont_szfront_gen', (string) time(), WEEK_IN_SECONDS );
		}

		return array(
			'done'       => $done,
			'total'      => $total,
			'index'      => $i,
			'percent'    => $total ? (int) round( ( $i / $total ) * 100 ) : 100,
			'uploaded'   => (int) $state['uploaded'],
			'updated'    => (int) $state['updated'],
			'skipped'    => (int) $state['skipped'],
			'errors'     => array_slice( (array) $state['errors'], -8 ),
			'current'    => $done ? '' : ( $jobs[ $i ]['fit_folder'] . '/' . $jobs[ $i ]['size_dir'] ),
		);
	}

	/**
	 * Upload diagrams for one size folder and upsert the variation_settings row.
	 */
	private static function import_size_folder( array $job, array $fit_map, array $size_map ) {
		$fit_folder = $job['fit_folder'];
		$size_code  = $job['size_code'];
		$path       = $job['path'];

		$body_fit  = isset( $fit_map[ $fit_folder ] ) ? $fit_map[ $fit_folder ] : sanitize_title( $fit_folder );
		$size_slug = isset( $size_map[ $size_code ] ) ? $size_map[ $size_code ] : $size_code;
		$key       = $body_fit . '___' . $size_slug;

		$files_map = self::folder_files( $path );
		$diagrams  = array();
		$uploaded  = 0;
		$skipped   = 0;

		foreach ( Mont_Size_Diagram_Helper::MEASUREMENT_FILES as $field => $candidates ) {
			$filename = self::match_candidate( $files_map, $candidates );
			if ( ! $filename ) {
				continue;
			}
			$source_rel = $fit_folder . '/' . $job['size_dir'] . '/' . $filename;
			$abs        = $path . '/' . $filename;
			$att_id     = self::ensure_attachment( $abs, $source_rel, $field . ' ' . $job['size_dir'] );
			if ( ! $att_id ) {
				continue;
			}
			if ( get_post_meta( $att_id, '_mont_just_uploaded', true ) ) {
				$uploaded++;
				delete_post_meta( $att_id, '_mont_just_uploaded' );
			} else {
				$skipped++;
			}
			$full  = wp_get_attachment_image_url( $att_id, 'full' );
			$thumb = wp_get_attachment_image_url( $att_id, 'mont_diagram_thumb' );
			if ( ! $thumb ) {
				$thumb = wp_get_attachment_image_url( $att_id, 'thumbnail' );
			}
			if ( ! $thumb ) {
				$thumb = $full;
			}
			$diagrams[ $field ] = array(
				'id'    => (int) $att_id,
				'full'  => $full ? $full : '',
				'thumb' => $thumb ? $thumb : '',
			);
		}

		$measures = array();
		if ( isset( self::CHART[ $fit_folder ][ $size_code ] ) ) {
			$measures = self::CHART[ $fit_folder ][ $size_code ];
		}

		$data = array(
			'attributes'     => $key,
			'body_fit'       => $body_fit,
			'size_slug'      => $size_slug,
			'shirt_length'   => isset( $measures['shirt_length'] ) ? $measures['shirt_length'] : 0,
			'sleeve_length'  => isset( $measures['sleeve_length'] ) ? $measures['sleeve_length'] : 0,
			'shoulder'       => isset( $measures['shoulder'] ) ? $measures['shoulder'] : 0,
			'half_chest'     => isset( $measures['half_chest'] ) ? $measures['half_chest'] : 0,
			'half_waist'     => isset( $measures['half_waist'] ) ? $measures['half_waist'] : 0,
			'half_bottom'    => isset( $measures['half_bottom'] ) ? $measures['half_bottom'] : 0,
			'armhole'        => 0,
			'neck_collar'    => isset( $measures['neck_collar'] ) ? $measures['neck_collar'] : 0,
			'diagram_images' => wp_json_encode( $diagrams ),
		);

		self::upsert_row( $data );

		return array(
			'uploaded' => $uploaded,
			'skipped'  => $skipped,
			'updated'  => 1,
			'key'      => $key,
		);
	}

	private static function folder_files( $path ) {
		$map = array();
		foreach ( (array) @scandir( $path ) as $file ) {
			if ( '.' === $file || '..' === $file ) {
				continue;
			}
			if ( ! preg_match( '/\.(jpe?g|png|webp)$/i', $file ) ) {
				continue;
			}
			$map[ strtolower( $file ) ] = $file;
		}
		return $map;
	}

	private static function match_candidate( array $files_map, array $candidates ) {
		foreach ( $candidates as $candidate ) {
			$key = strtolower( $candidate );
			if ( isset( $files_map[ $key ] ) ) {
				return $files_map[ $key ];
			}
		}
		foreach ( $candidates as $candidate ) {
			$stem = strtolower( pathinfo( $candidate, PATHINFO_FILENAME ) );
			foreach ( $files_map as $lower => $real ) {
				if ( false !== strpos( $lower, $stem ) ) {
					return $real;
				}
			}
		}
		return '';
	}

	/**
	 * Sideload file into Media Library once; reuse by source meta.
	 */
	private static function ensure_attachment( $abs_path, $source_rel, $title ) {
		global $wpdb;
		$existing = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value = %s LIMIT 1",
				self::META_SOURCE,
				$source_rel
			)
		);
		if ( $existing ) {
			return (int) $existing;
		}

		if ( ! file_exists( $abs_path ) || ! is_readable( $abs_path ) ) {
			return 0;
		}

		$tmp = wp_tempnam( basename( $abs_path ) );
		if ( ! $tmp || ! @copy( $abs_path, $tmp ) ) {
			return 0;
		}

		$file_array = array(
			'name'     => basename( $abs_path ),
			'tmp_name' => $tmp,
		);

		$att_id = media_handle_sideload( $file_array, 0, $title );
		if ( is_wp_error( $att_id ) ) {
			@unlink( $tmp );
			return 0;
		}

		update_post_meta( $att_id, self::META_SOURCE, $source_rel );
		update_post_meta( $att_id, '_mont_just_uploaded', 1 );
		return (int) $att_id;
	}

	private static function upsert_row( array $data ) {
		global $wpdb;
		$table = $wpdb->prefix . 'variation_settings';
		$id    = $wpdb->get_var(
			$wpdb->prepare( "SELECT id FROM {$table} WHERE attributes = %s", $data['attributes'] )
		);
		$format = array( '%s', '%s', '%s', '%f', '%f', '%f', '%f', '%f', '%f', '%f', '%f', '%s' );
		if ( $id ) {
			$wpdb->update( $table, $data, array( 'id' => (int) $id ), $format, array( '%d' ) );
			delete_transient( 'mont_chart_meas_v2_' . md5( $data['attributes'] ) );
			delete_transient( 'mont_diag_v2_' . md5( $data['attributes'] ) );
			return (int) $id;
		}
		$wpdb->insert( $table, $data, $format );
		return (int) $wpdb->insert_id;
	}

	/**
	 * Map fit folder → best matching pa_body-fit term slug.
	 */
	private static function resolve_fit_term_map() {
		$out   = array();
		$terms = get_terms(
			array(
				'taxonomy'   => 'pa_body-fit',
				'hide_empty' => false,
			)
		);
		if ( is_wp_error( $terms ) ) {
			$terms = array();
		}
		foreach ( self::FIT_SLUG_HINTS as $folder => $hints ) {
			$match = '';
			foreach ( $terms as $term ) {
				$slug = strtolower( str_replace( array( ' ', '_', '-' ), '', $term->slug ) );
				$name = strtolower( str_replace( array( ' ', '_', '-' ), '', $term->name ) );
				foreach ( $hints as $hint ) {
					if ( false !== strpos( $slug, $hint ) || false !== strpos( $name, $hint ) ) {
						$match = $term->slug;
						break 2;
					}
				}
			}
			$out[ $folder ] = $match ? $match : sanitize_title( $folder );
		}
		return $out;
	}

	/**
	 * Map size code (e.g. "40") → best matching pa_size term slug.
	 */
	private static function resolve_size_term_map() {
		$out   = array();
		$terms = get_terms(
			array(
				'taxonomy'   => 'pa_size',
				'hide_empty' => false,
			)
		);
		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return $out;
		}
		foreach ( $terms as $term ) {
			$code = Mont_Size_Diagram_Helper::extract_size_code( $term->slug );
			if ( ! $code ) {
				$code = Mont_Size_Diagram_Helper::extract_size_code( $term->name );
			}
			if ( ! $code ) {
				continue;
			}
			// Prefer exact numeric slug / shorter slug.
			if ( ! isset( $out[ $code ] ) || strlen( $term->slug ) < strlen( $out[ $code ] ) ) {
				$out[ $code ] = $term->slug;
			}
		}
		return $out;
	}
}
