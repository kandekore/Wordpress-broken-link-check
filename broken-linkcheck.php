<?php
/*
Plugin Name: Kandeshop Broken Links Checker
Plugin URI: https://github.com/dkandekore
Description: Checks posts and pages in batches to find and list broken external links.
Version: 1.4.1
Author: D Kandekore
Author URI: https://github.com/dkandekore
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: kandeshop-blc
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Add the admin menu page.
 */
function kblc_add_admin_menu() {
    add_menu_page(
        esc_html__( 'Broken Links', 'kandeshop-blc' ),
        esc_html__( 'Broken Links', 'kandeshop-blc' ),
        'manage_options',
        'kandeshop-broken-links',
        'kblc_admin_page_html',
        'dashicons-admin-links'
    );
}
add_action( 'admin_menu', 'kblc_add_admin_menu' );

/**
 * Display the admin page HTML with new tabs.
 */
function kblc_admin_page_html() {
    $default_batch_size = 20;
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Kandeshop Broken Links Checker', 'kandeshop-blc' ); ?></h1>
        <p><?php esc_html_e( 'Scan your posts and pages in batches to find broken, unreachable, and working external links.', 'kandeshop-blc' ); ?></p>
        
        <table class="form-table" role="presentation">
            <tr>
                <th scope="row">
                    <label for="kblc-batch-size"><?php esc_html_e( 'Posts/Pages per batch', 'kandeshop-blc' ); ?></label>
                </th>
                <td>
                    <input type="number" id="kblc-batch-size" value="<?php echo esc_attr( $default_batch_size ); ?>">
                </td>
            </tr>
        </table>

        <input type="hidden" id="kblc-current-batch" value="1">
        
        <?php
        // Add a Nonce field for security
        wp_nonce_field( 'kblc_check_links_nonce', '_kblc_nonce' );
        ?>
        
        <p>
            <button id="kblc-check-links" class="button button-primary"><?php esc_html_e( 'Check Next Batch', 'kandeshop-blc' ); ?></button>
            <button id="kblc-clear-results" class="button button-secondary" style="margin-left: 10px;"><?php esc_html_e( 'Clear All Results', 'kandeshop-blc' ); ?></button>
        </p>
        
        <div id="kblc-checking-notice" style="display: none; margin: 10px 0; padding: 10px; border: 1px solid #ccd0d4; background: #fff;">
            <span class="spinner is-active" style="float: left; margin-top: 0;"></span>
            <p style="margin: 0 0 0 30px; line-height: 1.8;"></p> </div>
        
        <div id="kblc-tabs" style="margin-top: 20px;">
            <ul>
                <li><a href="#kblc-tab-broken"><?php esc_html_e( 'Broken/Unreachable Links', 'kandeshop-blc' ); ?></a></li>
                <li><a href="#kblc-tab-working"><?php esc_html_e( 'Working External Links', 'kandeshop-blc' ); ?></a></li>
                <li><a href="#kblc-tab-checked"><?php esc_html_e( 'Pages Checked', 'kandeshop-blc' ); ?></a></li>
                <li><a href="#kblc-tab-guide"><?php esc_html_e( 'Guide & Limitations', 'kandeshop-blc' ); ?></a></li>
                <li><a href="#kblc-tab-statuscodes"><?php esc_html_e( 'Status Code Explanations', 'kandeshop-blc' ); ?></a></li>
            </ul>

            <div id="kblc-tab-broken">
                <p><?php esc_html_e( 'This tab shows all links that returned an error (404, 500, etc.) or could not be reached (timeout).', 'kandeshop-blc' ); ?></p>
                <div id="kblc-results-broken"></div>
            </div>

            <div id="kblc-tab-working">
                <p><?php esc_html_e( 'This tab shows all links that are NOT on your own domain and returned a successful status.', 'kandeshop-blc' ); ?></p>
                <div id="kblc-results-working"></div>
            </div>

            <div id="kblc-tab-checked">
                <p><?php esc_html_e( 'This tab lists all posts and pages scanned in this session.', 'kandeshop-blc' ); ?></p>
                <div id="kblc-results-checked" style="max-height: 400px; overflow-y: auto; border: 1px solid #ccd0d4; padding: 10px;"></div>
            </div>

            <div id="kblc-tab-guide">
                <h3><?php esc_html_e( 'How to Use This Plugin', 'kandeshop-blc' ); ?></h3>
                <ol>
                    <li><?php esc_html_e( 'Set your "Posts/Pages per batch". A smaller number (like 10-20) is safer for most servers.', 'kandeshop-blc' ); ?></li>
                    <li><?php esc_html_e( 'Click "Check Next Batch".', 'kandeshop-blc' ); ?></li>
                    <li><?php esc_html_e( 'The progress indicator will appear, showing you which post is being scanned in real-time.', 'kandeshop-blc' ); ?></li>
                    <li><?php esc_html_e( 'As the scan runs, results will be added to the "Broken/Unreachable" and "Working External Links" tabs.', 'kandeshop-blc' ); ?></li>
                    <li><?php esc_html_e( 'When the batch finishes, you can click "Check Next Batch" again to continue scanning.', 'kandeshop-blc' ); ?></li>
                    <li><?php esc_html_e( 'Click "Clear All Results" to reset the scan and start over from batch 1.', 'kandeshop-blc' ); ?></li>
                </ol>

                <hr>

                <h3><?php esc_html_e( 'Important Limitations & Warnings', 'kandeshop-blc' ); ?></h3>

                <p><strong><span style="color: #dc3232;"><?php esc_html_e( 'WARNING:', 'kandeshop-blc' ); ?></span> <?php esc_html_e( 'Keep this browser tab open while scanning.', 'kandeshop-blc' ); ?></strong></p>
                <p><?php esc_html_e( 'This plugin is managed by JavaScript in your browser. It is *not* a server background task. If you close this tab, the scan will stop immediately. You can, however, move to a different browser tab and the scan will continue.', 'kandeshop-blc' ); ?></p>

                <p><strong><?php esc_html_e( 'Why you should keep batch sizes small:', 'kandeshop-blc' ); ?></strong></p>
                <p><?php esc_html_e( 'This scanner is thorough. For each post, it fetches the *entire* rendered HTML (header, footer, content) and then checks *every single link* it finds. A single page with 100 links will make 101 requests to your server. A batch size of 20 posts could mean thousands of requests. Keeping the batch small (10-20) prevents server timeouts and errors.', 'kandeshop-blc' ); ?></p>
                
                <p><strong><?php esc_html_e( 'Understanding "False Positives" (Timeouts & Blocked Links):', 'kandeshop-blc' ); ?></strong></p>
                <p><?php esc_html_e( 'You will see many links marked as "Unreachable" or "Blocked" that you know are working. This is not a bug. It is a server configuration issue. See the "Status Code Explanations" tab for a full guide.', 'kandeshop-blc' ); ?></p>
            </div>

            <div id="kblc-tab-statuscodes">
                <h3><?php esc_html_e( 'Understanding Your Results', 'kandeshop-blc' ); ?></h3>
                <p><?php esc_html_e( 'This scanner groups links into different categories based on the server\'s response.', 'kandeshop-blc' ); ?></p>

                <hr>

                <h4 style="color: #46b450;"><?php esc_html_e( 'Working Link (2xx-3xx)', 'kandeshop-blc' ); ?></h4>
                <p><?php esc_html_e( 'These links are good! The server responded with a success (200 OK) or a redirect (301, 302). These only appear in the "Working External Links" tab.', 'kandeshop-blc' ); ?></p>

                <h4 style="color: #dc3232;"><?php esc_html_e( 'Broken Link (404, 410, 5xx)', 'kandeshop-blc' ); ?></h4>
                <p><?php esc_html_e( 'These links are genuinely broken. The server responded with "Not Found" (404), "Gone" (410), or a fatal server error (500+). These should be fixed.', 'kandeshop-blc' ); ?></p>

                <h4 style="color: #007cba;"><?php esc_html_e( 'Blocked Link (403, 429, 999, etc.)', 'kandeshop-blc' ); ?></h4>
                <p><?php esc_html_e( 'These links are NOT broken. This is an anti-bot measure. Sites like LinkedIn, Reddit, and many others see your *server* (not you) trying to access the link, assume it\'s a scraper bot, and block it with a "Forbidden" (403) or "Too Many Requests" (429) error. These links are almost always fine for real users.', 'kandeshop-blc' ); ?></p>

                <h4 style="color: #ffb900;"><?php esc_html_e( 'Unreachable Link (cURL error 28: Timeout)', 'kandeshop-blc' ); ?></h4>
                <p><?php esc_html_e( 'This is a problem with *your* server. It tried to contact the link and received no response at all after 10 seconds. This is often caused by a server firewall blocking outbound requests. The link itself is probably fine, but your server cannot verify it.', 'kandeshop-blc' ); ?></p>

                <h4 style="color: #ffb900;"><?php esc_html_e( 'Loopback Request Failed (cURL error 28: Timeout)', 'kandeshop-blc' ); ?></h4>
                <p><?php esc_html_e( 'This is a specific type of "Unreachable" error. It means your server tried to scan one of its *own* pages (e.g., yoursite.com/about) and failed. This is 100% a server configuration issue where a firewall is blocking "loopback" connections. The page cannot be scanned until this is fixed by your host.', 'kandeshop-blc' ); ?></p>
            </div>

        </div>
    </div>
    <?php
}

