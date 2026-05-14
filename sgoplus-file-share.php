<?php
/**
 * Plugin Name: SGOplus File Share
 * Description: A secure plugin for sharing password-protected files with advanced performance optimization.
 * Version: 1.2.3
 * Author: SGOplus
 * Author URI: https://sgoplus.one/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 6.5
 * Tested up to: 6.9
 * Requires PHP: 8.0
 * Stable tag: 1.2.3
 * Text Domain: sgoplus-file-share
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Define constants (outside any namespace, so they are truly global).
define( 'SGOPLUS_FS_VERSION', '1.2.3' );
define( 'SGOPLUS_FS_PATH', wp_normalize_path( plugin_dir_path( __FILE__ ) ) );
define( 'SGOPLUS_FS_URL', plugin_dir_url( __FILE__ ) );

// Include required classes.
require_once SGOPLUS_FS_PATH . 'includes/class-sgoplus-fs-activator.php';
require_once SGOPLUS_FS_PATH . 'includes/class-sgoplus-fs-cpt.php';
require_once SGOPLUS_FS_PATH . 'includes/class-sgoplus-fs-shortcode.php';
require_once SGOPLUS_FS_PATH . 'includes/class-sgoplus-fs-downloader.php';
require_once SGOPLUS_FS_PATH . 'includes/class-sgoplus-fs-settings.php';

/**
 * Global Helper: Check if PRO license is active.
 * Declared at global scope (no namespace) so all included classes can call it freely.
 */
if ( ! function_exists( 'sgoplus_fs_is_pro_active' ) ) {
	function sgoplus_fs_is_pro_active() {
		// Permanently false for the Free version
		return false;
	}
}

/**
 * Activation Hook — use the fully-qualified class name as a string literal.
 * Avoids __NAMESPACE__ string concatenation which can produce double-backslash bugs.
 */
register_activation_hook( __FILE__, array( 'SGOplus\File_Share\Activator', 'activate' ) );

/**
 * Plugin Initialization — hooked to WordPress 'init'.
 */
add_action( 'init', 'sgoplus_fs_init' );

/**
 * Bootstrap all plugin components.
 */
function sgoplus_fs_init() {
	$cpt = new SGOplus\File_Share\CPT();
	$cpt->register_post_type();

	new SGOplus\File_Share\Shortcode();
	new SGOplus\File_Share\Downloader();
	new SGOplus\File_Share\Settings();
}
