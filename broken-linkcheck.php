<?php
/*
Plugin Name: Broken Links Checker
Description: Checks and lists & links on a website and provides functionality to check for broken links.
Version: 1.0
Author: D kandekore
*/

// Add the admin menu
add_action('admin_menu', 'elc_add_admin_menu');

function elc_add_admin_menu() {
    add_menu_page('Broken Links Checker', 'Broken Links Checker', 'manage_options', 'broken-links-checker', 'elc_display_admin_page', 'dashicons-admin-links');
}

function elc_display_admin_page() {
    $default_batch_size = 20;

    echo '<h1>External Links Checker</h1>';
    echo 'Number of posts/pages per batch: <input type="number" id="batch-size" value="' . $default_batch_size . '">';
    echo '<input type="hidden" id="current-batch" value="1">';  // Hidden input to track the current batch
    echo '<button id="check-broken-links">Check Next Batch</button>';
    echo '<div id="checking-notice" style="color: blue; display: none;">Checking links...</div>'; 
    echo '<div id="broken-links-result"></div>'; 
}


// Enqueue the JavaScript
add_action('admin_enqueue_scripts', 'elc_enqueue_scripts');

function elc_enqueue_scripts($hook) {
    if ('toplevel_page_broken-links-checker' !== $hook) {
        return;
    }
    wp_enqueue_script('jquery');
    wp_enqueue_script('elc-script', plugins_url('elc-script.js', __FILE__), array('jquery'), '1.0', true);
}


// AJAX action for checking links
add_action('wp_ajax_check_broken_links', 'elc_check_broken_links');

function elc_check_broken_links() {
    $batch_size = isset($_POST['batch_size']) ? intval($_POST['batch_size']) : 20;
    $batch_number = isset($_POST['batch']) ? intval($_POST['batch']) : 1;
    $offset = ($batch_number - 1) * $batch_size;

    $args = array(
        'post_type' => array('post', 'page'),
        'posts_per_page' => $batch_size,
        'offset' => $offset
    );
    $query = new WP_Query($args);
    $broken_links_output = "";

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $content = get_the_content();

            preg_match_all('#<a[^>]+href=["\'](http[^"\']+)["\']#', $content, $matches);

            foreach ($matches[1] as $link) {
                $response = wp_remote_get($link, array('timeout' => 5));
                if (is_wp_error($response) || 404 == wp_remote_retrieve_response_code($response)) {
                    $broken_links_output .= '<p><strong>Page:</strong> <a href="' . get_the_permalink() . '">' . get_the_title() . '</a> - <strong>Broken Link:</strong> ' . $link . '</p>';
                }
            }
        }
        wp_reset_postdata();
    }

    echo $broken_links_output;
    wp_die();
}
