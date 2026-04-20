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
		<div class="wrap sfs-settings-wrap" style="max-width: 1200px;">
			<h1 style="margin-bottom: 20px;">WP Share Service Settings</h1>
			
			<div style="display: flex; gap: 20px; align-items: flex-start;">
				<!-- Main Content -->
				<div style="flex: 1; min-width: 0;">
					<form method="post" action="options.php">
						<?php
						settings_fields( 'sfs_settings_group' );
						do_settings_sections( 'sfs_settings_group' );
						?>
						
						<div class="card" style="margin: 0 0 20px 0; padding: 25px; border-radius: 12px; border: 1px solid #e5e5e5; box-shadow: 0 2px 4px rgba(0,0,0,0.02); max-width: none; width: 100%; box-sizing: border-box;">
							<h2 style="margin-top: 0; font-size: 1.3em; display: flex; align-items: center; gap: 10px;">
								<span class="dashicons dashicons-performance" style="color: #0073aa;"></span> 
								Performance Optimization
							</h2>
							<p style="color: #666; margin-bottom: 20px;">Optimize how your server handles file streams to maximize download speed and reduce CPU usage.</p>
							
							<table class="form-table" style="margin-top: 0;">
								<tr>
									<th scope="row" style="width: 200px; padding: 15px 0;">Acceleration Mode</th>
									<td>
										<?php $mode = get_option( 'sfs_acceleration_mode', 'standard' ); ?>
										<select name="sfs_acceleration_mode" style="width: 100%; max-width: 400px; height: 40px; border-radius: 6px;">
											<option value="standard" <?php selected( $mode, 'standard' ); ?>>Standard (PHP Chunked - Universal)</option>
											<option value="x_sendfile" <?php selected( $mode, 'x_sendfile' ); ?>>X-Sendfile (Apache)</option>
											<option value="x_accel" <?php selected( $mode, 'x_accel' ); ?>>X-Accel-Redirect (Nginx)</option>
											<option value="x_litespeed" <?php selected( $mode, 'x_litespeed' ); ?>>X-LiteSpeed-Location (LiteSpeed / OpenLiteSpeed)</option>
										</select>
										<div style="margin-top: 10px; padding: 12px; background: #fff8e1; border-left: 4px solid #ffc107; font-size: 0.9em; border-radius: 4px;">
											<strong>Notice:</strong> Specialized modes require server-side configuration. If downloads fail (0 bytes or 404), please revert to <strong>Standard</strong> mode.
										</div>
									</td>
								</tr>
							</table>
						</div>

						<div class="card" style="margin: 0; padding: 25px; border-radius: 12px; border: 1px solid #d1d1d1; background: linear-gradient(135deg, #f8f7ff 0%, #ffffff 100%); border-left: 5px solid #6c5ce7; box-shadow: 0 4px 12px rgba(108, 92, 231, 0.05); max-width: none; width: 100%; box-sizing: border-box;">
							<div style="display: flex; justify-content: space-between; align-items: center; gap: 20px; flex-wrap: wrap;">
								<div style="flex: 1;">
									<h2 style="color: #6c5ce7; margin-top: 0; font-size: 1.3em;">Unlock PRO Features</h2>
									<p style="margin-bottom: 0; color: #444;">Upgrade to unlock advanced download analytics, role-based access control, and automated notifications.</p>
								</div>
								<div style="text-align: right;">
									<a href="https://sgoplus.one/wp-share-service/" target="_blank" class="button button-primary" style="background: #6c5ce7; border-color: #6c5ce7; padding: 10px 25px; height: auto; font-weight: 600; border-radius: 8px; font-size: 1.1em; transition: all 0.2s;">Get PRO Version</a>
								</div>
							</div>
						</div>

						<div style="margin-top: 25px;">
							<?php submit_button( 'Save All Settings', 'primary large', 'submit', true, array( 'style' => 'padding: 10px 30px; border-radius: 8px;' ) ); ?>
						</div>
					</form>
				</div>

				<!-- Sidebar -->
				<div style="width: 320px; flex-shrink: 0;">
					<div class="card" style="padding: 30px 20px; border-radius: 15px; text-align: center; border: 1px solid #e5e5e5; box-shadow: 0 4px 20px rgba(0,0,0,0.04); background: #fff; margin: 0; position: sticky; top: 50px;">
						<h3 style="margin-top: 0; color: #1d2327; font-size: 1.2em;">Developer Hub</h3>
						<div style="margin: 20px 0;">
							<img src="https://sgoplus.one/wp-content/uploads/2023/06/SGOplus-Logo-Round.png" alt="SGOplus" style="width: 90px; height: 90px; border-radius: 50%; border: 4px solid #f8f9fa; box-shadow: 0 5px 15px rgba(0,0,0,0.05);" onerror="this.src='https://secure.gravatar.com/avatar/ad516503a11cd5ca435acc9bb6523536?s=90&d=mm&r=g';">
						</div>
						
						<p style="font-weight: 800; font-size: 1.2em; margin: 0 0 5px 0; color: #1d2327;">SGOplus Group</p>
						<p style="font-size: 0.9em; color: #666; margin: 0 0 25px 0; line-height: 1.4;">Premium WordPress Solutions<br>Crafted with Excellence</p>
						
						<div style="display: flex; flex-direction: column; gap: 12px; text-align: left; padding: 0 10px;">
							<a href="https://sgoplus.one" target="_blank" style="text-decoration: none; color: #2271b1; display: flex; align-items: center; gap: 12px; font-weight: 500; padding: 8px; border-radius: 8px; background: #f6f7f7;">
								<span class="dashicons dashicons-admin-site" style="font-size: 20px; color: #0073aa;"></span> Official Website
							</a>
							<a href="https://github.com/sgoleo" target="_blank" style="text-decoration: none; color: #2271b1; display: flex; align-items: center; gap: 12px; font-weight: 500; padding: 8px; border-radius: 8px; background: #f6f7f7;">
								<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: #1d2327;"><path d="M9 19c-5 1.5-5-2.5-7-3m14 6v-3.87a3.37 3.37 0 0 0-.94-2.61c3.14-.35 6.44-1.54 6.44-7A5.44 5.44 0 0 0 20 4.77 5.07 5.07 0 0 0 19.91 1S18.73.65 16 2.48a13.38 13.38 0 0 0-7 0C6.27.65 5.09 1 5.09 1A5.07 5.07 0 0 0 5 4.77a5.44 5.44 0 0 0-1.5 3.78c0 5.42 3.3 6.61 6.44 7A3.37 3.37 0 0 0 9 18.13V22"></path></svg>
								GitHub Profile
							</a>
							<a href="https://discord.gg/WnkEKkZYFY" target="_blank" style="text-decoration: none; color: #fff; display: flex; align-items: center; gap: 12px; font-weight: 600; padding: 10px; border-radius: 8px; background: #5865F2; box-shadow: 0 4px 10px rgba(88, 101, 242, 0.2);">
								<span class="dashicons dashicons-format-chat" style="font-size: 20px; color: #fff;"></span> Join Discord
							</a>
						</div>
						
						<hr style="margin: 25px 0; border: 0; border-top: 1px solid #f0f0f1;">
						
						<div style="font-size: 0.85em; color: #999;">
							<p style="margin: 0;">WP Share Service Pro <strong>v1.7.5</strong></p>
							<p style="margin: 5px 0 0 0;">© 2026 SGOplus</p>
						</div>
					</div>
				</div>
			</div>
		</div>
		<style>
			.sfs-settings-wrap .card h2 { font-weight: 700; margin-bottom: 15px; }
			.sfs-settings-wrap select:focus { border-color: #6c5ce7; box-shadow: 0 0 0 1px #6c5ce7; outline: 2px solid transparent; }
			.sfs-settings-wrap .button-primary:hover { transform: translateY(-1px); box-shadow: 0 5px 15px rgba(108, 92, 231, 0.3); }
			@media (max-width: 900px) {
				.sfs-settings-wrap > div { flex-direction: column; }
				.sfs-settings-wrap div[style*="width: 320px"] { width: 100% !important; }
			}
		</style>
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
