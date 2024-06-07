/**
 * Copyright © TrueLayer Ltd, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define(['ko', 'uiComponent'], function (ko, Component) {
    'use strict';

    return Component.extend({
        defaults: {
            isLoading: ko.observable(true),
            isError: ko.observable(false),
            requestCount: ko.observable(0),
            maxRequestCount: 20,
            delay: 2500,
        },

        initialize() {
            this._super();
            this.checkStatus();
        },

        checkStatus() {
            if (this.requestCount() >= this.maxRequestCount) {
                this.isError(true);
                this.isLoading(false);
            }

            fetch(this.checkUrl)
                .then((res) => {
                    if (!res.ok) return false;
                    this.requestCount(this.requestCount() + 1);
                    return res.json();
                })
                .then((json) => {
                    const timer = setTimeout(() => {
                        json === false ? this.checkStatus() : this.away();
                        clearTimeout(timer);
                    }, this.delay);
                })
                .catch(() => this.checkStatus());
        },

        away() {
            this.requestCount(this.maxRequestCount);
            window.location.replace(this.refreshUrl);
        }
    });
});