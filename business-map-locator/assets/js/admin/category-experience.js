(function () {
    'use strict';

    function slugify(value) {var map={'а':'a','б':'b','в':'v','г':'g','д':'d','е':'e','ё':'e','ж':'zh','з':'z','и':'i','й':'y','к':'k','л':'l','м':'m','н':'n','о':'o','п':'p','р':'r','с':'s','т':'t','у':'u','ф':'f','х':'h','ц':'c','ч':'ch','ш':'sh','щ':'shch','ъ':'','ы':'y','ь':'','э':'e','ю':'yu','я':'ya','і':'i','ї':'yi','є':'ye','ґ':'g','ў':'u'};return value.toString().trim().toLowerCase().split('').map(function(ch){return Object.prototype.hasOwnProperty.call(map,ch)?map[ch]:ch;}).join('').normalize('NFKD').replace(/[\u0300-\u036f]/g,'').replace(/[^a-z0-9]+/g,'-').replace(/^-+|-+$/g,'');
    }

    function initLiveSearch() {
        var input = document.getElementById('bml-category-search');
        if (!input) { return; }
        var form = input.closest('form');
        var timer = null;
        input.addEventListener('input', function () {
            clearTimeout(timer);
            timer = setTimeout(function () {
                if (form && form.requestSubmit) { form.requestSubmit(); }
                else if (form) { form.submit(); }
            }, 450);
        });
        document.addEventListener('keydown', function (event) {
            if (event.key === '/' && !/input|textarea|select/i.test(document.activeElement.tagName)) {
                event.preventDefault();
                input.focus();
            }
        });
    }

    function initEditor() {
        var form = document.querySelector('[data-bml-category-editor]');
        if (!form) { return; }
        var name = document.getElementById('bml-category-name');
        var slug = document.getElementById('bml-category-slug');
        var description = document.getElementById('bml-category-description');
        var previewName = form.querySelector('[data-bml-category-preview-name]');
        var previewDescription = form.querySelector('[data-bml-category-preview-description]');
        var saveState = form.querySelector('[data-bml-category-save-state]');
        var slugTouched = Boolean(slug && slug.value);

        function markDirty() {
            form.classList.add('is-dirty');
            if (saveState) { saveState.textContent = 'Unsaved changes'; }
        }
        function updatePreview() {
            if (previewName && name) { previewName.textContent = name.value.trim() || 'Category name'; }
            if (previewDescription && description) { previewDescription.textContent = description.value.trim() || 'Shown in map filters and location cards.'; }
        }
        if (slug) {
            slug.addEventListener('input', function () { slugTouched = slug.value.trim() !== ''; markDirty(); });
        }
        if (name) {
            name.addEventListener('input', function () {
                if (slug && !slugTouched) { slug.value = slugify(name.value); }
                updatePreview();
                markDirty();
            });
        }
        if (description) { description.addEventListener('input', function () { updatePreview(); markDirty(); }); }
        form.addEventListener('change', markDirty);
        form.addEventListener('submit', function () { form.classList.remove('is-dirty'); });
        document.addEventListener('keydown', function (event) {
            if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 's') {
                event.preventDefault();
                if (form.requestSubmit) { form.requestSubmit(); } else { form.submit(); }
            }
        });

        var observer = new MutationObserver(function () {
            var source = document.getElementById('bml-category-icon-preview');
            var target = form.querySelector('[data-bml-category-preview-icon]');
            if (source && target) { target.innerHTML = source.innerHTML; }
            markDirty();
        });
        var iconPreview = document.getElementById('bml-category-icon-preview');
        if (iconPreview) { observer.observe(iconPreview, { childList: true, subtree: true }); }
    }

    document.addEventListener('DOMContentLoaded', function () {
        initLiveSearch();
        initEditor();
    });
}());
