<?php

namespace SGOplus\File_Share;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Shortcode {

	public function __construct() {
		add_shortcode( 'sgoplus_file', array( $this, 'render_shortcode' ) );
		add_shortcode( 'sgoplus_files', array( $this, 'render_file_list' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );
	}

	public function enqueue_frontend_assets() {
		wp_enqueue_style( 'sgoplus-fs-public-style', SGOPLUS_FS_URL . 'assets/sgoplus-fs-public.css', array(), SGOPLUS_FS_VERSION );
		wp_enqueue_script( 'sgoplus-fs-public-script', SGOPLUS_FS_URL . 'assets/sgoplus-fs-public.js', array(), SGOPLUS_FS_VERSION, true );
	}

	public function render_shortcode( $atts ) {
		$atts = shortcode_atts( array(
			'id' => 0,
		), $atts, 'sgoplus_file' );

		$post_id = intval( $atts['id'] );
		if ( ! $post_id ) {
			return '<p>' . esc_html__( 'Error: Invalid file ID.', 'sgoplus-file-share' ) . '</p>';
		}

		$post = get_post( $post_id );
		if ( ! $post || $post->post_type !== 'sgoplus_fs_file' ) {
			return '<p>' . esc_html__( 'Error: File record not found.', 'sgoplus-file-share' ) . '</p>';
		}

		$title = get_the_title( $post_id );
		$content = $post->post_content;
		$file_url = get_post_meta( $post_id, '_sgoplus_fs_file_url', true );
		$update_log = get_post_meta( $post_id, '_sgoplus_fs_update_log', true );
		$thumbnail = get_the_post_thumbnail_url( $post_id, 'medium' ) ?: SGOPLUS_FS_URL . 'assets/placeholder.png';
		
		$is_pro = sgoplus_fs_is_pro_active();

		$role_restricted = false;
		$is_expired = false;
		$is_members_only = false;

		$has_password = (bool) get_post_meta( $post_id, '_sgoplus_fs_password', true );
		$btn_text = $has_password ? esc_html__( 'Download Protected', 'sgoplus-file-share' ) : esc_html__( 'Download Now', 'sgoplus-file-share' );
		$btn_class = $has_password ? 'sgoplus-fs-btn-protected' : 'sgoplus-fs-btn-now';

		$terms = get_the_terms( $post_id, 'sgoplus_fs_category' );
		$cat_slugs = array();
		if ( $terms && ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				$cat_slugs[] = $term->slug;
			}
		}
		$cat_data = implode( ',', $cat_slugs );

		ob_start();
		?>
		<div class="sgoplus-fs-file-card" id="sgoplus-fs-card-<?php echo esc_attr( intval( $post_id ) ); ?>" data-categories="<?php echo esc_attr( $cat_data ); ?>">
			<!-- Card Header -->
			<div class="sgoplus-fs-card-top-bar">
				<h3 class="sgoplus-fs-card-title"><?php echo esc_html( $title ); ?></h3>
				<?php if ( ! empty( $update_log ) ) : ?>
					<button type="button" class="sgoplus-fs-log-toggle" onclick="this.closest('.sgoplus-fs-file-card').querySelector('.sgoplus-fs-changelog-overlay').classList.toggle('active')">
						<?php esc_html_e( 'Log', 'sgoplus-file-share' ); ?>
					</button>
				<?php endif; ?>
			</div>

			<!-- Card Media -->
			<div class="sgoplus-fs-card-media">
				<div class="sgoplus-fs-media-inner">
					<img src="<?php echo esc_url( $thumbnail ); ?>" alt="<?php echo esc_attr( $title ); ?>">
					
					<!-- Changelog Overlay -->
					<?php if ( ! empty( $update_log ) ) : ?>
						<div class="sgoplus-fs-changelog-overlay">
							<div class="sgoplus-fs-changelog-content">
								<button type="button" class="sgoplus-fs-close-log" onclick="this.closest('.sgoplus-fs-changelog-overlay').classList.remove('active')">&times;</button>
								<h4><?php esc_html_e( 'Changelog', 'sgoplus-file-share' ); ?></h4>
								<pre><?php echo esc_html( $update_log ); ?></pre>
							</div>
						</div>
					<?php endif; ?>
				</div>
			</div>

			<!-- Card Body -->
			<div class="sgoplus-fs-body">
				<div class="sgoplus-fs-description">
					<?php echo wp_kses_post( wp_trim_words( $content, 20 ) ); ?>
				</div>
				
				<div class="sgoplus-fs-card-footer">
					<?php if ( $is_expired ) : ?>
						<div class="sgoplus-fs-alert sgoplus-fs-alert-error"><?php esc_html_e( 'This download link has expired.', 'sgoplus-file-share' ); ?></div>
					<?php elseif ( $role_restricted ) : ?>
						<a href="<?php echo esc_url( wp_login_url( get_permalink() ) ); ?>" class="sgoplus-fs-download-btn sgoplus-fs-btn-lock">
							<span class="dashicons dashicons-lock"></span> <?php esc_html_e( 'Login to Access', 'sgoplus-file-share' ); ?>
						</a>
					<?php else : ?>
						<form action="" method="post" class="sgoplus-fs-download-form">
							<?php wp_nonce_field( 'sgoplus_fs_download_file', 'sgoplus_fs_download_nonce' ); ?>
							<input type="hidden" name="sgoplus_fs_id" value="<?php echo esc_attr( intval( $post_id ) ); ?>">
							<input type="hidden" name="sgoplus_fs_action" value="download">
							
							<?php if ( $has_password ) : ?>
								<div class="sgoplus-fs-password-row">
									<input type="password" name="sgoplus_fs_password" placeholder="<?php echo esc_attr__( 'Enter Password', 'sgoplus-file-share' ); ?>" required>
								</div>
							<?php endif; ?>
							
							<button type="submit" class="sgoplus-fs-download-btn <?php echo esc_attr( $btn_class ); ?>">
								<?php echo esc_html( $btn_text ); ?>
							</button>
						</form>
					<?php endif; ?>
				</div>
			</div>
		</div>

		<?php
		return ob_get_clean();
	}
	
