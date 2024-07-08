/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
/*browser:true*/
/*global define*/
define([ 'uiComponent', 'Magento_Checkout/js/model/payment/renderer-list'], function (Component, rendererList) {
    'use strict';

    rendererList.push(
        {
            type: 'truelayer',
            component: window.checkoutConfig.payment.truelayer.isCheckoutWidgetEnabled
                ? 'TrueLayer_Connect/js/view/payment/method-renderer/checkout-widget'
                : 'TrueLayer_Connect/js/view/payment/method-renderer/hpp'
        }
    );


    return Component.extend({});
});
