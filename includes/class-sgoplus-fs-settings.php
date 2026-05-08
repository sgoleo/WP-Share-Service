<?php

namespace SGOplus\File_Share;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Settings {

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
	}

	public function enqueue_admin_assets( $hook ) {
		// Only load on our plugin pages
		if ( strpos( $hook, 'sgoplus-fs-' ) === false && strpos( $hook, 'sgoplus_fs_file' ) === false ) {
			return;
		}

		wp_enqueue_style( 'sgoplus-fs-admin-style', SGOPLUS_FS_URL . 'assets/sgoplus-fs-admin.css', array(), SGOPLUS_FS_VERSION );
		wp_enqueue_script( 'sgoplus-fs-admin-script', SGOPLUS_FS_URL . 'assets/sgoplus-fs-admin.js', array( 'jquery' ), SGOPLUS_FS_VERSION, true );
		
		if ( $hook === 'post.php' || $hook === 'post-new.php' ) {
			wp_enqueue_media();
		}
	}

	public function add_settings_page() {
		// Main Settings
		add_submenu_page(
			'edit.php?post_type=sgoplus_fs_file',
			esc_html__( 'Settings', 'sgoplus-file-share' ),
			esc_html__( 'Settings', 'sgoplus-file-share' ),
			'manage_options',
			'sgoplus-fs-settings',
			array( $this, 'render_settings_page' )
		);

		// PRO Log Submenu (Only if PRO is active)
		if ( is_sgoplus_fs_pro_active() ) {
			add_submenu_page(
				'edit.php?post_type=sgoplus_fs_file',
				esc_html__( 'PRO Log', 'sgoplus-file-share' ),
				esc_html__( 'PRO Log', 'sgoplus-file-share' ),
				'manage_options',
				'sgoplus-fs-pro-log',
				array( $this, 'render_pro_log_page' )
			);
		}

		// Guild Page (Usage Guide)
		add_submenu_page(
			'edit.php?post_type=sgoplus_fs_file',
			esc_html__( 'Guild', 'sgoplus-file-share' ),
			esc_html__( 'Guild', 'sgoplus-file-share' ),
			'manage_options',
			'sgoplus-fs-guild',
			array( $this, 'render_guild_page' )
		);
	}

	public function register_settings() {
		register_setting( 'sgoplus_fs_settings_group', 'sgoplus_fs_acceleration_mode', array(
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => 'standard',
		) );
		register_setting( 'sgoplus_fs_settings_group', 'sgoplus_fs_license_key', array(
			'sanitize_callback' => array( $this, 'validate_license' ),
		) );
	}

	/**
	 * Validate License Key via Software License Manager (SLM) API
	 */
	public function validate_license( $key ) {
		$key = sanitize_text_field( $key );
		$old_key = get_option( 'sgoplus_fs_license_key' );
		$license_status = get_option( 'sgoplus_fs_license_status' );
		$is_currently_valid = ( isset( $license_status['isValid'] ) && $license_status['isValid'] === true );
		
		// If key is empty, reset status
		if ( empty( $key ) ) {
			update_option( 'sgoplus_fs_license_status', array( 'isValid' => false ) );
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
			add_settings_error( 'sgoplus_fs_license_key', 'api_error', sprintf( 
				/* translators: %s: connection error message */
				esc_html__( 'Connection error: %s. Please try again in 3 seconds.', 'sgoplus-file-share' ), 
				$response->get_error_message() 
			) );
			return $old_key; // Revert to old key to maintain state
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		// SLM returns result: 'success' or 'error'
		$is_valid = ( isset( $data['result'] ) && $data['result'] === 'success' );

		update_option( 'sgoplus_fs_license_status', array(
			'isValid'     => $is_valid,
			'lastChecked' => current_time( 'mysql' ),
			'message'     => isset( $data['message'] ) ? sanitize_text_field( $data['message'] ) : ''
		) );

		if ( ! $is_valid ) {
			$error_msg = isset( $data['message'] ) ? sanitize_text_field( $data['message'] ) : esc_html__( 'Invalid License Key.', 'sgoplus-file-share' );
			add_settings_error( 'sgoplus_fs_license_key', 'invalid_key', $error_msg . ' ' . esc_html__( 'Please wait 3 seconds before trying again.', 'sgoplus-file-share' ) );
		}

		return $key;
	}

	public function render_settings_page() {
		$is_pro = is_sgoplus_fs_pro_active();
		?>
		<div class="wrap sgoplus-fs-settings-wrap" style="max-width: 1200px;">
			<h1 style="margin-bottom: 20px;"><?php esc_html_e( 'SGOplus File Share Settings', 'sgoplus-file-share' ); ?></h1>
			
			<div style="display: flex; gap: 20px; align-items: flex-start;">
				<!-- Main Content -->
				<div style="flex: 1; min-width: 0;">
					<form method="post" action="options.php">
						<?php
						settings_fields( 'sgoplus_fs_settings_group' );
						do_settings_sections( 'sgoplus_fs_settings_group' );
						?>
						
						<!-- License Card -->
						<div class="card" style="margin: 0 0 20px 0; padding: 25px; border-radius: 12px; border: 1px solid <?php echo $is_pro ? '#d4edda' : '#e5e5e5'; ?>; box-shadow: 0 2px 4px rgba(0,0,0,0.02); max-width: none; width: 100%; box-sizing: border-box; background: <?php echo $is_pro ? '#fafffa' : '#fff'; ?>;">
							<h2 style="margin-top: 0; font-size: 1.3em; display: flex; align-items: center; gap: 10px;">
								<span class="dashicons dashicons-shield-alt" style="color: <?php echo $is_pro ? '#28a745' : '#0073aa'; ?>;"></span> 
								<?php esc_html_e( 'PRO License Management', 'sgoplus-file-share' ); ?>
							</h2>
							<p style="color: #666; margin-bottom: 20px;"><?php esc_html_e( 'Enter your License Key to unlock all PRO features and premium support.', 'sgoplus-file-share' ); ?></p>
							
							<table class="form-table" style="margin-top: 0;">
								<tr>
									<th scope="row" style="width: 200px; padding: 15px 0;"><?php esc_html_e( 'License Key', 'sgoplus-file-share' ); ?></th>
									<td>
										<input type="text" name="sgoplus_fs_license_key" value="<?php echo esc_attr( get_option( 'sgoplus_fs_license_key' ) ); ?>" class="regular-text" style="width: 100%; max-width: 400px; height: 40px; border-radius: 6px;" placeholder="SFS-XXXX-XXXX-XXXX" />
										<?php if ( $is_pro ) : ?>
											<p style="color: #28a745; font-weight: 600; margin-top: 8px; display: flex; align-items: center; gap: 5px;">
												<span class="dashicons dashicons-yes-alt"></span> <?php esc_html_e( 'PRO License is Active', 'sgoplus-file-share' ); ?>
											</p>
										<?php else : ?>
											<p style="color: #d63031; font-weight: 600; margin-top: 8px;"><?php esc_html_e( 'License Inactive. Please activate to use PRO functions.', 'sgoplus-file-share' ); ?></p>
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
										<p style="margin: 0 0 10px 0; font-weight: 700; color: #1d2327;"><?php esc_html_e( 'PRO Feature Locked', 'sgoplus-file-share' ); ?></p>
										<p style="margin: 0; font-size: 0.9em; color: #666;"><?php esc_html_e( 'Please activate your license to enable acceleration.', 'sgoplus-file-share' ); ?></p>
									</div>
								</div>
							<?php endif; ?>

							<h2 style="margin-top: 0; font-size: 1.3em; display: flex; align-items: center; gap: 10px;">
								<span class="dashicons dashicons-performance" style="color: #0073aa;"></span> 
								<?php esc_html_e( 'Performance Optimization', 'sgoplus-file-share' ); ?>
							</h2>
							<p style="color: #666; margin-bottom: 20px;"><?php esc_html_e( 'Optimize how your server handles file streams to maximize download speed and reduce CPU usage.', 'sgoplus-file-share' ); ?></p>
							
							<table class="form-table" style="margin-top: 0;">
								<tr>
									<th scope="row" style="width: 200px; padding: 15px 0;"><?php esc_html_e( 'Acceleration Mode', 'sgoplus-file-share' ); ?></th>
									<td>
										<?php 
										$mode = get_option( 'sgoplus_fs_acceleration_mode', 'standard' ); 
										if ( ! $is_pro ) $mode = 'standard'; 
										?>
										<select name="sgoplus_fs_acceleration_mode" <?php echo ! $is_pro ? 'disabled' : ''; ?> style="width: 100%; max-width: 400px; height: 40px; border-radius: 6px;">
											<option value="standard" <?php selected( $mode, 'standard' ); ?>><?php esc_html_e( 'Standard (PHP Chunked - Universal)', 'sgoplus-file-share' ); ?></option>
											<option value="sgoplus_fs_sendfile" <?php selected( $mode, 'sgoplus_fs_sendfile' ); ?>><?php esc_html_e( 'X-Sendfile (Apache)', 'sgoplus-file-share' ); ?></option>
											<option value="sgoplus_fs_accel" <?php selected( $mode, 'sgoplus_fs_accel' ); ?>><?php esc_html_e( 'X-Accel-Redirect (Nginx)', 'sgoplus-file-share' ); ?></option>
											<option value="sgoplus_fs_litespeed" <?php selected( $mode, 'sgoplus_fs_litespeed' ); ?>><?php esc_html_e( 'X-LiteSpeed-Location (LiteSpeed / OpenLiteSpeed)', 'sgoplus-file-share' ); ?></option>
										</select>
										<div style="margin-top: 10px; padding: 12px; background: #fff8e1; border-left: 4px solid #ffc107; font-size: 0.9em; border-radius: 4px;">
											<strong><?php esc_html_e( 'Notice:', 'sgoplus-file-share' ); ?></strong> <?php esc_html_e( 'Specialized modes require server-side configuration. If downloads fail (0 bytes or 404), please revert to Standard mode.', 'sgoplus-file-share' ); ?>
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
										<h2 style="color: #6c5ce7; margin-top: 0; font-size: 1.3em;"><?php esc_html_e( 'Unlock PRO Features', 'sgoplus-file-share' ); ?></h2>
										<p style="margin-bottom: 0; color: #444;"><?php esc_html_e( 'Upgrade to unlock advanced download analytics, role-based access control, and automated notifications.', 'sgoplus-file-share' ); ?></p>
									</div>
									<div style="text-align: right;">
										<a href="https://sgoplus.one/file-share-service/" target="_blank" class="button button-primary" style="background: #6c5ce7; border-color: #6c5ce7; padding: 10px 25px; height: auto; font-weight: 600; border-radius: 8px; font-size: 1.1em; transition: all 0.2s;"><?php esc_html_e( 'Get PRO Version', 'sgoplus-file-share' ); ?></a>
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
							<img src="<?php echo esc_url( SGOPLUS_FS_URL . 'assets/logo.jpg' ); ?>" alt="SGOplus" style="width: 90px; height: 90px; border-radius: 50%; border: 4px solid #f8f9fa; box-shadow: 0 5px 15px rgba(0,0,0,0.05);" onerror="this.src='<?php echo esc_url( SGOPLUS_FS_URL . 'assets/default-avatar.png' ); ?>';">
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
							<p style="margin: 0;">SGOplus File Share <strong>v1.2.2</strong></p>
							<p style="margin: 5px 0 0 0;">© 2026 SGOplus</p>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	public function render_pro_log_page() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'sgoplus_fs_logs';
		
		// Re-check PRO status inside the page just in case
		if ( ! is_sgoplus_fs_pro_active() ) {
			wp_die( esc_html__( 'This page is only available in the PRO version. Please activate your license.', 'sgoplus-file-share' ) );
		}

		// Handle Clear Logs
		if ( isset( $_POST['sgoplus_fs_clear_logs'] ) && check_admin_referer( 'sgoplus_fs_clear_logs_nonce' ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->query( $wpdb->prepare( "TRUNCATE TABLE %i", $table_name ) );
			echo '<div class="updated"><p>' . esc_html__( 'Logs cleared successfully.', 'sgoplus-file-share' ) . '</p></div>';
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
				<?php esc_html_e( 'PRO Download Logs', 'sgoplus-file-share' ); ?>
			</h1>
			<p><?php esc_html_e( 'Track every download event with detailed user information.', 'sgoplus-file-share' ); ?></p>

			<div style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: flex-end;">
				<form method="post" onsubmit="return confirm('<?php echo esc_js( __( 'Are you sure you want to clear all logs?', 'sgoplus-file-share' ) ); ?>');">
					<?php wp_nonce_field( 'sgoplus_fs_clear_logs_nonce' ); ?>
					<input type="submit" name="sgoplus_fs_clear_logs" class="button button-secondary" value="<?php echo esc_attr__( 'Clear All Logs', 'sgoplus-file-share' ); ?>" />
				</form>
				
				<?php if ( $num_pages > 1 ) : ?>
					<div class="tablenav-pages">
						<span class="displaying-num"><?php echo sprintf( 
							/* translators: %d: total number of items */
							esc_html__( '%d items', 'sgoplus-file-share' ), 
							intval( $total_logs ) 
						); ?></span>
						<?php
						echo wp_kses_post( paginate_links( array(
							'base'      => add_query_arg( 'paged', '%#%' ),
							'format'    => '',
							'prev_text' => esc_html__( '&laquo;', 'sgoplus-file-share' ),
							'next_text' => esc_html__( '&raquo;', 'sgoplus-file-share' ),
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
						<th style="width: 15%;"><?php esc_html_e( 'Time', 'sgoplus-file-share' ); ?></th>
						<th style="width: 20%;"><?php esc_html_e( 'File', 'sgoplus-file-share' ); ?></th>
						<th style="width: 15%;"><?php esc_html_e( 'User', 'sgoplus-file-share' ); ?></th>
						<th style="width: 10%;"><?php esc_html_e( 'Status', 'sgoplus-file-share' ); ?></th>
						<th style="width: 15%;"><?php esc_html_e( 'IP Address', 'sgoplus-file-share' ); ?></th>
						<th style="width: 10%;"><?php esc_html_e( 'Country', 'sgoplus-file-share' ); ?></th>
						<th><?php esc_html_e( 'User Agent', 'sgoplus-file-share' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( $logs ) : ?>
						<?php foreach ( $logs as $log ) : ?>
							<?php 
							$file_title = get_the_title( $log->file_id ) ?: '<em>' . esc_html__( 'Deleted File', 'sgoplus-file-share' ) . ' (ID: '.intval($log->file_id).')</em>';
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
							<td colspan="7" style="text-align: center; padding: 40px;"><?php esc_html_e( 'No download records found yet.', 'sgoplus-file-share' ); ?></td>
						</tr>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	/**
	 * Render the Guild (Usage Guide) page
	 */
	public function render_guild_page() {
		?>
		<div class="wrap sgoplus-fs-guild-wrap" style="max-width: 1200px;">
			<div class="sgoplus-fs-guild-header">
				<h1>
					<span class="dashicons dashicons-welcome-learn-more"></span>
					<?php esc_html_e( 'SGOplus Guild & Usage Guide', 'sgoplus-file-share' ); ?>
				</h1>
				<p class="sgoplus-fs-guild-subtitle"><?php esc_html_e( 'Master your file sharing service with professional guidance.', 'sgoplus-file-share' ); ?></p>
				
				<div class="sgoplus-fs-lang-switcher">
					<button class="sgoplus-fs-lang-btn active" data-lang="zh"><?php echo esc_html__( '繁體中文', 'sgoplus-file-share' ); ?></button>
					<button class="sgoplus-fs-lang-btn" data-lang="jp"><?php echo esc_html__( '日本語', 'sgoplus-file-share' ); ?></button>
					<button class="sgoplus-fs-lang-btn" data-lang="en"><?php echo esc_html__( 'English', 'sgoplus-file-share' ); ?></button>
				</div>
			</div>

			<div class="sgoplus-fs-guild-content">
				<!-- ZH Content -->
				<div class="sgoplus-fs-guild-pane active" id="pane-zh">
					<div class="sgoplus-fs-intro-card">
						<h2><span class="dashicons dashicons-star-filled"></span> <?php echo esc_html__( '插件介紹', 'sgoplus-file-share' ); ?></h2>
						<p><?php echo esc_html__( 'SGOplus File Share 是一款專為 WordPress 設計的高效檔案分享插件。它結合了安全性與極速下載體驗，支援密碼保護、角色存取控制以及伺服器級別的下載加速（X-Accel-Redirect / X-Sendfile）。', 'sgoplus-file-share' ); ?></p>
					</div>

					<div class="sgoplus-fs-grid-guide">
						<div class="sgoplus-fs-guide-card">
							<h3><span class="dashicons dashicons-index-card"></span> <?php echo esc_html__( '1. 建立檔案', 'sgoplus-file-share' ); ?></h3>
							<p><?php echo esc_html__( '在「Share Service+」選單中點擊「Add New」，上傳或輸入檔案 URL，並設定下載密碼或會員權限。', 'sgoplus-file-share' ); ?></p>
						</div>
						<div class="sgoplus-fs-guide-card">
							<h3><span class="dashicons dashicons-editor-code"></span> <?php echo esc_html__( '2. 使用短代碼', 'sgoplus-file-share' ); ?></h3>
							<p><?php echo esc_html__( '在任何頁面或文章中貼上短代碼即可嵌入檔案下載介面。', 'sgoplus-file-share' ); ?></p>
						</div>
						<div class="sgoplus-fs-guide-card">
							<h3><span class="dashicons dashicons-performance"></span> <?php echo esc_html__( '3. 效能優化', 'sgoplus-file-share' ); ?></h3>
							<p><?php echo esc_html__( '在「Settings」中開啟加速模式，可大幅降低伺服器負載並提升大檔案下載穩定性。', 'sgoplus-file-share' ); ?></p>
						</div>
					</div>

					<div class="sgoplus-fs-code-reference">
						<h2><span class="dashicons dashicons-editor-help"></span> <?php echo esc_html__( '短代碼參考', 'sgoplus-file-share' ); ?></h2>
						<div class="sgoplus-fs-code-item">
							<code>[sgoplus_file id="123"]</code>
							<p><?php echo esc_html__( '嵌入特定 ID 的單個檔案卡片。', 'sgoplus-file-share' ); ?></p>
						</div>
						<div class="sgoplus-fs-code-item">
							<code>[sgoplus_files]</code>
							<p><?php echo esc_html__( '顯示所有已發布檔案的搜尋過濾網格。', 'sgoplus-file-share' ); ?></p>
						</div>
						<div class="sgoplus-fs-code-item">
							<code>[sgoplus_files category="software"]</code>
							<p><?php echo esc_html__( '顯示特定分類下的檔案列表。', 'sgoplus-file-share' ); ?></p>
						</div>
					</div>
				</div>

				<!-- JP Content -->
				<div class="sgoplus-fs-guild-pane" id="pane-jp">
					<div class="sgoplus-fs-intro-card">
						<h2><span class="dashicons dashicons-star-filled"></span> <?php echo esc_html__( 'プラグイン紹介', 'sgoplus-file-share' ); ?></h2>
						<p><?php echo esc_html__( 'SGOplus File Share は、WordPress 用に設計されたプロフェッショナルなファイル共有ソリューションです。セキュリティと高速ダウンロード体験を兩立し、パスワード保護、ロールベースのアクセス制御、サーバーレベルのダウンロード加速（X-Accel-Redirect / X-Sendfile）をサポートしています。', 'sgoplus-file-share' ); ?></p>
					</div>

					<div class="sgoplus-fs-grid-guide">
						<div class="sgoplus-fs-guide-card">
							<h3><span class="dashicons dashicons-index-card"></span> <?php echo esc_html__( '1. ファイル作成', 'sgoplus-file-share' ); ?></h3>
							<p><?php echo esc_html__( '「Share Service+」メニューの「Add New」をクリックし、ファイルURLを入力。必要に応じてパスワードや權限を設定します。', 'sgoplus-file-share' ); ?></p>
						</div>
						<div class="sgoplus-fs-guide-card">
							<h3><span class="dashicons dashicons-editor-code"></span> <?php echo esc_html__( '2. ショートコード', 'sgoplus-file-share' ); ?></h3>
							<p><?php echo esc_html__( '固定ページや投稿にショートコードを貼り付けるだけで、洗練されたダウンロードカードが表示されます。', 'sgoplus-file-share' ); ?></p>
						</div>
						<div class="sgoplus-fs-guide-card">
							<h3><span class="dashicons dashicons-performance"></span> <?php echo esc_html__( '3. パフォーマンス', 'sgoplus-file-share' ); ?></h3>
							<p><?php echo esc_html__( '「Settings」でアクセラレーションモードを有効にすると、サーバー負荷を抑えつつ高速な通信が可能になります。', 'sgoplus-file-share' ); ?></p>
						</div>
					</div>

					<div class="sgoplus-fs-code-reference">
						<h2><span class="dashicons dashicons-editor-help"></span> <?php echo esc_html__( 'ショートコードリファレンス', 'sgoplus-file-share' ); ?></h2>
						<div class="sgoplus-fs-code-item">
							<code>[sgoplus_file id="123"]</code>
							<p><?php echo esc_html__( '特定のIDを持つ單一のファイルカードを埋め込みます。', 'sgoplus-file-share' ); ?></p>
						</div>
						<div class="sgoplus-fs-code-item">
							<code>[sgoplus_files]</code>
							<p><?php echo esc_html__( '公開されているすべてのファイルの檢索・フィルタリンググリッドを表示します。', 'sgoplus-file-share' ); ?></p>
						</div>
					</div>
				</div>

				<!-- EN Content -->
				<div class="sgoplus-fs-guild-pane" id="pane-en">
					<div class="sgoplus-fs-intro-card">
						<h2><span class="dashicons dashicons-star-filled"></span> <?php echo esc_html__( 'Plugin Introduction', 'sgoplus-file-share' ); ?></h2>
						<p><?php echo esc_html__( 'SGOplus File Share is a high-performance file sharing solution for WordPress. It combines advanced security with lightning-fast download experiences, supporting password protection, role-based access control, and server-side acceleration (X-Accel-Redirect / X-Sendfile).', 'sgoplus-file-share' ); ?></p>
					</div>

					<div class="sgoplus-fs-grid-guide">
						<div class="sgoplus-fs-guide-card">
							<h3><span class="dashicons dashicons-index-card"></span> <?php echo esc_html__( '1. Create Record', 'sgoplus-file-share' ); ?></h3>
							<p><?php echo esc_html__( 'Go to "Share Service+" -> "Add New". Upload your file or paste a URL, then set your desired protection settings.', 'sgoplus-file-share' ); ?></p>
						</div>
						<div class="sgoplus-fs-guide-card">
							<h3><span class="dashicons dashicons-editor-code"></span> <?php echo esc_html__( '2. Use Shortcodes', 'sgoplus-file-share' ); ?></h3>
							<p><?php echo esc_html__( 'Paste the shortcode into any page or post to display the premium download interface.', 'sgoplus-file-share' ); ?></p>
						</div>
						<div class="sgoplus-fs-guide-card">
							<h3><span class="dashicons dashicons-performance"></span> <?php echo esc_html__( '3. Optimization', 'sgoplus-file-share' ); ?></h3>
							<p><?php echo esc_html__( 'Enable Acceleration Mode in Settings to reduce CPU usage and provide stable downloads for large files.', 'sgoplus-file-share' ); ?></p>
						</div>
					</div>

					<div class="sgoplus-fs-code-reference">
						<h2><span class="dashicons dashicons-editor-help"></span> <?php echo esc_html__( 'Shortcode Reference', 'sgoplus-file-share' ); ?></h2>
						<div class="sgoplus-fs-code-item">
							<code>[sgoplus_file id="123"]</code>
							<p><?php echo esc_html__( 'Embeds a specific file card by ID.', 'sgoplus-file-share' ); ?></p>
						</div>
						<div class="sgoplus-fs-code-item">
							<code>[sgoplus_files]</code>
							<p><?php echo esc_html__( 'Displays a searchable grid of all published files.', 'sgoplus-file-share' ); ?></p>
						</div>
					</div>
				</div>
			</div>
			
			<div class="sgoplus-fs-guild-footer">
				<p><?php echo sprintf( 
					/* translators: %s: Support center link */
					esc_html__( '© 2026 SGOplus Group • %s', 'sgoplus-file-share' ),
					'<a href="https://sgoplus.one" target="_blank">' . esc_html__( 'Support Center', 'sgoplus-file-share' ) . '</a>'
				); ?></p>
			</div>
		</div>

		<?php
	}
}
