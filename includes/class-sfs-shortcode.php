<?php

namespace SGOplus\WP_Share;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Shortcode {

	public function __construct() {
		add_shortcode( 'sgo_file_share', array( $this, 'render_shortcode' ) );
	}

	public function render_shortcode( $atts ) {
		$atts = shortcode_atts( array(
			'id' => 0,
		), $atts, 'sgo_file_share' );

		$post_id = intval( $atts['id'] );
		if ( ! $post_id ) {
			return '<p>' . esc_html__( 'Error: Invalid file ID.', 'sgoplus-wp-share' ) . '</p>';
		}

		$post = get_post( $post_id );
		if ( ! $post || $post->post_type !== 'sfs_file' ) {
			return '<p>' . esc_html__( 'Error: File record not found.', 'sgoplus-wp-share' ) . '</p>';
		}

		$title = get_the_title( $post_id );
		$content = apply_filters( 'the_content', $post->post_content );
		$file_url = get_post_meta( $post_id, '_sfs_file_url', true );
		$update_log = get_post_meta( $post_id, '_sfs_update_log', true );
		$thumbnail = get_the_post_thumbnail_url( $post_id, 'medium' ) ?: 'https://via.placeholder.com/150';
		
		$is_pro = is_sfs_pro_active();

		// Check Role Access (Visual Feedback)
		$allowed_roles = get_post_meta( $post_id, '_sfs_allowed_roles', true );
		$role_restricted = false;
		if ( ! empty( $allowed_roles ) && is_array( $allowed_roles ) ) {
			if ( ! is_user_logged_in() ) {
				$role_restricted = true;
			} else {
				$user = wp_get_current_user();
				$user_roles = (array) $user->roles;
				$has_access = false;
				foreach ( $allowed_roles as $role ) {
					if ( in_array( $role, $user_roles ) ) {
						$has_access = true;
						break;
					}
				}
				if ( ! $has_access && ! current_user_can( 'administrator' ) ) {
					$role_restricted = true;
				}
			}
		}

		// Check Expiry
		$expiry_date = get_post_meta( $post_id, '_sfs_expiry_date', true );
		$is_expired = false;
		if ( ! empty( $expiry_date ) ) {
			$today = date( 'Y-m-d' );
			if ( $today > $expiry_date ) {
				$is_expired = true;
			}
		}

		ob_start();
		?>
		<div class="sfs-file-card" id="sfs-card-<?php echo $post_id; ?>">
			<div class="sfs-header">
				<div class="sfs-thumbnail">
					<img src="<?php echo esc_url( $thumbnail ); ?>" alt="<?php echo esc_attr( $title ); ?>">
					<?php if ( $role_restricted ) : ?>
						<div class="sfs-badge-overlay"><?php esc_html_e( 'MEMBERS', 'sgoplus-wp-share' ); ?></div>
					<?php endif; ?>
				</div>
				<div class="sfs-info">
					<h3 class="sfs-title"><?php echo esc_html( $title ); ?></h3>
					<div class="sfs-meta">
						<span class="sfs-version-tag"><?php esc_html_e( 'Latest Build', 'sgoplus-wp-share' ); ?></span>
						<span class="sfs-update-date"><?php echo get_the_modified_date( 'M d, Y', $post_id ); ?></span>
					</div>
				</div>
			</div>

			<div class="sfs-body">
				<div class="sfs-description">
					<?php echo wp_kses_post( $content ); ?>
				</div>
				
				<?php if ( ! empty( $update_log ) ) : ?>
					<div class="sfs-changelog">
						<h4><?php esc_html_e( 'Changelog', 'sgoplus-wp-share' ); ?></h4>
						<pre><?php echo esc_html( $update_log ); ?></pre>
					</div>
				<?php endif; ?>
			</div>

			<div class="sfs-footer">
				<?php if ( $is_expired ) : ?>
					<div class="sfs-alert sfs-alert-error"><?php esc_html_e( 'This download link has expired.', 'sgoplus-wp-share' ); ?></div>
				<?php elseif ( $role_restricted ) : ?>
					<div class="sfs-alert sfs-alert-warning"><?php esc_html_e( 'Members Only Area', 'sgoplus-wp-share' ); ?></div>
					<a href="<?php echo wp_login_url( get_permalink() ); ?>" class="sfs-download-btn sfs-btn-lock">
						<span class="dashicons dashicons-lock"></span> <?php esc_html_e( 'Login to Access', 'sgoplus-wp-share' ); ?>
					</a>
				<?php else : ?>
					<form action="" method="post" class="sfs-download-form">
						<?php wp_nonce_field( 'sfs_download_file', 'sfs_download_nonce' ); ?>
						<input type="hidden" name="sfs_id" value="<?php echo $post_id; ?>">
						<input type="hidden" name="sfs_action" value="download">
						
						<?php if ( get_post_meta( $post_id, '_sfs_password', true ) ) : ?>
							<div class="sfs-password-row">
								<span class="dashicons dashicons-shield-alt"></span>
								<input type="password" name="sfs_password" placeholder="<?php echo esc_attr__( 'Enter password to unlock', 'sgoplus-wp-share' ); ?>" required>
							</div>
						<?php endif; ?>
						
						<button type="submit" class="sfs-download-btn">
							<span class="dashicons dashicons-download"></span> <?php esc_html_e( 'Download Now', 'sgoplus-wp-share' ); ?>
						</button>
					</form>
				<?php endif; ?>
			</div>
		</div>

		<style>
		:root {
			--sfs-primary: #00d2ff;
			--sfs-secondary: #3a7bd5;
			--sfs-bg: #ffffff;
			--sfs-text: #2d3436;
			--sfs-muted: #636e72;
			--sfs-border: #f1f2f6;
		}

		.sfs-file-card {
			background: var(--sfs-bg);
			border: 1px solid var(--sfs-border);
			border-radius: 16px;
			box-shadow: 0 10px 30px rgba(0,0,0,0.05);
			overflow: hidden;
			font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
			margin: 20px 0;
			max-width: 100%;
			transition: transform 0.3s ease;
		}

		.sfs-file-card:hover {
			transform: translateY(-5px);
		}

		.sfs-header {
			display: flex;
			padding: 20px;
			background: linear-gradient(to right, #f8f9fa, #ffffff);
			border-bottom: 1px solid var(--sfs-border);
			align-items: center;
			gap: 20px;
		}

		.sfs-thumbnail {
			width: 80px;
			height: 80px;
			flex-shrink: 0;
			position: relative;
		}

		.sfs-thumbnail img {
			width: 100%;
			height: 100%;
			object-fit: cover;
			border-radius: 12px;
			box-shadow: 0 4px 10px rgba(0,0,0,0.1);
		}

		.sfs-badge-overlay {
			position: absolute;
			top: -5px;
			left: -5px;
			background: #ff7675;
			color: white;
			font-size: 8px;
			font-weight: 900;
			padding: 2px 6px;
			border-radius: 4px;
			box-shadow: 0 2px 5px rgba(255,118,117,0.3);
			letter-spacing: 0.5px;
		}

		.sfs-info { flex: 1; }
		.sfs-title { margin: 0 0 8px 0; font-size: 1.4em; color: var(--sfs-text); font-weight: 700; }
		
		.sfs-meta { display: flex; align-items: center; gap: 10px; }
		.sfs-version-tag { background: #e3f2fd; color: #1976d2; font-size: 0.75em; font-weight: 700; padding: 2px 8px; border-radius: 20px; text-transform: uppercase; }
		.sfs-update-date { font-size: 0.85em; color: var(--sfs-muted); }

		.sfs-body { padding: 20px; }
		.sfs-description { font-size: 0.95em; line-height: 1.6; color: var(--sfs-muted); margin-bottom: 20px; }
		
		.sfs-changelog {
			background: #fdfdfd;
			border: 1px dashed #dfe6e9;
			padding: 15px;
			border-radius: 10px;
		}
		.sfs-changelog h4 { margin: 0 0 10px 0; font-size: 0.9em; text-transform: uppercase; color: var(--sfs-text); opacity: 0.7; }
		.sfs-changelog pre { margin: 0; font-size: 0.85em; white-space: pre-wrap; font-family: inherit; color: #4b6584; }

		.sfs-footer { padding: 20px; background: #fafafa; border-top: 1px solid var(--sfs-border); }
		
		.sfs-alert { padding: 12px; border-radius: 8px; margin-bottom: 15px; font-size: 0.9em; font-weight: 600; text-align: center; }
		.sfs-alert-warning { background: #fff3cd; color: #856404; }
		.sfs-alert-error { background: #f8d7da; color: #721c24; }

		.sfs-password-row {
			display: flex;
			align-items: center;
			background: #fff;
			border: 2px solid #dfe6e9;
			border-radius: 10px;
			padding: 0 15px;
			margin-bottom: 15px;
			transition: border-color 0.3s;
		}
		.sfs-password-row:focus-within { border-color: var(--sfs-secondary); }
		.sfs-password-row .dashicons { color: #b2bec3; }
		.sfs-password-row input {
			border: none;
			padding: 12px;
			width: 100%;
			font-size: 0.95em;
			outline: none;
		}

		.sfs-download-btn {
			width: 100%;
			padding: 15px;
			border: none;
			border-radius: 10px;
			background: linear-gradient(135deg, var(--sfs-primary) 0%, var(--sfs-secondary) 100%);
			color: white;
			font-size: 1.1em;
			font-weight: 700;
			cursor: pointer;
			display: flex;
			align-items: center;
			justify-content: center;
			gap: 10px;
			transition: all 0.3s;
			box-shadow: 0 4px 15px rgba(0, 210, 255, 0.3);
			text-decoration: none;
		}

		.sfs-download-btn:hover {
			transform: translateY(-2px);
			box-shadow: 0 6px 20px rgba(0, 210, 255, 0.4);
			filter: brightness(1.1);
		}

		.sfs-btn-lock { background: #636e72; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
		.sfs-btn-lock:hover { box-shadow: 0 6px 20px rgba(0,0,0,0.15); }
		</style>
		<?php
		return ob_get_clean();
	}
}
