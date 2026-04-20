<?php

namespace SGOplus\WP_Share;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Activator {

	public static function activate() {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();
		$table_name = $wpdb->prefix . 'sfs_logs';

		$sql = "CREATE TABLE $table_name (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			file_id bigint(20) NOT NULL,
			user_id bigint(20) DEFAULT 0,
			user_status varchar(20) DEFAULT 'guest',
			ip_address varchar(100) NOT NULL,
			country varchar(100) DEFAULT 'Unknown',
			user_agent text,
			timestamp datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
		
		// Set initial version for migration tracking
		update_option( 'sfs_db_version', '1.0' );
	}
}
