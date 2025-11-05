jQuery(document).ready(function($) {
    $("#kblc-check-links").click(function() {
      
        let batch_size = $("#kblc-batch-size").val();
        let current_batch = $("#kblc-current-batch").val();
        
        // Disable button and show notice
        $(this).prop("disabled", true).text("Checking...");
        $("#kblc-checking-notice").show();

        $.ajax({
            url: kblc_ajax.ajax_url, // From wp_localize_script
            type: "post",
            data: {
                action: "kblc_ajax_check_links",
                nonce: kblc_ajax.nonce, // From wp_localize_script
                batch_size: batch_size,
                batch: current_batch
            },
            success: function(response) {

                // First, check if the AJAX call itself was successful
                if ( response.success ) {

                    // --- THIS IS THE CORRECTED LOGIC ---

                    // Check the data payload
                    if ( response.data === '0' ) {
                        // No more posts found
                        $("#kblc-checking-notice").hide();
                        $("#kblc-results").prepend('<p><strong>' + kblc_ajax.no_more_posts + '</strong></p>');
                        $("#kblc-check-links").prop("disabled", true).text("All Done");
                    
                    } else if ( response.data === '1' ) {
                        // Posts found, but no broken links
                        $("#kblc-checking-notice").hide();
                        $("#kblc-results").prepend('<p><em>' + kblc_ajax.checking_text + ' (Batch ' + current_batch + ')</em></p>');
                        
                        // Increment batch and re-enable button
                        $("#kblc-current-batch").val(parseInt(current_batch) + 1);
                        $("#kblc-check-links").prop("disabled", false).text("Check Next Batch");

                    } else {
                        // Broken links HTML was returned in response.data
                        $("#kblc-checking-notice").hide();
                        $("#kblc-results").prepend(response.data); // Prepend the new errors
                        
                        // Increment batch and re-enable button
                        $("#kblc-current-batch").val(parseInt(current_batch) + 1);
                        $("#kblc-check-links").prop("disabled", false).text("Check Next Batch");
                    }
                } else {
                    // Handle a {success: false} response (just in case)
                    $("#kblc-checking-notice").hide();
                    $("#kblc-results").prepend('<p><strong>' + kblc_ajax.error_text + ' (Server-side error)</strong></p>');
                    $("#kblc-check-links").prop("disabled", false).text("Check Next Batch");
                }
            },
            error: function() {
                // This handles a total failure (like a 500 error or network down)
                $("#kblc-checking-notice").hide();
                $("#kblc-results").prepend('<p><strong>' + kblc_ajax.error_text + ' (AJAX request failed)</strong></p>');
                $("#kblc-check-links").prop("disabled", false).text("Check Next Batch");
            }
        });
    });
});