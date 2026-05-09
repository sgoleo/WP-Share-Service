=== Plugin Name ===
SGOplus File Share

Contributors: sgoleo, sgoplus
Tags: file sharing, secure download, password protection
Requires at least: 6.5
Tested up to: 6.9
Stable tag: 1.2.3
Requires PHP: 8.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A secure and high-performance WordPress plugin for sharing password-protected files with advanced download acceleration support.

== Description ==

SGOplus File Share is a professional-grade file sharing solution for WordPress. It allows administrators to securely share files with password protection, role-based access control, and download limits. 

Key features include:
*   Secure file sharing with password protection.
*   Advanced download acceleration (X-Sendfile, X-Accel-Redirect, X-LiteSpeed-Location).
*   Download analytics and logs (PRO).
*   Role-based access restrictions.
*   File expiration dates and download limits.

== Installation ==

1. Upload the `sgoplus-file-share` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to 'Share Service+' in the admin menu to start sharing files.
4. Use the shortcode `[sgoplus_file id="123"]` to display the download card on any page or post.
5. Use the shortcode `[sgoplus_files category="slug" limit="5"]` to list multiple files.

== External Services ==

SGOplus File Share utilizes the following third-party services to enhance plugin functionality:

1. Software License Manager (virduct.com)
*   Purpose: Used to validate PRO license keys and activate premium features.
*   Data Sent: License key, site URL, and plugin identifier.
*   Conditions: Data is sent only when a license key is entered or verified in the settings page.
*   Links: [Terms and Privacy Policy](https://sgoplus.one/policy)

2. Cloudflare (HTTP_CF_IPCOUNTRY)
*   Purpose: If your site is behind Cloudflare, the plugin may read the `HTTP_CF_IPCOUNTRY` header to record the downloader's country in the PRO download logs.
*   Data Received: Country code provided by Cloudflare.
*   Conditions: Only active for PRO users using the logging feature.

== Privacy Policy ==

SGOplus File Share is committed to user privacy. 
*   **License Validation**: When you activate a PRO license, your site URL and license key are sent to virduct.com to verify eligibility. No personal user data is transmitted during this process.
*   **Local Data**: All download logs, file metadata, and passwords are stored locally on your WordPress database and are never shared with external parties.
*   **Opt-in**: PRO features are entirely optional. If you do not enter a license key, no external communication with our license server will occur.

== Changelog ==

= 1.2.3 =
* Fixed: Replaced all external image fallback URLs (via.placeholder.com, gravatar.com) with locally bundled assets to comply with WordPress.org guideline on remote file calls.
* Updated: Minimum PHP requirement raised to 8.0 to match WordPress 6.9.x recommended environment.
* Updated: Tested up to WordPress 6.9.

= 1.2.2 =
* Security: Hardened all output escaping and input sanitization throughout the codebase.
* Fixed: Plugin activation fatal error resolved by adopting a class-based initialization architecture.

= 1.2.1 =
* Initial public release candidate.

