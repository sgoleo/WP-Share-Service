<?php

namespace SGOplus\File_Share;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CPT {

	public function register_post_type() {
		$labels = array(
			'name'               => esc_html__( 'Shared Files', 'sgoplus-file-share' ),
			'singular_name'      => esc_html__( 'Shared File', 'sgoplus-file-share' ),
			'menu_name'          => esc_html__( 'Share Service+', 'sgoplus-file-share' ),
			'all_items'          => esc_html__( 'Shared Files', 'sgoplus-file-share' ),
			'add_new'            => esc_html__( 'Add New', 'sgoplus-file-share' ),
			'add_new_item'       => esc_html__( 'Add New Shared File', 'sgoplus-file-share' ),
			'edit_item'          => esc_html__( 'Edit Shared File', 'sgoplus-file-share' ),
			'new_item'           => esc_html__( 'New Shared File', 'sgoplus-file-share' ),
			'view_item'          => esc_html__( 'View Shared File', 'sgoplus-file-share' ),
			'search_items'       => esc_html__( 'Search Shared Files', 'sgoplus-file-share' ),
			'not_found'          => esc_html__( 'No files found', 'sgoplus-file-share' ),
			'not_found_in_trash' => esc_html__( 'No files found in trash', 'sgoplus-file-share' ),
		);

		$args = array(
			'labels'              => $labels,
			'public'              => true,
			'has_archive'         => true,
			'publicly_queryable'  => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'query_var'           => true,
			'rewrite'             => array( 'slug' => 'shared-file' ),
			'capability_type'     => 'post',
			'hierarchical'        => false,
			'supports'            => array( 'title', 'editor', 'thumbnail' ),
			'menu_icon'           => 'dashicons-share-alt2',
		);

		register_post_type( 'sgoplus_fs_file', $args );
		
		$this->register_taxonomy();

		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post', array( $this, 'save_meta_data' ) );

		// Add ID and Downloads columns to list view
		add_filter( 'manage_sgoplus_fs_file_posts_columns', array( $this, 'add_id_column' ) );
		add_action( 'manage_sgoplus_fs_file_posts_custom_column', array( $this, 'display_id_column' ), 10, 2 );
	}

	public function register_taxonomy() {
		$labels = array(
			'name'              => esc_html__( 'File Categories', 'sgoplus-file-share' ),
			'singular_name'     => esc_html__( 'File Category', 'sgoplus-file-share' ),
			'search_items'      => esc_html__( 'Search Categories', 'sgoplus-file-share' ),
			'all_items'         => esc_html__( 'All Categories', 'sgoplus-file-share' ),
			'parent_item'       => esc_html__( 'Parent Category', 'sgoplus-file-share' ),
			'parent_item_colon' => esc_html__( 'Parent Category:', 'sgoplus-file-share' ),
			'edit_item'         => esc_html__( 'Edit Category', 'sgoplus-file-share' ),
			'update_item'       => esc_html__( 'Update Category', 'sgoplus-file-share' ),
			'add_new_item'      => esc_html__( 'Add New Category', 'sgoplus-file-share' ),
			'new_item_name'     => esc_html__( 'New Category Name', 'sgoplus-file-share' ),
			'menu_name'         => esc_html__( 'Categories', 'sgoplus-file-share' ),
		);

		$args = array(
			'hierarchical'      => true,
			'labels'            => $labels,
			'show_ui'           => true,
			'show_admin_column' => true,
			'query_var'         => true,
			'rewrite'           => array( 'slug' => 'file-category' ),
		);

		register_taxonomy( 'sgoplus_fs_category', array( 'sgoplus_fs_file' ), $args );
	}

	public function add_id_column( $columns ) {
		$new_columns = array();
		foreach ( $columns as $key => $value ) {
			if ( $key === 'title' ) {
				$new_columns['sgoplus_fs_id'] = esc_html__( 'ID / Shortcode', 'sgoplus-file-share' );
			}
			// Place Downloads before the last column (usually date)
			if ( $key === 'date' ) {
				$new_columns['sgoplus_fs_downloads'] = esc_html__( 'Downloads', 'sgoplus-file-share' );
			}
			$new_columns[ $key ] = $value;
		}
		return $new_columns;
	}

	public function display_id_column( $column, $post_id ) {
		if ( $column === 'sgoplus_fs_id' ) {
			echo '<code>' . intval( $post_id ) . '</code><br>';
			echo '<small><code>[sgoplus_file id="' . intval( $post_id ) . '"]</code></small>';
		}
		if ( $column === 'sgoplus_fs_downloads' ) {
			$count = get_post_meta( $post_id, '_sgoplus_fs_download_count', true );
			echo '<strong>' . ( $count ? intval( $count ) : 0 ) . '</strong>';
		}
	}

