/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
/*browser:true*/
/*global define*/
define(
    [
        'Magento_Checkout/js/view/payment/default',
        'mage/url',
        'Magento_Checkout/js/action/redirect-on-success',
    ],
    function (Component, url, redirectOnSuccess) {
        'use strict';

        return Component.extend({
            redirectAfterPlaceOrder: true,

            defaults: {
                template: 'TrueLayer_Connect/payment/hpp'
            },

            getCode: function() {
                return this.item.method;
            },

            afterPlaceOrder: function() {
                redirectOnSuccess.redirectUrl = url.build('truelayer/checkout/redirect');
            },

            /**
             * Get payment method description.
             */
            getDescription: function () {
                return window.checkoutConfig.payment[this.getCode()]?.description;
            },
        });
    }
);
