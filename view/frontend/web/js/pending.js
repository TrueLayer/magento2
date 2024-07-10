/**
 * Copyright © TrueLayer Ltd, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define(['jquery', 'mage/url', 'ko', 'uiComponent'], function ($, url, ko, Component) {
    'use strict';

    return Component.extend({
        defaults: {
            isLoading: ko.observable(true),
            isError: ko.observable(false),
            requestCount: ko.observable(0),
            maxRequestCount: 30,
            statusUrl: url.build('/truelayer/checkout/status'),
            isRedirecting: false,
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
                contentType: 'application/json',
                success: (data) => {
                    if (data && data.redirect) {
                        this.isRedirecting = true;
                        window.location.replace(data.redirect);
                    }
                },
                complete: () => {
                    if (!this.isRedirecting) {
                        setTimeout(this.checkStatus.bind(this), 2000);
                    }
                }
            })
        }
    });
});
