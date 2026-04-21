<?php

namespace SGOplus\WP_Share;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Shortcode {

	public function __construct() {
		add_shortcode( 'sgoplus_file', array( $this, 'render_shortcode' ) );
		add_shortcode( 'sgoplus_files', array( $this, 'render_file_list' ) );
	}

	public function render_shortcode( $atts ) {
		$atts = shortcode_atts( array(
			'id' => 0,
		), $atts, 'sgoplus_file' );

		$post_id = intval( $atts['id'] );
		if ( ! $post_id ) {
			return '<p>' . esc_html__( 'Error: Invalid file ID.', 'sgoplus-wp-share' ) . '</p>';
		}

		$post = get_post( $post_id );
		if ( ! $post || $post->post_type !== 'sfs_file' ) {
			return '<p>' . esc_html__( 'Error: File record not found.', 'sgoplus-wp-share' ) . '</p>';
		}

		$title = get_the_title( $post_id );
		$content = $post->post_content;
		$file_url = get_post_meta( $post_id, '_sfs_file_url', true );
		$update_log = get_post_meta( $post_id, '_sfs_update_log', true );
		$thumbnail = get_the_post_thumbnail_url( $post_id, 'medium' ) ?: 'https://via.placeholder.com/150';
		
		$is_pro = is_sfs_pro_active();

		// Check Role Access (Visual Feedback)
		$allowed_roles = get_post_meta( $post_id, '_sfs_allowed_roles', true );
		$is_members_only = ( ! empty( $allowed_roles ) && is_array( $allowed_roles ) );
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
			$today = gmdate( 'Y-m-d' );
			if ( $today > $expiry_date ) {
				$is_expired = true;
			}
		}

		$has_password = (bool) get_post_meta( $post_id, '_sfs_password', true );
		$btn_text = $has_password ? esc_html__( 'Download Protected', 'sgoplus-wp-share' ) : esc_html__( 'Download Now', 'sgoplus-wp-share' );
		$btn_class = $has_password ? 'sfs-btn-protected' : 'sfs-btn-now';

