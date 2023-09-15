<?php
/*
Plugin Name: Broken Links Checker
Description: Checks and lists & links on a website and provides functionality to check for broken links.
Version: 1.0
Author: D kandekore
*/

// Admin Page Setup
add_action('admin_menu', 'elc_add_admin_page');

function elc_add_admin_page() {
    add_menu_page('External Links Checker', 'External Links', 'manage_options', 'external-links-checker', 'elc_display_admin_page', 'dashicons-admin-links', 110);
}

function elc_display_admin_page() {
    echo '<h1>External Links Checker</h1>';
    echo '<button id="check-broken-links">Check for Broken Links</button>';
    echo '<div id="broken-links-result"></div>'; 
    echo '<script type="text/javascript">
    jQuery(document).ready(function($) {
        $("#check-broken-links").click(function() {
            $.ajax({
                url: ajaxurl,
                type: "post",
                data: {
                    action: "check_broken_links"
                },
                success: function(response) {
                    $("#broken-links-result").html(response); // Displaying results in the div
                }
            });
        });
    });
    </script>';
}

// Enqueue and Localize JS
add_action('admin_enqueue_scripts', 'elc_enqueue_scripts');

function elc_enqueue_scripts() {
    wp_enqueue_script('jquery');
}

// Handle AJAX in PHP & Check for Broken Links
add_action('wp_ajax_check_broken_links', 'elc_check_broken_links');

function elc_check_broken_links() {
    $args = array(
        'post_type' => array('post', 'page'),
        'posts_per_page' => -1,
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

