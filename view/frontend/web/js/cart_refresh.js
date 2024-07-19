/**
 * Copyright © TrueLayer Ltd, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define(['uiComponent', 'TrueLayer_Connect/js/action/invalidate-cart'], function (Component, invalidateCart) {
    'use strict';

    return Component.extend({
        initialize() {
            this._super();
            invalidateCart.invalidate();
        },
    });
});