		$terms = get_the_terms( $post_id, 'sfs_category' );
		$cat_slugs = array();
		if ( $terms && ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				$cat_slugs[] = $term->slug;
			}
		}
		$cat_data = implode( ',', $cat_slugs );

		ob_start();
		?>
		<div class="sfs-file-card" id="sfs-card-<?php echo intval( $post_id ); ?>" data-categories="<?php echo esc_attr( $cat_data ); ?>">
			<!-- Card Header -->
			<div class="sfs-card-top-bar">
				<h3 class="sfs-card-title"><?php echo esc_html( $title ); ?></h3>
				<?php if ( ! empty( $update_log ) ) : ?>
					<button type="button" class="sfs-log-toggle" onclick="this.closest('.sfs-file-card').querySelector('.sfs-changelog-overlay').classList.toggle('active')">
						<?php esc_html_e( 'Log', 'sgoplus-wp-share' ); ?>
					</button>
				<?php endif; ?>
			</div>

			<!-- Card Media -->
			<div class="sfs-card-media">
				<div class="sfs-media-inner">
					<img src="<?php echo esc_url( $thumbnail ); ?>" alt="<?php echo esc_attr( $title ); ?>">
					<?php if ( $is_members_only ) : ?>
						<div class="sfs-badge-overlay"><?php esc_html_e( 'MEMBERS', 'sgoplus-wp-share' ); ?></div>
					<?php endif; ?>
					
					<!-- Changelog Overlay -->
					<?php if ( ! empty( $update_log ) ) : ?>
						<div class="sfs-changelog-overlay">
							<div class="sfs-changelog-content">
								<button type="button" class="sfs-close-log" onclick="this.closest('.sfs-changelog-overlay').classList.remove('active')">&times;</button>
								<h4><?php esc_html_e( 'Changelog', 'sgoplus-wp-share' ); ?></h4>
								<pre><?php echo esc_html( $update_log ); ?></pre>
							</div>
						</div>
					<?php endif; ?>
				</div>
			</div>

			<!-- Card Body -->
			<div class="sfs-body">
				<div class="sfs-description">
					<?php echo wp_kses_post( wp_trim_words( $content, 20 ) ); ?>
				</div>
				
				<div class="sfs-card-footer">
					<?php if ( $is_expired ) : ?>
						<div class="sfs-alert sfs-alert-error"><?php esc_html_e( 'This download link has expired.', 'sgoplus-wp-share' ); ?></div>
					<?php elseif ( $role_restricted ) : ?>
						<a href="<?php echo esc_url( wp_login_url( get_permalink() ) ); ?>" class="sfs-download-btn sfs-btn-lock">
							<span class="dashicons dashicons-lock"></span> <?php esc_html_e( 'Login to Access', 'sgoplus-wp-share' ); ?>
						</a>
					<?php else : ?>
						<form action="" method="post" class="sfs-download-form">
							<?php wp_nonce_field( 'sfs_download_file', 'sfs_download_nonce' ); ?>
							<input type="hidden" name="sfs_id" value="<?php echo intval( $post_id ); ?>">
							<input type="hidden" name="sfs_action" value="download">
							
							<?php if ( $has_password ) : ?>
								<div class="sfs-password-row">
									<input type="password" name="sfs_password" placeholder="<?php echo esc_attr__( 'Enter Password', 'sgoplus-wp-share' ); ?>" required>
								</div>
							<?php endif; ?>
							
							<button type="submit" class="sfs-download-btn <?php echo esc_attr( $btn_class ); ?>">
								<?php echo esc_html( $btn_text ); ?>
							</button>
						</form>
					<?php endif; ?>
				</div>
			</div>
		</div>

		<style>
		:root {
			--sfs-primary: #0984e3;
			--sfs-secondary: #00b894;
			--sfs-bg: #ffffff;
			--sfs-text: #2d3436;
			--sfs-muted: #636e72;
			--sfs-border: #f1f2f6;
			--sfs-card-bg: #ffffff;
			--sfs-font: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
		}

		.sfs-file-card {
			background: var(--sfs-card-bg);
			border-radius: 24px;
			box-shadow: 0 10px 40px rgba(0,0,0,0.06);
			overflow: hidden;
			font-family: var(--sfs-font);
			margin-bottom: 30px;
			transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
			display: flex;
			flex-direction: column;
			border: 1px solid rgba(0,0,0,0.03);
		}

		.sfs-file-card:hover { transform: translateY(-8px); box-shadow: 0 20px 50px rgba(0,0,0,0.1); }

		/* Card Top Bar */
		.sfs-card-top-bar { padding: 15px 25px; display: flex; justify-content: space-between; align-items: center; background: #fff; }
		.sfs-card-title { margin: 0; font-size: 1.25rem; font-weight: 800; color: var(--sfs-text); letter-spacing: -0.2px; }
		.sfs-log-toggle { 
			background: #f8f9fa; border: 1px solid #eee; padding: 4px 15px; border-radius: 10px; font-size: 0.85rem; 
			font-weight: 600; color: #666; cursor: pointer; transition: all 0.2s;
		}
		.sfs-log-toggle:hover { background: #eee; color: #333; }

		/* Card Media */
		.sfs-card-media { position: relative; width: 100%; background: #fff; padding: 0 20px; box-sizing: border-box; }
		.sfs-media-inner { width: 100%; aspect-ratio: 16/9; overflow: hidden; border-radius: 15px; position: relative; }
		.sfs-card-media img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.6s cubic-bezier(0.165, 0.84, 0.44, 1); }
		.sfs-file-card:hover .sfs-card-media img { transform: scale(1.1); }

		.sfs-badge-overlay {
			position: absolute; top: 15px; left: 15px; background: #6c5ce7; color: #fff; 
			padding: 4px 12px; border-radius: 8px; font-weight: 800; font-size: 0.75rem; letter-spacing: 1px; box-shadow: 0 4px 10px rgba(108, 92, 231, 0.3);
			z-index: 10;
		}

		/* Changelog Overlay */
		.sfs-changelog-overlay {
			position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255,255,255,0.85);
			z-index: 20; display: flex; align-items: center; justify-content: center; opacity: 0; pointer-events: none; transition: all 0.3s;
			backdrop-filter: blur(10px); border-radius: 15px;
		}
		.sfs-changelog-overlay.active { opacity: 1; pointer-events: auto; }
		.sfs-changelog-content { padding: 30px; width: 100%; height: 100%; overflow-y: auto; position: relative; }
		.sfs-close-log { position: absolute; top: 15px; right: 20px; font-size: 2rem; border: none; background: none; cursor: pointer; color: #999; }
		.sfs-changelog-content h4 { margin: 0 0 15px 0; font-weight: 800; color: var(--sfs-text); border-bottom: 2px solid #eee; padding-bottom: 10px; }
		.sfs-changelog-content pre { white-space: pre-wrap; font-family: var(--sfs-font); font-size: 0.9rem; line-height: 1.6; color: #555; }

		/* Card Body */
		.sfs-body { padding: 10px 25px 25px 25px; flex-grow: 1; display: flex; flex-direction: column; }
		.sfs-description { font-size: 0.95rem; line-height: 1.6; color: var(--sfs-muted); margin-bottom: 20px; }

		/* Card Footer / Actions */
		.sfs-card-footer { margin-top: auto; }
		.sfs-password-row { margin-bottom: 15px; }
		.sfs-password-row input { 
			width: 100%; padding: 12px 20px; border-radius: 14px; border: 2px solid #f1f2f6; background: #fdfdfd;
			font-size: 0.95rem; outline: none; transition: all 0.2s; box-sizing: border-box;
		}
		.sfs-password-row input:focus { border-color: var(--sfs-secondary); background: #fff; box-shadow: 0 0 0 4px rgba(0, 184, 148, 0.1); }

		.sfs-download-btn {
			width: 100%; padding: 16px; border: none; border-radius: 14px; color: #fff; font-size: 1.1rem;
			font-weight: 800; cursor: pointer; transition: all 0.3s; display: block; text-align: center; text-decoration: none;
		}
		.sfs-btn-now { background: #0088cc; box-shadow: 0 8px 20px rgba(0, 136, 204, 0.25); }
		.sfs-btn-protected { background: #00b894; box-shadow: 0 8px 20px rgba(0, 184, 148, 0.25); }
		.sfs-btn-lock { background: #636e72; }

		.sfs-download-btn:hover { transform: translateY(-2px); filter: brightness(1.1); box-shadow: 0 12px 25px rgba(0,0,0,0.15); }

		/* List View Header */
		.sfs-list-header { 
			display: flex; gap: 15px; margin-bottom: 40px; background: #fff; padding: 10px; border-radius: 50px;
			box-shadow: 0 5px 25px rgba(0,0,0,0.05); align-items: center; flex-wrap: wrap;
		}
		.sfs-search-input { 
			flex: 1; min-width: 200px; border: none; padding: 12px 25px; border-radius: 50px; font-size: 1rem; outline: none;
		}
		.sfs-cat-select { 
			background: #f1f2f6; border: none; padding: 12px 40px 12px 25px; border-radius: 50px; font-weight: 600; color: #444; outline: none;
			appearance: none; -webkit-appearance: none;
			background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%23666' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");
			background-repeat: no-repeat;
			background-position: right 15px center;
			cursor: pointer;
		}
		.sfs-search-btn { 
			background: #0073aa; color: #fff; border: none; padding: 12px 40px; border-radius: 50px; font-weight: 700; cursor: pointer;
		}

		/* Grid System */
		.sfs-file-list-wrapper {
			display: grid;
			grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
			gap: 30px;
			width: 100%;
		}
		
		@media (max-width: 768px) {
			.sfs-list-header { border-radius: 20px; padding: 20px; }
			.sfs-search-input, .sfs-cat-select, .sfs-search-btn { width: 100%; flex: none; }
			.sfs-file-list-wrapper { gap: 15px; }
			.sfs-file-card { margin-bottom: 15px; }
		}
		</style>
		<?php
		return ob_get_clean();
	}
	
	public function render_file_list( $atts ) {
		$atts = shortcode_atts( array(
			'category' => '',
			'limit'    => -1,
		), $atts, 'sgoplus_files' );

		$args = array(
			'post_type'      => 'sfs_file',
			'posts_per_page' => intval( $atts['limit'] ),
			'post_status'    => 'publish',
		);

		if ( ! empty( $atts['category'] ) ) {
			$args['tax_query'] = array(
				array(
					'taxonomy' => 'sfs_category',
					'field'    => 'slug',
					'terms'    => $atts['category'],
				),
			);
		}

		$query = new \WP_Query( $args );
		if ( ! $query->have_posts() ) {
			return '<p>' . esc_html__( 'No files found.', 'sgoplus-wp-share' ) . '</p>';
		}

		ob_start();
		?>
		<div class="sfs-file-list-container">
			<!-- Header / Filter Bar -->
			<div class="sfs-list-header">
				<input type="text" class="sfs-search-input" placeholder="<?php echo esc_attr__( 'Search files...', 'sgoplus-wp-share' ); ?>" id="sfs-search-field">
				<select class="sfs-cat-select" id="sfs-cat-filter">
					<option value="all"><?php esc_html_e( 'All Categories', 'sgoplus-wp-share' ); ?></option>
					<?php
					$categories = get_terms( array( 'taxonomy' => 'sfs_category', 'hide_empty' => true ) );
					foreach ( $categories as $cat ) {
						echo '<option value="' . esc_attr( $cat->slug ) . '">' . esc_html( $cat->name ) . '</option>';
					}
					?>
				</select>
				<button type="button" class="sfs-search-btn"><?php esc_html_e( 'Search', 'sgoplus-wp-share' ); ?></button>
			</div>

			<div class="sfs-file-list-wrapper">
				<?php
				while ( $query->have_posts() ) {
					$query->the_post();
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					echo $this->render_shortcode( array( 'id' => get_the_ID() ) );
				}
				?>
			</div>
		</div>
		<script>
		document.addEventListener('DOMContentLoaded', function() {
			const searchInput = document.getElementById('sfs-search-field');
			const catFilter = document.getElementById('sfs-cat-filter');
			const cards = document.querySelectorAll('.sfs-file-card');

			function filterFiles() {
				const term = searchInput.value.toLowerCase();
				const cat = catFilter.value;

				cards.forEach(card => {
					const title = card.querySelector('.sfs-card-title').textContent.toLowerCase();
					const cardCats = (card.getAttribute('data-categories') || '').split(',');
					
					const matchesSearch = title.includes(term);
					const matchesCat = (cat === 'all' || cardCats.includes(cat));

					if (matchesSearch && matchesCat) {
						card.style.display = 'flex';
					} else {
						card.style.display = 'none';
					}
				});
			}

			if (searchInput) searchInput.addEventListener('input', filterFiles);
			if (catFilter) catFilter.addEventListener('change', filterFiles);
		});
		</script>
		<?php
		wp_reset_postdata();

		return ob_get_clean();
	}
}
