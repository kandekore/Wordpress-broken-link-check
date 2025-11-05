<?php
/*
Plugin Name: Kandeshop Broken Links Checker
Plugin URI: https://github.com/dkandekore
Description: Checks posts and pages in batches to find and list broken external links.
Version: 1.1.0
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
 * Display the admin page HTML.
 */
function kblc_admin_page_html() {
    $default_batch_size = 20;
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Kandeshop Broken Links Checker', 'kandeshop-blc' ); ?></h1>
        <p><?php esc_html_e( 'Scan your posts and pages in batches to find broken external links.', 'kandeshop-blc' ); ?></p>
        
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
        </p>
        
        <div id="kblc-checking-notice" style="color: blue; display: none; margin-top: 10px;">
            <p><?php esc_html_e( 'Checking links, please wait...', 'kandeshop-blc' ); ?></p>
        </div>
        
        <div id="kblc-results" style="margin-top: 20px;"></div>
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

    $script_asset_path = plugins_url( 'kblc-admin-script.js', __FILE__ );
    
    wp_enqueue_script(
        'kblc-admin-script',
        $script_asset_path,
        array( 'jquery' ),
        '1.1.0', // <-- CACHE BUSTING
        true // In footer
    );

    // Localize the script to pass the AJAX URL and Nonce
    wp_localize_script(
        'kblc-admin-script',
        'kblc_ajax', // Object name in JavaScript
        array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'kblc_check_links_nonce' ),
            'checking_text' => esc_html__( 'No broken links found in this batch. Checking next batch...', 'kandeshop-blc' ),
            'no_more_posts' => esc_html__( 'All posts and pages have been checked.', 'kandeshop-blc' ),
            'error_text'    => esc_html__( 'An error occurred while checking the links.', 'kandeshop-blc' )
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

    $args = array(
        'post_type'      => array( 'post', 'page' ),
        'posts_per_page' => $batch_size,
        'offset'         => $offset,
        'post_status'    => 'publish', // Only check published posts
    );
    
    $query = new WP_Query( $args );
    $broken_links_output = "";
    $found_posts = false;

    if ( $query->have_posts() ) {
        $found_posts = true;
        while ( $query->have_posts() ) {
            $query->the_post();
            $content = get_the_content();

            preg_match_all( '#<a[^>]+href=["\'](https?://[^"\']+)["\']#i', $content, $matches );

            if ( ! empty( $matches[1] ) ) {
                foreach ( $matches[1] as $link ) {
                    // Keep 10 second timeout
                    $response = wp_remote_get( esc_url_raw( $link ), array( 'timeout' => 10 ) );
                    
                    if ( is_wp_error( $response ) ) {
                        // --- NEW LOGIC: Differentiate errors ---
                        $error_message = $response->get_error_message();
                        
                        // Check if it's a timeout error
                        if ( strpos( $error_message, 'cURL error 28' ) !== false || strpos( $error_message, 'Operation timed out' ) !== false ) {
                            // This is a TIMEOUT. Format it as an advisory.
                            $broken_links_output .= '<p style="border-left: 4px solid #ffb900; padding-left: 10px; background: #fff; margin: 10px 0;">'; // Yellow border for warning
                            $broken_links_output .= '<strong>' . esc_html__( 'Page:', 'kandeshop-blc' ) . '</strong> <a href="' . esc_url( get_the_permalink() ) . '" target="_blank">' . esc_html( get_the_title() ) . '</a><br>';
                            $broken_links_output .= '<strong>' . esc_html__( 'Unreachable Link:', 'kandeshop-blc' ) . '</strong> <a href="' . esc_url( $link ) . '" target="_blank">' . esc_html( $link ) . '</a><br>';
                            $broken_links_output .= '<strong>' . esc_html__( 'Warning: (Timeout)', 'kandeshop-blc' ) . '</strong> ' . esc_html( $error_message ) . '<br>';
                            $broken_links_output .= '<em style="font-size: 0.9em;">' . esc_html__( 'This link is not necessarily broken. Your server was blocked or unable to contact it. This is common for firewalls or "loopback" connections (checking a link on its own site).', 'kandeshop-blc' ) . '</em></p><hr>';
                        } else {
                            // This is a different cURL error (e.g., DNS not found). This IS a broken link.
                            $broken_links_output .= '<p style="border-left: 4px solid #dc3232; padding-left: 10px; background: #fff; margin: 10px 0;">'; // Red border for error
                            $broken_links_output .= '<strong>' . esc_html__( 'Page:', 'kandeshop-blc' ) . '</strong> <a href="' . esc_url( get_the_permalink() ) . '" target="_blank">' . esc_html( get_the_title() ) . '</a><br>';
                            $broken_links_output .= '<strong>' . esc_html__( 'Broken Link:', 'kandeshop-blc' ) . '</strong> <a href="' . esc_url( $link ) . '" target="_blank">' . esc_html( $link ) . '</a><br>';
                            $broken_links_output .= '<strong>' . esc_html__( 'Error:', 'kandeshop-blc' ) . '</strong> ' . esc_html( $error_message ) . '</p><hr>';
                        }

                    } else {
                        // Check for HTTP error codes (400+)
                        $response_code = wp_remote_retrieve_response_code( $response );
                        if ( $response_code >= 400 ) {
                            // This is a 404 or 500. This IS a broken link.
                            $broken_links_output .= '<p style="border-left: 4px solid #dc3232; padding-left: 10px; background: #fff; margin: 10px 0;">'; // Red border for error
                            $broken_links_output .= '<strong>' . esc_html__( 'Page:', 'kandeshop-blc' ) . '</strong> <a href="' . esc_url( get_the_permalink() ) . '" target="_blank">' . esc_html( get_the_title() ) . '</a><br>';
                            $broken_links_output .= '<strong>' . esc_html__( 'Broken Link:', 'kandeshop-blc' ) . '</strong> <a href="' . esc_url( $link ) . '" target="_blank">' . esc_html( $link ) . '</a><a_><br>';
                            $broken_links_output .= '<strong>' . esc_html__( 'Status Code:', 'kandeshop-blc' ) . '</strong> ' . esc_html( $response_code ) . '</p><hr>';
                        }
                    }
                }
            }
        }
        wp_reset_postdata();
    }

    if ( ! $found_posts ) {
        // Send a special code '0' if no more posts were found
        wp_send_json( '0' );
    } elseif ( empty( $broken_links_output ) ) {
        // Send '1' if posts were found but no broken links
         wp_send_json( '1' );
    } else {
        // Send the HTML of broken links
        wp_send_json_success( $broken_links_output );
    }
    
    wp_die(); // Required for all AJAX in WordPress
}
add_action( 'wp_ajax_kblc_ajax_check_links', 'kblc_ajax_check_links' );