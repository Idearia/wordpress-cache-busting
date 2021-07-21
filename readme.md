Invalidate the browser cache of CSS e JS files loaded via wp_enqueue_script and wp_enqueue_style.

The cache can be invalidated in one of two ways;

- statically, specifying a fixed value for the `ver` query parameter,
- dynamically, setting 'ver' with the timestamp of the last file change (uses `filemtime()`).

You need to choose the desired way at the asset level configuring the $this->assets array in the main class.

Inspired by https://www.recolize.com/en/blog/wordpress-cache-busting-design-changes/

# Example

```php
class CacheBuster extends \Idearia\WordPressCacheBusting\CacheBuster
{
	protected $assets = [
		/**
		 * Example of dynamic cache invalidation
		 */
		[
			'handle' => 'some-script-or-css',
			'path'   => 'wp-content/plugins/some-plugin/script.js',
		],
		/**
		 * Example of static cache invalidation
		 */
		[
			'handle' => 'some-other-script-or-css',
			'ver'    => '1.2.3',
		],
	];
}

new CacheBuster;
```