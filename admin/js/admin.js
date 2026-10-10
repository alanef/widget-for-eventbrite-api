(function (wp) {
    const {__, _x, _n, sprintf} = wp.i18n;
    (function ($) {
        'use strict';

        /**
         * All of the code for your public-facing JavaScript source
         * should reside in this file.
         *
         * Note: It has been assumed you will write jQuery code here, so the
         * $ function reference has been prepared for usage within the scope
         * of this function.
         *
         * This enables you to define handlers, for when the DOM is ready:
         *
         * $(function() {
         *
         * });
         *
         * When the window is loaded:
         *
         * $( window ).load(function() {
         *
         * });
         *
         * ...and/or other possibilities.
         *
         * Ideally, it is not considered best practise to attach more than a
         * single DOM-ready or window-load handler for a particular page.
         * Although scripts in the WordPress core, Plugins and Themes may be
         * practising this, we should strive to set a better example in our own work.
         *
         *
         */


        $(function () {

            $(document).on('click', '.wfea_notice .notice-dismiss', function (event) {
                let data = {
                    action: 'wfea_dismiss_notice',
                    nonce: wfea_data.nonce,
                    id: $(this).closest('div').attr('id')
                };

                $.post(ajaxurl, data, function (response) {
                    console.log(response, 'DONE!');
                });
            });


            $('#widget-for-eventbrite-api-setup-api-key').on('input', function () {
                $('#wfea-wizard .api-key-result').replaceWith('<p class="api-key-result active connecting"><span class="progress"></span>' + wfea_data.StringConnecting + '</p>');
                let input = $(this);
                let apikey = input.val();
                let nonce = jQuery(this).attr("data-nonce");
                if (apikey) {
                    $.ajax({
                        url: "https://www.eventbriteapi.com/v3/users/me/organizations/?token=" + apikey,
                        statusCode: {
                            401: function () {
                                $('.api-key-result').replaceWith('<p class="api-key-result active error">' + wfea_data.StringInvalidAPI + '</p>');
                            }
                        }
                    }).then(function (data) {
                        $.ajax({
                            type: "post",
                            dataType: "json",
                            url: wfea_data.ajaxurl,
                            data: {action: "update_api_option", apikey: apikey, nonce: nonce},
                            success: function (response) {
                                if (response.result == true) {
                                    $('#wfea-wizard .api-key-result').replaceWith('<p class="api-key-result active connected">' + wfea_data.StringConnected + '</p>');
                                    window.location.href = wfea_data.redirectURL;
                                }
                            }
                        });
                    });
                } else {
                    input.removeClass("valid").addClass("invalid");
                }
            });

            // The status is advisory only and never disables Save: the server validates the key
            // on save, and blocking here left an expired saved key impossible to replace.
            const wfeaCheckApikey = (input) => {
                const key = input.value;
                if (!key) {
                    return;
                }
                const row = $(input).closest('.wfea-api-key-row');

                $.ajax({
                    url: "https://www.eventbriteapi.com/v3/users/me/organizations/?token=" + key
                }).fail(function (jqXHR) {
                    // Responses arrive out of order while typing; ignore any for a value since replaced.
                    if (input.value !== key || 401 !== jqXHR.status) {
                        return;
                    }
                    $(input).removeClass("valid").addClass("invalid");
                    row.find('.api-key-status').replaceWith('<p class="api-key-status error">' + wfea_data.StringInvalidAPI + '</p>');
                    row.find('.api-key-result').empty();
                }).done(function (data) {
                    if (input.value !== key) {
                        return;
                    }
                    $(input).removeClass("invalid").addClass("valid");
                    row.find('.api-key-status').replaceWith('<p class="api-key-status connected">' + wfea_data.StringConnected + '</p>');

                    row.find('.api-key-result').empty().append('<h4>' + wfea_data.StringOrganisations + '</h4>')
                    var arrayLength = data.organizations.length;
                    for (var i = 0; i < arrayLength; i++) {
                        row.find('.api-key-result').append('<li class="api-key-org">' + data.organizations[i].name +
                            ' ID: ' + data.organizations[i].id
                            + '</li>');
                    }
                });
            };

            const maybeToggleAddApiKeyButtonState = () => {
                const emptyKeyInputs = jQuery('input[id^="widget-for-eventbrite-api-settings-api-key_"]').filter(function () {
                    return jQuery(this).val().trim() === '';
                });

                if (emptyKeyInputs.length !== 0) {
                    jQuery('.wfea-add-api-key').prop('disabled', true);
                } else {
                    jQuery('.wfea-add-api-key').prop('disabled', false);
                }
            };

            jQuery(document).on('input', (event) => {
                const target = event.target;
                if (!target.matches('input[id^="widget-for-eventbrite-api-settings-api-key_"]')) {
                    return;
                }
                maybeToggleAddApiKeyButtonState();
                wfeaCheckApikey(target)
            });

            maybeToggleAddApiKeyButtonState();

            $('input[id^="widget-for-eventbrite-api-settings-api-key_"]').each((index, target) => {
                wfeaCheckApikey(target)
            });

            
        });
    })(jQuery);

    
})(wp);

