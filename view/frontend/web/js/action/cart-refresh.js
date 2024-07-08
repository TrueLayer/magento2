/**
 * Copyright © TrueLayer Ltd, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define(['Magento_Customer/js/customer-data'], function (customerData) {
    'use strict';

    return function() {
        var sections = ['cart', 'checkout-data'];
        customerData.invalidate(sections);
        customerData.reload(sections, true);
    };
});