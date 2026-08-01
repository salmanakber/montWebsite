<?php
/**
 * Resolve measurement diagram images from montTheme/Size/{Fit}/{SizeFolder}/*.jpg
 */
class Mont_Size_Diagram_Helper {

	const MEASUREMENT_FILES = array(
		'shirt_length' => array( 'Back.jpg', 'BackLength.jpg', '39REGULAR-MAN-BackLength.jpg' ),
		'sleeve_length' => array( 'Sleeve.jpg', '39REGULAR-MAN-Sleeve.jpg' ),
		'half_waist'    => array( 'Waist.jpg', '39REGULAR-MAN-Waist.jpg' ),
		'half_chest'    => array( 'Chest.jpg', '39REGULAR-MAN-Chest.jpg' ),
		'half_bottom'   => array( 'Bottom.jpg', '39REGULAR-MAN-bottom.jpg', 'bottom.jpg' ),
		'shoulder'      => array( 'BackShoulder.jpg', 'Shoulder.jpg', '39REGULAR-MAN-BackSholder.jpg', 'BackSholder.jpg' ),
		'neck_collar'   => array( 'Collar.jpg', '39REGULAR-MAN-Collar.jpg' ),
		'armhole'       => array( 'Sleeve.jpg', 'Chest.jpg' ),
	);

	/** Map WC body-fit slug fragments → Size/ top folder. */
	const FIT_FOLDERS = array(
		'contemporary' => 'CONTEMPORARY',
		'slim'         => 'Slim',
		'slimfit'      => 'Slim',
		'modern'       => 'Modern',
		'regular'      => 'Modern',
		'vanlig'       => 'Modern',
		'loose'        => 'Modern',
	);

	public static function size_root() {
		return trailingslashit( get_template_directory() ) . 'Size';
	}

	public static function size_uri() {
		return trailingslashit( get_template_directory_uri() ) . 'Size';
	}

	public static function normalize_fit_folder( $fit_slug ) {
		$slug = strtolower( (string) $fit_slug );
		$slug = str_replace( array( ' ', '_', '-' ), '', $slug );
		foreach ( self::FIT_FOLDERS as $needle => $folder ) {
			if ( false !== strpos( $slug, $needle ) ) {
				return $folder;
			}
		}
		return '';
	}

	/**
	 * Extract leading size number from slug/name (e.g. "40", "40m", "m-40", "462xl" → "40"/"462").
	 */
	public static function extract_size_code( $size_slug ) {
		$slug = strtolower( (string) $size_slug );
		if ( preg_match( '/(\d{2,3})/', $slug, $m ) ) {
			return $m[1];
		}
		return '';
	}

	/**
	 * Find Size/{Fit}/{SizeFolder} absolute path for a fit+size combo.
	 */
	public static function find_size_folder( $fit_slug, $size_slug ) {
		$fit_folder = self::normalize_fit_folder( $fit_slug );
		$code       = self::extract_size_code( $size_slug );
		if ( ! $fit_folder || ! $code ) {
			return '';
		}

		$base = self::size_root() . '/' . $fit_folder;
		if ( ! is_dir( $base ) ) {
			return '';
		}

		$dirs = scandir( $base );
		if ( ! $dirs ) {
			return '';
		}

		$matches = array();
		foreach ( $dirs as $dir ) {
			if ( '.' === $dir || '..' === $dir ) {
				continue;
			}
			$path = $base . '/' . $dir;
			if ( ! is_dir( $path ) ) {
				continue;
			}
			if ( 0 === strpos( $dir, $code ) ) {
				$next = substr( $dir, strlen( $code ), 1 );
				// Prefer code followed by a letter (40M) over accidental longer numbers (if any).
				$score = ( $next && ! ctype_digit( $next ) ) ? 2 : 1;
				$matches[] = array( 'path' => $path, 'score' => $score, 'len' => strlen( $dir ) );
			}
		}

		if ( empty( $matches ) ) {
			return '';
		}

		usort(
			$matches,
			function ( $a, $b ) {
				if ( $a['score'] === $b['score'] ) {
					return $b['len'] - $a['len'];
				}
				return $b['score'] - $a['score'];
			}
		);

		return $matches[0]['path'];
	}

