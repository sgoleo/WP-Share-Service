<?php

class SFS_CPT {

	public function register_post_type() {
		$labels = array(
			'name'               => 'Secure Files',
			'singular_name'      => 'Secure File',
			'menu_name'          => 'Secure Files',
			'add_new'            => 'Add New',
			'add_new_item'       => 'Add New Secure File',
			'edit_item'          => 'Edit Secure File',
			'new_item'           => 'New Secure File',
			'view_item'          => 'View Secure File',
			'search_items'       => 'Search Secure Files',
			'not_found'          => 'No files found',
			'not_found_in_trash' => 'No files found in trash',
		);

		$args = array(
			'labels'              => $labels,
			'public'              => true,
			'has_archive'         => true,
			'publicly_queryable'  => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'query_var'           => true,
			'rewrite'             => array( 'slug' => 'secure-file' ),
			'capability_type'     => 'post',
			'hierarchical'        => false,
			'supports'            => array( 'title', 'editor', 'thumbnail' ),
			'menu_icon'           => 'dashicons-shield-lock',
		);

		register_post_type( 'sfs_file', $args );
		
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post', array( $this, 'save_meta_data' ) );
	}

	public function add_meta_boxes() {
		add_meta_box(
			'sfs_file_details',
			'File Sharing Details',
			array( $this, 'render_meta_box' ),
			'sfs_file',
			'normal',
			'high'
		);
	}

	public function render_meta_box( $post ) {
		// Use wp_nonce_field for security
		wp_nonce_field( 'sfs_save_meta', 'sfs_nonce' );

		$file_url = get_post_meta( $post->ID, '_sfs_file_url', true );
		$update_log = get_post_meta( $post->ID, '_sfs_update_log', true );
		$has_password = get_post_meta( $post->ID, '_sfs_password', true ) ? ' (Password set)' : ' (Public)';

		echo '<p><label><strong>File Upload:</strong></label><br>';
		echo '<input type="text" id="sfs_file_url" name="sfs_file_url" value="' . esc_attr( $file_url ) . '" style="width:80%;" /> ';
		echo '<button type="button" class="button" id="sfs_upload_btn">Select File</button></p>';
		
		echo '<p><label><strong>Password:</strong>' . esc_html( $has_password ) . '</label><br>';
		echo '<input type="password" name="sfs_password" value="" placeholder="Leave blank to keep current or make public" style="width:100%;" /></p>';

		echo '<p><label><strong>Update Log:</strong></label><br>';
		echo '<textarea name="sfs_update_log" style="width:100%; height:100px;">' . esc_textarea( $update_log ) . '</textarea></p>';

		// Simple Script for Media Uploader
		?>
		<script>
		jQuery(document).ready(function($){
			$('#sfs_upload_btn').click(function(e) {
				e.preventDefault();
				var image = wp.media({ 
					title: 'Upload File',
					multiple: false
				}).open()
				.on('select', function(e){
					var uploaded_image = image.state().get('selection').first();
					var file_url = uploaded_image.toJSON().url;
					$('#sfs_file_url').val(file_url);
				});
			});
		});
		</script>
		<?php
	}

	public function save_meta_data( $post_id ) {
		// Security checks
		if ( ! isset( $_POST['sfs_nonce'] ) || ! wp_verify_nonce( $_POST['sfs_nonce'], 'sfs_save_meta' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Save File URL
		if ( isset( $_POST['sfs_file_url'] ) ) {
			update_post_meta( $post_id, '_sfs_file_url', sanitize_text_field( $_POST['sfs_file_url'] ) );
		}

		// Save Update Log
		if ( isset( $_POST['sfs_update_log'] ) ) {
			update_post_meta( $post_id, '_sfs_update_log', sanitize_textarea_field( $_POST['sfs_update_log'] ) );
		}

		// Save Password (Hashed)
		if ( ! empty( $_POST['sfs_password'] ) ) {
			$hashed_password = wp_hash_password( $_POST['sfs_password'] );
			update_post_meta( $post_id, '_sfs_password', $hashed_password );
		} elseif ( isset( $_POST['sfs_password'] ) && $_POST['sfs_password'] === '' ) {
			// If explicitly cleared (optional logic, usually we want a way to make it public)
			// For simplicity, if empty and field exists, we might want to keep old one unless a "make public" checkbox exists.
			// Let's assume blank means "no change" unless it's a new post.
		}
	}
}
