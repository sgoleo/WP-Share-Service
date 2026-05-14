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


		// Verify Password
		$hashed_password = get_post_meta( $post_id, '_sgoplus_fs_password', true );
		if ( ! empty( $hashed_password ) ) {
			$submitted_password = isset( $_POST['sgoplus_fs_password'] ) ? sanitize_text_field( wp_unslash( $_POST['sgoplus_fs_password'] ) ) : '';
			if ( ! wp_check_password( $submitted_password, $hashed_password ) ) {
				wp_die( esc_html__( 'Incorrect password. Please try again.', 'sgoplus-file-share' ) );
			}
		}

		// Get File Info
		$attachment_id = get_post_meta( $post_id, '_sgoplus_fs_attachment_id', true );
		$file_url = get_post_meta( $post_id, '_sgoplus_fs_file_url', true );
		
		if ( ! $file_url && ! $attachment_id ) {
			wp_die( esc_html__( 'No file associated with this record.', 'sgoplus-file-share' ) );
		}

		$file_path = $this->url_to_path( $file_url, $attachment_id );
		if ( ! $file_path || ! file_exists( $file_path ) ) {
			wp_die( esc_html__( 'Error: The requested file could not be found on the server.', 'sgoplus-file-share' ) );
		}

		// Increment Download Count
		$current_count = get_post_meta( $post_id, '_sgoplus_fs_download_count', true ) ?: 0;
		$count = intval( $current_count ) + 1;
		update_post_meta( $post_id, '_sgoplus_fs_download_count', $count );

		// Clean output buffer
		while ( ob_get_level() > 0 ) {
			ob_end_clean();
		}

		// Serve File
		$file_name = basename( $file_path );
		$file_size = filesize( $file_path );
		$mime_type = wp_check_filetype( $file_name )['type'] ?: 'application/octet-stream';

		// Acceleration Optimization
		$accel_mode = get_option( 'sgoplus_fs_acceleration_mode', 'standard' );

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

		if ( $accel_mode === 'sgoplus_fs_sendfile' ) {
			header( 'X-Sendfile: ' . $file_path );
			exit;
		} elseif ( $accel_mode === 'sgoplus_fs_accel' || $accel_mode === 'sgoplus_fs_litespeed' ) {
			// Correctly determine relative URI without relying on hardcoded ABSPATH physical paths
			$file_uri = str_replace( home_url(), '', $file_url );
			$relative_path = '/' . ltrim( $file_uri, '/' );
			if ( $accel_mode === 'sgoplus_fs_litespeed' ) {
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


	private function url_to_path( $url, $attachment_id = 0 ) {
		// Priority 1: Use Attachment ID (Most stable and fastest)
		if ( ! empty( $attachment_id ) ) {
			$path = get_attached_file( $attachment_id );
			if ( $path && file_exists( $path ) ) {
				return $path;
			}
		}

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
