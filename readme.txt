=== Kandeshop Broken Links Checker ===
Contributors: dkandekore
Tags: links, broken links, http, checker, maintenance
Requires at least: 6.0
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.4.1
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Checks posts and pages in batches to find and list broken external links.

== Description ==

The Kandeshop Broken Links Checker is a WordPress plugin designed to identify and list broken or non-functional external links. It scans through your site in batches and organizes links into five tabs:

1.  **Broken/Unreachable Links:** Shows all links (internal and external) that return an error (404, 500) or are unreachable (timeout).
2.  **Working External Links:** Shows all links that are NOT on your own domain and return a successful status.
3.  **Pages Checked:** Lists all posts and pages that have been scanned in your current session.
4.  **Guide & Limitations:** A simple "how-to" guide with important warnings about how the plugin works.
5.  **Status Code Explanations:** A full explanation of what each error (404, 403, 999, cURL 28) actually means.

The plugin now intelligently categorizes errors and features a real-time progress indicator that shows you exactly which post is being scanned.

== Installation ==

1.  Upload the `kandeshop-broken-link-check` folder to your `/wp-content/plugins/` directory.
2.  Activate the plugin through the 'Plugins' screen in WordPress.
3.  Navigate to the 'Broken Links' menu in your WordPress admin to use the tool.

== Usage ==

1.  Go to the admin dashboard.
2.  Find the 'Broken Links' option in the main menu and click it.
3.  Read the **Guide & Limitations** tab to understand how the plugin works.
4.  On the 'Broken Links Checker' page, set the number of posts/pages per batch.
5.  Click the 'Check Next Batch' button.
6.  A real-time progress bar will appear, showing you which post is currently being scanned.
7.  Results will be added to the appropriate tabs as they are found.
8.  Click "Clear All Results" to reset the scan and start over.

== Frequently Asked Questions ==

= What is a batch? =

A batch refers to a set of posts or pages that the plugin scans at a time. You can set the number of posts/pages in a batch in the admin dashboard.

= Does the plugin automatically fix the broken links? =

No, the plugin only identifies and lists the broken links. You will need to fix the links manually.

== Changelog ==

= 1.4.1 =
* Fix: Corrected a fatal JavaScript syntax error that prevented the script from loading, which broke the tabs and buttons.
* Best Practice: Bumped script versions to force cache-busting.

= 1.4.0 =
* Feature: Added a "Guide & Limitations" documentation tab to the admin page.
* Feature: Added a "Status Code Explanations" tab to explain what 404, 403, and cURL errors mean.
* Best Practice: Bumped script versions to force cache-busting.

= 1.3.0 =
* Feature: Reworked the entire scanning architecture to be more stable and provide real-time feedback.
* Feature: The progress indicator now shows exactly which post is being scanned (e.g., "Scanning (1/20): 'My Post Title'...").
* Fix: Changed the scan from one long request per batch to many small requests (one per post) to prevent server timeouts.
* Best Practice: Added a new AJAX endpoint `kblc_ajax_get_batch_posts` to feed the queue.
* Best Practice: `kblc_ajax_check_links` now only scans a single post ID.

= 1.2.4 =
* Feature: Added detailed explanations for anti-bot status codes (403, 429, 999, 460).
* Feature: These "Blocked" links are now styled in blue to distinguish them from "Broken" (red) and "Unreachable" (yellow) links.
* Fix: Cleaned up link normalization to remove URL fragments (#) before checking.

= 1.2.3 =
* Feature: Added a spinner and dynamic text (e.g., "Checking batch 2...") to the "Checking..." notice for better user feedback.
* Fix: Changed the HTML for the notice to include a dedicated paragraph for text, improving layout.

= 1.2.2 =
* Feature: Scanner now fetches the full rendered HTML (header, footer, etc.) instead of just the post content.
* Feature: Scanner now warns about "Loopback Request Failed" errors if the server blocks it from scanning its own pages.
* Fix: Scanner now correctly finds all *publicly queryable* post types, including custom post types.

= 1.2.1 =
* Feature: The scanner now automatically detects and checks all public post types (e.g., 'portfolio', 'products') instead of just posts and pages.
* Fix: Excludes 'attachment' post type from the scan by default.

= 1.2.0 =
* Feature: Added a tabbed interface (Broken, Working External, Pages Checked).
* Feature: Added "Working External Links" tab to show only 200/300 status links not on the local domain.
* Feature: Added "Pages Checked" tab to list all scanned posts/pages.
* Feature: Results are now persistent in the browser's `sessionStorage`.
* Feature: Added "Clear All Results" button to reset the session.
* Fix: AJAX now returns a full JSON object instead of simple HTML/codes.
* Best Practice: Added `jquery-ui-tabs` as a dependency.

= 1.1.0 =
* Feature: Differentiated between "Broken Links" (404s, DNS errors) and "Unreachable Links" (cURL timeouts).
* Feature: Unreachable links are now displayed as a warning with an explanation.

= 1.0.3 =
* Fix: Increased cURL timeout from 5 to 10 seconds to help with slow servers.

= 1.0.2 =
* Fix: Force cache-busting for JavaScript file.
* Fix: Add console logging for easier debugging.

= 1.0.1 =
* Security: Added Nonce validation for all AJAX requests.
* Fix: Correctly handles HTTPS links in addition to HTTP.
* Fix: Improved link checking to detect all HTTP error codes (400+) instead of just 404.
* Fix: Removed a fatal PHP error (stray brace).
* Best Practice: Refactored all functions with `kblc_` prefix.
* Best Practice: Enqueued and localized script properly.
* Best Practice: Escaped all output and internationalized strings.

= 1.0 =
* Initial release of the plugin.