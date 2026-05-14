=== Plugin Name ===
SGOplus File Share
Contributors: sgoleo, sgoplus
Tags: file sharing, secure download, password protection, downloads, cloud share
Requires at least: 6.5
Tested up to: 6.9
Stable tag: 1.2.3
Requires PHP: 8.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A high-performance WordPress plugin for sharing secure, password-protected files with professional download acceleration support.

== Description ==

SGOplus File Share is a robust and lightweight file sharing solution designed for WordPress. It provides a clean, modern interface for administrators to share files securely with visitors while optimizing server performance.

Key features include:
*   **Secure Sharing**: Easily share files from your Media Library or external URLs.
*   **Password Protection**: Protect sensitive files with hashed passwords.
*   **Performance Optimization**: Support for server-level acceleration including X-Sendfile (Apache), X-Accel-Redirect (Nginx), and X-LiteSpeed-Location.
*   **Modern UI**: Beautiful, responsive download cards with changelog overlays.
*   **Shortcode Powered**: Embed single files or full searchable lists anywhere using `[sgoplus_file]` or `[sgoplus_files]`.

== Installation ==

1. Upload the `sgoplus-file-share` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to 'Share Service+' in the admin menu to start managing your files.
4. Use the shortcode `[sgoplus_file id="123"]` to display a specific file.
5. Use `[sgoplus_files]` to show a searchable grid of all available files.

== Privacy Policy ==

SGOplus File Share is built with privacy in mind.
*   **No Tracking**: This plugin does not track user behavior or phone home to any external servers.
*   **Local Storage**: All file metadata and passwords are stored locally on your WordPress database.
*   **Data Security**: Passwords are saved using WordPress's native hashing mechanisms.
*   **No Third-Party Services**: This version of the plugin does not communicate with any third-party APIs or external services.

== Changelog ==

= 1.2.3 =
* Optimization: Converted to a standalone free version with all acceleration features unlocked.
* Compliance: Removed all external API calls and license validation logic for WordPress.org directory compliance.
* Fixed: Replaced remote assets with local fallback images.

= 1.2.2 =
* Security: Hardened all output escaping and input sanitization throughout the codebase.
* Fixed: Plugin activation fatal error resolved by adopting a class-based initialization architecture.

= 1.2.1 =
* Initial release.
