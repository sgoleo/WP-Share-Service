<?php

namespace SGOplus\File_Share;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Downloader {

	public function __construct() {
		// Use a slightly later hook to ensure all headers are ready
		add_action( 'init', array( $this, 'handle_download_request' ), 20 );
	}

	public function handle_download_request() {
		if ( ! isset( $_POST['sgoplus_fs_action'] ) || $_POST['sgoplus_fs_action'] !== 'download' ) {
			return;
		}

		// Verify Nonce for security
		if ( ! isset( $_POST['sgoplus_fs_download_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['sgoplus_fs_download_nonce'] ) ), 'sgoplus_fs_download_file' ) ) {
			wp_die( esc_html__( 'Security check failed. Please refresh the page and try again.', 'sgoplus-file-share' ) );
		}

		$post_id = isset( $_POST['sgoplus_fs_id'] ) ? intval( wp_unslash( $_POST['sgoplus_fs_id'] ) ) : 0;
		if ( ! $post_id ) {
			wp_die( esc_html__( 'Invalid File ID.', 'sgoplus-file-share' ) );
		}

		$post = get_post( $post_id );
		if ( ! $post || $post->post_type !== 'sgoplus_fs_file' ) {
			wp_die( esc_html__( 'File not found.', 'sgoplus-file-share' ) );
		}

		// Check Role Access (PRO)
		$allowed_roles = get_post_meta( $post_id, '_sgoplus_fs_allowed_roles', true );
		if ( ! empty( $allowed_roles ) && is_array( $allowed_roles ) ) {
			if ( ! is_user_logged_in() ) {
				wp_die( esc_html__( 'Error: This file is restricted to members only. Please log in first.', 'sgoplus-file-share' ) );
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
				wp_die( esc_html__( 'Error: You do not have the required role to download this file.', 'sgoplus-file-share' ) );
			}
		}

		// Check Expiration Date (PRO)
		$expiry_date = get_post_meta( $post_id, '_sgoplus_fs_expiry_date', true );
		if ( ! empty( $expiry_date ) ) {
			$today = gmdate( 'Y-m-d' );
			if ( $today > $expiry_date ) {
				wp_die( esc_html__( 'Error: This download link has expired.', 'sgoplus-file-share' ) );
			}
		}

		// Check Download Limit (PRO)
		$download_limit = get_post_meta( $post_id, '_sgoplus_fs_download_limit', true );
		$current_count = get_post_meta( $post_id, '_sgoplus_fs_download_count', true ) ?: 0;
		if ( ! empty( $download_limit ) && intval( $download_limit ) > 0 ) {
			if ( intval( $current_count ) >= intval( $download_limit ) ) {
				wp_die( esc_html__( 'Error: Download limit reached for this file.', 'sgoplus-file-share' ) );
			}
		}

		// Verify Password
		$hashed_password = get_post_meta( $post_id, '_sgoplus_fs_password', true );
		if ( ! empty( $hashed_password ) ) {
			$submitted_password = isset( $_POST['sgoplus_fs_password'] ) ? sanitize_text_field( wp_unslash( $_POST['sgoplus_fs_password'] ) ) : '';
			if ( ! wp_check_password( $submitted_password, $hashed_password ) ) {
				wp_die( esc_html__( 'Incorrect password. Please try again.', 'sgoplus-file-share' ) );
			}
		}

		// Get File Info
		$file_url = get_post_meta( $post_id, '_sgoplus_fs_file_url', true );
		if ( ! $file_url ) {
			wp_die( esc_html__( 'No file associated with this record.', 'sgoplus-file-share' ) );
		}

		$file_path = $this->url_to_path( $file_url );
		if ( ! $file_path || ! file_exists( $file_path ) ) {
			wp_die( esc_html__( 'Error: The requested file could not be found on the server.', 'sgoplus-file-share' ) );
		}

		// --- PRO Logic Start ---

		// 1. Record Download Log
		$this->record_download_log( $post_id );

		// 2. Handle Email Notifications
		$enable_notifications = get_post_meta( $post_id, '_sgoplus_fs_enable_notifications', true );
		if ( $enable_notifications === 'yes' ) {
			$to = get_option( 'admin_email' );
			$subject = '[SGOplus File Share] New Download: ' . get_the_title( $post_id );
			$user_info = is_user_logged_in() ? wp_get_current_user()->display_name : 'Guest';
			$ip_address = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'Unknown';
			$message = "File: " . get_the_title( $post_id ) . "\nBy: " . $user_info . "\nIP: " . $ip_address . "\nTime: " . current_time( 'mysql' );
			wp_mail( $to, $subject, $message );
		}

		// 3. Increment Download Count
		$count = intval( $current_count ) + 1;
		update_post_meta( $post_id, '_sgoplus_fs_download_count', $count );

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
		$accel_mode = get_option( 'sgoplus_fs_acceleration_mode', 'standard' );
		if ( ! is_sgoplus_fs_pro_active() ) {
			$accel_mode = 'standard';
		}

		// Server Detection for Compatibility
		$server_soft = isset( $_SERVER['SERVER_SOFTWARE'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_SOFTWARE'] ) ) : '';
		$is_apache = ( stripos( $server_soft, 'apache' ) !== false );
		$is_nginx  = ( stripos( $server_soft, 'nginx' ) !== false );
		$is_litespeed = ( stripos( $server_soft, 'litespeed' ) !== false );

		// Auto-fallback if server doesn't match selected mode
		if ( $accel_mode === 'sgoplus_fs_sendfile' && ! $is_apache && ! $is_litespeed ) {
			$accel_mode = 'standard';
		} elseif ( $accel_mode === 'sgoplus_fs_accel' && ! $is_nginx ) {
			$accel_mode = 'standard';
		} elseif ( $accel_mode === 'sgoplus_fs_litespeed' && ! $is_litespeed ) {
			$accel_mode = 'standard';
		}

		header( 'Content-Description: File Transfer' );
		header( 'Content-Type: ' . $mime_type );
		header( 'Content-Disposition: attachment; filename="' . esc_attr( $file_name ) . '"' );
		header( 'Expires: 0' );
		header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
		header( 'Pragma: public' );

		if ( $accel_mode === 'x_sendfile' ) {
			header( 'X-Sendfile: ' . $file_path );
			exit;
		} elseif ( $accel_mode === 'x_accel' || $accel_mode === 'x_litespeed' ) {
			// Correctly determine relative URI without relying on hardcoded ABSPATH physical paths
			$file_uri = str_replace( home_url(), '', $file_url );
			$relative_path = '/' . ltrim( $file_uri, '/' );
			if ( $accel_mode === 'x_litespeed' ) {
				header( 'X-LiteSpeed-Location: ' . $relative_path );
			} else {
				header( 'X-Accel-Redirect: ' . $relative_path );
			}
			exit;
		}

		// Fallback: Standard PHP Streaming
		header( 'Content-Length: ' . $file_size );
		// phpcs:ignore Squiz.PHP.DiscouragedFunctions.Discouraged
		set_time_limit( 0 );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		$handle = fopen( $file_path, 'rb' );
		if ( $handle ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fread
			while ( ! feof( $handle ) ) {
				// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fread, WordPress.Security.EscapeOutput.OutputNotEscaped
				echo fread( $handle, 1024 * 1024 );
				ob_flush();
				flush();
			}
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
			fclose( $handle );
		}
		exit;
	}

	private function record_download_log( $post_id ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'sgoplus_fs_logs';
		
		$user_id = get_current_user_id();
		$user_status = is_user_logged_in() ? 'member' : 'guest';
		$ip_address = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'Unknown';
		$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : 'Unknown';
		
		// Basic Country Detection
		$country = 'Unknown';
		if ( isset( $_SERVER['HTTP_CF_IPCOUNTRY'] ) ) { // Cloudflare
			$country = sanitize_text_field( wp_unslash( $_SERVER['HTTP_CF_IPCOUNTRY'] ) );
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->insert(
			$table_name,
			array(
				'file_id'     => $post_id,
				'user_id'     => $user_id,
				'user_status' => $user_status,
				'ip_address'  => $ip_address,
				'country'     => $country,
				'user_agent'  => $user_agent,
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
			return wp_normalize_path( str_replace( $norm_base_url, $base_path, $norm_url ) );
		}
		$attachment_id = attachment_url_to_postid( $url );
		if ( $attachment_id ) {
			return get_attached_file( $attachment_id );
		}
		$content_url = content_url();
		$norm_content_url = str_replace( array( 'http://', 'https://' ), '//', $content_url );
		if ( strpos( $norm_url, $norm_content_url ) === 0 ) {
			$rel_path = str_replace( $norm_content_url, '', $norm_url );
			// Use WP_CONTENT_DIR for dynamic content location handling, but normalize for consistency
			return wp_normalize_path( WP_CONTENT_DIR . $rel_path );
		}
		return false;
	}
}
