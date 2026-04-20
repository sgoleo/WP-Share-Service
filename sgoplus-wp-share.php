<?php
/**
 * Plugin Name: SGOplus WP Share
 * Description: A secure plugin for sharing password-protected files with advanced performance optimization.
 * Version: 1.0.0
 * Author: SGOplus
 * Author URI: https://sgoplus.one
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 6.5
 * Tested up to: 6.9
 * Requires PHP: 7.4
 * Stable tag: 1.0.0
 * Text Domain: sgoplus-wp-share
 */

namespace SGOplus\WP_Share;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Define constants
define( 'SFS_VERSION', '1.8.5' );
define( 'SFS_PATH', plugin_dir_path( __FILE__ ) );
define( 'SFS_URL', plugin_dir_url( __FILE__ ) );

// Include required classes
require_once SFS_PATH . 'includes/class-sfs-activator.php';
require_once SFS_PATH . 'includes/class-sfs-cpt.php';
require_once SFS_PATH . 'includes/class-sfs-shortcode.php';
require_once SFS_PATH . 'includes/class-sfs-downloader.php';
require_once SFS_PATH . 'includes/class-sfs-settings.php';

/**
 * Global Helper: Check if PRO license is active
 */
function is_sfs_pro_active() {
	$license_status = get_option( 'sfs_license_status' );
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