/**
 * Enqueue and localize the admin script.
 */
function kblc_admin_enqueue_scripts( $hook ) {
    // Only load on our plugin page
    if ( 'toplevel_page_kandeshop-broken-links' !== $hook ) {
        return;
    }

    // Add jQuery UI Tabs
    wp_enqueue_style( 'jquery-ui-style', '//code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css' );
    
    $script_asset_path = plugins_url( 'kblc-admin-script.js', __FILE__ );
    
    wp_enqueue_script(
        'kblc-admin-script',
        $script_asset_path,
        array( 'jquery', 'jquery-ui-tabs' ), // Add 'jquery-ui-tabs' dependency
        '1.4.1', // <-- CACHE BUSTING
        true // In footer
    );

    // Get the site's host to compare against
    $site_host = wp_parse_url( home_url(), PHP_URL_HOST );

    // Localize the script to pass the AJAX URL, Nonce, and Site Host
    wp_localize_script(
        'kblc-admin-script',
        'kblc_ajax', // Object name in JavaScript
        array(
            'ajax_url'      => admin_url( 'admin-ajax.php' ),
            'nonce'         => wp_create_nonce( 'kblc_check_links_nonce' ),
            'site_host'     => $site_host, // Pass the site host
            'checking_text' => esc_html__( 'Batch {batch}: No new issues found.', 'kandeshop-blc' ),
            'no_more_posts' => esc_html__( 'All posts and pages have been checked.', 'kandeshop-blc' ),
            'error_text'    => esc_html__( 'An error occurred while checking the links.', 'kandeshop-blc' ),
            'clear_confirm' => esc_html__( 'Are you sure you want to clear all results and reset the scan?', 'kandeshop-blc' ),
            'scan_text'     => esc_html__( 'Scanning {current} of {total} in batch: "{title}"...', 'kandeshop-blc' ),
        )
    );
}
add_action( 'admin_enqueue_scripts', 'kblc_admin_enqueue_scripts' );


