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
    'TrueLayer_Connect/js/action/invalidate-cart'
], function ($, Component, observer, customerData, selectPaymentAction, invalidateCartAction) {
    'use strict';

    return Component.extend({
        initialize() {
            this._super();

            if (!this.errorMessage) {
                return;
            }

            invalidateCartAction.invalidate();

            observer.get('#tl-payment-method', () => {
                this.showError();
                selectPaymentAction({'method': 'truelayer'});
            });
        },

        showError() {
            $('#truelayer-payment-error-message').text(this.errorMessage).show();
        },
    });
});
