<?php

namespace SGOplus\WP_Share;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Settings {

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	public function add_settings_page() {
		// Main Settings
		add_submenu_page(
			'edit.php?post_type=sfs_file',
			esc_html__( 'Settings', 'sgoplus-wp-share' ),
			esc_html__( 'Settings', 'sgoplus-wp-share' ),
			'manage_options',
			'sfs-settings',
			array( $this, 'render_settings_page' )
		);

		// PRO Log Submenu (Only if PRO is active)
		if ( is_sfs_pro_active() ) {
			add_submenu_page(
				'edit.php?post_type=sfs_file',
				esc_html__( 'PRO Log', 'sgoplus-wp-share' ),
				esc_html__( 'PRO Log', 'sgoplus-wp-share' ),
				'manage_options',
				'sfs-pro-log',
				array( $this, 'render_pro_log_page' )
			);
		}

		// Guild Page (Usage Guide)
		add_submenu_page(
			'edit.php?post_type=sfs_file',
			esc_html__( 'Guild', 'sgoplus-wp-share' ),
			esc_html__( 'Guild', 'sgoplus-wp-share' ),
			'manage_options',
			'sfs-guild',
			array( $this, 'render_guild_page' )
		);
	}

	public function register_settings() {
		register_setting( 'sfs_settings_group', 'sfs_acceleration_mode', array(
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => 'standard',
		) );
		register_setting( 'sfs_settings_group', 'sfs_license_key', array(
			'sanitize_callback' => array( $this, 'validate_license' ),
		) );
	}

