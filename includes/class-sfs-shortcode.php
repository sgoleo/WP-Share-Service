<?php

class SFS_Shortcode {

	public function __construct() {
		// Single file shortcode
		add_shortcode( 'sgo_file_share', array( $this, 'render_single_file' ) );
		// List and search shortcode
		add_shortcode( 'sgo_file_list', array( $this, 'render_file_list' ) );
		
		// Add some basic CSS for responsiveness and premium look
		add_action( 'wp_head', array( $this, 'add_inline_css' ) );
	}

	/**
	 * Single File Shortcode [sgo_file_share id="123"]
	 */
	public function render_single_file( $atts ) {
		$atts = shortcode_atts( array(
			'id' => 0,
		), $atts, 'sgo_file_share' );

		$post_id = intval( $atts['id'] );
		if ( ! $post_id ) return '<p>Invalid File ID.</p>';

		return $this->render_file_card( $post_id );
	}

	/**
	 * List and Search Shortcode [sgo_file_list]
	 */
	public function render_file_list( $atts ) {
		$search_query = isset( $_GET['sfs_s'] ) ? sanitize_text_field( $_GET['sfs_s'] ) : '';
		$cat_filter = isset( $_GET['sfs_cat'] ) ? sanitize_text_field( $_GET['sfs_cat'] ) : '';
		
		$args = array(
			'post_type'      => 'sfs_file',
			'posts_per_page' => -1,
			's'              => $search_query,
			'post_status'    => 'publish'
		);

		if ( ! empty( $cat_filter ) ) {
			$args['tax_query'] = array(
				array(
					'taxonomy' => 'sfs_category',
					'field'    => 'slug',
					'terms'    => $cat_filter,
				),
			);
		}

		$query = new WP_Query( $args );
		
		ob_start();
		?>
		<div class="sfs-list-wrapper" style="width: 100%; margin: 0 auto; padding: 20px; box-sizing: border-box;">
			<!-- Search & Filter Bar -->
			<div class="sfs-search-container" style="margin-bottom: 40px; text-align: center;">
				<form method="GET" action="" class="sfs-search-form" style="display: flex; flex-wrap: wrap; justify-content: center; gap: 15px; width: 100%; max-width: 900px; margin: 0 auto;">
					<div style="flex-grow: 2; min-width: 250px; position: relative;">
						<input type="text" name="sfs_s" value="<?php echo esc_attr( $search_query ); ?>" placeholder="Search files..." style="width: 100%; padding: 14px 25px; border: 2px solid #eee; border-radius: 50px; outline: none; font-size: 1em; transition: all 0.3s; box-sizing: border-box;" />
					</div>
					
					<div style="flex-grow: 1; min-width: 180px;">
						<select name="sfs_cat" onchange="this.form.submit()" style="width: 100%; padding: 14px 20px; border: 2px solid #eee; border-radius: 50px; outline: none; font-size: 1em; appearance: none; background: url('data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A//www.w3.org/2000/svg%22%20width%3D%22292.4%22%20height%3D%22292.4%22%3E%3Cpath%20fill%3D%22%23666%22%20d%3D%22M287%2069.4a17.6%2017.6%200%200%200-13-5.4H18.4c-5%200-9.3%201.8-12.9%205.4A17.6%2017.6%200%200%200%200%2082.2c0%205%201.8%209.3%205.4%2012.9l128%20127.9c3.6%203.6%207.8%205.4%2012.8%205.4s9.2-1.8%2012.8-5.4L287%2095c3.5-3.5%205.4-7.8%205.4-12.8%200-5-1.9-9.2-5.5-12.8z%22/%3E%3C/svg%3E') no-repeat right 15px center; background-size: 12px; box-sizing: border-box;">
							<option value="">All Categories</option>
							<?php
							$terms = get_terms( array( 'taxonomy' => 'sfs_category', 'hide_empty' => true ) );
							if ( ! is_wp_error( $terms ) ) {
								foreach ( $terms as $term ) {
									echo '<option value="' . esc_attr( $term->slug ) . '" ' . selected( $cat_filter, $term->slug, false ) . '>' . esc_html( $term->name ) . '</option>';
								}
							}
							?>
						</select>
					</div>
					
					<button type="submit" style="padding: 14px 35px; background: #0073aa; color: #fff; border: none; border-radius: 50px; cursor: pointer; font-weight: bold; transition: all 0.3s; font-size: 1em;">Search</button>
					
					<?php if ( $search_query || $cat_filter ) : ?>
						<a href="<?php echo esc_url( remove_query_arg( array( 'sfs_s', 'sfs_cat' ) ) ); ?>" style="padding: 14px 20px; background: #f1f3f5; color: #495057; text-decoration: none; border-radius: 50px; font-size: 0.9em; display: flex; align-items: center; border: 1px solid #dee2e6;">Reset</a>
					<?php endif; ?>
				</form>
			</div>

			<?php if ( $query->have_posts() ) : ?>
				<div class="sfs-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 30px;">
					<?php while ( $query->have_posts() ) : $query->the_post(); ?>
						<div class="sfs-grid-item">
							<?php echo $this->render_file_card( get_the_ID(), true ); ?>
						</div>
					<?php endwhile; wp_reset_postdata(); ?>
				</div>
			<?php else : ?>
				<div style="text-align: center; padding: 60px 20px; color: #888; background: #fff; border: 1px dashed #ddd; border-radius: 20px;">
					<div style="font-size: 40px; margin-bottom: 20px;">🔍</div>
					<h3 style="margin: 0 0 10px 0; color: #333;">No files matched your criteria</h3>
					<p style="margin: 0;">Please try different search terms or select another category.</p>
				</div>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Helper: Render a single file card
	 */
	private function render_file_card( $post_id, $is_grid = false ) {
		$post = get_post( $post_id );
		if ( ! $post || $post->post_type !== 'sfs_file' ) return '';

		$file_url = get_post_meta( $post_id, '_sfs_file_url', true );
		$update_log = get_post_meta( $post_id, '_sfs_update_log', true );
		$has_password = get_post_meta( $post_id, '_sfs_password', true );
		$screenshot = get_the_post_thumbnail_url( $post_id, 'large' );

		ob_start();
		?>
		<div class="sfs-container <?php echo $is_grid ? 'sfs-grid-card' : ''; ?>" style="border: 1px solid #eef0f2; padding: 25px; border-radius: 20px; width: 100%; max-width: <?php echo $is_grid ? 'none' : '600px'; ?>; margin: <?php echo $is_grid ? '0' : '20px auto'; ?>; background: #fff; box-shadow: 0 12px 35px rgba(0,0,0,0.04); font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; box-sizing: border-box; transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);">
			
			<div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; gap: 15px;">
				<h2 style="margin: 0; font-size: 1.35em; color: #1a1a1a; line-height: 1.3; font-weight: 700;"><?php echo esc_html( $post->post_title ); ?></h2>
				<?php if ( ! empty( $update_log ) ) : ?>
					<button type="button" class="sfs-open-log-<?php echo $post_id; ?>" style="padding: 7px 15px; background: #f8f9fa; color: #555; border: 1px solid #e0e0e0; border-radius: 10px; cursor: pointer; font-size: 0.8em; font-weight: 700; white-space: nowrap; transition: all 0.2s;">Log</button>
				<?php endif; ?>
			</div>
			
			<?php if ( $screenshot ) : ?>
				<div class="sfs-screenshot" style="margin-bottom: 5px; overflow: hidden; border-radius: 15px; background: #f8f9fa; aspect-ratio: 16/9;">
					<img src="<?php echo esc_url( $screenshot ); ?>" alt="Screenshot" style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.6s ease;" />
				</div>
			<?php endif; ?>

			<div class="sfs-description" style="margin-bottom: 25px; color: #555; font-size: 0.95em; line-height: 1.7; height: 3.4em; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;">
				<?php echo wp_kses_post( apply_filters( 'the_excerpt', $post->post_content ) ); ?>
			</div>

			<div class="sfs-download-section">
				<?php if ( $has_password ) : ?>
					<form method="POST" action="">
						<input type="hidden" name="sfs_action" value="download" />
						<input type="hidden" name="sfs_id" value="<?php echo esc_attr( $post_id ); ?>" />
						<div style="display: flex; flex-direction: column; gap: 12px;">
							<input type="password" name="sfs_password" required placeholder="Enter Password" style="padding: 14px 18px; border: 1px solid #eee; border-radius: 12px; background: #fafafa; font-size: 0.95em;" />
							<button type="submit" style="padding: 14px; background: #00b894; color: #fff; border: none; border-radius: 12px; cursor: pointer; font-weight: 700; font-size: 1em; box-shadow: 0 8px 20px rgba(0,184,148,0.2); transition: all 0.3s;">Download Protected</button>
						</div>
					</form>
				<?php else : ?>
					<form method="POST" action="">
						<input type="hidden" name="sfs_action" value="download" />
						<input type="hidden" name="sfs_id" value="<?php echo esc_attr( $post_id ); ?>" />
						<button type="submit" style="padding: 15px; background: #0984e3; color: #fff; border: none; border-radius: 15px; cursor: pointer; font-size: 1.05em; width: 100%; font-weight: 700; box-shadow: 0 8px 25px rgba(9,132,227,0.25); transition: all 0.3s;">Download Now</button>
					</form>
				<?php endif; ?>
			</div>
		</div>

		<?php if ( ! empty( $update_log ) ) : ?>
			<div id="sfs-modal-<?php echo $post_id; ?>" class="sfs-modal-overlay" style="display: none; position: fixed; z-index: 100000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); backdrop-filter: blur(10px);">
				<div style="background: #fff; margin: 12vh auto; padding: 50px 35px 35px 35px; border-radius: 25px; width: 92%; max-width: 550px; position: relative; box-shadow: 0 30px 70px rgba(0,0,0,0.2); animation: sfs-pop 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);">
					<span class="sfs-close-<?php echo $post_id; ?>" style="position: absolute; top: 20px; right: 25px; color: #adb5bd; font-size: 36px; font-weight: 200; cursor: pointer; transition: all 0.2s; line-height: 1;">&times;</span>
					<h3 style="margin: 0 0 20px 0; font-size: 1.6em; color: #1a1a1a; font-weight: 700;">Update Log</h3>
					<div style="max-height: 400px; overflow-y: auto; background: #fcfcfc; padding: 25px; border-radius: 15px; border: 1px solid #f1f1f1;">
						<pre style="white-space: pre-wrap; font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace; font-size: 0.9em; margin: 0; color: #444; line-height: 1.6;"><?php echo esc_html( $update_log ); ?></pre>
					</div>
				</div>
			</div>
			<script>
			(function() {
				var modal = document.getElementById("sfs-modal-<?php echo $post_id; ?>");
				var btn = document.querySelector(".sfs-open-log-<?php echo $post_id; ?>");
				var span = document.querySelector(".sfs-close-<?php echo $post_id; ?>");
				btn.onclick = function() { modal.style.display = "block"; document.body.style.overflow = "hidden"; }
				span.onclick = function() { modal.style.display = "none"; document.body.style.overflow = "auto"; }
				window.addEventListener('click', function(e) { if (e.target == modal) { modal.style.display = "none"; document.body.style.overflow = "auto"; } });
			})();
			</script>
		<?php endif; ?>
		<?php
		return ob_get_clean();
	}

	public function add_inline_css() {
		?>
		<style>
		@keyframes sfs-pop { from { opacity: 0; transform: translateY(20px) scale(0.95); } to { opacity: 1; transform: translateY(0) scale(1); } }
		.sfs-grid-card:hover { transform: translateY(-8px); box-shadow: 0 20px 45px rgba(0,0,0,0.1) !important; }
		.sfs-grid-card img:hover { transform: scale(1.06); }
		.sfs-close-btn:hover, .sfs-modal-overlay span:hover { color: #2d3436 !important; transform: rotate(90deg); }
		.sfs-search-container input:focus, .sfs-search-container select:focus { border-color: #0984e3 !important; background: #fff !important; }
		.sfs-download-section button:active { transform: scale(0.98); }
		.sfs-description p { margin-top: 0; margin-bottom: 10px; }
		@media (max-width: 768px) {
			.sfs-search-form { flex-direction: column; align-items: stretch; }
			.sfs-search-form > div, .sfs-search-form button { width: 100% !important; }
			.sfs-grid { grid-template-columns: 1fr !important; }
		}
		</style>
		<?php
	}
}
