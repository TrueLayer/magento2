/**
 * Copyright © TrueLayer Ltd, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define(['jquery', 'mage/url', 'ko', 'Magento_Customer/js/customer-data', 'uiComponent'], function ($, url, ko, customerData, Component) {
    'use strict';

    return Component.extend({
        defaults: {
            isLoading: ko.observable(true),
            isError: ko.observable(false),
            requestCount: ko.observable(0),
            maxRequestCount: 30,
            statusUrl: url.build('truelayer/checkout/status'),
        },

        initialize() {
            this._super();
            this.checkStatus();
        },

        checkStatus() {
            if (this.requestCount() >= this.maxRequestCount) {
                this.isError(true);
                this.isLoading(false);
                return;
            }

            this.requestCount(this.requestCount() + 1);

            $.ajax({
                url: this.statusUrl + window.location.search + '&attempt=' + this.requestCount(),
                type: 'POST',
                dataType: 'json',
                success: (data) => {
                    if (data && data.redirect) {
                        var sections = ['cart', 'checkout-data'];
                        customerData.invalidate(sections);
                        customerData.reload(sections, true);
                        window.location.replace(data.redirect);
                    }
                },
                complete: () => {
                    setTimeout(
                        this.checkStatus.bind(this),
                        Math.min(this.requestCount() * 2000, 30000)
                    );
                }
            })
        }
    });
});


/*
 require([
         'Magento_Customer/js/customer-data'
     ], function (customerData) {
         var sections = ['cart'];
         customerData.invalidate(sections);
         customerData.reload(sections, true);
     });
 */