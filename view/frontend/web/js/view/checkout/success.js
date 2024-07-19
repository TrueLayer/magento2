/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
require([
    'jquery',
    'TrueLayer_Connect/js/action/invalidate-cart'
], function ($, invalidateCart) {
    'use strict';

    $(document).ready(function () {
        invalidateCart.invalidateIfRequired();
    });
});