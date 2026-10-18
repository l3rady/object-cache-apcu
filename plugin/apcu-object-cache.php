<?php
/**
 * Plugin Name: APCu Object Cache
 * Plugin URI: https://github.com/l3rady/object-cache-apcu
 * Description: Manages the APCu object cache drop-in for WordPress.
 * Requires at least: 5.6
 * Requires PHP: 7.4
 * x-release-please-start-version
 * Version: 2.0.1
 * x-release-please-end
 * Author: Scott Cariss
 * Author URI: https://cariss.dev
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: apcu-object-cache
 *
 * @package apcu-object-cache
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// The drop-in the plugin installs is shipped next to this file, byte for byte as in the repository.
define( 'APCU_OBJECT_CACHE_PLUGIN_FILE', __FILE__ );
define( 'APCU_OBJECT_CACHE_DROPIN_SOURCE', __DIR__ . '/object-cache.php' );
