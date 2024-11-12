/**
 * Copyright © TrueLayer Ltd, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'uiComponent',
], function (Component) {
    'use strict';

    return Component.extend({
        clickUpload() {
            document.getElementById(this.htmlFileInputId).click();
        },
        handleFile(object, event) {
            const textInput = document.getElementById(this.htmlTextInputId);
            // Get a reference to the file
            const file = event?.target?.files[0];

            if (!file) {
                return;
            }
            // Encode the file using the FileReader API
            const reader = new FileReader();
            reader.onloadend = () => {
                // Use a regex to remove data url part
                const base64String = reader.result
                    .replace(/^data:.+,/, "");

                textInput.value = base64String;
            };
            reader.readAsDataURL(file);
        }
    });
});
