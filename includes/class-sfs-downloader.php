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

		// Convert URL to Path
		$file_path = $this->url_to_path( $file_url );
		
		if ( ! $file_path || ! file_exists( $file_path ) ) {
			error_log( "SFS Error: File path not found or inaccessible.\nURL: $file_url\nResolved Path: $file_path" );
			wp_die( 'Error: The requested file could not be found on the server. Please check the error log for details.' );
		}

		// Increment Download Count
		$count = get_post_meta( $post_id, '_sfs_download_count', true );
		$count = $count ? intval( $count ) + 1 : 1;
		update_post_meta( $post_id, '_sfs_download_count', $count );

		// Clean output buffer to prevent corrupt downloads
		while ( ob_get_level() > 0 ) {
			ob_end_clean();
		}

		// Serve File
		$file_name = basename( $file_path );
		$file_size = filesize( $file_path );
		$mime_type = wp_check_filetype( $file_name )['type'] ?: 'application/octet-stream';

		header( 'Content-Description: File Transfer' );
		header( 'Content-Type: ' . $mime_type );
		header( 'Content-Disposition: attachment; filename="' . $file_name . '"' );
		header( 'Expires: 0' );
		header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
		header( 'Pragma: public' );
		header( 'Content-Length: ' . $file_size );

		// Final check to ensure no extra output
		if ( ob_get_level() ) ob_end_clean();
		
		// Speed & Stability Optimization
		set_time_limit(0); // Prevent timeout
		$handle = fopen( $file_path, 'rb' );
		if ( $handle ) {
			while ( ! feof( $handle ) ) {
				echo fread( $handle, 1024 * 1024 ); // 1MB chunks
				ob_flush();
				flush();
			}
			fclose( $handle );
		}
		exit;
	}

	/**
	 * Robustly convert a WordPress URL to a server path
	 */
	private function url_to_path( $url ) {
		$upload_dir = wp_upload_dir();
		
		// 1. Try simple string replacement (handles most cases)
		$base_url = $upload_dir['baseurl'];
		$base_path = $upload_dir['basedir'];

		// Normalize protocols for comparison
		$norm_url = str_replace( array( 'http://', 'https://' ), '//', $url );
		$norm_base_url = str_replace( array( 'http://', 'https://' ), '//', $base_url );

		if ( strpos( $norm_url, $norm_base_url ) === 0 ) {
			return str_replace( $norm_base_url, $base_path, $norm_url );
		}

		// 2. Try to resolve via attachment ID if it's in the media library
		$attachment_id = attachment_url_to_postid( $url );
		if ( $attachment_id ) {
			return get_attached_file( $attachment_id );
		}

		// 3. Last resort: try to match relative path from wp-content
		$content_url = content_url();
		$norm_content_url = str_replace( array( 'http://', 'https://' ), '//', $content_url );
		if ( strpos( $norm_url, $norm_content_url ) === 0 ) {
			$rel_path = str_replace( $norm_content_url, '', $norm_url );
			return WP_CONTENT_DIR . $rel_path;
		}

		return false;
	}
}
