/**
 * Copyright © TrueLayer Ltd, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define(['jquery', 'mage/url', 'ko', 'uiComponent'], function ($, url, ko, Component) {
    'use strict';

    return Component.extend({
        defaults: {
            url: url.build('/truelayer/checkout/clearpaymenterror'),
            isQueued: ko.observable(false),
            isClearing: ko.observable(false),
            timeout: 5 * 1000,
        },
        initialize() {
            this._super();
            if (this.hasError) {
                this.clearAfterDelay()
            }
        },
        clearAfterDelay() {
            this.isQueued(true);
            setTimeout(this.clear.bind(this), this.timeout);
            $('#truelayer-payment-error-message').delay(this.timeout).fadeOut(1000);
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
                        }
                    },
                    complete: () => {
                        this.isQueued(false);
                        this.isClearing(false);
                        this.clearAfterDelay();
                    },
                    error: (err) => {}
                });
            }
        },
    });
});
/**
 * Copyright © TrueLayer Ltd, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

// define(['jquery', 'mage/url', 'ko', 'uiComponent'], function ($, url, ko, Component) {
//     'use strict';

//     return Component.extend({
//         defaults: {
//             isLoading: ko.observable(true),
//             isError: ko.observable(false),
//             requestCount: ko.observable(0),
//             maxRequestCount: 15,
//             statusUrl: url.build('/truelayer/checkout/status'),
//             isRedirecting: false,
//         },

//         initialize() {
//             this._super();
//             this.checkStatus();
//         },

//         checkStatus() {
//             if (this.requestCount() >= this.maxRequestCount) {
//                 this.isError(true);
//                 this.isLoading(false);
//                 return;
//             }

//             this.requestCount(this.requestCount() + 1);

//             $.ajax({
//                 url: this.statusUrl + window.location.search + '&attempt=' + this.requestCount(),
//                 type: 'POST',
//                 dataType: 'json',
//                 contentType: 'application/json',
//                 success: (data) => {
//                     if (data && data.redirect) {
//                         this.isRedirecting = true;
//                         window.location.replace(data.redirect);
//                     }
//                 },
//                 complete: () => {
//                     if (!this.isRedirecting) {
//                         setTimeout(this.checkStatus.bind(this), this.requestCount() * 2000);
//                     }
//                 }
//             })
//         }
//     });
// });



// define(
//     [
//         "uiComponent",
//         'ko',
//         'Magento_Customer/js/model/customer',
//     ],
//     function(
//         Component,
//         ko,
//         customer,
//     ) {
//         'use strict';
//         return Component.extend({
//              defaults: {
//                 template: 'Truelayer_Connect/checkout/payment_error_message'
//             },
//             isCustomerLoggedIn: customer.isLoggedIn,
//             initialize: function () {
//                 this._super(); //you must call super on components or they will not render
//                 console.log('shiny js initialized');
//             }
//         });
//     }
// );
