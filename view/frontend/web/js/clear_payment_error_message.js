/**
 * Copyright © TrueLayer Ltd, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define(['jquery', 'mage/url', 'ko', 'uiComponent'], function ($, url, ko, Component) {
    'use strict';

    return Component.extend({
        defaults: {
            url: url.build('/truelayer/checkout/clearpaymenterror'),
            renderAttemptsCount: ko.observable(0),
            maxRenderAttemptsCount: 30,
            isQueued: ko.observable(false),
            isClearing: ko.observable(false),
            timeout: 10 * 1000,
        },
        initialize() {
            this._super();
            if (this.hasError) {
                this.showError();
            }
        },
        showError() {
            if (this.renderAttemptsCount() >= this.maxRenderAttemptsCount) {
                return;
            }
            this.renderAttemptsCount(this.renderAttemptsCount() + 1);
            console.log('attempt', this.renderAttemptsCount());
            let element = $('#truelayer-payment-error-message');
            if (element.length) {
                $('#truelayer-payment-error-message').text(this.errorMessage);
                $('#truelayer-payment-error-message').show();
                this.clearAfterDelay();
            } else {
                setTimeout(this.showError.bind(this), 300)
            }
        },
        clearAfterDelay() {
            if (!this.isQueued()) {
                this.isQueued(true);
                setTimeout(this.clear.bind(this), this.timeout);
            }
        },
        clear() {
            if (!this.isClearing()) {
                this.isClearing(true);
                $.ajax({
                    url: this.url,
                    type: 'GET',
                    dataType: 'json',
                    contentType: 'application/json',
                    success: (data) => {
                        if (data) {
                            this.hasError = !data.done ?? true;
                            this.errorMessage = '';
                        }
                    },
                    complete: () => {
                        this.isQueued(false);
                        this.isClearing(false);
                    },
                    error: (err) => {}
                });
            }
        },
    });
});
