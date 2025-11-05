<?php
/*
Plugin Name: Kandeshop Broken Links Checker
Plugin URI: https://github.com/dkandekore
Description: Checks posts and pages in batches to find and list broken external links.
Version: 1.2.4
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
        '1.2.4', // <-- CACHE BUSTING
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
        )
    );
}
add_action( 'admin_enqueue_scripts', 'kblc_admin_enqueue_scripts' );


/**
 * AJAX action for checking links.
 */
function kblc_ajax_check_links() {
    // 1. Check the Nonce for security
    check_ajax_referer( 'kblc_check_links_nonce', 'nonce' );

    // 2. Get and sanitize variables
    $batch_size   = isset( $_POST['batch_size'] ) ? intval( $_POST['batch_size'] ) : 20;
    $batch_number = isset( $_POST['batch'] ) ? intval( $_POST['batch'] ) : 1;
    $offset       = ( $batch_number - 1 ) * $batch_size;

    // Get the site's host to differentiate internal/external
    $site_host = wp_parse_url( home_url(), PHP_URL_HOST );

    // --- FIX: Get all *publicly queryable* post types ---
    $post_types_to_scan = get_post_types( array( 'publicly_queryable' => true ), 'names' );
    // Exclude 'attachment' as it's not useful to scan
    $post_types_to_scan = array_diff( $post_types_to_scan, array( 'attachment' ) );

    $args = array(
        'post_type'      => $post_types_to_scan, // <-- THE FIX
        'posts_per_page' => $batch_size,
        'offset'         => $offset,
        'post_status'    => 'publish', // Only check published posts
    );
    
    $query = new WP_Query( $args );
    
    // Create arrays for each result type
    $output_broken  = array();
    $output_working = array();
    $output_checked = array();
    
    $found_posts = false;

    if ( $query->have_posts() ) {
        $found_posts = true;
        
        while ( $query->have_posts() ) {
            $query->the_post();
            
            $post_title = esc_html( get_the_title() );
            $post_type = esc_html( get_post_type() );
            $edit_link = esc_url( get_edit_post_link() );
            $permalink = esc_url( get_the_permalink() );

            // Add to Pages Checked list
            $output_checked[] = "<li><strong>{$post_title}</strong> ({$post_type}) - <a href='{$edit_link}' target='_blank'>Edit</a></li>";

            // --- FIX: Get the full rendered HTML of the page ---
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
                
                // Skip to the next post
                continue;
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
        }
        wp_reset_postdata();
    }

    // --- NEW Response Format ---
    if ( ! $found_posts ) {
        // Send a 'finished' signal
        wp_send_json( array( 'finished' => true ) );
    } else {
        // Send all three arrays in a success payload
        wp_send_json_success(
            array(
                'broken'  => $output_broken,
                'working' => $output_working,
                'checked' => $output_checked,
            )
        );
    }
    
    wp_die(); // Required for all AJAX in WordPress
}
add_action( 'wp_ajax_kblc_ajax_check_links', 'kblc_ajax_check_links' );