/**
 * Copyright © TrueLayer Ltd, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'uiComponent',
    'Magento_Ui/js/lib/view/utils/dom-observer',
    'Magento_Customer/js/customer-data',
    'Magento_Checkout/js/action/select-payment-method',
], function ($, Component, observer, customerData, selectPaymentAction) {
    'use strict';

    return Component.extend({
        initialize() {
            this._super();

            if (!this.errorMessage) {
                return;
            }

            this.invalidateSections();

            observer.get('#truelayer-payment-method', () => {
                this.showError();
                selectPaymentAction({'method': 'truelayer'});
            });
        },

        showError() {
            $('#truelayer-payment-error-message').text(this.errorMessage).show();
        },

        invalidateSections() {
            var sections = ['cart', 'checkout-data'];
            customerData.invalidate(sections);
            customerData.reload(sections, true);
        }
    });
});
