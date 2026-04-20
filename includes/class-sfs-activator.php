<?php

class SFS_Activator {

	public static function activate() {
		// Create the secure files directory and add .htaccess
		$upload_dir = wp_upload_dir();
		$secure_dir = $upload_dir['basedir'] . '/secure_files';

		if ( ! file_exists( $secure_dir ) ) {
			if ( ! wp_mkdir_p( $secure_dir ) ) {
				error_log( 'SFS Error: Could not create secure files directory at ' . $secure_dir );
			}
		}

		// Protect the directory with .htaccess (Apache)
		$htaccess_file = $secure_dir . '/.htaccess';
		if ( ! file_exists( $htaccess_file ) ) {
			$htaccess_content = "Order Deny,Allow\nDeny from all";
			file_put_contents( $htaccess_file, $htaccess_content );
		}

		// Create PRO Log Table
		self::create_log_table();
	}

	private static function create_log_table() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'sfs_logs';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			file_id bigint(20) NOT NULL,
			user_id bigint(20) DEFAULT 0,
			user_status varchar(20) DEFAULT 'guest',
			ip_address varchar(45) NOT NULL,
			country varchar(100) DEFAULT 'Unknown',
			user_agent text,
			timestamp datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY file_id (file_id)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}
}