	public function add_meta_boxes() {
		add_meta_box(
			'sgoplus_fs_file_details',
			esc_html__( 'Share Service Details', 'sgoplus-file-share' ),
			array( $this, 'render_meta_box' ),
			'sgoplus_fs_file',
			'normal',
			'high'
		);
	}

	public function render_meta_box( $post ) {
		// Use wp_nonce_field for security
		wp_nonce_field( 'sgoplus_fs_save_meta', 'sgoplus_fs_nonce' );

		$file_url = get_post_meta( $post->ID, '_sgoplus_fs_file_url', true );
		$update_log = get_post_meta( $post->ID, '_sgoplus_fs_update_log', true );
		$has_password = get_post_meta( $post->ID, '_sgoplus_fs_password', true ) ? ' (' . esc_html__( 'Password set', 'sgoplus-file-share' ) . ')' : ' (' . esc_html__( 'Public', 'sgoplus-file-share' ) . ')';
		$download_count = get_post_meta( $post->ID, '_sgoplus_fs_download_count', true ) ?: 0;
		$download_limit = get_post_meta( $post->ID, '_sgoplus_fs_download_limit', true );
		$expiry_date = get_post_meta( $post->ID, '_sgoplus_fs_expiry_date', true );
		$allowed_roles = get_post_meta( $post->ID, '_sgoplus_fs_allowed_roles', true ) ?: array();
		$enable_notifications = get_post_meta( $post->ID, '_sgoplus_fs_enable_notifications', true );
		
		$is_pro = sgoplus_fs_is_pro_active();

		echo '<div style="background: #e7f7ff; padding: 15px; border: 1px solid #bce8f1; border-radius: 8px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">';
		echo '<div><strong>' . esc_html__( 'Shortcode:', 'sgoplus-file-share' ) . '</strong><br><code>[sgoplus_file id="' . intval( $post->ID ) . '"]</code></div>';
		echo '<div style="text-align: right;"><span style="font-size: 0.9em; color: #666;">' . esc_html__( 'Total Downloads', 'sgoplus-file-share' ) . '</span><br><strong style="font-size: 1.5em; color: #0073aa;">' . intval( $download_count ) . '</strong></div>';
		echo '</div>';

		echo '<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">';
			echo '<div><label><strong>' . esc_html__( 'File Upload / Selection:', 'sgoplus-file-share' ) . '</strong></label><br>';
			echo '<div id="sgoplus-fs-file-selector-container" style="margin-top: 10px;">';
				$attachment_id = get_post_meta( $post->ID, '_sgoplus_fs_attachment_id', true );
				$filename = $attachment_id ? basename( get_attached_file( $attachment_id ) ) : '';
				
				echo '<div id="sgoplus-fs-selected-file-info" style="' . ( $file_url ? 'display: block;' : 'display: none;' ) . ' margin-bottom: 10px; padding: 10px; background: #f9f9f9; border: 1px dashed #ccc; border-radius: 4px;">';
					echo '<span class="dashicons dashicons-media-default" style="vertical-align: middle; margin-right: 5px;"></span>';
					echo '<span id="sgoplus-fs-filename" style="font-weight: 600;">' . esc_html( $filename ?: basename( $file_url ) ) . '</span>';
					echo ' <a href="#" id="sgoplus_fs_clear_btn" style="color: #d63031; text-decoration: none; margin-left: 10px; font-size: 0.9em;">[' . esc_html__( 'Clear', 'sgoplus-file-share' ) . ']</a>';
				echo '</div>';

				echo '<input type="hidden" id="sgoplus_fs_file_url" name="sgoplus_fs_file_url" value="' . esc_attr( $file_url ) . '" />';
				echo '<input type="hidden" id="sgoplus_fs_attachment_id" name="sgoplus_fs_attachment_id" value="' . esc_attr( $attachment_id ) . '" />';
				echo '<button type="button" class="button button-secondary" id="sgoplus_fs_upload_btn">' . ( $file_url ? esc_html__( 'Change File', 'sgoplus-file-share' ) : esc_html__( 'Select or Upload File', 'sgoplus-file-share' ) ) . '</button>';
				echo '<p class="description">' . esc_html__( 'Choose a file from the Media Library or upload a new one.', 'sgoplus-file-share' ) . '</p>';
			echo '</div></div>';
			
			echo '<div><label><strong>' . esc_html__( 'Password Protection:', 'sgoplus-file-share' ) . '</strong>' . esc_html( $has_password ) . '</label><br>';
			echo '<div style="display:flex; gap:10px; align-items:center; margin-top: 10px;">';
			echo '<input type="password" name="sgoplus_fs_password" value="" placeholder="' . esc_attr__( 'Set new password', 'sgoplus-file-share' ) . '" style="flex:1; height: 30px;" />';
			if ( get_post_meta( $post->ID, '_sgoplus_fs_password', true ) ) {
				echo '<label style="color:#d63031; font-weight:600; cursor:pointer;"><input type="checkbox" name="sgoplus_fs_clear_password" value="1" /> ' . esc_html__( 'Clear', 'sgoplus-file-share' ) . '</label>';
			}
			echo '</div></div>';
		echo '</div>';

		// PRO Advanced Controls Section (Compact & Blurred for Promotion)
		echo '<div style="background: #fcfcfc; padding: 15px; border: 1px solid #eee; border-radius: 10px; margin-bottom: 20px; position: relative; overflow: hidden;">';
			echo '<div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255,255,255,0.7); z-index: 10; display: flex; flex-direction: column; align-items: center; justify-content: center; backdrop-filter: blur(4px);">';
				echo '<p style="margin: 0 0 10px 0; font-weight: 700; color: #6c5ce7; display: flex; align-items: center; gap: 8px;"><span class="dashicons dashicons-lock"></span> ' . esc_html__( 'Advanced Controls (PRO)', 'sgoplus-file-share' ) . '</p>';
				echo '<a href="https://sgoplus.one/siteservices/wp-file-share/" target="_blank" class="button button-primary" style="background: #6c5ce7; border: none; font-size: 0.85em;">' . esc_html__( 'Upgrade to Unlock', 'sgoplus-file-share' ) . '</a>';
			echo '</div>';

			echo '<h4 style="margin: 0 0 10px 0; color: #999; font-size: 0.9em; display: flex; align-items: center; gap: 8px;"><span class="dashicons dashicons-star-filled"></span> ' . esc_html__( 'PRO Features Preview', 'sgoplus-file-share' ) . '</h4>';
			echo '<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; opacity: 0.3; pointer-events: none;">';
				echo '<div><small><strong>' . esc_html__( 'Download Limit', 'sgoplus-file-share' ) . '</strong></small><br><input type="number" disabled style="width:100%; height: 24px;"></div>';
				echo '<div><small><strong>' . esc_html__( 'Expiration Date', 'sgoplus-file-share' ) . '</strong></small><br><input type="date" disabled style="width:100%; height: 24px;"></div>';
				echo '<div><small><strong>' . esc_html__( 'Role Restriction', 'sgoplus-file-share' ) . '</strong></small><br><div style="border: 1px solid #ddd; height: 40px; background: #fff;"></div></div>';
				echo '<div><small><strong>' . esc_html__( 'Notifications', 'sgoplus-file-share' ) . '</strong></small><br><label><input type="checkbox" disabled> Email on Download</label></div>';
			echo '</div>';
		echo '</div>';

		echo '<p><label><strong>' . esc_html__( 'Update Log:', 'sgoplus-file-share' ) . '</strong></label><br>';
		echo '<textarea name="sgoplus_fs_update_log" style="width:100%; height:100px;">' . esc_textarea( $update_log ) . '</textarea></p>';
	}

