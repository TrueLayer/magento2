/**
 * Copyright © TrueLayer Ltd, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define(['Magento_Customer/js/customer-data', 'uiComponent'], function (customerData, Component) {
    'use strict';

    return Component.extend({
        initialize() {
            this._super();

            var sections = ['cart', 'checkout-data'];
            customerData.invalidate(sections);
            customerData.reload(sections, true);
        },
    });
});