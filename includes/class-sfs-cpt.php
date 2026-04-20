<?php

class SFS_CPT {

	public function register_post_type() {
		$labels = array(
			'name'               => 'Shared Files',
			'singular_name'      => 'Shared File',
			'menu_name'          => 'Share Service+',
			'all_items'          => 'Shared Files',
			'add_new'            => 'Add New',
			'add_new_item'       => 'Add New Shared File',
			'edit_item'          => 'Edit Shared File',
			'new_item'           => 'New Shared File',
			'view_item'          => 'View Shared File',
			'search_items'       => 'Search Shared Files',
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
			'rewrite'             => array( 'slug' => 'shared-file' ),
			'capability_type'     => 'post',
			'hierarchical'        => false,
			'supports'            => array( 'title', 'editor', 'thumbnail' ),
			'menu_icon'           => 'dashicons-share-alt2',
		);

		register_post_type( 'sfs_file', $args );
		
		$this->register_taxonomy();

		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post', array( $this, 'save_meta_data' ) );

		// Add ID and Downloads columns to list view
		add_filter( 'manage_sfs_file_posts_columns', array( $this, 'add_id_column' ) );
		add_action( 'manage_sfs_file_posts_custom_column', array( $this, 'display_id_column' ), 10, 2 );
	}

	public function register_taxonomy() {
		$labels = array(
			'name'              => 'File Categories',
			'singular_name'     => 'File Category',
			'search_items'      => 'Search Categories',
			'all_items'         => 'All Categories',
			'parent_item'       => 'Parent Category',
			'parent_item_colon' => 'Parent Category:',
			'edit_item'         => 'Edit Category',
			'update_item'       => 'Update Category',
			'add_new_item'      => 'Add New Category',
			'new_item_name'     => 'New Category Name',
			'menu_name'         => 'Categories',
		);

		$args = array(
			'hierarchical'      => true,
			'labels'            => $labels,
			'show_ui'           => true,
			'show_admin_column' => true,
			'query_var'         => true,
			'rewrite'           => array( 'slug' => 'file-category' ),
		);

		register_taxonomy( 'sfs_category', array( 'sfs_file' ), $args );
	}

	public function add_id_column( $columns ) {
		$new_columns = array();
		foreach ( $columns as $key => $value ) {
			if ( $key === 'title' ) {
				$new_columns['sfs_id'] = 'ID / Shortcode';
			}
			// Place Downloads before the last column (usually date)
			if ( $key === 'date' ) {
				$new_columns['sfs_downloads'] = 'Downloads';
			}
			$new_columns[ $key ] = $value;
		}
		return $new_columns;
	}

	public function display_id_column( $column, $post_id ) {
		if ( $column === 'sfs_id' ) {
			echo '<code>' . intval( $post_id ) . '</code><br>';
			echo '<small><code>[sgo_file_share id="' . intval( $post_id ) . '"]</code></small>';
		}
		if ( $column === 'sfs_downloads' ) {
			$count = get_post_meta( $post_id, '_sfs_download_count', true );
			echo '<strong>' . ( $count ? intval( $count ) : 0 ) . '</strong>';
		}
	}

