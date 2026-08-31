(function () {
    'use strict';

    function ready(callback) {
        if (document.readyState !== 'loading') {
            callback();
            return;
        }

        document.addEventListener('DOMContentLoaded', callback);
    }

    function initSettingsRuntime(studio, form) {
        var listeners = {};
        var initialSnapshot = snapshot();

        function snapshot() {
            return Array.from(new FormData(form).entries()).map(function (entry) {
                return entry[0] + '=' + entry[1];
            }).join('&');
        }

        function emit(eventName, value) {
            (listeners[eventName] || []).forEach(function (listener) {
                listener(value);
            });
        }

        function markDirty() {
            var dirty = snapshot() !== initialSnapshot;
            var saveState = studio.querySelector('[data-save-state]');
            var savebar = studio.querySelector('.bml-settings-studio__savebar');
            var label = studio.querySelector('[data-savebar-label]');

            if (saveState) {
                saveState.classList.toggle('is-dirty', dirty);
                saveState.querySelector('span').textContent = dirty ? 'Unsaved changes' : 'Saved';
            }
            if (savebar) {
                savebar.classList.toggle('is-dirty', dirty);
            }
            if (label) {
                label.textContent = dirty ? 'Unsaved changes' : 'Map settings';
            }

            form.dataset.dirty = dirty ? '1' : '0';
        }

        function selectedProvider() {
            var input = form.querySelector('input[name="provider"]:checked');
            return input ? input.value : 'osm';
        }

        function updateProviderUi() {
            var provider = selectedProvider();

            studio.querySelectorAll('.bml-settings-studio__provider label').forEach(function (label) {
                var input = label.querySelector('input');
                label.classList.toggle('is-selected', !!input && input.checked);
            });
            studio.querySelectorAll('[data-provider-panel]').forEach(function (panel) {
                panel.hidden = panel.dataset.providerPanel !== provider;
            });
        }

        form.addEventListener('input', markDirty);
        form.addEventListener('change', function (event) {
            markDirty();

            if (event.target.name === 'provider') {
                updateProviderUi();
                emit('provider', selectedProvider());
            }
            if (event.target.name === 'layout') {
                studio.querySelectorAll('.bml-settings-studio__layouts label').forEach(function (label) {
                    label.classList.toggle('is-selected', label.contains(event.target));
                });
                emit('layout', event.target.value);
            }
            if (event.target.matches('[data-preview-toggle]')) {
                emit('previewToggle', {
                    name: event.target.dataset.previewToggle,
                    enabled: event.target.checked
                });
            }
            if (event.target.name === 'map_style') {
                emit('mapStyle', event.target.value);
            }
            if (event.target.name === 'marker_color' || event.target.name === 'cluster') {
                emit('mapAppearance');
            }
        });
        form.addEventListener('submit', function () {
            form.dataset.dirty = '0';
        });
        window.addEventListener('beforeunload', function (event) {
            if (form.dataset.dirty === '1') {
                event.preventDefault();
                event.returnValue = '';
            }
        });

        studio.querySelectorAll('[data-submit-settings]').forEach(function (button) {
            button.addEventListener('click', function () {
                form.requestSubmit();
            });
        });

        var resetForm = studio.querySelector('[data-reset-form]');
        if (resetForm) {
            resetForm.addEventListener('click', function () {
                if (form.dataset.dirty === '1' && !window.confirm('Discard unsaved changes?')) { return; }
                window.location.reload();
            });
        }

        var toggleKey = studio.querySelector('[data-toggle-key]');
        var googleKeyInput = document.getElementById('bml-google-key');
        if (toggleKey && googleKeyInput) {
            toggleKey.addEventListener('click', function () {
                var hidden = googleKeyInput.type === 'password';
                googleKeyInput.type = hidden ? 'text' : 'password';
                toggleKey.textContent = hidden ? 'Hide' : 'Show';
            });
        }

        studio.querySelectorAll('[data-copy-shortcode]').forEach(function (copyButton) {
            copyButton.addEventListener('click', function () {
                var original = copyButton.textContent;
                navigator.clipboard.writeText('[business_map_locator]').then(function () {
                    copyButton.textContent = '✓';
                    window.setTimeout(function () {
                        copyButton.textContent = original;
                    }, 1400);
                });
            });
        });

        updateProviderUi();

        return {
            on: function (eventName, listener) {
                listeners[eventName] = listeners[eventName] || [];
                listeners[eventName].push(listener);
            },
            selectedProvider: selectedProvider
        };
    }

    function initMapPreviewRuntime(studio, form, settingsRuntime) {
        var previewShell = studio.querySelector('[data-preview-shell]');
        if (!previewShell) {
            return null;
        }

        var restBase = window.BMLAdmin && BMLAdmin.restUrl ? BMLAdmin.restUrl : '/wp-json/business-map/v1/';
        var restNonce = window.BMLAdmin && BMLAdmin.nonce ? BMLAdmin.nonce : '';
        var osmElement = document.getElementById('bml-settings-map');
        var googleElement = document.getElementById('bml-google-preview-map');
        var loading = studio.querySelector('[data-map-loading]');
        var progress = studio.querySelector('[data-loading-progress]');
        var loadedCount = studio.querySelector('[data-loaded-count]');
        var providerLabel = studio.querySelector('[data-provider-label]');
        var googleStatus = studio.querySelector('[data-google-status]');
        var googleKeyInput = document.getElementById('bml-google-key');
        var leafletMap = null;
        var leafletLayer = null;
        var leafletTileLayer = null;
        var leafletTileFallbackApplied = false;
        var suppressLeafletBoundsReload = false;
        var leafletBounds = [];
        var googleMap = null;
        var googleMarkers = [];
        var googleBounds = null;
        var locations = [];
        var markerAbortController = null;
        var markerSequence = 0;
        var markerDebounceTimer = null;
        var boundsAbortController = null;
        var PREVIEW_MARKER_LIMIT = 1000;
        var leafletEventsBound = false;
        var googleEventsBound = false;
        var previewView = { lat: parseFloat((window.BMLAdmin && BMLAdmin.settings && BMLAdmin.settings.center_lat) || 53.9006), lng: parseFloat((window.BMLAdmin && BMLAdmin.settings && BMLAdmin.settings.center_lng) || 27.5590), zoom: parseInt((window.BMLAdmin && BMLAdmin.settings && BMLAdmin.settings.zoom) || 11, 10) };

        if (!osmElement || !googleElement) {
            return null;
        }

        function markerBounds() {
            var bounds;
            if (settingsRuntime.selectedProvider() === 'google' && googleMap && googleMap.getBounds) {
                bounds = googleMap.getBounds();
                if (bounds) { return { north: bounds.getNorthEast().lat(), south: bounds.getSouthWest().lat(), east: bounds.getNorthEast().lng(), west: bounds.getSouthWest().lng() }; }
            }
            if (leafletMap && leafletMap.getBounds && leafletMap.getBounds().isValid()) {
                bounds = leafletMap.getBounds();
                return { north: bounds.getNorth(), south: bounds.getSouth(), east: bounds.getEast(), west: bounds.getWest() };
            }
            return null;
        }

        function fetchMarkers(bounds) {
            var params = Object.assign({}, bounds, { limit: PREVIEW_MARKER_LIMIT });
            var url = restBase.replace(/\/?$/, '/') + 'locations/markers?' + new URLSearchParams(params).toString();
            if (markerAbortController) { markerAbortController.abort(); }
            markerAbortController = window.AbortController ? new AbortController() : null;
            return fetch(url, { headers: restNonce ? { 'X-WP-Nonce': restNonce } : {}, signal: markerAbortController && markerAbortController.signal }).then(function (response) {
                if (!response.ok) {
                    throw new Error('REST ' + response.status);
                }
                return response.json();
            });
        }

        function fetchPreviewBounds() {
            var url = restBase.replace(/\/?$/, '/') + 'locations/bounds';
            if (boundsAbortController) { boundsAbortController.abort(); }
            boundsAbortController = window.AbortController ? new AbortController() : null;
            return fetch(url, {
                headers: restNonce ? { 'X-WP-Nonce': restNonce } : {},
                signal: boundsAbortController && boundsAbortController.signal
            }).then(function (response) {
                if (!response.ok) { throw new Error('REST ' + response.status); }
                return response.json();
            });
        }

        function markerIcon() {
            var color = form.querySelector('[data-marker-color]');
            var value = color ? color.value : '#2876f0';
            return L.divIcon({
                className: 'bml-settings-studio__marker-wrap',
                html: '<span style="display:block;width:24px;height:24px;border:3px solid #fff;border-radius:50% 50% 50% 0;background:' + value + ';transform:rotate(-45deg);box-shadow:0 4px 12px rgba(15,23,42,.25)"></span>',
                iconSize: [28, 28], iconAnchor: [14, 28], popupAnchor: [0, -28]
            });
        }

        function findExistingLeafletMap(container) {
            var targets = (window.L && L._targets) || (window.L && L.DomEvent && L.DomEvent._targets) || null;
            if (!container || !container._leaflet_id || !targets) {
                return null;
            }
            for (var key in targets) {
                if (Object.prototype.hasOwnProperty.call(targets, key) && targets[key] && targets[key]._container === container) {
                    return targets[key];
                }
            }
            return null;
        }

        function canonicalOsmTileUrl() {
            return 'https://tile.openstreetmap.org/{z}/{x}/{y}.png';
        }

        function configuredOsmTileUrl() {
            return (window.BMLAdmin && BMLAdmin.settings && BMLAdmin.settings.tile_url) || canonicalOsmTileUrl();
        }

        function attachLeafletTileLayer(tileUrl) {
            if (!leafletMap) { return; }
            if (leafletTileLayer) {
                leafletMap.removeLayer(leafletTileLayer);
            }
            leafletTileLayer = L.tileLayer(tileUrl, {
                maxZoom: 19,
                maxNativeZoom: 19,
                attribution: (window.BMLAdmin && BMLAdmin.settings && BMLAdmin.settings.attribution) || '&copy; OpenStreetMap contributors'
            });
            leafletTileLayer.on('tileerror', function () {
                if (leafletTileFallbackApplied || tileUrl === canonicalOsmTileUrl()) { return; }
                leafletTileFallbackApplied = true;
                attachLeafletTileLayer(canonicalOsmTileUrl());
            });
            leafletTileLayer.addTo(leafletMap);
        }

        function invalidateLeafletSize() {
            if (!leafletMap) { return; }
            suppressLeafletBoundsReload = true;
            leafletMap.invalidateSize({ pan: false });
            window.setTimeout(function () {
                suppressLeafletBoundsReload = false;
            }, 120);
        }

        function renderLeafletMap() {
            if (!window.L) {
                return;
            }
            if (!leafletMap) {
                leafletMap = findExistingLeafletMap(osmElement);
                if (!leafletMap) {
                    leafletMap = L.map(osmElement, { zoomControl: true }).setView([previewView.lat, previewView.lng], previewView.zoom);
                    attachLeafletTileLayer(configuredOsmTileUrl());
                    if (!leafletEventsBound) {
                        leafletEventsBound = true;
                        leafletMap.on('moveend zoomend', function () {
                            syncControlsFromMap();
                            if (!suppressLeafletBoundsReload) { scheduleMarkerLoad(); }
                        });
                    }
                }
            }
            if (leafletLayer) {
                leafletLayer.remove();
            }
            leafletLayer = form.querySelector('input[name="cluster"]:checked') && L.markerClusterGroup ? L.markerClusterGroup() : L.layerGroup();
            leafletBounds = [];
            locations.forEach(function (item) {
                var lat = Number(item && item.lat);
                var lng = Number(item && item.lng);
                if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
                    return;
                }
                var marker = L.marker([lat, lng], { icon: markerIcon() });
                marker.bindPopup('<strong>' + escapeHtml(item.title || '') + '</strong><br><span>' + escapeHtml(item.address || '') + '</span>');
                leafletLayer.addLayer(marker);
                leafletBounds.push([lat, lng]);
            });
            leafletLayer.addTo(leafletMap);
        }

        function googleStyle() {
            var style = form.querySelector('[data-map-style]:checked') || form.querySelector('[data-map-style]');
            var value = style ? style.value : 'streets';
            if (value === 'mono') {
                return [{ stylers: [{ saturation: -100 }] }];
            }
            if (value === 'soft') {
                return [{ stylers: [{ saturation: -35 }, { lightness: 10 }] }];
            }
            return [];
        }

        function renderGoogleMap() {
            if (!window.google || !google.maps) {
                return;
            }
            if (!googleMap) {
                googleMap = new google.maps.Map(googleElement, { center: { lat: previewView.lat, lng: previewView.lng }, zoom: previewView.zoom });
                if (!googleEventsBound) { googleEventsBound = true; google.maps.event.addListener(googleMap, 'idle', function () { syncControlsFromMap(); scheduleMarkerLoad(); }); }
            }
            googleMap.setOptions({ styles: googleStyle() });
            googleMarkers.forEach(function (marker) {
                marker.setMap(null);
            });
            googleMarkers = [];
            googleBounds = new google.maps.LatLngBounds();
            locations.forEach(function (item) {
                if (typeof item.lat !== 'number' || typeof item.lng !== 'number') {
                    return;
                }
                var color = form.querySelector('[data-marker-color]');
                var marker = new google.maps.Marker({
                    position: { lat: item.lat, lng: item.lng },
                    map: googleMap,
                    title: item.title || '',
                    icon: {
                        path: google.maps.SymbolPath.CIRCLE,
                        fillColor: color ? color.value : '#2876f0',
                        fillOpacity: 1,
                        strokeColor: '#ffffff',
                        strokeWeight: 3,
                        scale: 9
                    }
                });
                var info = new google.maps.InfoWindow({ content: '<strong>' + escapeHtml(item.title || '') + '</strong><br><span>' + escapeHtml(item.address || '') + '</span>' });
                marker.addListener('click', function () {
                    info.open({ anchor: marker, map: googleMap });
                });
                googleMarkers.push(marker);
                googleBounds.extend(marker.getPosition());
            });
        }

        function cancelMarkerLoad() {
            markerSequence += 1;
            if (markerAbortController) { markerAbortController.abort(); markerAbortController = null; }
            if (boundsAbortController) { boundsAbortController.abort(); boundsAbortController = null; }
            if (markerDebounceTimer) { window.clearTimeout(markerDebounceTimer); markerDebounceTimer = null; }
        }

        function destroyPreviewProviders() {
            cancelMarkerLoad();
            if (leafletMap) { previewView = { lat: leafletMap.getCenter().lat, lng: leafletMap.getCenter().lng, zoom: leafletMap.getZoom() }; leafletMap.off(); leafletMap.remove(); leafletMap = null; leafletLayer = null; leafletTileLayer = null; leafletTileFallbackApplied = false; suppressLeafletBoundsReload = false; leafletEventsBound = false; }
            if (googleMap) {
                previewView = { lat: googleMap.getCenter().lat(), lng: googleMap.getCenter().lng(), zoom: googleMap.getZoom() };
                googleMarkers.forEach(function (marker) { marker.setMap(null); });
                if (window.google && google.maps && google.maps.event) { google.maps.event.clearInstanceListeners(googleMap); }
                googleMap = null; googleMarkers = []; googleBounds = null; googleEventsBound = false;
            }
        }

        function updatePreviewProvider(provider) {
            destroyPreviewProviders();
            previewShell.dataset.provider = provider;
            if (providerLabel) {
                providerLabel.textContent = provider === 'google' ? 'Google Maps' : 'OpenStreetMap';
            }
            if (provider === 'google') {
                osmElement.hidden = true;
                googleElement.hidden = false;
                if (window.google && google.maps) {
                    renderGoogleMap();
                }
            } else {
                googleElement.hidden = true;
                osmElement.hidden = false;
                renderLeafletMap();
            }
            window.setTimeout(function () {
                window.dispatchEvent(new Event('resize'));
            }, 80);
        }

        function loadViewportMarkers() {
            var bounds = markerBounds();
            var sequence;
            if (!bounds) { return Promise.resolve(); }
            sequence = ++markerSequence;
            if (loading) { loading.hidden = false; }
            if (progress) { progress.textContent = loading ? (loading.dataset.loadingText || '') : ''; }
            return fetchMarkers(bounds).then(function (data) {
                if (sequence !== markerSequence) { return; }
                locations = data.items || [];
                if (loadedCount) { loadedCount.textContent = locations.length; }
                if (progress) {
                    progress.textContent = data.truncated
                        ? ('Previewing ' + locations.length + ' locations. Zoom in for a smaller area.')
                        : (locations.length ? String(locations.length) : (loading ? (loading.dataset.emptyText || '') : ''));
                }
                if (settingsRuntime.selectedProvider() === 'google' && window.google && google.maps) {
                    renderGoogleMap();
                } else {
                    renderLeafletMap();
                }
            }).catch(function (error) {
                if (error.name === 'AbortError' || sequence !== markerSequence) { return; }
                if (progress) { progress.textContent = loading ? (loading.dataset.errorText || '') : ''; }
            }).finally(function () {
                if (sequence === markerSequence && loading) { loading.hidden = true; }
            });
        }

        function scheduleMarkerLoad() {
            if (markerDebounceTimer) { window.clearTimeout(markerDebounceTimer); }
            markerDebounceTimer = window.setTimeout(function () {
                markerDebounceTimer = null;
                loadViewportMarkers();
            }, 350);
        }

        function escapeHtml(value) {
            return String(value).replace(/[&<>'"]/g, function (character) {
                return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;' })[character];
            });
        }

        function setGoogleStatus(type, text) {
            if (!googleStatus) {
                return;
            }
            googleStatus.className = 'bml-settings-studio__status ' + type;
            googleStatus.querySelector('span').textContent = text;
        }

        function loadGoogleApi(key) {
            return new Promise(function (resolve, reject) {
                if (window.google && google.maps) {
                    resolve();
                    return;
                }
                var old = document.getElementById('bml-google-maps-runtime');
                if (old) {
                    old.remove();
                }
                var callback = '__bmlGoogleReady' + Date.now();
                var timeout = window.setTimeout(function () {
                    reject(new Error('Google Maps API did not respond in time.'));
                }, 15000);
                window.gm_authFailure = function () {
                    window.clearTimeout(timeout);
                    reject(new Error('Google rejected the key. Check Billing and HTTP referrer restrictions.'));
                };
                window[callback] = function () {
                    window.clearTimeout(timeout);
                    delete window[callback];
                    resolve();
                };
                var script = document.createElement('script');
                script.id = 'bml-google-maps-runtime';
                script.async = true;
                script.onerror = function () {
                    window.clearTimeout(timeout);
                    reject(new Error('Maps JavaScript API could not be loaded.'));
                };
                script.src = 'https://maps.googleapis.com/maps/api/js?key=' + encodeURIComponent(key) + '&callback=' + callback;
                document.head.appendChild(script);
            });
        }

        function testGoogleKey() {
            var key = googleKeyInput ? googleKeyInput.value.trim() : '';
            if (!key) {
                setGoogleStatus('is-error', 'Enter a Google Browser API key.');
                return;
            }
            setGoogleStatus('is-testing', 'Testing Maps JavaScript API and geocoding…');
            loadGoogleApi(key).then(function () {
                return new Promise(function (resolve, reject) {
                    var geocoder = new google.maps.Geocoder();
                    geocoder.geocode({ address: 'Berlin, Germany' }, function (results, status) {
                        if (status === 'OK' && results && results.length) {
                            resolve();
                            return;
                        }
                        reject(new Error('Maps API works, but Geocoding API is unavailable: ' + status));
                    });
                });
            }).then(function () {
                setGoogleStatus('is-success', 'The key is valid. Maps JavaScript API and Geocoding API are available.');
                renderGoogleMap();
                scheduleMarkerLoad();
            }).catch(function (error) {
                setGoogleStatus('is-error', error.message || 'The connection test failed.');
            });
        }


        var centerSearch = studio.querySelector('[data-center-search]');
        var centerResults = studio.querySelector('[data-center-results]');
        var centerStatus = studio.querySelector('[data-center-status]');
        var centerLatInput = studio.querySelector('[data-center-lat]');
        var centerLngInput = studio.querySelector('[data-center-lng]');
        var centerLabel = studio.querySelector('[data-center-label]');
        var centerCoordinates = studio.querySelector('[data-center-coordinates]');
        var advancedLatInput = studio.querySelector('[data-advanced-lat]');
        var advancedLngInput = studio.querySelector('[data-advanced-lng]');
        var zoomRange = studio.querySelector('[data-zoom-range]');
        var zoomOutput = studio.querySelector('[data-zoom-output]');
        var frontendPreview = studio.querySelector('[data-frontend-preview]');
        var frontendList = studio.querySelector('[data-frontend-list]');
        var frontendCount = studio.querySelector('[data-frontend-count]');
        var frontendLoaded = false;

        function setCenterStatus(type, text) {
            if (!centerStatus) { return; }
            centerStatus.className = 'bml-settings-studio__status ' + type;
            var span = centerStatus.querySelector('span');
            if (span) { span.textContent = text; }
        }

        function updateCenterFields(lat, lng, label) {
            previewView.lat = Number(lat);
            previewView.lng = Number(lng);
            if (centerLatInput) { centerLatInput.value = Number(lat).toFixed(6); }
            if (centerLngInput) { centerLngInput.value = Number(lng).toFixed(6); }
            if (centerCoordinates) { centerCoordinates.textContent = Number(lat).toFixed(6) + ', ' + Number(lng).toFixed(6); }
            if (advancedLatInput) { advancedLatInput.value = Number(lat).toFixed(6); }
            if (advancedLngInput) { advancedLngInput.value = Number(lng).toFixed(6); }
            if (centerLabel && label) { centerLabel.textContent = label; }
            if (leafletMap) { leafletMap.setView([previewView.lat, previewView.lng], previewView.zoom, { animate: true }); }
            if (googleMap) { googleMap.setCenter({ lat: previewView.lat, lng: previewView.lng }); googleMap.setZoom(previewView.zoom); }
            form.dispatchEvent(new Event('input', { bubbles: true }));
            scheduleMarkerLoad();
        }

        function syncControlsFromMap() {
            var center;
            if (settingsRuntime.selectedProvider() === 'google' && googleMap) {
                center = googleMap.getCenter();
                previewView = { lat: center.lat(), lng: center.lng(), zoom: googleMap.getZoom() };
            } else if (leafletMap) {
                center = leafletMap.getCenter();
                previewView = { lat: center.lat, lng: center.lng, zoom: leafletMap.getZoom() };
            } else { return; }
            if (zoomRange) { zoomRange.value = String(previewView.zoom); }
            if (zoomOutput) { zoomOutput.textContent = String(previewView.zoom); }
        }

        function selectGeocodeResult(item) {
            if (centerResults) { centerResults.hidden = true; centerResults.innerHTML = ''; }
            if (centerSearch) { centerSearch.value = item.display_name || ''; }
            setCenterStatus('is-success', 'Location found. Coordinates and preview were updated.');
            updateCenterFields(item.lat, item.lng, item.display_name || 'Selected map center');
        }

        function renderCenterResults(items) {
            if (!centerResults) { return; }
            centerResults.innerHTML = '';
            if (!items.length) {
                centerResults.hidden = true;
                setCenterStatus('is-error', 'No matching place was found. Try a more specific name.');
                return;
            }
            items.slice(0, 6).forEach(function (item) {
                var button = document.createElement('button');
                button.type = 'button';
                button.className = 'bml-settings-studio__geocode-result';
                button.innerHTML = '<strong>' + escapeHtml(item.display_name || '') + '</strong><small>' + Number(item.lat).toFixed(5) + ', ' + Number(item.lng).toFixed(5) + '</small>';
                button.addEventListener('click', function () { selectGeocodeResult(item); });
                centerResults.appendChild(button);
            });
            centerResults.hidden = false;
        }

        function findCenter() {
            var query = centerSearch ? centerSearch.value.trim() : '';
            if (query.length < 3) {
                setCenterStatus('is-error', 'Enter at least three characters.');
                return;
            }
            setCenterStatus('is-testing', 'Searching for the place…');
            fetch(restBase.replace(/\/?$/, '/') + 'geocode/search?q=' + encodeURIComponent(query), {
                headers: restNonce ? { 'X-WP-Nonce': restNonce } : {}
            }).then(function (response) {
                if (!response.ok) { throw new Error('REST ' + response.status); }
                return response.json();
            }).then(renderCenterResults).catch(function () {
                setCenterStatus('is-error', 'The place could not be found. Please try again.');
            });
        }

        var findCenterButton = studio.querySelector('[data-find-center]');
        if (findCenterButton) { findCenterButton.addEventListener('click', findCenter); }
        if (centerSearch) {
            centerSearch.addEventListener('keydown', function (event) {
                if (event.key === 'Enter') { event.preventDefault(); findCenter(); }
            });
        }
        var clearCenterSearch = studio.querySelector('[data-clear-center-search]');
        if (clearCenterSearch) { clearCenterSearch.addEventListener('click', function () { if (centerSearch) { centerSearch.value = ''; centerSearch.focus(); } if (centerResults) { centerResults.hidden = true; centerResults.innerHTML = ''; } }); }
        studio.querySelectorAll('[data-use-map-center]').forEach(function (button) {
            button.addEventListener('click', function () {
                syncControlsFromMap();
                updateCenterFields(previewView.lat, previewView.lng, 'Current preview position');
                setCenterStatus('is-success', 'Current map position is now the initial center.');
            });
        });
        [advancedLatInput, advancedLngInput].forEach(function (input) {
            if (!input) { return; }
            input.addEventListener('change', function () {
                var lat = parseFloat(advancedLatInput ? advancedLatInput.value : '');
                var lng = parseFloat(advancedLngInput ? advancedLngInput.value : '');
                if (!Number.isFinite(lat) || !Number.isFinite(lng) || lat < -90 || lat > 90 || lng < -180 || lng > 180) {
                    setCenterStatus('is-error', 'Enter valid latitude and longitude values.');
                    return;
                }
                updateCenterFields(lat, lng, 'Manual map center');
                setCenterStatus('is-success', 'Manual coordinates were applied to the preview.');
            });
        });

        if (zoomRange) {
            zoomRange.addEventListener('input', function () {
                previewView.zoom = parseInt(zoomRange.value, 10) || 11;
                if (zoomOutput) { zoomOutput.textContent = String(previewView.zoom); }
                if (leafletMap) { leafletMap.setZoom(previewView.zoom); }
                if (googleMap) { googleMap.setZoom(previewView.zoom); }
            });
        }

        function renderFrontendCards(items, total) {
            if (!frontendList || !frontendCount) { return; }
            frontendList.innerHTML = '';
            frontendCount.textContent = 'Showing ' + Math.min(items.length, total) + ' of ' + total + ' locations';
            items.slice(0, 6).forEach(function (item) {
                var card = document.createElement('article');
                card.className = 'bml-settings-studio__frontend-card';
                card.innerHTML = '<span class="bml-settings-studio__frontend-thumb" aria-hidden="true"></span><div><strong>' + escapeHtml(item.title || '') + '</strong><small>' + escapeHtml(item.category && item.category.name ? item.category.name : (item.category || 'Location')) + '</small><p data-preview-card-address>' + escapeHtml(item.address || '') + '</p><div class="bml-settings-studio__frontend-actions"><span data-preview-card-phone>Call</span><span data-preview-card-directions>Directions</span><span>Show on map</span></div></div>';
                frontendList.appendChild(card);
            });
        }

        function loadFrontendPreview() {
            if (frontendLoaded || !frontendPreview) { return; }
            frontendLoaded = true;
            fetch(restBase.replace(/\/?$/, '/') + 'locations?page=1&per_page=6&orderby=title&order=ASC', {
                headers: restNonce ? { 'X-WP-Nonce': restNonce } : {}
            }).then(function (response) {
                if (!response.ok) { throw new Error('REST ' + response.status); }
                return response.json();
            }).then(function (data) {
                var items = data.items || [];
                var total = data.pagination && typeof data.pagination.total === 'number' ? data.pagination.total : items.length;
                renderFrontendCards(items, total);
            }).catch(function () {
                if (frontendCount) { frontendCount.textContent = 'Frontend cards could not be loaded.'; }
            });
        }

        function applyPreviewLayout(layout) {
            if (!frontendPreview) { return; }
            frontendPreview.dataset.layout = layout || 'split';
        }

        function applyPreviewToggle(change) {
            if (!frontendPreview || !change) { return; }
            frontendPreview.classList.toggle('is-' + change.name + '-hidden', !change.enabled);
        }

        studio.addEventListener('bml:studio-tab', function (event) {
            var name = event.detail && event.detail.name ? event.detail.name : 'map';
            studio.querySelector('.bml-settings-studio__preview-panel').dataset.previewMode = name;
            if (frontendPreview) { frontendPreview.hidden = false; }
            loadFrontendPreview();
            window.setTimeout(function () { if (leafletMap) { invalidateLeafletSize(); } window.dispatchEvent(new Event('resize')); }, 60);
        });

        settingsRuntime.on('provider', updatePreviewProvider);
        settingsRuntime.on('layout', applyPreviewLayout);
        settingsRuntime.on('previewToggle', applyPreviewToggle);
        settingsRuntime.on('mapStyle', function (style) {
            previewShell.dataset.style = style;
            if (settingsRuntime.selectedProvider() === 'google') { renderGoogleMap(); }
        });
        settingsRuntime.on('mapAppearance', function () {
            if (settingsRuntime.selectedProvider() === 'google') { renderGoogleMap(); } else { renderLeafletMap(); }
        });

        var testButton = studio.querySelector('[data-test-google-key]');
        if (testButton) {
            testButton.addEventListener('click', testGoogleKey);
        }
        var height = studio.querySelector('[data-map-height]');
        if (height) {
            height.addEventListener('input', function () {
                previewShell.style.setProperty('--bml-map-height', height.value + 'px');
                var heightOutput = studio.querySelector('[data-height-output]');
                if (heightOutput) { heightOutput.textContent = height.value + ' px'; }
                window.setTimeout(function () {
                    if (leafletMap) {
                        invalidateLeafletSize();
                    }
                }, 30);
            });
        }
        studio.querySelectorAll('[data-fit-all]').forEach(function (fitAll) {
            fitAll.addEventListener('click', function () {
                fitAll.disabled = true;
                fetchPreviewBounds().then(function (data) {
                    var north = Number(data.north);
                    var south = Number(data.south);
                    var east = Number(data.east);
                    var west = Number(data.west);
                    if (![north, south, east, west].every(Number.isFinite)) { return; }
                    if (settingsRuntime.selectedProvider() === 'google' && googleMap && window.google && google.maps) {
                        var bounds = new google.maps.LatLngBounds();
                        bounds.extend({ lat: south, lng: west });
                        bounds.extend({ lat: north, lng: east });
                        googleMap.fitBounds(bounds, 28);
                    } else if (leafletMap) {
                        leafletMap.fitBounds([[south, west], [north, east]], { padding: [28, 28], maxZoom: 15 });
                    }
                }).catch(function (error) {
                    if (error.name !== 'AbortError' && progress) { progress.textContent = 'Could not fit published locations.'; }
                }).finally(function () {
                    fitAll.disabled = false;
                });
            });
        });
        var reload = studio.querySelector('[data-reload-map]');
        if (reload) {
            reload.addEventListener('click', loadViewportMarkers);
        }

        applyPreviewLayout((form.querySelector('input[name="layout"]:checked') || {}).value || 'split');
        loadFrontendPreview();
        updatePreviewProvider(settingsRuntime.selectedProvider());
        scheduleMarkerLoad();

        return {
            loadMarkers: loadViewportMarkers
        };
    }


    function initStudioTabs(studio) {
        var tabs = Array.from(studio.querySelectorAll('[data-studio-tab]'));
        var panels = Array.from(studio.querySelectorAll('[data-studio-panel]'));

        if (!tabs.length || !panels.length) {
            return;
        }

        function activate(name, focus) {
            tabs.forEach(function (tab) {
                var active = tab.dataset.studioTab === name;
                tab.classList.toggle('is-active', active);
                tab.setAttribute('aria-selected', active ? 'true' : 'false');
                tab.tabIndex = active ? 0 : -1;
                if (active && focus) { tab.focus(); }
            });
            panels.forEach(function (panel) {
                var active = panel.dataset.studioPanel === name;
                panel.hidden = !active;
                panel.classList.toggle('is-active', active);
            });
            try { window.sessionStorage.setItem('bmlSettingsStudioTab', name); } catch (error) {}
            studio.dispatchEvent(new CustomEvent('bml:studio-tab', { detail: { name: name } }));
            window.setTimeout(function () { window.dispatchEvent(new Event('resize')); }, 40);
        }

        tabs.forEach(function (tab, index) {
            tab.addEventListener('click', function () { activate(tab.dataset.studioTab, false); });
            tab.addEventListener('keydown', function (event) {
                var next;
                if (event.key !== 'ArrowLeft' && event.key !== 'ArrowRight') { return; }
                event.preventDefault();
                next = event.key === 'ArrowRight' ? (index + 1) % tabs.length : (index - 1 + tabs.length) % tabs.length;
                activate(tabs[next].dataset.studioTab, true);
            });
        });

        var saved = '';
        try { saved = window.sessionStorage.getItem('bmlSettingsStudioTab') || ''; } catch (error) {}
        activate(tabs.some(function (tab) { return tab.dataset.studioTab === saved; }) ? saved : tabs[0].dataset.studioTab, false);
    }

    function initPreviewDevices(studio) {
        var canvas = studio.querySelector('[data-preview-canvas]');
        var buttons = Array.from(studio.querySelectorAll('[data-preview-device]'));
        if (!canvas || !buttons.length) { return; }
        buttons.forEach(function (button) {
            button.addEventListener('click', function () {
                var device = button.dataset.previewDevice || 'desktop';
                canvas.dataset.device = device;
                buttons.forEach(function (candidate) {
                    var active = candidate === button;
                    candidate.classList.toggle('is-active', active);
                    candidate.setAttribute('aria-pressed', active ? 'true' : 'false');
                });
                window.setTimeout(function () { window.dispatchEvent(new Event('resize')); }, 260);
            });
        });
    }

    ready(function () {
        var studio = document.querySelector('[data-bml-settings-studio]');
        var form = studio && studio.querySelector('.bml-settings-studio__form');
        if (!studio || !form) {
            return;
        }

        initStudioTabs(studio);
        initPreviewDevices(studio);
        var settingsRuntime = initSettingsRuntime(studio, form);
        initMapPreviewRuntime(studio, form, settingsRuntime);
    });
}());

(function () {
    'use strict';
    function initFullscreenStudio() {
        var studio = document.querySelector('[data-bml-settings-studio]');
        if (!studio || !studio.classList.contains('bml-settings-studio--fullscreen')) { return; }
        document.body.classList.add('bml-map-studio-fullscreen');
        var collapse = studio.querySelector('[data-collapse-inspector]');
        if (!collapse) { return; }
        collapse.addEventListener('click', function () {
            var collapsed = studio.classList.toggle('is-inspector-collapsed');
            collapse.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
            collapse.setAttribute('aria-label', collapsed ? 'Expand settings panel' : 'Collapse settings panel');
            collapse.querySelector('span').textContent = collapsed ? '⇥' : '⇤';
            window.setTimeout(function () { window.dispatchEvent(new Event('resize')); }, 240);
        });
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initFullscreenStudio);
    } else {
        initFullscreenStudio();
    }
}());
