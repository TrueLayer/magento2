/**
 * Copyright © TrueLayer Ltd, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'uiComponent',
], function (Component) {
    'use strict';

    return Component.extend({
        initialize() {
            this._super();
        },

        getCurrentVersion() {
            return this.currentVersion;
        },
        getLatestVersion() {
            return this.latestVersion;
        },
        isUpToDate() {
            return this.upToDate;
        }
    });
});