	/**
	 * Validate License Key via Software License Manager (SLM) API
	 */
	public function validate_license( $key ) {
		$key = sanitize_text_field( $key );
		$old_key = get_option( 'sfs_license_key' );
		$license_status = get_option( 'sfs_license_status' );
		$is_currently_valid = ( isset( $license_status['isValid'] ) && $license_status['isValid'] === true );
		
		// If key is empty, reset status
		if ( empty( $key ) ) {
			update_option( 'sfs_license_status', array( 'isValid' => false ) );
			return $key;
		}

		// Optimization: If the key hasn't changed and it's already validated, skip the remote request.
		if ( $key === $old_key && $is_currently_valid ) {
			return $key;
		}

		// SLM API Config
		$api_url    = 'https://virduct.com';
		$secret_key = '69e5d4eb0cf195.93364420';
		
		// Build URL for wp_remote_get
		$query_url = add_query_arg( array(
			'slm_action'        => 'slm_activate',
			'secret_key'        => $secret_key,
			'license_key'       => $key,
			'registered_domain' => home_url(),
		), $api_url );

		$response = wp_remote_get( $query_url, array( 'timeout' => 20 ) );

		if ( is_wp_error( $response ) ) {
			add_settings_error( 'sfs_license_key', 'api_error', sprintf( 
				/* translators: %s: connection error message */
				esc_html__( 'Connection error: %s. Please try again in 3 seconds.', 'sgoplus-wp-share' ), 
				$response->get_error_message() 
			) );
			return $old_key; // Revert to old key to maintain state
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		// SLM returns result: 'success' or 'error'
		$is_valid = ( isset( $data['result'] ) && $data['result'] === 'success' );

		update_option( 'sfs_license_status', array(
			'isValid'     => $is_valid,
			'lastChecked' => current_time( 'mysql' ),
			'message'     => isset( $data['message'] ) ? $data['message'] : ''
		) );

		if ( ! $is_valid ) {
			$error_msg = isset( $data['message'] ) ? $data['message'] : esc_html__( 'Invalid License Key.', 'sgoplus-wp-share' );
			add_settings_error( 'sfs_license_key', 'invalid_key', $error_msg . ' ' . esc_html__( 'Please wait 3 seconds before trying again.', 'sgoplus-wp-share' ) );
		}

		return $key;
	}

	public function render_settings_page() {
		$is_pro = is_sfs_pro_active();
		?>
		<div class="wrap sfs-settings-wrap" style="max-width: 1200px;">
			<h1 style="margin-bottom: 20px;"><?php esc_html_e( 'SGOplus WP Share Settings', 'sgoplus-wp-share' ); ?></h1>
			
			<div style="display: flex; gap: 20px; align-items: flex-start;">
				<!-- Main Content -->
				<div style="flex: 1; min-width: 0;">
					<form method="post" action="options.php">
						<?php
						settings_fields( 'sfs_settings_group' );
						do_settings_sections( 'sfs_settings_group' );
						?>
						
						<!-- License Card -->
						<div class="card" style="margin: 0 0 20px 0; padding: 25px; border-radius: 12px; border: 1px solid <?php echo $is_pro ? '#d4edda' : '#e5e5e5'; ?>; box-shadow: 0 2px 4px rgba(0,0,0,0.02); max-width: none; width: 100%; box-sizing: border-box; background: <?php echo $is_pro ? '#fafffa' : '#fff'; ?>;">
							<h2 style="margin-top: 0; font-size: 1.3em; display: flex; align-items: center; gap: 10px;">
								<span class="dashicons dashicons-shield-alt" style="color: <?php echo $is_pro ? '#28a745' : '#0073aa'; ?>;"></span> 
								<?php esc_html_e( 'PRO License Management', 'sgoplus-wp-share' ); ?>
							</h2>
							<p style="color: #666; margin-bottom: 20px;"><?php esc_html_e( 'Enter your License Key to unlock all PRO features and premium support.', 'sgoplus-wp-share' ); ?></p>
							
							<table class="form-table" style="margin-top: 0;">
								<tr>
									<th scope="row" style="width: 200px; padding: 15px 0;"><?php esc_html_e( 'License Key', 'sgoplus-wp-share' ); ?></th>
									<td>
										<input type="text" name="sfs_license_key" value="<?php echo esc_attr( get_option( 'sfs_license_key' ) ); ?>" class="regular-text" style="width: 100%; max-width: 400px; height: 40px; border-radius: 6px;" placeholder="SFS-XXXX-XXXX-XXXX" />
										<?php if ( $is_pro ) : ?>
											<p style="color: #28a745; font-weight: 600; margin-top: 8px; display: flex; align-items: center; gap: 5px;">
												<span class="dashicons dashicons-yes-alt"></span> <?php esc_html_e( 'PRO License is Active', 'sgoplus-wp-share' ); ?>
											</p>
										<?php else : ?>
											<p style="color: #d63031; font-weight: 600; margin-top: 8px;"><?php esc_html_e( 'License Inactive. Please activate to use PRO functions.', 'sgoplus-wp-share' ); ?></p>
										<?php endif; ?>
									</td>
								</tr>
							</table>
						</div>

						<!-- Performance Optimization Card -->
						<div class="card" style="margin: 0 0 20px 0; padding: 25px; border-radius: 12px; border: 1px solid #e5e5e5; box-shadow: 0 2px 4px rgba(0,0,0,0.02); max-width: none; width: 100%; box-sizing: border-box; position: relative;">
							<?php if ( ! $is_pro ) : ?>
								<div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255,255,255,0.7); z-index: 10; border-radius: 12px; display: flex; align-items: center; justify-content: center; backdrop-filter: blur(2px);">
									<div style="background: #fff; padding: 15px 25px; border-radius: 10px; border: 1px solid #e5e5e5; box-shadow: 0 5px 20px rgba(0,0,0,0.1); text-align: center;">
										<p style="margin: 0 0 10px 0; font-weight: 700; color: #1d2327;"><?php esc_html_e( 'PRO Feature Locked', 'sgoplus-wp-share' ); ?></p>
										<p style="margin: 0; font-size: 0.9em; color: #666;"><?php esc_html_e( 'Please activate your license to enable acceleration.', 'sgoplus-wp-share' ); ?></p>
									</div>
								</div>
							<?php endif; ?>

							<h2 style="margin-top: 0; font-size: 1.3em; display: flex; align-items: center; gap: 10px;">
								<span class="dashicons dashicons-performance" style="color: #0073aa;"></span> 
								<?php esc_html_e( 'Performance Optimization', 'sgoplus-wp-share' ); ?>
							</h2>
							<p style="color: #666; margin-bottom: 20px;"><?php esc_html_e( 'Optimize how your server handles file streams to maximize download speed and reduce CPU usage.', 'sgoplus-wp-share' ); ?></p>
							
							<table class="form-table" style="margin-top: 0;">
								<tr>
									<th scope="row" style="width: 200px; padding: 15px 0;"><?php esc_html_e( 'Acceleration Mode', 'sgoplus-wp-share' ); ?></th>
									<td>
										<?php 
										$mode = get_option( 'sfs_acceleration_mode', 'standard' ); 
										if ( ! $is_pro ) $mode = 'standard'; 
										?>
										<select name="sfs_acceleration_mode" <?php echo ! $is_pro ? 'disabled' : ''; ?> style="width: 100%; max-width: 400px; height: 40px; border-radius: 6px;">
											<option value="standard" <?php selected( $mode, 'standard' ); ?>><?php esc_html_e( 'Standard (PHP Chunked - Universal)', 'sgoplus-wp-share' ); ?></option>
											<option value="x_sendfile" <?php selected( $mode, 'x_sendfile' ); ?>><?php esc_html_e( 'X-Sendfile (Apache)', 'sgoplus-wp-share' ); ?></option>
											<option value="x_accel" <?php selected( $mode, 'x_accel' ); ?>><?php esc_html_e( 'X-Accel-Redirect (Nginx)', 'sgoplus-wp-share' ); ?></option>
											<option value="x_litespeed" <?php selected( $mode, 'x_litespeed' ); ?>><?php esc_html_e( 'X-LiteSpeed-Location (LiteSpeed / OpenLiteSpeed)', 'sgoplus-wp-share' ); ?></option>
										</select>
										<div style="margin-top: 10px; padding: 12px; background: #fff8e1; border-left: 4px solid #ffc107; font-size: 0.9em; border-radius: 4px;">
											<strong><?php esc_html_e( 'Notice:', 'sgoplus-wp-share' ); ?></strong> <?php esc_html_e( 'Specialized modes require server-side configuration. If downloads fail (0 bytes or 404), please revert to Standard mode.', 'sgoplus-wp-share' ); ?>
										</div>
									</td>
								</tr>
							</table>
						</div>

						<!-- Get PRO Link (Only if not PRO) -->
						<?php if ( ! $is_pro ) : ?>
							<div class="card" style="margin: 0; padding: 25px; border-radius: 12px; border: 1px solid #d1d1d1; background: linear-gradient(135deg, #f8f7ff 0%, #ffffff 100%); border-left: 5px solid #6c5ce7; box-shadow: 0 4px 12px rgba(108, 92, 231, 0.05); max-width: none; width: 100%; box-sizing: border-box;">
								<div style="display: flex; justify-content: space-between; align-items: center; gap: 20px; flex-wrap: wrap;">
									<div style="flex: 1;">
										<h2 style="color: #6c5ce7; margin-top: 0; font-size: 1.3em;"><?php esc_html_e( 'Unlock PRO Features', 'sgoplus-wp-share' ); ?></h2>
										<p style="margin-bottom: 0; color: #444;"><?php esc_html_e( 'Upgrade to unlock advanced download analytics, role-based access control, and automated notifications.', 'sgoplus-wp-share' ); ?></p>
									</div>
									<div style="text-align: right;">
										<a href="https://sgoplus.one/wp-share-service/" target="_blank" class="button button-primary" style="background: #6c5ce7; border-color: #6c5ce7; padding: 10px 25px; height: auto; font-weight: 600; border-radius: 8px; font-size: 1.1em; transition: all 0.2s;"><?php esc_html_e( 'Get PRO Version', 'sgoplus-wp-share' ); ?></a>
									</div>
								</div>
							</div>
						<?php endif; ?>

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
							<img src="<?php echo esc_url( SGOPLUS_SFS_URL . 'assets/logo.png' ); ?>" alt="SGOplus" style="width: 90px; height: 90px; border-radius: 50%; border: 4px solid #f8f9fa; box-shadow: 0 5px 15px rgba(0,0,0,0.05);" onerror="this.src='https://secure.gravatar.com/avatar/ad516503a11cd5ca435acc9bb6523536?s=90&d=mm&r=g';">
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
							<p style="margin: 0;">SGOplus WP Share <strong>v1.2.0</strong></p>
							<p style="margin: 5px 0 0 0;">© 2026 SGOplus</p>
						</div>
					</div>
				</div>
			</div>
		</div>
		<style>
			.sfs-settings-wrap .card h2 { font-weight: 700; margin-bottom: 15px; }
			.sfs-settings-wrap select:focus, .sfs-settings-wrap input:focus { border-color: #6c5ce7; box-shadow: 0 0 0 1px #6c5ce7; outline: 2px solid transparent; }
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
		
		// Re-check PRO status inside the page just in case
		if ( ! is_sfs_pro_active() ) {
			wp_die( esc_html__( 'This page is only available in the PRO version. Please activate your license.', 'sgoplus-wp-share' ) );
		}

		// Handle Clear Logs
		if ( isset( $_POST['sfs_clear_logs'] ) && check_admin_referer( 'sfs_clear_logs_nonce' ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->query( $wpdb->prepare( "TRUNCATE TABLE %i", $table_name ) );
			echo '<div class="updated"><p>' . esc_html__( 'Logs cleared successfully.', 'sgoplus-wp-share' ) . '</p></div>';
		}

		$pagenum = isset( $_GET['paged'] ) ? max( 1, intval( $_GET['paged'] ) ) : 1;
		$per_page = 20;
		$offset = ( $pagenum - 1 ) * $per_page;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$total_logs = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id) FROM %i", $table_name ) );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$logs = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM %i ORDER BY timestamp DESC LIMIT %d, %d", $table_name, $offset, $per_page ) );
		
