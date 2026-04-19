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
		
		$args = array(
			'post_type'      => 'sfs_file',
			'posts_per_page' => -1,
			's'              => $search_query,
			'post_status'    => 'publish'
		);

		$query = new WP_Query( $args );
		
		ob_start();
		?>
		<div class="sfs-list-wrapper" style="width: 100%; max-width: 1200px; margin: 0 auto; padding: 20px; box-sizing: border-box;">
			<!-- Search Bar -->
			<div class="sfs-search-container" style="margin-bottom: 30px; text-align: center;">
				<form method="GET" action="" style="display: inline-flex; width: 100%; max-width: 600px; gap: 10px;">
					<input type="text" name="sfs_s" value="<?php echo esc_attr( $search_query ); ?>" placeholder="Search files..." style="flex-grow: 1; padding: 12px 20px; border: 2px solid #eee; border-radius: 50px; outline: none; font-size: 1em; transition: border-color 0.3s;" />
					<button type="submit" style="padding: 12px 30px; background: #0073aa; color: #fff; border: none; border-radius: 50px; cursor: pointer; font-weight: bold; transition: background 0.3s;">Search</button>
					<?php if ( $search_query ) : ?>
						<a href="<?php echo esc_url( remove_query_arg( 'sfs_s' ) ); ?>" style="padding: 12px 20px; background: #eee; color: #666; text-decoration: none; border-radius: 50px; font-size: 0.9em; display: flex; align-items: center;">Clear</a>
					<?php endif; ?>
				</form>
			</div>

			<?php if ( $query->have_posts() ) : ?>
				<div class="sfs-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 25px;">
					<?php while ( $query->have_posts() ) : $query->the_post(); ?>
						<div class="sfs-grid-item">
							<?php echo $this->render_file_card( get_the_ID(), true ); ?>
						</div>
					<?php endwhile; wp_reset_postdata(); ?>
				</div>
			<?php else : ?>
				<div style="text-align: center; padding: 50px; color: #888; background: #f9f9f9; border-radius: 12px;">
					<h3>No files found.</h3>
					<p>Try different keywords or browse our full collection.</p>
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
		<div class="sfs-container <?php echo $is_grid ? 'sfs-grid-card' : ''; ?>" style="border: 1px solid #eef0f2; padding: 25px; border-radius: 16px; width: 100%; max-width: <?php echo $is_grid ? 'none' : '600px'; ?>; margin: <?php echo $is_grid ? '0' : '20px auto'; ?>; background: #fff; box-shadow: 0 10px 30px rgba(0,0,0,0.05); font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; box-sizing: border-box; transition: transform 0.3s ease;">
			
			<div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; gap: 15px;">
				<h2 style="margin: 0; font-size: 1.4em; color: #2d3436; line-height: 1.2;"><?php echo esc_html( $post->post_title ); ?></h2>
				<?php if ( ! empty( $update_log ) ) : ?>
					<button type="button" class="sfs-open-log-<?php echo $post_id; ?>" style="padding: 6px 14px; background: #f1f3f5; color: #495057; border: 1px solid #dee2e6; border-radius: 8px; cursor: pointer; font-size: 0.85em; font-weight: 600; white-space: nowrap; transition: all 0.2s;">Log</button>
				<?php endif; ?>
			</div>
			
			<?php if ( $screenshot ) : ?>
				<div class="sfs-screenshot" style="margin-bottom: 20px; overflow: hidden; border-radius: 10px; background: #f8f9fa;">
					<img src="<?php echo esc_url( $screenshot ); ?>" alt="Screenshot" style="width: 100%; height: 200px; object-fit: cover; transition: transform 0.5s ease;" />
				</div>
			<?php endif; ?>

			<div class="sfs-description" style="margin-bottom: 25px; color: #636e72; font-size: 0.95em; line-height: 1.6;">
				<?php echo wp_kses_post( apply_filters( 'the_excerpt', $post->post_content ) ); ?>
			</div>

			<div class="sfs-download-section">
				<?php if ( $has_password ) : ?>
					<form method="POST" action="">
						<input type="hidden" name="sfs_action" value="download" />
						<input type="hidden" name="sfs_id" value="<?php echo esc_attr( $post_id ); ?>" />
						<div style="display: flex; flex-direction: column; gap: 10px;">
							<input type="password" name="sfs_password" required placeholder="Enter Password" style="padding: 12px 15px; border: 1px solid #dfe6e9; border-radius: 10px; background: #fdfdfd;" />
							<button type="submit" style="padding: 12px; background: #00b894; color: #fff; border: none; border-radius: 10px; cursor: pointer; font-weight: bold; font-size: 1em; box-shadow: 0 4px 12px rgba(0,184,148,0.2);">Download Protected File</button>
						</div>
					</form>
				<?php else : ?>
					<form method="POST" action="">
						<input type="hidden" name="sfs_action" value="download" />
						<input type="hidden" name="sfs_id" value="<?php echo esc_attr( $post_id ); ?>" />
						<button type="submit" style="padding: 14px; background: #0984e3; color: #fff; border: none; border-radius: 12px; cursor: pointer; font-size: 1em; width: 100%; font-weight: bold; box-shadow: 0 4px 12px rgba(9,132,227,0.2);">Download Now</button>
					</form>
				<?php endif; ?>
			</div>
		</div>

		<?php if ( ! empty( $update_log ) ) : ?>
			<div id="sfs-modal-<?php echo $post_id; ?>" class="sfs-modal-overlay" style="display: none; position: fixed; z-index: 100000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.4); backdrop-filter: blur(8px);">
				<div style="background: #fff; margin: 15vh auto; padding: 45px 30px 30px 30px; border-radius: 20px; width: 90%; max-width: 500px; position: relative; box-shadow: 0 20px 50px rgba(0,0,0,0.15); animation: sfs-pop 0.3s ease-out;">
					<span class="sfs-close-<?php echo $post_id; ?>" style="position: absolute; top: 15px; right: 20px; color: #b2bec3; font-size: 32px; font-weight: 300; cursor: pointer; transition: color 0.2s;">&times;</span>
					<h3 style="margin: 0 0 15px 0; font-size: 1.4em; color: #2d3436;">Update Log</h3>
					<div style="max-height: 350px; overflow-y: auto; background: #fbfbfc; padding: 20px; border-radius: 12px; border: 1px solid #f1f2f6;">
						<pre style="white-space: pre-wrap; font-family: 'Courier New', Courier, monospace; font-size: 0.9em; margin: 0; color: #2d3436; line-height: 1.5;"><?php echo esc_html( $update_log ); ?></pre>
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
		@keyframes sfs-pop { from { opacity: 0; transform: scale(0.9); } to { opacity: 1; transform: scale(1); } }
		.sfs-grid-card:hover { transform: translateY(-5px); box-shadow: 0 15px 35px rgba(0,0,0,0.08) !important; }
		.sfs-grid-card img:hover { transform: scale(1.05); }
		.sfs-close-btn:hover { color: #d63031 !important; }
		.sfs-search-container input:focus { border-color: #0984e3 !important; box-shadow: 0 0 0 4px rgba(9,132,227,0.1); }
		@media (max-width: 600px) {
			.sfs-grid { grid-template-columns: 1fr !important; }
			.sfs-container { padding: 15px !important; }
		}
		</style>
		<?php
	}
}
