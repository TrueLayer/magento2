/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
/*browser:true*/
/*global define*/
define(
    [
        'jquery',
        'ko',
        'mage/url',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/action/set-payment-information',
        'Magento_Checkout/js/action/select-payment-method',
        'TrueLayer_Connect/js/action/cart-refresh',
        'truelayer/web-sdk',
    ],
    function ($, ko, url, Component, setPaymentInformation, selectPaymentMethod, cartRefresh, webSdk) {
        'use strict';

        // TODO: pending state => hide UI and show spinner
        // TODO: on mobile perhaps use 'consent' not waiting bank

        return Component.extend({
            redirectAfterPlaceOrder: false,

            defaults: {
                template: 'TrueLayer_Connect/payment/checkout-widget',
                isError: ko.observable(false),
                getPaymentUrl: url.build('/truelayer/checkout/payment'),
                processUrl: url.build('/truelayer/checkout/process?force_api_fallback=1'),
                isOrderPlaced: ko.observable(false),
                isWidgetDone: ko.observable(false),
                paymentId: null,
            },

            createWidget: function (container) {
                var self = this;

                if (this.shouldPreselect()) {
                    selectPaymentMethod(this.getData())
                }

                var widget = this.initWidget(container);

                this.getPayment().done(function(payment) {
                    widget.start(payment)
                    self.paymentId = payment.paymentId;
                });

                return true;
            },

            initWidget: function (container) {
                var self = this;

                return webSdk
                    .initWebSdk({
                        uiSettings: {
                            size: 'large',
                            recommendedPaymentMethod: this.isRecommended(),
                        },
                        onNavigation: function (page) {
                            console.log('navigation', page);
                            if (['waiting-bank', 'qr-code-loader'].includes(page)) {
                                self.handlePlaceOrder();
                            }
                            if (page === 'cancel') {
                                self.handleWidgetClose();
                            }
                        },
                        onDone: this.handleWidgetDone.bind(self),
                    })
                    .mount(container);
            },

            // TODO: retries, error handling
            getPayment: function () {
                return $.ajax({
                    url: this.getPaymentUrl,
                    type: 'POST',
                    dataType: 'json',
                    contentType: 'application/json',
                }).then(function (data) {
                    return {
                        paymentId: data.payment_id,
                        resourceToken: data.resource_token,
                    }
                })
            },

            // TODO: handle failure;
            handlePlaceOrder: function() {
                console.log('handle placed order');
                var self = this;

                var paymentInformationSet =
                    this.isChecked() === this.getCode() ||
                    setPaymentInformation(this.messageContainer, this.getData());

                console.log(paymentInformationSet);

                $.when(paymentInformationSet).done(function () {
                    console.log('parent place order');
                    self.placeOrder();
                })
            },

            handleWidgetClose: function () {

            },

            handleWidgetDone: function(info) {
                console.log('onDone', info.resultStatus);
                this.isWidgetDone(true);
                window.location.replace(this.processUrl + '&payment_id=' + this.paymentId);
            },

            getCode: function() {
                return 'truelayer';
            },

            afterPlaceOrder: function() {
                console.log('cart refresh')
                cartRefresh();
                this.isOrderPlaced(true);
            },

            shouldPreselect: function () {
                return window.checkoutConfig.payment.truelayer.isPreselected;
            },

            isSeamless: function () {
                return window.checkoutConfig.payment.truelayer.isCheckoutWidgetSeamless;
            },

            isRecommended: function () {
                return window.checkoutConfig.payment.truelayer.isCheckoutWidgetRecommended;
            }
        });
    }
);
