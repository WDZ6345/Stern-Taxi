jQuery(document).ready(function($) {
    // Fahrer genehmigen/sperren
    $('.approve-driver-btn, .unapprove-driver-btn').on('click', function(e) {
        e.preventDefault();
        const $button = $(this);
        const driverId = $button.data('driver-id');
        const action = $button.hasClass('approve-driver-btn') ? 'approve' : 'unapprove';
        const $statusCell = $button.closest('tr').find('.column-driver_approved');
        const $spinner = $('<span class="spinner is-active inline"></span>');

        // Disable button and show spinner
        $button.addClass('processing').prop('disabled', true);
        $button.parent().append($spinner);


        $.ajax({
            url: wp_taxi_admin_params.ajax_url,
            type: 'POST',
            data: {
                action: 'wp_taxi_approve_driver',
                nonce: wp_taxi_admin_params.nonce_approve_driver,
                driver_id: driverId,
                approve_action: action
            },
            success: function(response) {
                if (response.success) {
                    $statusCell.html(response.data.new_status_text);
                    if (response.data.is_approved) {
                        $statusCell.removeClass('status-no status-not-approved').addClass('status-yes status-approved');
                        $button.removeClass('approve-driver-btn').addClass('unapprove-driver-btn')
                               .text(wp_taxi_admin_params.text_unapprove_driver_btn || 'Sperren')
                               .data('original-text', wp_taxi_admin_params.text_unapprove_driver_btn || 'Sperren')
                               .css({'background-color': '#ffe0e0', 'border-color': '#d9534f', 'color': '#a94442'});

                    } else {
                        $statusCell.removeClass('status-yes status-approved').addClass('status-no status-not-approved');
                        $button.removeClass('unapprove-driver-btn').addClass('approve-driver-btn')
                               .text(wp_taxi_admin_params.text_approve_driver_btn || 'Genehmigen')
                               .data('original-text', wp_taxi_admin_params.text_approve_driver_btn || 'Genehmigen')
                               .css({'background-color': '#e0ffe0', 'border-color': '#5cb85c', 'color': '#3d8b3d'});
                    }
                    // alert(response.data.message); // Optional: Erfolgsmeldung
                } else {
                    alert(response.data.message || wp_taxi_admin_params.text_error_approving);
                }
            },
            error: function() {
                alert(wp_taxi_admin_params.text_error_approving + ' (Request Failed)');
            },
            complete: function() {
                $button.removeClass('processing').prop('disabled', false);
                $spinner.remove();
            }
        });
    });
});
