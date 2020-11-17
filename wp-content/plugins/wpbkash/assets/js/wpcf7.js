jQuery(
    function ($) {

        const wpbkash_wpcf7 = {
            bodyEl: $('body'),
            wpcf7Form: 'form#wpcf7-admin-form-element',
            $wpcf7Form: $('form#wpcf7-admin-form-element'),
            $trigger: $('#wpcf7-wpbkash-enable'),
            $email: $('#wpcf7-wpbkash-email'),
            $amount: $('#wpcf7-wpbkash-amount'),
            $disabled: $('#wpcf7-wpbkash-confirm-disabled'),
            $shortcode: $('#wpcf7-wpbkash-pay'),
            error: true,

            /*
             * Document ready function. 
             * Runs on the $(document).ready event.
             */
            documentReady: function () {
                wpbkash_wpcf7.$wpcf7Form.on('submit', wpbkash_wpcf7.formSubmit);
            },

            checkEmailField: function () {
                if(!wpbkash_wpcf7.$email.val().length ) {
                    wpbkash_wpcf7.$email.addClass('error');
                    return false;
                }
                return true;
            },

            checkMailBody: function () {
                if(!wpbkash_wpcf7.$shortcode.val().length ) {
                    wpbkash_wpcf7.$shortcode.addClass('error');
                    return false;
                }
                return true;
            },
        
            formSubmit: function (e) {
                var error = false;

                if(wpbkash_wpcf7.$trigger.is(':checked') ) {

                    $('.wpbkash_error_msg').remove();

                    if(!wpbkash_wpcf7.$email.val().length ) {
                        wpbkash_wpcf7.$email.addClass('error');
                        wpbkash_wpcf7.$email.after('<span class="wpbkash_error_msg"><span class="icon-in-circle" aria-hidden="true">!</span> '+wpbkash_wpcf7_params.email_error+'</span>');
                        error = true;
                    } else {
                        var tag_val = wpbkash_wpcf7.$email.val();
                        tag_val = tag_val.replace(/[\[\]']+/g,'');
                    
                        var regex = new RegExp(tag_val,);
                        if(regex.test($('#wpcf7-form').val()) === false ) {
                            wpbkash_wpcf7.$email.addClass('error');
                            wpbkash_wpcf7.$email.after('<span class="wpbkash_error_msg"><span class="icon-in-circle" aria-hidden="true">!</span> '+wpbkash_wpcf7_params.valid_tag+'</span>');
                            error = true;
                        }
                    }
                    if(!wpbkash_wpcf7.$amount.val().length ) {
                        wpbkash_wpcf7.$amount.addClass('error');
                        wpbkash_wpcf7.$amount.after('<span class="wpbkash_error_msg"><span class="icon-in-circle" aria-hidden="true">!</span> '+wpbkash_wpcf7_params.amount_error+'</span>');
                        error = true;
                    }
                
                    if(!wpbkash_wpcf7.$shortcode.val().length ) {
                        wpbkash_wpcf7.$shortcode.addClass('error');
                        wpbkash_wpcf7.$shortcode.after('<span class="wpbkash_error_msg"><span class="icon-in-circle" aria-hidden="true">!</span> '+wpbkash_wpcf7_params.message_error+'</span>');
                        error = true;
                    } else if(/\[(wpbkash-paymenturl)\]/.test(wpbkash_wpcf7.$shortcode.val()) === false ) {
                        wpbkash_wpcf7.$shortcode.addClass('error');
                        wpbkash_wpcf7.$shortcode.after('<span class="wpbkash_error_msg"><span class="icon-in-circle" aria-hidden="true">!</span> '+wpbkash_wpcf7_params.url_error+'</span>');
                        error = true;
                    }

                    if(error ) {
                        e.preventDefault();
                        if($(this).find('input[type="submit"][name="wpcf7-save"]').prev('span.is-active').length ) {
                            $(this).find('input[type="submit"][name="wpcf7-save"]').prev('span.is-active').removeClass('is-active');
                        }
                    }
                }
            },

            disabled: function(){
                if( $(this).is(':checked') ) {
                    $('#wpcf7-wpbkash-confirm').attr('disabled', 'disabled');
                    $('#wpcf7-wpbkash-confirm-use-html').attr('disabled', 'disabled');
                } else {
                    $('#wpcf7-wpbkash-confirm').removeAttr('disabled');
                    $('#wpcf7-wpbkash-confirm-use-html').removeAttr('disabled');
                }
            },
            /*
            * Initiates the script and sets the triggers for the functions.
            */
            init: function () {
                $(document).ready(wpbkash_wpcf7.documentReady());
                wpbkash_wpcf7.$disabled.on('change', wpbkash_wpcf7.disabled);
            },
        }
        wpbkash_wpcf7.init();

    }
);