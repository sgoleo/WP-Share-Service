<?php

class SFS_Activator {

	/**
	 * Activation hook callback
	 */
	public static function activate() {
		self::create_secure_directory();
	}

	/**
	 * Create the secure files directory and add .htaccess
	 */
	private static function create_secure_directory() {
		$upload_dir = wp_upload_dir();
		$secure_dir = $upload_dir['basedir'] . '/secure_files';

		if ( ! file_exists( $secure_dir ) ) {
			if ( ! wp_mkdir_p( $secure_dir ) ) {
				error_log( 'SFS Error: Could not create secure files directory at ' . $secure_dir );
				return;
			}
		}

		// Create .htaccess to block direct access
		$htaccess_file = $secure_dir . '/.htaccess';
		if ( ! file_exists( $htaccess_file ) ) {
			$content = "<IfModule mod_authz_core.c>\n    Require all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\n    Order deny,allow\n    Deny from all\n</IfModule>";
			file_put_contents( $htaccess_file, $content );
		}

		// Create index.php to prevent directory listing
		$index_file = $secure_dir . '/index.php';
		if ( ! file_exists( $index_file ) ) {
			file_put_contents( $index_file, "<?php // Silence is golden" );
		}
	}
}
