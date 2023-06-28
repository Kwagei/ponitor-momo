(function ($, window, document) {
    "use strict";

    $(document).ready(function () {

        // Handle back btn event
        $("#confirm_payment_btn").on('click', function (event) {

            $("#confirm_payment_btn").attr("disabled", true);
            $(".loader").show()
            $("#message_ponitor").show()
            $("#message_ponitor").html('<div class="woocommerce-info">Processing....</div>');

            const order_id = $('#order_id').val();
            const url = params?.ajaxUrl;

            if (url) {

                // Makes request to wp_ajax_object.ajaxUrl, not defined in the file but hooked in by wordpress
                const request = $.ajax({
                    url,
                    contentType: "application/x-www-form-urlencoded",
                    cache: false,
                    data: {action: "confirm_payment", order_id: order_id}
                });

                request.done(function(result) {

                    $("#confirm_payment_btn").attr("disabled", false);
                    const data = result.data;

                    if (data?.success) {

                        $(".loader").hide();

                        $("#message_ponitor").html('<div class="woocommerce-message">Order confirmation Successful! Redirecting now...</div>')

                        setTimeout(() => window.location.href = window.location.href = $('#order_complete_url').val(), 3000);

                    } else if (data?.failed) {

                        $(".loader").hide();

                        $("#message_ponitor").html('<div class="woocommerce-error"> Payment confirmation Failed! Cancelling order...</div>')

                        setTimeout(() => window.location.href = $('#order_cancel_url').val(), 3000);

                    } else {

                        $(".loader").hide();

                        $("#message_ponitor").html('<div class="woocommerce-info">Your payment is pending confirmation. If you have already confirmed and you are still seeing this message, please try again by clicking "Confirm Payment". <br><br>If you have not yet confirmed your payment, please check your approvals list to confirm by dialing 156*8*2#.</div>');
                    }

                });

                request.fail(function(jqXHR, textStatus ) {

                    $("#message_ponitor").html('<div class="woocommerce-error">An error occurred. Please try again.</div>');

                    $(".loader").hide();

                    $("#confirm_payment_btn").attr("disabled", false);

                    setTimeout(() => window.location.href = $('#order_cancel_url').val(), 2500);

                });

            }

        })

    });


})(jQuery, window, document);