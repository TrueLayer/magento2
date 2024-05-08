/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
/*browser:true*/
/*global define*/
define(
    [
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'mage/url',
        'Magento_Customer/js/customer-data',
        'Magento_Checkout/js/model/error-processor',
        'Magento_Checkout/js/model/full-screen-loader',
    ],
    function ($, Component, url, customerData, errorProcessor, fullScreenLoader) {
        'use strict';

        var payload = '';

        return Component.extend({
            redirectAfterPlaceOrder: false,

            defaults: {
                template: 'TrueLayer_Connect/payment/truelayer'
            },

            getCode: function() {
                return 'truelayer';
            },

            afterPlaceOrder: function () {
                fullScreenLoader.startLoader();

                $.ajax({
                    url: '/rest/V1/truelayer/order-request',
                    type: 'POST',
                    data: JSON.stringify({
                        isLoggedIn: true,
                        cartId: '', // @todo Find out where validation happens and drop params
                    }),
                    contentType: "application/json; charset=utf-8",
                    dataType: "json"
                })
                    .done(function (response) {
                        //customerData.invalidate(['cart']);
                        if (response[0].success) {
                            window.location.replace(response[0].payment_page_url);
                        } else {
                            this.addError(response[0].message);
                        }
                    })
                    .fail(function (response) {
                        errorProcessor.process(response, this.messageContainer);
                    })
                    .always(function () {
                        fullScreenLoader.stopLoader();
                    });
            }
        });
    }
);
