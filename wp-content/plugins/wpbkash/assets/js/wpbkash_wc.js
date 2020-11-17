jQuery(
    function ($) {

        const wpbkash = {
            bodyEl: $('body'),
            checkoutFormSelector: 'form.checkout',
            orderReview: 'form#order_review',
            $checkoutFormSelector: $('form.checkout'),
            trigger: '#bkash_trigger',
            onTrigger: '#bkash_on_trigger',

            // Order notes.
            orderNotesValue: '',
            orderNotesSelector: 'textarea#order_comments',
            orderNotesEl: $('textarea#order_comments'),

            // Payment method
            paymentMethodEl: $('input[name="payment_method"]:checked'),
            paymentMethod: '',
            selectAnotherSelector: '#paysoncheckout-select-other',

            // Address data.
            accessToken: '',
            scriptloaded: false,
            checkoutProcess: false,
            singleton: false,


            blockOnSubmit: function ($form) {
                var form_data = $form.data();

                if (1 !== form_data['blockUI.isBlocked']) {
                    $form.block({
                        message: null,
                        overlayCSS: {
                            background: '#fff',
                            opacity: 0.6
                        }
                    });
                }
            },
            /*
             * Check if Payson is the selected gateway.
             */
            checkIfbKashSelected: function () {
                if ($(wpbkash.checkoutFormSelector).length && $('input[name="payment_method"]').val().length > 0) {
                    wpbkash.paymentMethod = $('input[name="payment_method"]:checked').val();
                    if ('wpbkash' === wpbkash.paymentMethod) {
                        return true;
                    }
                }
                return false;
            },

            wcbkashTrigger: function () {

                if (!wpbkash.checkIfbKashSelected()) {
                    return false;
                }

                if (!wpbkash.scriptloaded) {
                    $.when(
                        $.getScript(wpbkash_params.scriptUrl),
                        $.Deferred(
                            function (deferred) {
                                $(deferred.resolve);
                            }
                        )
                    ).done(
                        function () {
                            window.$ = jQuery.noConflict(true);
                            wpbkash.scriptloaded = true;
                            wpbkash.wcbkashInit();
                        }
                    );
                } else {
                    wpbkash.wcbkashInit();
                }

                return false;
            },

            wcbkashInit: async function (order_id = '', redirect = '') {
                
                wpbkash.getTrigger();

                var paymentRequest,
                    paymentID;

                paymentRequest = await wpbkash.getOrderData(order_id);
                // return false;
                bKash.init({
                    paymentMode: 'checkout',
                    paymentRequest: paymentRequest,
                    createRequest: function (request) {
                        wpbkash.createPayment(order_id);
                    },
                    executeRequestOnAuthorization: function () {
                        wpbkash.executePayment(order_id);
                    },
                    onClose: function () {
                        if( $(wpbkash.checkoutFormSelector).length ) {
                            $(wpbkash.checkoutFormSelector).removeClass('processing').unblock();
                        }
                        alertify.error("Payment process cancelled");
                        if( $('#bkash_on_trigger').length > 0 && $('#bkash_on_trigger').hasClass('wpbkash_processing') ) {
                            $('#bkash_on_trigger').removeClass('wpbkash_processing');
                        }
                        if( $('#bKashFrameWrapper').length ) {
                            $('#bKashFrameWrapper').remove();
                        }
                        if (redirect && redirect.length) {
                            window.location.href = redirect;
                        }
                    }
                });

            },
            createPayment: function (order_id = '') {
                if( wpbkash.singleton ) {
                    return false;
                }
                wpbkash.singleton = true;
                $.ajax({
                    url: wpbkash_params.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'wpbkash_createpayment',
                        order_id: order_id,
                        nonce: $('#wpbkash_nonce').val()
                    },
                    success: function (result) {
                        wpbkash.singleton = false;
                        try {
                            if (result) {
                                var obj = JSON.parse(result);
                                if( obj.paymentID != null ) {
                                    paymentID = obj.paymentID;
                                    bKash.create().onSuccess(obj);
                                } else {
                                    if( $(wpbkash.checkoutFormSelector).length !== 0 ) {
                                        $(wpbkash.checkoutFormSelector).removeClass('processing').unblock();
                                    }
                                    if( $(wpbkash.orderReview).length !== 0 ) {
                                        $(wpbkash.orderReview).removeClass('processing').unblock();
                                    }
                                    alertify.error(wpbkash.errorMessage(result));
                                    bKash.execute().onError();
                                    throw 'Invalid response';
                                }
                            } else {
                                if( $(wpbkash.checkoutFormSelector).length !== 0 ) {
                                    $(wpbkash.checkoutFormSelector).removeClass('processing').unblock();
                                }
                                if( $(wpbkash.orderReview).length !== 0 ) {
                                    $(wpbkash.orderReview).removeClass('processing').unblock();
                                }
                                alertify.error(wpbkash.errorMessage(result));
                                bKash.execute().onError();
                                throw 'Failed response';
                            }
                        } catch (err) {
                            if( $(wpbkash.checkoutFormSelector).length !== 0 ) {
                                $(wpbkash.checkoutFormSelector).removeClass('processing').unblock();
                            }
                            if( $(wpbkash.orderReview).length !== 0 ) {
                                $(wpbkash.orderReview).removeClass('processing').unblock();
                            }
                            alertify.error(wpbkash.errorMessage(result));
                            bKash.execute().onError();
                            if( $('#bKashFrameWrapper').length ) {
                                $('#bKashFrameWrapper').remove();
                            }
                        }
                    },
                    error: function () {
                        wpbkash.singleton = false;
                        if( $(wpbkash.checkoutFormSelector).length !== 0 ) {
                            $(wpbkash.checkoutFormSelector).removeClass('processing').unblock();
                        }
                        if( $(wpbkash.orderReview).length !== 0 ) {
                            $(wpbkash.orderReview).removeClass('processing').unblock();
                        }
                        alertify.error(wpbkash.errorMessage());
                        bKash.create().onError();
                    }
                });
            },
            executePayment: function (order_id = '') {
                $.ajax({
                    url: wpbkash_params.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'wpbkash_executepayment',
                        paymentid: paymentID,
                        order_id: order_id,
                        nonce: $('#wpbkash_nonce').val()
                    },
                    success: function (result) {
                        if( $(wpbkash.checkoutFormSelector).length !== 0 ) {
                            $(wpbkash.checkoutFormSelector).removeClass('processing').unblock();
                        }
                        if( $(wpbkash.orderReview).length !== 0 ) {
                            $(wpbkash.orderReview).removeClass('processing').unblock();
                        }
                        if (result && true === result.success && result.data.transactionStatus != null && result.data.transactionStatus === 'completed') {
                            alertify.success('Successfully payment completed');
                            $('#bkash_checkout_valid').val('2').change();
                            if( $(wpbkash.checkoutFormSelector).length !== 0 ) {
                                $(wpbkash.checkoutFormSelector).trigger('submit');
                            }
                            if( $(wpbkash.orderReview).length !== 0 ) {
                                $(wpbkash.orderReview).trigger('submit');
                            }
                            alertify.message('Order is being processed', 0);
                            bKash.execute().onError();

                        } else if (result && result.error && result.data.order_url) {
                            if( result.data.message ) {
                                alertify.error(result.data.message);
                            }
                            bKash.execute().onError();
                        } else {
                            if( result.data.message  ) {
                                alertify.error(result.data.message);
                            }
                            bKash.execute().onError();
                        }
                    },
                    error: function () {
                        if( $(wpbkash.checkoutFormSelector).length !== 0 ) {
                            $(wpbkash.checkoutFormSelector).removeClass('processing').unblock();
                        }
                        alertify.error(wpbkash.errorMessage());
                        bKash.execute().onError();
                    }
                });
            },
            getOrderData: async function(order_id = '') {
                return await wpbkash.getPayData(order_id).then(response => {
                  if (response.success) {
                    return {
                      amount: response.data.amount,
                      intent: "sale",
                      merchantInvoiceNumber: response.data.invoice
                    }
                  }
                });
            },
            getTrigger: function () {
                $('#bKash_button').removeAttr('disabled');
                setTimeout(
                    function () {
                        $('#bKash_button').trigger('click');
                    }, 1000
                )
            },
            getPayData: function(order_id = '') {          
                return $.ajax({
                  url: wc_checkout_params.ajax_url,
                  data: {
                    action: "wpbkash_get_orderdata",
                    order_id: order_id,
                    nonce: $('#wpbkash_nonce').val()
                  },
                  method: "POST",
                });
            },
            errorTrigger: function() {
                var error_count = $('.woocommerce-error li').length,
                    bkash_method = $('#payment_method_wpbkash');

                if ( bkash_method.is(':checked') && error_count == 1 && $('.woocommerce-error li[data-id="bkash-payment-required"]').length ) { // Validation Passed (Just the Fake Error I Created Exists)
                    $('.woocommerce-error li[data-id="bkash-payment-required"]').closest('div').hide();
                    $( 'html, body' ).stop();
                    alertify.success("bKash Payment processing...");
                    $(wpbkash.checkoutFormSelector).addClass('processing');
                    wpbkash.wcbkashTrigger();
                }
            },
            errorMessage: function(msg) {
                return ( msg !== undefined && msg.errorMessage ) ? msg.errorMessage : 'Payment failed due to technical reasons';
            },

            blockOnSubmit: function ($form) {
                var form_data = $form.data();

                if (1 !== form_data['blockUI.isBlocked']) {
                    $form.block({
                        message: null,
                        overlayCSS: {
                            background: '#fff',
                            opacity: 0.6
                        }
                    });
                }
            },

            orderReviewSubmit: function (e) {
                var $form = $(this).closest('form');
                var method = $form.find('input[name="payment_method"]:checked').val();
                
                if ('wpbkash' === method && $('#bkash_checkout_valid').val() === '1') {
                    e.preventDefault();

                    wpbkash.blockOnSubmit($form);

                    var redirect = $form.find('input[name="_wp_http_referer"]').val().match(/^.*\/(\d+)\/.*$/),
                        order_id = redirect[1];

                    if (!wpbkash.scriptloaded) {
                        $.when(
                            $.getScript(wpbkash_params.scriptUrl),
                            $.Deferred(
                                function (deferred) {
                                    $(deferred.resolve);
                                }
                            )
                        ).done(
                            function () {
                                window.$ = jQuery.noConflict(true);
                                wpbkash.scriptloaded = true;
                                wpbkash.wcbkashInit(parseInt(order_id), redirect[0]);
                                return false;
                            }
                        );
                    } else {
                        wpbkash.wcbkashInit(parseInt(order_id), redirect[0]);
                        
                    }

                    return false;
                }
            },

            /*
             * Initiates the script and sets the triggers for the functions.
             */
            init: function () {
                $(document.body).on('checkout_error', wpbkash.errorTrigger);
                $('#place_order').on('click', wpbkash.orderReviewSubmit);
            },
        }
        wpbkash.init();

    }
);