(function (window) {
    'use strict';

    function OpenStreetMapProvider() {
        window.BMLBaseMapProvider.call(this);
        this.markerLayer = null;
        this.userMarker = null;
        this._bounds = [];
    }

    OpenStreetMapProvider.prototype = Object.create(window.BMLBaseMapProvider.prototype);
    OpenStreetMapProvider.prototype.constructor = OpenStreetMapProvider;

    OpenStreetMapProvider.prototype.isReady = function () {
        return Boolean(window.L);
    };

    OpenStreetMapProvider.prototype.whenReady = function () {
        return this.isReady()
            ? Promise.resolve()
            : Promise.reject(new Error('Leaflet is not available.'));
    };

    OpenStreetMapProvider.prototype._latLng = function (location) {
        var lat = Number(location && location.lat);
        var lng = Number(location && location.lng);

        if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
            return null;
        }

        return [lat, lng];
    };

    OpenStreetMapProvider.prototype._markerIcon = function (location) {
        var iconUrl = location && location.category && location.category.icon;

        if (!iconUrl || !window.L.divIcon) {
            return null;
        }

        return window.L.divIcon({
            className: 'bml-map-category-icon',
            html: '<img src="' + String(iconUrl)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/"/g, '&quot;') + '" alt="">',
            iconSize: [42, 42],
            iconAnchor: [21, 21],
            popupAnchor: [0, -24]
        });
    };

    OpenStreetMapProvider.prototype.createMap = function (container, config, options) {
        options = options || {};
        window.BMLBaseMapProvider.prototype.init.call(this, container, options);
        config = config || {};
        this.config = config;
        var center = config.center || { lat: 0, lng: 0 };
        var tileUrl = config.tileUrl || '';

        if (!container) {
            throw new Error('OpenStreetMap container is missing.');
        }

        if (!this.isReady()) {
            throw new Error('Leaflet is not available.');
        }

        if (!tileUrl) {
            throw new Error('OpenStreetMap tile URL is missing.');
        }

        this.map = window.L.map(container).setView(
            [parseFloat(center.lat) || 0, parseFloat(center.lng) || 0],
            parseInt(config.zoom, 10) || 11
        );

        window.L.tileLayer(tileUrl, {
            maxZoom: 19,
            maxNativeZoom: 19,
            attribution: config.attribution || ''
        }).addTo(this.map);

        this.markerLayer = this.createMarkerLayer();
        this.markerLayer.addTo(this.map);

        return this;
    };

    OpenStreetMapProvider.prototype.createMarkerLayer = function () {
        return this.config && this.config.cluster && window.L.markerClusterGroup
            ? window.L.markerClusterGroup()
            : window.L.layerGroup();
    };

    OpenStreetMapProvider.prototype.createMarker = function (location, popupHtml, onSelect, layer, registry, bounds) {
        var latLng = this._latLng(location);
        var marker;
        var markerIcon;
        var markerOptions = {};

        if (!latLng || !layer) { return null; }

        markerIcon = this._markerIcon(location);
        if (markerIcon) {
            markerOptions.icon = markerIcon;
        }

        marker = window.L.marker(latLng, markerOptions);
        if (popupHtml) {
            marker.bindPopup(popupHtml);
        }
        marker.on('click', function () {
            if (onSelect) {
                onSelect(location);
            }
        });
        registry[location.id] = marker;
        layer.addLayer(marker);
        bounds.push(latLng);

        return marker;
    };

    OpenStreetMapProvider.prototype.addMarker = function (location, popupHtml, onSelect) {
        return this.createMarker(location, popupHtml, onSelect, this.markerLayer, this.markersById, this._bounds);
    };

    OpenStreetMapProvider.prototype.replaceMarkers = function (locations, popupRenderer, onSelect) {
        var self = this;
        var oldLayer = this.markerLayer;
        var newLayer;
        var newRegistry = {};
        var newBounds = [];

        if (!this.map) { return false; }
        newLayer = this.createMarkerLayer();
        if (!newLayer) { return false; }

        (locations || []).forEach(function (location) {
            self.createMarker(
                location,
                popupRenderer ? popupRenderer(location) : '',
                onSelect,
                newLayer,
                newRegistry,
                newBounds
            );
        });

        newLayer.addTo(this.map);
        this.markerLayer = newLayer;
        this.markersById = newRegistry;
        this._bounds = newBounds;

        if (oldLayer && oldLayer !== newLayer && this.map.hasLayer(oldLayer)) {
            this.map.removeLayer(oldLayer);
        }

        return true;
    };

    OpenStreetMapProvider.prototype.removeMarker = function (id) {
        var marker = this.markersById[id];
        if (!marker) { return; }
        if (this.markerLayer) {
            this.markerLayer.removeLayer(marker);
        }
        delete this.markersById[id];
    };

    OpenStreetMapProvider.prototype.clearMarkers = function () {
        if (this.markerLayer) {
            this.markerLayer.clearLayers();
        }
        this.markersById = {};
        this._bounds = [];
    };

    OpenStreetMapProvider.prototype.cluster = function (enabled) {
        return Boolean(enabled && window.L && window.L.markerClusterGroup && this.markerLayer && this.markerLayer.addLayer);
    };

    OpenStreetMapProvider.prototype.openMarkerPopup = function (id, content) {
        var marker = this.markersById[id];
        if (!marker) { return false; }
        if (content) { marker.bindPopup(content); }
        marker.openPopup();
        return true;
    };

    OpenStreetMapProvider.prototype.closePopup = function () {
        if (this.map && typeof this.map.closePopup === 'function') { this.map.closePopup(); }
    };

    OpenStreetMapProvider.prototype.openPopup = function (id) {
        return this.openMarkerPopup(id);
    };

    OpenStreetMapProvider.prototype.focusMarker = function (id, zoom) {
        var marker = this.markersById[id];
        var latLng;

        if (!marker || !this.map) { return; }

        latLng = marker.getLatLng();
        this.map.setView(latLng, zoom || 16, { animate: true });
        marker.openPopup();
    };

    OpenStreetMapProvider.prototype.focusCoordinates = function (lat, lng, zoom) {
        if (!this.map || !Number.isFinite(Number(lat)) || !Number.isFinite(Number(lng))) { return; }
        this.map.setView([Number(lat), Number(lng)], zoom || 16, { animate: true });
    };

    OpenStreetMapProvider.prototype.emphasizeMarker = function (id, enabled) {
        var marker = this.markersById[id];
        var element;
        if (!marker || typeof marker.getElement !== 'function') { return; }
        element = marker.getElement();
        if (element) { element.classList.toggle('bml-marker-is-emphasized', enabled !== false); }
    };

    OpenStreetMapProvider.prototype.invalidateSize = function () {
        if (this.map && typeof this.map.invalidateSize === 'function') {
            this.map.invalidateSize({ pan: false });
        }
    };

    OpenStreetMapProvider.prototype.fitCoordinates = function (coordinates) {
        var points;
        if (!this.map || !window.L || !Array.isArray(coordinates) || !coordinates.length) { return; }
        points = coordinates.filter(function (point) {
            return point && Number.isFinite(Number(point.lat)) && Number.isFinite(Number(point.lng));
        }).map(function (point) {
            return [Number(point.lat), Number(point.lng)];
        });
        if (!points.length) { return; }
        if (points.length === 1) {
            this.map.setView(points[0], 16, { animate: true });
            return;
        }
        this.map.fitBounds(window.L.latLngBounds(points), {
            padding: [42, 42],
            maxZoom: 15,
            animate: true
        });
    };

    OpenStreetMapProvider.prototype.fitBounds = function () {
        if (this.map && this._bounds && this._bounds.length) {
            this.map.fitBounds(this._bounds, { padding: [30, 30], maxZoom: 15 });
        }
    };
    OpenStreetMapProvider.prototype.getBounds = function () { if (!this.map) { return null; } var b=this.map.getBounds(); return {north:b.getNorth(),south:b.getSouth(),east:b.getEast(),west:b.getWest()}; };
    OpenStreetMapProvider.prototype.onBoundsChanged = function (callback) { if (this.map) { this.map.on('moveend', callback); } };

    OpenStreetMapProvider.prototype.setUserLocation = function (position, label) {
        var latLng = this._latLng(position);

        if (!latLng || !this.map) { return; }

        if (this.userMarker) {
            this.map.removeLayer(this.userMarker);
        }
        this.userMarker = window.L.circleMarker(latLng, {
            radius: 8,
            color: '#2563eb',
            fillColor: '#60a5fa',
            fillOpacity: 1,
            weight: 3
        }).addTo(this.map);
        if (label) {
            this.userMarker.bindPopup(label);
        }
        this.map.setView(latLng, 12);
    };

    OpenStreetMapProvider.prototype.destroy = function () {
        if (this.userMarker && this.map) {
            this.map.removeLayer(this.userMarker);
        }
        if (this.map) {
            this.map.remove();
        }
        this.map = null;
        this.markerLayer = null;
        this.userMarker = null;
        this.markersById = {};
        this._bounds = [];
    };

    window.BMLMapProviders.osm = OpenStreetMapProvider;
}(window));
