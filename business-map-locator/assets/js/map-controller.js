(function (window, document) {
    'use strict';

    function escapeHtml(value) {
        var div = document.createElement('div');
        div.textContent = value == null ? '' : String(value);
        return div.innerHTML;
    }

    function debounce(callback, delay) {
        var timer = null;
        return function () {
            var context = this;
            var args = arguments;
            window.clearTimeout(timer);
            timer = window.setTimeout(function () {
                callback.apply(context, args);
            }, delay);
        };
    }

    function distanceKm(lat1, lng1, lat2, lng2) {
        var radius = 6371;
        var toRadians = Math.PI / 180;
        var deltaLat = (lat2 - lat1) * toRadians;
        var deltaLng = (lng2 - lng1) * toRadians;
        var value = Math.sin(deltaLat / 2) ** 2 +
            Math.cos(lat1 * toRadians) * Math.cos(lat2 * toRadians) *
            Math.sin(deltaLng / 2) ** 2;
        return radius * 2 * Math.atan2(Math.sqrt(value), Math.sqrt(1 - value));
    }

    function formatDistance(distance, settings, isServerDistance) {
        if (isServerDistance) {
            return Number(distance).toFixed(1) + ' ' + (settings.distance_unit === 'mi' ? 'mi' : 'km');
        }
        if (settings.distance_unit === 'mi') {
            return (distance * 0.621371).toFixed(1) + ' mi';
        }
        return distance.toFixed(1) + ' km';
    }

    function fetchJson(url, signal) {
        return window.fetch(url, { signal: signal }).then(function (response) {
            if (!response.ok) {
                throw new Error('HTTP ' + response.status);
            }
            return response.json();
        });
    }

    function filterMode(root, dimension) {
        var mode = (root.dataset[dimension + 'Mode'] || 'visible').toLowerCase();
        return ['visible', 'locked', 'hidden'].indexOf(mode) !== -1 ? mode : 'visible';
    }

    function effectiveFilterValue(root, selector, dimension) {
        var control = root.querySelector(selector);
        if (filterMode(root, dimension) !== 'visible') {
            return root.dataset[dimension] || '';
        }
        return control ? control.value : '';
    }

    function appendNearParams(params, settings, origin) {
        if (!origin) { return; }
        params.set('lat', String(origin.lat));
        params.set('lng', String(origin.lng));
        params.set('radius', String(Math.max(1, Math.min(500, parseFloat(settings.radius) || 25))));
        params.set('unit', settings.distance_unit === 'mi' ? 'mi' : 'km');
        params.set('orderby', 'distance');
    }

    function safePerPage(value) {
        return Math.max(12, Math.min(500, parseInt(value, 10) || 24));
    }

    function createParams(root, settings, page, origin, perPage) {
        var params = new URLSearchParams();
        var searchInput = root.querySelector('.bml-search-input');
        var search = searchInput ? searchInput.value.trim() : '';
        var category = effectiveFilterValue(root, '.bml-category-filter', 'category');
        var city = effectiveFilterValue(root, '.bml-city-filter', 'city');

        params.set('page', String(page || 1));
        params.set('per_page', String(safePerPage(perPage || settings.per_page)));
        var sortControl = root.querySelector('.bml-sort-filter');
        var sortValue = sortControl ? sortControl.value : 'default';
        params.set('orderby', origin ? 'distance' : (sortValue === 'default' ? 'menu_order' : 'title'));
        params.set('order', origin || sortValue !== 'title-desc' ? 'ASC' : 'DESC');

        if (search) { params.set('search', search); }
        if (category) { params.set('category', category); }
        if (city) { params.set('city', city); }
        appendNearParams(params, settings, origin);

        return params;
    }

    function createFilterParams(root) {
        var params = new URLSearchParams();
        var category = effectiveFilterValue(root, '.bml-category-filter', 'category');
        var city = effectiveFilterValue(root, '.bml-city-filter', 'city');

        if (category) { params.set('category', category); }
        if (city) { params.set('city', city); }

        return params;
    }

    /* Popup */
    function PopupController(root, strings, settings) {
        this.root = root;
        this.strings = strings || {};
        this.settings = settings || {};
        this.template = root ? root.querySelector('.bml-popup-template') : null;
    }

    function popupIcon(name) {
        var icons = {
            phone: '<svg viewBox="0 0 24 24"><path d="M7.2 4.8c.4-.4 1-.4 1.4 0l2 2c.4.4.4 1 0 1.4L9.3 9.5c1.2 2.4 2.8 4 5.2 5.2l1.3-1.3c.4-.4 1-.4 1.4 0l2 2c.4.4.4 1 0 1.4l-1.2 1.2c-.8.8-2 1.1-3 .7-4.9-1.7-8.8-5.6-10.5-10.5-.4-1 0-2.2.7-3L7.2 4.8Z" fill="currentColor"/></svg>',
            email: '<svg viewBox="0 0 24 24"><rect x="3.5" y="5.5" width="17" height="13" rx="2" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="m4.5 7 7.5 5.5L19.5 7" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>',
            website: '<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="8.5" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M3.8 12h16.4M12 3.5c2.1 2.3 3.2 5.1 3.2 8.5S14.1 18.2 12 20.5M12 3.5C9.9 5.8 8.8 8.6 8.8 12s1.1 6.2 3.2 8.5" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>',
            navigation: '<svg viewBox="0 0 24 24"><path d="M12 3.3 20.7 12 12 20.7 3.3 12 12 3.3Z" fill="currentColor"/><path d="M9 14.8v-2.2c0-.9.7-1.6 1.6-1.6h3.2" fill="none" stroke="#fff" stroke-width="1.7" stroke-linecap="round"/><path d="m12.7 8.8 2.3 2.2-2.3 2.2" fill="none" stroke="#fff" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>',
            whatsapp: '<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9" fill="currentColor"/><path d="M8.2 7.7c.3-.6.7-.6 1-.6h.4c.2 0 .4.1.5.4l.9 2c.1.3.1.5-.1.7l-.7.9c-.2.2-.1.4 0 .6.7 1.3 1.8 2.3 3.1 3 .3.2.5.2.7 0l.9-1.1c.2-.2.4-.3.7-.2l2 .9c.3.1.4.3.4.5 0 .5-.2 1.6-1 2.2-.7.6-1.7.9-2.8.6-1.5-.4-3.5-1.2-5.2-3-1.4-1.5-2.3-3.3-2.5-4.7-.2-1 .1-1.7.7-2.2Z" fill="#fff"/></svg>',
            telegram: '<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9" fill="currentColor"/><path d="m6.8 11.7 9.8-3.8c.5-.2.9.1.7.8l-1.7 7.8c-.1.6-.5.7-1 .4l-2.6-1.9-1.2 1.2c-.2.2-.3.3-.6.3l.2-2.7 5-4.5c.2-.2-.1-.3-.4-.1l-6.2 3.9-2.6-.8c-.6-.2-.6-.5.1-.8Z" fill="#fff"/></svg>',
            viber: '<svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="6" fill="currentColor"/><path d="M8 7.4c.5-.5 1.2-.6 1.8-.2 1.4.8 3.3 2.7 4.1 4.1.4.7.3 1.4-.2 1.8l-.8.7c-.2.2-.2.4-.1.6.4.7 1 1.3 1.7 1.7.2.1.4.1.6-.1l.7-.8c.5-.5 1.2-.6 1.8-.2l.7.5c.5.4.6 1.1.2 1.6-.7.9-1.7 1.3-2.9 1.1-2.4-.5-5.6-3.6-6.1-6.1-.2-1.2.2-2.2 1.1-2.9Z" fill="#fff" transform="translate(-2 -1) scale(.95)"/></svg>',
            facebook: '<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9" fill="currentColor"/><path d="M13.4 18v-5h1.8l.3-2h-2.1V9.7c0-.6.2-1 1-1h1.2V6.9c-.2 0-.9-.1-1.7-.1-1.7 0-2.8 1-2.8 2.9V11H9.2v2H11v5h2.4Z" fill="#fff"/></svg>',
            instagram: '<svg viewBox="0 0 24 24"><rect x="4" y="4" width="16" height="16" rx="5" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="12" r="3.5" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="17.3" cy="6.8" r="1" fill="currentColor"/></svg>',
            linkedin: '<svg viewBox="0 0 24 24"><rect x="4" y="4" width="16" height="16" rx="2" fill="currentColor"/><circle cx="8.2" cy="9" r="1.2" fill="#fff"/><path d="M7.2 11h2v6h-2zm3.6 0h1.9v.8c.5-.7 1.2-1 2.1-1 2 0 2.4 1.4 2.4 3.2v3h-2v-2.7c0-.7 0-1.7-1.1-1.7s-1.3.8-1.3 1.7V17h-2v-6Z" fill="#fff"/></svg>',
            tiktok: '<svg viewBox="0 0 24 24"><path d="M14.2 5.2c.4 1.9 1.5 3 3.3 3.3v2.2c-1.2 0-2.3-.4-3.3-1.1v4.3c0 2.7-1.8 4.6-4.4 4.6-2.5 0-4.3-1.8-4.3-4.2 0-2.7 2.2-4.7 5-4.2v2.3c-1.4-.4-2.7.4-2.7 1.8 0 1.1.8 1.9 1.9 1.9 1.3 0 2.1-.8 2.1-2.3V5.2h2.4Z" fill="currentColor"/></svg>'
        };
        return icons[name] || '';
    }

    function popupContactUrl(name, value) {
        var clean = String(value || '').trim();
        var digits;
        if (!clean) { return ''; }
        if (name === 'whatsapp') {
            digits = clean.replace(/\D/g, '');
            return digits ? 'https://wa.me/' + digits : '';
        }
        if (name === 'telegram') {
            clean = clean.replace(/^https?:\/\/(www\.)?t\.me\//i, '').replace(/^@+/, '');
            return clean ? 'https://t.me/' + clean : '';
        }
        if (name === 'viber') {
            digits = clean.replace(/\D/g, '');
            return digits ? 'viber://chat?number=%2B' + digits : '';
        }
        return /^https?:\/\//i.test(clean) ? clean : 'https://' + clean;
    }

    function automaticNavigationUrl(lat, lng) {
        var latitude = encodeURIComponent(String(lat));
        var longitude = encodeURIComponent(String(lng));
        var point = latitude + ',' + longitude;
        var ua = navigator.userAgent || '';
        var platform = navigator.platform || '';
        var isApple = /iPad|iPhone|iPod/.test(ua) || (platform === 'MacIntel' && navigator.maxTouchPoints > 1);
        var isAndroid = /Android/i.test(ua);
        var provider = window.BMLFrontend && window.BMLFrontend.provider
            ? String(window.BMLFrontend.provider.active || '').toLowerCase()
            : 'osm';

        if (isApple) {
            return 'https://maps.apple.com/?daddr=' + point + '&dirflg=d';
        }
        if (isAndroid) {
            return 'geo:' + point + '?q=' + point;
        }
        if (provider === 'google') {
            return 'https://www.google.com/maps/dir/?api=1&destination=' + point;
        }
        return 'https://www.openstreetmap.org/directions?engine=fossgis_osrm_car&route=;' + point;
    }

    function safeWebsiteUrl(value) {
        var clean = String(value || '').trim();
        var url;
        if (!clean) { return ''; }
        if (!/^https?:\/\//i.test(clean)) {
            clean = 'https://' + clean.replace(/^\/+/, '');
        }
        try {
            url = new window.URL(clean);
            return /^https?:$/i.test(url.protocol) ? url.href : '';
        } catch (error) {
            return '';
        }
    }

    function safeTelephoneUrl(value) {
        var clean = String(value || '').trim();
        var digits = clean.replace(/\D/g, '');
        if (!digits) { return ''; }
        return 'tel:' + (/^\+/.test(clean) ? '+' : '') + digits;
    }

    function safeEmailUrl(value) {
        var clean = String(value || '').trim();
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(clean) ? 'mailto:' + encodeURIComponent(clean) : '';
    }

    function hasValidCoordinates(lat, lng) {
        var latitude = Number(lat);
        var longitude = Number(lng);
        return isFinite(latitude) && isFinite(longitude) && latitude >= -90 && latitude <= 90 && longitude >= -180 && longitude <= 180;
    }

    PopupController.prototype.render = function (location) {
        var node;
        var title;
        var address;
        var image;
        var status;
        var contacts;
        var website;
        var navigation;
        var channels;

        if (!this.template || !this.template.content) {
            return document.createTextNode(location.title || '');
        }

        node = this.template.content.firstElementChild.cloneNode(true);
        title = node.querySelector('[data-bml-popup="title"]');
        address = node.querySelector('[data-bml-popup="address"]');
        image = node.querySelector('[data-bml-popup="image"]');
        status = node.querySelector('[data-bml-popup="status"]');
        contacts = node.querySelector('[data-bml-popup="contacts"]');
        website = node.querySelector('[data-bml-popup="website"]');
        navigation = node.querySelector('[data-bml-popup="navigation"]');

        if (title) { title.textContent = location.title || ''; }
        if (address) {
            if (this.settings.showAddress === false || this.settings.show_address === 0 || this.settings.show_address === '0') {
                address.remove();
            } else {
                address.textContent = location.address || '';
            }
        }
        if (image && location.image) {
            image.hidden = false;
            image.style.backgroundImage = 'url("' + String(location.image).replace(/"/g, '') + '")';
        }
        if (status) {
            if (location.operational_status === 'temporarily_closed') {
                status.textContent = this.strings.temporarilyClosed || 'Temporarily closed';
            } else {
                status.remove();
            }
        }

        channels = [
            ['phone', (this.settings.showPhone === false || this.settings.show_phone === 0 || this.settings.show_phone === '0') ? '' : location.phone, location.phone ? 'tel:' + location.phone : ''],
            ['website', location.website, location.website || ''],
            ['whatsapp', location.whatsapp, popupContactUrl('whatsapp', location.whatsapp)],
            ['telegram', location.telegram, popupContactUrl('telegram', location.telegram)],
            ['viber', location.viber, popupContactUrl('viber', location.viber)],
            ['facebook', location.facebook, popupContactUrl('facebook', location.facebook)],
            ['instagram', location.instagram, popupContactUrl('instagram', location.instagram)],
            ['linkedin', location.linkedin, popupContactUrl('linkedin', location.linkedin)],
            ['tiktok', location.tiktok, popupContactUrl('tiktok', location.tiktok)]
        ];

        if (contacts) {
            channels.forEach(function (channel) {
                if (!channel[1]) { return; }
                var link = document.createElement('a');
                link.className = 'bml-preview-contact-icon bml-preview-contact-icon--' + channel[0];
                link.href = channel[2] || '#';
                link.target = channel[0] === 'phone' || channel[0] === 'email' ? '' : '_blank';
                link.rel = 'noopener';
                link.setAttribute('aria-label', channel[0]);
                link.innerHTML = popupIcon(channel[0]);
                contacts.appendChild(link);
            });
        }

        if (website) { website.remove(); }

        if (navigation && this.settings.showNavigation !== false && this.settings.show_navigation !== 0 && this.settings.show_navigation !== '0' && location.lat != null && location.lng != null) {
            navigation.href = automaticNavigationUrl(location.lat, location.lng);
            navigation.innerHTML = popupIcon('navigation') + '<span>' + escapeHtml(this.strings.navigation || 'Navigation') + '</span>';
            navigation.setAttribute('aria-label', 'Navigation');
            navigation.setAttribute('title', 'Navigation');
            if (/^geo:/i.test(navigation.href)) {
                navigation.removeAttribute('target');
            }
            if (contacts) { contacts.appendChild(navigation); }
        } else if (navigation) {
            navigation.remove();
        }

        return node;
    };

    /* Map */
    function MapEngine(providerId, container, options) {
        this.providerId = providerId;
        this.container = container;
        this.options = options || {};
        this.provider = null;
    }

    MapEngine.prototype.init = function () {
        var Provider = window.BMLMapProviders && window.BMLMapProviders[this.providerId];
        var self = this;

        if (!Provider) {
            return Promise.reject(new Error('Map provider is not registered: ' + this.providerId));
        }

        this.provider = new Provider();
        this.provider.container = this.container;
        this.provider.options = this.options;

        return this.provider.whenReady().then(function () {
            self.provider.createMap(self.container, self.options.providerConfig || {}, self.options);
            return self;
        }).catch(function (error) {
            return self.initFallback(error);
        });
    };

    MapEngine.prototype.initFallback = function (error) {
        var config = this.options.providerConfig || {};
        var fallbackId = config.fallbackProvider;
        var FallbackProvider = fallbackId && window.BMLMapProviders && window.BMLMapProviders[fallbackId];
        var fallbackOptions;
        var self = this;

        if (!FallbackProvider || fallbackId === this.providerId) {
            return Promise.reject(error);
        }

        this.destroy();

        fallbackOptions = Object.assign({}, this.options, {
            providerConfig: config.fallbackConfig || {}
        });

        this.providerId = fallbackId;
        this.options = fallbackOptions;
        this.provider = new FallbackProvider();
        this.provider.container = this.container;
        this.provider.options = fallbackOptions;

        return this.provider.whenReady().then(function () {
            self.provider.createMap(self.container, fallbackOptions.providerConfig || {}, fallbackOptions);
            if (typeof fallbackOptions.onProviderFallback === 'function') {
                fallbackOptions.onProviderFallback(error, fallbackId);
            }
            return self;
        });
    };

    MapEngine.prototype.destroy = function () {
        if (this.provider) {
            this.provider.destroy();
        }
        this.provider = null;
    };

    /* Markers */
    function MarkerController(map, popup, onSelect, options) {
        this.map = map;
        this.popup = popup;
        this.onSelect = onSelect;
        this.options = options || {};
        this.locations = [];
    }

    MarkerController.prototype.setLocations = function (locations) {
        var self = this;
        this.locations = locations || [];

        if (!this.map.provider) { return; }

        this.map.provider.clearMarkers();
        this.locations.forEach(function (location) {
            self.map.provider.addMarker(
                location,
                self.options.suppressPopup ? '' : self.popup.render(location),
                self.onSelect
            );
        });
    };

    MarkerController.prototype.focus = function (id, zoom) {
        if (this.map.provider) {
            this.map.provider.focusMarker(id, zoom);
        }
    };

    MarkerController.prototype.destroy = function () {
        if (this.map.provider) {
            this.map.provider.clearMarkers();
        }
        this.locations = [];
    };

    /* Clustering */
    function ClusteringController(map) {
        this.map = map;
    }

    ClusteringController.prototype.enable = function (enabled) {
        if (!this.map.provider) { return false; }
        return this.map.provider.cluster(Boolean(enabled));
    };

    /* Viewport */
    function ViewportController(map) {
        this.map = map;
    }

    ViewportController.prototype.fit = function () {
        if (this.map.provider) {
            this.map.provider.fitBounds();
        }
    };

    ViewportController.prototype.focus = function (id, zoom) {
        if (this.map.provider) {
            this.map.provider.focusMarker(id, zoom);
        }
    };

    /* Geolocation */
    function GeolocationController(map, strings, onSuccess, onError) {
        this.map = map;
        this.strings = strings || {};
        this.onSuccess = onSuccess;
        this.onError = onError;
    }

    GeolocationController.prototype.locate = function () {
        var self = this;

        if (!navigator.geolocation) {
            if (this.onError) { this.onError(); }
            return;
        }

        navigator.geolocation.getCurrentPosition(function (position) {
            var user = {
                lat: position.coords.latitude,
                lng: position.coords.longitude
            };

            if (self.map.provider) {
                self.map.provider.setUserLocation(user, self.strings.currentLocation);
            }
            if (self.onSuccess) {
                self.onSuccess(user);
            }
        }, function () {
            if (self.onError) { self.onError(); }
        });
    };

    /* Data */
    function LocatorDataSource(root, settings, restUrl) {
        this.root = root;
        this.settings = settings || {};
        this.restUrl = restUrl;
        this.cardAbortController = null; this.markerAbortController = null; this.detailAbortController = null;
    }

    LocatorDataSource.prototype.abort = function (type) {
        var key = type + 'AbortController'; if (this[key]) { this[key].abort(); this[key] = null; }
    };

    LocatorDataSource.prototype.loadFilters = function () {
        var params = createFilterParams(this.root);
        var query = params.toString();

        return fetchJson(this.restUrl + 'filters' + (query ? '?' + query : ''));
    };

    LocatorDataSource.prototype.loadLocations = function (page, origin, perPage) {
        var self = this; this.abort('card'); if (window.AbortController) { this.cardAbortController = new AbortController(); }
        return fetchJson(this.restUrl + 'locations?' + createParams(this.root, this.settings, page || 1, origin, perPage).toString(), this.cardAbortController && this.cardAbortController.signal);
    };
    LocatorDataSource.prototype.loadMarkers = function (bounds, origin) {
        var params = new URLSearchParams(bounds); var filters = createFilterParams(this.root);
        filters.forEach(function (value, key) { params.set(key, value); });
        var search = this.root.querySelector('.bml-search-input');
        if (search && search.value.trim()) { params.set('search', search.value.trim()); }
        appendNearParams(params, this.settings, origin);
        this.abort('marker'); if (window.AbortController) { this.markerAbortController = new AbortController(); }
        return fetchJson(this.restUrl + 'locations/markers?' + params.toString(), this.markerAbortController && this.markerAbortController.signal);
    };
    LocatorDataSource.prototype.loadDetail = function (id) {
        this.abort('detail'); if (window.AbortController) { this.detailAbortController = new AbortController(); }
        return fetchJson(this.restUrl + 'locations/' + encodeURIComponent(id), this.detailAbortController && this.detailAbortController.signal);
    };

    LocatorDataSource.prototype.destroy = function () {
        this.abort('card'); this.abort('marker'); this.abort('detail');
    };

    /* Locator */
    function LocatorController(root, globals) {
        this.root = root;
        this.globals = globals || window.BMLFrontend || {};
        this.loadingMarkup = (root.querySelector('.bml-loading') || {}).outerHTML || '';
        this.settings = Object.assign({}, this.globals.settings || {}, this.instanceSettings());
        this.providerData = this.globals.provider || {};
        this.strings = this.globals.strings || {};
        this.state = { items: [], markers: [], user: null, page: 1, perPage: safePerPage(this.settings.per_page), total: 0, totalPages: 0, loading: false, loadingMore: false, selectedId: null, cardSequence: 0, markerSequence: 0, detailSequence: 0 };
        this.data = new LocatorDataSource(root, this.settings, this.globals.restUrl || '');
        this.popup = new PopupController(root, this.strings, this.settings);
        this.map = null;
        this.markers = null;
        this.clustering = null;
        this.viewport = null;
        this.geolocation = null;
        this.boundInput = null;
        this.boundChange = null;
        this.boundNear = null;
        this.boundReset = null;
        this.boundImageError = null;
        this.boundLoadMore = null;
        this.boundCardAction = null;
        this.boundDetailAction = null;
        this.boundKeydown = null;
        this.boundCardHover = null;
        this.boundCardLeave = null;
        this.boundCardFocus = null;
        this.boundCardBlur = null;
        this.boundMobileView = null;
        this.detailPanel = root.querySelector('.bml-detail-panel');
        this.detailBody = root.querySelector('.bml-detail-panel__body');
        this.detailTitle = root.querySelector('.bml-detail-panel__header h2');
        this.detailBackdrop = root.querySelector('.bml-detail-backdrop');
        this.detailTrigger = null;
    }

    LocatorController.prototype.instanceSettings = function () {
        if (!this.root || !this.root.dataset || !this.root.dataset.settings) {
            return {};
        }

        try {
            return JSON.parse(this.root.dataset.settings) || {};
        } catch (error) {
            return {};
        }
    };

    LocatorController.prototype.init = function () {
        var self = this;
        var mapElement = this.root.querySelector('.bml-map');

        if (!mapElement) {
            return Promise.reject(new Error('Locator map element is missing.'));
        }

        var suppressProviderPopup = this.root.classList.contains('bml-layout-split');
        var providerConfig = Object.assign({}, this.providerData.config || {}, { suppressPopup: suppressProviderPopup });
        if (providerConfig.fallbackConfig) {
            providerConfig.fallbackConfig = Object.assign({}, providerConfig.fallbackConfig, { suppressPopup: suppressProviderPopup });
        }

        this.map = new MapEngine(this.providerData.active || 'osm', mapElement, {
            providerConfig: providerConfig,
            onProviderFallback: function (error) {
                if (window.console && window.console.warn) {
                    window.console.warn(self.strings.providerFallback, error);
                }
            }
        });
        this.markers = new MarkerController(this.map, this.popup, function (location) {
            self.state.selectedId = String(location.id);
            self.selectCard(location.id, true);
            self.loadDetail(location.id);
        }, { suppressPopup: suppressProviderPopup });
        this.clustering = new ClusteringController(this.map);
        this.viewport = new ViewportController(this.map);
        this.geolocation = new GeolocationController(this.map, this.strings, function (user) {
            self.state.user = user;
            self.state.page = 1;
            self.load().then(function () { return self.loadMarkers(); });
        }, function () {
            self.showMessage(self.strings.geolocationError);
        });

        return this.map.init().then(function () {
            self.clustering.enable(self.providerData.config && self.providerData.config.cluster);
            self.bindEvents();
            if (self.map.provider && self.map.provider.onBoundsChanged) { self.map.provider.onBoundsChanged(debounce(function () { self.loadMarkers(); }, 300)); }
            return self.refreshFilters().then(function () {
                return self.load().then(function () { return self.loadMarkers(); });
            });
        }).catch(function (error) {
            self.showMessage(self.strings.providerError);
            throw error;
        });
    };

    LocatorController.prototype.bindEvents = function () {
        var self = this;
        var nearButton = this.root.querySelector('.bml-near-me');
        var resetButton = this.root.querySelector('.bml-reset-filters');
        var loadMoreButton = this.root.querySelector('.bml-load-more');

        this.boundInput = debounce(function (event) {
            if (event.target.matches('.bml-search-input')) {
                self.state.page = 1; self.load(); self.loadMarkers();
            }
        }, 300);

        this.boundChange = function (event) {
            if (event.target.matches('.bml-category-filter,.bml-city-filter')) {
                self.refreshFilters().then(function () {
                    self.state.page = 1;
                    return self.load().then(function () {
                        self.state.selectedId = null;
                        window.setTimeout(function () { self.loadMarkers(); }, 350);
                    });
                });
                return;
            }
            if (event.target.matches('.bml-sort-filter')) {
                self.state.page = 1;
                self.load();
            }
        };

        this.root.addEventListener('input', this.boundInput);
        this.root.addEventListener('change', this.boundChange);

        this.boundCardAction = function (event) {
            var action = event.target.closest('[data-bml-card-action]');
            var card;
            var id;
            if (!action || !self.root.contains(action)) { return; }
            card = action.closest('.bml-location-card');
            if (!card || !self.root.contains(card)) { return; }
            id = card.dataset.id;
            if (!id) { return; }
            event.preventDefault();

            if (action.dataset.bmlCardAction === 'map') {
                self.focusLocationOnMap(id);
                return;
            }

            if (action.dataset.bmlCardAction === 'details') {
                self.root.dispatchEvent(new CustomEvent('bml:location-details-request', {
                    bubbles: true,
                    detail: { id: id }
                }));
                self.loadDetail(id, action);
            }
        };
        this.root.addEventListener('click', this.boundCardAction);

        this.boundCardSelect = function (event) {
            var card = event.target.closest && event.target.closest('.bml-location-card');
            if (!card || !self.root.contains(card) || !card.dataset.id) { return; }
            if (event.target.closest('a,button,select,input,textarea')) { return; }
            self.focusLocationOnMap(card.dataset.id);
            self.loadDetail(card.dataset.id, card);
        };
        this.root.addEventListener('click', this.boundCardSelect);

        this.boundDetailAction = function (event) {
            var action = event.target.closest('[data-bml-detail-action]');
            if (!action || !self.root.contains(action)) { return; }
            if (action.dataset.bmlDetailAction === 'close') { event.preventDefault(); self.closeDetail(true); }
            if (action.dataset.bmlDetailAction === 'map' && self.state.selectedId) { event.preventDefault(); self.viewport.focus(self.state.selectedId, 16); }
        };
        this.boundKeydown = function (event) {
            var focusable; var first; var last;
            if (!self.detailPanel || self.detailPanel.hidden) { return; }
            if (event.key === 'Escape') { event.preventDefault(); self.closeDetail(true); return; }
            if (event.key !== 'Tab') { return; }
            focusable = Array.prototype.slice.call(self.detailPanel.querySelectorAll('a[href],button:not([disabled]),[tabindex]:not([tabindex="-1"])'))
                .filter(function (node) { return !node.hidden && node.offsetParent !== null; });
            if (!focusable.length) { return; }
            first = focusable[0]; last = focusable[focusable.length - 1];
            if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
            if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
        };
        this.root.addEventListener('click', this.boundDetailAction);
        this.root.addEventListener('keydown', this.boundKeydown);

        this.boundCardHover = function (event) {
            var card = event.target.closest && event.target.closest('.bml-location-card');
            if (!card || !self.root.contains(card) || !card.dataset.id) { return; }
            self.emphasizeMarker(card.dataset.id, true);
        };
        this.boundCardLeave = function (event) {
            var card = event.target.closest && event.target.closest('.bml-location-card');
            var related = event.relatedTarget;
            if (!card || !self.root.contains(card) || !card.dataset.id) { return; }
            if (related && card.contains(related)) { return; }
            self.emphasizeMarker(card.dataset.id, false);
        };
        this.boundCardFocus = function (event) {
            var card = event.target.closest && event.target.closest('.bml-location-card');
            if (!card || !self.root.contains(card) || !card.dataset.id) { return; }
            self.emphasizeMarker(card.dataset.id, true);
        };
        this.boundCardBlur = function (event) {
            var card = event.target.closest && event.target.closest('.bml-location-card');
            var related = event.relatedTarget;
            if (!card || !self.root.contains(card) || !card.dataset.id) { return; }
            if (related && card.contains(related)) { return; }
            self.emphasizeMarker(card.dataset.id, false);
        };
        this.root.addEventListener('mouseover', this.boundCardHover);
        this.root.addEventListener('mouseout', this.boundCardLeave);
        this.root.addEventListener('focusin', this.boundCardFocus);
        this.root.addEventListener('focusout', this.boundCardBlur);

        this.boundMobileView = function (event) {
            var button = event.target.closest('[data-bml-mobile-view]');
            var body;
            if (!button || !self.root.contains(button)) { return; }
            body = self.root.querySelector('.bml-locator-body');
            if (!body) { return; }
            self.setMobileView(button.dataset.bmlMobileView || 'list');
        };
        this.root.addEventListener('click', this.boundMobileView);

        this.boundImageError = function (event) {
            var image = event.target;
            var placeholder;
            if (!image || !image.matches || !image.matches('.bml-location-card__image, .bml-detail-panel__image')) { return; }
            placeholder = document.createElement('div');
            placeholder.className = image.classList.contains('bml-detail-panel__image')
                ? 'bml-detail-panel__placeholder'
                : 'bml-location-card__placeholder';
            placeholder.setAttribute('role', 'img');
            placeholder.setAttribute('aria-label', self.strings.imageUnavailable || 'Image unavailable');
            image.replaceWith(placeholder);
        };
        this.root.addEventListener('error', this.boundImageError, true);

        if (nearButton) {
            this.boundNear = function () {
                self.geolocation.locate();
            };
            nearButton.addEventListener('click', this.boundNear);
        }
        if (resetButton) {
            this.boundReset = function () {
                self.resetFilters();
            };
            resetButton.addEventListener('click', this.boundReset);
        }
        if (loadMoreButton) {
            this.boundLoadMore = function () { self.loadMore(); };
            loadMoreButton.addEventListener('click', this.boundLoadMore);
        }
    };

    LocatorController.prototype.load = function (page, append) {
        var self = this; var requestPage = page || 1; var sequence = ++this.state.cardSequence;
        append = Boolean(append);
        if (append) { this.state.loadingMore = true; this.setLoadMoreStatus(''); } else { this.state.loading = true; this.state.total = 0; this.state.totalPages = 0; this.state.items = []; this.setLoading(true); }
        if (!append) { this.clearDetail(false); }
        this.updatePagination();
        return this.data.loadLocations(requestPage, this.state.user, this.state.perPage).then(function (data) {
            if (sequence !== self.state.cardSequence) { return; }
            var pagination = data.pagination || {};
            var incoming = data.items || [];
            self.state.items = self.mergeItems(append ? self.state.items : [], incoming);
            self.state.page = Number(pagination.page) || requestPage;
            self.state.total = Math.max(0, Number(pagination.total) || 0);
            self.state.totalPages = Math.max(0, Number(pagination.totalPages) || 0);
            self.renderList();
            return self.state.items;
        }).catch(function (error) {
            if (error.name !== 'AbortError' && !append) {
                self.showMessage(self.strings.requestError);
            }
            if (error.name !== 'AbortError' && append) { self.setLoadMoreStatus(self.strings.requestError); }
        }).finally(function () {
            if (sequence === self.state.cardSequence) { self.state.loading = false; self.state.loadingMore = false; self.setLoading(false); self.updatePagination(); }
        });
    };

    LocatorController.prototype.loadMore = function () {
        if (this.state.loading || this.state.loadingMore || this.state.page >= this.state.totalPages) { return Promise.resolve(); }
        return this.load(this.state.page + 1, true);
    };

    LocatorController.prototype.mergeItems = function (existing, incoming) {
        var ids = {}; var merged = [];
        existing.concat(incoming).forEach(function (item) {
            var id = String(item && item.id);
            if (id && id !== 'undefined' && !ids[id]) { ids[id] = true; merged.push(item); }
        });
        return merged;
    };

    LocatorController.prototype.loadMarkers = function () {
        var self = this; var bounds = this.map.provider && this.map.provider.getBounds ? this.map.provider.getBounds() : null; var sequence;
        if (!bounds) { return Promise.resolve(); } sequence = ++this.state.markerSequence;
        return this.data.loadMarkers(bounds, this.state.user).then(function (data) {
            if (sequence !== self.state.markerSequence) { return; }
            self.state.markers = data.items || [];
            self.markers.setLocations(self.state.markers);
            if (self.pendingMapFocusId) {
                var focusId = self.pendingMapFocusId;
                self.pendingMapFocusId = null;
                window.setTimeout(function () { self.viewport.focus(focusId, 16); }, 0);
            }
        }).catch(function (error) {
            if (error.name !== 'AbortError') { self.showMessage(self.strings.requestError); }
        });
    };

    LocatorController.prototype.focusLocationOnMap = function (id) {
        var self = this;
        var location = this.state.items.find(function (item) { return String(item.id) === String(id); });
        var provider = this.map && this.map.provider;

        this.state.selectedId = String(id);
        this.selectCard(id, true);

        function moveToCoordinates(item) {
            if (!item || !hasValidCoordinates(item.lat, item.lng) || !provider || typeof provider.focusCoordinates !== 'function') {
                return false;
            }

            self.pendingMapFocusId = String(id);
            provider.focusCoordinates(Number(item.lat), Number(item.lng), 16);

            if (provider.markersById && provider.markersById[id]) {
                window.setTimeout(function () {
                    if (self.state.selectedId === String(id)) {
                        self.pendingMapFocusId = null;
                        self.viewport.focus(id, 16);
                    }
                }, 180);
            }

            return true;
        }

        if (moveToCoordinates(location)) {
            return Promise.resolve(location);
        }

        return this.data.loadDetail(id).then(function (detail) {
            moveToCoordinates(detail);
            return detail;
        }).catch(function (error) {
            if (error.name !== 'AbortError') { self.showMessage(self.strings.requestError); }
        });
    };

    LocatorController.prototype.usesInlineDirectoryDetail = function () {
        return false;
    };

    LocatorController.prototype.inlineDetailHost = function (id) {
        var card = this.root.querySelector('.bml-location-card[data-id="' + String(id).replace(/"/g, '') + '"]');
        if (!card) { return null; }
        var host = card.querySelector('.bml-location-card__expanded');
        if (!host) {
            host = document.createElement('div');
            host.className = 'bml-location-card__expanded';
            card.appendChild(host);
        }
        return host;
    };

    LocatorController.prototype.clearInlineDetails = function () {
        this.root.querySelectorAll('.bml-location-card__expanded').forEach(function (node) { node.remove(); });
    };

    LocatorController.prototype.loadDetail = function (id, trigger) {
        var self = this; var sequence = ++this.state.detailSequence; this.state.selectedId = id;
        if (trigger) { this.detailTrigger = trigger; }
        this.showDetailLoading();
        return this.data.loadDetail(id).then(function (detail) {
            if (sequence !== self.state.detailSequence || String(id) !== String(self.state.selectedId)) { return; }
            self.selectCard(id);
            if (hasValidCoordinates(detail.lat, detail.lng) && self.map && self.map.provider && typeof self.map.provider.focusCoordinates === 'function') {
                self.pendingMapFocusId = String(id);
                self.map.provider.focusCoordinates(Number(detail.lat), Number(detail.lng), 16);
            } else {
                self.viewport.focus(id, 16);
            }
            self.renderDetail(detail);
            return detail;
        }).catch(function (error) {
            if (error.name !== 'AbortError' && sequence === self.state.detailSequence) { self.showDetailError(); }
        });
    };

    LocatorController.prototype.showDetailLoading = function () {
        var close;
        if (this.usesInlineDirectoryDetail() && this.state.selectedId) {
            this.clearInlineDetails();
            var inlineHost = this.inlineDetailHost(this.state.selectedId);
            if (inlineHost) { inlineHost.innerHTML = '<div class="bml-inline-detail__loading">' + escapeHtml(this.strings.loadingDetails || 'Loading location details…') + '</div>'; }
            if (this.detailPanel) { this.detailPanel.hidden = true; this.detailPanel.setAttribute('aria-hidden', 'true'); }
            return;
        }
        if (!this.detailPanel || !this.detailBody) { return; }
        this.detailPanel.hidden = false; this.detailPanel.setAttribute('aria-hidden', 'false');
        if (this.detailBackdrop && this.root.classList.contains('bml-layout-split')) { this.detailBackdrop.hidden = false; }
        this.detailBody.setAttribute('aria-busy', 'true'); this.detailBody.textContent = this.strings.loadingDetails || 'Loading location details…';
        if (this.detailTitle) { this.detailTitle.textContent = this.strings.details || 'Details'; }
        close = this.detailPanel.querySelector('[data-bml-detail-action="close"]'); if (close) { close.focus(); }
    };

    LocatorController.prototype.showDetailError = function () {
        if (this.usesInlineDirectoryDetail() && this.state.selectedId) {
            var host = this.inlineDetailHost(this.state.selectedId);
            if (host) { host.innerHTML = '<div class="bml-inline-detail__error">' + escapeHtml(this.strings.detailsError || 'Could not load location details.') + '</div>'; }
            return;
        }
        if (this.detailBody) { this.detailBody.setAttribute('aria-busy', 'false'); this.detailBody.textContent = this.strings.detailsError || 'Could not load location details.'; }
    };

    LocatorController.prototype.renderDetail = function (detail) {
        var title = detail.title || this.strings.location || 'Location'; var category = detail.category && detail.category.name ? detail.category.name : '';
        var city = detail.city && detail.city.name ? detail.city.name : ''; var image = detail.image ? '<img class="bml-detail-panel__image" src="' + escapeHtml(detail.image) + '" alt="' + escapeHtml(title) + '" width="640" height="360">' : '<div class="bml-detail-panel__placeholder" role="img" aria-label="' + escapeHtml(this.strings.imageUnavailable || 'Image unavailable') + '"></div>';
        var phone = safeTelephoneUrl(detail.phone); var email = safeEmailUrl(detail.email); var website = safeWebsiteUrl(detail.website); var directions = hasValidCoordinates(detail.lat, detail.lng) ? automaticNavigationUrl(detail.lat, detail.lng) : '';
        var address = [detail.address, detail.postcode, city, detail.region, detail.country].filter(Boolean).join(', ');
        var status = detail.operational_status === 'temporarily_closed'
            ? '<p class="bml-detail-panel__status is-closed">' + escapeHtml(this.strings.temporarilyClosed || 'Temporarily closed') + '</p>'
            : (detail.operational_status === 'active' ? '<p class="bml-detail-panel__status is-active">' + escapeHtml(this.strings.active || 'Active') + '</p>' : '');
        if (this.usesInlineDirectoryDetail()) {
            this.clearInlineDetails();
            var inlineHost = this.inlineDetailHost(detail.id);
            if (inlineHost) {
                inlineHost.innerHTML =
                    (detail.hours ? '<div class="bml-inline-detail__row"><strong>' + escapeHtml(this.strings.hours || 'Hours') + '</strong><span>' + escapeHtml(detail.hours) + '</span></div>' : '') +
                    (phone ? '<div class="bml-inline-detail__row"><strong>' + escapeHtml(this.strings.call || 'Phone') + '</strong><a href="' + escapeHtml(phone) + '">' + escapeHtml(detail.phone || this.strings.call || 'Call') + '</a></div>' : '') +
                    (website ? '<div class="bml-inline-detail__row"><strong>' + escapeHtml(this.strings.visitWebsite || 'Website') + '</strong><a href="' + escapeHtml(website) + '" target="_blank" rel="noopener noreferrer">' + escapeHtml(this.strings.visitWebsite || 'Website') + '</a></div>' : '') +
                    (detail.excerpt ? '<p class="bml-inline-detail__text">' + escapeHtml(detail.excerpt) + '</p>' : '') +
                    '<div class="bml-inline-detail__actions">' +
                    (directions ? '<a href="' + escapeHtml(directions) + '" target="_blank" rel="noopener noreferrer">' + escapeHtml(this.strings.directions || 'Directions') + '</a>' : '') +
                    '<button type="button" data-bml-detail-action="map">' + escapeHtml(this.strings.showOnMap || 'Show on map') + '</button></div>';
            }
            if (this.detailPanel) { this.detailPanel.hidden = true; this.detailPanel.setAttribute('aria-hidden', 'true'); }
            return;
        }
        if (!this.detailPanel || !this.detailBody) { return; }
        if (this.detailTitle) { this.detailTitle.textContent = title; }
        this.detailBody.setAttribute('aria-busy', 'false');
        this.detailBody.innerHTML = '<div class="bml-detail-panel__media">' + image + '</div>' + status +
            (category ? '<p class="bml-detail-panel__category">' + escapeHtml(category) + '</p>' : '') +
            (address ? '<p class="bml-detail-panel__address">' + escapeHtml(address) + '</p>' : '') +
            (detail.excerpt ? '<p class="bml-detail-panel__excerpt">' + escapeHtml(detail.excerpt) + '</p>' : '') +
            (detail.content ? '<div class="bml-detail-panel__content">' + detail.content + '</div>' : '') +
            (detail.hours ? '<p class="bml-detail-panel__hours">' + escapeHtml(detail.hours) + '</p>' : '') +
            '<div class="bml-detail-panel__links">' +
            (phone ? '<a href="' + escapeHtml(phone) + '">' + escapeHtml(this.strings.call || 'Call') + '</a>' : '') +
            (email ? '<a href="' + escapeHtml(email) + '">' + escapeHtml(this.strings.email || 'Email') + '</a>' : '') +
            (website ? '<a href="' + escapeHtml(website) + '" target="_blank" rel="noopener noreferrer">' + escapeHtml(this.strings.visitWebsite || 'Visit website') + '</a>' : '') +
            (directions ? '<a href="' + escapeHtml(directions) + '" target="_blank" rel="noopener noreferrer">' + escapeHtml(this.strings.directions || 'Directions') + '</a>' : '') +
            '<button type="button" data-bml-detail-action="map">' + escapeHtml(this.strings.showOnMap || 'Show on map') + '</button></div>';
    };

    LocatorController.prototype.closeDetail = function (restoreFocus) {
        var trigger = this.detailTrigger;
        if (this.root.classList.contains('bml-layout-split')) {
            this.state.detailSequence++; this.data.abort('detail'); this.clearInlineDetails();
            if (this.detailPanel) { this.detailPanel.hidden = true; this.detailPanel.setAttribute('aria-hidden', 'true'); }
            if (this.detailBackdrop) { this.detailBackdrop.hidden = true; }
            if (this.detailBody) { this.detailBody.textContent = ''; this.detailBody.setAttribute('aria-busy', 'false'); }
            this.detailTrigger = null;
            if (restoreFocus) { if (trigger && document.contains(trigger)) { trigger.focus(); } else { this.root.focus(); } }
            return;
        }
        this.clearDetail(restoreFocus !== false);
    };
    LocatorController.prototype.clearDetail = function (restoreFocus) {
        var trigger = this.detailTrigger;
        this.state.detailSequence++; this.data.abort('detail'); this.state.selectedId = null; this.clearInlineDetails(); this.selectCard('');
        if (this.detailPanel) { this.detailPanel.hidden = true; this.detailPanel.setAttribute('aria-hidden', 'true'); }
        if (this.detailBackdrop) { this.detailBackdrop.hidden = true; }
        if (this.detailBody) { this.detailBody.textContent = ''; this.detailBody.setAttribute('aria-busy', 'false'); }
        this.detailTrigger = null;
        if (restoreFocus) { if (trigger && document.contains(trigger)) { trigger.focus(); } else { this.root.focus(); } }
    };

    LocatorController.prototype.refreshFilters = function () {
        var self = this;
        return this.data.loadFilters().then(function (data) {
            self.fillFilters(data);
            return data;
        });
    };

    LocatorController.prototype.render = function () {
        this.renderList();
    };

    LocatorController.prototype.renderList = function () {
        var self = this;
        var results = this.root.querySelector('.bml-results');
        var items = this.state.items.slice();

        if (!results) { return; }

        results.innerHTML = '';
        if (!items.length) {
            this.showMessage(this.strings.noResults);
            this.updatePagination();
            return;
        }

        items.forEach(function (location) {
            var card = document.createElement('article');
            var distance = location.distance !== null && location.distance !== undefined
                ? formatDistance(location.distance, self.settings, true)
                : '';
            var category = location.category && location.category.name ? location.category.name : '';
            var city = location.city && location.city.name ? location.city.name : location.region;
            var showAddress = self.settings.showAddress !== false && self.settings.show_address !== 0 && self.settings.show_address !== '0';
            var phoneUrl = safeTelephoneUrl(location.phone);
            var websiteUrl = safeWebsiteUrl(location.website);
            var directionsUrl = hasValidCoordinates(location.lat, location.lng)
                ? automaticNavigationUrl(location.lat, location.lng)
                : '';
            var title = location.title || self.strings.location;
            var image = location.image
                ? '<img class="bml-location-card__image" src="' + escapeHtml(location.image) + '" alt="' + escapeHtml(title) + '" loading="lazy" width="84" height="84">'
                : '<div class="bml-location-card__placeholder" role="img" aria-label="' + escapeHtml(self.strings.imageUnavailable || 'Image unavailable') + '"></div>';
            var status = location.operational_status === 'temporarily_closed'
                ? '<span class="bml-location-card__status">' + escapeHtml(self.strings.temporarilyClosed || 'Temporarily closed') + '</span>'
                : '';
            var heading = (category ? '<span class="bml-location-card__type">' + escapeHtml(category) + '</span>' : '') + status;
            var detailsLabel = self.strings.details || 'Details';
            var primaryMeta = [];
            var secondaryMeta = [];

            if (showAddress && location.address) { primaryMeta.push(location.address); }
            if (city && primaryMeta.indexOf(city) === -1) { primaryMeta.push(city); }
            if (distance) { secondaryMeta.push(distance); }
            if (location.hours) { secondaryMeta.push(location.hours); }

            card.className = 'bml-location-card';
            card.dataset.id = location.id;
            card.tabIndex = 0;
            card.setAttribute('aria-selected', String(location.id) === String(self.state.selectedId) ? 'true' : 'false');
            card.innerHTML = '<div class="bml-location-card__media">' + image + '</div>' +
                '<div class="bml-location-card__body">' +
                '<div class="bml-location-card__topline">' +
                '<h3>' + escapeHtml(title) + '</h3>' +
                (heading ? '<div class="bml-location-card__heading">' + heading + '</div>' : '') +
                '</div>' +
                (primaryMeta.length ? '<p class="bml-location-card__address">' + escapeHtml(primaryMeta.join(' · ')) + '</p>' : '') +
                (secondaryMeta.length ? '<p class="bml-location-card__meta">' + escapeHtml(secondaryMeta.join(' · ')) + '</p>' : '') +
                '<div class="bml-location-card__footer"><div class="bml-location-card__links">' +
                (phoneUrl ? '<a class="bml-location-card__link" href="' + escapeHtml(phoneUrl) + '">' + escapeHtml(self.strings.call || 'Call') + '</a>' : '') +
                (websiteUrl ? '<a class="bml-location-card__link" href="' + escapeHtml(websiteUrl) + '" target="_blank" rel="noopener noreferrer">' + escapeHtml(self.strings.visitWebsite || 'Website') + '</a>' : '') +
                (directionsUrl ? '<a class="bml-location-card__link" href="' + escapeHtml(directionsUrl) + '" target="_blank" rel="noopener noreferrer">' + escapeHtml(self.strings.directions || 'Directions') + '</a>' : '') +
                '</div><div class="bml-location-card__actions"><button type="button" class="bml-location-card__action" data-bml-card-action="map" data-location-id="' + escapeHtml(location.id) + '" aria-pressed="false">' +
                escapeHtml(self.strings.showOnMap || 'Show on map') + '</button><button type="button" class="bml-location-card__action" data-bml-card-action="details" data-location-id="' + escapeHtml(location.id) + '">' +
                escapeHtml(detailsLabel) + '</button></div></div></div>';
            results.appendChild(card);
        });
        this.updatePagination();
    };

    LocatorController.prototype.resetSelect = function (select) {
        if (!select) { return; }
        while (select.options.length > 1) {
            select.remove(1);
        }
    };

    LocatorController.prototype.fillFilters = function (data) {
        var category = this.root.querySelector('.bml-category-filter');
        var city = this.root.querySelector('.bml-city-filter');
        var selectedCategory = category ? category.value : '';
        var selectedCity = city ? city.value : '';

        this.resetSelect(category);
        this.resetSelect(city);

        (data.categories || []).forEach(function (term) {
            var option;
            if (!category) { return; }
            option = document.createElement('option');
            option.value = term.slug;
            option.textContent = term.name + ' (' + term.count + ')';
            category.appendChild(option);
        });

        (data.cities || []).forEach(function (term) {
            var option;
            if (!city) { return; }
            option = document.createElement('option');
            option.value = term.slug;
            option.textContent = term.name + ' (' + term.count + ')';
            city.appendChild(option);
        });

        if (category) {
            category.value = category.dataset.bmlFilterInitialized === '1' ? selectedCategory : (this.root.dataset.category || '');
            category.dataset.bmlFilterInitialized = '1';
        }
        if (city) {
            city.value = city.dataset.bmlFilterInitialized === '1' ? selectedCity : (this.root.dataset.city || '');
            city.dataset.bmlFilterInitialized = '1';
        }
    };

    LocatorController.prototype.resetFilters = function () {
        var self = this;
        var search = this.root.querySelector('.bml-search-input');
        var category = this.root.querySelector('.bml-category-filter');
        var city = this.root.querySelector('.bml-city-filter');

        if (search) { search.value = ''; }
        if (category && filterMode(this.root, 'category') === 'visible') { category.value = ''; }
        if (city && filterMode(this.root, 'city') === 'visible') { city.value = ''; }

        this.state.user = null;
        this.state.page = 1;
        return this.refreshFilters().then(function () {
            return self.load().then(function () { return self.loadMarkers(); });
        });
    };

    LocatorController.prototype.selectCard = function (id, scrollIntoView) {
        var selectedCard = null;
        this.root.querySelectorAll('.bml-location-card').forEach(function (card) {
            var selected = String(card.dataset.id) === String(id);
            var mapButton = card.querySelector('[data-bml-card-action="map"]');
            card.classList.toggle('is-active', selected);
            card.setAttribute('aria-selected', selected ? 'true' : 'false');
            if (selected) { selectedCard = card; }
            if (mapButton) { mapButton.setAttribute('aria-pressed', selected ? 'true' : 'false'); }
        });
        if (selectedCard && scrollIntoView && typeof selectedCard.scrollIntoView === 'function') {
            selectedCard.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
        }
    };

    LocatorController.prototype.emphasizeMarker = function (id, enabled) {
        if (!this.map || !this.map.provider || typeof this.map.provider.emphasizeMarker !== 'function') { return; }
        this.map.provider.emphasizeMarker(id, enabled !== false);
    };

    LocatorController.prototype.setMobileView = function (view) {
        var body = this.root.querySelector('.bml-locator-body');
        var value = view === 'map' ? 'map' : 'list';
        if (!body) { return; }
        body.dataset.bmlMobileActiveView = value;
        this.root.querySelectorAll('[data-bml-mobile-view]').forEach(function (button) {
            var active = button.dataset.bmlMobileView === value;
            button.classList.toggle('is-active', active);
            button.setAttribute('aria-pressed', active ? 'true' : 'false');
        });
        if (value === 'map' && this.map && this.map.provider && typeof this.map.provider.invalidateSize === 'function') {
            window.setTimeout(function () { this.map.provider.invalidateSize(); }.bind(this), 20);
        }
    };

    LocatorController.prototype.applyVisualConfig = function (settings, patch) {
        this.settings = Object.assign({}, this.settings, settings || {});
        if (patch && patch.layout) {
            this.root.classList.remove('bml-layout-split', 'bml-layout-map', 'bml-layout-cards');
            this.root.classList.add('bml-layout-' + patch.layout);
        }
    };

    LocatorController.prototype.showMessage = function (message) {
        var results = this.root.querySelector('.bml-results');
        if (results) {
            results.innerHTML = '<div class="bml-empty">' + escapeHtml(message) + '</div>';
        }
    };

    LocatorController.prototype.setLoading = function (loading) {
        var results = this.root.querySelector('.bml-results');
        this.root.setAttribute('aria-busy', loading ? 'true' : 'false');
        if (loading && results && this.loadingMarkup) {
            results.innerHTML = this.loadingMarkup;
        }
    };

    LocatorController.prototype.setLoadMoreStatus = function (message) {
        var status = this.root.querySelector('.bml-load-more-status');
        if (status) { status.textContent = message || ''; }
    };

    LocatorController.prototype.updatePagination = function () {
        var count = this.root.querySelector('.bml-result-count');
        var button = this.root.querySelector('.bml-load-more');
        var template;
        if (count) {
            template = count.dataset.bmlCountTemplate || '';
            count.textContent = template.replace('%1$d', String(this.state.items.length)).replace('%2$d', String(this.state.total));
        }
        if (button) {
            button.hidden = this.state.total === 0 || this.state.items.length >= this.state.total || this.state.page >= this.state.totalPages;
            button.disabled = this.state.loadingMore;
            button.setAttribute('aria-busy', this.state.loadingMore ? 'true' : 'false');
        }
    };

    LocatorController.prototype.destroy = function () {
        var nearButton = this.root.querySelector('.bml-near-me');
        var resetButton = this.root.querySelector('.bml-reset-filters');

        this.data.destroy();
        if (this.boundInput) {
            this.root.removeEventListener('input', this.boundInput);
        }
        if (this.boundChange) {
            this.root.removeEventListener('change', this.boundChange);
        }
        if (this.boundCardAction) {
            this.root.removeEventListener('click', this.boundCardAction);
        }
        if (this.boundCardSelect) {
            this.root.removeEventListener('click', this.boundCardSelect);
        }
        if (this.boundDetailAction) { this.root.removeEventListener('click', this.boundDetailAction); }
        if (this.boundKeydown) { this.root.removeEventListener('keydown', this.boundKeydown); }
        if (this.boundCardHover) { this.root.removeEventListener('mouseover', this.boundCardHover); }
        if (this.boundCardLeave) { this.root.removeEventListener('mouseout', this.boundCardLeave); }
        if (this.boundCardFocus) { this.root.removeEventListener('focusin', this.boundCardFocus); }
        if (this.boundCardBlur) { this.root.removeEventListener('focusout', this.boundCardBlur); }
        if (this.boundMobileView) { this.root.removeEventListener('click', this.boundMobileView); }
        if (nearButton && this.boundNear) {
            nearButton.removeEventListener('click', this.boundNear);
        }
        if (resetButton && this.boundReset) {
            resetButton.removeEventListener('click', this.boundReset);
        }
        if (this.boundImageError) {
            this.root.removeEventListener('error', this.boundImageError, true);
        }
        var loadMoreButton = this.root.querySelector('.bml-load-more');
        if (loadMoreButton && this.boundLoadMore) { loadMoreButton.removeEventListener('click', this.boundLoadMore); }
        if (this.markers) {
            this.markers.destroy();
        }
        if (this.map) {
            this.map.destroy();
        }

        this.boundInput = null;
        this.boundChange = null;
        this.boundNear = null;
        this.boundReset = null;
        this.boundImageError = null;
        this.boundLoadMore = null;
        this.boundCardAction = null;
        this.boundDetailAction = null;
        this.boundKeydown = null;
        this.boundCardHover = null;
        this.boundCardLeave = null;
        this.boundCardFocus = null;
        this.boundCardBlur = null;
        this.boundMobileView = null;
        this.map = null;
        this.markers = null;
    };

    window.BMLMapController = MapEngine;
    window.BMLLocatorController = LocatorController;
}(window, document));
