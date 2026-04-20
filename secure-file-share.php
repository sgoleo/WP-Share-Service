<?php
/**
 * Plugin Name: SGOplus WP Share
 * Description: A secure plugin for sharing password-protected files.
 * Version: 1.8.5
 * Author: sgoleo, sgoplus.one
 * License: GPL-2.0+
 */

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
register_activation_hook( __FILE__, array( 'SFS_Activator', 'activate' ) );

/**
 * Initialization
 */
function sfs_init() {
	// Initialize CPT
	$cpt = new SFS_CPT();
	$cpt->register_post_type();
	
	// Initialize Shortcodes
	new SFS_Shortcode();
	
	// Initialize Downloader
	new SFS_Downloader();

	// Initialize Settings
	new SFS_Settings();
}
add_action( 'init', 'sfs_init' );
