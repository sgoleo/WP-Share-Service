<?php

class SFS_Settings {

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	public function add_settings_page() {
		// Main Settings
		add_submenu_page(
			'edit.php?post_type=sfs_file',
			'Settings',
			'Settings',
			'manage_options',
			'sfs-settings',
			array( $this, 'render_settings_page' )
		);

		// PRO Log Submenu
		add_submenu_page(
			'edit.php?post_type=sfs_file',
			'PRO Log',
			'PRO Log',
			'manage_options',
			'sfs-pro-log',
			array( $this, 'render_pro_log_page' )
		);
	}

	public function register_settings() {
		register_setting( 'sfs_settings_group', 'sfs_acceleration_mode' );
	}

	public function render_settings_page() {
		?>
		<div class="wrap">
			<h1>WP Share Service Settings</h1>
			<form method="post" action="options.php">
				<?php
				settings_fields( 'sfs_settings_group' );
				do_settings_sections( 'sfs_settings_group' );
				?>
				
				<div class="card" style="max-width: 800px; margin-top: 20px; padding: 20px; border-radius: 10px;">
					<h2 style="margin-top: 0;">Performance Optimization</h2>
					<p>Choose the specialized accelerator for your server environment to optimize large file downloads.</p>
					
					<table class="form-table">
						<tr>
							<th scope="row">Acceleration Mode</th>
							<td>
								<?php $mode = get_option( 'sfs_acceleration_mode', 'standard' ); ?>
								<select name="sfs_acceleration_mode" style="width: 300px;">
									<option value="standard" <?php selected( $mode, 'standard' ); ?>>Standard (PHP Chunked - Universal)</option>
									<option value="x_sendfile" <?php selected( $mode, 'x_sendfile' ); ?>>X-Sendfile (Apache / LiteSpeed)</option>
									<option value="x_accel" <?php selected( $mode, 'x_accel' ); ?>>X-Accel-Redirect (Nginx)</option>
								</select>
								<p class="description"><strong>Note:</strong> Specialized modes require server-side modules (mod_xsendfile for Apache). Use "Standard" if unsure.</p>
							</td>
						</tr>
					</table>
				</div>

				<div class="card" style="max-width: 800px; margin-top: 20px; padding: 20px; border-radius: 10px; border-left: 4px solid #6c5ce7; background: #f8f7ff;">
					<h2 style="color: #6c5ce7; margin-top: 0;">Get PRO</h2>
					<p>Buy SGOplus SiteService or Join Insider Program to Unlock PRO version for extra functions.</p>
					<a href="https://sgoplus.one/wp-share-service/" target="_blank" class="button button-primary" style="background: #6c5ce7; border-color: #6c5ce7; padding: 5px 20px; height: auto;">Unlock PRO Functions</a>
				</div>

				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	public function render_pro_log_page() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'sfs_logs';
		
		// Handle Clear Logs
		if ( isset( $_POST['sfs_clear_logs'] ) && check_admin_referer( 'sfs_clear_logs_nonce' ) ) {
			$wpdb->query( "TRUNCATE TABLE $table_name" );
			echo '<div class="updated"><p>Logs cleared successfully.</p></div>';
		}

		$pagenum = isset( $_GET['paged'] ) ? max( 1, intval( $_GET['paged'] ) ) : 1;
		$per_page = 20;
		$offset = ( $pagenum - 1 ) * $per_page;

		$total_logs = $wpdb->get_var( "SELECT COUNT(id) FROM $table_name" );
		$logs = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table_name ORDER BY timestamp DESC LIMIT %d, %d", $offset, $per_page ) );
		
		$num_pages = ceil( $total_logs / $per_page );
		?>
		<div class="wrap">
			<h1 style="display: flex; align-items: center; gap: 10px;">
				<span class="dashicons dashicons-list-view" style="font-size: 1.2em; width: auto; height: auto;"></span> 
				PRO Download Logs
			</h1>
			<p>Track every download event with detailed user information.</p>

			<div style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: flex-end;">
				<form method="post" onsubmit="return confirm('Are you sure you want to clear all logs?');">
					<?php wp_nonce_field( 'sfs_clear_logs_nonce' ); ?>
					<input type="submit" name="sfs_clear_logs" class="button button-secondary" value="Clear All Logs" />
				</form>
				
				<?php if ( $num_pages > 1 ) : ?>
					<div class="tablenav-pages">
						<span class="displaying-num"><?php echo intval( $total_logs ); ?> items</span>
						<?php
						echo paginate_links( array(
							'base'      => add_query_arg( 'paged', '%#%' ),
							'format'    => '',
							'prev_text' => __( '&laquo;' ),
							'next_text' => __( '&raquo;' ),
							'total'     => $num_pages,
							'current'   => $pagenum,
						) );
						?>
					</div>
				<?php endif; ?>
			</div>

			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th style="width: 15%;">Time</th>
						<th style="width: 20%;">File</th>
						<th style="width: 15%;">User</th>
						<th style="width: 10%;">Status</th>
						<th style="width: 15%;">IP Address</th>
						<th style="width: 10%;">Country</th>
						<th>User Agent</th>
					</tr>
				</thead>
				<tbody>
					<?php if ( $logs ) : ?>
						<?php foreach ( $logs as $log ) : ?>
							<?php 
							$file_title = get_the_title( $log->file_id ) ?: '<em>Deleted File (ID: '.$log->file_id.')</em>';
							$user_name = $log->user_id ? get_userdata( $log->user_id )->display_name : 'Guest';
							?>
							<tr>
								<td><?php echo esc_html( $log->timestamp ); ?></td>
								<td><strong><?php echo wp_kses_post( $file_title ); ?></strong></td>
								<td><?php echo esc_html( $user_name ); ?></td>
								<td>
									<span class="status-tag" style="background: <?php echo $log->user_status === 'member' ? '#e7f7ff' : '#f8f9fa'; ?>; color: <?php echo $log->user_status === 'member' ? '#0073aa' : '#666'; ?>; padding: 2px 8px; border-radius: 4px; font-size: 0.85em; font-weight: 600;">
										<?php echo esc_html( ucfirst( $log->user_status ) ); ?>
									</span>
								</td>
								<td><code><?php echo esc_html( $log->ip_address ); ?></code></td>
								<td><span class="dashicons dashicons-location"></span> <?php echo esc_html( $log->country ); ?></td>
								<td title="<?php echo esc_attr( $log->user_agent ); ?>"><small style="color: #888;"><?php echo esc_html( wp_trim_words( $log->user_agent, 6 ) ); ?></small></td>
							</tr>
						<?php endforeach; ?>
					<?php else : ?>
						<tr>
							<td colspan="7" style="text-align: center; padding: 40px;">No download records found yet.</td>
						</tr>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
		<style>
		.status-tag { display: inline-block; text-transform: uppercase; letter-spacing: 0.5px; }
		</style>
		<?php
	}
}
