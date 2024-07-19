/**
 * Copyright © TrueLayer Ltd, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define(['jquery', 'mage/url', 'ko', 'uiComponent'], function ($, url, ko, Component) {
    'use strict';

    return Component.extend({
        defaults: {
            requestCount: ko.observable(0),
            maxRequestCount: 15,
            statusUrl: url.build('/truelayer/checkout/status'),
            unknownStatusUrl: url.build('/truelayer/checkout/pending'),
            isRedirecting: false,
        },

        initialize() {
            this._super();
            this.checkStatus();
        },

        checkStatus() {
            if (this.requestCount() >= this.maxRequestCount) {
                $.mage.redirect(this.unknownStatusUrl + window.location.search);
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
                        $.mage.redirect(data.redirect);
                    }
                },
                complete: function () {
                    if (!this.isRedirecting) {
                        setTimeout(this.checkStatus.bind(this), this.requestCount() * 1500);
                    }
                }.bind(this)
            })
        }
    });
});