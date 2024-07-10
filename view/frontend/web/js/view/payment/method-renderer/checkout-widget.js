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
        'Magento_Checkout/js/model/full-screen-loader',
        'mage/translate',
        'truelayer/web-sdk',
    ],
    function ($, ko, url, Component, setPaymentInformation, selectPaymentMethod, cartRefresh, loader, translate, webSdk) {
        'use strict';

        // TODO: retries, error handling

        return Component.extend({
            redirectAfterPlaceOrder: false,

            defaults: {
                template: 'TrueLayer_Connect/payment/checkout-widget',
                getPaymentUrl: url.build('/truelayer/checkout/payment'),
                processUrl: url.build('/truelayer/checkout/process?force_api_fallback=1'),
                isOrderPlaced: ko.observable(false),
                shouldRedirect: ko.observable(false),
                isRedirecting: ko.observable(false),
                paymentId: null,
                widget: null,
                widgetContainer: null,
                config: {
                    isSeamless: window.checkoutConfig.payment.truelayer.isCheckoutWidgetSeamless,
                    isRecommended: window.checkoutConfig.payment.truelayer.isCheckoutWidgetRecommended,
                    isPreselected: window.checkoutConfig.payment.truelayer.isPreselected,
                },

            },

            // Setup method is invoked when the widget container is rendered
            setup: function(container) {
                window.messageContainer = this.messageContainer;

                if (this.config.isPreselected) {
                    this.selectPaymentMethod();
                }

                this.initWidget(container);

                if (this.config.isSeamless || this.isActive()) {
                    this.startWidget();
                }

                ko.computed(this.redirectWhenReady, this);
                ko.computed(this.showFullScreenLoader, this);
            },

            selectPaymentMethod: function() {
                if (!this.config.isSeamless) {
                    this.startWidget();
                }

                return this._super();
            },

            initWidget: function(container) {
                this.widgetContainer = container;

                this.widget = webSdk
                    .initWebSdk({
                        uiSettings: {
                            size: 'large',
                            recommendedPaymentMethod: this.config.isRecommended,
                        },
                        onPayButtonClicked: this.handlePlaceOrder.bind(this),
                        onDone: this.handleWidgetDone.bind(this),
                        onCancel: function () {
                            this.resetWidget('You cancelled your payment. Please try again.')
                        }.bind(this),
                    })
                    .mount(this.widgetContainer);
            },

            startWidget: function() {
                var self = this;

                this.getPayment().done(function(payment) {
                    self.widget.start(payment)
                    self.paymentId = payment.paymentId;
                });
            },

            getPayment: function() {
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

            resetWidget: function(errorMessage) {
                this.isOrderPlaced(false);
                this.shouldRedirect(false);
                this.paymentId = null;

                if (errorMessage) {
                    this.messageContainer.addErrorMessage({
                        message: translate(errorMessage)
                    });
                }

                this.initWidget(this.widgetContainer);
                this.startWidget();
            },

            handleWidgetDone: function(info) {
                if (info.resultStatus === 'failed') {
                    this.resetWidget('Your payment failed. Please try again');
                    return;
                }

                this.shouldRedirect(true);
                loader.startLoader();
                this.messageContainer.addSuccessMessage({
                    message: translate('We are placing your order and will redirect you shortly.')
                });
            },

            handlePlaceOrder: function() {
                var self = this;

                var paymentInformationSet = this.isActive() || setPaymentInformation(this.messageContainer, this.getData());

                $.when(paymentInformationSet).done(function () {
                    self.placeOrder();
                    loader.stopLoader();
                })
            },

            afterPlaceOrder: function() {
                // cartRefresh();
                this.isOrderPlaced(true);
            },

            redirectWhenReady: function() {
                if (this.isOrderPlaced() && this.shouldRedirect()) {
                    this.isRedirecting(true);
                    $.mage.redirect(this.processUrl + '&payment_id=' + this.paymentId)
                }
            },

            showFullScreenLoader: function() {
                if (this.shouldRedirect() || this.isRedirecting()) {
                    loader.startLoader();
                }
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