	/**
	 * Resolve one diagram URL inside a size folder.
	 */
	public static function resolve_file_url( $folder_path, array $candidates ) {
		if ( ! $folder_path || ! is_dir( $folder_path ) ) {
			return '';
		}

		$files = scandir( $folder_path );
		if ( ! $files ) {
			return '';
		}

		$lower_map = array();
		foreach ( $files as $file ) {
			if ( '.' === $file || '..' === $file ) {
				continue;
			}
			$lower_map[ strtolower( $file ) ] = $file;
		}

		foreach ( $candidates as $candidate ) {
			$key = strtolower( $candidate );
			if ( isset( $lower_map[ $key ] ) ) {
				$rel = str_replace( self::size_root(), '', $folder_path . '/' . $lower_map[ $key ] );
				$rel = ltrim( str_replace( '\\', '/', $rel ), '/' );
				return trailingslashit( self::size_uri() ) . $rel;
			}
		}

		// Fuzzy: any file containing candidate stem.
		foreach ( $candidates as $candidate ) {
			$stem = strtolower( pathinfo( $candidate, PATHINFO_FILENAME ) );
			foreach ( $lower_map as $lower => $real ) {
				if ( false !== strpos( $lower, $stem ) ) {
					$rel = str_replace( self::size_root(), '', $folder_path . '/' . $real );
					$rel = ltrim( str_replace( '\\', '/', $rel ), '/' );
					return trailingslashit( self::size_uri() ) . $rel;
				}
			}
		}

		return '';
	}

	/**
	 * Return measurement_key => image URL map for fit+size.
	 */
	public static function get_images_for( $fit_slug, $size_slug ) {
		$folder = self::find_size_folder( $fit_slug, $size_slug );
		$out    = array();
		foreach ( self::MEASUREMENT_FILES as $key => $candidates ) {
			$url = self::resolve_file_url( $folder, $candidates );
			if ( $url ) {
				$out[ $key ] = $url;
			}
		}
		return $out;
	}

	/**
	 * Merge auto Size/ diagrams with custom URL overrides (custom wins).
	 *
	 * @param string $fit_slug
	 * @param string $size_slug
	 * @param array|string $overrides JSON string or assoc array of measurement => url
	 * @return array
	 */
	public static function get_merged_images( $fit_slug, $size_slug, $overrides = array() ) {
		$auto = self::get_images_for( $fit_slug, $size_slug );
		if ( is_string( $overrides ) ) {
			$decoded = json_decode( $overrides, true );
			$overrides = is_array( $decoded ) ? $decoded : array();
		}
		if ( ! is_array( $overrides ) ) {
			$overrides = array();
		}
		foreach ( $overrides as $key => $url ) {
			$url = esc_url_raw( (string) $url );
			if ( $url ) {
				$auto[ $key ] = $url;
			}
		}
		return $auto;
	}

	/**
	 * Decode overrides JSON safely.
	 */
	public static function parse_overrides( $raw ) {
		if ( is_array( $raw ) ) {
			return $raw;
		}
		if ( ! is_string( $raw ) || $raw === '' ) {
			return array();
		}
		$decoded = json_decode( $raw, true );
		return is_array( $decoded ) ? $decoded : array();
	}

	/**
	 * Scan Size/ tree for admin: fit => [ size_code => folder_name, images_count ]
	 */
	public static function scan_library() {
		$root = self::size_root();
		$out  = array();
		if ( ! is_dir( $root ) ) {
			return $out;
		}
		foreach ( scandir( $root ) as $fit ) {
			if ( '.' === $fit || '..' === $fit || ! is_dir( $root . '/' . $fit ) ) {
				continue;
			}
			$out[ $fit ] = array();
			foreach ( scandir( $root . '/' . $fit ) as $size_dir ) {
				$path = $root . '/' . $fit . '/' . $size_dir;
				if ( '.' === $size_dir || '..' === $size_dir || ! is_dir( $path ) ) {
					continue;
				}
				$code  = self::extract_size_code( $size_dir );
				$count = count( array_filter( scandir( $path ), function ( $f ) {
					return (bool) preg_match( '/\.(jpe?g|png|webp)$/i', $f );
				} ) );
				$out[ $fit ][] = array(
					'folder' => $size_dir,
					'code'   => $code,
					'images' => $count,
					'path'   => $fit . '/' . $size_dir,
				);
			}
		}
		return $out;
	}
}
