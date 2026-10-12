=== NoHackMe Defender ===
Contributors: needtome
Donate link: https://www.donationalerts.com/r/needtome
Tags: security, protection, anti-hack, IP blocking, firewall
Requires at least: 6.0
Tested up to: 7.1.3
Stable tag: 1.1.9
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Plugin URI: https://needtome.com/nohackme/

Enhance your WordPress security by blocking IPs that send too many or suspicious requests.

== Description ==

The NoHackMe Defender plugin ensures the security of your WordPress site by blocking IP addresses when receiving suspicious requests, or when too many requests are received from a single IP over a certain period. The plugin offers comprehensive protection mechanisms including:

* Hacking protection: Blocks IP addresses that send suspicious data.
* Parsing protection: Prevents malicious parsing attempts on your website.
* DoS protection: Stops denial-of-service attacks by limiting excessive requests.
* Password brute force protection: Prevents repeated login attempts to guess passwords.

Explore our instructional videos to see the NoHackMe Defender plugin in action and learn how to configure settings and manage blocked IP addresses efficiently:

1. [Protect Your WordPress Site for Free - Installing and Configuring NoHackMe Defender Plugin](https://youtu.be/l6LFvNn7RE8): A thorough guide on activating and configuring the NoHackMe Defender plugin, including its free version features.
2. [How to Protect Your Site from Hackers, Parsing, and DoS - Testing WordPress Plugin NoHackMe Defender](https://youtu.be/DqTvUfLmmGQ): Demonstrates the plugin's effectiveness in blocking suspicious requests and securing your site.

For more information and a live demonstration, visit our [Plugin Demo Page](https://needtome.com/nohackme/).

Special thanks to our sponsors for supporting the development of this plugin:
- [malinovsky.io](https://malinovsky.io)
- [gloap.net](https://gloap.net)
- [gloapm.com](https://gloapm.com)
- [o-keto.com](https://o-keto.com)

== Frequently Asked Questions ==

= Is the plugin fully available in one version? =

Yes. The plugin is now distributed as a single version with all functionality available without a paid upgrade.

= How can I support the plugin developer? =

If NoHackMe Defender helps protect your site, you can support further development via the donation links listed in the plugin settings page.

= Can the plugin show the country for a blocked IP? =

Yes. Add the Sypex Geo PHP API file `SxGeo.php` and the country database `SxGeo.dat` to the GeoIP directory under your WordPress uploads directory: `wp-content/uploads/nohackme-defender/geo/`. Create the folders if they do not exist. If your site uses a custom uploads directory, use that directory instead of `wp-content/uploads`.

This location is outside the plugin, so plugin updates will not remove the files. For backward compatibility, the plugin also checks the old `wp-content/plugins/nohackme-defender/lib/geo/` folder. Both files must be present in the same directory. IP blocking works without GeoIP files, but the country is shown as unknown.

Download the PHP API and Sypex Geo Country database from the [official Sypex Geo download page](https://sypexgeo.net/ru/download/).

= About the plugin developer =

You can download and view my portfolio in [Google Docs](https://docs.google.com/document/d/141cWx986ApRqlxEIb3GUHEGQ-US1c3KutbzRqr3osm8/edit?usp=sharing). Also, find me on [LinkedIn](https://www.linkedin.com/in/roman-klymenko-parad0x/).

For website and WordPress plugin development inquiries, email [paraz0n3@gmail.com](mailto:paraz0n3@gmail.com).

== Screenshots ==

1. screenshot-1.png
2. screenshot-2.png
3. screenshot-3.png
4. screenshot-4.png
5. screenshot-5.png
6. screenshot-6.png
7. screenshot-7.png

== Changelog ==

= 1.1.9 =
* Store GeoIP files in the WordPress uploads directory so plugin updates do not remove them, while continuing to support the previous plugin folder.

= 1.1.8 =
* Verified compatibility with WordPress 7.1.3, including suspicious-path blocking and the English/Russian demonstrations.

= 1.1.7 =
* Tested with WordPress 7.1.2.

= 1.1.6 =
* Added a runtime safety guard that ignores overly broad short alphabetic payload rules such as `or` or `and`, preventing normal URLs like `/sailors/` from being blocked.
* Existing active short alphabetic payload rules are now automatically marked inactive in the stored rules file during default-rule synchronization.
* Removed the broad standalone `or` payload signature from default rules while keeping safer quoted SQL variants.
* Added a "Разблокировать всех" button to the blocked IP admin page to clear all current IP blocks at once.

= 1.1.5 =
* Split suspicious request payload patterns and suspicious URL path patterns into two separate admin sections with independent URLs and submenu entries.
* Added the Suspicious URL paths admin page for sensitive files, foreign CMS/framework fingerprints, admin panels, and service endpoints.
* Added separate temporary protection toggles for payload/body scanning and suspicious URL path scanning.
* Added per-rule Active checkboxes so individual rules can be temporarily disabled without deleting them.
* Added per-row Delete buttons for both rule lists, making it clear that unused rules can be removed directly.
* Disabled rules are preserved in the settings files and ignored by the runtime scanner until re-enabled.
* Updated default-list synchronization so disabled default rules are not re-added as active duplicates.

= 1.1.4 =
* Added raw request body and decoded JSON scanning for REST, GraphQL, and other API-style POST/PUT/PATCH/DELETE requests.
* Added request URI, query string, request parameters, and User-Agent scanning to close bypasses where suspicious paths or scanner signatures were not submitted as normal form parameters.
* Added immediate blocking for sensitive scanner paths such as `.env`, `wp-config.php`, `composer.json`, backup/swap/SQL dump extensions, PHP-CGI probes, and exposed debug/runtime endpoints.
* Added immediate blocking for high-confidence foreign CMS and framework scanner paths, including Drupal, Joomla, Magento, Laravel, Symfony, TYPO3, Umbraco, and Ghost fingerprints.
* Added a missing-file probe counter that blocks IPs repeatedly requesting non-existent high-risk files such as executable script variants, backups, logs, archives, or config files.
* Added new default suspicious fragments for REST batch abuse, GraphQL probes, PHP-CGI attempts, exposed configuration/debug paths, and additional SQL injection patterns.
* Existing suspicious-fragment lists now receive missing default security signatures without removing custom entries.

= 1.1.2 =
* The plugin is now distributed as a single version without a separate premium/free split.
* Added a support block with donation links to the settings page.
* Removed premium-only UI restrictions and related admin notices.

= 1.1.1 =
* Fixed deletion of blocked IPs from the middle of the list in the admin panel.
* Added IPv6 support for blocked IPs, whitelists, range matching, and admin display.
* Added buttons to update Google and Yandex crawler IP ranges from their official sources.
* Improved admin update UX: buttons now show progress and reload the page after a successful update.
* Added safer file locking for counters and ban files to reduce race conditions under concurrent requests.
* Removed unnecessary unauthenticated AJAX actions for admin-only operations.

= 1.1.0 =
* Fixed issue with the IP whitelist functionality.
* Added a page view counter for a single IP in 10 minutes to the Statistics section.
* Updated the logic of page view counters for a single IP in 1 minute and 50 minutes in the Statistics section.

= 1.0.0 =
* Initial release: Includes hacking, parsing, DoS, and password brute force protection.

== Upgrade Notice ==

= 1.1.6 =
This update prevents overly broad short payload rules from blocking normal traffic, disables existing unsafe short payload rules, and adds a bulk unblock button.

= 1.1.5 =
This update separates payload/body rules from suspicious URL path rules and adds per-rule Active and Delete controls.

= 1.1.4 =
This update improves blocking for malicious API requests, raw JSON bodies, sensitive scanner paths, repeated missing-file probes, and additional SQL injection patterns.

= 1.1.2 =
This update unifies the plugin into a single version and adds a support section to the settings page.

= 1.1.1 =
This update improves blocked IP management, adds IPv6 support, and allows refreshing crawler IP ranges from official sources.

= 1.0.0 =
This is the first release of the plugin. Installing this version ensures comprehensive security measures are in place to protect your WordPress site from various types of attacks.

= 1.1.0 =
This update improves IP whitelist handling and extends the statistics section with better per-IP counters.
