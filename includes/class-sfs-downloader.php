<?php

class SFS_Downloader {

	public function __construct() {
		// Use a slightly later hook to ensure all headers are ready
		add_action( 'init', array( $this, 'handle_download_request' ), 20 );
	}

	public function handle_download_request() {
		if ( ! isset( $_POST['sfs_action'] ) || $_POST['sfs_action'] !== 'download' ) {
			return;
		}

		$post_id = isset( $_POST['sfs_id'] ) ? intval( $_POST['sfs_id'] ) : 0;
		if ( ! $post_id ) {
			wp_die( 'Invalid File ID.' );
		}

		$post = get_post( $post_id );
		if ( ! $post || $post->post_type !== 'sfs_file' ) {
			wp_die( 'File not found.' );
		}

		// Check Role Access (PRO)
		$allowed_roles = get_post_meta( $post_id, '_sfs_allowed_roles', true );
		if ( ! empty( $allowed_roles ) && is_array( $allowed_roles ) ) {
			if ( ! is_user_logged_in() ) {
				wp_die( 'Error: This file is restricted to members only. Please log in first.' );
			}
			$user = wp_get_current_user();
			$user_roles = (array) $user->roles;
			$has_access = false;
			foreach ( $allowed_roles as $role ) {
				if ( in_array( $role, $user_roles ) ) {
					$has_access = true;
					break;
				}
			}
			if ( ! $has_access && ! current_user_can( 'administrator' ) ) {
				wp_die( 'Error: You do not have the required role to download this file.' );
			}
		}

		// Check Expiration Date (PRO)
		$expiry_date = get_post_meta( $post_id, '_sfs_expiry_date', true );
		if ( ! empty( $expiry_date ) ) {
			$today = date( 'Y-m-d' );
			if ( $today > $expiry_date ) {
				wp_die( 'Error: This download link has expired.' );
			}
		}

		// Check Download Limit (PRO)
		$download_limit = get_post_meta( $post_id, '_sfs_download_limit', true );
		$current_count = get_post_meta( $post_id, '_sfs_download_count', true ) ?: 0;
		if ( ! empty( $download_limit ) && intval( $download_limit ) > 0 ) {
			if ( intval( $current_count ) >= intval( $download_limit ) ) {
				wp_die( 'Error: Download limit reached for this file.' );
			}
		}

		// Verify Password
		$hashed_password = get_post_meta( $post_id, '_sfs_password', true );
		if ( ! empty( $hashed_password ) ) {
			$submitted_password = isset( $_POST['sfs_password'] ) ? $_POST['sfs_password'] : '';
			if ( ! wp_check_password( $submitted_password, $hashed_password ) ) {
				wp_die( 'Incorrect password. Please try again.' );
			}
		}

		// Get File Info
		$file_url = get_post_meta( $post_id, '_sfs_file_url', true );
		if ( ! $file_url ) {
			wp_die( 'No file associated with this record.' );
		}

		$file_path = $this->url_to_path( $file_url );
		if ( ! $file_path || ! file_exists( $file_path ) ) {
			wp_die( 'Error: The requested file could not be found on the server. Path: ' . esc_html($file_path) );
		}

		// --- PRO Logic Start ---

		// 1. Record Download Log
		$this->record_download_log( $post_id );

		// 2. Handle Email Notifications
		$enable_notifications = get_post_meta( $post_id, '_sfs_enable_notifications', true );
		if ( $enable_notifications === 'yes' ) {
			$to = get_option( 'admin_email' );
			$subject = '[Share Service] New Download: ' . get_the_title( $post_id );
			$user_info = is_user_logged_in() ? wp_get_current_user()->display_name : 'Guest';
			$message = "File: " . get_the_title( $post_id ) . "\nBy: " . $user_info . "\nIP: " . $_SERVER['REMOTE_ADDR'] . "\nTime: " . current_time( 'mysql' );
			wp_mail( $to, $subject, $message );
		}

		// 3. Increment Download Count
		$count = intval( $current_count ) + 1;
		update_post_meta( $post_id, '_sfs_download_count', $count );

		// --- PRO Logic End ---

		// Clean output buffer
		while ( ob_get_level() > 0 ) {
			ob_end_clean();
		}

		// Serve File
		$file_name = basename( $file_path );
		$file_size = filesize( $file_path );
		$mime_type = wp_check_filetype( $file_name )['type'] ?: 'application/octet-stream';

		// Acceleration Optimization (Gated by PRO License)
		$accel_mode = get_option( 'sfs_acceleration_mode', 'standard' );
		if ( ! is_sfs_pro_active() ) {
			$accel_mode = 'standard';
		}

		header( 'Content-Description: File Transfer' );
		header( 'Content-Type: ' . $mime_type );
		header( 'Content-Disposition: attachment; filename="' . $file_name . '"' );
		header( 'Expires: 0' );
		header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
		header( 'Pragma: public' );

		if ( $accel_mode === 'x_sendfile' ) {
			// Apache / LiteSpeed
			header( 'X-Sendfile: ' . $file_path );
			exit;
		} elseif ( $accel_mode === 'x_accel' || $accel_mode === 'x_litespeed' ) {
			// Nginx or LiteSpeed
			// Both use a relative URI path from the web root
			$relative_path = str_replace( trailingslashit( str_replace( '\\', '/', ABSPATH ) ), '/', str_replace( '\\', '/', $file_path ) );
			
			if ( $accel_mode === 'x_litespeed' ) {
				header( 'X-LiteSpeed-Location: ' . $relative_path );
			} else {
				header( 'X-Accel-Redirect: ' . $relative_path );
			}
			exit;
		}

		// Fallback: Standard PHP Chunked
		header( 'Content-Length: ' . $file_size );
		set_time_limit(0); 
		$handle = fopen( $file_path, 'rb' );
		if ( $handle ) {
			while ( ! feof( $handle ) ) {
				echo fread( $handle, 1024 * 1024 ); 
				ob_flush();
				flush();
			}
			fclose( $handle );
		}
		exit;
	}

