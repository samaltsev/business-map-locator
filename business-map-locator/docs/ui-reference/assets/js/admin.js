(function () {
    'use strict';

    var navItems = document.querySelectorAll('[data-page]');
    var pageLinks = document.querySelectorAll('[data-page-link]');
    var pages = document.querySelectorAll('.bm-page');
    var title = document.getElementById('page-title');

    function openPage(id) {
        pages.forEach(function (page) {
            page.classList.toggle('is-active', page.id === id);
        });

        navItems.forEach(function (item) {
            item.classList.toggle('is-active', item.getAttribute('data-page') === id);
        });

        var activePage = document.getElementById(id);
        if (activePage) {
            title.textContent = activePage.getAttribute('data-title') || 'Business Map Locator';
            window.scrollTo({ top: 0, behavior: 'smooth' });
            history.replaceState(null, '', '#' + id);
        }
    }

    navItems.forEach(function (item) {
        item.addEventListener('click', function () {
            openPage(item.getAttribute('data-page'));
        });
    });

    pageLinks.forEach(function (item) {
        item.addEventListener('click', function () {
            openPage(item.getAttribute('data-page-link'));
        });
    });

    var hash = window.location.hash.replace('#', '');
    if (hash && document.getElementById(hash)) {
        openPage(hash);
    }

    document.querySelectorAll('.bm-tabs button').forEach(function (button) {
        button.addEventListener('click', function () {
            button.parentElement.querySelectorAll('button').forEach(function (item) {
                item.classList.remove('is-active');
            });
            button.classList.add('is-active');
        });
    });

    document.querySelectorAll('.bm-layout-options label').forEach(function (label) {
        label.addEventListener('click', function () {
            label.parentElement.querySelectorAll('label').forEach(function (item) {
                item.classList.remove('is-selected');
            });
            label.classList.add('is-selected');
        });
    });
}());