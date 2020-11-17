jQuery(
    function ($) {

        const wpbkash = {
            bodyEl: $('body'),
            trigger: '#bkash_trigger',
            onTrigger: '#bkash_on_trigger',
            frontForm: '#wpbkash--frontend-form',

            // Address data.
            scriptloaded: false,

            /*
             * Document ready function. 
             * Runs on the $(document).ready event.
             */
            documentReady: function () {
                wpbkash.getAmount();
            },

            /*
            * Window Load function. 
            * Runs on when window will be load
            */
            onLoad: function () {
                wpbkash.getAmount();
            },

            getAmount: function () {
                var price = '';

                if ($('#wpbkash--frontend-form').find('.wpbkash--frontend-bdt').length) {
                    price = $('#wpbkash--frontend-form').find('.wpbkash--frontend-bdt').text();
                }

                if (typeof price === 'object') {
                    price = price[0];
                }

                price = price.replace(/\.00$/,'');
                return price;
            },
            getRedirect: function ($url) {
                if($url.length ) {
                    window.location.href = $url;
                }
            },
            submit_error_review: function (error_message) {
                $('.wpbkash--frontend-notice').html('');
                $('.wpbkash--frontend-notice').prepend(error_message); // eslint-disable-line max-len
            },
            scroll_to_notices: function( scrollElement ) {
                // Your changes ...
                if ( scrollElement.length ) {
                  $( 'html, body' ).animate( { scrollTop: ( scrollElement.offset().top - 100 ) }, 1000 );
                }
            },
            onbkashTrigger: function (e) {
                e.preventDefault();

                var $self = $(this);

                $self.addClass('wpbkash_processing');

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
                            wpbkash.wcbkashInit($self);
                        }
                    );
                } else {
                    wpbkash.wcbkashInit($self);
                }

                return false;
            },
            wcbkashInit: function ($self, redirect = '') {

                wpbkash.getAccesToken($self);

                var paymentRequest,
                paymentID;
                paymentRequest = {
                    amount: wpbkash.getAmount(),
                    intent: 'sale'
                };

                bKash.init(
                    {
                        paymentMode: 'checkout',
                        paymentRequest: paymentRequest,
                        createRequest: function (request) {
                            wpbkash.createPayment($self);
                        },
                        executeRequestOnAuthorization: function () {
                            wpbkash.executePayment($self);
                        },
                        onClose: function () {
                            if (redirect && redirect.length) {
                                window.location.href = redirect;
                            }
                            if( $('#bKashFrameWrapper').length ) {
                                setTimeout( function() {                            
                                    $('#bKashFrameWrapper').remove();
                                }, 250 );
                            }
                        }
                    }
                );

            },
            getOrderID: function ($param) {
                if (typeof $param === 'object') {
                    var get_id = $param.attr('data-id'),
                    entry_id = '';

                    if (typeof get_id !== typeof undefined && get_id !== false) {
                        entry_id = get_id;
                    }

                    return entry_id;
                } else if (typeof $param === 'string') {
                    return string;
                } else if (typeof $param === 'number') {
                    return $param;
                }
            },
            createPayment: function ($param) {
                $.ajax(
                    {
                        url: wpbkash_params.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'wpbkash_form_createpayment',
                            entry_id: wpbkash.getOrderID($param),
                            nonce: $('#wpbkash_nonce').val()
                        },
                        success: function (result) {
                            
                            $('.wpbkash_processing').removeClass('wpbkash_processing');

                            try {
                                if (result) {
                                    var obj = JSON.parse(result);
                                    if( obj.paymentID != null ) {
                                        paymentID = obj.paymentID;
                                        bKash.create().onSuccess(obj);
                                    } else {
                                        throw 'Invalid response';
                                    }
                                } else {
                                    throw 'Failed response';
                                }
                            } catch (err) {
                                // Add new errors
                                if( $(wpbkash.frontForm).length ) {
                                    wpbkash.submit_error_review('<div class="wpbkash--notice wpbkash--notice-error">' + wpbkash_params.i18n_error + '</div>'); // eslint-disable-line max-len
                                    wpbkash.scroll_to_notices( $('.wpbkash--frontend-notice') );
                                }
                                if( $('#bKashFrameWrapper').length ) {
                                    $('#bKashFrameWrapper').remove();
                                }
                            }
                        },
                        error: function () {
                            bKash.create().onError();
                        }
                    }
                );
            },
            executePayment: function ($param) {

                $.ajax(
                    {
                        url: wpbkash_params.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'wpbkash_form_executepayment',
                            paymentid: paymentID,
                            entry_id: wpbkash.getOrderID($param),
                            nonce: $('#wpbkash_nonce').val()
                        },
                        success: function (result) {
                            $('#wpbkash--frontend-form').addClass('wpbkash--finished');
                            $('#wpbkash--frontend-form').find('.wpbkash--frontend-inner').hide();
                    
                            if (result && true === result.success && result.data.transactionStatus != null && result.data.transactionStatus === 'completed') {
                                $('#wpbkash--frontend-form').find('.wpbkash--sucessfull-response').show();
                                wpbkash.getRedirect(result.data.order_url);
                            } else if(result && result.error && result.data.order_url ) {
                                $('#wpbkash--frontend-form').find('.wpbkash--error-response').show();
                                wpbkash.getRedirect(result.data.order_url);
                                bKash.execute().onError();
                            } else {
                                $('#wpbkash--frontend-form').find('.wpbkash--error-response').show();
                                window.location.reload();
                                bKash.execute().onError();
                            }
                        },
                        error: function () {
                            bKash.execute().onError();
                        }

                    }
                );
            },
            getAccesToken: function ($param) {
                $('#bKash_button').removeAttr('disabled');
                setTimeout(
                    function () {
                        $('#bKash_button').trigger('click');
                    }, 1000
                )
            },
            formSubmit: function (e) {
                if (wpbkash.checkIfbKashSelected()) {
                    wpbkash.WooCommerceCheckoutInit();
                    return false;
                }
            },

            /*
            * Initiates the script and sets the triggers for the functions.
            */
            init: function () {
                $(document).ready(wpbkash.documentReady());
                $(window).on('load', wpbkash.onLoad());
                $(document).on('click', '#bkash_on_trigger', wpbkash.onbkashTrigger);
            },
        }
        wpbkash.init();

    }
);