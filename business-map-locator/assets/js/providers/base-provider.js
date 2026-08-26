(function (window) {
    'use strict';

    window.BMLMapProviders = window.BMLMapProviders || {};

    function BaseProvider() {
        this.container = null;
        this.options = {};
        this.config = {};
        this.map = null;
        this.markersById = {};
        this.bounds = null;
    }

    BaseProvider.prototype.init = function (container, options) {
        this.container = container;
        this.options = options || {};
        this.config = this.options.providerConfig || {};
        return this;
    };

    BaseProvider.prototype.isReady = function () {
        return true;
    };

    BaseProvider.prototype.whenReady = function () {
        return Promise.resolve();
    };

    BaseProvider.prototype.createMap = function () {
        return this;
    };

    BaseProvider.prototype.addMarker = function () {
        return null;
    };

    BaseProvider.prototype.removeMarker = function () {};

    BaseProvider.prototype.clearMarkers = function () {
        var self = this;
        Object.keys(this.markersById).forEach(function (id) {
            self.removeMarker(id);
        });
        this.markersById = {};
        this.bounds = null;
    };

    BaseProvider.prototype.cluster = function () {
        return false;
    };

    BaseProvider.prototype.openPopup = function () {};

    BaseProvider.prototype.focusMarker = function () {};
    BaseProvider.prototype.focusCoordinates = function () {};

    BaseProvider.prototype.emphasizeMarker = function () {};

    BaseProvider.prototype.invalidateSize = function () {};

    BaseProvider.prototype.fitCoordinates = function () {};

    BaseProvider.prototype.fitBounds = function () {};

    BaseProvider.prototype.setUserLocation = function () {};

    BaseProvider.prototype.destroy = function () {
        this.clearMarkers();
        this.container = null;
        this.options = {};
        this.config = {};
        this.map = null;
    };

    window.BMLBaseMapProvider = BaseProvider;
}(window));
