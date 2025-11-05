jQuery(document).ready(function($) {
    
    var $tabs = $("#kblc-tabs").tabs();
    var $check_button = $("#kblc-check-links");
    var $clear_button = $("#kblc-clear-results");
    var $notice_box = $("#kblc-checking-notice");
    var $notice_p = $notice_box.find('p');

    // DEBUGGING: This message MUST appear in your browser console if the new file is loading.
    console.log('KBLC Admin Script v1.4.1 Loaded');

    // Define storage keys
    var STORAGE_KEYS = {
        broken: 'kblc_results_broken',
        working: 'kblc_results_working',
        checked: 'kblc_results_checked',
        batch: 'kblc_current_batch'
    };

    // --- Global queue for the current batch ---
    var post_queue = [];

    // Function to load results from sessionStorage
    function loadResultsFromStorage() {
        var broken_html = sessionStorage.getItem(STORAGE_KEYS.broken) || '';
        var working_html = sessionStorage.getItem(STORAGE_KEYS.working) || '';
        var checked_html = sessionStorage.getItem(STORAGE_KEYS.checked) || '';
        var batch_num = sessionStorage.getItem(STORAGE_KEYS.batch) || '1';

        $("#kblc-results-broken").html(broken_html);
        $("#kblc-results-working").html(working_html);
        $("#kblc-results-checked").html(checked_html);
        $("#kblc-current-batch").val(batch_num);
    }

    // Load results on page load
    loadResultsFromStorage();

    // --- Click handler for the "Clear Results" button ---
    $clear_button.click(function() {
        if (confirm(kblc_ajax.clear_confirm)) {
            // Clear storage
            sessionStorage.removeItem(STORAGE_KEYS.broken);
            sessionStorage.removeItem(STORAGE_KEYS.working);
            sessionStorage.removeItem(STORAGE_KEYS.checked);
            sessionStorage.removeItem(STORAGE_KEYS.batch);

            // Clear HTML and reset batch
            loadResultsFromStorage();
            $check_button.prop("disabled", false).text("Check Next Batch");
            $notice_box.hide();
            console.log('Results cleared.');
        }
    });

    // --- Main "Check Links" button click handler ---
    $check_button.click(function() {
        
        var batch_size = parseInt($("#kblc-batch-size").val());
        var current_batch = parseInt($("#kblc-current-batch").val());

        $check_button.prop("disabled", true).text("Fetching post list...");
        $notice_p.text('Preparing batch ' + current_batch + '... Please wait.');
        $notice_box.show();

        // Step 1: Get the list of posts for this batch
        $.ajax({
            url: kblc_ajax.ajax_url,
            type: "post",
            data: {
                action: "kblc_ajax_get_batch_posts",
                nonce: kblc_ajax.nonce,
                batch_size: batch_size,
                batch: current_batch
            },
            success: function(response) {
                if (response.finished) {
                    // No posts were found in this batch, we are all done
                    $notice_p.text(kblc_ajax.no_more_posts);
                    $check_button.prop("disabled", true).text("All Done");
                    sessionStorage.removeItem(STORAGE_KEYS.batch); // Reset batch
                    return;
                }

                if (response.success) {
                    // We have our list of posts. Start the queue.
                    post_queue = response.data;
                    console.log('Got ' + post_queue.length + ' posts to scan.');
                    // Start processing the first item in the queue
                    process_post_queue(0, post_queue.length);
                } else {
                    $notice_p.text('Error: Could not retrieve post list.');
                    $check_button.prop("disabled", false).text("Check Next Batch");
                }
            },
            error: function() {
                $notice_p.text(kblc_ajax.error_text + ' (Failed to get post list)');
                $check_button.prop("disabled", false).text("Check Next Batch");
            }
        });
    });

    /**
     * This is the new recursive loop function.
     * It processes one post at a time from the queue.
     */
    function process_post_queue(index, total) {
        
        // Get the post for this iteration
        var post = post_queue[index];

        // Update the progress indicator
        var progress_text = kblc_ajax.scan_text
            .replace('{current}', index + 1)
            .replace('{total}', total)
            .replace('{title}', post.title);
            
        $notice_p.text(progress_text);
        $check_button.text('Checking (' + (index + 1) + '/' + total + ')...');

        // Step 2: Send an AJAX request to scan just this ONE post
        $.ajax({
            url: kblc_ajax.ajax_url,
            type: "post",
            data: {
                action: "kblc_ajax_check_links", // The *other* AJAX function
                nonce: kblc_ajax.nonce,
                post_id: post.id
            },
            success: function(response) {
                
                if (response.success) {
                    var data = response.data;

                    // Append results to the correct tabs
                    if (data.broken && data.broken.length > 0) {
                        var broken_html = sessionStorage.getItem(STORAGE_KEYS.broken) || '';
                        broken_html = data.broken.join('') + broken_html;
                        $("#kblc-results-broken").html(broken_html);
                        sessionStorage.setItem(STORAGE_KEYS.broken, broken_html);
                    }

                    if (data.working && data.working.length > 0) {
                        var working_html = sessionStorage.getItem(STORAGE_KEYS.working) || '';
                        working_html = data.working.join('') + working_html;
                        $("#kblc-results-working").html(working_html);
                        sessionStorage.setItem(STORAGE_KEYS.working, working_html);
                    }

                    if (data.checked && data.checked.length > 0) {
                        var checked_html = sessionStorage.getItem(STORAGE_KEYS.checked) || '';
                        checked_html = data.checked + checked_html; // Only one item
                        $("#kblc-results-checked").html(checked_html);
                        sessionStorage.setItem(STORAGE_KEYS.checked, checked_html);
                    }

                } else {
                    // The single post scan failed
                    var fail_html = '<p style="border-left: 4px solid #dc3232; padding-left: 10px;"><strong>Scan failed for ' + post.title + ':</strong> ' + response.data + '</p><hr>';
                    var broken_html = sessionStorage.getItem(STORAGE_KEYS.broken) || '';
                    broken_html = fail_html + broken_html;
                    $("#kblc-results-broken").html(broken_html);
                    sessionStorage.setItem(STORAGE_KEYS.broken, broken_html);
                }

                // --- Process the next item ---
                var next_index = index + 1;
                if (next_index < total) {
                    // Go to the next post in the queue
                    process_post_queue(next_index, total);
                } else {
                    // This batch is finished!
                    $notice_box.hide();
                    $check_button.prop("disabled", false).text("Check Next Batch");
                    
                    // Increment and save the batch number
                    var next_batch = parseInt($("#kblc-current-batch").val()) + 1;
                    $("#kblc-current-batch").val(next_batch);
                    sessionStorage.setItem(STORAGE_KEYS.batch, next_batch);
                    
                    $("#kblc-results-broken").prepend('<p><strong>Batch ' + (next_batch - 1) + ' complete.</strong></p>');
                }

            },
            error: function(xhr) {
                // The AJAX call itself failed (e.g., 500 error on the server)
                // *** THIS WAS THE LINE WITH THE BUG ***
                $notice_p.text('A fatal error occurred while scanning ' + post.title + '. Stopping scan.');
                console.error('AJAX Request Failed:', xhr.responseText);
                $check_button.prop("disabled", false).text("Check Next Batch");
            }
        });
    }

});