/**
 * NEW AJAX ACTION: Get a list of posts for the batch.
 * This just returns a list; it doesn't scan them.
 */
function kblc_ajax_get_batch_posts() {
    // 1. Check the Nonce for security
    check_ajax_referer( 'kblc_check_links_nonce', 'nonce' );

    // 2. Get and sanitize variables
    $batch_size   = isset( $_POST['batch_size'] ) ? intval( $_POST['batch_size'] ) : 20;
    $batch_number = isset( $_POST['batch'] ) ? intval( $_POST['batch'] ) : 1;
    $offset       = ( $batch_number - 1 ) * $batch_size;

    $post_types_to_scan = get_post_types( array( 'publicly_queryable' => true ), 'names' );
    $post_types_to_scan = array_diff( $post_types_to_scan, array( 'attachment' ) );

    $args = array(
        'post_type'      => $post_types_to_scan,
        'posts_per_page' => $batch_size,
        'offset'         => $offset,
        'post_status'    => 'publish',
        'fields'         => 'ids', // Only get IDs to be lightweight
    );
    
    $query = new WP_Query( $args );
    
    $posts_to_scan = array();
    
    if ( $query->have_posts() ) {
        foreach ( $query->posts as $post_id ) {
            $posts_to_scan[] = array(
                'id'    => $post_id,
                'title' => esc_html( get_the_title( $post_id ) ),
                'type'  => esc_html( get_post_type( $post_id ) ),
                'edit'  => esc_url( get_edit_post_link( $post_id ) ),
                'view'  => esc_url( get_permalink( $post_id ) ),
            );
        }
        wp_send_json_success( $posts_to_scan );
    } else {
        // No posts found, send 'finished' signal
        wp_send_json( array( 'finished' => true ) );
    }
    
    wp_die();
}
add_action( 'wp_ajax_kblc_ajax_get_batch_posts', 'kblc_ajax_get_batch_posts' );


