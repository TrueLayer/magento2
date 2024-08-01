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
        'Magento_Checkout/js/model/full-screen-loader',
        'mage/translate',
        'TrueLayer_Connect/js/action/invalidate-cart',
        'TrueLayer_Connect/js/model/tl-payment',
        'TrueLayer_Connect/js/model/magento-payment-information',
        'truelayer-web-sdk',
    ],
    function ($, ko, url, Component, loader, translate, invalidateCart, tlPayment, paymentInformation, webSdk) {
        'use strict';

        return Component.extend({
            redirectAfterPlaceOrder: false,

            defaults: {
                template: 'TrueLayer_Connect/payment/checkout-widget',
                getPaymentUrl: url.build('/truelayer/checkout/payment'),
                processUrl: url.build('/truelayer/checkout/process?force_api_fallback=1'),

                isOrderPlaced: ko.observable(false),
                shouldRedirect: ko.observable(false),
                isRedirecting: ko.observable(false),

                errorMessage: ko.observable(null),

                widget: null,
                widgetContainer: null,

                config: {
                    isProduction: window.checkoutConfig.payment.truelayer.isProduction,
                    isRecommended: window.checkoutConfig.payment.truelayer.isCheckoutWidgetRecommended,
                    isPreselected: window.checkoutConfig.payment.truelayer.isPreselected,
                },

            },

            // Setup method is invoked when the widget container is rendered
            setup: function(container) {
                this.widgetContainer = container;

                if (this.config.isPreselected) {
                    this.selectPaymentMethod();
                }

                ko.computed(this.loadPayment, this);
                ko.computed(this.initWidget, this);
                ko.computed(this.startWidget, this);
                ko.computed(this.redirectWhenReady, this);
                ko.computed(this.showFullScreenLoader, this);
                ko.computed(this.showErrorMessage, this);
            },

            loadPayment: function() {
                if (this.getCode() !== this.isChecked()) {
                    return;
                }

                if (!paymentInformation.isSet() || tlPayment.paymentId()) {
                    return;
                }

                tlPayment.load();
            },

            initWidget: function() {
                if (!tlPayment.isLoading()) {
                    return;
                }

                this.isOrderPlaced(false);
                this.shouldRedirect(false);

                if (this.widget) {
                    this.widget.cleanup();
                }

                this.widget = webSdk
                    .initWebSdk({
                        production: this.config.isProduction,
                        maxWaitForResult: 10,
                        uiSettings: {
                            size: 'large',
                            recommendedPaymentMethod: this.config.isRecommended,
                        },
                        onPayButtonClicked: this.placeOrder.bind(this),
                        onDone: this.handleWidgetDone.bind(this),
                        onCancel: function () {
                            tlPayment.clear();
                            this.errorMessage('You cancelled your payment. Please try again.');
                        }.bind(this),
                        onError: function () {
                            tlPayment.clear();
                            this.errorMessage('Sorry, there was an error. Please try again.');
                        }
                    })
                    .mount(this.widgetContainer);
            },

            startWidget: function() {
                if (tlPayment.isLoading() || !tlPayment.paymentId() || !tlPayment.resourceToken()) {
                    return;
                }

                this.widget.start({
                    paymentId: tlPayment.paymentId(),
                    resourceToken: tlPayment.resourceToken(),
                })
            },

            handleWidgetDone: function(info) {
                if (info.resultStatus === 'failed') {
                    this.errorMessage('Your payment failed. Please try again');
                    tlPayment.clear();
                    return;
                }

                this.shouldRedirect(true);
                loader.startLoader();
                this.messageContainer.addSuccessMessage({
                    message: translate('We are placing your order and will redirect you shortly.')
                });
            },

            afterPlaceOrder: function() {
                this.isOrderPlaced(true);
            },

            redirectWhenReady: function() {
                if (this.isOrderPlaced() && this.shouldRedirect()) {
                    this.isRedirecting(true);
                    invalidateCart.requireInvalidation(true);
                    $.mage.redirect(this.processUrl + '&payment_id=' + tlPayment.paymentId())
                }
            },

            showFullScreenLoader: function() {
                if (this.shouldRedirect()) {
                    loader.startLoader();
                }
            },

            showErrorMessage: function() {
                var error = this.errorMessage() || tlPayment.error();

                if (!error) {
                    return;
                }

                this.messageContainer.addErrorMessage({
                    message: translate(error)
                });

                this.errorMessage(null);
                tlPayment.error(null);
            },

            getCode: function() {
                return this.item.method;
            },

            isActive: function() {
                return this.getCode() === this.isChecked();
            },
        });
    }
);
