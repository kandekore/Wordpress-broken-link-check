jQuery(document).ready(function($) {
    // --- NEW ---
    // Initialize tabs
    var $tabs = $("#kblc-tabs").tabs();

    // --- NEW ---
    // Define storage keys
    var STORAGE_KEYS = {
        broken: 'kblc_results_broken',
        working: 'kblc_results_working',
        checked: 'kblc_results_checked',
        batch: 'kblc_current_batch'
    };

    // --- NEW ---
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

    // --- NEW ---
    // Click handler for the "Clear Results" button
    $("#kblc-clear-results").click(function() {
        if (confirm(kblc_ajax.clear_confirm)) {
            // Clear storage
            sessionStorage.removeItem(STORAGE_KEYS.broken);
            sessionStorage.removeItem(STORAGE_KEYS.working);
            sessionStorage.removeItem(STORAGE_KEYS.checked);
            sessionStorage.removeItem(STORAGE_KEYS.batch);

            // Clear HTML and reset batch
            loadResultsFromStorage();
            $("#kblc-check-links").prop("disabled", false).text("Check Next Batch");
            console.log('Results cleared.');
        }
    });

    // Main "Check Links" button click handler
    $("#kblc-check-links").click(function() {
        
        var $button = $(this);
        var batch_size = $("#kblc-batch-size").val();
        var current_batch = $("#kblc-current-batch").val();
        
        // Disable button and show notice
        $button.prop("disabled", true).text("Checking...");
        $("#kblc-checking-notice").show();

        $.ajax({
            url: kblc_ajax.ajax_url,
            type: "post",
            data: {
                action: "kblc_ajax_check_links",
                nonce: kblc_ajax.nonce,
                batch_size: batch_size,
                batch: current_batch
            },
            success: function(response) {
                console.log('AJAX Success Response:', response);

                // Check for 'finished' signal
                if (response.finished) {
                    $("#kblc-checking-notice").hide();
                    $("#kblc-results-broken").prepend('<p><strong>' + kblc_ajax.no_more_posts + '</strong></p>');
                    $button.prop("disabled", true).text("All Done");
                    sessionStorage.removeItem(STORAGE_KEYS.batch); // Reset batch for next time
                    return;
                }

                if (response.success) {
                    var data = response.data;
                    var has_results = false;

                    // --- NEW: Append to correct tabs ---

                    if (data.broken && data.broken.length > 0) {
                        has_results = true;
                        var broken_html = sessionStorage.getItem(STORAGE_KEYS.broken) || '';
                        broken_html = data.broken.join('') + broken_html; // Prepend new results
                        $("#kblc-results-broken").html(broken_html);
                        sessionStorage.setItem(STORAGE_KEYS.broken, broken_html);
                    }

                    if (data.working && data.working.length > 0) {
                        has_results = true;
                        var working_html = sessionStorage.getItem(STORAGE_KEYS.working) || '';
                        working_html = data.working.join('') + working_html; // Prepend new results
                        $("#kblc-results-working").html(working_html);
                        sessionStorage.setItem(STORAGE_KEYS.working, working_html);
                    }

                    if (data.checked && data.checked.length > 0) {
                        var checked_html = sessionStorage.getItem(STORAGE_KEYS.checked) || '';
                        checked_html = data.checked.join('') + checked_html; // Prepend new results
                        $("#kblc-results-checked").html(checked_html);
                        sessionStorage.setItem(STORAGE_KEYS.checked, checked_html);
                    }
                    
                    // Show a "no new issues" message if scan ran but found nothing
                    if ( !has_results && data.checked.length > 0 ) {
                         $("#kblc-results-broken").prepend('<p><em>' + kblc_ajax.checking_text.replace('{batch}', current_batch) + '</em></p>');
                    }

                    // Increment batch and re-enable button
                    var next_batch = parseInt(current_batch) + 1;
                    $("#kblc-current-batch").val(next_batch);
                    sessionStorage.setItem(STORAGE_KEYS.batch, next_batch);
                    
                    $button.prop("disabled", false).text("Check Next Batch");
                    $("#kblc-checking-notice").hide();

                } else {
                    // Handle a {success: false} response
                    console.error('AJAX Error (Success: false):', response);
                    $("#kblc-checking-notice").hide();
                    $("#kblc-results-broken").prepend('<p><strong>' + kblc_ajax.error_text + ' (Server-side error. Check console.)</strong></p>');
                    $button.prop("disabled", false).text("Check Next Batch");
                }
            },
            error: function(xhr, status, error) {
                // This handles a total failure (like a 500 error or network down)
                console.error('AJAX Request Failed:', status, error, xhr.responseText);
                $("#kblc-checking-notice").hide();
                $("#kblc-results-broken").prepend('<p><strong>' + kblc_ajax.error_text + ' (AJAX request failed. Check console.)</strong></p>');
                $button.prop("disabled", false).text("Check Next Batch");
            }
        });
    });
});