	public function save_meta_data( $post_id ) {
		// Security checks
		if ( ! isset( $_POST['sgoplus_fs_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['sgoplus_fs_nonce'] ) ), 'sgoplus_fs_save_meta' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Save File URL
		if ( isset( $_POST['sgoplus_fs_file_url'] ) ) {
			update_post_meta( $post_id, '_sgoplus_fs_file_url', esc_url_raw( wp_unslash( $_POST['sgoplus_fs_file_url'] ) ) );
		}

		// Save Attachment ID
		if ( isset( $_POST['sgoplus_fs_attachment_id'] ) ) {
			update_post_meta( $post_id, '_sgoplus_fs_attachment_id', sanitize_text_field( wp_unslash( $_POST['sgoplus_fs_attachment_id'] ) ) );
		}

		// Save Update Log
		if ( isset( $_POST['sgoplus_fs_update_log'] ) ) {
			update_post_meta( $post_id, '_sgoplus_fs_update_log', sanitize_textarea_field( wp_unslash( $_POST['sgoplus_fs_update_log'] ) ) );
		}

		// Save Password (Hashed)
		if ( isset( $_POST['sgoplus_fs_clear_password'] ) && '1' === $_POST['sgoplus_fs_clear_password'] ) {
			delete_post_meta( $post_id, '_sgoplus_fs_password' );
		} elseif ( ! empty( $_POST['sgoplus_fs_password'] ) ) {
			$raw_password = sanitize_text_field( wp_unslash( $_POST['sgoplus_fs_password'] ) );
			$hashed_password = wp_hash_password( $raw_password );
			update_post_meta( $post_id, '_sgoplus_fs_password', $hashed_password );
		}

	}
}
