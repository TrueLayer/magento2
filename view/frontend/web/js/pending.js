/**
 * Copyright © TrueLayer Ltd, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define(['ko', 'uiComponent'], function (ko, Component) {
    'use strict';

    return Component.extend({
        defaults: {
            isShowLoader: ko.observable(true),
            isSuccess: ko.observable(false),
            isError: ko.observable(false),
            requestCount: ko.observable(0),
            maxRequestCount: 3,
            delay: 2500,
        },

        initialize() {
            this._super();
            this.getRequest();
        },

        getRequest() {
            if (this.requestCount() < this.maxRequestCount) {
                fetch(this.checkUrl)
                    .then((res) => {
                        if (!res.ok) throw new Error();
                        this.requestCount(this.requestCount() + 1);
                        return res.json();
                    })
                    .then((json) => {
                        const timer = setTimeout(() => {
                            json === false ? this.getRequest() : this.successOrder();
                            clearTimeout(timer);
                        }, this.delay);
                    })
                    .catch(() => this.errorOrder());
            } else {
                this.errorOrder();
            }
        },

        successOrder() {
            this.requestCount(this.maxRequestCount);
            this.isShowLoader(false);
            this.isSuccess(true);
            window.location.replace(this.refreshUrl);
        },

        errorOrder() {
            this.isShowLoader(false);
            this.isError(true);
        }
    });
});
