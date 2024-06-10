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
            maxRequestCount: 45,
            delay: 4000,
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

            fetch(window.location.href + '&json=1')
                .then((res) => {
                    if (!res.ok) return false;
                    this.requestCount(this.requestCount() + 1);
                    return res.json();
                })
                .then((json) => {
                    const timer = setTimeout(() => {
                        if (json && json.redirect) {
                            window.location.replace(json.redirect);
                        } else {
                            this.checkStatus();
                        }

                        clearTimeout(timer);
                    }, this.delay);
                })
                .catch(() => this.checkStatus());
        }
    });
});