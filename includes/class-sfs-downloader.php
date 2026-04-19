<?php

class SFS_Downloader {

	public function __construct() {
		add_action( 'init', array( $this, 'handle_download_request' ) );
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
		if ( $hashed_password ) {
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
			error_log( 'SFS Error: File path not found or inaccessible: ' . $file_path );
			wp_die( 'Error: The requested file could not be found on the server.' );
		}

		// Log download (optional but good practice)
		// error_log( 'SFS: File downloaded - ' . $file_path );

		// Clean output buffer to prevent corrupt downloads
		if ( ob_get_level() ) {
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

		// Clear buffer again just in case
		flush();

		// Use readfile for streaming
		readfile( $file_path );
		exit;
	}

	/**
	 * Helper to convert a WordPress URL to a server path
	 */
	private function url_to_path( $url ) {
		$upload_dir = wp_upload_dir();
		$base_url = $upload_dir['baseurl'];
		$base_path = $upload_dir['basedir'];

		if ( strpos( $url, $base_url ) === 0 ) {
			return str_replace( $base_url, $base_path, $url );
		}

		// Fallback for paths outside uploads (not recommended for this plugin)
		return false;
	}
}