	public function render_file_list( $atts ) {
		$atts = shortcode_atts( array(
			'category' => '',
			'limit'    => -1,
		), $atts, 'sgoplus_files' );

		$args = array(
			'post_type'      => 'sgoplus_fs_file',
			'posts_per_page' => intval( $atts['limit'] ),
			'post_status'    => 'publish',
		);

		if ( ! empty( $atts['category'] ) ) {
			$args['tax_query'] = array(
				array(
					'taxonomy' => 'sgoplus_fs_category',
					'field'    => 'slug',
					'terms'    => $atts['category'],
				),
			);
		}

		$query = new \WP_Query( $args );
		if ( ! $query->have_posts() ) {
			return '<p>' . esc_html__( 'No files found.', 'sgoplus-file-share' ) . '</p>';
		}

		ob_start();
		?>
		<div class="sgoplus-fs-file-list-container">
			<!-- Header / Filter Bar -->
			<div class="sgoplus-fs-list-header">
				<input type="text" class="sgoplus-fs-search-input" placeholder="<?php echo esc_attr__( 'Search files...', 'sgoplus-file-share' ); ?>" id="sgoplus-fs-search-field">
				<select class="sgoplus-fs-cat-select" id="sgoplus-fs-cat-filter">
					<option value="all"><?php esc_html_e( 'All Categories', 'sgoplus-file-share' ); ?></option>
					<?php
					$categories = get_terms( array( 'taxonomy' => 'sgoplus_fs_category', 'hide_empty' => true ) );
					foreach ( $categories as $cat ) {
						echo '<option value="' . esc_attr( $cat->slug ) . '">' . esc_html( $cat->name ) . '</option>';
					}
					?>
				</select>
				<button type="button" class="sgoplus-fs-search-btn"><?php esc_html_e( 'Search', 'sgoplus-file-share' ); ?></button>
			</div>

			<div class="sgoplus-fs-file-list-wrapper">
				<?php
				while ( $query->have_posts() ) {
					$query->the_post();
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					echo $this->render_shortcode( array( 'id' => get_the_ID() ) );
				}
				?>
			</div>
		</div>
		<?php
		wp_reset_postdata();

		return ob_get_clean();
	}
}
