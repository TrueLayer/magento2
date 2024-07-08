require(['jquery'], function ($) {

    $(document).ready(function() {
        var cwEnabledSelect = $('#truelayer_settings_checkout_widget_enabled');

        function toggleSections() {
            var isCheckoutWidgetEnabled = cwEnabledSelect.find(':selected').val() === '1';
            $('#truelayer_settings_cw').parents('.section-config').toggle(isCheckoutWidgetEnabled);
            $('#truelayer_settings_hpp').parents('.section-config').toggle(!isCheckoutWidgetEnabled);
        }

        if (cwEnabledSelect.length) {
            toggleSections();
            cwEnabledSelect.on('change', toggleSections);
        }
    });
});
