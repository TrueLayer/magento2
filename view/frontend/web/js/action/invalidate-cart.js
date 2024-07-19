/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
/*browser:true*/
/*global define*/
define(['Magento_Customer/js/customer-data'], function (customerData) {
    'use strict';

    var KEY = 'TL.checkout.refresh_required';
    var SECTIONS = ['cart', 'checkout-data'];

    return {
        requireInvalidation: function (bool) {
            if (bool) {
                localStorage.setItem(KEY, 'true');
                return;
            }

            localStorage.removeItem(KEY);
        },

        isInvalidationRequired: function() {
            return localStorage.getItem(KEY) === 'true';
        },

        invalidateIfRequired: function () {
            if (this.isInvalidationRequired()) {
                this.invalidate();
                this.requireInvalidation(false);
            }
        },

        invalidate: function() {
            var maxAttempts = 10;
            var attempt = 0;

            // Various versions of Magento will have an issue with
            // customer-data not initialising 'storage' correctly in some cases
            // https://github.com/magento/magento2/issues/31920
            function invalidate() {
                try {
                    attempt++;
                    customerData.invalidate(SECTIONS);
                    customerData.reload(SECTIONS, true);
                } catch (e) {
                    if (attempt <= maxAttempts) {
                        setTimeout(invalidate, 500);
                    }
                }
            }

            invalidate();
        },
    }
});
