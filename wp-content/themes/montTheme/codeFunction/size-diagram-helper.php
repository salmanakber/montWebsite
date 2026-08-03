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
		static $cache = array();
		$cache_key = strtolower( (string) $fit_slug ) . '|' . strtolower( (string) $size_slug );
		if ( isset( $cache[ $cache_key ] ) ) {
			return $cache[ $cache_key ];
		}

		$fit_folder = self::normalize_fit_folder( $fit_slug );
		$code       = self::extract_size_code( $size_slug );
		if ( ! $fit_folder || ! $code ) {
			$cache[ $cache_key ] = '';
			return '';
		}

		$base = self::size_root() . '/' . $fit_folder;
		if ( ! is_dir( $base ) ) {
			$cache[ $cache_key ] = '';
			return '';
		}

		$dirs = scandir( $base );
		if ( ! $dirs ) {
			$cache[ $cache_key ] = '';
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
				$score = ( $next && ! ctype_digit( $next ) ) ? 2 : 1;
				$matches[] = array( 'path' => $path, 'score' => $score, 'len' => strlen( $dir ) );
			}
		}

		if ( empty( $matches ) ) {
			$cache[ $cache_key ] = '';
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

		$cache[ $cache_key ] = $matches[0]['path'];
		return $cache[ $cache_key ];
	}

	/**
	 * Build lowercase filename => real filename map once per folder.
	 */
	private static function folder_file_map( $folder_path ) {
		static $maps = array();
		if ( isset( $maps[ $folder_path ] ) ) {
			return $maps[ $folder_path ];
		}
		$maps[ $folder_path ] = array();
		if ( ! $folder_path || ! is_dir( $folder_path ) ) {
			return $maps[ $folder_path ];
		}
		$files = @scandir( $folder_path );
		if ( ! $files ) {
			return $maps[ $folder_path ];
		}
		foreach ( $files as $file ) {
			if ( '.' === $file || '..' === $file ) {
				continue;
			}
			$maps[ $folder_path ][ strtolower( $file ) ] = $file;
		}
		return $maps[ $folder_path ];
	}

	/**
	 * Resolve one diagram URL inside a size folder (reuses cached scandir map).
	 */
	public static function resolve_file_url( $folder_path, array $candidates, $lower_map = null ) {
		if ( ! $folder_path ) {
			return '';
		}
		if ( null === $lower_map ) {
			$lower_map = self::folder_file_map( $folder_path );
		}
		if ( empty( $lower_map ) ) {
			return '';
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
	 * Cached in a transient (12h) so PDP AJAX does not rescan Size/ every click.
	 */
	public static function get_images_for( $fit_slug, $size_slug ) {
		$cache_key = 'mont_szimg_' . md5( strtolower( (string) $fit_slug ) . '|' . strtolower( (string) $size_slug ) );
		$cached    = get_transient( $cache_key );
		if ( is_array( $cached ) ) {
			return $cached;
		}

		$folder = self::find_size_folder( $fit_slug, $size_slug );
		$out    = array();
		if ( $folder ) {
			$lower_map = self::folder_file_map( $folder );
			foreach ( self::MEASUREMENT_FILES as $key => $candidates ) {
				$url = self::resolve_file_url( $folder, $candidates, $lower_map );
				if ( $url ) {
					$out[ $key ] = $url;
				}
			}
		}

		set_transient( $cache_key, $out, 12 * HOUR_IN_SECONDS );
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
		if ( is_string( $overrides ) ) {
			$decoded = json_decode( $overrides, true );
			$overrides = is_array( $decoded ) ? $decoded : array();
		}
		if ( ! is_array( $overrides ) ) {
			$overrides = array();
		}

		$clean = array();
		foreach ( $overrides as $key => $url ) {
			$url = esc_url_raw( (string) $url );
			if ( $url ) {
				$clean[ $key ] = $url;
			}
		}

		// Frontend only needs these six keys; skip filesystem if overrides cover them all.
		$needed = array( 'shirt_length', 'sleeve_length', 'half_waist', 'half_chest', 'half_bottom', 'shoulder' );
		$covered = true;
		foreach ( $needed as $key ) {
			if ( empty( $clean[ $key ] ) ) {
				$covered = false;
				break;
			}
		}
		if ( $covered ) {
			return $clean;
		}

		$auto = self::get_images_for( $fit_slug, $size_slug );
		foreach ( $clean as $key => $url ) {
			$auto[ $key ] = $url;
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