	public function add_meta_boxes() {
		add_meta_box(
			'sfs_file_details',
			'Share Service Details',
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
		$download_count = get_post_meta( $post->ID, '_sfs_download_count', true ) ?: 0;
		$download_limit = get_post_meta( $post->ID, '_sfs_download_limit', true );
		$expiry_date = get_post_meta( $post->ID, '_sfs_expiry_date', true );
		$allowed_roles = get_post_meta( $post->ID, '_sfs_allowed_roles', true ) ?: array();
		$enable_notifications = get_post_meta( $post->ID, '_sfs_enable_notifications', true );
		
		$is_pro = is_sfs_pro_active();

		echo '<div style="background: #e7f7ff; padding: 15px; border: 1px solid #bce8f1; border-radius: 8px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">';
		echo '<div><strong>Shortcode:</strong><br><code>[sgo_file_share id="' . intval( $post->ID ) . '"]</code></div>';
		echo '<div style="text-align: right;"><span style="font-size: 0.9em; color: #666;">Total Downloads</span><br><strong style="font-size: 1.5em; color: #0073aa;">' . intval( $download_count ) . '</strong></div>';
		echo '</div>';

		echo '<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">';
			echo '<div><label><strong>File Upload:</strong></label><br>';
			echo '<input type="text" id="sfs_file_url" name="sfs_file_url" value="' . esc_attr( $file_url ) . '" style="width:70%;" /> ';
			echo '<button type="button" class="button" id="sfs_upload_btn">Select</button></div>';
			
			echo '<div><label><strong>Password:</strong>' . esc_html( $has_password ) . '</label><br>';
			echo '<input type="password" name="sfs_password" value="" placeholder="Leave blank to keep" style="width:100%;" /></div>';
		echo '</div>';

		// PRO Advanced Controls Section
		echo '<div style="background: #fcfcfc; padding: 20px; border: 1px solid #eee; border-radius: 12px; margin-bottom: 20px; position: relative;">';
			
			if ( ! $is_pro ) {
				echo '<div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255,255,255,0.7); z-index: 10; border-radius: 12px; display: flex; align-items: center; justify-content: center; backdrop-filter: blur(2px);">';
				echo '<div style="background: #fff; padding: 10px 20px; border-radius: 8px; border: 1px solid #e5e5e5; box-shadow: 0 4px 15px rgba(0,0,0,0.05); text-align: center;">';
				echo '<p style="margin: 0; font-weight: 700; color: #6c5ce7; display: flex; align-items: center; gap: 8px;"><span class="dashicons dashicons-lock"></span> PRO License Required</p>';
				echo '</div></div>';
			}

			echo '<h4 style="margin: 0 0 15px 0; border-bottom: 1px solid #eee; padding-bottom: 10px; color: #6c5ce7; display: flex; align-items: center; gap: 10px;"><span class="dashicons dashicons-star-filled"></span> PRO: Advanced Controls</h4>';
			
			echo '<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 25px; margin-bottom: 20px;">';
				echo '<div><label><strong>Download Limit (Max):</strong></label><br>';
				echo '<input type="number" name="sfs_download_limit" value="' . esc_attr( $download_limit ) . '" placeholder="0 = Unlimited" style="width:100%;" ' . ( ! $is_pro ? 'disabled' : '' ) . ' /></div>';
				
				echo '<div><label><strong>Expiration Date:</strong></label><br>';
				echo '<input type="date" name="sfs_expiry_date" value="' . esc_attr( $expiry_date ) . '" style="width:100%;" ' . ( ! $is_pro ? 'disabled' : '' ) . ' /></div>';
			echo '</div>';

			echo '<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 25px;">';
				echo '<div><label><strong>Allowed User Roles:</strong> (Multiple)</label><br>';
				echo '<div style="background: #fff; border: 1px solid #ddd; padding: 10px; border-radius: 5px; max-height: 120px; overflow-y: auto;">';
				global $wp_roles;
				foreach ( $wp_roles->role_names as $role_slug => $role_name ) {
					$checked = in_array( $role_slug, $allowed_roles ) ? 'checked' : '';
					$disabled = ! $is_pro ? 'disabled' : '';
					echo '<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="sfs_allowed_roles[]" value="' . esc_attr( $role_slug ) . '" ' . $checked . ' ' . $disabled . '> ' . esc_html( $role_name ) . '</label>';
				}
				echo '</div><p style="font-size: 0.85em; color: #666;">If none selected, role restriction is disabled.</p></div>';
				
				echo '<div><label><strong>Notifications:</strong></label><br>';
				echo '<label style="display: block; margin-top: 10px;"><input type="checkbox" name="sfs_enable_notifications" value="yes" ' . checked( $enable_notifications, 'yes', false ) . ' ' . ( ! $is_pro ? 'disabled' : '' ) . '> Send email to Admin on each download</label></div>';
			echo '</div>';
		echo '</div>';

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
		}

		// Only save PRO meta if license is active
		if ( is_sfs_pro_active() ) {
			// PRO: Download Limit
			if ( isset( $_POST['sfs_download_limit'] ) ) {
				update_post_meta( $post_id, '_sfs_download_limit', intval( $_POST['sfs_download_limit'] ) );
			}

			// PRO: Expiry Date
			if ( isset( $_POST['sfs_expiry_date'] ) ) {
				update_post_meta( $post_id, '_sfs_expiry_date', sanitize_text_field( $_POST['sfs_expiry_date'] ) );
			}

			// PRO: Allowed Roles
			$roles = isset( $_POST['sfs_allowed_roles'] ) ? array_map( 'sanitize_text_field', $_POST['sfs_allowed_roles'] ) : array();
			update_post_meta( $post_id, '_sfs_allowed_roles', $roles );

			// PRO: Notifications
			$notify = isset( $_POST['sfs_enable_notifications'] ) ? 'yes' : 'no';
			update_post_meta( $post_id, '_sfs_enable_notifications', $notify );
		}
	}
}
