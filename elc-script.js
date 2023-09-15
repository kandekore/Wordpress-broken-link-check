jQuery(document).ready(function($) {
    $("#check-broken-links").click(function() {
        let batch_size = $("#batch-size").val();
        let current_batch = $("#current-batch").val();

        $("#checking-notice").show();

        $.ajax({
            url: ajaxurl,
            type: "post",
            data: {
                action: "check_broken_links",
                batch_size: batch_size,
                batch: current_batch  // Sending the current batch number
            },
            success: function(response) {
                $("#checking-notice").hide();
                $("#broken-links-result").html(response);

                if (response.trim() === "") {
                    $("#broken-links-result").html("No broken links found in this batch.");
                }

                // Increment the current batch for next run
                $("#current-batch").val(parseInt(current_batch) + 1);
            },
            error: function() {
                $("#checking-notice").hide();
                $("#broken-links-result").html("An error occurred while checking the links.");
            }
        });
    });
});
