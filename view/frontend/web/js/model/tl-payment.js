/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
/*browser:true*/
/*global define*/
define(['jquery', 'ko', 'mage/url'], function ($, ko, url) {
    'use strict';

    var query = ko.observable(null);
    var isLoading = ko.observable(false);
    var paymentId = ko.observable(null);
    var resourceToken = ko.observable(null);
    var error = ko.observable(null);
    var paymentUrl = url.build('/truelayer/checkout/payment');

    return {
        paymentId: paymentId,
        resourceToken: resourceToken,
        isLoading: isLoading,
        error: error,
        query: query,
        load: function() {
            // Abort any in-flight requests to avoid race conditions
            if (query()) {
                query().abort();
            }

            // Update payment related state
            isLoading(true);
            paymentId(null);

            // Make new request
            query($.ajax({
                url: paymentUrl,
                type: 'POST',
                dataType: 'json',
                contentType: 'application/json',
                error: function (xhr, textStatus, errorThrown) {
                    if (errorThrown !== 'abort') {
                        error(errorThrown || textStatus);
                    }
                },
                success: function(data) {
                    paymentId(data.payment_id);
                    resourceToken(data.resource_token);
                },
                complete: function() {
                    isLoading(false);
                }
            }));
        },
        clear: function () {
            isLoading(false);
            error(null);
            paymentId(null);
            resourceToken(null);
        }
    }
});
