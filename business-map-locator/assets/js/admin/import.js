(function ($) {
    'use strict';

    var config = window.BMLAdmin || {};
    var form = document.getElementById('bml-import-form');
    var fileInput = document.getElementById('bml-csv-file');
    var uploadZone = document.getElementById('bml-upload-zone');
    var selectedFile = document.getElementById('bml-selected-file');
    var replaceFileButton = document.getElementById('bml-replace-file');
    var submitButton = document.getElementById('bml-import-submit');
    var helpText = document.getElementById('bml-import-help-text');
    var progress = document.getElementById('bml-import-progress');
    var stateBadge = document.getElementById('bml-import-state-badge');
    var duplicateResults = document.getElementById('bml-duplicate-results');
    var deleteButton = document.getElementById('bml-delete-duplicates');
    var pauseButton = document.getElementById('bml-import-pause');
    var cancelButton = document.getElementById('bml-import-cancel');
    var retryButton = document.getElementById('bml-import-retry');
    var resumeButton = document.getElementById('bml-import-resume');
    var newButton = document.getElementById('bml-import-new');
    var currentToken = '';
    var stopped = false;
    var lastFailedAction = null;
    var jobStartedAt = 0;
    var selectedFileObject = null;

    function request(action, data) {
        data = data || new FormData();
        data.append('action', action);
        data.append('nonce', config.importNonce || '');
        return fetch(config.ajaxUrl, {
            method: 'POST',
            credentials: 'same-origin',
            body: data
        }).then(function (response) {
            return response.json();
        }).then(function (payload) {
            if (!payload.success) {
                var error = new Error(payload.data && payload.data.message ? payload.data.message : 'Request failed.');
                error.code = payload.data && payload.data.code ? payload.data.code : 'request_failed';
                throw error;
            }
            return payload.data;
        });
    }

    function actionWithToken(action, token) {
        var data = new FormData();
        data.append('token', token || currentToken);
        return request(action, data);
    }

    function formatBytes(bytes) {
        var value = parseInt(bytes, 10) || 0;
        if (value < 1024) { return value + ' B'; }
        if (value < 1024 * 1024) { return (value / 1024).toFixed(value < 10240 ? 1 : 0) + ' KB'; }
        return (value / (1024 * 1024)).toFixed(1) + ' MB';
    }

    function formatDuration(seconds) {
        var total = Math.max(0, Math.round(seconds || 0));
        var minutes = Math.floor(total / 60);
        var remaining = total % 60;
        return String(minutes).padStart(2, '0') + ':' + String(remaining).padStart(2, '0');
    }

    function delimiterLabel(delimiter) {
        if (delimiter === ';') { return 'Semicolon (;)'; }
        if (delimiter === '\t') { return 'Tab'; }
        return 'Comma (,)';
    }

    function fileNode(key) {
        return selectedFile ? selectedFile.querySelector('[data-file="' + key + '"]') : null;
    }

    function jobNode(key) {
        return progress ? progress.querySelector('[data-job="' + key + '"]') : null;
    }

    function setText(node, value) {
        if (node) { node.textContent = value; }
    }

    function setStateBadge(state) {
        if (!stateBadge) { return; }
        var labels = {
            idle: 'Ready',
            selected: 'File selected',
            preparing: 'Validating',
            running: 'Importing',
            paused: 'Paused',
            failed: 'Needs attention',
            complete: 'Completed',
            cancelled: 'Cancelled'
        };
        stateBadge.className = 'bml-job-badge bml-job-badge--' + state;
        stateBadge.textContent = labels[state] || state;
    }

    function showSelectedFile(file) {
        selectedFileObject = file;
        if (!file) {
            if (selectedFile) { selectedFile.hidden = true; }
            if (uploadZone) { uploadZone.hidden = false; uploadZone.classList.add('bml-upload--empty'); }
            if (submitButton) { submitButton.disabled = true; }
            setStateBadge('idle');
            return;
        }
        if (uploadZone) { uploadZone.hidden = true; }
        if (selectedFile) { selectedFile.hidden = false; }
        setText(fileNode('name'), file.name);
        setText(fileNode('meta'), file.lastModified ? 'Modified ' + new Date(file.lastModified).toLocaleString() : 'Ready for validation');
        setText(fileNode('size'), formatBytes(file.size));
        setText(fileNode('encoding'), 'Not checked');
        setText(fileNode('delimiter'), 'Not checked');
        setText(fileNode('rows'), '—');
        setText(fileNode('columns'), '—');
        if (submitButton) { submitButton.disabled = false; }
        if (helpText) { helpText.textContent = 'The file is ready. Validation runs before the first batch.'; }
        setStateBadge('selected');
    }

    function applyValidatedFile(data) {
        setText(fileNode('name'), data.fileName || (selectedFileObject ? selectedFileObject.name : 'locations.csv'));
        setText(fileNode('size'), formatBytes(data.fileSize || (selectedFileObject ? selectedFileObject.size : 0)));
        setText(fileNode('encoding'), data.encoding || 'UTF-8');
        setText(fileNode('delimiter'), delimiterLabel(data.delimiter));
        setText(fileNode('rows'), String(parseInt(data.total, 10) || 0));
        setText(fileNode('columns'), String(parseInt(data.columnCount, 10) || 0));
        setText(fileNode('meta'), data.dryRun ? 'Validated for dry run' : 'Validated for import');
    }

    function setStat(name, value) {
        var node = progress ? progress.querySelector('[data-stat="' + name + '"]') : null;
        if (node) { node.textContent = String(parseInt(value, 10) || 0); }
    }

    function setButtons(state, retryable) {
        if (pauseButton) { pauseButton.hidden = state !== 'running'; }
        if (cancelButton) { cancelButton.hidden = ['running', 'paused', 'failed'].indexOf(state) === -1; }
        if (retryButton) { retryButton.hidden = state !== 'failed' || !retryable; }
        if (resumeButton) { resumeButton.hidden = state !== 'paused'; }
        if (newButton) { newButton.hidden = ['complete', 'cancelled'].indexOf(state) === -1; }
    }

    function updateTiming(processed, total, state) {
        var elapsed = jobStartedAt > 0 ? (Date.now() - jobStartedAt) / 1000 : 0;
        var eta = processed > 0 && total > processed && elapsed > 0 ? elapsed / processed * (total - processed) : 0;
        setText(jobNode('elapsed'), formatDuration(elapsed));
        setText(jobNode('eta'), state === 'complete' ? '00:00' : (eta > 0 ? formatDuration(eta) : '—'));
    }

    function updateProgress(data) {
        if (!progress) { return; }
        progress.hidden = false;
        applyValidatedFile(data);

        var total = parseInt(data.total, 10) || 0;
        var processed = parseInt(data.processed, 10) || 0;
        var percent = data.percent != null ? parseInt(data.percent, 10) : (total > 0 ? Math.min(100, Math.round(processed / total * 100)) : 0);
        var bar = progress.querySelector('.bml-import-progress__bar span');
        var barRoot = progress.querySelector('.bml-import-progress__bar');
        var status = progress.querySelector('.bml-import-progress__status');
        var log = progress.querySelector('.bml-import-log');
        var state = data.cancelled ? 'cancelled' : (data.done ? 'complete' : (data.status || 'running'));
        var remaining = Math.max(0, total - processed);

        if (bar) { bar.style.width = percent + '%'; }
        if (barRoot) { barRoot.setAttribute('aria-valuenow', String(percent)); }
        setText(jobNode('percent'), percent + '%');
        setText(jobNode('processed'), String(processed));
        setText(jobNode('total'), String(total));
        setText(jobNode('remaining'), String(remaining));
        setText(jobNode('current-row'), state === 'complete' ? '—' : String(data.currentRow || Math.min(total, processed + 1)));
        setText(jobNode('title'), data.fileName || (selectedFileObject ? selectedFileObject.name : 'CSV import'));
        updateTiming(processed, total, state);

        if (status) {
            var messages = {
                complete: 'Import completed successfully.',
                paused: 'Import paused. Committed rows are safe.',
                failed: 'The batch stopped. Review the details and retry when ready.',
                cancelled: 'Import cancelled.',
                running: 'Importing locations in safe batches.',
                processing: 'Processing the current batch.',
                prepared: 'CSV validated. Starting the first batch.'
            };
            status.textContent = messages[state] || messages.running;
        }

        ['processed', 'added', 'updated', 'skipped', 'duplicates', 'errors', 'wouldCreate', 'wouldUpdate', 'wouldSkip', 'wouldFail'].forEach(function (key) {
            setStat(key, data[key]);
        });

        var dryStats = progress.querySelector('.bml-dry-run-stats');
        if (dryStats) { dryStats.hidden = !data.dryRun; }

        var lines = [];
        (data.log || []).forEach(function (entry) { lines.push('[' + entry.level + '] ' + entry.message); });
        if (!lines.length && data.errorDetails) {
            data.errorDetails.forEach(function (error) {
                lines.push('[' + (error.code || 'import_error') + '] ' + (error.row ? 'Row ' + error.row + ': ' : '') + (error.message || 'Import error.'));
            });
        }
        if (!lines.length) { lines = data.errorMessages || []; }
        if (log) { log.textContent = lines.join('\n'); log.hidden = true; }

        var errorSummary = progress.querySelector('.bml-import-error-summary');
        var errorCount = parseInt(data.errors, 10) || 0;
        if (errorSummary) {
            errorSummary.hidden = errorCount === 0;
            setText(errorSummary.querySelector('[data-error="count"]'), String(errorCount));
        }

        setStateBadge(state);
        setButtons(state, !!data.retryable);
    }

    function fail(error, retryAction) {
        lastFailedAction = retryAction || lastFailedAction;
        if (progress) {
            progress.hidden = false;
            var status = progress.querySelector('.bml-import-progress__status');
            if (status) { status.textContent = error.message; }
        }
        setStateBadge('failed');
        setButtons('failed', error.code === 'import_batch_retryable');
    }

    function processBatch(token) {
        if (stopped) { return Promise.resolve(); }
        currentToken = token;
        setStateBadge('running');
        setButtons('running');
        return actionWithToken('bml_process_import', token).then(function (result) {
            updateProgress(result);
            if (!result.done && result.status !== 'paused') { return processBatch(token); }
            return result;
        }).catch(function (error) {
            fail(error, error.code === 'import_batch_retryable' ? function () {
                return actionWithToken('bml_resume_import', token).then(function (result) {
                    updateProgress(result);
                    return !result.done && result.status !== 'paused' ? processBatch(token) : result;
                });
            } : null);
            throw error;
        });
    }

    function resetImport() {
        stopped = false;
        currentToken = '';
        lastFailedAction = null;
        jobStartedAt = 0;
        selectedFileObject = null;
        if (form) { form.reset(); }
        if (fileInput) { fileInput.value = ''; }
        if (progress) { progress.hidden = true; }
        showSelectedFile(null);
        document.querySelectorAll('.bml-mode-card').forEach(function (card, index) { card.classList.toggle('is-active', index === 0); });
    }

    if (fileInput) {
        fileInput.addEventListener('change', function () { showSelectedFile(fileInput.files && fileInput.files[0] ? fileInput.files[0] : null); });
    }

    if (uploadZone) {
        uploadZone.addEventListener('click', function (event) { if (event.target.tagName !== 'LABEL' && fileInput) { fileInput.click(); } });
        uploadZone.addEventListener('keydown', function (event) { if ((event.key === 'Enter' || event.key === ' ') && fileInput) { event.preventDefault(); fileInput.click(); } });
        ['dragenter', 'dragover'].forEach(function (name) { uploadZone.addEventListener(name, function (event) { event.preventDefault(); uploadZone.classList.add('is-dragover'); }); });
        ['dragleave', 'drop'].forEach(function (name) { uploadZone.addEventListener(name, function (event) { event.preventDefault(); uploadZone.classList.remove('is-dragover'); }); });
        uploadZone.addEventListener('drop', function (event) {
            var files = event.dataTransfer && event.dataTransfer.files;
            if (!files || !files.length || !fileInput) { return; }
            try { var transfer = new DataTransfer(); transfer.items.add(files[0]); fileInput.files = transfer.files; } catch (ignore) {}
            showSelectedFile(files[0]);
        });
    }

    if (replaceFileButton) { replaceFileButton.addEventListener('click', function () { if (fileInput) { fileInput.click(); } }); }
    if (newButton) { newButton.addEventListener('click', resetImport); }

    document.querySelectorAll('.bml-mode-card input').forEach(function (input) {
        input.addEventListener('change', function () {
            document.querySelectorAll('.bml-mode-card').forEach(function (card) { card.classList.toggle('is-active', !!card.querySelector('input:checked')); });
        });
    });

    if (form) {
        form.addEventListener('submit', function (event) {
            event.preventDefault();
            if (!selectedFileObject && (!fileInput || !fileInput.files.length)) { return; }
            stopped = false;
            currentToken = '';
            lastFailedAction = null;
            jobStartedAt = Date.now();
            if (submitButton) { submitButton.disabled = true; }
            if (progress) { progress.hidden = false; }
            setStateBadge('preparing');
            setText(progress ? progress.querySelector('.bml-import-progress__status') : null, (config.strings && config.strings.importPreparing) || 'Checking CSV...');
            setButtons('running');
            var data = new FormData();
            data.append('csv', selectedFileObject || fileInput.files[0]);
            var mode = form.querySelector('input[name="import_mode"]:checked');
            if (mode && mode.value === 'dry') { data.append('dry_run', '1'); }
            request('bml_prepare_import', data).then(function (prepared) {
                currentToken = prepared.token;
                updateProgress(Object.assign({ processed: 0, added: 0, updated: 0, skipped: 0 }, prepared));
                return processBatch(prepared.token);
            }).catch(function (error) { fail(error); }).finally(function () {
                if (submitButton && !currentToken) { submitButton.disabled = false; }
            });
        });
    }

    if (pauseButton) {
        pauseButton.addEventListener('click', function () {
            if (!currentToken) { return; }
            stopped = true;
            pauseButton.disabled = true;
            actionWithToken('bml_pause_import').then(updateProgress).catch(fail).finally(function () { pauseButton.disabled = false; });
        });
    }

    if (cancelButton) {
        cancelButton.addEventListener('click', function () {
            if (!currentToken) { return; }
            stopped = true;
            cancelButton.disabled = true;
            actionWithToken('bml_cancel_import').then(updateProgress).catch(fail).finally(function () { cancelButton.disabled = false; });
        });
    }

    if (resumeButton) {
        resumeButton.addEventListener('click', function () {
            if (!currentToken) { return; }
            stopped = false;
            resumeButton.disabled = true;
            actionWithToken('bml_resume_import').then(function (result) {
                updateProgress(result);
                return !result.done ? processBatch(currentToken) : result;
            }).catch(fail).finally(function () { resumeButton.disabled = false; });
        });
    }

    if (retryButton) {
        retryButton.addEventListener('click', function () {
            if (!lastFailedAction) { return; }
            retryButton.disabled = true;
            Promise.resolve(lastFailedAction()).finally(function () { retryButton.disabled = false; });
        });
    }

    $(document).on('click', '[data-toggle-log]', function () {
        var log = progress ? progress.querySelector('.bml-import-log') : null;
        if (log) { log.hidden = !log.hidden; this.textContent = log.hidden ? 'View details' : 'Hide details'; }
    });

    function renderDuplicates(data) {
        if (!duplicateResults) { return; }
        var html = '<div class="bml-import-stats"><div><small>Total locations</small><strong>' + data.total + '</strong></div><div><small>Duplicate groups</small><strong>' + data.groups + '</strong></div><div><small>Extra duplicate records</small><strong>' + data.extra + '</strong></div></div>';
        if (!data.groups) { html += '<div class="bml-duplicate-success">No exact duplicates found.</div>'; }
        else {
            html += '<div class="bml-duplicate-list">';
            (data.duplicates || []).forEach(function (group) {
                html += '<div class="bml-duplicate-group"><strong>Keep ID ' + group.keepId + '</strong><ul>';
                group.items.forEach(function (item) { html += '<li>#' + item.id + ' - ' + $('<div>').text(item.title).html() + ' - ' + $('<div>').text(item.address).html() + ' (' + item.lat + ', ' + item.lng + ')</li>'; });
                html += '</ul></div>';
            });
            html += '</div>';
        }
        duplicateResults.innerHTML = html;
        if (deleteButton) { deleteButton.hidden = !data.extra; }
    }

    $('#bml-check-duplicates').on('click', function () {
        var button = this;
        button.disabled = true;
        duplicateResults.innerHTML = '<p>Checking all locations...</p>';
        request('bml_scan_duplicates').then(renderDuplicates).catch(function (error) { duplicateResults.textContent = error.message; }).finally(function () { button.disabled = false; });
    });

    $('#bml-delete-duplicates').on('click', function () {
        if (!window.confirm((config.strings && config.strings.duplicateConfirm) || 'Delete duplicates?')) { return; }
        var button = this;
        button.disabled = true;
        request('bml_delete_duplicates').then(renderDuplicates).catch(function (error) { duplicateResults.textContent = error.message; }).finally(function () { button.disabled = false; });
    });
}(jQuery));

