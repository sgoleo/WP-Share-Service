<?php
/**
 * Plugin Name: SGOplus File Share
 * Description: A secure plugin for sharing password-protected files with advanced performance optimization.
 * Version: 1.2.1
 * Author: SGOplus
 * Author URI: https://sgoplus.one/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 6.5
 * Tested up to: 6.9
 * Requires PHP: 7.4
 * Stable tag: 1.2.1
 * Text Domain: sgoplus-file-share
 */

namespace SGOplus\File_Share;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Define constants
define( 'SGOPLUS_FS_VERSION', '1.2.1' );
define( 'SGOPLUS_FS_PATH', wp_normalize_path( plugin_dir_path( __FILE__ ) ) );
define( 'SGOPLUS_FS_URL', plugin_dir_url( __FILE__ ) );

// Include required classes
require_once SGOPLUS_FS_PATH . 'includes/class-sgoplus-fs-activator.php';
require_once SGOPLUS_FS_PATH . 'includes/class-sgoplus-fs-cpt.php';
require_once SGOPLUS_FS_PATH . 'includes/class-sgoplus-fs-shortcode.php';
require_once SGOPLUS_FS_PATH . 'includes/class-sgoplus-fs-downloader.php';
require_once SGOPLUS_FS_PATH . 'includes/class-sgoplus-fs-settings.php';

/**
 * Global Helper: Check if PRO license is active
 */
function is_sgoplus_fs_pro_active() {
	$license_status = get_option( 'sgoplus_fs_license_status' );
	return ( isset( $license_status['isValid'] ) && $license_status['isValid'] === true );
}

/**
 * Activation Logic
 */
register_activation_hook( __FILE__, array( __NAMESPACE__ . '\\Activator', 'activate' ) );

/**
 * Initialization
 */
function init() {
	// Initialize CPT
	$cpt = new CPT();
	$cpt->register_post_type();
	
	// Initialize Shortcodes
	new Shortcode();
	
	// Initialize Downloader
	new Downloader();

	// Initialize Settings
	new Settings();
}
add_action( 'init', __NAMESPACE__ . '\\init' );
