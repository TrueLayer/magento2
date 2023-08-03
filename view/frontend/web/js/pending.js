/**
 * Copyright © TrueLayer Ltd, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([], function () {
    'use strict';

    return function (data) {
        let success = document.querySelector('[data-success]'),
            error = document.querySelector('[data-error]'),
            loader = document.querySelector('[data-loader]'),
            count = 0,
            interval = setInterval(() => { getRequest() }, 2500);

        function getRequest() {
            fetch(data.checkUrl)
                .then((res) => {
                    count++;
                    if (!res.ok) throw new Error();
                    displayRequstResult(success);
                    window.location.replace(data.refreshUrl);
                })
                .catch(() => {
                    if (count === 3) displayRequstResult(error);
                });
        }

        // Element - HTML element
        function displayRequstResult(element) {
            clearInterval(interval);
            loader.setAttribute('style', 'display: none;');
            element.removeAttribute('style');
        }
    };
});
