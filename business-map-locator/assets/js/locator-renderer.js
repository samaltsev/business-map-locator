(function (window) {
    'use strict';

    function normalizeBoolean(value) {
        if (value === true || value === 1 || value === '1') { return true; }
        if (value === false || value === 0 || value === '0') { return false; }
        return value;
    }

    function LocatorRenderer(root, globals) {
        if (!root) {
            throw new Error('Locator renderer root is required.');
        }
        if (!window.BMLLocatorController) {
            throw new Error('BMLLocatorController is not available.');
        }

        this.root = root;
        this.globals = globals || window.BMLFrontend || {};
        this.controller = null;
    }

    LocatorRenderer.prototype.init = function () {
        if (this.controller && typeof this.controller.destroy === 'function') {
            this.controller.destroy();
        }
        this.controller = new window.BMLLocatorController(this.root, this.globals);
        return this.controller.init().then(function () {
            return this;
        }.bind(this));
    };

    LocatorRenderer.prototype.updateConfig = function (patch) {
        patch = patch || {};
        var settings = {};
        var current = this.root.dataset.settings ? JSON.parse(this.root.dataset.settings) : {};

        Object.keys(current || {}).forEach(function (key) { settings[key] = current[key]; });
        Object.keys(patch).forEach(function (key) { settings[key] = normalizeBoolean(patch[key]); });
        this.root.dataset.settings = JSON.stringify(settings);

        if (patch.layout) {
            this.root.classList.remove('bml-layout-split', 'bml-layout-map', 'bml-layout-cards');
            this.root.classList.add('bml-layout-' + patch.layout);
        }
        if (patch.height) {
            this.root.style.setProperty('--bml-map-height', String(parseInt(patch.height, 10) || 620) + 'px');
        }
        if (patch.listWidth) {
            this.root.style.setProperty('--bml-list-width', String(parseInt(patch.listWidth, 10) || 38) + '%');
        }
        if (patch.markerColor) {
            this.root.style.setProperty('--bml-marker-color', String(patch.markerColor));
        }

        /* Visual configuration is intentionally applied without a data reload.
         * Filters/search/radius remain controller-owned and trigger REST themselves. */
        if (this.controller && typeof this.controller.applyVisualConfig === 'function') {
            this.controller.applyVisualConfig(settings, patch);
        }
        return this;
    };

    LocatorRenderer.prototype.destroy = function () {
        if (this.controller && typeof this.controller.destroy === 'function') {
            this.controller.destroy();
        }
        this.controller = null;
    };

    window.BMLLocatorRenderer = LocatorRenderer;
}(window));