	private function record_download_log( $post_id ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'sfs_logs';
		
		$user_id = get_current_user_id();
		$user_status = is_user_logged_in() ? 'member' : 'guest';
		$ip_address = $_SERVER['REMOTE_ADDR'];
		
		// Basic Country Detection (Mocked or simple header check)
		$country = 'Unknown';
		if ( isset( $_SERVER['HTTP_CF_IPCOUNTRY'] ) ) { // Cloudflare
			$country = $_SERVER['HTTP_CF_IPCOUNTRY'];
		}

		$wpdb->insert(
			$table_name,
			array(
				'file_id'     => $post_id,
				'user_id'     => $user_id,
				'user_status' => $user_status,
				'ip_address'  => $ip_address,
				'country'     => $country,
				'user_agent'  => $_SERVER['HTTP_USER_AGENT'],
				'timestamp'   => current_time( 'mysql' ),
			)
		);
	}

	private function url_to_path( $url ) {
		$upload_dir = wp_upload_dir();
		$base_url = $upload_dir['baseurl'];
		$base_path = $upload_dir['basedir'];
		$norm_url = str_replace( array( 'http://', 'https://' ), '//', $url );
		$norm_base_url = str_replace( array( 'http://', 'https://' ), '//', $base_url );
		if ( strpos( $norm_url, $norm_base_url ) === 0 ) {
			return str_replace( $norm_base_url, $base_path, $norm_url );
		}
		$attachment_id = attachment_url_to_postid( $url );
		if ( $attachment_id ) {
			return get_attached_file( $attachment_id );
		}
		$content_url = content_url();
		$norm_content_url = str_replace( array( 'http://', 'https://' ), '//', $content_url );
		if ( strpos( $norm_url, $norm_content_url ) === 0 ) {
			$rel_path = str_replace( $norm_content_url, '', $norm_url );
			return WP_CONTENT_DIR . $rel_path;
		}
		return false;
	}
}
