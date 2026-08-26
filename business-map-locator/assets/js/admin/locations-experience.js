(function () {
    'use strict';

    function initLiveSearch() {
        var input = document.getElementById('bml-locations-search');
        var table = document.querySelector('[data-bml-locations-table]');
        var empty = document.querySelector('[data-bml-locations-empty-live]');
        if (!input || !table) { return; }
        var rows = Array.prototype.slice.call(table.querySelectorAll('tbody tr[data-location-search]'));
        input.addEventListener('input', function () {
            var query = input.value.trim().toLowerCase();
            var visible = 0;
            rows.forEach(function (row) {
                var show = !query || (row.getAttribute('data-location-search') || '').indexOf(query) !== -1;
                row.hidden = !show;
                if (show) { visible++; }
            });
            if (empty) { empty.hidden = visible !== 0; }
        });
        document.addEventListener('keydown', function (event) {
            if (event.key === '/' && !/input|textarea|select/i.test(document.activeElement.tagName)) {
                event.preventDefault();
                input.focus();
            }
            if (event.key.toLowerCase() === 'n' && !event.ctrlKey && !event.metaKey && !event.altKey && !/input|textarea|select/i.test(document.activeElement.tagName)) {
                window.location.href = 'admin.php?page=bml-location-edit';
            }
        });
    }

    function initSelectionCounter() {
        var table = document.querySelector('[data-bml-locations-table]');
        var counter = document.querySelector('[data-bml-selected-count]');
        if (!table || !counter) { return; }
        var checkboxes = Array.prototype.slice.call(table.querySelectorAll('tbody input[type="checkbox"][name="ids[]"]'));
        function update() {
            var selected = checkboxes.filter(function (checkbox) { return checkbox.checked; }).length;
            counter.textContent = selected === 0 ? 'No locations selected' : selected + ' selected';
        }
        table.addEventListener('change', update);
        update();
    }

    document.addEventListener('DOMContentLoaded', function () {
        initLiveSearch();
        initSelectionCounter();
    });
}());
