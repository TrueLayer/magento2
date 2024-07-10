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

                isPaymentFetching: ko.observable(false),
                paymentQuery: ko.observable(null),
                paymentId: ko.observable(null),

                errorMessage: ko.observable(null),

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
                this.widgetContainer = container;

                if (this.config.isPreselected) {
                    this.selectPaymentMethod();
                }

                if (this.config.isSeamless || this.isActive()) {
                    this.fetchPayment();
                }

                ko.computed(this.initWidget, this);
                ko.computed(this.startWidget, this);
                ko.computed(this.redirectWhenReady, this);
                ko.computed(this.showFullScreenLoader, this);
                ko.computed(this.showErrorMessage, this);
            },

            selectPaymentMethod: function() {
                if (!this.config.isSeamless) {
                    this.fetchPayment();
                }

                return this._super();
            },

            fetchPayment: function() {
                // Abort any in-flight requests to avoid race conditions
                if (this.paymentQuery()) {
                    this.paymentQuery().abort();
                }

                // Update payment related state
                this.isPaymentFetching(true);
                this.paymentId(null);

                // Make new request
                var xhr = $.ajax({
                    url: this.getPaymentUrl,
                    type: 'POST',
                    dataType: 'json',
                    contentType: 'application/json',
                });

                var self = this;

                // Update payment state
                this.paymentQuery(xhr);
                xhr
                    .then(function (data) {
                        self.paymentId(data.payment_id);
                    })
                    .always(function() {
                        self.isPaymentFetching(false);
                    })
            },

            initWidget: function() {
                if (!this.isPaymentFetching()) {
                    return;
                }

                this.isOrderPlaced(false);
                this.shouldRedirect(false);

                if (this.widget) {
                    this.widget.cleanup();
                }

                this.widget = webSdk
                    .initWebSdk({
                        uiSettings: {
                            size: 'large',
                            recommendedPaymentMethod: this.config.isRecommended,
                        },
                        onPayButtonClicked: this.handlePlaceOrder.bind(this),
                        onDone: this.handleWidgetDone.bind(this),
                        onCancel: function () {
                            this.fetchPayment();
                            this.errorMessage('You cancelled your payment. Please try again.');
                        }.bind(this),
                    })
                    .mount(this.widgetContainer);
            },

            startWidget: function() {
                this.paymentQuery().done(function (payment) {
                    this.widget.start({
                        paymentId: payment.payment_id,
                        resourceToken: payment.resource_token,
                    })
                }.bind(this));
            },

            handleWidgetDone: function(info) {
                if (info.resultStatus === 'failed') {
                    this.errorMessage('Your payment failed. Please try again');
                    return;
                }

                this.shouldRedirect(true);
                loader.startLoader();
                this.messageContainer.addSuccessMessage({
                    message: translate('We are placing your order and will redirect you shortly.')
                });
            },

            handlePlaceOrder: function() {
                var paymentInformationSet = this.isActive() || setPaymentInformation(this.messageContainer, this.getData());

                $.when(paymentInformationSet).done(function () {
                    this.placeOrder();
                }.bind(this))
            },

            afterPlaceOrder: function() {
                cartRefresh();
                this.isOrderPlaced(true);
            },

            redirectWhenReady: function() {
                if (this.isOrderPlaced() && this.shouldRedirect()) {
                    this.isRedirecting(true);
                    $.mage.redirect(this.processUrl + '&payment_id=' + this.paymentId())
                }
            },

            showFullScreenLoader: function() {
                if (this.shouldRedirect()) {
                    loader.startLoader();
                }
            },

            showErrorMessage: function() {
                if (this.errorMessage()) {
                    this.messageContainer.addErrorMessage({
                        message: translate(this.errorMessage())
                    });

                    this.errorMessage(null);
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
