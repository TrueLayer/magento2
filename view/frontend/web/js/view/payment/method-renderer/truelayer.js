define([
    'ko',
    'Magento_Checkout/js/view/payment/default',
    'Magento_Checkout/js/model/quote',
    'Magento_Customer/js/model/customer',
    'Magento_Checkout/js/model/step-navigator',
    'Magento_Checkout/js/action/set-payment-information',
    'mage/translate',
    'Magento_Ui/js/model/messageList',
    'https://cdn.jsdelivr.net/npm/truelayer-web-sdk/dist/sdk.min.js',
], function (ko, Component, quote, customer, stepNavigator, setPaymentInformation, $t, messageList, truelayerSdk) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'TrueLayer_Connect/payment/truelayer',
            successMessage: ko.observable(''),
            infoMessage: ko.observable(''),
            buttonSize: ko.observable('small'), // 'small' or 'large',
            isWidgetInit: ko.observable(false),
            orderResult: null,
            doneFuncCompletedProtection: ko.observable(0), // Fix: savety against infinite requests
        },

        initialize() {
            this._super();

            this.isPaymentStepLoaded();
            stepNavigator.steps.subscribe(() => this.isPaymentStepLoaded());

            return this;
        },

        isPaymentStepLoaded() {
            const steps = stepNavigator.steps();
            const payment = steps.find((step) => step.code === 'payment');

            if (payment && payment.isVisible()) {
                // Fix: Correcting a duplicate request "set-payment-information"
                // Trigger prompt on page load when no payment method is selected
                if (!quote.paymentMethod()) {
                    setPaymentInformation(messageList, { method: this.getCode() }, false);
                }
                
                const timeout = setTimeout(async () => {
                    this.orderResult = await this.orderRequest(customer.isLoggedIn(), quote.getQuoteId());
                    this.initWidget();

                    clearTimeout(timeout);
                }, 500);
            }
        },

        getCode: () => 'truelayer',

        async orderRequest(isLoggedIn, cartId) {
            try {
                const response = await fetch(`${window.location.origin}/rest/V1/truelayer/order-request`, {
                    method: 'POST',
                    headers: {
                      'Accept': 'application/json',
                      'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        isLoggedIn,
                        cartId,
                        paymentMethod: this.getData(),
                    }),
                });

                const result = await response.json();

                if (result[0].success) {
                    return result[0].response;
                } else {
                    throw new Error($t('Failed getting order request result.'));
                }
            } catch (error) {
                messageList.addErrorMessage({ message: error });
            }
        },

        initWidget() {
            this.isWidgetInit(true);
            this.doneFuncCompletedProtection(0);

            truelayerSdk.initWebSdk({
                uiSettings: {
                    size: this.buttonSize(),
                    recommendedPaymentMethod: true,
                },

                onError: (error) => messageList.addErrorMessage({ message: $t('Widget error: ') + error }),
                onDone: (response) => this.onDone(response),
            })
            .mount(document.getElementById('truelayer-widget-iframe'))
            .start({
                paymentId: this.orderResult['payment_id'],
                resourceToken: this.orderResult['resource_token'],
            });
        },

        async onDone(response) {
            this.doneFuncCompletedProtection(this.doneFuncCompletedProtection() + 1);

            if (this.doneFuncCompletedProtection() === 1) {
                if (response.resultStatus === 'success') {
                    this.successMessage($t('Payment success: page will reload.'));
                    window.location.replace(`/truelayer/checkout/process/payment_id/${this.orderResult['transaction_id']}`);
                }

                if (response.resultStatus === 'pending') {
                    this.infoMessage($t('Payment pending: page will reload.'));
                    window.location.replace(`/truelayer/checkout/process/payment_id/${this.orderResult['transaction_id']}`);
                }

                if (response.resultStatus === 'failed') {
                    this.isWidgetInit(false);
                    this.orderResult = await this.orderRequest(customer.isLoggedIn(), quote.getQuoteId());

                    const timeout = setTimeout(() => {
                        this.initWidget();
                        clearTimeout(timeout);
                    }, 1000);
                }
            }
        },
    });
});