/* Import / Export Experience — beta21 */
(function () {
    'use strict';
    var root = document.querySelector('[data-bml-transfer-page]');
    if (!root) { return; }

    var tabs = root.querySelectorAll('[data-transfer-tab]');
    var panels = root.querySelectorAll('[data-transfer-panel]');

    function activate(name) {
        tabs.forEach(function (tab) {
            tab.classList.toggle('is-active', tab.getAttribute('data-transfer-tab') === name);
            tab.setAttribute('aria-selected', tab.classList.contains('is-active') ? 'true' : 'false');
        });
        panels.forEach(function (panel) {
            var active = panel.getAttribute('data-transfer-panel') === name;
            panel.hidden = !active;
            panel.classList.toggle('is-active', active);
        });
        try { window.sessionStorage.setItem('bml-transfer-tab', name); } catch (ignore) {}
    }

    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () { activate(tab.getAttribute('data-transfer-tab')); });
    });

    var initial = 'import';
    try { initial = window.sessionStorage.getItem('bml-transfer-tab') || initial; } catch (ignore) {}
    if (window.location.hash && root.querySelector('[data-transfer-tab="' + window.location.hash.substring(1) + '"]')) {
        initial = window.location.hash.substring(1);
    }
    activate(initial);

    var fieldInput = root.querySelector('[data-bml-export-form] input[name="fields"]');
    var presets = {
        full: 'external_id,title,address,city,category,region,country,postcode,lat,lng,phone,email,website,hours,status,operational_status,visible',
        basic: 'external_id,title,address,city,category,lat,lng,status',
        contact: 'external_id,title,address,city,phone,email,website,status'
    };
    root.querySelectorAll('[data-export-preset]').forEach(function (button) {
        button.addEventListener('click', function () {
            var preset = button.getAttribute('data-export-preset');
            if (fieldInput && presets[preset]) { fieldInput.value = presets[preset]; }
            root.querySelectorAll('[data-export-preset]').forEach(function (item) { item.classList.toggle('is-active', item === button); });
        });
    });
})();
