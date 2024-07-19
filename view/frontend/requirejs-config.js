var config = {
    paths: {
        'truelayer-embedded-payment-page': 'TrueLayer_Connect/node_modules/truelayer-embedded-payment-page/dist/truelayer-payment.min',
        'truelayer-web-sdk': 'TrueLayer_Connect/node_modules/truelayer-web-sdk/dist/sdk.min'
    },
    config: {
        mixins: {
            'Magento_Checkout/js/action/set-payment-information-extended': {
                'TrueLayer_Connect/js/action/set-payment-information-extended-mixin': true
            }
        }

    }
}