/**
 * UPDATED AJAX ACTION: Now scans only ONE post at a time.
 */
function kblc_ajax_check_links() {
    // 1. Check the Nonce for security
    check_ajax_referer( 'kblc_check_links_nonce', 'nonce' );

    // 2. Get and sanitize the single post ID
    if ( ! isset( $_POST['post_id'] ) || ! intval( $_POST['post_id'] ) ) {
        wp_send_json_error( 'Invalid Post ID.' );
    }
    $post_id = intval( $_POST['post_id'] );

    // Get post data
    $post_title = esc_html( get_the_title( $post_id ) );
    $post_type  = esc_html( get_post_type( $post_id ) );
    $edit_link  = esc_url( get_edit_post_link( $post_id ) );
    $permalink  = esc_url( get_permalink( $post_id ) );

    // Get the site's host to differentiate internal/external
    $site_host = wp_parse_url( home_url(), PHP_URL_HOST );
    
    // Create arrays for each result type
    $output_broken  = array();
    $output_working = array();
    
    // This is the "Pages Checked" HTML for this one post
    $output_checked = "<li><strong>{$post_title}</strong> ({$post_type}) - <a href='{$edit_link}' target='_blank'>Edit</a></li>";

    // --- Get the full rendered HTML of the page ---
    $page_response = wp_remote_get( $permalink, array( 'timeout' => 10 ) );
    
    if ( is_wp_error( $page_response ) ) {
        // --- This is a LOOPBACK error. The server can't fetch its own page. ---
        $error_message = $page_response->get_error_message();
        $result_html = '<p style="border-left: 4px solid #ffb900; padding-left: 10px; background: #fff; margin: 10px 0;">'; // Yellow border
        $result_html .= '<strong>' . esc_html__( 'Loopback Request Failed:', 'kandeshop-blc' ) . '</strong> ' . esc_html( $post_title ) . '<br>';
        $result_html .= '<strong>' . esc_html__( 'Page URL:', 'kandeshop-blc' ) . '</strong> <a href="' . $permalink . '" target="_blank">' . $permalink . '</a><br>';
        $result_html .= '<strong>' . esc_html__( 'Error:', 'kandeshop-blc' ) . '</strong> ' . esc_html( $error_message ) . '<br>';
        $result_html .= '<em style="font-size: 0.9em;">' . esc_html__( 'The plugin could not scan this page because your server timed out trying to fetch it. This is a "loopback" issue, likely caused by a firewall.', 'kandeshop-blc' ) . '</em></p><hr>';
        $output_broken[] = $result_html;
        
        // Send the results for just this one failed page
        wp_send_json_success(
            array(
                'broken'  => $output_broken,
                'working' => $output_working,
                'checked' => $output_checked,
            )
        );
        wp_die();
    }
    
    $content = wp_remote_retrieve_body( $page_response );
    // --- End of new logic ---

    preg_match_all( '#<a[^>]+href=["\'](https?://[^"\']+)["\']#i', $content, $matches );
    $processed_links = array(); // Array to avoid checking the same link multiple times per page

    if ( ! empty( $matches[1] ) ) {
        foreach ( $matches[1] as $link ) {
            // Normalize link: remove hash
            $link_no_hash = strtok( $link, '#' );
            
            // Skip if we've already checked this exact link on this page
            if ( isset( $processed_links[ $link_no_hash ] ) ) {
                continue;
            }
            $processed_links[ $link_no_hash ] = true;

            $link_host = wp_parse_url( $link_no_hash, PHP_URL_HOST );
            $is_external = ( $link_host !== $site_host );

            $response = wp_remote_get( esc_url_raw( $link_no_hash ), array( 'timeout' => 10 ) );
            
            if ( is_wp_error( $response ) ) {
                // --- This is a cURL error (Timeout, DNS fail, etc.) ---
                $error_message = $response->get_error_message();
                
                // Check if it's a timeout error
                if ( strpos( $error_message, 'cURL error 28' ) !== false || strpos( $error_message, 'Operation timed out' ) !== false ) {
                    $result_html = '<p style="border-left: 4px solid #ffb900; padding-left: 10px; background: #fff; margin: 10px 0;">'; // Yellow border
                    $result_html .= '<strong>' . esc_html__( 'Unreachable Link:', 'kandeshop-blc' ) . '</strong>';
                    $result_html .= ' <a href="' . esc_url( $link ) . '" target="_blank">' . esc_html( $link ) . '</a><br>';
                    $result_html .= '<strong>' . esc_html__( 'Found on Page:', 'kandeshop-blc' ) . '</strong> <a href="' . $permalink . '" target="_blank">' . $post_title . '</a><br>';
                    $result_html .= '<strong>' . esc_html__( 'Error:', 'kandeshop-blc' ) . '</strong> ' . esc_html( $error_message );
                    $result_html .= '<br><em style="font-size: 0.9em;">' . esc_html__( 'This is often a server firewall issue. The link may be fine.', 'kandeshop-blc' ) . '</em>';
                } else {
                    // This is a different cURL error (e.g., DNS not found). This IS a broken link.
                    $result_html = '<p style="border-left: 4px solid #dc3232; padding-left: 10px; background: #fff; margin: 10px 0;">'; // Red border
                    $result_html .= '<strong>' . esc_html__( 'Broken Link:', 'kandeshop-blc' ) . '</strong>';
                    $result_html .= ' <a href="' . esc_url( $link ) . '" target="_blank">' . esc_html( $link ) . '</a><br>';
                    $result_html .= '<strong>' . esc_html__( 'Found on Page:', 'kandeshop-blc' ) . '</strong> <a href="' . $permalink . '" target="_blank">' . $post_title . '</a><br>';
                    $result_html .= '<strong>' . esc_html__( 'Error:', 'kandeshop-blc' ) . '</strong> ' . esc_html( $error_message );
                }
                $result_html .= '</p><hr>';
                $output_broken[] = $result_html;

            } else {
                // --- This is a valid HTTP response ---
                $response_code = wp_remote_retrieve_response_code( $response );
                
                // --- NEW: Detailed status code logic ---
                if ( $response_code == 404 || $response_code == 410 || $response_code >= 500 ) {
                    // This is a 404, 500, etc. This IS a broken link.
                    $result_html = '<p style="border-left: 4px solid #dc3232; padding-left: 10px; background: #fff; margin: 10px 0;">'; // Red border
                    $result_html .= '<strong>' . esc_html__( 'Broken Link:', 'kandeshop-blc' ) . '</strong> <a href="' . esc_url( $link ) . '" target="_blank">' . esc_html( $link ) . '</a><br>';
                    $result_html .= '<strong>' . esc_html__( 'Found on Page:', 'kandeshop-blc' ) . '</strong> <a href="' . $permalink . '" target="_blank">' . $post_title . '</a><br>';
                    $result_html .= '<strong>' . esc_html__( 'Status Code:', 'kandeshop-blc' ) . '</strong> ' . esc_html( $response_code ) . ' (Not Found / Server Error)</p><hr>';
                    $output_broken[] = $result_html;

                } elseif ( in_array( $response_code, array( 403, 429, 999, 460 ) ) ) {
                    // This is an Anti-Bot/Rate-Limit code. This is NOT a broken link.
                    $result_html = '<p style="border-left: 4px solid #007cba; padding-left: 10px; background: #fff; margin: 10px 0;">'; // Blue border (info)
                    $result_html .= '<strong>' . esc_html__( 'Link Blocked:', 'kandeshop-blc' ) . '</strong> <a href="' . esc_url( $link ) . '" target="_blank">' . esc_html( $link ) . '</a><br>';
                    $result_html .= '<strong>' . esc_html__( 'Found on Page:', 'kandeshop-blc' ) . '</strong> <a href="' . $permalink . '" target="_blank">' . $post_title . '</a><br>';
                    $result_html .= '<strong>' . esc_html__( 'Status Code:', 'kandeshop-blc' ) . '</strong> ' . esc_html( $response_code ) . ' (' . esc_html__( 'Forbidden / Too Many Requests', 'kandeshop-blc' ) . ')<br>';
                    $result_html .= '<em style="font-size: 0.9em;">' . esc_html__( 'This link is likely fine. The remote server is blocking the scanner, suspecting it is a bot.', 'kandeshop-blc' ) . '</em></p><hr>';
                    $output_broken[] = $result_html; // Add to "Broken" tab for review, but with blue styling

                } elseif ( $response_code >= 400 ) {
                    // This is some other 4xx error (e.g., 401 Unauthorized). This IS a broken link.
                    $result_html = '<p style="border-left: 4px solid #dc3232; padding-left: 10px; background: #fff; margin: 10px 0;">'; // Red border
                    $result_html .= '<strong>' . esc_html__( 'Broken Link:', 'kandeshop-blc' ) . '</strong> <a href="' . esc_url( $link ) . '" target="_blank">' . esc_html( $link ) . '</a><br>';
                    $result_html .= '<strong>' . esc_html__( 'Found on Page:', 'kandeshop-blc' ) . '</strong> <a href="' . $permalink . '" target="_blank">' . $post_title . '</a><br>';
                    $result_html .= '<strong>' . esc_html__( 'Status Code:', 'kandeshop-blc' ) . '</strong> ' . esc_html( $response_code ) . ' (Client Error)</p><hr>';
                    $output_broken[] = $result_html;

                } elseif ( $is_external ) {
                     // This is a 2xx or 3xx response, AND it's external.
                    $result_html = '<p style="border-left: 4px solid #46b450; padding-left: 10px; background: #fff; margin: 10px 0;">'; // Green border
                    $result_html .= '<strong>' . esc_html__( 'Working Link:', 'kandeshop-blc' ) . '</strong> <a href="' . esc_url( $link ) . '" target="_blank">' . esc_html( $link ) . '</a><br>';
                    $result_html .= '<strong>' . esc_html__( 'Found on Page:', 'kandeshop-blc' ) . '</strong> <a href="' . $permalink . '" target="_blank">' . $post_title . '</a><br>';
                    $result_html .= '<strong>' . esc_html__( 'Status Code:', 'kandeshop-blc' ) . '</strong> ' . esc_html( $response_code ) . '</p><hr>';
                    $output_working[] = $result_html;
                }
                // else: Link is 200 OK and internal, so we do nothing (as requested).
            }
        }
    }
    
    // Send all three arrays in a success payload
    wp_send_json_success(
        array(
            'broken'  => $output_broken,
            'working' => $output_working,
            'checked' => $output_checked,
        )
    );
    
    wp_die(); // Required for all AJAX in WordPress
}
add_action( 'wp_ajax_kblc_ajax_check_links', 'kblc_ajax_check_links' );