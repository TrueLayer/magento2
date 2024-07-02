/**
 * Copyright © TrueLayer Ltd, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'mage/url',
    'uiComponent',
    'Magento_Ui/js/lib/view/utils/dom-observer',
    'Magento_Customer/js/customer-data',
    'Magento_Checkout/js/action/select-payment-method',
], function ($, url, Component, observer, customerData, selectPaymentAction) {
    'use strict';

    return Component.extend({
        defaults: {
            url: url.build('/truelayer/checkout/clearpaymenterror'),
        },

        initialize() {
            this._super();

            if (!this.hasError) {
                return;
            }

            this.invalidateSections();

            observer.get('#truelayer-payment-method', () => {
                this.showError();
                selectPaymentAction({'method': 'truelayer'});
                this.clear();
            });
        },

        showError() {
            $('#truelayer-payment-error-message').text(this.errorMessage).show();
        },

        clear() {
            $.ajax({
                url: this.url,
                type: 'POST',
                dataType: 'json',
                contentType: 'application/json',
            });
        },

        invalidateSections() {
            var sections = ['cart', 'checkout-data'];
            customerData.invalidate(sections);
            customerData.reload(sections, true);
        }
    });
});
