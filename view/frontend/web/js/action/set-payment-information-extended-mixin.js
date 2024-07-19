/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    'jquery',
    'mage/utils/wrapper',
    'TrueLayer_Connect/js/model/magento-payment-information',
], function ($, wrapper, paymentInformation) {
    'use strict';

    return function (setPaymentInformationExtendedAction) {

        return wrapper.wrap(setPaymentInformationExtendedAction, function (originalAction, messageContainer, paymentData, skipBilling) {

            var result = originalAction(messageContainer, paymentData, skipBilling);

            paymentInformation.isSetting(true);

            $.when(result).done(function () {
                paymentInformation.isSet(true);
            });

            return result;
        });
    };

});
