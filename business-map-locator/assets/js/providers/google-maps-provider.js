(function (window, document) {
    'use strict';

    var ready = Boolean(window.google && window.google.maps);
    var loadPromise = null;
    var loadError = null;
    var authFailureHandler = window.gm_authFailure;

    window.BMLGoogleMapsReady = function () {
        ready = true;
        document.dispatchEvent(new CustomEvent('bml:google-ready'));
    };

    window.gm_authFailure = function () {
        loadError = new Error('Google Maps authentication failed.');
        loadError.code = 'authorization_failed';
        ready = false;
        document.dispatchEvent(new CustomEvent('bml:google-error', { detail: loadError }));

        if (typeof authFailureHandler === 'function') {
            authFailureHandler();
        }
    };

    function classifyGoogleError(message, config) {
        var text = String(message || '');
        var error = null;

        if (text.indexOf('MissingKeyMapError') !== -1) {
            error = new Error((config.errors && config.errors.missingApiKey) || 'Google Maps API key is missing.');
            error.code = 'missing_api_key';
        } else if (text.indexOf('InvalidKeyMapError') !== -1 || text.indexOf('RefererNotAllowedMapError') !== -1) {
            error = new Error((config.errors && config.errors.invalidApiKey) || 'Google Maps API key is invalid or restricted.');
            error.code = 'invalid_api_key';
        } else if (text.indexOf('BillingNotEnabledMapError') !== -1 || text.indexOf('BillingNotEnabled') !== -1) {
            error = new Error((config.errors && config.errors.billingDisabled) || 'Google Maps billing is disabled.');
            error.code = 'billing_disabled';
        }

        return error;
    }

    function GoogleMapsProvider() {
        window.BMLBaseMapProvider.call(this);
        this.infoWindow = null;
        this.userMarker = null;
    }

    GoogleMapsProvider.prototype = Object.create(window.BMLBaseMapProvider.prototype);
    GoogleMapsProvider.prototype.constructor = GoogleMapsProvider;

    GoogleMapsProvider.prototype.isReady = function () {
        return ready && Boolean(window.google && window.google.maps);
    };

    GoogleMapsProvider.prototype.whenReady = function () {
        var config = this.options.providerConfig || {};
        var apiKey = config.apiKey || '';

        if (this.isReady()) {
            return Promise.resolve();
        }

        if (!config.configured || !apiKey) {
            loadError = new Error((config.errors && config.errors.missingApiKey) || 'Google Maps API key is missing.');
            loadError.code = 'missing_api_key';
            return Promise.reject(loadError);
        }

        return this.loadApi(config);
    };

    GoogleMapsProvider.prototype.loadApi = function (config) {
        var apiUrl = config.apiUrl || 'https://maps.googleapis.com/maps/api/js';

        if (loadPromise) {
            return loadPromise;
        }

        loadPromise = new Promise(function (resolve, reject) {
            var script = document.createElement('script');
            var timeout = window.setTimeout(function () {
                cleanup();
                loadError = new Error((config.errors && config.errors.loadFailed) || 'Google Maps did not load.');
                loadError.code = 'load_failed';
                reject(loadError);
            }, 15000);

            function onReady() {
                if (!(window.google && window.google.maps)) { return; }
                window.clearTimeout(timeout);
                cleanup();
                ready = true;
                resolve();
            }

            function onError(event) {
                window.clearTimeout(timeout);
                cleanup();
                loadError = event.detail instanceof Error
                    ? event.detail
                    : new Error((config.errors && config.errors.loadFailed) || 'Google Maps did not load.');
                loadError.code = loadError.code || 'load_failed';
                reject(loadError);
            }

            function onWindowError(event) {
                var googleError = classifyGoogleError(event.message, config);
                if (googleError) {
                    onError({ detail: googleError });
                }
            }

            function cleanup() {
                document.removeEventListener('bml:google-ready', onReady);
                document.removeEventListener('bml:google-error', onError);
                window.removeEventListener('error', onWindowError);
                script.onerror = null;
            }

            script.src = apiUrl + '?' + new URLSearchParams({
                key: config.apiKey,
                callback: 'BMLGoogleMapsReady',
                loading: 'async'
            }).toString();
            script.async = true;
            script.defer = true;
            script.onerror = function () {
                onError({ detail: new Error((config.errors && config.errors.loadFailed) || 'Google Maps script request failed.') });
            };

            document.addEventListener('bml:google-ready', onReady);
            document.addEventListener('bml:google-error', onError);
            window.addEventListener('error', onWindowError);
            document.head.appendChild(script);
        });

        return loadPromise;
    };

    GoogleMapsProvider.prototype.createMap = function (container, config, options) {
        options = options || {};
        config = config || {};
        window.BMLBaseMapProvider.prototype.init.call(this, container, options);
        this.config = config;
        var center = config.center || { lat: 0, lng: 0 };

        if (!container) {
            throw new Error('Google Maps container is missing.');
        }

        if (loadError) {
            throw loadError;
        }

        if (!this.isReady()) {
            throw new Error('Google Maps is not available.');
        }

        this.map = new window.google.maps.Map(container, {
            center: { lat: parseFloat(center.lat) || 0, lng: parseFloat(center.lng) || 0 },
            zoom: parseInt(config.zoom, 10) || 11,
            mapTypeControl: false
        });
        this.infoWindow = new window.google.maps.InfoWindow();
        return this;
    };

    GoogleMapsProvider.prototype.addMarker = function (location, popupHtml, onSelect) {
        var self = this;
        var lat = Number(location && location.lat);
        var lng = Number(location && location.lng);
        var marker;
        var markerOptions;
        var iconUrl;

        if (!Number.isFinite(lat) || !Number.isFinite(lng) || !this.map) { return null; }

        if (!this.bounds) {
            this.bounds = new window.google.maps.LatLngBounds();
        }

        markerOptions = {
            map: this.map,
            position: { lat: lat, lng: lng },
            title: location.title || ''
        };
        iconUrl = location && location.category && location.category.icon;
        if (iconUrl) {
            markerOptions.icon = {
                url: iconUrl,
                scaledSize: new window.google.maps.Size(40, 40),
                anchor: new window.google.maps.Point(20, 20)
            };
        }

        marker = new window.google.maps.Marker(markerOptions);

        marker.addListener('click', function () {
            if (popupHtml && self.infoWindow) {
                self.infoWindow.setContent(popupHtml);
                self.infoWindow.open({ anchor: marker, map: self.map });
            }
            if (onSelect) {
                onSelect(location);
            }
        });

        this.markersById[location.id] = marker;
        this.bounds.extend(marker.getPosition());

        return marker;
    };

    GoogleMapsProvider.prototype.removeMarker = function (id) {
        var marker = this.markersById[id];
        if (!marker) { return; }
        marker.setMap(null);
        delete this.markersById[id];
    };

    GoogleMapsProvider.prototype.clearMarkers = function () {
        var self = this;
        Object.keys(this.markersById).forEach(function (id) {
            self.removeMarker(id);
        });
        this.markersById = {};
        this.bounds = window.google && window.google.maps ? new window.google.maps.LatLngBounds() : null;
    };

    GoogleMapsProvider.prototype.cluster = function () {
        return false;
    };

    GoogleMapsProvider.prototype.openMarkerPopup = function (id, content) {
        var marker = this.markersById[id];
        if (!marker || !this.infoWindow || !this.map) { return false; }
        if (content) { this.infoWindow.setContent(content); }
        this.infoWindow.open({ anchor: marker, map: this.map });
        return true;
    };

    GoogleMapsProvider.prototype.closePopup = function () {
        if (this.infoWindow) { this.infoWindow.close(); }
    };

    GoogleMapsProvider.prototype.openPopup = function (id) {
        return this.openMarkerPopup(id);
    };

    GoogleMapsProvider.prototype.focusMarker = function (id, zoom) {
        var marker = this.markersById[id];
        if (!marker || !this.map) { return; }
        this.map.panTo(marker.getPosition());
        this.map.setZoom(zoom || 16);
        this.openPopup(id);
    };

    GoogleMapsProvider.prototype.focusCoordinates = function (lat, lng, zoom) {
        if (!this.map || !Number.isFinite(Number(lat)) || !Number.isFinite(Number(lng))) { return; }
        this.map.panTo({ lat: Number(lat), lng: Number(lng) });
        this.map.setZoom(zoom || 16);
    };

    GoogleMapsProvider.prototype.emphasizeMarker = function (id, enabled) {
        var marker = this.markersById[id];
        if (!marker) { return; }
        if (typeof marker.setZIndex === 'function') {
            marker.setZIndex(enabled !== false ? 9999 : undefined);
        }
        if (typeof marker.setAnimation === 'function' && window.google && window.google.maps) {
            marker.setAnimation(enabled !== false ? window.google.maps.Animation.BOUNCE : null);
            if (enabled !== false) {
                window.setTimeout(function () { if (marker && marker.setAnimation) { marker.setAnimation(null); } }, 450);
            }
        }
    };

    GoogleMapsProvider.prototype.invalidateSize = function () {
        if (this.map && window.google && window.google.maps) {
            window.google.maps.event.trigger(this.map, 'resize');
        }
    };

    GoogleMapsProvider.prototype.fitCoordinates = function (coordinates) {
        var bounds;
        var valid;
        if (!this.map || !window.google || !window.google.maps || !Array.isArray(coordinates) || !coordinates.length) { return; }
        valid = coordinates.filter(function (point) {
            return point && Number.isFinite(Number(point.lat)) && Number.isFinite(Number(point.lng));
        });
        if (!valid.length) { return; }
        if (valid.length === 1) {
            this.focusCoordinates(valid[0].lat, valid[0].lng, 16);
            return;
        }
        bounds = new window.google.maps.LatLngBounds();
        valid.forEach(function (point) {
            bounds.extend({ lat: Number(point.lat), lng: Number(point.lng) });
        });
        this.map.fitBounds(bounds, 42);
    };

    GoogleMapsProvider.prototype.fitBounds = function () {
        if (this.map && this.bounds && !this.bounds.isEmpty()) {
            this.map.fitBounds(this.bounds, 30);
        }
    };
    GoogleMapsProvider.prototype.getBounds = function () { if (!this.map) { return null; } var b=this.map.getBounds(); if (!b) { return null; } var ne=b.getNorthEast(), sw=b.getSouthWest(); return {north:ne.lat(),south:sw.lat(),east:ne.lng(),west:sw.lng()}; };
    GoogleMapsProvider.prototype.onBoundsChanged = function (callback) { if (this.map) { this.map.addListener('idle', callback); } };

    GoogleMapsProvider.prototype.setUserLocation = function (position, label) {
        var lat = Number(position && position.lat);
        var lng = Number(position && position.lng);

        if (!Number.isFinite(lat) || !Number.isFinite(lng) || !this.map) { return; }

        if (this.userMarker) {
            this.userMarker.setMap(null);
        }
        this.userMarker = new window.google.maps.Marker({
            map: this.map,
            position: { lat: lat, lng: lng },
            title: label || '',
            icon: {
                path: window.google.maps.SymbolPath.CIRCLE,
                scale: 8,
                fillColor: '#60a5fa',
                fillOpacity: 1,
                strokeColor: '#2563eb',
                strokeWeight: 3
            }
        });
        this.map.panTo(this.userMarker.getPosition());
        this.map.setZoom(12);
    };

    GoogleMapsProvider.prototype.destroy = function () {
        var self = this;
        Object.keys(this.markersById).forEach(function (id) {
            self.markersById[id].setMap(null);
        });
        if (this.userMarker) {
            this.userMarker.setMap(null);
        }
        this.map = null;
        this.infoWindow = null;
        this.userMarker = null;
        this.markersById = {};
        this.bounds = null;
    };

    window.BMLMapProviders.google = GoogleMapsProvider;
}(window, document));
