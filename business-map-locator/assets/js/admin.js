(function () {
    'use strict';

    function ready(fn) {
        if (document.readyState !== 'loading') {
            fn();
        } else {
            document.addEventListener('DOMContentLoaded', fn);
        }
    }

    function api(path) {
        return window.wp.apiFetch({
            path: '/business-map/v1/' + path,
            headers: { 'X-WP-Nonce': BMLAdmin.nonce }
        });
    }

    ready(function () {
        initAdminMap();
        initSettingsMap();
        initMediaUploader();
        initCategoryIconUploader();
        initShortcode();
        initTables();
        initTabs();
        initLayoutOptions();
        initProviderCards();
        initGoogleSetup();
        initSettingsDirtyState();
        initCopyCodeButtons();
        initProductionPreview();
        initDeleteConfirmation();
    });

    function initAdminMap() {
        if (document.querySelector('.bml-location-workspace')) { return; }
        var mapEl = document.getElementById('bml-admin-map');
        if (!mapEl || !window.L) {
            return;
        }

        var latInput = document.getElementById('bml_lat');
        var lngInput = document.getElementById('bml_lng');
        var lat = parseFloat(latInput.value || BMLAdmin.settings.center_lat);
        var lng = parseFloat(lngInput.value || BMLAdmin.settings.center_lng);
        var map = L.map(mapEl).setView([lat, lng], parseInt(BMLAdmin.settings.zoom, 10));

        L.tileLayer(BMLAdmin.settings.tile_url, {
            maxZoom: 20,
            attribution: BMLAdmin.settings.attribution
        }).addTo(map);

        var marker = L.marker([lat, lng], { draggable: true }).addTo(map);

        function setPoint(point, reverse) {
            marker.setLatLng(point);
            latInput.value = Number(point.lat).toFixed(7);
            lngInput.value = Number(point.lng).toFixed(7);
            if (reverse) {
                reverseLookup(point.lat, point.lng);
            }
        }

        map.on('click', function (event) {
            setPoint(event.latlng, true);
        });

        marker.on('dragend', function () {
            setPoint(marker.getLatLng(), true);
        });

        function reverseLookup(latValue, lngValue) {
            api('geocode/reverse?lat=' + encodeURIComponent(latValue) + '&lng=' + encodeURIComponent(lngValue))
                .then(function (data) {
                    fillAddress(data.address || {});
                    var note = document.getElementById('bml-auto-address-note');
                    if (note) {
                        note.hidden = false;
                        note.querySelector('p').textContent = BMLAdmin.strings.reverseNotice;
                    }
                })
                .catch(function () {});
        }

        function fillAddress(address) {
            [
                ['bml_address', 'address'],
                ['bml_region', 'region'],
                ['bml_country', 'country'],
                ['bml_postcode', 'postcode']
            ].forEach(function (pair) {
                var field = document.getElementById(pair[0]);
                if (field && address[pair[1]]) {
                    field.value = address[pair[1]];
                    field.dispatchEvent(new Event('input'));
                }
            });
        }

        var findButton = document.getElementById('bml-find-address');
        if (findButton) {
            findButton.addEventListener('click', function () {
                var query = document.getElementById('bml-address-search').value.trim();
                if (query.length < 3) {
                    return;
                }

                findButton.disabled = true;
                api('geocode/search?q=' + encodeURIComponent(query))
                    .then(function (items) {
                        var box = document.getElementById('bml-geocode-results');
                        box.innerHTML = '';
                        if (!items.length) {
                            box.textContent = BMLAdmin.strings.geocodeError;
                            return;
                        }

                        items.forEach(function (item) {
                            var button = document.createElement('button');
                            button.type = 'button';
                            button.className = 'bml-geocode-result';
                            button.textContent = item.display_name;
                            button.addEventListener('click', function () {
                                var point = L.latLng(item.lat, item.lng);
                                map.setView(point, 16);
                                setPoint(point, false);
                                fillAddress(item.address || {});
                                box.innerHTML = '';
                            });
                            box.appendChild(button);
                        });
                    })
                    .catch(function () {
                        window.alert(BMLAdmin.strings.geocodeError);
                    })
                    .finally(function () {
                        findButton.disabled = false;
                    });
            });
        }

        var title = document.getElementById('bml_title');
        var previewTitle = document.getElementById('bml-preview-title');
        if (title && previewTitle) {
            title.addEventListener('input', function () {
                previewTitle.textContent = title.value || 'Business name';
            });
        }

        var addressField = document.getElementById('bml_address');
        var previewAddress = document.getElementById('bml-preview-address');
        if (addressField && previewAddress) {
            addressField.addEventListener('input', function () {
                previewAddress.textContent = addressField.value || 'Address will appear here';
            });
        }

        setTimeout(function () {
            map.invalidateSize();
        }, 250);
    }

    function initSettingsMap() {
        if (document.querySelector('[data-bml-settings-studio]')) {
            return;
        }

        var settingsMap = document.getElementById('bml-settings-map');
        if (!settingsMap || !window.L) {
            return;
        }

        var latInput = document.getElementById('bml-center-lat') || document.getElementById('bml-settings-lat');
        var lngInput = document.getElementById('bml-center-lng') || document.getElementById('bml-settings-lng');
        var zoomInput = document.getElementById('bml-map-zoom') || document.getElementById('bml-settings-zoom');
        var coordinatesOutput = document.getElementById('bml-preview-coordinates');
        var zoomOutput = document.getElementById('bml-preview-zoom');
        var useCurrentButton = document.getElementById('bml-use-current-view');
        var initialLat = parseFloat((latInput && latInput.value) || BMLAdmin.settings.center_lat);
        var initialLng = parseFloat((lngInput && lngInput.value) || BMLAdmin.settings.center_lng);
        var initialZoom = parseInt((zoomInput && zoomInput.value) || BMLAdmin.settings.zoom, 10);
        var map = L.map(settingsMap).setView([initialLat, initialLng], initialZoom);
        var marker = L.marker([initialLat, initialLng], { draggable: true, opacity: 0 }).addTo(map);

        L.tileLayer(BMLAdmin.settings.tile_url, {
            maxZoom: 20,
            attribution: BMLAdmin.settings.attribution
        }).addTo(map);

        function updateOutputs(center, zoom, updateFields) {
            var lat = Number(center.lat).toFixed(6);
            var lng = Number(center.lng).toFixed(6);
            if (coordinatesOutput) { coordinatesOutput.textContent = lat + ', ' + lng; }
            if (zoomOutput) { zoomOutput.textContent = String(zoom); }
            if (updateFields) {
                if (latInput) { latInput.value = lat; latInput.dispatchEvent(new Event('change', { bubbles: true })); }
                if (lngInput) { lngInput.value = lng; lngInput.dispatchEvent(new Event('change', { bubbles: true })); }
                if (zoomInput) { zoomInput.value = zoom; zoomInput.dispatchEvent(new Event('change', { bubbles: true })); }
            }
        }

        map.on('move zoom', function () {
            updateOutputs(map.getCenter(), map.getZoom(), false);
        });
        map.on('moveend zoomend', function () {
            marker.setLatLng(map.getCenter());
        });
        marker.on('dragend', function () {
            map.panTo(marker.getLatLng());
            updateOutputs(marker.getLatLng(), map.getZoom(), true);
        });
        map.on('click', function (event) {
            marker.setLatLng(event.latlng);
            map.panTo(event.latlng);
            updateOutputs(event.latlng, map.getZoom(), true);
        });

        if (useCurrentButton) {
            useCurrentButton.addEventListener('click', function () {
                updateOutputs(map.getCenter(), map.getZoom(), true);
                useCurrentButton.textContent = 'View saved to fields';
                setTimeout(function () { useCurrentButton.textContent = 'Use current view'; }, 1500);
            });
        }

        [latInput, lngInput, zoomInput].forEach(function (field) {
            if (!field) { return; }
            field.addEventListener('change', function () {
                var lat = parseFloat(latInput.value);
                var lng = parseFloat(lngInput.value);
                var zoom = parseInt(zoomInput.value, 10);
                if (!Number.isFinite(lat) || !Number.isFinite(lng) || !Number.isFinite(zoom)) { return; }
                map.setView([lat, lng], zoom);
                marker.setLatLng([lat, lng]);
                updateOutputs(map.getCenter(), map.getZoom(), false);
            });
        });

        setTimeout(function () { map.invalidateSize(); }, 200);
    }

    function initGoogleSetup() {
        if (document.querySelector('[data-bml-settings-studio]')) {
            return;
        }

        var keyInput = document.getElementById('bml-google-key');
        var verifyButton = document.getElementById('bml-verify-google-key') || document.getElementById('bml-verify-google');
        var toggleButton = document.getElementById('bml-toggle-google-key') || document.getElementById('bml-toggle-key');
        if (!keyInput) { return; }

        function setCheck(name, state, message) {
            var item = document.querySelector('#bml-google-checks [data-check="' + name + '"]');
            if (!item && name === 'geocoder') {
                item = document.querySelector('#bml-google-checks [data-check="geocoding"]');
            }
            if (!item) { return; }
            item.classList.remove('is-success', 'is-error', 'is-loading');
            item.classList.add('is-' + state);
            var icon = item.querySelector('.dashicons');
            var text = item.querySelector('small');
            if (icon) {
                icon.className = 'dashicons ' + (state === 'success' ? 'dashicons-yes-alt' : state === 'error' ? 'dashicons-dismiss' : 'dashicons-update');
            }
            if (text) {
                text.textContent = message;
            } else {
                item.textContent = (state === 'success' ? '✓ ' : state === 'error' ? '× ' : '… ') + message;
            }
        }

        function updateSummary(ok, text) {
            var status = document.getElementById('bml-google-summary-status');
            var health = document.getElementById('bml-health-google');
            var legacyResult = document.getElementById('bml-google-result');
            if (status) {
                status.textContent = text;
                status.className = 'bml-status-pill ' + (ok ? 'is-success' : 'is-error');
            }
            if (health) {
                health.textContent = ok ? 'Verified' : 'Check failed';
                health.className = 'bml-badge ' + (ok ? 'bml-badge--success' : 'bml-badge--warning');
            }
            if (legacyResult) {
                legacyResult.textContent = text;
            }
        }

        if (toggleButton) {
            toggleButton.addEventListener('click', function () {
                var show = keyInput.type === 'password';
                keyInput.type = show ? 'text' : 'password';
                toggleButton.textContent = show ? 'Hide' : 'Show';
            });
        }

        function resetVerifyButton() {
            verifyButton.disabled = false;
            verifyButton.textContent = verifyButton.dataset.defaultText || 'Verify key';
        }

        if (!verifyButton) { return; }
        verifyButton.dataset.defaultText = verifyButton.textContent || 'Verify key';
        verifyButton.addEventListener('click', function () {
            var key = keyInput.value.trim();
            var validFormat = /^AIza[0-9A-Za-z_-]{20,}$/.test(key);
            setCheck('format', validFormat ? 'success' : 'error', validFormat ? 'Key format looks valid' : 'Enter a valid Google browser API key');
            if (!validFormat) { updateSummary(false, 'Invalid key format'); return; }

            verifyButton.disabled = true;
            verifyButton.textContent = 'Verifying…';
            setCheck('maps', 'loading', 'Loading Maps JavaScript API…');
            setCheck('geocoder', 'loading', 'Waiting for Maps API…');
            setCheck('domain', 'loading', 'Testing this website…');

            var oldScript = document.getElementById('bml-google-verification-script');
            if (oldScript) { oldScript.remove(); }
            window.bmlGoogleVerifyCallback = function () {
                setCheck('maps', 'success', 'Maps JavaScript API loaded');
                setCheck('domain', 'success', 'Current website is allowed');
                try {
                    var geocoder = new google.maps.Geocoder();
                    geocoder.geocode({ location: { lat: parseFloat(BMLAdmin.settings.center_lat), lng: parseFloat(BMLAdmin.settings.center_lng) } }, function (results, status) {
                        if (status === 'OK' || status === 'ZERO_RESULTS') {
                            setCheck('geocoder', 'success', 'Geocoding API responded');
                            updateSummary(true, 'Google Maps connected');
                        } else {
                            setCheck('geocoder', 'error', 'Geocoding returned: ' + status);
                            updateSummary(false, 'Maps works, geocoding needs attention');
                        }
                        resetVerifyButton();
                    });
                } catch (error) {
                    setCheck('geocoder', 'error', 'Geocoding test failed');
                    updateSummary(false, 'Maps works, geocoding needs attention');
                    resetVerifyButton();
                }
            };
            window.gm_authFailure = function () {
                setCheck('maps', 'error', 'Google rejected the key');
                setCheck('domain', 'error', 'Check API restrictions and billing');
                setCheck('geocoder', 'error', 'Not tested');
                updateSummary(false, 'Google rejected the key');
                resetVerifyButton();
            };
            var script = document.createElement('script');
            script.id = 'bml-google-verification-script';
            script.async = true;
            script.defer = true;
            script.onerror = function () {
                setCheck('maps', 'error', 'Could not load Google Maps');
                setCheck('domain', 'error', 'Check key restrictions or network access');
                setCheck('geocoder', 'error', 'Not tested');
                updateSummary(false, 'Connection failed');
                resetVerifyButton();
            };
            script.src = 'https://maps.googleapis.com/maps/api/js?key=' + encodeURIComponent(key) + '&callback=bmlGoogleVerifyCallback&loading=async';
            document.head.appendChild(script);
        });
    }

    function initSettingsDirtyState() {
        document.querySelectorAll('.bml-settings-form').forEach(function (form) {
            var strong = form.querySelector('.bml-savebar strong');
            var indicator = form.querySelector('.bml-unsaved-indicator');
            var initial = new FormData(form);
            var baseline = Array.from(initial.entries()).map(function (item) { return item.join('='); }).join('&');
            function update() {
                var current = Array.from(new FormData(form).entries()).map(function (item) { return item.join('='); }).join('&');
                var dirty = current !== baseline;
                form.classList.toggle('is-dirty', dirty);
                if (indicator) { indicator.classList.toggle('is-dirty', dirty); }
                if (strong) { strong.textContent = dirty ? 'Unsaved changes' : strong.dataset.defaultText || strong.textContent; }
            }
            if (strong) { strong.dataset.defaultText = strong.textContent; }
            form.addEventListener('input', update);
            form.addEventListener('change', update);
        });
    }

    function initCopyCodeButtons() {
        document.querySelectorAll('.bml-copy-code').forEach(function (button) {
            button.addEventListener('click', function () {
                var code = button.parentElement.querySelector('code');
                if (!code) { return; }
                navigator.clipboard.writeText(code.textContent);
                var original = button.textContent;
                button.textContent = 'Copied';
                setTimeout(function () { button.textContent = original; }, 1400);
            });
        });
    }

    function initMediaUploader() {
        if (document.querySelector('.bml-location-workspace')) { return; }
        var selectButton = document.getElementById('bml-select-image');
        var removeButton = document.getElementById('bml-remove-image');
        var input = document.getElementById('bml_image_id');
        var preview = document.getElementById('bml-image-preview');

        if (!selectButton || !input || !preview || !window.wp || !wp.media) {
            return;
        }

        var frame;
        selectButton.addEventListener('click', function () {
            if (frame) {
                frame.open();
                return;
            }

            frame = wp.media({
                title: BMLAdmin.strings.selectImage,
                button: { text: BMLAdmin.strings.useImage },
                multiple: false,
                library: { type: 'image' }
            });

            frame.on('select', function () {
                var attachment = frame.state().get('selection').first().toJSON();
                input.value = attachment.id;
                preview.innerHTML = '<img src="' + (attachment.sizes.medium ? attachment.sizes.medium.url : attachment.url) + '" alt="">';
            });

            frame.open();
        });

        if (removeButton) {
            removeButton.addEventListener('click', function () {
                input.value = '';
                preview.innerHTML = '<span class="dashicons dashicons-format-image"></span><p>No image selected</p>';
            });
        }
    }

    function initCategoryIconUploader() {
        var selectButton = document.getElementById('bml-select-category-icon');
        var removeButton = document.getElementById('bml-remove-category-icon');
        var input = document.getElementById('bml_category_icon_id');
        var preview = document.getElementById('bml-category-icon-preview');

        if (!selectButton || !input || !preview || !window.wp || !wp.media) {
            return;
        }

        var frame;
        selectButton.addEventListener('click', function () {
            if (frame) {
                frame.open();
                return;
            }

            frame = wp.media({
                title: BMLAdmin.strings.selectCategoryIcon || 'Select category icon',
                button: { text: BMLAdmin.strings.useCategoryIcon || 'Use this icon' },
                multiple: false,
                library: { type: 'image' }
            });

            frame.on('select', function () {
                var attachment = frame.state().get('selection').first().toJSON();
                var sizes = attachment.sizes || {};
                var image = sizes.bml_category_icon || sizes.thumbnail || sizes.medium || {};
                input.value = attachment.id;
                preview.innerHTML = '<img src="' + (image.url || attachment.url) + '" alt="">';
            });

            frame.open();
        });

        if (removeButton) {
            removeButton.addEventListener('click', function () {
                input.value = '';
                preview.innerHTML = '<span class="dashicons dashicons-format-image"></span>';
            });
        }
    }

    function initShortcode() {
        var output = document.getElementById('bml-shortcode-output');
        if (!output) {
            return;
        }

        function build() {
            var layout = document.getElementById('bml-sc-layout').value;
            var category = document.getElementById('bml-sc-category').value.trim();
            var city = document.getElementById('bml-sc-city').value.trim();
            var height = document.getElementById('bml-sc-height').value || 620;
            output.textContent = '[business_map_locator layout="' + layout + '" height="' + height + '"' +
                (category ? ' category="' + category + '"' : '') +
                (city ? ' city="' + city + '"' : '') + ']';
        }

        ['bml-sc-layout', 'bml-sc-category', 'bml-sc-city', 'bml-sc-height'].forEach(function (id) {
            var field = document.getElementById(id);
            if (field) {
                field.addEventListener('input', build);
            }
        });

        var copyButton = document.getElementById('bml-copy-shortcode');
        if (copyButton) {
            copyButton.addEventListener('click', function () {
                navigator.clipboard.writeText(output.textContent);
                copyButton.textContent = 'Copied';
                setTimeout(function () { copyButton.textContent = 'Copy'; }, 1500);
            });
        }

        build();
    }

    function initTables() {
        var checkAll = document.querySelector('.bml-check-all');
        if (checkAll) {
            checkAll.addEventListener('change', function () {
                document.querySelectorAll('input[name="ids[]"]').forEach(function (checkbox) {
                    checkbox.checked = checkAll.checked;
                });
            });
        }
    }

    function initTabs() {
        document.querySelectorAll('.bml-tabs button').forEach(function (button) {
            button.addEventListener('click', function () {
                button.parentElement.querySelectorAll('button').forEach(function (item) {
                    item.classList.remove('is-active');
                });
                button.classList.add('is-active');
            });
        });
    }

    function initLayoutOptions() {
        document.querySelectorAll('.bml-layout-options label').forEach(function (label) {
            label.addEventListener('click', function () {
                label.parentElement.querySelectorAll('label').forEach(function (item) {
                    item.classList.remove('is-selected');
                });
                label.classList.add('is-selected');
            });
        });
    }

    function initProviderCards() {
        document.querySelectorAll('.bml-provider-card, .bml-provider-choice label').forEach(function (card) {
            card.addEventListener('click', function () {
                card.parentElement.querySelectorAll('.bml-provider-card, .bml-provider-choice label').forEach(function (item) {
                    item.classList.remove('is-selected');
                });
                card.classList.add('is-selected');
                var input = card.querySelector('input[type="radio"]');
                var googleSetup = document.getElementById('bml-google-setup');
                var providerLabel = document.getElementById('bml-live-provider-label');
                var healthProvider = document.getElementById('bml-health-provider');
                if (input && googleSetup) { googleSetup.classList.toggle('is-visible', input.value === 'google'); }
                if (input && providerLabel) { providerLabel.textContent = input.value === 'google' ? 'Google Maps' : 'OpenStreetMap'; }
                if (input && healthProvider) { healthProvider.textContent = input.value === 'google' ? 'Google Maps' : 'OpenStreetMap'; }
            });
        });
    }

    function initDeleteConfirmation() {
        document.querySelectorAll('.bml-delete-link').forEach(function (link) {
            link.addEventListener('click', function (event) {
                if (!window.confirm(BMLAdmin.strings.confirmDelete)) {
                    event.preventDefault();
                }
            });
        });
    }
}());

    function initProductionPreview() {
        var form = document.querySelector('.bml-design-workspace');
        if (!form) { return; }
        var locator = form.querySelector('.bml-locator');
        var stage = form.querySelector('.bml-preview-stage');
        if (!locator || !stage) { return; }

        function update() {
            var layout = form.querySelector('input[name="layout"]:checked');
            locator.classList.remove('bml-layout-split', 'bml-layout-map', 'bml-layout-cards');
            locator.classList.add('bml-layout-' + (layout ? layout.value : 'split'));
            var height = form.querySelector('input[name="map_height"]');
            var width = form.querySelector('input[name="list_width"]');
            locator.style.setProperty('--bml-map-height', (height ? height.value : 620) + 'px');
            locator.style.setProperty('--bml-list-width', (width ? width.value : 38) + '%');
            var hOut = document.getElementById('bml-height-output');
            var wOut = document.getElementById('bml-width-output');
            if (hOut && height) { hOut.value = height.value + 'px'; hOut.textContent = height.value + 'px'; }
            if (wOut && width) { wOut.value = width.value + '%'; wOut.textContent = width.value + '%'; }
            var search = form.querySelector('input[name="show_search"]');
            var filters = form.querySelector('input[name="show_filters"]');
            var geo = form.querySelector('input[name="show_geolocation"]');
            var searchEl = locator.querySelector('.bml-search');
            var cats = locator.querySelector('.bml-category-filter');
            var cities = locator.querySelector('.bml-city-filter');
            var near = locator.querySelector('.bml-near-me');
            var showAddress = form.querySelector('input[name="show_address"]');
            var showPhone = form.querySelector('input[name="show_phone"]');
            var showNavigation = form.querySelector('input[name="show_navigation"]');
            locator.querySelectorAll('[data-bml-popup="address"], .bml-location-card p').forEach(function (item) { item.hidden = !!(showAddress && !showAddress.checked); });
            locator.querySelectorAll('.bml-preview-contact-icon--phone').forEach(function (item) { item.hidden = !!(showPhone && !showPhone.checked); });
            locator.querySelectorAll('.bml-preview-contact-icon--navigation').forEach(function (item) { item.hidden = !!(showNavigation && !showNavigation.checked); });
            if (searchEl) { searchEl.hidden = search && !search.checked; }
            if (cats) { cats.hidden = filters && !filters.checked; }
            if (cities) { cities.hidden = filters && !filters.checked; }
            if (near) { near.hidden = geo && !geo.checked; }
            var toolbar = locator.querySelector('.bml-toolbar');
            if (toolbar) { toolbar.hidden = !!((search && !search.checked) && (filters && !filters.checked) && (geo && !geo.checked)); }
        }
        form.addEventListener('input', update);
        form.addEventListener('change', update);
        form.querySelectorAll('[data-device]').forEach(function (button) {
            button.addEventListener('click', function () {
                form.querySelectorAll('[data-device]').forEach(function (item) { item.classList.remove('is-active'); });
                button.classList.add('is-active');
                stage.dataset.device = button.dataset.device;
                window.setTimeout(function () { window.dispatchEvent(new Event('resize')); }, 80);
            });
        });
        update();
    }



(function () {
    'use strict';
    document.addEventListener('DOMContentLoaded', function () {
        var button = document.querySelector('[data-bml-sidebar-toggle]');
        var shell = document.querySelector('.bml-admin-shell');
        var storageKey = 'bmlSidebarCollapsedV2';
        var stored;
        var page;
        var defaultCollapsed;
        if (!button || !shell) { return; }
        stored = window.localStorage.getItem(storageKey);
        page = shell.dataset.bmlPage || '';
        defaultCollapsed = ['bml-locations', 'bml-categories', 'bml-cities', 'bml-import'].indexOf(page) !== -1;
        if (stored === '1' || (stored === null && defaultCollapsed)) {
            shell.classList.add('is-sidebar-collapsed');
        }
        button.addEventListener('click', function () {
            shell.classList.toggle('is-sidebar-collapsed');
            window.localStorage.setItem(storageKey, shell.classList.contains('is-sidebar-collapsed') ? '1' : '0');
        });
    });
}());
