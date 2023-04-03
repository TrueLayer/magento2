/**
 * Copyright © TrueLayer Ltd, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery'
], function ($) {
    'use strict';

    return function (data) {
        let i = 0;
        let check = function() {
            if (i === 3) {
                $("#truelayer-refresh-button").show();
                $("#truelayer-loading").hide();
            } else {
                setTimeout(function () {
                    i++;
                    $.ajax({
                        method: 'GET',
                        url: data.checkUrl,
                        success: function (result) {
                            if (result == true) {
                                window.location.replace(data.refreshUrl);
                            } else {
                                check();
                            }
                        },
                        error: function () {
                            $("#truelayer-refresh-button").show();
                            $("#truelayer-loading").hide();
                        }
                    });
                }, 2000);
            }
        }
        check();
    };
});