		$num_pages = ceil( $total_logs / $per_page );
		?>
		<div class="wrap">
			<h1 style="display: flex; align-items: center; gap: 10px;">
				<span class="dashicons dashicons-list-view" style="font-size: 1.2em; width: auto; height: auto;"></span> 
				<?php esc_html_e( 'PRO Download Logs', 'sgoplus-wp-share' ); ?>
			</h1>
			<p><?php esc_html_e( 'Track every download event with detailed user information.', 'sgoplus-wp-share' ); ?></p>

			<div style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: flex-end;">
				<form method="post" onsubmit="return confirm('<?php echo esc_js( __( 'Are you sure you want to clear all logs?', 'sgoplus-wp-share' ) ); ?>');">
					<?php wp_nonce_field( 'sfs_clear_logs_nonce' ); ?>
					<input type="submit" name="sfs_clear_logs" class="button button-secondary" value="<?php echo esc_attr__( 'Clear All Logs', 'sgoplus-wp-share' ); ?>" />
				</form>
				
				<?php if ( $num_pages > 1 ) : ?>
					<div class="tablenav-pages">
						<span class="displaying-num"><?php echo sprintf( 
							/* translators: %d: total number of items */
							esc_html__( '%d items', 'sgoplus-wp-share' ), 
							intval( $total_logs ) 
						); ?></span>
						<?php
						echo wp_kses_post( paginate_links( array(
							'base'      => add_query_arg( 'paged', '%#%' ),
							'format'    => '',
							'prev_text' => esc_html__( '&laquo;', 'sgoplus-wp-share' ),
							'next_text' => esc_html__( '&raquo;', 'sgoplus-wp-share' ),
							'total'     => $num_pages,
							'current'   => $pagenum,
						) ) );
						?>
					</div>
				<?php endif; ?>
			</div>

			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th style="width: 15%;"><?php esc_html_e( 'Time', 'sgoplus-wp-share' ); ?></th>
						<th style="width: 20%;"><?php esc_html_e( 'File', 'sgoplus-wp-share' ); ?></th>
						<th style="width: 15%;"><?php esc_html_e( 'User', 'sgoplus-wp-share' ); ?></th>
						<th style="width: 10%;"><?php esc_html_e( 'Status', 'sgoplus-wp-share' ); ?></th>
						<th style="width: 15%;"><?php esc_html_e( 'IP Address', 'sgoplus-wp-share' ); ?></th>
						<th style="width: 10%;"><?php esc_html_e( 'Country', 'sgoplus-wp-share' ); ?></th>
						<th><?php esc_html_e( 'User Agent', 'sgoplus-wp-share' ); ?></th>
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
							<td colspan="7" style="text-align: center; padding: 40px;"><?php esc_html_e( 'No download records found yet.', 'sgoplus-wp-share' ); ?></td>
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

