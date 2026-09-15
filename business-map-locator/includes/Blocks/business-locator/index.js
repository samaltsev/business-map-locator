(function (blocks, element, components, blockEditor, i18n) {
    'use strict';
    var el = element.createElement;
    var InspectorControls = blockEditor.InspectorControls;
    var PanelBody = components.PanelBody;
    var SelectControl = components.SelectControl;
    var TextControl = components.TextControl;
    var ToggleControl = components.ToggleControl;

    blocks.registerBlockType('business-map/locator', {
        edit: function (props) {
            var a = props.attributes;
            function set(name) { return function (value) { var next = {}; next[name] = value; props.setAttributes(next); }; }
            function setArea(value) { props.setAttributes({ area: value, city: '' }); }
            function setAreaMode(value) { props.setAttributes({ areaMode: value, cityMode: value }); }
            var area = a.area || a.city || '';
            var areaMode = a.areaMode || a.cityMode || 'visible';
            return el('div', { className: 'bml-block-editor-preview' },
                el(InspectorControls, {},
                    el(PanelBody, { title: i18n.__('Locator settings', 'business-map-locator'), initialOpen: true },
                        el(SelectControl, { label: i18n.__('Layout', 'business-map-locator'), value: a.layout, options: [{label:'List + map',value:'split'},{label:'Map only',value:'map'}], onChange: set('layout') }),
                        el(TextControl, { label: i18n.__('Category slug', 'business-map-locator'), value: a.category, onChange: set('category') }),
                        el(TextControl, { label: i18n.__('Area slug', 'business-map-locator'), value: area, onChange: setArea }),
                        el(SelectControl, { label: i18n.__('Category filter mode', 'business-map-locator'), value: a.categoryMode, options: [{label:'Visible',value:'visible'},{label:'Locked',value:'locked'},{label:'Hidden',value:'hidden'}], onChange: set('categoryMode') }),
                        el(SelectControl, { label: i18n.__('Area filter mode', 'business-map-locator'), value: areaMode, options: [{label:'Visible',value:'visible'},{label:'Locked',value:'locked'},{label:'Hidden',value:'hidden'}], onChange: setAreaMode }),
                        el(TextControl, { label: i18n.__('Height', 'business-map-locator'), type: 'number', value: a.height, onChange: function(v){ set('height')(parseInt(v || 620,10)); } }),
                        el(ToggleControl, { label: i18n.__('Search', 'business-map-locator'), checked: a.search, onChange: set('search') }),
                        el(ToggleControl, { label: i18n.__('Filters', 'business-map-locator'), checked: a.filters, onChange: set('filters') }),
                        el(ToggleControl, { label: i18n.__('Geolocation', 'business-map-locator'), checked: a.geolocation, onChange: set('geolocation') })
                    )
                ),
                el('div', { className: 'bml-block-editor-preview__map', style: { minHeight: a.height + 'px' } },
                    el('strong', {}, 'Business Map Locator'),
                    el('p', {}, i18n.__('Interactive map preview appears on the frontend.', 'business-map-locator')),
                    el('small', {}, 'Layout: ' + a.layout + (a.category ? ' · Category: ' + a.category : '') + (area ? ' · Area: ' + area : ''))
                )
            );
        },
        save: function () { return null; }
    });
})(window.wp.blocks, window.wp.element, window.wp.components, window.wp.blockEditor, window.wp.i18n);
