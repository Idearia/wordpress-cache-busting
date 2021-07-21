<?php

namespace Idearia\WordPressCacheBusting;

defined( 'ABSPATH' ) || exit;

if ( class_exists( '\Idearia\Idearia\WordPressCacheBusting\Buster' ) ) {
	return;
}

/**
 * Extend this class to invalidate the browser cache of CSS e JS files
 * loaded via wp_enqueue_script and wp_enqueue_style.
 *
 * The cache can be invalidated in one of two ways;
 *
 * - statically, specifying a fixed value for the `ver` query parameter,
 * - dynamically, setting 'ver' with the timestamp of the last file change.
 *
 * You need to choose the desired way at the asset level configuring the $this->assets
 * array in the main class.
 *
 * Inspired by https://www.recolize.com/en/blog/wordpress-cache-busting-design-changes/
 */
abstract class CacheBuster
{
	/**
	 * List of the assets for which the cache needs to be invalidated.
	 *
	 * Each array element represents a single asset and can have this fields:
	 *  - handle: handle used to load the asset (first argument of wp_enqueue_xxx).
	 *  - ver:    value to assign to the 'ver' query parameter.
	 *  - path:   relative path of the asset in the filesystem (optional)
	 *
	 * If 'ver' is given, the 'ver' query parameter will be set to the given
	 * value > this is the static invalidation of the cache.
	 * 
	 * The 'path' is alternative to the 'ver' field. If it is given, the
	 * value of the 'ver' query parameter will be dynamically computed
	 * to be equal to the timestamp of the file's last change >  this is
	 * the dynamical invalidation of the cache.
	 */
	protected $assets = [
		/**
		 * Dynamic cache invalidation example
		 */
		// [
		// 	'handle' => 'some-script-js',
		// 	'path'   => 'wp-content/plugins/some-plugin/script.js',
		// ],
		/**
		 * Static cache invalidation example
		 */
		// [
		// 	'handle' => 'bookly-calendar-common.js',
		// 	'ver'    => '1.2.3',
		// ],
	];

	/**
	 * Debug variable
	 */
	protected $debug = false;

	/**
	 * Hook in methods.
	 */
	public function __construct()
	{
		add_filter( 'style_loader_src', [ $this, 'set_custom_ver_css_js' ], 9999, 2 );
		add_filter( 'script_loader_src', [ $this, 'set_custom_ver_css_js' ], 9999, 2 );
	}

	/**
	 * Set the 'ver' query parameter according to the configuration
	 * given in the $this->assets array
	 */
	public function set_custom_ver_css_js( $src, $handle )
	{
		// Asset configuration
		$asset = $this->get_asset( $handle );

		// Skip js/css which are not in the list
		if ( ! $asset ) {
			return $src;
		}

		$this->debug && \error_log( __CLASS__ . ": Before: $src" );

		// Case 1: static cache invalidation
		if ( ! empty( $asset['ver'] ) ) {
			$this->debug && error_log( 'DEBUG: ' . __CLASS__ . ': After : ' . add_query_arg( 'ver', $asset['ver'], $src ) );
			return add_query_arg( 'ver', $asset['ver'], $src );
		}

		// Case 1: dynamic cache invalidation
		if ( ! empty( $asset['path'] ) )
		{
			// Asset file path
			$full_path = \ABSPATH . $asset['path'];
			if ( ! file_exists( $full_path ) ) {
				$this->debug && error_log( 'WARNING: ' . __CLASS__ . ": Cannot invalidate cache for '$handle' because the asset file does not exist here > $full_path " );
				return $src;
			}
	
			// Timestamp of the file's last change
			$last_modified = filemtime( $full_path );
			if ( $last_modified ) {
				$this->debug && error_log( 'DEBUG: ' . __CLASS__ . ': After : ' . add_query_arg( 'ver', $last_modified, $src ) );
				return add_query_arg( 'ver', $last_modified, $src );
			}
		}

		return $src;
	}

	/**
	 * Given an asset handle, return the corresponding element
	 * in the configuration array $this->assets
	 */
	private function get_asset( string $handle ): ?array
	{
		$asset = array_filter(
            $this->assets,
            function( $a ) use ( $handle ) {
				return $a['handle'] === $handle;
			}
        );

		return array_shift( $asset );
	}
}
