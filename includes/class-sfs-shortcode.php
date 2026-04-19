<?php

class SFS_Shortcode {


	public function render_shortcode( $atts ) {
		$atts = shortcode_atts( array(
			'id' => 0,
		), $atts, 'secure_file_share' );

		$post_id = intval( $atts['id'] );
		if ( ! $post_id ) {
			return '<p>Invalid File ID.</p>';
		}

		$post = get_post( $post_id );
		if ( ! $post || $post->post_type !== 'sfs_file' ) {
			return '<p>File not found.</p>';
		}

		// Get Metadata
		$file_url = get_post_meta( $post_id, '_sfs_file_url', true );
		$update_log = get_post_meta( $post_id, '_sfs_update_log', true );
		$has_password = get_post_meta( $post_id, '_sfs_password', true );
		$screenshot = get_the_post_thumbnail_url( $post_id, 'large' );

		ob_start();
		?>
		<div class="sfs-container" style="border: 1px solid #ddd; padding: 20px; border-radius: 8px; max-width: 600px; margin: 20px auto; background: #fff; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
			<h2 style="margin-top: 0;"><?php echo esc_html( $post->post_title ); ?></h2>
			
			<?php if ( $screenshot ) : ?>
				<div class="sfs-screenshot" style="margin-bottom: 20px;">
					<img src="<?php echo esc_url( $screenshot ); ?>" alt="Screenshot" style="width: 100%; height: auto; border-radius: 4px;" />
				</div>
			<?php endif; ?>

			<div class="sfs-description" style="margin-bottom: 20px;">
				<?php echo wp_kses_post( apply_filters( 'the_content', $post->post_content ) ); ?>
			</div>

			<?php if ( $update_log ) : ?>
				<div class="sfs-update-log" style="background: #f9f9f9; padding: 10px; border-left: 4px solid #0073aa; margin-bottom: 20px;">
					<strong>Update Log:</strong><br>
					<pre style="white-space: pre-wrap; font-family: inherit; font-size: 0.9em;"><?php echo esc_html( $update_log ); ?></pre>
				</div>
			<?php endif; ?>

			<div class="sfs-download-section">
				<?php if ( $has_password ) : ?>
					<form method="POST" action="">
						<input type="hidden" name="sfs_action" value="download" />
						<input type="hidden" name="sfs_id" value="<?php echo esc_attr( $post_id ); ?>" />
						<p><label>Enter Password to Download:</label></p>
						<input type="password" name="sfs_password" required style="padding: 8px; width: 70%; border: 1px solid #ccc; border-radius: 4px;" />
						<button type="submit" style="padding: 8px 16px; background: #0073aa; color: #fff; border: none; border-radius: 4px; cursor: pointer;">Download</button>
					</form>
				<?php else : ?>
					<form method="POST" action="">
						<input type="hidden" name="sfs_action" value="download" />
						<input type="hidden" name="sfs_id" value="<?php echo esc_attr( $post_id ); ?>" />
						<button type="submit" style="padding: 12px 24px; background: #28a745; color: #fff; border: none; border-radius: 8px; cursor: pointer; font-size: 1.1em; width: 100%;">Download Now</button>
					</form>
				<?php endif; ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}
}
