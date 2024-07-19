/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
/*browser:true*/
/*global define*/
define([ 'ko' ], function (ko) {
    'use strict';

    return {
        isSetting: ko.observable(false),
        isSet: ko.observable(false),
    };
});