	/**
	 * Render the Guild (Usage Guide) page
	 */
	public function render_guild_page() {
		?>
		<div class="wrap sfs-guild-wrap" style="max-width: 1200px;">
			<div class="sfs-guild-header">
				<h1>
					<span class="dashicons dashicons-welcome-learn-more"></span>
					<?php esc_html_e( 'SGOplus Guild & Usage Guide', 'sgoplus-wp-share' ); ?>
				</h1>
				<p class="sfs-guild-subtitle"><?php esc_html_e( 'Master your file sharing service with professional guidance.', 'sgoplus-wp-share' ); ?></p>
				
				<div class="sfs-lang-switcher">
					<button class="sfs-lang-btn active" data-lang="zh"><?php echo esc_html__( '繁體中文', 'sgoplus-wp-share' ); ?></button>
					<button class="sfs-lang-btn" data-lang="jp"><?php echo esc_html__( '日本語', 'sgoplus-wp-share' ); ?></button>
					<button class="sfs-lang-btn" data-lang="en"><?php echo esc_html__( 'English', 'sgoplus-wp-share' ); ?></button>
				</div>
			</div>

			<div class="sfs-guild-content">
				<!-- ZH Content -->
				<div class="sfs-guild-pane active" id="pane-zh">
					<div class="sfs-intro-card">
						<h2><span class="dashicons dashicons-star-filled"></span> <?php echo esc_html__( '插件介紹', 'sgoplus-wp-share' ); ?></h2>
						<p><?php echo esc_html__( 'SGOplus WP Share 是一款專為 WordPress 設計的高效檔案分享插件。它結合了安全性與極速下載體驗，支援密碼保護、角色存取控制以及伺服器級別的下載加速（X-Accel-Redirect / X-Sendfile）。', 'sgoplus-wp-share' ); ?></p>
					</div>

					<div class="sfs-grid-guide">
						<div class="sfs-guide-card">
							<h3><span class="dashicons dashicons-index-card"></span> <?php echo esc_html__( '1. 建立檔案', 'sgoplus-wp-share' ); ?></h3>
							<p><?php echo esc_html__( '在「Share Service+」選單中點擊「Add New」，上傳或輸入檔案 URL，並設定下載密碼或會員權限。', 'sgoplus-wp-share' ); ?></p>
						</div>
						<div class="sfs-guide-card">
							<h3><span class="dashicons dashicons-editor-code"></span> <?php echo esc_html__( '2. 使用短代碼', 'sgoplus-wp-share' ); ?></h3>
							<p><?php echo esc_html__( '在任何頁面或文章中貼上短代碼即可嵌入檔案下載介面。', 'sgoplus-wp-share' ); ?></p>
						</div>
						<div class="sfs-guide-card">
							<h3><span class="dashicons dashicons-performance"></span> <?php echo esc_html__( '3. 效能優化', 'sgoplus-wp-share' ); ?></h3>
							<p><?php echo esc_html__( '在「Settings」中開啟加速模式，可大幅降低伺服器負載並提升大檔案下載穩定性。', 'sgoplus-wp-share' ); ?></p>
						</div>
					</div>

					<div class="sfs-code-reference">
						<h2><span class="dashicons dashicons-editor-help"></span> <?php echo esc_html__( '短代碼參考', 'sgoplus-wp-share' ); ?></h2>
						<div class="sfs-code-item">
							<code>[sgoplus_file id="123"]</code>
							<p><?php echo esc_html__( '嵌入特定 ID 的單個檔案卡片。', 'sgoplus-wp-share' ); ?></p>
						</div>
						<div class="sfs-code-item">
							<code>[sgoplus_files]</code>
							<p><?php echo esc_html__( '顯示所有已發布檔案的搜尋過濾網格。', 'sgoplus-wp-share' ); ?></p>
						</div>
						<div class="sfs-code-item">
							<code>[sgoplus_files category="software"]</code>
							<p><?php echo esc_html__( '顯示特定分類下的檔案列表。', 'sgoplus-wp-share' ); ?></p>
						</div>
					</div>
				</div>

				<!-- JP Content -->
				<div class="sfs-guild-pane" id="pane-jp">
					<div class="sfs-intro-card">
						<h2><span class="dashicons dashicons-star-filled"></span> <?php echo esc_html__( 'プラグイン紹介', 'sgoplus-wp-share' ); ?></h2>
						<p><?php echo esc_html__( 'SGOplus WP Share は、WordPress 用に設計されたプロフェッショナルなファイル共有ソリューションです。セキュリティと高速ダウンロード体験を兩立し、パスワード保護、ロールベースのアクセス制御、サーバーレベルのダウンロード加速（X-Accel-Redirect / X-Sendfile）をサポートしています。', 'sgoplus-wp-share' ); ?></p>
					</div>

					<div class="sfs-grid-guide">
						<div class="sfs-guide-card">
							<h3><span class="dashicons dashicons-index-card"></span> <?php echo esc_html__( '1. ファイル作成', 'sgoplus-wp-share' ); ?></h3>
							<p><?php echo esc_html__( '「Share Service+」メニューの「Add New」をクリックし、ファイルURLを入力。必要に応じてパスワードや權限を設定します。', 'sgoplus-wp-share' ); ?></p>
						</div>
						<div class="sfs-guide-card">
							<h3><span class="dashicons dashicons-editor-code"></span> <?php echo esc_html__( '2. ショートコード', 'sgoplus-wp-share' ); ?></h3>
							<p><?php echo esc_html__( '固定ページや投稿にショートコードを貼り付けるだけで、洗練されたダウンロードカードが表示されます。', 'sgoplus-wp-share' ); ?></p>
						</div>
						<div class="sfs-guide-card">
							<h3><span class="dashicons dashicons-performance"></span> <?php echo esc_html__( '3. パフォーマンス', 'sgoplus-wp-share' ); ?></h3>
							<p><?php echo esc_html__( '「Settings」でアクセラレーションモードを有効にすると、サーバー負荷を抑えつつ高速な通信が可能になります。', 'sgoplus-wp-share' ); ?></p>
						</div>
					</div>

					<div class="sfs-code-reference">
						<h2><span class="dashicons dashicons-editor-help"></span> <?php echo esc_html__( 'ショートコードリファレンス', 'sgoplus-wp-share' ); ?></h2>
						<div class="sfs-code-item">
							<code>[sgoplus_file id="123"]</code>
							<p><?php echo esc_html__( '特定のIDを持つ單一のファイルカードを埋め込みます。', 'sgoplus-wp-share' ); ?></p>
						</div>
						<div class="sfs-code-item">
							<code>[sgoplus_files]</code>
							<p><?php echo esc_html__( '公開されているすべてのファイルの檢索・フィルタリンググリッドを表示します。', 'sgoplus-wp-share' ); ?></p>
						</div>
					</div>
				</div>

				<!-- EN Content -->
				<div class="sfs-guild-pane" id="pane-en">
					<div class="sfs-intro-card">
						<h2><span class="dashicons dashicons-star-filled"></span> <?php echo esc_html__( 'Plugin Introduction', 'sgoplus-wp-share' ); ?></h2>
						<p><?php echo esc_html__( 'SGOplus WP Share is a high-performance file sharing solution for WordPress. It combines advanced security with lightning-fast download experiences, supporting password protection, role-based access control, and server-side acceleration (X-Accel-Redirect / X-Sendfile).', 'sgoplus-wp-share' ); ?></p>
					</div>

					<div class="sfs-grid-guide">
						<div class="sfs-guide-card">
							<h3><span class="dashicons dashicons-index-card"></span> <?php echo esc_html__( '1. Create Record', 'sgoplus-wp-share' ); ?></h3>
							<p><?php echo esc_html__( 'Go to "Share Service+" -> "Add New". Upload your file or paste a URL, then set your desired protection settings.', 'sgoplus-wp-share' ); ?></p>
						</div>
						<div class="sfs-guide-card">
							<h3><span class="dashicons dashicons-editor-code"></span> <?php echo esc_html__( '2. Use Shortcodes', 'sgoplus-wp-share' ); ?></h3>
							<p><?php echo esc_html__( 'Paste the shortcode into any page or post to display the premium download interface.', 'sgoplus-wp-share' ); ?></p>
						</div>
						<div class="sfs-guide-card">
							<h3><span class="dashicons dashicons-performance"></span> <?php echo esc_html__( '3. Optimization', 'sgoplus-wp-share' ); ?></h3>
							<p><?php echo esc_html__( 'Enable Acceleration Mode in Settings to reduce CPU usage and provide stable downloads for large files.', 'sgoplus-wp-share' ); ?></p>
						</div>
					</div>

					<div class="sfs-code-reference">
						<h2><span class="dashicons dashicons-editor-help"></span> <?php echo esc_html__( 'Shortcode Reference', 'sgoplus-wp-share' ); ?></h2>
						<div class="sfs-code-item">
							<code>[sgoplus_file id="123"]</code>
							<p><?php echo esc_html__( 'Embeds a specific file card by ID.', 'sgoplus-wp-share' ); ?></p>
						</div>
						<div class="sfs-code-item">
							<code>[sgoplus_files]</code>
							<p><?php echo esc_html__( 'Displays a searchable grid of all published files.', 'sgoplus-wp-share' ); ?></p>
						</div>
					</div>
				</div>
			</div>
			
			<div class="sfs-guild-footer">
				<p><?php echo sprintf( 
					/* translators: %s: Support center link */
					esc_html__( '© 2026 SGOplus Group • %s', 'sgoplus-wp-share' ),
					'<a href="https://sgoplus.one" target="_blank">' . esc_html__( 'Support Center', 'sgoplus-wp-share' ) . '</a>'
				); ?></p>
			</div>
		</div>

		<style>
			:root {
				--sfs-guild-primary: #6c5ce7;
				--sfs-guild-secondary: #a29bfe;
				--sfs-guild-bg: #f8f9fa;
				--sfs-guild-card: #ffffff;
				--sfs-guild-text: #2d3436;
				--sfs-guild-muted: #636e72;
			}

			.sfs-guild-wrap { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif; padding: 20px; color: var(--sfs-guild-text); }
			
			.sfs-guild-header { text-align: center; margin-bottom: 40px; background: linear-gradient(135deg, #6c5ce7 0%, #a29bfe 100%); padding: 60px 20px; border-radius: 20px; color: #fff; box-shadow: 0 10px 30px rgba(108, 92, 231, 0.2); }
			.sfs-guild-header h1 { color: #fff; font-size: 2.5em; font-weight: 800; margin: 0 0 10px 0; display: flex; align-items: center; justify-content: center; gap: 15px; }
			.sfs-guild-header h1 .dashicons { font-size: 40px; width: 40px; height: 40px; }
			.sfs-guild-subtitle { font-size: 1.2em; opacity: 0.9; margin: 0; }

			.sfs-lang-switcher { margin-top: 30px; display: flex; justify-content: center; gap: 10px; }
			.sfs-lang-btn { background: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.3); color: #fff; padding: 10px 25px; border-radius: 50px; cursor: pointer; font-weight: 600; transition: all 0.3s; backdrop-filter: blur(5px); }
			.sfs-lang-btn:hover { background: rgba(255,255,255,0.25); }
			.sfs-lang-btn.active { background: #fff; color: var(--sfs-guild-primary); border-color: #fff; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }

			.sfs-guild-pane { display: none; animation: sfsFadeIn 0.5s ease; }
			.sfs-guild-pane.active { display: block; }
			@keyframes sfsFadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

			.sfs-intro-card { background: var(--sfs-guild-card); padding: 40px; border-radius: 20px; box-shadow: 0 5px 20px rgba(0,0,0,0.03); margin-bottom: 30px; border-left: 6px solid var(--sfs-guild-primary); }
			.sfs-intro-card h2 { margin-top: 0; color: var(--sfs-guild-primary); display: flex; align-items: center; gap: 10px; }
			.sfs-intro-card p { font-size: 1.1em; line-height: 1.8; color: var(--sfs-guild-muted); margin: 0; }

			.sfs-grid-guide { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-bottom: 30px; }
			.sfs-guide-card { background: var(--sfs-guild-card); padding: 30px; border-radius: 20px; box-shadow: 0 5px 20px rgba(0,0,0,0.03); transition: transform 0.3s; }
			.sfs-guide-card:hover { transform: translateY(-5px); }
			.sfs-guide-card h3 { margin-top: 0; color: #1d2327; display: flex; align-items: center; gap: 10px; font-size: 1.3em; }
			.sfs-guide-card p { color: var(--sfs-guild-muted); line-height: 1.6; margin: 0; }

			.sfs-code-reference { background: #1d2327; color: #fff; padding: 40px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
			.sfs-code-reference h2 { color: var(--sfs-guild-secondary); margin-top: 0; display: flex; align-items: center; gap: 10px; }
			.sfs-code-item { margin-bottom: 25px; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 20px; }
			.sfs-code-item:last-child { margin-bottom: 0; border-bottom: none; padding-bottom: 0; }
			.sfs-code-item code { background: rgba(108, 92, 231, 0.2); color: #a29bfe; padding: 8px 15px; border-radius: 8px; font-size: 1.1em; display: inline-block; margin-bottom: 10px; border: 1px solid rgba(162, 155, 254, 0.3); }
			.sfs-code-item p { margin: 0; opacity: 0.8; font-size: 0.95em; }

			.sfs-guild-footer { text-align: center; margin-top: 50px; padding-bottom: 30px; color: var(--sfs-guild-muted); }
			.sfs-guild-footer a { color: var(--sfs-guild-primary); text-decoration: none; font-weight: 600; }

			@media (max-width: 768px) {
				.sfs-guild-header { padding: 40px 15px; }
				.sfs-guild-header h1 { font-size: 1.8em; }
				.sfs-intro-card, .sfs-code-reference { padding: 25px; }
			}
		</style>

		<script>
			document.addEventListener('DOMContentLoaded', function() {
				const buttons = document.querySelectorAll('.sfs-lang-btn');
				const panes = document.querySelectorAll('.sfs-guild-pane');

				buttons.forEach(btn => {
					btn.addEventListener('click', function() {
						const lang = this.getAttribute('data-lang');
						
						// Update buttons
						buttons.forEach(b => b.classList.remove('active'));
						this.classList.add('active');

						// Update panes
						panes.forEach(p => p.classList.remove('active'));
						document.getElementById('pane-' + lang).classList.add('active');
					});
				});
			});
		</script>
		<?php
	}
}
