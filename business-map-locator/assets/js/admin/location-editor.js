(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        var form = document.getElementById('bml-location-form');
        if (!form) { return; }

        var initialSnapshot = new FormData(form);
        var dirty = false;
        var autoSaveTimer = 0;
        var autoSaving = false;
        var saveState = document.getElementById('bml-save-state');
        var saveStateText = saveState ? saveState.querySelector('span') : null;
        var savebar = document.getElementById('bml-sticky-savebar');

        function t(key, fallback) {
            return window.BMLAdmin && BMLAdmin.strings && BMLAdmin.strings[key] ? BMLAdmin.strings[key] : fallback;
        }

        function field(name) { return form.querySelector('[data-bml-field="' + name + '"]') || form.elements[name]; }
        function value(name) { var el = field(name); return el && typeof el.value === 'string' ? el.value.trim() : ''; }
        function selectedText(name) { var el = field(name); return el && el.value && el.options[el.selectedIndex] ? el.options[el.selectedIndex].text.trim() : ''; }
        function appendSubmissionAliases(data) {
            ['address', 'region', 'country', 'postcode', 'phone'].forEach(function (name) {
                data.set(name, value(name));
            });
        }

        function setSaveState(state, label) {
            if (!saveState) { return; }
            saveState.className = 'bml-save-state is-' + state;
            if (saveStateText) { saveStateText.textContent = label; }
        }

        function setDirty(next) {
            dirty = next;
            if (savebar) { savebar.hidden = !next; }
            if (next) {
                setSaveState('unsaved', t('unsavedChanges', 'Unsaved changes'));
                scheduleAutoSave();
            }
        }

        function checks() {
            return {
                name: !!value('title'),
                category: !!value('category_id'),
                city: !!value('city_id'),
                address: !!value('address'),
                coordinates: value('lat') !== '' && value('lng') !== '',
                phone: value('phone').replace(/\D/g, '').length >= 7
            };
        }

        function updateCompletion() {
            var all = checks();
            var keys = Object.keys(all);
            var done = keys.filter(function (key) { return all[key]; }).length;
            var percent = Math.round(done / keys.length * 100);
            var percentEl = document.getElementById('bml-completion-percent');
            var bar = document.getElementById('bml-completion-bar');
            if (percentEl) { percentEl.textContent = percent + '%'; }
            if (bar) { bar.style.width = percent + '%'; }
            keys.forEach(function (key) {
                var item = document.querySelector('[data-check="' + key + '"]');
                if (!item) { return; }
                item.classList.toggle('is-complete', all[key]);
                var icon = item.querySelector('.dashicons');
                if (icon) { icon.className = 'dashicons ' + (all[key] ? 'dashicons-yes-alt' : 'dashicons-marker'); }
            });
            var sectionStates = {
                basic: all.name,
                address: all.address && all.coordinates,
                classification: all.category && all.city,
                contact: all.phone
            };
            Object.keys(sectionStates).forEach(function (key) {
                var el = document.querySelector('[data-section-state="' + key + '"]');
                if (!el) { return; }
                el.textContent = sectionStates[key] ? 'Complete' : 'Needs attention';
                el.classList.toggle('has-warning', !sectionStates[key]);
            });
        }

        function updatePreview() {
            var title = document.getElementById('bml-preview-title');
            var category = document.getElementById('bml-preview-category');
            var address = document.getElementById('bml-preview-address');
            var status = document.getElementById('bml-preview-status');
            var icons = document.getElementById('bml-preview-contact-icons');
            var navigation = document.getElementById('bml-preview-navigation');
            var subtitle = document.getElementById('bml-workspace-subtitle');
            var displayTitle = value('title') || t('businessName', 'Business name');
            if (title) { title.textContent = displayTitle; }
            if (subtitle) { subtitle.textContent = displayTitle; }
            if (category) { category.textContent = selectedText('category_id'); category.hidden = !category.textContent; }
            if (address) {
                address.textContent = [selectedText('city_id'), value('address')].filter(Boolean).join(', ') || t('addressPlaceholder', 'Address will appear here');
            }
            if (status) {
                status.textContent = value('operational_status') === 'temporarily_closed' ? t('temporarilyClosed', 'Temporarily closed') : (value('operational_status') === 'hidden' ? t('hidden', 'Hidden') : t('openNow', 'Open now'));
            }
            if (icons) {
                icons.querySelectorAll('.bml-preview-contact-icon--phone').forEach(function (el) { el.remove(); });
                if (value('phone').replace(/\D/g, '').length >= 7) {
                    var phone = document.createElement('span');
                    phone.className = 'bml-preview-contact-icon bml-preview-contact-icon--phone';
                    phone.innerHTML = '<span class="dashicons dashicons-phone"></span><span>Phone</span>';
                    icons.insertBefore(phone, icons.firstChild);
                }
            }
            if (navigation && !navigation.dataset.navigationDisabled) {
                navigation.hidden = !(value('lat') && value('lng'));
            }
            document.querySelectorAll('.bml-preview-skeleton').forEach(function (el) { el.hidden = true; });
        }

        function updateCityHint() {
            var hint = document.querySelector('.bml-search-hint');
            if (!hint) { return; }
            hint.textContent = selectedText('city_id') ? 'Search area: ' + selectedText('city_id') + '.' : 'Select a city to improve search results.';
        }

        function updateAll() { updateCompletion(); updatePreview(); updateCityHint(); }

        function clearFieldError(target) {
            var wrap = target && target.closest ? target.closest('.bml-field, .bml-coordinate-details') : null;
            if (!wrap) { return; }
            wrap.classList.remove('is-invalid');
            var output = wrap.querySelector('.bml-field-error');
            if (output) { output.textContent = ''; }
        }

        function markDirty(target) {
            clearFieldError(target);
            setDirty(true);
            updateAll();
        }

        function scheduleAutoSave() {
            window.clearTimeout(autoSaveTimer);
            if (!value('title') || autoSaving) { return; }
            autoSaveTimer = window.setTimeout(autoSaveDraft, 5000);
        }

        function autoSaveDraft() {
            if (!dirty || autoSaving || !value('title')) { return; }
            autoSaving = true;
            setSaveState('saving', t('savingDraft', 'Saving draft…'));
            var data = new FormData(form);
            appendSubmissionAliases(data);
            data.set('action', 'bml_autosave_location');
            data.set('nonce', BMLAdmin.locationEditorNonce);
            data.set('status', 'draft');
            fetch(BMLAdmin.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: data })
                .then(function (response) { return response.json(); })
                .then(function (json) {
                    if (!json.success) { throw new Error(json.data && json.data.message ? json.data.message : 'Autosave failed.'); }
                    if (json.data && json.data.id) {
                        field('id').value = String(json.data.id);
                        form.dataset.locationId = String(json.data.id);
                    }
                    dirty = false;
                    if (savebar) { savebar.hidden = true; }
                    setSaveState('saved', t('draftSaved', 'Draft saved'));
                    initialSnapshot = new FormData(form);
                })
                .catch(function () { setSaveState('error', t('autosaveFailed', 'Autosave failed')); })
                .finally(function () { autoSaving = false; });
        }

        function invalidate(name, message) {
            var el = field(name);
            if (!el) { return; }
            var wrap = el.closest('.bml-field');
            if (wrap) {
                wrap.classList.add('is-invalid');
                var output = wrap.querySelector('.bml-field-error');
                if (output) { output.textContent = message; }
            }
        }

        function validatePublish() {
            form.querySelectorAll('.is-invalid').forEach(function (el) { el.classList.remove('is-invalid'); });
            var errors = [];
            [['title','Enter a location name.'],['address','Enter or find an address.']].forEach(function (item) {
                if (!value(item[0])) { invalidate(item[0], item[1]); errors.push(item[1]); }
            });
            if (!value('lat') || !value('lng')) {
                var details = document.querySelector('.bml-coordinate-details');
                if (details) { details.open = true; details.classList.add('is-invalid'); }
                errors.push('Place the marker on the map.');
            }
            if (errors.length) {
                var target = form.querySelector('.is-invalid input, .is-invalid select');
                if (target) {
                    var section = target.closest('[data-section-card]');
                    if (section) { openSection(section); }
                    target.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    window.setTimeout(function () { target.focus(); }, 250);
                }
                createNotice('Please complete the required fields before publishing.');
            }
            return !errors.length;
        }

        function createNotice(message) {
            var notices = document.getElementById('bml-editor-notices');
            if (!notices) { return; }
            notices.innerHTML = '<div class="bml-editor-notice"><span class="dashicons dashicons-info-outline"></span><span></span></div>';
            notices.querySelector('span:last-child').textContent = message;
            window.setTimeout(function () { notices.innerHTML = ''; }, 4500);
        }

        function saveLocation(draft) {
            if (!draft && !validatePublish()) { return; }
            if (autoSaving) { return; }

            autoSaving = true;
            setSaveState('saving', 'Saving...');
            if (savebar) { savebar.hidden = true; }

            var data = new FormData(form);
            appendSubmissionAliases(data);
            data.set('action', 'bml_autosave_location');
            data.set('nonce', BMLAdmin.locationEditorNonce);
            data.set('status', draft ? 'draft' : (value('status') || 'draft'));

            fetch(BMLAdmin.ajaxUrl, {
                method: 'POST',
                credentials: 'same-origin',
                body: data
            })
                .then(function (response) { return response.json(); })
                .then(function (json) {
                    if (!json.success) {
                        throw new Error(json.data && json.data.message ? json.data.message : 'Save failed.');
                    }
                    if (json.data && json.data.id) {
                        field('id').value = String(json.data.id);
                        form.dataset.locationId = String(json.data.id);
                        var url = new URL(window.location.href);
                        url.searchParams.set('id', String(json.data.id));
                        url.searchParams.delete('bml_notice');
                        window.history.replaceState({}, '', url.toString());
                    }
                    dirty = false;
                    initialSnapshot = new FormData(form);
                    setSaveState('saved', draft ? t('draftSaved', 'Draft saved') : t('saved', 'Saved'));
                    createNotice(json.data && json.data.message ? json.data.message : (draft ? 'Draft saved.' : 'Location saved.'));
                })
                .catch(function (error) {
                    setSaveState('error', t('saveFailed', 'Save failed'));
                    createNotice(error.message || 'Save failed.');
                    if (savebar) { savebar.hidden = false; }
                })
                .finally(function () { autoSaving = false; });
        }

        function openSection(section) {
            var body = section.querySelector('.bml-section-body');
            var toggle = section.querySelector('.bml-section-toggle');
            section.classList.add('is-open');
            if (body) { body.hidden = false; }
            if (toggle) { toggle.setAttribute('aria-expanded', 'true'); }
            document.dispatchEvent(new CustomEvent('bml:workspace-map-visible'));
        }

        document.querySelectorAll('[data-section-card]').forEach(function (section) {
            var key = 'bmlEditorSection:' + section.dataset.sectionCard;
            var stored = window.localStorage ? localStorage.getItem(key) : null;
            if (stored === 'open') { openSection(section); }
            if (stored === 'closed') {
                section.classList.remove('is-open');
                var body = section.querySelector('.bml-section-body');
                var toggle = section.querySelector('.bml-section-toggle');
                if (body) { body.hidden = true; }
                if (toggle) { toggle.setAttribute('aria-expanded', 'false'); }
            }
            var button = section.querySelector('.bml-section-toggle');
            if (button) {
                button.addEventListener('click', function () {
                    var willOpen = !section.classList.contains('is-open');
                    if (willOpen) { openSection(section); } else {
                        section.classList.remove('is-open');
                        section.querySelector('.bml-section-body').hidden = true;
                        button.setAttribute('aria-expanded', 'false');
                    }
                    if (window.localStorage) { localStorage.setItem(key, willOpen ? 'open' : 'closed'); }
                });
            }
        });

        form.addEventListener('input', function (event) { markDirty(event.target); });
        form.addEventListener('change', function (event) { markDirty(event.target); });
        form.addEventListener('keydown', function (event) {
            if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 's') {
                event.preventDefault();
                saveLocation(false);
                return;
            }
            if (event.key === 'Enter' && !event.target.matches('textarea, button')) {
                event.preventDefault();
                if (event.target.id === 'bml-address-search') {
                    var searchButton = document.getElementById('bml-find-address');
                    if (searchButton) { searchButton.click(); }
                }
            }
        }, true);
        form.addEventListener('submit', function (event) {
            event.preventDefault();
            var submitter = event.submitter || null;
            var draft = !!(submitter && submitter.value === 'draft');
            if (submitter && submitter.dataset && submitter.dataset.bmlSave) {
                draft = submitter.dataset.bmlSave === 'draft';
            }
            saveLocation(draft);
        });
        form.querySelectorAll('[data-bml-save]').forEach(function (button) {
            button.addEventListener('click', function () {
                saveLocation(button.dataset.bmlSave === 'draft');
            });
        });
        form.addEventListener('bml:legacy-submit', function (event) {
            var draft = Boolean(event.detail && event.detail.draft);
            if (!draft && !validatePublish()) { return; }
            if (autoSaving) { return; }

            autoSaving = true;
            setSaveState('saving', 'Saving…');
            if (savebar) { savebar.hidden = true; }

            var data = new FormData(form);
            appendSubmissionAliases(data);
            data.set('action', 'bml_autosave_location');
            data.set('nonce', BMLAdmin.locationEditorNonce);
            data.set('status', draft ? 'draft' : (value('status') || 'draft'));

            fetch(BMLAdmin.ajaxUrl, {
                method: 'POST',
                credentials: 'same-origin',
                body: data
            })
                .then(function (response) { return response.json(); })
                .then(function (json) {
                    if (!json.success) {
                        throw new Error(json.data && json.data.message ? json.data.message : 'Save failed.');
                    }
                    if (json.data && json.data.id) {
                        field('id').value = String(json.data.id);
                        form.dataset.locationId = String(json.data.id);
                        var url = new URL(window.location.href);
                        url.searchParams.set('id', String(json.data.id));
                        url.searchParams.delete('bml_notice');
                        window.history.replaceState({}, '', url.toString());
                    }
                    dirty = false;
                    initialSnapshot = new FormData(form);
                    setSaveState('saved', draft ? t('draftSaved', 'Draft saved') : t('saved', 'Saved'));
                    createNotice(json.data && json.data.message ? json.data.message : (draft ? 'Draft saved.' : 'Location saved.'));
                })
                .catch(function (error) {
                    setSaveState('error', t('saveFailed', 'Save failed'));
                    createNotice(error.message || 'Save failed.');
                    if (savebar) { savebar.hidden = false; }
                })
                .finally(function () { autoSaving = false; });
        });
        window.addEventListener('beforeunload', function (event) {
            if (!dirty || autoSaving) { return; }
            event.preventDefault(); event.returnValue = '';
        });

        document.querySelectorAll('[data-preview-focus]').forEach(function (button) {
            button.addEventListener('click', function () {
                var preview = document.getElementById('bml-live-preview-card');
                if (preview) { preview.scrollIntoView({ behavior: 'smooth', block: 'center' }); preview.classList.add('is-highlighted'); window.setTimeout(function () { preview.classList.remove('is-highlighted'); }, 900); }
            });
        });
        document.querySelectorAll('[data-open-map]').forEach(function (button) {
            button.addEventListener('click', function () {
                var lat = value('lat'), lng = value('lng');
                if (!lat || !lng) { createNotice('Place the marker on the map first.'); return; }
                window.open('https://www.openstreetmap.org/?mlat=' + encodeURIComponent(lat) + '&mlon=' + encodeURIComponent(lng) + '#map=17/' + encodeURIComponent(lat) + '/' + encodeURIComponent(lng), '_blank', 'noopener');
            });
        });
        var discard = document.querySelector('[data-discard-changes]');
        if (discard) { discard.addEventListener('click', function () { window.location.reload(); }); }

        form.querySelectorAll('[data-bml-no-address-save]').forEach(function (input) {
            input.setAttribute('autocomplete', 'off');
            input.setAttribute('autocapitalize', 'off');
            input.setAttribute('spellcheck', 'false');
        });

        form.querySelectorAll('[data-term-field]').forEach(function (wrapper) {
            var panel = wrapper.querySelector('.bml-inline-term');
            var add = wrapper.querySelector('[data-add-term]');
            var cancel = wrapper.querySelector('[data-cancel-term]');
            var create = wrapper.querySelector('[data-create-term]');
            var name = wrapper.querySelector('[data-term-name]');
            var slug = wrapper.querySelector('[data-term-slug]');
            function slugifyTerm(value) { var map={'а':'a','б':'b','в':'v','г':'g','д':'d','е':'e','ё':'e','ж':'zh','з':'z','и':'i','й':'y','к':'k','л':'l','м':'m','н':'n','о':'o','п':'p','р':'r','с':'s','т':'t','у':'u','ф':'f','х':'h','ц':'c','ч':'ch','ш':'sh','щ':'shch','ъ':'','ы':'y','ь':'','э':'e','ю':'yu','я':'ya','і':'i','ї':'yi','є':'ye','ґ':'g','ў':'u'}; return String(value || '').trim().toLowerCase().split('').map(function(ch){return Object.prototype.hasOwnProperty.call(map,ch)?map[ch]:ch;}).join('').normalize('NFKD').replace(/[\u0300-\u036f]/g,'').replace(/[^a-z0-9]+/g,'-').replace(/^-+|-+$/g,''); }
            var slugTouched = Boolean(slug && slug.value.trim());
            if (name && slug) {
                slug.addEventListener('input', function () { slugTouched = slug.value.trim() !== ''; });
                name.addEventListener('input', function () { if (!slugTouched) { slug.value = slugifyTerm(name.value); } });
            }
            var select = wrapper.querySelector('select');
            var message = wrapper.querySelector('.bml-inline-term-message');
            if (add) { add.addEventListener('click', function () { panel.hidden = false; name.focus(); }); }
            if (cancel) { cancel.addEventListener('click', function () { panel.hidden = true; message.textContent = ''; }); }
            if (create) { create.addEventListener('click', function () {
                if (!name.value.trim()) { message.textContent = 'Enter a name.'; return; }
                create.disabled = true; message.textContent = 'Creating…';
                var body = new URLSearchParams({ action:'bml_create_inline_term', nonce:BMLAdmin.locationEditorNonce, taxonomy:wrapper.dataset.taxonomy, name:name.value.trim(), slug:slugifyTerm(slug.value.trim() || name.value.trim()) });
                fetch(BMLAdmin.ajaxUrl, { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded;charset=UTF-8'}, body:body.toString() })
                    .then(function (response) { return response.json(); })
                    .then(function (json) { if (!json.success) { throw new Error(json.data && json.data.message ? json.data.message : 'Could not create term.'); } select.add(new Option(json.data.name, String(json.data.id), true, true)); panel.hidden = true; name.value = ''; slug.value = ''; message.textContent = ''; markDirty(select); })
                    .catch(function (error) { message.textContent = error.message; })
                    .finally(function () { create.disabled = false; });
            }); }
        });

        var copy = document.getElementById('bml-copy-coordinates');
        if (copy) { copy.addEventListener('click', function () { if (navigator.clipboard && value('lat') && value('lng')) { navigator.clipboard.writeText(value('lat') + ', ' + value('lng')); createNotice('Coordinates copied.'); } }); }

        updateAll();
        window.setTimeout(function () { document.querySelectorAll('.bml-map-skeleton').forEach(function (el) { el.hidden = true; }); }, 700);
    });
}());
