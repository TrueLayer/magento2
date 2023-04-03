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
        'Magento_Checkout/js/model/error-processor',
        'Magento_Checkout/js/model/quote',
        'Magento_Customer/js/model/customer',
        'Magento_Checkout/js/model/url-builder',
        'Magento_Checkout/js/model/full-screen-loader',
        'mage/storage',
        'Magento_Ui/js/model/messageList',
        'Magento_Checkout/js/model/payment/additional-validators',
        'uiRegistry'
    ],
    function ($, Component, errorProcessor, quote, customer, urlBuilder, fullScreenLoader, storage, messageList, additionalValidators, uiRegistry) {
        'use strict';

        var payload = '';

        return Component.extend({
            defaults: {
                template: 'TrueLayer_Connect/payment/truelayer'
            },

            getCode: function() {
                return 'truelayer';
            },

            placeOrder: function (data, event) {
                if (event) {
                    event.preventDefault();
                }

                this.isPlaceOrderActionAllowed(false);
                var _this = this;

                if (additionalValidators.validate()) {
                    fullScreenLoader.startLoader();
                    _this._placeOrder();
                }
            },

            _placeOrder: function () {
                return this.setPaymentInformation().success(function () {
                    this.orderRequest(customer.isLoggedIn(), quote.getQuoteId());
                }.bind(this));
            },

            setPaymentInformation: function() {
                var serviceUrl, payload;

                payload = {
                    cartId: quote.getQuoteId(),
                    billingAddress: quote.billingAddress(),
                    paymentMethod: this.getData()
                };

                if (customer.isLoggedIn()) {
                    serviceUrl = urlBuilder.createUrl('/carts/mine/set-payment-information', {});
                } else {
                    payload.email = quote.guestEmail;
                    serviceUrl = urlBuilder.createUrl('/guest-carts/:quoteId/set-payment-information', {
                        quoteId: quote.getQuoteId()
                    });
                }

                return storage.post(
                    serviceUrl, JSON.stringify(payload)
                );
            },

            orderRequest: function(isLoggedIn, cartId) {
                var url = 'rest/V1/truelayer/order-request';

                payload = {
                    isLoggedIn: isLoggedIn,
                    cartId: cartId,
                    paymentMethod: this.getData()
                };

                storage.post(
                    url,
                    JSON.stringify(payload)
                ).done(function (response) {
                    if (response[0].success) {
                        fullScreenLoader.stopLoader();
                        window.location.replace(response[0].payment_page_url);
                    } else {
                        fullScreenLoader.stopLoader();
                        this.addError(response[0].message);
                    }
                }.bind(this));
            },

            /**
             * Adds error message
             *
             * @param {String} message
             */
            addError: function (message) {
                messageList.addErrorMessage({
                    message: message
                });
            },
        });
    }
);
