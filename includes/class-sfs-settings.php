<?php

class SFS_Settings {

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_settings_menu' ) );
	}

	public function add_settings_menu() {
		add_submenu_page(
			'edit.php?post_type=sfs_file',
			'SFS Settings',
			'Settings',
			'manage_options',
			'sfs-settings',
			array( $this, 'render_settings_page' )
		);
	}

	public function render_settings_page() {
		?>
		<div class="wrap" style="background: #f0f0f1; padding: 20px; border-radius: 12px;">
			<h1 style="font-weight: 700; color: #1d2327;">Secure File Share Settings</h1>
			<p style="color: #646970;">Manage your secure file sharing preferences and developer information.</p>
			<hr style="border: 0; border-top: 1px solid #dcdcde; margin: 20px 0;">
			
			<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 20px;">
				<!-- Developer Card -->
				<div style="background: #fff; padding: 30px; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border-top: 5px solid #0073aa;">
					<h2 style="margin-top: 0; font-size: 1.5em; display: flex; align-items: center; gap: 10px;">
						<span class="dashicons dashicons-businessman" style="font-size: 1.2em; width: auto; height: auto;"></span>
						Developer Info
					</h2>
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><strong>官方網站</strong></th>
							<td><a href="https://sgoplus.one" target="_blank" style="text-decoration: none; color: #0073aa;">sgoplus.one</a></td>
						</tr>
						<tr>
							<th scope="row"><strong>Github</strong></th>
							<td><a href="https://github.com/sgoleo" target="_blank" style="text-decoration: none; color: #0073aa;">@sgoleo</a></td>
						</tr>
						<tr>
							<th scope="row"><strong>Discord</strong></th>
							<td><a href="https://discord.gg/WnkEKkZYFY" target="_blank" style="text-decoration: none; color: #0073aa;">Join Community</a></td>
						</tr>
					</table>
					<div style="margin-top: 20px; padding: 15px; background: #f6f7f7; border-radius: 10px; font-size: 0.9em; color: #50575e; line-height: 1.6;">
						This plugin is maintained by <strong>SGOplus</strong>. We provide premium WordPress solutions and custom development services.
					</div>
				</div>

				<!-- Quick Actions Card -->
				<div style="background: #fff; padding: 30px; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border-top: 5px solid #28a745;">
					<h2 style="margin-top: 0; font-size: 1.5em; display: flex; align-items: center; gap: 10px;">
						<span class="dashicons dashicons-admin-links" style="font-size: 1.2em; width: auto; height: auto;"></span>
						Quick Actions
					</h2>
					<p>Quickly manage your files and categories using the links below:</p>
					<div style="display: flex; flex-direction: column; gap: 10px; margin-top: 20px;">
						<a href="edit.php?post_type=sfs_file" class="button button-primary button-large" style="text-align: center; justify-content: center; border-radius: 8px;">Manage Secure Files</a>
						<a href="edit-tags.php?taxonomy=sfs_category&post_type=sfs_file" class="button button-secondary button-large" style="text-align: center; justify-content: center; border-radius: 8px;">Manage Categories</a>
						<a href="post-new.php?post_type=sfs_file" class="button button-large" style="text-align: center; justify-content: center; border-radius: 8px;">Add New File</a>
					</div>
					<div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee;">
						<p><strong>Plugin Version:</strong> <?php echo SFS_VERSION; ?></p>
						<p><strong>Environment:</strong> Apache 2.4 / Nginx Compatible</p>
					</div>
				</div>
			</div>

			<div style="margin-top: 40px; text-align: center; color: #a7aaad;">
				<p>&copy; 2026 SGOplus. Crafted with ❤️ for the WordPress community.</p>
			</div>
		</div>
		<?php
	}
}
