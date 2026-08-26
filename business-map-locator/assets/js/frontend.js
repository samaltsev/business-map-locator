(function (window, document) {
    'use strict';

    function ready(callback) {
        if (document.readyState !== 'loading') {
            callback();
            return;
        }
        document.addEventListener('DOMContentLoaded', callback);
    }

    function initLocator(root) {
        if (!window.BMLLocatorRenderer) { return; }

        if (root.bmlLocator && typeof root.bmlLocator.destroy === 'function') {
            root.bmlLocator.destroy();
        }

        root.bmlLocator = new window.BMLLocatorRenderer(root, window.BMLFrontend || {});
        root.bmlLocator.init().catch(function (error) {
            if (window.console && window.console.error) {
                window.console.error(error);
            }
        });
    }

    ready(function () {
        document.querySelectorAll('.bml-locator').forEach(initLocator);
    });
}(window, document));
