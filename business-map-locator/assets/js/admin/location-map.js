(function () {
    'use strict';

    function ready(callback) {
        if (document.readyState !== 'loading') {
            callback();
            return;
        }
        document.addEventListener('DOMContentLoaded', callback);
    }

    function api(path) {
        return window.wp.apiFetch({
            path: '/business-map/v1/' + path,
            headers: {
                'X-WP-Nonce': BMLAdmin.nonce
            }
        });
    }

    function selectedText(selector) {
        var field = document.querySelector(selector);
        if (!field || !field.value || !field.options[field.selectedIndex]) {
            return '';
        }
        return field.options[field.selectedIndex].text.trim();
    }

    function normalizePlaceName(value) {
        return String(value || '')
            .toLocaleLowerCase()
            .replace(/\b(городской пос[её]лок|город|пос[её]лок|деревня|село|агрогородок|municipality|city|town|village)\b/giu, '')
            .replace(/[.()'’`-]/g, ' ')
            .replace(/\s+/g, ' ')
            .trim();
    }

    function houseNumberFromQuery(value) {
        var match = String(value || '').trim().match(/(?:^|[\s,])([0-9]+[A-Za-zА-Яа-я]?(?:[\/-][0-9A-Za-zА-Яа-я]+)?)\s*$/u);
        return match ? match[1] : '';
    }

    ready(function () {
        var root = document.querySelector('.bml-editor-page');
        var mapElement = document.getElementById('bml-admin-map');
        var searchInput = document.getElementById('bml-address-search');
        var findButton = document.getElementById('bml-find-address');
        var resultsBox = document.getElementById('bml-geocode-results');
        var latitudeInput = document.getElementById('bml_lat');
        var longitudeInput = document.getElementById('bml_lng');
        var note = document.getElementById('bml-auto-address-note');
        var citySelect = document.querySelector('select[name="city_id"]');

        if (!root || !mapElement || !window.L || !latitudeInput || !longitudeInput) {
            return;
        }

        var latitude = parseFloat(latitudeInput.value || BMLAdmin.settings.center_lat);
        var longitude = parseFloat(longitudeInput.value || BMLAdmin.settings.center_lng);
        var map = L.map(mapElement).setView(
            [latitude, longitude],
            parseInt(BMLAdmin.settings.zoom, 10)
        );

        L.tileLayer(BMLAdmin.settings.tile_url, {
            maxZoom: 20,
            attribution: BMLAdmin.settings.attribution
        }).addTo(map);

        var marker = L.marker([latitude, longitude], { draggable: true }).addTo(map);

        function setState(state, title, text) {
            if (!note) {
                return;
            }
            note.dataset.state = state;
            var heading = note.querySelector('strong');
            var paragraph = note.querySelector('p');
            if (heading) {
                heading.textContent = title;
            }
            if (paragraph) {
                paragraph.textContent = text || '';
            }
        }

        function dispatchInput(field) {
            field.dispatchEvent(new Event('input', { bubbles: true }));
        }

        function setPoint(point, reverseLookupRequired) {
            marker.setLatLng(point);
            latitudeInput.value = Number(point.lat).toFixed(7);
            longitudeInput.value = Number(point.lng).toFixed(7);
            dispatchInput(latitudeInput);
            dispatchInput(longitudeInput);

            if (reverseLookupRequired) {
                reverseLookup(point.lat, point.lng);
            }
        }

        function selectCity(cityName) {
            if (!citySelect || !cityName) {
                return false;
            }
            var expected = normalizePlaceName(cityName);
            var matched = Array.prototype.find.call(citySelect.options, function (option) {
                return option.value && normalizePlaceName(option.textContent) === expected;
            });
            if (!matched) {
                return false;
            }
            if (citySelect.value !== matched.value) {
                citySelect.value = matched.value;
                citySelect.dispatchEvent(new Event('change', { bubbles: true }));
                citySelect.dispatchEvent(new Event('input', { bubbles: true }));
            }
            return true;
        }

        function fillAddress(address) {
            if (address && address.city) {
                selectCity(address.city);
            }
            [
                ['bml_address', 'address'],
                ['bml_region', 'region'],
                ['bml_country', 'country'],
                ['bml_postcode', 'postcode']
            ].forEach(function (pair) {
                var field = document.getElementById(pair[0]);
                if (field && address[pair[1]]) {
                    field.value = address[pair[1]];
                    dispatchInput(field);
                }
            });
        }

        function reverseLookup(lat, lng) {
            setState('loading', BMLAdmin.strings.searching || 'Searching…', '');
            api('geocode/reverse?lat=' + encodeURIComponent(lat) + '&lng=' + encodeURIComponent(lng))
                .then(function (data) {
                    fillAddress(data.address || {});
                    setState(
                        'success',
                        BMLAdmin.strings.addressFound || 'Address found',
                        data.display_name || BMLAdmin.strings.reverseNotice
                    );
                })
                .catch(function () {
                    setState('error', BMLAdmin.strings.geocodeError, '');
                });
        }

        function buildSearchPath(query) {
            var city = selectedText('select[name="city_id"]');
            var regionField = document.getElementById('bml_region');
            var countryField = document.getElementById('bml_country');
            var params = new URLSearchParams();

            params.set('q', query);
            if (city) {
                params.set('city', city);
            }
            if (regionField && regionField.value.trim()) {
                params.set('region', regionField.value.trim());
            }
            if (countryField && countryField.value.trim()) {
                params.set('country', countryField.value.trim());
            }

            return 'geocode/search?' + params.toString();
        }

        function performSearch() {
            var query = searchInput ? searchInput.value.trim() : '';
            if (query.length < 3) {
                setState('error', BMLAdmin.strings.geocodeError, 'Enter at least three characters.');
                return;
            }

            findButton.disabled = true;
            resultsBox.innerHTML = '';
            setState('loading', BMLAdmin.strings.searching || 'Searching…', '');

            api(buildSearchPath(query))
                .then(function (items) {
                    if (!Array.isArray(items) || !items.length) {
                        setState(
                            'error',
                            BMLAdmin.strings.geocodeError,
                            'Try entering the street, house number and selecting the city.'
                        );
                        return;
                    }

                    items.forEach(function (item) {
                        var button = document.createElement('button');
                        button.type = 'button';
                        button.className = 'bml-geocode-result';
                        button.textContent = item.display_name;
                        button.addEventListener('click', function () {
                            var point = L.latLng(item.lat, item.lng);
                            map.setView(point, item.type === 'coordinates' ? 17 : 16);
                            setPoint(point, false);
                            var resolvedAddress = Object.assign({}, item.address || {});
                            var houseNumber = houseNumberFromQuery(searchInput.value);
                            if (resolvedAddress.address && houseNumber && !new RegExp('(?:^|[\s,])' + houseNumber.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + '(?:$|[\s,])', 'i').test(resolvedAddress.address)) {
                                resolvedAddress.address = resolvedAddress.address.replace(/[\s,]+$/, '') + ', ' + houseNumber;
                            }
                            fillAddress(resolvedAddress);
                            resultsBox.innerHTML = '';
                            setState(
                                'success',
                                BMLAdmin.strings.addressFound || 'Address found',
                                item.display_name
                            );
                        });
                        resultsBox.appendChild(button);
                    });

                    setState(
                        'success',
                        BMLAdmin.strings.addressFound || 'Address results found',
                        'Select the correct address from the list.'
                    );
                })
                .catch(function (error) {
                    var message = error && error.message ? error.message : '';
                    setState('error', BMLAdmin.strings.geocodeError, message);
                })
                .finally(function () {
                    findButton.disabled = false;
                });
        }

        map.on('click', function (event) {
            setPoint(event.latlng, true);
        });

        marker.on('dragend', function () {
            setPoint(marker.getLatLng(), true);
        });

        if (findButton && searchInput && resultsBox) {
            findButton.addEventListener('click', performSearch);
            searchInput.addEventListener('keydown', function (event) {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    event.stopPropagation();
                    performSearch();
                }
            });
        }

        document.addEventListener('bml:workspace-map-visible', function () {
            map.invalidateSize();
        });

        setTimeout(function () {
            map.invalidateSize();
        }, 250);
    });
}());
